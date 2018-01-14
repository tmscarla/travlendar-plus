<?php

namespace App\Http\Interfaces;

use App\Http\Interfaces\ExternalInterfaces\UberService;
use App\Models\Travel;
use App\Models\Event;
use App\Http\Controllers\Controller;
use App\Http\Interfaces\ExternalInterfaces\GoogleService;
use App\Http\Interfaces\ExternalInterfaces\MapboxService;

use function PHPSTORM_META\type;

/**
 * This class is an interface that provides methods for handling the request
 * associated to the ScheduleController.
 * This interface provides methods to retrieve available travel options for a given Event and starting location.
 */
class MapsInterface {

    /**
     * This method retrieve travel information provided by
     * Google Directions API, MapBox API and Uber API.
     *
     * @param Event $event the event of which the available travel options are required.
     *
     * @param float $startLatitude latitude from which the travel starts
     *
     * @param float $startLongitude longitude from which the travel starts
     *
     * @return array cointaining the available travel options
     */
    public static function getAvailableTravel(Event $event, $startLatitude, $startLongitude) {
        $endLongitude = $event->longitude;
        $endLatitude = $event->latitude;
        $arrival_time = $event->start;

        $googleTravels = GoogleService::getTravels($startLatitude, $startLongitude, $endLatitude, $endLongitude, $arrival_time);
        $mapTravels = MapboxService::getTravels( $startLatitude, $startLongitude, $endLatitude, $endLongitude);
        $uberTravels = UberService::getTravels( $startLatitude, $startLongitude, $endLatitude, $endLongitude);

        $availableTravels = array_merge($googleTravels, $mapTravels, $uberTravels);

        return $availableTravels;

    }
}