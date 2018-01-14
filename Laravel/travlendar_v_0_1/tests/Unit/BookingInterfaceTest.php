<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Interfaces\BookingInterface;
use App\Http\Helpers\ApiKeys;
use App\Models\Booking;
use App\Models\Travel;
use App\Models\Event;
use Tests\TestCase;
use Carbon\Carbon;
use App\User;

class BookingInterfaceTest extends TestCase {

    /*
		Each test is wrapped in a database transaction and therefore is indipendent.
	*/
    use DatabaseTransactions;

    /*
		The following functions are used to populate the database in order to test different scenarios and they are not considered as tests.
    */

    /* START HELPER FUNCTIONS */

    private function createEvent($userId, $title, $start, $end, $latitude, $longitude, $category, $description){
        $event = new Event();

        $event->build(
            $userId,
            $title,
            $start,
            $end,
            $longitude,
            $latitude,
            $category,
            $description
        );

        $event->save();

        return $event;
    }

    private function createTravel($eventId, $startLatitude, $startLongitude, $distance, $travelDuration, $mean, $bookingId){
        $travel = new Travel();

        $travel->build(
            $eventId,
            $startLongitude,
            $startLatitude,
            $distance,
            $travelDuration,
            $mean,
            $bookingId
        );

        $travel->save();

        return $travel;
    }

    /* END HELPER FUNCTIONS */

    /*
		The following functions are the tests performed.
    */

    /* START TEST FUNCTIONS */

    /*
     Tests the successful retrieval of available booking options
     given an event and starting latitude and longitude of the related travel
    */
    public function testGetAvailableBookings() {
        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $event = $this->createEvent(
            $user->id,
            'Test event',
            Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
            Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
            45.485976,
            9.204145,
            "this is a test event",
            "test"
        );
        $start_latitude = 45.490291;
        $start_longitude= 9.22570;

        $response = BookingInterface::bookingOptions($event, $start_latitude, $start_longitude);

        $this->assertEquals("success", $response["message"]);
        $this->assertNotEmpty($response["bookings"]);
    }

    /*
     Tests the unsuccessful retrieval of available booking options
     given an event and given starting latitude and longitude of the related travel
     when the coordinates are not valid
    */
    public function testGetAvailableBookingsFailedInvalidCoordinates() {
        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $event = $this->createEvent(
            $user->id,
            'Test event',
            Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
            Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
            45.485976,
            9.204145,
            "this is a test event",
            "test"
        );
        $start_latitude = -45.490291;
        $start_longitude= -9.22570;

        $response = BookingInterface::bookingOptions($event, $start_latitude, $start_longitude);


        $this->assertEquals("Error in Uber services: no available travels", $response["message"]);
        $this->assertEmpty($response["bookings"]);
    }

    /*
     Tests the successful creation of a booking given an event and the starting travel coordinates
    */
    public function testCreateBookingWithoutFailure() {
        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $start_latitude = 45.490291;
        $start_longitude= 9.22570;

        $event = $this->createEvent(
            $user->id,
            'Test event',
            Carbon::now('Europe/Paris')->timestamp + 600,
            Carbon::now('Europe/Paris')->timestamp + 2400,
            45.485976,
            9.204145,
            "this is a test event",
            "test"
        );


        $booking = BookingInterface::bookingOptions($event, $start_latitude, $start_longitude);

        $booking = $booking["bookings"][0];

        $response = BookingInterface::bookRide(ApiKeys::$uberClientToken, $booking);

        $this->assertEquals("success", $response["message"]);
        $this->assertInstanceOf(Booking::class, $response["booking"]);
        $this->assertNotEquals(new Booking(), $response["booking"]);
    }

    /*
     Tests the unsuccessful creation of a booking given an invalid product identifier
    */
    public function testCreateBookingWithInvalidProductID() {
        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $start_latitude = 45.490291;
        $start_longitude= 9.22570;

        $event = $this->createEvent(
            $user->id,
            'Test event',
            Carbon::now('Europe/Paris')->timestamp + 600,
            Carbon::now('Europe/Paris')->timestamp + 2400,
            45.485976,
            9.204145,
            "this is a test event",
            "test"
        );


        $booking = BookingInterface::bookingOptions($event, $start_latitude, $start_longitude);

        $booking = $booking["bookings"][0];

        $bookingInfo = array(
            "product_id"        =>  'nothing',
            "request_id"        =>  $booking["bookingInfo"]["request_id"],
            "type"              =>  $booking["bookingInfo"]["type"],
            "duration"          =>  $booking["bookingInfo"]["duration"],
            "distance"          =>  $booking["bookingInfo"]["distance"],
            "price_low"         =>  $booking["bookingInfo"]["price_low"],
            "price_high"        =>  $booking["bookingInfo"]["price_high"],
            "start_latitude"    =>  $booking["bookingInfo"]["start_latitude"],
            "start_longitude"   =>  $booking["bookingInfo"]["start_longitude"],
            "end_latitude"      =>  $booking["bookingInfo"]["end_latitude"],
            "end_longitude"     =>  $booking["bookingInfo"]["end_longitude"]
        );

        $booking["bookingInfo"] = $bookingInfo;

        $response = BookingInterface::bookRide(ApiKeys::$uberClientToken, $booking);

        $this->assertNotEquals("success", $response["message"]);
        $this->assertEquals(new Booking(), $response["booking"]);
    }

    /*
     Tests the unsuccessful creation of a booking given an invalid token
    */
    public function testCreateBookingWithInvalidUberToken() {
        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $start_latitude = 45.490291;
        $start_longitude= 9.22570;

        $event = $this->createEvent(
            $user->id,
            'Test event',
            Carbon::now('Europe/Paris')->timestamp + 600,
            Carbon::now('Europe/Paris')->timestamp + 2400,
            45.485976,
            9.204145,
            "this is a test event",
            "test"
        );


        $booking = BookingInterface::bookingOptions($event, $start_latitude, $start_longitude);

        $booking = $booking["bookings"][0];

        $response = BookingInterface::bookRide(ApiKeys::$uberClientToken . 'x' , $booking);

        $this->assertEquals("unauthorized", $response["message"]);
        $this->assertEquals(new Booking(), $response["booking"]);
    }
}
