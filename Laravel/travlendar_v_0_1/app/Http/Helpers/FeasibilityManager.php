<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\DB;
use App\Http\Helpers\QueryHelper as Query;
use App\Models\Travel;
use App\Models\Event;
use App\User;
use stdClass;

/**
* FeasibilityManager class
* 
* This class provides utility methods to check the validity of the schedule and obtain information
* about conflicts with other events, violation of preferences and logical inconsistencies within an event.
* 
*/
class FeasibilityManager{

    /**
    * Gets schedule adjustments to fit an Event into the Schedule
    *
    * Given the event that has to be added to the schedule, this method provides the 
    * 
    * @param an Event $event (App\Models\Event) 
    *
    * @return a StdClass object containing the adjustements to be applied in the following structure
    *          { id0 : [lower, upper], id1 : [lower, upper], ...}.
    *           ID -1 refers to the current Event.
    *           lower and upper are unix epoch timestamps.
    */
    public static function getScheduleAdjustements(Event $event){

        // Get array of the identifiers of the Events that cause conflicts
        $conflictIds = array_map(function($idObj) {
            return $idObj->id;
        }, FeasibilityManager::getConflicts($event));

        $nFixedConflicts = Event::doesntHave('flexibleInfo')->find($conflictIds)->count();

        // If no Event in the conflicts is flexbile there are no valid solutions 
        if($event->flexibleInfo === null && $nFixedConflicts > 0){
            return json_decode('{}');
        }

        // Get conflicting Events 
        $conflicts = Event::findMany($conflictIds)->all();

        $events = FeasibilityManager::findEventsInBounds($conflicts);
        
        array_push($events, $event);
        // Get CSP variables
        $CSPVariables = FeasibilityManager::prepareForCSP($events);

        // Test environment needs a different script path
        if (env('APP_ENV') === 'testing') {
            $solutions = FeasibilityManager::solveCSP($CSPVariables, 300, "app/Http/Helpers/externalScripts/");
        } else {
            $solutions = FeasibilityManager::solveCSP($CSPVariables, 300, "../app/Http/Helpers/externalScripts/");
        }

        return json_decode($solutions);

    }


    private static function findEventsInBounds(Array $conflicts){
        if(empty($conflicts)){
            return [];
        }
        $user = auth()->user();
        $min = array_reduce($conflicts,function($A,$B){
            return $A->start < $B->start ? $A : $B;
        }, $conflicts[0])->start;
        $max = array_reduce($conflicts,function($A,$B){
            return $A->end > $B->end ? $A : $B;
        }, $conflicts[0])->end;

        $events = $user->events
          ->where('start', '>=', $min)
          ->where('end', '<=', $max)
          ->load('travel', 'flexibleInfo')->all();

        return $events;
    }

    /**
    * Solves CSP Problem
    *
    * This method calls a python script used to solve the CSP problems
    *
    * @param Array $variables an array of stdClass objects that are the variables of the CSP
    * @param integer $slot granularity of the accepted values for the variables
    * @param string $path path to the python script
    *
    * @return a string containing the adjustements to be applied in the following structure
    *          "{ id0 : [lower, upper], id1 : [lower, upper], ...}".
    *           ID -1 refers to the current Event.
    *           lower and upper are unix epoch timestamps.
    */
    public static function solveCSP(Array $variables, $slot, $path){
        $command = 'python3 '. $path .'ScheduleCSP.py ';
        
        $argv0 = escapeshellarg($slot);
        $argv1 = escapeshellarg(json_encode($variables));

        // Call external script
        $result = shell_exec($command . $argv0 . " " . $argv1 . " 2>&1");

        return $result;
    }

