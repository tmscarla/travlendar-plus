<?php 

namespace App\Http\Controllers\api\v1;

use App\Http\Interfaces\BookingInterface;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Booking;
use Carbon\Carbon;
use App\Models\Travel;

/**
 * This class is a controller that provides methods for handling the routes/endpoints
 * associated with booking management.
 * More information about such routes are in the routes/api.php file.
 * This controller provides methods to retrieve available booking options,
 * to create a new booking and delete an existing one.
 */

class BookingController extends Controller 
{

    /**
     * *Not Implemented* Just used to conform to laravel resource management structure
     *
     * @param
     *
     * @return a Response object (Illuminate\Http\Response) containing the json representation of
     *     {
     *         'message' => (string) response message.
     *     }
     */
  public function index()
  {
      $message = "use GET */*/show to get the current booking";
      return response()->json(array("message" => $message), 501);
  }

    /**
     * *Not Implemented* Just used to conform to laravel resource management structure
     *
     * @param
     *
     * @return a Response object (Illuminate\Http\Response) containing the json representation of
     *     {
     *         'message' => (string) response message.
     *     }
     */
  public function create(){
      $message = "use POST */*/book to create a new Booking";
      return response()->json(array("message" => $message), 501);
  }

    /**
     * *Not Implemented* Just used to conform to laravel resource management structure
     *
     * @param
     *
     * @return a Response object (Illuminate\Http\Response) containing the json representation of
     *     {
     *         'message' => (string) response message.
     *     }
     */
  public function store(Request $request)
  {
      $message = "use POST */*/book to create a new Booking";
      return response()->json(array("message" => $message), 501);
  }

    /**
     * This method call the the method bookingOptions of BookingInterface in order
     * to retrieve the available Uber options.
     *
     * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
     *      {
     *         'event_id'  =>  (integer) the id of the event of which the available bookings options are requested. Required.
     *         'start_latitude' => (float) start latitude of the travel
     *         'start_longitude' => (float) start longitude of the travel
     *      }
     *
     * @return an array containing
     *     {
     *         'message'   => (string) response message.
     *         'available' => (array) the array of available booking options.
     *     }
     */
  private function availableBookings(Request $request) {
      $user = auth()->user();

      $validateData = $request->validate([
          'event_id' => 'required|integer|min:0',
          'start_latitude' => 'required',
          'start_longitude' => 'required',
      ]);

      $event = $user->events
          ->where('id', $request->event_id);

      $event_to_send = null;
      $available = [];

      foreach ($event as $ev) {
          $event_to_send = $ev;
      }

      if (is_null($event_to_send)) {
          $message = 'Event not found';
          return array("message" => $message, "available" => $available);
      }
      else {
          $available = BookingInterface::bookingOptions($event_to_send, $request->start_latitude, $request->start_longitude);

          if ($available['message'] != 'success') {
              $message = $available['message'];
              return array("message" => $message, "available" => $available['bookings']);
          }

          $message = "Request successful";
          return array("message" => $message, "available" => $available['bookings']);
      }
  }

  /**
   * Handles the request to retrieve available booking options
   *
   * This method handles a GET request sent to the api/v1/available and call the the method availableBookings
   * in order to retrieve the available Uber options.
   *
   * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
   *      {
   *         'event_id'  =>  (integer) the id of the event of which the available bookings options are requested. Required.
   *         'start_latitude' => (float) start latitude of the travel.
   *         'start_longitude' => (float) start longitude of the travel.
   *      }
   *
   * @return a Response object (Illuminate\Http\Response) containing the json representation of
   *     {
   *         'message'   => (string) response message.
   *         'available' => (array) the array of available booking options.
   *     }
   */
  public function getAvailableBooking(Request $request) {

      $available = $this->availableBookings($request);

      If ($available["message"] != "Request successful") {
          return response()->json($available, 404, [], JSON_NUMERIC_CHECK);
      }
      else {
          return response()->json($available, 200, [], JSON_NUMERIC_CHECK);
      }
  }

