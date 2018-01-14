<?php 

namespace App\Http\Controllers\api\v1;

use App\Http\Interfaces\MapsInterface;
use App\Http\Helpers\FeasibilityManager;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\RepetitiveEvent;
use App\Models\FlexibleEvent;
use InvalidArgumentException;
use Illuminate\Http\Request;
use App\Http\Helpers\UUID;
use App\Models\Travel;
use App\Models\Event;
use ErrorException;
use Carbon\Carbon;
use App\User;

/**
* ScheduleController class
*
* This class is a controller that provides methods for handling the routes/endpoints 
* associated with schedule management.
* More information about such routes are in the routes/api.php file.
* This controller provides methods to manage resources such as 
* Standard Events, Flexible Events, Repetitive Events and Travels.
* It allows creation, modification and deletion of such resources.
* It also provides utilities functions that provide other possibily useful information. 
*/
class ScheduleController extends Controller {

  /**
  * Handles the request for the listing of Event resources belonging to the logged in User.
  *
  * This method handles a GET request sent to the api/v1/event endpoint and provides a list of Events present 
  * in the user schedule within specified bounds.
  *
  * @param a Request object $request (Illuminate\Http\Request) containing as url parameters
  *      {
  *         'from'   => (integer) unix epoch specifying the lower bound of the list. Required.
  *         'to'     => (integer) unix epoch specifying the upper bound of the list. Required.
  *      }
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message' => (string) response message.
  *         'events'  => (array)  an Array of Event objects.
  *     }
  */
  public function index(Request $request){

    // Validate the request
    $validatedData = $request->validate([
      'from' => 'required|integer|min:0',
      'to' => 'required|integer|min:0'
    ]);

    // Get currently logged user
    $user = auth()->user();

    // Get events within the bounds
    $events = $user->events
              ->where('start', '>=', $request->from)
              ->where('start', '<=', $request->to)
              ->load('travel', 'flexibleInfo', 'repetitiveInfo');

    // Remove keys from array
    $tmp = [];
    foreach ($events as $key => $value) {
      array_push($tmp, $value);
    }

    $message = 'Request successful';
    return response()->json(array('message' => $message, 'events' => $tmp), 200, [], JSON_NUMERIC_CHECK);

  }

  /**
  * Handles the request for the listing of Days with at least one event belonging to the logged in User.
  *
  * This method handles a GET request sent to the api/v1/days endpoint and provides a list of Dates that
  * contain at least one event.
  *
  * @param a Request object $request (Illuminate\Http\Request) containing as url parameters
  *      {
  *         'from'   => (integer) unix epoch specifying the lower bound of the list. Required.
  *         'to'     => (integer) unix epoch specifying the upper bound of the list. Required.
  *         'epoch'  => (boolean) specifies if the resulting dates are requested in epoch format. Required.
  *         'gmt'    => (integer) specifies the requested timezone as displacement from Greenwhich Mean Time. Required.
  *      }
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message' => (string) response message.
  *         'days'    => (array)  an Array of dates in string or epoch format.
  *         'timezone'=> (string) the requested timezone as PHP supported representation.
  *     }
  */
  public function getDaysWithEvents(Request $request){

    // Validates the request
    $validatedData = $request->validate([
      'from'  => 'required|integer|min:0',
      'to'    => 'required|integer|min:0',
      'epoch' => 'required|boolean',
      'gmt'   => 'required|integer'
    ]);

    // Get currently logged user
    $user = auth()->user();

    // Get events within the bounds
    $events = $user->events
              ->where('start', '>=', $request->from)
              ->where('start', '<=', $request->to)
              ->load('travel', 'flexibleInfo', 'repetitiveInfo');


    // Converts GMT displacement representation to PHP supported representation
    try{
      $timezone = Carbon::now($request->get('gmt')+1)->tzName;
    } catch (InvalidArgumentException $e){
      return response()->json(array('message' => 'Invalid timezone'), 400);
    }

    // Get days from events

    $days = array();

    foreach ($events as $event) {
      $day = Carbon::createFromTimestamp($event->start, $timezone)->setTime(0, 0, 0);

      if($request->get('epoch')){
        $day = $day->timestamp;
      } else {
        $day = $day->toDateString();
      }

      array_push($days, $day);
    }
    
    // Remove duplicates
    $uniqueDays = array_values(array_unique($days));

    $message = 'Request successful';
    return response()->json(array('message' => $message, 'days' => $uniqueDays, 'timezone' => $timezone), 200);
    
  }

