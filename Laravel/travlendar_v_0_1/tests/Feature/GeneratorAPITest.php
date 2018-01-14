<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Helpers\FeasibilityManager;
use App\Models\FlexibleEvent;
use App\Models\Travel;
use App\Models\Event;
use Tests\TestCase;
use Carbon\Carbon;
use stdClass;
use App\User;


/*
    This class contains tests concerning the funtionalities provided by the feasibility manager
*/


class GeneratorAPITest extends TestCase{

	/*
		Each test is wrapped in a database transaction and therefore is indipendent.
	*/
    use DatabaseTransactions;

    /*
        The following functions are used to populate the database and create various data structures in order to test different scenarios and they are not considered as tests.
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
		Tests the request to generate adjustments to the schedule in order to insert a new standard Event in a fixable schedule
	*/
	public function testFixScheduleForEventSuccess(){

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
	        ),
	        $this->createFlexibleEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 12, 0, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 14, 30, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test",
	      		Carbon::create(2019, 1, 1, 12, 0, 0)->timestamp,
	      		Carbon::create(2019, 1, 1, 14, 30, 0)->timestamp,
	      		3600
	        )
    	];

		$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 10, 30, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 12, 30, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	false,
			"flexible" 		=> 	false,
			"travel" 		=> 	false,
			"adjustements" 	=> 	[]
		];

		$endpoint = "api/v1/generator";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());
        $this->assertEquals(200, $response->status());
        $this->assertEquals("Request successful", $content->message);
        $this->assertTrue(!empty((array) $content->options[0]->adjustements));

    }

    /*
		Tests the request to generate adjustments to the schedule in order to insert a new standard Event in a non fixable schedule
	*/
	public function testFixScheduleForEventFailure(){

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
	        ),
	        $this->createFlexibleEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 12, 0, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 14, 30, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test",
	      		Carbon::create(2019, 1, 1, 12, 0, 0)->timestamp,
	      		Carbon::create(2019, 1, 1, 14, 30, 0)->timestamp,
	      		3600
	        )
    	];

		$payload = [
			"title" 		=> 	"Test",
			"start"			=> 	Carbon::create(2019, 1, 1, 12, 30, 0)->timestamp,
			"end"			=> 	Carbon::create(2019, 1, 1, 14, 30, 0)->timestamp,
			"longitude" 	=> 	45.478133,
			"latitude"	 	=> 	9.227356,
			"description"	=> 	"this is a test event",
			"category" 		=> 	"test",
			"repetitive" 	=> 	false,
			"flexible" 		=> 	false,
			"travel" 		=> 	false,
			"adjustements" 	=> 	[]
		];

		$endpoint = "api/v1/generator";
        $method = "POST";
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ["HTTP_Accept" => "application/json"], [] );
        
        $content = json_decode($response->getContent());
        $this->assertEquals(200, $response->status());
        $this->assertEquals("Request successful", $content->message);
        $this->assertTrue(empty((array) $content->options[0]->adjustements));



    }

	/* END TEST FUNCTIONS */

}




