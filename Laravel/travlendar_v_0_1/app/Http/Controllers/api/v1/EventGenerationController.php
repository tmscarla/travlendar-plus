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
use Illuminate\Http\Request;
use App\Http\Helpers\UUID;
use App\Models\Travel;
use App\Models\Event;
use ErrorException;
use Carbon\Carbon;
use App\User;

/**
* EventGenerationController class
*
* This class is a controller that provides methods for handling the routes/endpoints 
* associated with Event generation.
* More information about such routes are in the routes/api.php file.
* This controller provides methods to generate a valid Event taking into account
* travel information and the current state of the User's schedule.
*/
class EventGenerationController extends Controller {

  /**
  * Handles the request for the generation feasible events with associated travel options
  *
  * This method handles a POST request sent to the api/v1/generator endpoint.
  * According to the specified event details this method provides the available travel options and 
  * the adjustements to apply to the schedule for each option in order to make it feasible.
  * 
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *        'title'       =>  (string) title of the event. Required.
  *        'start'       =>  (integer) unix epoch specifying the start of the event. Required.
  *        'end'         =>  (integer) unix epoch specifying the end of the event. Required.
  *        'longitude'   =>  (float) the longitude of the start location of the Event in decimal format (9,6).Required.
  *        'latitude'    =>  (float) the latitude of the start location of the Event in decimal format (9,6).Required.
  *        'category'    =>  (string) category of the event (e.g. "work", "school"). Required.
  *        'description' =>  (string) description of the event. Required.
  *        'flexible'    =>  (boolean) whether the event is flexible. Required.
  *        'travel'      =>  (boolean) whether the event has a travel. Required.
  *        'repetitive'  =>  (boolean) whether the event is repetitive. Required.
  *      }
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message'    => (string) response message.
  *         'event'      => (Event) an Event object.
  *         'options'    => (Array) an array of Travel with associated schedule adjustements.
  *     }
  */
  public function generateEvent(Request $request){
    try{
      // Validate the request
      $validatedData = $request->validate([
          'title'            =>  'required|string',
          'start'            =>  'required|integer|min:0',
          'end'              =>  'required|integer|min:0',
          'longitude'        =>  'required|numeric',
          'latitude'         =>  'required|numeric',
          'category'         =>  'required|nullable|string',
          'description'      =>  'required|nullable|string',
          'flexible'         =>  'required|boolean',
          'repetitive'       =>  'required|boolean',
          'travel'           =>  'required|boolean'
      ]);

      // Create a new Event object
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

      // Assign a temporary identifier
      $newEvent->id = -1;


      if($request->get('flexible')){

        $validatedData = $request->validate([
          'duration' =>  'required|integer|min:0',
        ]);

        // Create a new Flexible Event
        $flexibleInfo = new FlexibleEvent();
        $flexibleInfo->build(
          $newEvent->id,
          $request->get('start'),
          $request->get('end'),
          $request->get('duration')
        );

        // Assign flexible information to the event
        $newEvent->flexibleInfo = $flexibleInfo;
      }

      $message = "Request successful";

      if($request->get('travel')){

        $travels = $this->getTravelOptions($request, $newEvent);

        $options = array();

        // For each travel option generate adjustments to the schedule
        foreach ($travels as $type) {

          foreach ($type["travels"] as $travel) {

            if(!FeasibilityManager::checkTravelPreferences($travel, auth()->user())){
              break;
            }

            $newEvent->travel = $travel;
            if($request->get('repetitive')){
              array_push($options, ["travel" => $travel, "adjustements" => null]);
            } else {
              $adjustements = FeasibilityManager::getScheduleAdjustements($newEvent);
              if (!empty((array) $adjustements)){
                array_push($options, ["travel" => $travel, "adjustements" => $adjustements]);
              }
            }        
          }
        }
      } else {

        if($request->get('repetitive')){
          return response()->json(["message" => $message, "event" => $newEvent, "options" => null], 200);
        }
        
        $adjustements = FeasibilityManager::getScheduleAdjustements($newEvent);

        $options = Array(["travel" => null, "adjustements" => $adjustements]);
      
      }
    } catch(QueryException $e){
      $message = "Query Error";
      return response()->json(["message" => $message], 500);
    }
    unset($newEvent->travel);
    return response()->json(["message" => $message, "event" => $newEvent, "options" => $options], 200);
  }

  /**
  * Gets all Travel options available.
  * 
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *         'startLongitude'   =>  (float)   the longitude of the start location of the Travel in decimal format (9,6). *                                          Required.
  *         'startLatitude'    =>  (float)   the latitude of the start location of the Travel in decimal format (9,6).
  *                                          Required.
  *      }
  *
  * @param an Event object $newEvent (App\Models\Event)
  *
  * @return (Array) an Array of Travel objects
  *
  */
  private function getTravelOptions(Request $request, Event $newEvent){

    // Validate the data
    $validatedData = $request->validate([
        'startLongitude'   =>  'required|numeric',
        'startLatitude'    =>  'required|numeric'
    ]);

    $startLongitude = $request->get('startLongitude');
    $startLatitude = $request->get('startLatitude');

    // Requests the travel options from Maps services
    $availableTravels = MapsInterface::getAvailableTravel($newEvent, $startLatitude, $startLongitude);
    return $availableTravels;
  }
 
  
}

?>