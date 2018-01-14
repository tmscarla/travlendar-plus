<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Travel class
* 
* This class defines the model associated 'travels' table
* This model stores information about Travels.
*
*/
class Travel extends Model {

    /**
    * @var string $table associates a table with the model class 
    */
    protected $table = 'travels';

    /** 
    * @var string $primaryKey specifies a different primary key 
    * when such key is different from the default identifier 'id'
    */
    protected $primaryKey = 'eventId';

    /** 
    * @var boolean $timestamps whether to include timestamps in the database table
    */
    public $timestamps = false;

    /**
    * Travel object builder
    *
    * @param integer $eventId identifier of the Event associated with the Travel
    * @param float   $startLongitude the longitude of the start location of the Travel
    * @param float   $startLatitude the latitude of the start location of the Travel
    * @param integer $distance the distance of travel in meters
    * @param integer $duration the duration of travel in meters
    * @param string  $mean the mean of transport
    * @param integer|null $bookingId the identifier of the associated booking
    *
    * @return void
    */
    function build($eventId, $startLongitude, $startLatitude, $distance, $duration, $mean, $bookingId) {
		$this->eventId = $eventId;
	    $this->startLongitude = $startLongitude;
	    $this->startLatitude = $startLatitude;
	    $this->distance = $distance;
	    $this->duration = $duration;
	    $this->mean = $mean;
	    $this->bookingId = $bookingId;
	}

    /**
    * Defines a relation of belonging to the Event Model
    * (Travel) eventId -> (Event) id
    */
    public function event(){
        return $this->belongsTo('App\Models\Event', 'eventId');
    }

    /**
     * Defines a relation of ownership of the Booking Model
     * (Travel) bookingId -> (Booking) id
     */
    public function booking() {
        return $this->HasOne('App\Models\Booking', 'id', 'bookingId');
    }

}