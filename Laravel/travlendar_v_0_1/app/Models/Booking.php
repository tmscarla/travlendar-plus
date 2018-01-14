<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This class defines the model associated 'bookings' table
 * This model stores information about bookings associated to the Users.
 *
 */
class Booking extends Model 
{

    /**
     * @var string $table associates a table with the model class
     */
    protected $table = 'bookings';

    /**
     * @var boolean $timestamps whether to include timestamps in the database table
     */
    public $timestamps = false;

    /**
     * Booking object builder
     *
     * @param string $service booking service
     * @param json $bookingInfo information concerning the Booking
     *
     * @return void
     */
    public function build($service, $bookingInfo) {
        $this->service = $service;
        $this->bookingInfo = $bookingInfo;
    }

    /**
     * Defines a relation of belonging to the Travel Model
     * (Booking) id -> (Travel) bookingId
     */
    public function travel(){
        return $this->belongsTo('App\Models\Travel', 'bookingId');
    }
    
}