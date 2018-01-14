<?php
namespace App\Http\Interfaces\ExternalInterfaces;

use App\Http\Helpers\ApiKeys;
use App\Models\Travel;

/**
 * This class provides methods for handling the request associated to the MapsInterface
 * in order to retrieve the available travel options given by the Google Directions API.
 */
class GoogleService {

    /**
     * This method retrieve travel information provided by Google Directions API.
     * More specifically, the options returned are the one associated to the following
     * travel means:
     *
     * transit -> public transport
     * driving -> personal car
     * walking -> walking
     *
     * @param float $startLatitude latitude from which the travel starts
     *
     * @param float $startLongitude longitude from which the travel starts
     *
     * @param float $endLatitude latitude associated to the arrival location of the travel
     *
     * @param float $endLongitude longitude associated to the arrival location of the travel
     *
     * @return an array containing
     *     {
     *         'travels'   => (array) the array of available travel options (if exists)
     *         'message' => (string) response message.
     *     }
     */
    public static function getTravels($startLatitude, $startLongitude, $endLatitude, $endLongitude, $arrival_time) {
        $mode = ['transit', 'driving', 'walking'];
        $availableTravels = array();

        for ($i = 0; $i < count($mode); $i++) {
            $availableTravelsSingle = array();
            $mean = $mode[$i];
            $curl = curl_init();

            $start = $startLatitude . ',' . $startLongitude;
            $end = $endLatitude . ',' . $endLongitude;

            $url = 'https://maps.googleapis.com/maps/api/directions/json?' . 'origin=' . $start
                . '&destination=' . $end . '&mode=' . $mean
                . '&arrival_time=' . $arrival_time . '&alternatives=true&key=' . ApiKeys::$googleKey;
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url,
            ));
            $resp = curl_exec($curl);
            curl_close($curl);

            $routes = json_decode($resp);
            if ($routes->status == "OK") {
                foreach ($routes->routes as $r) {
                    $l = $r->legs[0];
                    $travel = new Travel();
                    $travel->mean = $mode[$i];
                    $travel->duration = (int) round($l->duration->value, 0);
                    $travel->distance = (int) round($l->distance->value, 0);
                    $travel->startLatitude = $startLatitude;
                    $travel->startLongitude = $startLongitude;
                    array_push($availableTravelsSingle, $travel);
                }
                $message = "success";
            }
            else {
                $message = "Error: no available Google travels";#$routes->status;
            }
            $availableTravels[$mean] = array("travels" => $availableTravelsSingle, "message" => $message);
        }
        return $availableTravels;
    }
}