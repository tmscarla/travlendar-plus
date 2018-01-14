<?php

namespace App\Http\Interfaces\ExternalInterfaces;

use App\Http\Helpers\ApiKeys;
use App\Models\Booking;
use App\Models\Travel;

/**
 * This class provides methods for handling the request associated to the MapsInterface and BookingController.
 * Specifically, this class allows to retrieve the available travel options given by the Uber API
 * and Book a trip for a User registered to Uber.
 */
class UberService {

    /**
     * This method makes a GET request to the endpoint api.uber.com/v1.2/estimates/time.
     * It retrieves Estimated Time of Arrival (ETA) of the available Uber services.
     *
     * @param float $latitude latitude from which the travel starts
     *
     * @param float $longitude longitude from which the travel starts
     *
     * @return json $resp cointaining the available travel options and associated ETAs.
     */
    private static function availableETA($latitude, $longitude) {
        $url = 'https://api.uber.com/v1.2/estimates/time?';

        $headers = array(
            "Authorization: Token " . ApiKeys::$uberServerToken,
            "Content-Type: application/json",
            "Accept-Language: en_US"
        );
        $url = $url . 'start_latitude=' . $latitude . '&start_longitude=' . $longitude;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
        ));
        $resp = curl_exec($curl);
        curl_close($curl);

        return $resp;
    }

    /**
     * This method makes a GET request to the endpoint api.uber.com/v1.2/estimates/prices.
     * It retrieves prices of the available Uber services.
     *
     * @param float $startLatitude latitude from which the travel starts
     *
     * @param float $startLongitude longitude from which the travel starts
     *
     * @param float $endLatitude latitude of the end of the travel
     *
     * @param float $endLongitude longitude of the end of the travel
     *
     * @return json $resp cointaining the available travel options and associated prices.
     */
    private static function availablePrice($startLatitude, $startLongitude, $endLatitude, $endLongitude) {
        $url = 'https://api.uber.com/v1.2/estimates/price?';

        $headers = array(
            "Authorization: Token " . ApiKeys::$uberServerToken,
            "Content-Type: application/json",
            "Accept-Language: en_US"
        );
        $url = $url . 'start_latitude=' . $startLatitude . '&start_longitude=' . $startLongitude
                . '&end_latitude=' . $endLatitude . '&end_longitude=' . $endLongitude;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
        ));
        $resp = curl_exec($curl);
        curl_close($curl);

        return $resp;
    }

    /**
     * This method retrieve available travel options (if exists) given by Uber services, calling the methods:
     *      $this->availableETA
     *      $this->availablePrice
     *
     *
     * @param float $startLatitude latitude from which the travel starts
     *
     * @param float $startLongitude longitude from which the travel starts
     *
     * @param float $endLatitude latitude of the end of the travel
     *
     * @param float $endLongitude longitude of the end of the travel
     *
     * @return an array containing
     *     {
     *         'travels'   => (array) the array of available travel options.
     *         'message' => (string) response message.
     *     }
     */
    private static function availableTravels($startLatitude, $startLongitude, $endLatitude, $endLongitude) {
        $prods = array();
        $travels = array();

        $prices = json_decode(self::availablePrice($startLatitude, $startLongitude, $endLatitude, $endLongitude));
        if (isset($prices->message) or empty($prices->prices)) {
            $message = 'Error in Uber services: no available travels';
            $travels['uber'] = array('travels' => $prods, 'message' => $message);
            return $travels;
        }

        $etas = json_decode(self::availableETA($startLatitude, $startLongitude));
        if (isset($etas->message) or empty($etas->times)) {
            $message = 'Error in Uber services: no available travels';
            $travels['uber'] = array('travels' => $prods, 'message' => $message);
            return $travels;
        }

        foreach ($prices->prices as $p) {
            $id = $p->product_id;
            $type = $p->display_name;
            $price_low = $p->low_estimate;
            $price_high = $p->high_estimate;
            $duration = $p->duration;
            $distance = $p->distance;
            foreach ($etas->times as $times) {
                if ($times->product_id == $id) {
                    $estimatedTime = $times->estimate;
                    break;
                }
            }
            $travel = array("product_id" => $id,
                            "request_id" => null,
                            "type" => $type,
                            "duration" => (int) round($duration + $estimatedTime, 0),
                            "distance" => (int) round($distance * 1609.34, 0),
                            "price_low" => $price_low,
                            "price_high" => $price_high,
                            "start_latitude" => $startLatitude,
                            "start_longitude" => $startLongitude,
                            "end_latitude" => $endLatitude,
                            "end_longitude" => $endLongitude);
            array_push($prods, $travel);
        }
        $travels['uber'] = array('travels' => $prods, 'message' => 'success');
        return $travels;
    }

    /**
     * This method retrieve available travel options given by Uber services, handling the request made by
     * MapsInterface.
     * If some travel option exists, returns an array of Travel .
     *
     * @param float $startLatitude latitude from which the travel starts
     *
     * @param float $startLongitude longitude from which the travel starts
     *
     * @param float $endLatitude latitude of the end of the travel
     *
     * @param float $endLongitude longitude of the end of the travel
     *
     * @return an array containing
     *     {
     *         'travels'   => (array) the array of Travel.
     *         'message' => (string) response message.
     *     }
     */
    public static function getTravels($startLatitude, $startLongitude, $endLatitude, $endLongitude) {
        $travels = self::availableTravels($startLatitude, $startLongitude, $endLatitude, $endLongitude);
        $availableTravels = array();
        #return $travels['uber']['travels'][0]['product_id'];
        $message = $travels['uber']['message'];
        if ($message == "success") {
            foreach ($travels['uber']['travels'] as $t) {
                $trav = new Travel();
                $trav->mean = "uber";
                $trav->duration = $t['duration'];
                $trav->distance = $t['distance'];
                $trav->startLatitude = $startLatitude;
                $trav->startLongitude = $startLongitude;
                array_push($availableTravels, $trav);
            }
        }
        $tr['uber'] = array('travels' => $availableTravels, 'message' => $message);
        return $tr;
    }

    /**
     * This method makes a POST request to the endpoint sandbox-api.uber.com/v1.2/requests.
     * It is used to make a request of an available Uber service.
     *
     * @param $token User token given after the Uber authentication.
     *
     * @param $productId identifier of a specific Uber product.
     *
     * @param float $startLatitude latitude from which the travel starts.
     *
     * @param float $startLongitude longitude from which the travel starts.
     *
     * @param float $endLatitude latitude of the end of the travel.
     *
     * @param float $endLongitude longitude of the end of the travel.
     *
     * @return json $resp cointaining the information concerning the booking.
     */
    private static function requestRide($token, $productId, $startLatitude, $startLongitude, $endLatitude, $endLongitude) {
        $url = 'https://sandbox-api.uber.com/v1.2/requests';

        $headers = array(
            "Authorization: Bearer " . $token,
            "Accept-Language: en_US",
            "Content-Type: application/json"
        );

        $fields = array(
            'product_id' => $productId,
            'start_latitude' => $startLatitude,
            'start_longitude' => $startLongitude,
            'end_latitude' => $endLatitude,
            'end_longitude' => $endLongitude
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => json_encode($fields),
                CURLOPT_HTTPHEADER => $headers
            )
        );

        $resp = curl_exec($curl);
        curl_close($curl);

        return $resp;
    }

    /**
     * This method makes a DELETE request to the endpoint sandbox-api.uber.com/v1.2/requests/current.
     * It is used to delete the current Uber service previously booked.
     *
     * @param $token User token given after the Uber authentication.
     *
     * @return json $resp the confirmation of the elimination
     */
    private static function deleteCurrentRide($token) {
        $url = 'https://sandbox-api.uber.com/v1.2/requests/current';

        $headers = array(
            "Authorization: Bearer " . $token,
            "Accept-Language: en_US",
            "Content-Type: application/json"
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers
            )
        );

        $resp = curl_exec($curl);
        curl_close($curl);

        return $resp;
    }

    /**
     * This method makes a GET request to the endpoint sandbox-api.uber.com/v1.2/requests/current.
     * It is used to retrieve the current Uber service previously booked.
     *
     * @param $token User token given after the Uber authentication.
     *
     * @return json $resp cointaining the information concerning the current booking.
     */
    private static function currentRide($token) {
        $url = 'https://sandbox-api.uber.com/v1.2/requests/current';

        $headers = array(
            "Authorization: Bearer " . $token,
            "Accept-Language: en_US",
            "Content-Type: application/json"
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers
            )
        );

        $resp = curl_exec($curl);
        curl_close($curl);

        return $resp;
    }

    /**
     * This method retrieve available booking options given by Uber services, handling the request made by
     * BookingController.
     * If some travel option exists, returns an array of Bookings.
     *
     * @param float $startLatitude latitude from which the travel starts
     *
     * @param float $startLongitude longitude from which the travel starts
     *
     * @param float $endLatitude latitude of the end of the travel
     *
     * @param float $endLongitude longitude of the end of the travel
     *
     * @return an array containing
     *     {
     *         'bookings'   => (array) the array of available Booking options.
     *         'message' => (string) response message.
     *     }
     */
    public static function getBookingOptions($startLatitude, $startLongitude, $endLatitude, $endLongitude) {
        $availableTravels = self::availableTravels($startLatitude, $startLongitude, $endLatitude, $endLongitude);
        $message = $availableTravels['uber']['message'];
        $bookings = array();

        if ($message == "success") {
            foreach ($availableTravels['uber']['travels'] as $av) {
                $bookOpt = new Booking();
                $bookOpt->service = 'uber';
                $bookOpt->bookingInfo = $av;
                array_push($bookings, $bookOpt);
            }
        }
        return array("bookings" => $bookings, "message" => $message);
    }

    /**
     * This method handles the request made by BookingController in order to book one of
     * the available Uber product.
     * If the booking is accepted, returns the current Booking.
     *
     * @param $token User token given after the Uber authentication.
     *
     * @param $productId identifier of a specific Uber product.
     *
     * @param $type the type of Uber product.
     *
     * @param int $duration the estimated duration of the Uber travel.
     *
     * @param int $distance the estimated distance of the Uber travel.
     *
     * @param int $price_low the estimated cost (lower bound) of the Uber booking.
     *
     * @param int $price_high the estimated cost (upper bound) of the Uber booking.
     *
     * @param float $startLatitude latitude from which the travel starts
     *
     * @param float $startLongitude longitude from which the travel starts
     *
     * @param float $endLatitude latitude of the end of the travel
     *
     * @param float $endLongitude longitude of the end of the travel
     *
     * @return an array containing
     *     {
     *         'booking'   => the current Booking.
     *         'message' => (string) response message.
     *     }
     */
    public static function bookTravel($token, $productId, $type, $duration, $distance, $price_low, $price_high,
                                      $startLatitude, $startLongitude, $endLatitude, $endLongitude) {
        $bookingRequest = json_decode(self::requestRide($token, $productId, $startLatitude,
                                $startLongitude, $endLatitude, $endLongitude));
        $booking = new Booking();
        if (isset($bookingRequest->status)) {
            $request_id = $bookingRequest->request_id;
            $travel = array("product_id" => $productId,
                "request_id" => $request_id,
                "type" => $type,
                "duration" => $duration,
                "distance" => $distance,
                "price_low" => $price_low,
                "price_high" => $price_high,
                "start_lat" => $startLatitude,
                "start_lon" => $startLongitude,
                "end_lat" => $endLatitude,
                "end_lon" => $endLongitude);
            $booking->service = 'uber';
            $booking->bookingInfo = json_encode($travel);
            $message = "success";
        }
        elseif (isset($bookingRequest->message)) {
            $message = $bookingRequest->code;
        }
        elseif (isset($bookingRequest->errors)) {
            $message = $bookingRequest->errors[0]->code;
        }
        return array("booking" => $booking, "message" => $message);
    }

    /**
     * This method handles the BookingController request in order to delete the current booking.
     * It is used to delete the current Uber service previously booked, calling the method $this->deleteRide.
     *
     * @param $token User token given after the Uber authentication.
     *
     * @return json $resp the confirmation of the elimination
     */
    public static function deleteCurrentBooking($token) {
        $del = UberService::deleteCurrentRide($token);
        return $del;
    }

    /**
     * This method handles the BookingController request in order to retrieve the current booking.
     * It is used to retrieve the current Uber service previously booked, calling the method $this->currentRide.
     *
     * @param $token User token given after the Uber authentication.
     *
     * @return json $resp the current Booking information (if exists)
     */
    public static function getCurrentBooking($token) {
        $current = UberService::currentRide($token);

        return $current;
    }
}