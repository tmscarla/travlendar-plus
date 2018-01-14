<?php

namespace Tests\Unit;


use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Helpers\FeasibilityManager;
use App\Models\FlexibleEvent;
use App\Models\Event;
use Tests\TestCase;
use Carbon\Carbon;
use App\User;
use stdClass;

/*
    This class contains tests concerning the funtionalities provided by the feasibility manager
*/

class FeasibilityManagerTest extends TestCase{

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

	private function createCSPVariable($id, $lower, $upper, $duration, $travel){
        $var = new stdClass();
        $var->lower = $lower;
        $var->upper = $upper;
        $var->id = $id;
        $var->duration = $duration;
        $var->travel = $travel;
        return $var;
	}

	/* END HELPER FUNCTIONS */

	/*
		The following functions are the tests performed.
    */

	/* START TEST FUNCTIONS */

	/*
		Tests whether the CSP solver returns a valid assignment for a solvable set of variables
	*/
	public function testCSPSolverWithSolution(){

		$variables = [
			$this->createCSPVariable(
				1,
				Carbon::create(2019, 1, 1, 7, 0, 0)->timestamp,
				Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
				1800,
				1800
			),
			$this->createCSPVariable(
				2,
				Carbon::create(2019, 1, 1, 8, 30, 0)->timestamp,
				Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
				1800,
				600
			),
			$this->createCSPVariable(
				3,
				Carbon::create(2019, 1, 1, 8, 30, 0)->timestamp,
				Carbon::create(2019, 1, 1, 10, 0, 0)->timestamp,
				1800,
				600
			)
		];

		$solutions = json_decode(FeasibilityManager::solveCSP($variables, 300, "app/Http/Helpers/externalScripts/"));

		$this->assertTrue(!empty((array) $solutions));

		foreach ($solutions as $key1 => $solution1) {
			$this->assertTrue($solution1[1] > $solution1[0]);
			foreach ($solutions as $key2 => $solution2) {
				$this->assertTrue(!($solution2[0] < $solution1[1] && $solution1[1] < $solution2[1]));
				$this->assertTrue(!($solution2[0] < $solution1[0] && $solution1[0] < $solution2[1]));
				$this->assertTrue(!($solution1[0] < $solution2[0] && $solution1[1] > $solution2[1]));
			}
		}
	}

	/*
		Tests whether the CSP solver returns an empty assignment for a unsolvable set of variables
	*/
	public function testCSPSolverWithoutSolution(){

		$variables = [
			$this->createCSPVariable(
				1,
				Carbon::create(2019, 1, 1, 7, 0, 0)->timestamp,
				Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
				1800,
				1800
			),
			$this->createCSPVariable(
				2,
				Carbon::create(2019, 1, 1, 8, 30, 0)->timestamp,
				Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
				1800,
				600
			),
			$this->createCSPVariable(
				3,
				Carbon::create(2019, 1, 1, 8, 30, 0)->timestamp,
				Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
				600,
				600
			)
		];

		$solutions = json_decode(FeasibilityManager::solveCSP($variables, 300, "app/Http/Helpers/externalScripts/"));

		$this->assertTrue(empty((array)$solutions));

	}

	/*
		Test feasibility check for feasible event
	*/
	public function testFeasibilityCheckFeasible(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $events = [
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

		$feasibility = FeasibilityManager::checkFeasibility($events);

		$this->assertTrue($feasibility['result']);
		$this->assertTrue(empty($feasibility['errors']));

	}

	/*
		Test feasibility check for an event with end before start
	*/
	public function testFeasibilityCheckInconsistentEvent(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $events = [
	        $this->createEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 10, 0, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test"
	        )
    	];

		$feasibility = FeasibilityManager::checkFeasibility($events);

		$this->assertTrue(!$feasibility['result']);
		$this->assertEquals('end before start', $feasibility['errors'][0]['details']);

	}

	/*
		Test feasibility check for a flexible event with flexible bounds too strict
	*/
	public function testFeasibilityCheckFlexibleBoundsTooStrict(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $events = [
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
	      		86400
	        )
    	];

		$feasibility = FeasibilityManager::checkFeasibility($events);

		$this->assertTrue(!$feasibility['result']);
		$this->assertEquals('flexible bounds too strict', $feasibility['errors'][0]['details']);

	}

	/*
		Test feasibility check for a flexible event with inconsistent flexible bounds
	*/
	public function testFeasibilityCheckFlexibleInconsistentBounds(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $events = [
	        $this->createFlexibleEvent(
	        	$user->id,
	        	"test0", 
	        	Carbon::create(2019, 1, 1, 10, 30, 0)->timestamp,
	        	Carbon::create(2019, 1, 1, 11, 30, 0)->timestamp,
	        	45.478133,
	        	9.227356,
	        	"this is a test event",
	      		"test",
	      		Carbon::create(2019, 1, 1, 9, 0, 0)->timestamp,
	      		Carbon::create(2019, 1, 1, 10, 0, 0)->timestamp,
	      		300
	        )
    	];

		$feasibility = FeasibilityManager::checkFeasibility($events);

		$this->assertTrue(!$feasibility['result']);
		$this->assertEquals('flexible bounds do not respect fixed bounds', $feasibility['errors'][0]['details']);

	}

	/*
		Test feasibility check for a flexible event with schedule conflict
	*/
	public function testFeasibilityCheckFlexibleScheduleConflict(){

        $email = "testUser@test.test";
        $user = User::where("email", $email)->first();
        $this->actingAs($user, "api");

        $event = new Event();

        $event->build(
			$user->id,
        	"test0", 
        	Carbon::create(2019, 1, 1, 8, 0, 0)->timestamp,
        	Carbon::create(2019, 1, 1, 10, 0, 0)->timestamp,
        	45.478133,
        	9.227356,
        	"test",
        	"this is a test event"
      	);	        	

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

		$feasibility = FeasibilityManager::checkFeasibility(Array($event));

		$this->assertTrue(!$feasibility['result']);
		$this->assertEquals('Schedule conflict', $feasibility['errors'][0]['cause']);

	}
	/* END TEST FUNCTIONS */

}
