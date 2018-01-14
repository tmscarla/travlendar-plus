<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* RepetitiveEvent class
* 
* This class defines the model associated 'repetitiveEvents' table
* This model stores information about repetitive Events.
*
*/
class RepetitiveEvent extends Model {

    /**
    * @var string $table associates a table with the model class 
    */
    protected $table = 'repetitiveEvents';
    
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
    * RepetitiveEvent object builder
    *
    * @param integer $eventId identifier of the Event associated with the Travel
    * @param string  $groupId the identifier for the repetition group
    * @param string $frequency indicating the frequency of repetition (day, week, month, year)
    * @param integer $until unix epoch timestamp indicating the limit of the repetitions
    *
    * @return void
    */
	function build($eventId, $groupId, $frequency, $until) {
		$this->eventId = $eventId;
		$this->groupId = $groupId;
        $this->frequency = $frequency;
        $this->until = $until;
	}

    /**
    * Defines a relation of belonging to the Event Model
    * (RepetitiveEvent) userId -> (Event) id
    */
    public function event(){
        return $this->belongsTo('App\Models\Event', 'eventId');
    }

}