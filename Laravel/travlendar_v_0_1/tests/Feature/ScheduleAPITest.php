<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\FlexibleEvent;
use App\Models\Event;
use App\Models\Travel;
use Tests\TestCase;
use Carbon\Carbon;
use App\User;


/*
    This class contains tests concerning the creation and management of event related information.
*/

class ScheduleTest extends TestCase{

	/*
		Each test is wrapped in a database transaction and therefore is indipendent.
	*/
    use DatabaseTransactions;

    /*
		The following functions are used to populate the database in order to test different scenarios and they are not considered as tests.
    */

	/* START HELPER FUNCTIONS */

    private function createEvent($userId, $title, $start, $end, $longitude, $latitude, $category, $description){
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

    private function createTravel($eventId, $startLongitude, $startLatitude, $distance, $travelDuration, $mean, $bookingId){
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

    private function createEventWithTravel($userId, $title, $start, $end, $longitude, $latitude, $category, $description,
    									   $startLongitude, $startLatitude, $distance, $travelDuration, $mean, $bookingId){
        $event = $this->createEvent(
	        $userId,
	        $title,
	        $start,
	        $end,
	        $longitude,
	        $latitude,
	        $category,
	        $description
      	);

        $travel = $this->createTravel(
        	$event->id,
			$startLongitude,
			$startLatitude,
			$distance,
			$travelDuration,
			$mean,
			$bookingId
        );

        $event->travel = $travel;

        return $event;
    }

    private function createFlexibleInfo($eventId, $lowerBound, $upperBound, $duration){

		$flexibleInfo = new FlexibleEvent();

		$flexibleInfo->build(
			$eventId,
			$lowerBound,
			$upperBound,
			$duration
		);

		$flexibleInfo->save();

		return $flexibleInfo;

    }

    private function createFlexibleEvent($userId, $title, $start, $end, $longitude, $latitude, $category, $description,
    									 $lowerBound, $upperBound, $duration){

    	$event = $this->createEvent(
	        $userId,
	        $title,
	        $start,
	        $end,
	        $longitude,
	        $latitude,
	        $category,
	        $description
      	);

    	$flexibleInfo = $this->createFlexibleInfo(
    		$event->id,
    		$lowerBound,
    		$upperBound,
    		$duration
    	);

    	$event->flexibleInfo = $flexibleInfo;

    	return $event;
    }

    /* END HELPER FUNCTIONS */

    /*
		The following functions are the tests performed.
    */

	/* START TEST FUNCTIONS */

    /*
	 Tests the creation of a standard event that causes no conflicts with other events in the schedule
    */
    public function testCreateStandardEventWithoutConflict(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

		$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	false,
			"flexible" 		=> 	false,
			"travel" 		=> 	false,
			"adjustements" 	=> 	[]
		];

		$endpoint = "api/v1/event";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Event creation successful", $content->message);
        $this->assertDatabaseHas(
        	"events", 
	        [
	        	"userId"		=>  $user->id,
	        	"id" 			=> 	$content->events[0]->id,
	        	"title" 		=> 	$payload["title"],
	        	"start" 		=> 	$payload["start"],
				"end"			=> 	$payload["end"],
				"longitude" 	=> 	$payload["longitude"],
				"latitude"	 	=> 	$payload["latitude"],
				"description"	=> 	$payload["description"],
				"category" 		=> 	$payload["category"],
			]
		);

    }

    /*
	 Tests the creation of an event with travel that causes no conflicts with other events in the schedule
    */
    public function testCreateEventWithTravelWithoutConflict(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

		$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	false,
			"flexible" 		=> 	false,
			"travel" 		=> 	true,
			"mean"			=> 	"walking",
			"travelDuration"=> 	300,
			"distance"		=> 	1500,
			"startLatitude" => 	45.476851,
			"startLongitude"=> 	9.225882,
			"adjustements" 	=> 	[]
		];

		$endpoint = "api/v1/event";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Event creation successful", $content->message);
        $this->assertDatabaseHas(
        	"events", 
	        [
	        	"userId"		=>  $user->id,
	        	"id" 			=> 	$content->events[0]->id,
	        	"title" 		=> 	$payload["title"],
	        	"start" 		=> 	$payload["start"],
				"end"			=> 	$payload["end"],
				"longitude" 	=> 	$payload["longitude"],
				"latitude"	 	=> 	$payload["latitude"],
				"description"	=> 	$payload["description"],
				"category" 		=> 	$payload["category"],
			]
		);
        $this->assertDatabaseHas(
        	"travels", 
	        [
				"mean"			=> 	$payload["mean"],
				"duration"		=> 	$payload["travelDuration"],
				"distance"		=> 	$payload["distance"],
				"startLatitude" => 	$payload["startLatitude"],
				"startLongitude"=> 	$payload["startLongitude"],
			]
		);
    }