  /**
  * Handles the request for the creation of a new resource in the User's schedule
  *
  * This method handles a POST request sent to the api/v1/event endpoint and allows the creation 
  * of several types of resources: Standard Event, Flexible Event, Repetitive Event, Travel.
  * The success of the request is conditioned (among other things) by whether the creation of 
  * the new resource causes an unfeasible schedule.
  * 
  * 
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *         'repetitive'   => (boolean) whether the event to be created is repetitive. Required.
  *      }
  *         also other parameters according to the type of event to be created and specified in 
  *         the respective methods
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message'        => (string) response message.
  *         'feasibility'    => (array) an Array containing the details of the feasibility check. 
  *                             (if feasibility check fails)
  *         OR
  *         'events'         => (array) the request timezone as PHP supported representation.
  *                             (if creation is succesful)
  *     }
  */
  public function store(Request $request){
    
    // Validates the request
    $validatedData = $request->validate([
        'repetitive'   =>  'boolean|required',
    ]);

    try{

      // Starts a database transaction
      DB::beginTransaction();

      // Creates the event and applies the specified adjutements to the schedule
      if( $request->get('repetitive')) {
        $newEvents = $this->createRepetitiveEvent($request);
      } else {
        $newEvent = $this->createSingleEvent($request);
        $newEvents = Array($this->applyAdjustements($request, $newEvent));
      }

      // Check feasibility of the schedule
      $feasibility = FeasibilityManager::checkFeasibility($newEvents);

      if(!$feasibility['result']){
        
        // If not feasible rollback the database;
        DB::rollBack();

        // Return error message and feasibility informations
        $message = 'Feasibility check failed';
        return response()->json(array('message' => $message, 'feasibility' => $feasibility), 400);
      }

      // Commit the changes to the database
      DB::commit();

      $message = 'Event creation successful';

      return response()->json(array('message' => $message, 'events' => $newEvents), 200, [], JSON_NUMERIC_CHECK);

    } catch (QueryException $e) {

      // If there were problems with the insert rollback the database;
      DB::rollBack();
            return $e;

      $message = 'Event creation failed';
      return response()->json(array('message' => $message), 500);

    } catch (ErrorException $e) {

      // If there were errors rollback the database;
      DB::rollBack();
      return $e;
      $message = 'Event creation failed';
      return response()->json(array('message' => $message), 500);
    }

  }

  /**
  * Applies of the adjustments specified during the Event creation process
  * 
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *         'adjustements' => (object) the adjustements to be applied structured as follows
  *          { id0 : [lower, upper], id1 : [lower, upper], ...}.
  *           ID -1 refers to the current Event.
  *           lower and upper are unix epoch timestamps.
  *           Required.
  *      }
  * @param an Event object $event (App\Models\Event)
  *
  * @return the updated Event
  *
  */
  private function applyAdjustements(Request $request, Event $event){

      // Validate the request
      $validatedData = $request->validate([
        'adjustements' => 'nullable',
      ]);

      // Get currently logged user
      $user = auth()->user();

      // Modifies the Events in storage with the updated information
      foreach($request->get('adjustements') as $id => $bounds) {
        if($id === -1){
          $id = $event->id;
        }
        $adjEvent = $user->events->where('id', $id)->first();
        if($adjEvent->flexibleInfo !== null){
          $flexibleInfo = $adjEvent->flexibleInfo;
          $flexibleInfo->lowerBound = $bounds[0];
          $flexibleInfo->upperBound = $bounds[1];
          $flexibleInfo->save();
        }
      }

      return $user->events->where('id', $event->id)->first();
  }

  /**
  * Creates a Travel associated with an Event
  * 
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *         'travelDuration'   =>  (integer) the duration of the Travel in seconds. Required.
  *         'startLongitude'   =>  (float)   the longitude of the start location of the Travel in decimal format (9,6). *                                          Required.
  *         'startLatitude'    =>  (float)   the latitude of the start location of the Travel in decimal format (9,6).
  *                                          Required.
  *         'mean'             =>  (string)  the mean of transport (e.g. "driving", "trainsit"...). Required.
  *         'distance'         =>  (integer) the distance of travel in meters. Required.
  *      }
  * @param an Event object $event (App\Models\Event)
  *
  * @return Travel
  *
  */
  private function createTravel(Request $request, Event $event){

    // Validate the request
    $validatedData = $request->validate([
        'travelDuration'   =>  'required|integer|min:0',
        'startLongitude'   =>  'required|numeric',
        'startLatitude'    =>  'required|numeric',
        'mean'             =>  'required|string',
        'distance'         =>  'required|integer|min:0'
    ]);

    // Create a new Travel object
    $newTravel = new Travel();

    $newTravel->build(
      $event->id,
      $request->get('startLongitude'),
      $request->get('startLatitude'),
      $request->get('distance'),
      $request->get('travelDuration'),
      $request->get('mean'),
      null
    );

    // Stores the newly create resource
    $newTravel->save();

    return $newTravel;
  }