  /**
   * Handles the request to book an Uber travel.
   *
   * This method handles a POST request sent to the api/v1/book and call the the method bookRide
   * in order to book one of the available Uber travel option.
   *
   * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
   *      {
   *         'event_id'  =>  (integer) the id of the event of which the available bookings options are requested. Required.
   *         'product_id' => (alphanumeric) the id of the Uber product selected by the User.
   *         'token' => (alphanumeric) User token given after the Uber authentication.
   *         'start_latitude' => (float) start latitude of the travel.
   *         'start_longitude' => (float) start longitude of the travel.
   *      }
   *
   * @return a Response object (Illuminate\Http\Response) containing the json representation of
   *     {
   *         'message'   => (string) response message.
   *         'booking' => (Booking) the booking option.
   *     }
   */
  public function createBooking(Request $request){

      $events = auth()->user()->events;

      $validateData = $request->validate([
          'event_id' => 'required',
          'product_id' => 'required',
          'token' => 'required',
          'start_latitude' => 'required',
          'start_longitude' => 'required'
      ]);

      $activeBooking = false;
      foreach ($events as $event) {
          $travel = $event->travel;
          if(!is_null($travel['booking'])){
              $activeBooking = true;
              break;
          }
      }

      if (!$activeBooking) {
          $currentBooking = $this->retrieveCurrentBooking($request->token);
          if ($currentBooking["message"] == "Current Uber booking not present in database") {
              BookingInterface::deleteRide($request->token);
          }

          $now = Carbon::now('Europe/Paris')->timestamp;
          $eventStart = DB::table('events')->where('id', $request->event_id)->value('start');

          $available = $this->availableBookings($request);
          $available = $available['available'];
          foreach ($available as $av) {
              if ($av['bookingInfo']['product_id'] == $request->product_id) {
                  $selectedBooking = $av;
                  break;
              }
          }

          if (($eventStart-$now) < 0) {
              $message = "Is not possible to book a ride after the start of the event";
              return response()->json(array("message" => $message, "booking" => new Booking()), 400, [], JSON_NUMERIC_CHECK);
          }
          elseif (($eventStart-$now) > 3600) {
              $message = "Is not possible to book a ride until 60 minutes before the start of the event";
              return response()->json(array("message" => $message, "booking" => new Booking()), 400, [], JSON_NUMERIC_CHECK);
          }

          $booking = BookingInterface::bookRide($request->token, $selectedBooking);

          if ($booking["message"] == "success") {
              try {
                  $booking["booking"]->save();
                  $booking["booking"]->bookingInfo = json_decode($booking["booking"]->bookingInfo);

                  $bookingId = DB::table('bookings')
                      ->where('bookingInfo->request_id', $booking["booking"]->bookingInfo->request_id)->value('id');

                  $travel = auth()->user()->events->where('id', $request->event_id)->first()->travel;

                  $travel->bookingId = $bookingId;

                  $travel->save();

                  $message = "Request successful";
                  return response()->json(array("message" => $message, "booking" => $booking["booking"]), 200, [], JSON_NUMERIC_CHECK);
              }
              catch (QueryExeption $e) {
                  $this->destroy($request->token);
                  $message = "A problem occurred during the booking creation";
                  return response()->json(array("message" => $message), 404);
              }
          }
          else {
              return response()->json(array("message" => $booking["message"], "booking" => $booking["booking"]), 400, [], JSON_NUMERIC_CHECK);
          }
      }
      else {
          $message = "User already booked a trip";
          return response()->json(array("message" => $message, "booking" => new Booking()), 400, [], JSON_NUMERIC_CHECK);
      }
  }

  /**
   * Handles the request for the display of the current booking for a given Travel.
   *
   * This method handles a GET request sent to the api/v1/book/{bookingId} endpoint and return the
   * information of the Booking specified by the provided identifier if such Booking exists and
   * belongs to the logged in User.
   *
   * @param  int $bookingId identifier of the requested Booking
   *
   * @return a Response object (Illuminate\Http\Response) containing the json representation of
   *     {
   *         'message'   => (string) response message.
   *         'booking'     => (Booking) the Booking requested.
   *                        (if the requested resource is available)
   *     }
   */
  public function show($bookingId)
  {
      $user = auth()->user();

      try {
          $found = false;
          $travel = DB::table('travels')->where('bookingId', $bookingId)->value('eventId');
          foreach ($user->events as $event) {
              if ($event->id == $travel) {
                  $found = true;
                  break;
              }
          }
          if ($found == false) {
              $message = "Booking doesn't exists";
              return response()->json(array("message" => $message, "booking" => null), 404);
          }
          $currentBooking = DB::table('bookings')->where('id', $bookingId)->first();

          $newBooking = new Booking();
          $newBooking->service = $currentBooking->service;
          $newBooking->bookingInfo = json_decode($currentBooking->bookingInfo);

          $message = "Request successful";
          return response()->json(array("message" => $message, "booking" => $newBooking), 200, [], JSON_NUMERIC_CHECK);
      }
      catch (QueryException $e) {
          $message = "Invalid booking id";
          return response()->json(array("message" => $message), 404);
      }
  }

