<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\ApiKeys;
use App\Models\Travel;
use App\Models\Event;
use Tests\TestCase;
use Carbon\Carbon;
use App\User;

class BookingControllerTest extends TestCase {

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

    private function getAvailableBookingOptions($payload) {
        $endpoint = "api/v1/available";
        $method = "GET";

        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        return $response;
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
    public function testAvailableBookingsWithSolution() {
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

        $payload = [
            "event_id" => $event->id,
            "start_latitude" => $start_latitude,
            "start_longitude" => $start_longitude
        ];

        $response = $this->getAvailableBookingOptions($payload);

        $content = json_decode($response->getContent());

        $this->assertEquals("Request successful", $content->message);
        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content->available);
    }

    /*
     Tests the unsuccessful retrieval of available booking options
     given an event and given starting latitude and longitude of the related travel
     when the event_id is not valid
    */
    public function testAvailableBookingsInvalidEventId() {
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

        $payload = [
            "event_id" => 0,
            "start_latitude" => $start_latitude,
            "start_longitude" => $start_longitude
        ];

        $response = $this->getAvailableBookingOptions($payload);

        $content = json_decode($response->getContent());

        $this->assertEquals("Event not found", $content->message);
        $this->assertEquals(404, $response->status());
        $this->assertEmpty($content->available);
    }

    /*
     Tests the unsuccessful retrieval of available booking options
     given an event and given starting latitude and longitude of the related travel
     when the latitude and longitude are not valid
    */
    public function testAvailableBookingsInvalidCoordinates() {
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
        $start_longitude= 9.22570;

        $payload = [
            "event_id" => $event->id,
            "start_latitude" => $start_latitude,
            "start_longitude" => $start_longitude
        ];

        $response = $this->getAvailableBookingOptions($payload);

        $content = json_decode($response->getContent());

        $this->assertEquals("Error in Uber services: no available travels", $content->message);
        $this->assertEquals(404, $response->status());
        $this->assertEmpty($content->available);
    }

    /*
	 Tests the successful creation of a booking
     given an event and a travel
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

        $payload = [
            "event_id" => $event->id,
            "start_latitude" => $start_latitude,
            "start_longitude" => $start_longitude
        ];

        $response = $this->getAvailableBookingOptions($payload);

        $content = json_decode($response->getContent());
        $available = $content->available[0]->bookingInfo;

        $travel = $this->createTravel(
                    $event->id,
                    $available->start_latitude,
                    $available->start_longitude,
                    $available->distance,
                    $available->duration,
                    'uber',
                    null
                );

        $payload = [
            'event_id' => $event->id,
            'product_id' => $available->product_id,
            'token' => ApiKeys::$uberClientToken,
            'start_latitude' => $start_latitude,
            'start_longitude' => $start_longitude
        ];

        $endpoint = "api/v1/book";
        $method = "POST";

        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        $content = json_decode($response->getContent());

        $this->assertEquals("Request successful", $content->message);
        $this->assertEquals(200, $response->status());

        $this->assertDatabaseHas(
            "bookings",
            [
                "id"            =>  $content->booking->id,
                "service"       =>  $content->booking->service,
            ]
        );

        $bookingInfoDB = DB::table('bookings')->where('id', $content->booking->id)->value('bookingInfo');

        $this->assertJsonStringEqualsJsonString(
            $bookingInfoDB,
            json_encode($content->booking->bookingInfo),
            "Equals"
        );

        $this->assertDatabaseHas(
            "travels",
            [
                "eventId"   =>  $event->id,
                "mean"      =>  $content->booking->service,
                "bookingId" =>  $content->booking->id,
            ]
        );

        $payload = [
            'token' => ApiKeys::$uberClientToken,
        ];

        $endpoint = "api/v1/book/";
        $method = "DELETE";

        $response_2 = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
    }

    /*
	 Tests the unsuccessful creation of a booking
     given an event and a travel, when the booking is carried
     more than 60 min before the start of the event
    */
    public function testCreateBookingTooEarly() {
        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $start_latitude = 45.490291;
        $start_longitude= 9.22570;

        $event = $this->createEvent(
            $user->id,
            'Test event',
            Carbon::now('Europe/Paris')->timestamp + 36000,
            Carbon::now('Europe/Paris')->timestamp + 240000,
            45.485976,
            9.204145,
            "this is a test event",
            "test"
        );

        $payload = [
            "event_id" => $event->id,
            "start_latitude" => $start_latitude,
            "start_longitude" => $start_longitude
        ];

        $response = $this->getAvailableBookingOptions($payload);

        $content = json_decode($response->getContent());
        $available = $content->available[0]->bookingInfo;

        $travel = $this->createTravel(
            $event->id,
            $available->start_latitude,
            $available->start_longitude,
            $available->distance,
            $available->duration,
            'uber',
            null
        );

        $payload = [
            'event_id' => $event->id,
            'product_id' => $available->product_id,
            'token' => ApiKeys::$uberClientToken,
            'start_latitude' => $start_latitude,
            'start_longitude' => $start_longitude
        ];

        $endpoint = "api/v1/book";
        $method = "POST";

        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        $content = json_decode($response->getContent());

        $this->assertEquals("Is not possible to book a ride until 60 minutes before the start of the event", $content->message);
        $this->assertEquals(400, $response->status());

        $payload = [
            'token' => ApiKeys::$uberClientToken,
        ];

        $endpoint = "api/v1/book/current/delete";
        $method = "DELETE";

        $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
    }

