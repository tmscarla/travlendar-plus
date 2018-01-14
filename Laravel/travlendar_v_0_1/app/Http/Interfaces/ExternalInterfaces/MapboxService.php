<?php
namespace App\Http\Interfaces\ExternalInterfaces;

use App\Http\Helpers\ApiKeys;
use App\Models\Travel;

/**
 * This class provides methods for handling the request associated to the MapsInterface
 * in order to retrieve the available travel options given by the MapBox API.
 */
class MapboxService {

    /**
     * This method retrieve travel information provided by MapBox API.
     * More specifically, the options returned are the one associated to the bicycle travel mean.
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
    public static function getTravels($startLatitude, $startLongitude, $endLatitude, $endLongitude) {
        $mean = 'cycling';
        $start = $startLongitude . ',' . $startLatitude;
        $end = $endLongitude . ',' . $endLatitude;
        $availableTravels = array();
        $travels = array();

        $url = 'https://api.mapbox.com/directions/v5/mapbox/' . $mean . '/' . $start . ';' . $end
            . '.json?alternatives=true&geometries=geojson&steps=false&access_token=' . ApiKeys::$mapboxKey;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
        ));

        $resp = curl_exec($curl);
        curl_close($curl);

        $routes = json_decode($resp);
        if (isset($routes->code) and $routes->code == 'Ok') {
            foreach ($routes->routes as $r) {
                $travel = new Travel();
                $travel->mean = $mean;
                $travel->duration = (int) round($r->duration, 0);
                $travel->distance = (int) round($r->distance, 0);
                $travel->startLatitude = $startLatitude;
                $travel->startLongitude = $startLongitude;
                array_push($availableTravels, $travel);
            }
            $message = "success";
        }
        else {
            $message = "Error: no available MapBox travels"; # . $routes->code;
        }
        $travels[$mean] = array("travels" => $availableTravels, "message" => $message);
        return $travels;
    }

}