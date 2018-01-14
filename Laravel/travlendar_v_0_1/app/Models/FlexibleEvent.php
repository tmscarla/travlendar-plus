<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* FlexbileEvent class
* 
* This class defines the model associated 'flexibleEvents' table
* This model stores information about flexbile Events.
*
*/
class FlexibleEvent extends Model {

    /**
    * @var string $table associates a table with the model class 
    */
    protected $table = 'flexibleEvents';

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
    * FlexibleEvent object builder
    *
    * @param integer $eventId identifier of the Event associated with the Travel
    * @param integer $lowerBound unix epoch specifying the actual start of the event
    * @param integer $upperBound unix epoch specifying the actual end of the event
    * @param integer $duration the minimum duration of the Flexible Event
    *
    * @return void
    */
	function build($eventId, $lowerBound, $upperBound, $duration) {
		$this->eventId = $eventId;
	    $this->lowerBound = $lowerBound;
	    $this->upperBound = $upperBound;
	    $this->duration = $duration;
	}

    /**
    * Defines a relation of belonging to the Event Model
    * (FlexibleEvent) userId -> (Event) id
    */
    public function event(){
        return $this->belongsTo('App\Models\Event', 'eventId');
    }

}