    /**
    * Create the CSP representation of the conflicts.
    *
    * Convert the Array of Event objects to their CSP representation
    *
    * @param Array $conflicts an array of Event objects
    *
    * @return an array of stdClass objects that are the variables of the CSP
    */
    private static function prepareForCSP(Array $conflicts){
        $CSPVariables = array();

        foreach ($conflicts as $conflict) {
            $var = new stdClass();
            $var->lower = $conflict->start;
            $var->upper = $conflict->end;
            $var->id =  $conflict->id;
            if($conflict->flexibleInfo !== null){
                $var->duration = $conflict->flexibleInfo->duration;
            } else {
                $var->duration = $conflict->end - $conflict->start;
            }
            if($conflict->travel !== null){
                $var->travel = $conflict->travel->duration;
            } else {
                $var->travel = 0;
            }

            array_push($CSPVariables, $var);
        }

        return $CSPVariables;
    }

    /**
    * Get the conflicts
    *
    * Get the identifiers of the events that cause conflict with the provided event
    *
    * @param Event $event 
    *
    * @return an array of integers
    */
    public static function getConflicts(Event $event){
        
        $userId = auth()->id();
        $id = $event->id;

        $flexibleInfo = $event->flexibleInfo;
        $travel = $event->travel;

        if($flexibleInfo !== null){
            $start = $flexibleInfo->lowerBound;
            $end = $flexibleInfo->upperBound;
        } else {
            $start = $event->start;
            $end = $event->end;
        }

        if($travel !== null){
            $start -= $travel->duration;
        }

        $conflicts = DB::select(
            Query::$eventAddConflictQuery, 
            ['newEventId'    => $id,
             'currentUserId' => $userId,
             'newEventstart' => $start,
             'newEventEnd'   => $end]
        );

        return $conflicts;
    }

    /**
    * Check if a Travel respects the User's preferences
    *
    * @param Travel $travel the Travel to be checked
    * @param User $user the User whose preferences have to be respected
    *
    * @return boolean whether the preferences are respected or not
    */
    public static function checkTravelPreferences(Travel $travel, User $user){
        if($user->preferences !== null){
            $preferences = (array) json_decode($user->preferences);
            $meanPreferences = $preferences[$travel->mean];
            return ($meanPreferences->active && $travel->distance < $meanPreferences->maxDistance);
        } else {
            return true;
        }
    }


    /**
    * Checks the feasibility of a number of Events
    *
    * This method checks the conflicts with other events, violation of preferences and logical
    * inconsistencies within an event.
    *
    * @param Array $events the events to be checked
    *
    * @return Array with the following structure ['result' => boolean, 'errors'=> Array]
    */
    public static function checkFeasibility(Array $events){
        $user = auth()->user();

        $errors = array();
        
        foreach ($events as $event) {

            if ( $event->end - $event->start <= 0){
                array_push(
                    $errors, 
                    ['event' => $event, 'cause' => 'Event data not valid', 'details' => 'end before start']
                );
                break;
            }
            $efi = $event->flexibleInfo;
            if ( $efi !== null && ($efi->upperBound - $efi->lowerBound) < $efi->duration){
                array_push(
                    $errors, 
                    ['event' => $event, 'cause' => 'Event data not valid', 'details' => 'flexible bounds too strict']
                );
                break;
            }
            if ( $efi !== null && ($efi->lowerBound < $event->start || $efi->upperBound > $event->end)) {
                array_push(
                    $errors, 
                    ['event' => $event, 'cause' => 'Event data not valid', 'details' => 'flexible bounds do not respect fixed bounds']
                );
                break;
            }
            if ($event->travel !== null && !FeasibilityManager::checkTravelPreferences($event->travel, $user)) {
                array_push(
                    $errors,
                    ['event' => $event, 'cause' => 'Preferences not satisfied', 'details' => null]
                );
                break;
            }
            $conflicts = FeasibilityManager::getConflicts($event);

            if (!count($conflicts) == 0){
                array_push(
                    $errors,
                    ['event' => $event, 'cause' => 'Schedule conflict', 'details' => $conflicts]
                );
            }
        }


        return ['result' => (count($errors) == 0),  'errors' => $errors];


    }



}