  /**
  * Creates a Repetitive Event
  * 
  * Creates a set of Standard Events for each repetition of the Repetitive Event according to the frequency of repetition
  * and the upper limit of the repetitions.
  *
  *
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *         'frequency'       =>  (integer) the elapsing time between each repetition of the Event. Required.
  *         'until'           =>  (integer) the unix epoch specifying the upper limit of the repetitions. Required.
  *      }
  *
  * @return Array of Events objects
  *
  */
  private function createRepetitiveEvent(Request $request){

      // Validate the data
      $validatedData = $request->validate([
        'frequency'  =>  'required|string',
        'until'      =>  'required|integer|min:0',
      ]);

      $start = $request->get('start');
      $end = $request->get('end');
      $until = $request->get('until');
      $frequency = $request->get('frequency');
      
      $groupId = UUID::v4();

      $newEvents = array();

      // Create and store a new Event for each repetition
      while ($start <= $until) {

        // Create a new RepetitiveEvent object
        $repetitiveInfo = new RepetitiveEvent();

        $newEvent = $this->createSingleEvent($request);

        $repetitiveInfo->build(
          $newEvent->id,
          $groupId,
          $frequency,
          $until
        );

        // Stores the newly create resource
        $repetitiveInfo->save();

        // Load ORM relationships
        $newEvent->load('travel', 'flexibleInfo', 'repetitiveInfo');

        array_push($newEvents, $newEvent);

        switch ($frequency) {
          case 'day':
            $start = Carbon::createFromTimestamp($start)->addDay()->timestamp;
            $end = Carbon::createFromTimestamp($end)->addDay()->timestamp;
            break;
          case 'week':
            $start = Carbon::createFromTimestamp($start)->addWeek()->timestamp;
            $end = Carbon::createFromTimestamp($end)->addWeek()->timestamp;
            break;
          case 'month':
            $start = Carbon::createFromTimestamp($start)->addMonth()->timestamp;
            $end = Carbon::createFromTimestamp($end)->addMonth()->timestamp;
            break;
          case 'year':
            $start = Carbon::createFromTimestamp($start)->addYear()->timestamp;
            $end = Carbon::createFromTimestamp($end)->addYear()->timestamp;
            break;
          default:
            return $newEvents;
            break;
        }


        // Updates the request object with the next repetitions information
        $request->merge(array('start' => $start));
        $request->merge(array('end' => $end));
        if($request->get('flexible')){
          $request->merge(array('lowerBound' => $start));
          $request->merge(array('upperBound' => $end));
        }

      }

      return $newEvents;
  }

  /**
  * Creates a Flexible Event
  *
  *
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *         'lowerBound'  =>  (integer) the unix epoch specifying the effective start of the Flexible Event. Required.
  *         'upperBound'  =>  (integer) the unix epoch specifying the effective end of the Flexible Event. Required.
  *         'duration'    =>  (integer) the minimum duration of the Flexible Event. Required.
  *      }
  *
  * @return FlexibleEvent
  *
  */
  private function createFlexibleInfo(Request $request, Event $newEvent){

      // Validate the request
      $validatedData = $request->validate([
        'lowerBound' => 'required|integer|min:0',
        'upperBound' => 'required|integer|min:0',
        'duration'   => 'required|integer|min:0',
      ]);

      // Create a new FlexibleEvent object
      $flexbileInfo = new FlexibleEvent();

      $flexbileInfo->build(
        $newEvent->id,
        $request->get('lowerBound'),
        $request->get('upperBound'),
        $request->get('duration')
      );

      // Stores the newly create resource
      $flexbileInfo->save();

      return $flexbileInfo;
  }