    /*
	 Tests the creation of a standard event that causes a conflict with events already present in the schedule
    */
    public function testCreateStandardEventWithConflict(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $schedule = [
	        $this->createEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 10, 0, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test"
	        ),
	        $this->createEvent(
	        	$user->id,
	        	"test1", 
	        	Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 11, 0, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test"
	        ),
    	];

		$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 9, 30, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	false,
			"flexible" 		=> 	false,
			"travel" 		=> 	false,
			"adjustements" 	=> 	[]
		];

		$endpoint = "api/v1/event";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $conflictIds = array_map(function($idObj) {
            return $idObj->id;
        }, $content->feasibility->errors[0]->details);

        $this->assertEquals(400, $response->status());
        $this->assertEquals("Feasibility check failed", $content->message);

        foreach ($schedule as $event) {
        	$this->assertTrue(in_array($event->id, $conflictIds));
        }

        $this->assertDatabaseMissing(
        	"events", 
	        [
	        	"userId"		=>  $user->id,
	        	"id" 			=> 	$content->feasibility->errors[0]->event->id,
	        	"title" 		=> 	$payload["title"],
	        	"start" 		=> 	$payload["start"],
				"end"			=> 	$payload["end"],
				"longitude" 	=> 	$payload["longitude"],
				"latitude"	 	=> 	$payload["latitude"],
				"description"	=> 	$payload["description"],
				"category" 		=> 	$payload["category"],
			]
		);

    }


    /*
	 Tests the creation of a standard event that causes a conflict with events already present in the schedule because of the associated travel
    */
    public function testCreateStandardEventWithConflictWithTravel(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $schedule = [
        	$this->createEventWithTravel(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 10, 0, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test",
    			45.489868,
    			9.225768,
    			1800, 
    			1800,
    			"driving",
    			null
    		)
    	];


    	$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 7, 0, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 7, 45, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	false,
			"flexible" 		=> 	false,
			"travel" 		=> 	false,
			"adjustements" 	=> 	[]
		];

		$endpoint = "api/v1/event";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $conflictIds = array_map(function($idObj) {
            return $idObj->id;
        }, $content->feasibility->errors[0]->details);

        $this->assertEquals(400, $response->status());
        $this->assertEquals("Feasibility check failed", $content->message);

        foreach ($schedule as $event) {
        	$this->assertTrue(in_array($event->id, $conflictIds));
        }

        $this->assertDatabaseMissing(
        	"events", 
	        [
	        	"userId"		=>  $user->id,
	        	"id" 			=> 	$content->feasibility->errors[0]->event->id,
	        	"title" 		=> 	$payload["title"],
	        	"start" 		=> 	$payload["start"],
				"end"			=> 	$payload["end"],
				"longitude" 	=> 	$payload["longitude"],
				"latitude"	 	=> 	$payload["latitude"],
				"description"	=> 	$payload["description"],
				"category" 		=> 	$payload["category"],
			]
		);
    }

    /*
	 Tests the creation of an event with a travel that causes a conflict with events already present in the schedule
    */
    public function testCreateEventWithTravelWithConflict(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $schedule = [
	        $this->createEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 7, 0, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 7, 45, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test"
	        )
    	];


		$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	false,
			"flexible" 		=> 	false,
			"travel" 		=> 	true,
			"mean"			=> 	"walking",
			"travelDuration"=> 	1800,
			"distance"		=> 	15000,
			"startLatitude" => 	45.476851,
			"startLongitude"=> 	9.225882,
			"adjustements" 	=> 	[]
		];

		$endpoint = "api/v1/event";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $conflictIds = array_map(function($idObj) {
            return $idObj->id;
        }, $content->feasibility->errors[0]->details);

        $this->assertEquals(400, $response->status());
        $this->assertEquals("Feasibility check failed", $content->message);

        foreach ($schedule as $event) {
        	$this->assertTrue(in_array($event->id, $conflictIds));
        }

        $this->assertDatabaseMissing(
        	"events", 
	        [
	        	"userId"		=>  $user->id,
	        	"id" 			=> 	$content->feasibility->errors[0]->event->id,
	        	"title" 		=> 	$payload["title"],
	        	"start" 		=> 	$payload["start"],
				"end"			=> 	$payload["end"],
				"longitude" 	=> 	$payload["longitude"],
				"latitude"	 	=> 	$payload["latitude"],
				"description"	=> 	$payload["description"],
				"category" 		=> 	$payload["category"],
			]
		);

		$this->assertDatabaseMissing(
        	"travels", 
	        [
				"mean"			=> 	$payload["mean"],
				"duration"		=> 	$payload["travelDuration"],
				"distance"		=> 	$payload["distance"],
				"startLatitude" => 	$payload["startLatitude"],
				"startLongitude"=> 	$payload["startLongitude"],
			]
		);
    }

    /*
	 Tests the creation of an event that does not causes a conflict with a flexbile event since we take into account the flexbile bounds
    */
    public function testCreateStandardEventWithFixedFlexibleConflict(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $schedule = [
	        $this->createFlexibleEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 7, 30, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 11, 30, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test",
	      		Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
	      		Carbon::create(2019, 1, 1, 10, 0, 0)->timestamp,
	      		3600
	        )
    	];


		$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 7, 0, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 8, 30, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	false,
			"flexible" 		=> 	false,
			"travel" 		=> 	false,
			"adjustements" 	=> 	[]
		];

		$endpoint = "api/v1/event";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Event creation successful", $content->message);
        $this->assertDatabaseHas(
        	"events", 
	        [
	        	"userId"		=>  $user->id,
	        	"id" 			=> 	$content->events[0]->id,
	        	"title" 		=> 	$payload["title"],
	        	"start" 		=> 	$payload["start"],
				"end"			=> 	$payload["end"],
				"longitude" 	=> 	$payload["longitude"],
				"latitude"	 	=> 	$payload["latitude"],
				"description"	=> 	$payload["description"],
				"category" 		=> 	$payload["category"],
			]
		);
    }

    /*
	 Tests the creation of a repetitive event that does not causes conflicts
    */
    public function testCreateRepetitiveEventWithoutConflict(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

		$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 7, 0, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 8, 30, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	true,
			"until"			=> 	Carbon::create(2019, 2, 1, 7, 0, 0)->timestamp,
			"frequency"		=>  'day',
			"flexible" 		=> 	false,
			"travel" 		=> 	false,
			"adjustements" 	=> 	[]
		];

		$endpoint = "api/v1/event";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Event creation successful", $content->message);

        $start = $payload["start"];
        $end = $payload["end"];
        $until = $payload["until"];
        $frequency = $payload["frequency"];
        $it = 0;

        while ($start <= $until) {

        	$this->assertDatabaseHas(
	        	"events", 
		        [
		        	"userId"		=>  $user->id,
		        	"id" 			=> 	$content->events[$it]->id,
		        	"title" 		=> 	$payload["title"],
		        	"start" 		=> 	$start,
					"end"			=> 	$end,
					"longitude" 	=> 	$payload["longitude"],
					"latitude"	 	=> 	$payload["latitude"],
					"description"	=> 	$payload["description"],
					"category" 		=> 	$payload["category"],
				]
			);

			$this->assertDatabaseHas(
	        	"repetitiveEvents", 
		        [
		        	"eventId"		=>  $content->events[$it]->id,
		        	"groupId" 		=> 	$content->events[$it]->repetitive_info->groupId,

				]
			);

			$it++;
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
	            break;
	        }
        }

    }

    /*
	 Tests the creation of a repetitive event that causes a conflict with events already present in the schedule
    */
    public function testCreateRepetitiveEventWithConflict(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $schedule = [
	        $this->createEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 10, 0, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test"
	        )
    	];

		$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 7, 0, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 8, 30, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	true,
			"until"			=> 	Carbon::create(2019, 2, 1, 7, 0, 0)->timestamp,
			"frequency"		=>  'day',
			"flexible" 		=> 	false,
			"travel" 		=> 	false,
			"adjustements" 	=> 	[]
		];

		$endpoint = "api/v1/event";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $this->assertEquals(400, $response->status());
        $this->assertEquals("Feasibility check failed", $content->message);

		$this->assertDatabaseMissing(
        	"repetitiveEvents", 
	        [
	        	"groupId" 		=> 	$content->feasibility->errors[0]->event->repetitive_info->groupId,

			]
		);

    }

    /*
	 Tests the creation of a standard Event that does not cause a conflict with a flexible event in the schedule since the right adjustements are provided
    */
    public function testCreateStandardEventWithFixableFlexibleConflict(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $schedule = [
	        $this->createFlexibleEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 7, 30, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 11, 30, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test",
	      		Carbon::create(2019, 1, 1, 7, 30, 0)->timestamp,
	      		Carbon::create(2019, 1, 1, 11, 30, 0)->timestamp,
	      		3600
	        )
    	];


		$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 7, 0, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 8, 30, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	false,
			"flexible" 		=> 	false,
			"travel" 		=> 	false,
			"adjustements" 	=> 	[$schedule[0]->id => [Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
													  Carbon::create(2019, 1, 1, 11, 30, 0)->timestamp]]
		];

		$endpoint = "api/v1/event";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Event creation successful", $content->message);
        $this->assertDatabaseHas(
        	"events", 
	        [
	        	"userId"		=>  $user->id,
	        	"id" 			=> 	$content->events[0]->id,
	        	"title" 		=> 	$payload["title"],
	        	"start" 		=> 	$payload["start"],
				"end"			=> 	$payload["end"],
				"longitude" 	=> 	$payload["longitude"],
				"latitude"	 	=> 	$payload["latitude"],
				"description"	=> 	$payload["description"],
				"category" 		=> 	$payload["category"],
			]
		);
    }

    /*
	 Tests the creation of a flexible Event that does not cause a conflict with a standard event in the schedule since the right adjustements are provided
    */
    public function testCreateFlexibleEventWithFixableConflict(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $schedule = [
	        $this->createEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 11, 30, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test"
	        )
    	];

		$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 7, 0, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 8, 30, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	false,
			"flexible" 		=> 	true,
			"duration"		=> 	1800,
			"lowerBound"	=> 	Carbon::create(2019, 1, 1, 7, 0, 0)->timestamp,
			"upperBound"	=> 	Carbon::create(2019, 1, 1, 8, 30, 0)->timestamp,
			"travel" 		=> 	false,
			"adjustements" 	=> 	["-1" => [Carbon::create(2019, 1, 1, 7, 0, 0)->timestamp,
										Carbon::create(2019, 1, 1, 7, 30, 0)->timestamp]]
		];

		$endpoint = "api/v1/event";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Event creation successful", $content->message);
        $this->assertDatabaseHas(
        	"events", 
	        [
	        	"userId"		=>  $user->id,
	        	"id" 			=> 	$content->events[0]->id,
	        	"title" 		=> 	$payload["title"],
	        	"start" 		=> 	$payload["start"],
				"end"			=> 	$payload["end"],
				"longitude" 	=> 	$payload["longitude"],
				"latitude"	 	=> 	$payload["latitude"],
				"description"	=> 	$payload["description"],
				"category" 		=> 	$payload["category"],
			]
		);
    }

	/*
	 Test the deletion of an event
    */
    public function testDeleteEvent(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $schedule = [
	        $this->createEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 11, 30, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test"
	        )
    	];

		$endpoint = "api/v1/event/".$schedule[0]->id;
        $method = "DELETE";
        
        $response = $this->call($method, $endpoint, [] ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Event deleted", $content->message);
        $this->assertDatabaseMissing(
        	"events", 
	        [
	        	"userId"		=>  $user->id,
	        	"id" 			=> 	$schedule[0]->id,
			]
		);
    }

	/*
	 Test the request of an event information
    */
    public function testGetEventInformation(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $schedule = [
	        $this->createEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 11, 30, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test"
	        )
    	];

		$endpoint = "api/v1/event/".$schedule[0]->id;
        $method = "GET";
        
        $response = $this->call($method, $endpoint, [],[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Request successful", $content->message);
        $this->assertDatabaseHas(
        	"events", 
	        [
	        	"userId"		=>  $user->id,
	        	"id" 			=> 	$schedule[0]->id,
	        	"title" 		=> 	$schedule[0]->title,
	        	"start" 		=> 	$schedule[0]->start,
				"end"			=> 	$schedule[0]->end,
				"longitude" 	=> 	$schedule[0]->longitude,
				"latitude"	 	=> 	$schedule[0]->latitude,
				"description"	=> 	$schedule[0]->description,
				"category" 		=> 	$schedule[0]->category,
			]
		);
    }


	/* END TEST FUNCTIONS */

}