    /*
	 Tests the unsuccessful creation of a booking
     given an event and a travel, when the booking is carried
     after the start of the event
    */
    public function testCreateBookingTooLate() {
        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $start_latitude = 45.490291;
        $start_longitude= 9.22570;

        $event = $this->createEvent(
            $user->id,
            'Test event',
            Carbon::now('Europe/Paris')->timestamp - 1000,
            Carbon::now('Europe/Paris')->timestamp,
            45.485976,
            9.204145,
            "this is a test event",
            "test"
        );

        $payload = [
            "event_id" => $event->id,
            "start_latitude" => $start_latitude,
            "start_longitude" => $start_longitude
        ];

        $response = $this->getAvailableBookingOptions($payload);

        $content = json_decode($response->getContent());
        $available = $content->available[0]->bookingInfo;

        $travel = $this->createTravel(
            $event->id,
            $available->start_latitude,
            $available->start_longitude,
            $available->distance,
            $available->duration,
            'uber',
            null
        );

        $payload = [
            'event_id' => $event->id,
            'product_id' => $available->product_id,
            'token' => ApiKeys::$uberClientToken,
            'start_latitude' => $start_latitude,
            'start_longitude' => $start_longitude
        ];

        $endpoint = "api/v1/book";
        $method = "POST";

        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        $content = json_decode($response->getContent());

        $this->assertEquals("Is not possible to book a ride after the start of the event", $content->message);
        $this->assertEquals(400, $response->status());

        $payload = [
            'token' => ApiKeys::$uberClientToken,
        ];

        $endpoint = "api/v1/book/current/delete";
        $method = "DELETE";

        $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
    }


    /*
     Tests the successful retrieval of a current booking previously created
    */
    public function testShowCurrentBooking() {

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

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
        $start_latitude = 45.490291;
        $start_longitude= 9.22570;

        $payload = [
            "event_id" => $event->id,
            "start_latitude" => $start_latitude,
            "start_longitude" => $start_longitude
        ];

        $response = $this->getAvailableBookingOptions($payload);

        $content = json_decode($response->getContent());
        $available = $content->available[0]->bookingInfo;

        $travel = $this->createTravel(
            $event->id,
            $available->start_latitude,
            $available->start_longitude,
            $available->distance,
            $available->duration,
            'uber',
            null
        );

        $event->travel = $travel;

        $payload = [
            'event_id' => $event->id,
            'product_id' => $available->product_id,
            'token' => ApiKeys::$uberClientToken,
            'start_latitude' => $start_latitude,
            'start_longitude' => $start_longitude
        ];

        $endpoint = "api/v1/book";
        $method = "POST";

        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );

        usleep(2000000);

        $payload_2 = [
            "token" => ApiKeys::$uberClientToken
        ];

        $endpoint_2 = "api/v1/book/current/booking";
        $method_2 = "GET";

        $response_2 = $this->call($method_2, $endpoint_2, $payload_2, [], [], ["HTTP_Accept" => "application/json"], [] );
        $content_2 = json_decode($response_2->getContent());

        $this->assertEquals("Current ride available", $content_2->message);
        $this->assertEquals(200, $response_2->status());

        $payload_3 = [
            'token' => ApiKeys::$uberClientToken,
        ];

        $endpoint_3 = "api/v1/book";
        $method_3 = "DELETE";

        $response_3 = $this->call($method_3, $endpoint_3, $payload_3, [], [], ["HTTP_Accept" => "application/json"], [] );
    }

    /*
     Tests the unsuccessful retrieval of a booking not previously created
    */
    public function testShowCurrentBookingFailed() {
        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $payload = [
            "token" => ApiKeys::$uberClientToken
        ];

        $endpoint = "api/v1/book/current/booking";
        $method = "GET";

        $response_2 = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        $content_2 = json_decode($response_2->getContent());

        $this->assertEquals("User has no active booking", $content_2->message);
        $this->assertEquals(403, $response_2->status());
        $this->assertEquals(null, $content_2->current);

        $payload = [
            'token' => ApiKeys::$uberClientToken,
        ];
    }

    /*
	 Tests the successful deletion of the current booking
    */
    public function testDeleteBookingWithoutFailure() {
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

        $payload = [
            "event_id" => $event->id,
            "start_latitude" => $start_latitude,
            "start_longitude" => $start_longitude
        ];

        $response = $this->getAvailableBookingOptions($payload);

        $content = json_decode($response->getContent());
        $available = $content->available[0]->bookingInfo;

        $travel = $this->createTravel(
            $event->id,
            $available->start_latitude,
            $available->start_longitude,
            $available->distance,
            $available->duration,
            'uber',
            null
        );

        $payload = [
            'event_id' => $event->id,
            'product_id' => $available->product_id,
            'token' => ApiKeys::$uberClientToken,
            'start_latitude' => $start_latitude,
            'start_longitude' => $start_longitude
        ];

        $endpoint = "api/v1/book";
        $method = "POST";

        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        $content = json_decode($response->getContent());

        $payload = [
            'token' => ApiKeys::$uberClientToken,
        ];

        $endpoint = "api/v1/book";
        $method = "DELETE";

        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        $content = json_decode($response->getContent());
        $this->assertEquals('Booking deletion successful', $content->message);
        $this->assertEquals(200, $response->status());
    }

    /*
	 Tests the unsuccessful deletion of a booking
    */
    public function testDeleteBookingWithFailure() {
        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $payload = [
            'token'         =>  ApiKeys::$uberClientToken,
        ];

        $endpoint = "api/v1/book/";
        $method = "DELETE";

        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        $content = json_decode($response->getContent());
        $this->assertEquals(403, $response->status());
        $this->assertEquals('User has no active booking', $content->message);
    }

}