  /**
  * Creates a Standard Event
  *
  *
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *        'title'       =>  (string) title of the event. Required.
  *        'start'       =>  (integer) unix epoch specifying the start of the event. Required.
  *        'end'         =>  (integer) unix epoch specifying the end of the event. Required.
  *        'longitude'   =>  (float) the longitude of the location of the Event in decimal format (9,6).Required.
  *        'latitude'    =>  (float) the latitude of the location of the Event in decimal format (9,6).Required.
  *        'category'    =>  (string) category of the event (e.g. "work", "school"). Required.
  *        'description' =>  (string) description of the event. Required.
  *        'flexible'    =>  (boolean) whether the event is flexible. Required.
  *        'travel'      =>  (boolean) whether the event has a travel. Required.
  *      }
  *         also other parameters according to the type of event to be created and specified in 
  *         the respective methods
  *
  * @return Event
  *
  */
  private function createSingleEvent(Request $request){

      // Validates the request
      $validatedData = $request->validate([
          'title'       =>  'required|string',
          'start'       =>  'required|integer|min:0',
          'end'         =>  'required|integer|min:0',
          'longitude'   =>  'required|numeric',
          'latitude'    =>  'required|numeric',
          'category'    =>  'required|nullable|string',
          'description' =>  'required|nullable|string',
          'flexible'    =>  'required|boolean',
          'travel'      =>  'required|boolean'
      ]);

      // Creates a new Event object
      $newEvent = new Event();

      $newEvent->build(
        auth()->id(),
        $request->get('title'),
        $request->get('start'),
        $request->get('end'),
        $request->get('longitude'),
        $request->get('latitude'),
        $request->get('category'),
        $request->get('description')
      );

      // Stores the newly created storage
      $newEvent->save();

      if( $request->get('flexible') ){
        // Adds flexible information to the Event
        $this->createFlexibleInfo($request, $newEvent);
      }

      if ($request->get('travel')) {
        // Adds travel information to the Event
        $this->createTravel($request, $newEvent);
      }

      // Load ORM relationships
      $newEvent->load('travel', 'flexibleInfo', 'repetitiveInfo');

      return $newEvent;
  }

  /**
  * Handles the request for the display a resource in the User's schedule
  *
  * This method handles a GET request sent to the api/v1/event endpoint and return the 
  * information of the Event specified by the provided identifier if such Event exists and
  * belongs to the logged in User
  *
  * @param  int $id identifier of the requested Event
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message'   => (string) response message.
  *         'event'     => (Event) the Event requested. 
  *                        (if the requested resource is available)
  *     }
  */
  public function show($id){

    // Get logged in user
    $user = auth()->user();

    // Get requested resource
    $event = $user->events->where('id', $id)->load('travel', 'flexibleInfo', 'repetitiveInfo')->first();

    if(empty($event)){
      $message = 'The requested resource does not belong to the current User or does not exists';
      return response()->json(array('message' => $message), 403);
    }

    $message = 'Request successful';
    return response()->json(array('message' => $message, 'event' => $event), 200);

  }

  /**
  * Handles the request to delete an Event from the User's schedule
  *
  * This method handles a DELETE request sent to the api/v1/event endpoint and deletes from the database
  * the specified by the provided identifier. If such an Event is repetitive, it's possible to delete
  * the single specified repetition or all repetitions.
  *
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *         'repetitions'  =>  (boolean) whether to delete all the repetitions of a Repetitive Event. Required.
  *      }
  *
  * @param  int $id identifier of the requested Event
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message'   => (string) response message.
  *     }
  */
  public function destroy(Request $request, $id){

    // Validate the request
    $validatedData = $request->validate([
      'repetitions' => 'boolean'
    ]);

    // Get currently logged user
    $user = auth()->user();

    $event = $user->events->where('id', $id)->first();

    if(empty($event)){
      $message = 'The requested resource does not belong to the current User or does not exists';
      return response()->json(array('message' => $message), 403);
    }

    if($event->travel !== null && $event->travel->booking !== null){
      $event->travel->booking->delete();
    }

    try{

      if($event->repetitiveInfo !== null && $request->get('repetitions')){
        $events = Event::whereHas('repetitiveInfo', function ($query) use ($event) {
          $query->where('groupId', $event->repetitiveInfo->groupId);
        });
        $events->delete();
      }else{
        $event->delete();
      }      

      $message = 'Event deleted';
      return response()->json(array('message' => $message), 200);

    } catch (QueryException $e) {

      $message = 'Event deletion failed';
      return response()->json(array('message' => $message), 500);
    }

  }

  /**
  * *Not Implemented* Just used to conform to laravel resource management structure
  *
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message' => (string) response message.
  *     }
  */
  public function create(){
    $message = 'use POST */*/event/ to create a new Event';
    return response()->json(array('message' => $message), 501);
  }

  /**
  * *Not Implemented* Just used to conform to laravel resource management structure
  *
  * @param int $id
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message' => (string) response message.
  *     }
  */
  public function edit($id){

    $message = 'use PUT */*/event/ID to edit Event information';
    return response()->json(array('message' => $message), 501);

  }

  /**
  * *Not Implemented* Will be implemented in future
  *
  * @param Request $request
  * @param int $id
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message' => (string) response message.
  *     }
  */
  public function update(Request $request, $id){

    $message = 'update is currently not availale';
    return response()->json(array('message' => $message), 501);

  }
  
}

?>