<?php

namespace App\Http\Interfaces;

use App\Http\Interfaces\ExternalInterfaces\UberService;
use App\Models\Booking;
use App\Models\Event;

/**
 * This class is an interface that provides methods for handling the request
 * associated to the BookingController.
 * This interface provides methods to retrieve available booking options for a given Event and starting location,
 * and to book one of the Booking option previously requested.
 */
class BookingInterface {

    /**
     * This method call the the method getBookingOptions of UberService in order
     * to retrieve the available Uber options given by Uber API.
     *
     * @param Event $ev the event of which travel the available booking options are requested
     *
     * @param float $startLatitude latitude from which the travel starts
     *
     * @param float $startLongitude longitude from which the travel starts
     *
     * @return an array containing
     *     {
     *         'message'   => (string) response message.
     *         'available' => (array) the array of available booking options.
     *     }
     */
    public static function bookingOptions(Event $ev, $startLatitude, $startLongitude) {
        $endLatitude = (float) $ev->latitude;
        $endLongitude = (float) $ev->longitude;
        $availableOptions = UberService::getBookingOptions($startLatitude, $startLongitude, $endLatitude, $endLongitude);
        return $availableOptions;
    }

    /**
     * This method call the the method bookTravel of UberService in order
     * to book one of the available Uber options previously requested.
     *
     * @param $token User token given after the Uber authentication.
     *
     * @param Booking $book one of the available Booking option.
     *
     * @return an array containing
     *     {
     *         'message'   => (string) response message.
     *         'booking' => (Booking) the booking information.
     *     }
     */
    public static function bookRide($token, Booking $book) {
        $bookingOpt = $book['bookingInfo'];
        $productId = $bookingOpt['product_id'];
        $startLatitude = (float) $bookingOpt['start_latitude'];
        $startLongitude = (float) $bookingOpt['start_longitude'];
        $endLatitude = (float) $bookingOpt['end_latitude'];
        $endLongitude = (float) $bookingOpt['end_longitude'];
        $type = $bookingOpt['type'];
        $duration = $bookingOpt['duration'];
        $distance = $bookingOpt['distance'];
        $price_low = $bookingOpt['price_low'];
        $price_high = $bookingOpt['price_high'];
        $booking = UberService::bookTravel($token, $productId, $type, $duration, $distance, $price_low, $price_high,
                                            $startLatitude, $startLongitude, $endLatitude, $endLongitude);
        return $booking;
    }

    /**
     * This method handles the BookingController request in order to delete the current booking.
     * It is used to delete the current Uber service previously booked, calling the method deleteCurrentBookig
     * of UberService.
     *
     * @param $token User token given after the Uber authentication.
     *
     * @return json $resp the confirmation of the elimination
     */
    public static function deleteRide($token) {
        return UberService::deleteCurrentBooking($token);
    }

    /**
     * This method handles the BookingController request in order to retrieve the current booking.
     * It is used to retrieve the current Uber service previously booked, calling the method getCurrentBooking
     * of UberService.
     *
     * @param $token User token given after the Uber authentication.
     *
     * @return an array containing
     *     {
     *         'message'   => (string) response message.
     *         'request_id' => (alphanumeric) the booking request identifier.
     *     }
     */
    public static function getCurrentRide($token) {
        $current = json_decode(UberService::getCurrentBooking($token));
        if (isset($current->errors)) {
            $message = "User is not currently on a trip";
            $req_id = null;
        }
        else {
            $message = "Request successful";
            $req_id = $current->request_id;
        }
        return array("message" => $message, "request_id" => $req_id);
    }
}