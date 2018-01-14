<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* Event class
* 
* This class defines the model associated 'events' table
* This model stores information about Events.
*
*/
class Event extends Model {

    /**
    * @var string $table associates a table with the model class 
    */
    protected $table = 'events';

    /** 
    * @var boolean $timestamps whether to include timestamps in the database table
    */
    public $timestamps = false;

    /**
    * Event object builder
    *
    * @param integer $userId identifier of the User associated with the Event
    * @param string  $title the title of the Event
    * @param integer $start unix epoch specifying the start of the event
    * @param integer $end unix epoch specifying the end of the event
    * @param float   $longitude longitude of the location of the Event
    * @param float   $latitude latitude of the location of the Event
    * @param string  $category category of the event
    * @param string  $description description of the event
    *
    * @return void
    */
	function build($userId, $title, $start, $end, $longitude, $latitude, $category, $description) {
		$this->userId = $userId;
        $this->title = $title;
	    $this->start = $start;
	    $this->end = $end;
	    $this->longitude = $longitude;
	    $this->latitude = $latitude;
	    $this->category = $category;
	    $this->description = $description;
	}

    /**
    * Defines a relation of belonging to the User Model
    * (Event) userId -> (User) id
    */
    public function user(){
        return $this->belongsTo('App\User', 'userId');
    }

    /**
    * Defines a relation of ownership of the Travel Model
    * (Event) id -> (Travel) eventId
    */
    public function travel(){
    	return $this->hasOne('App\Models\Travel', 'eventId');
    }

    /**
    * Defines a relation of ownership of the FlexibleEvent Model
    * (Event) id -> (FlexibleEvent) eventId
    */
    public function flexibleInfo(){
    	return $this->hasOne('App\Models\FlexibleEvent', 'eventId');
    }

    /**
    * Defines a relation of ownership of the RepetitiveEvent Model
    * (Event) id -> (RepetitiveEvent) eventId
    */
    public function repetitiveInfo(){
        return $this->hasOne('App\Models\RepetitiveEvent', 'eventId');
    }

}