  /**
   * *Not Implemented* Just used to conform to laravel resource management structure
   *
   * @param
   *
   * @return a Response object (Illuminate\Http\Response) containing the json representation of
   *     {
   *         'message' => (string) response message.
   *     }
   */
  public function edit() {
      $message = 'edit is currently not availale';
      return response()->json(array('message' => $message), 501);
  }

  /**
   * *Not Implemented* Just used to conform to laravel resource management structure
   *
   * @param
   *
   * @return a Response object (Illuminate\Http\Response) containing the json representation of
   *     {
   *         'message' => (string) response message.
   *     }
   */
  public function update() {
      $message = 'update is currently not availale';
      return response()->json(array('message' => $message), 501);
  }

  /**
   * Handles the request to delete a Booking from a User's travel
   *
   * This method handles a DELETE request sent to the api/v1/delete endpoint and deletes from the database
   * the Booking given by the identifier, only if exists and belongs to the User.
   *
   * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
   *      {
   *         'token' => given to the User after the authentication with the Uber service.
   *      }
   *
   * @return a Response object (Illuminate\Http\Response) containing the json representation of
   *     {
   *         'message'   => (string) response message.
   *     }
   */
  public function destroy(Request $request) {
      $validateData = $request->validate([
          'token' => 'required'
      ]);

      $current = $this->retrieveCurrentBooking($request->token);

      if ($current["message"] == "User has no active booking") {
          return response()->json(array("message" => $current["message"]), 403);
      }
      elseif ($current["message"] == "Current Uber booking not present in database") {
          BookingInterface::deleteRide($request->token);
          $message = "User has no active booking";
          return response()->json(array("message" => $message), 403);
      }
      else {
          try {
              $bookingId = $current["current"]["id"];
              DB::table('bookings')->where('id', $bookingId)->delete();
              BookingInterface::deleteRide($request->token);
              $current = $this->retrieveCurrentBooking($request->token);

              if ($current["message"] == "User has no active booking") {
                  $message = "Booking deletion successful";
                  return response()->json(array("message" => $message), 200);
              }
          }
          catch (QueryException $e) {
              $message = "Booking deletion failed";
              return response()->json(array("message" => $message), 404);
          }
      }
  }

    /**
     * Handles the request to delete the current Booking
     *
     * This method handles a DELETE request sent to the api/v1/book/current/booking endpoint and
     * delete the request from the service. Mostly used for testing scopes.
     *
     * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
     *      {
     *         'token' => given to the User after the authentication with the Uber service.
     *      }
     *
     * @return (string) that confirms the deletion
     */
  public function deleteBookingTest(Request $request) {
      BookingInterface::deleteRide($request->get('token'));
      return "Deleted";
  }

  /**
   * Handles the request to retrieve the current Booking
   *
   * This method handles a GET request sent to the api/v1/book/current/booking endpoint and
   * retrieve from the database requested Booking, only if exists and belongs to the User.
   * Mostly used for testing functionality.
   *
   * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
   *      {
   *         'token' => given to the User after the authentication with the Uber service.
   *      }
   *
   * @return a Response object (Illuminate\Http\Response) containing the json representation of
   *     {
   *         'message'   => (string) response message.
   *         'current'   => (Booking) the current Booking
   *     }
   */
    public function getCurrentBookingRequest(Request $request) {
      $validateData = $request->validate([
          'token' => 'required'
      ]);
      $current = $this->retrieveCurrentBooking($request->token);
      if ($current["message"] == "Current ride available") {
          return response()->json($current, 200);
      }
      else {
          return response()->json($current, 403);
      }
  }

  /**
   *
   * This method calls the method getCurrentRide of BookingInterface
   * in order to retrieve the current booking, if exists and belongs to the User
   *
   * @param (alphanumeric) $token given to the User after the authentication with the Uber service.
   *
   * @return (array) containing
   *     {
   *         'message'   => (string) response message.
   *         'current'   => (Booking) the current Booking
   *     }
   */
  private function retrieveCurrentBooking($token) {
      $current = BookingInterface::getCurrentRide($token);
      if ($current["message"] == "Request successful") {
          $events = auth()->user()->events;
          foreach ($events as $event) {
              if (!is_null($event->travel)) {
                  if (!is_null($event->travel->bookingId)) {
                      $booking = Booking::where('id', $event->travel->bookingId)->first();
                      if (json_decode($booking["bookingInfo"])->request_id == $current["request_id"]) {
                          $booking["bookingInfo"] = json_decode($booking["bookingInfo"]);
                          $message = "Current ride available";
                          return array("message" => $message, "current" => $booking);
                      }
                  }
              }
          }
          $message = "Current Uber booking not present in database";
          return array("message" => $message, "current" => null);
      }
      $message = "User has no active booking";
      return array("message" => $message, "current" => null);
  }
}

?>