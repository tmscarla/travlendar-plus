//
//  Booking.swift
//  Travlendar+
//
//  Created by Tommaso Scarlatti.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//
//  This is the booking model. It contains a main struct BookingInfo which is used to
//  encapsulate all the booking-related information received from the server.
//
//  It also holds a global variable selectedBookingOption which is used to store the
//  currently available booking options retrieved from the server

import Foundation
import UberCore
import Alamofire

var selectedBookingOptions: [BookingOption]?

// Wrapper structure for JSON decoding
struct RawBooking: Codable {
    var message: String
    var available: [BookingOption]?
}

struct BookingOption : Codable {
    var service: String?
    var bookingInfo: BookingInfo?
}

struct BookingInfo : Codable {
    var product_id: String?
    var request_id: String?
    var type: String?
    var duration: Int?
    var distance: Int?
    var price_low: Int?
    var price_high: Int?
    var start_latitude: Double?
    var start_longitude: Double?
    var end_latitude: Double?
    var end_longitude: Double?
}

/* HTTP REQUESTS */

/**
 Delete a booking from Uber.
 
 - Parameter token: the Travlendar+ token.
 - Parameter uberAccessToken: the Uber access token given after the log in.
 - Parameter bookingId: the id of the booking to delete.
 
 - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
 - Parameter statusCode: the HTTP status code returned from the POST request.
 - Parameter data: the data returned from the server in a JSON format.
 */
func deleteBooking(authenticateWith token: String, withUberToken uberAccessToken: AccessToken, bookingId: Int, completion: @escaping (_ statusCode: Int, _ data: Data?) -> Void) {
    
    let headers: HTTPHeaders = [
        "Accept": "application/json",
        "Authorization": "Bearer " + token
    ]
    
    let parameters: Parameters = [
        "token": uberAccessToken.tokenString,
        "booking_id": bookingId,
    ]
    
    Alamofire.request(serverName + "api/v1/book/", method: .delete, parameters: parameters, headers: headers).responseJSON { response in
        
        if let statusCode = response.response?.statusCode {
            
            // Success
            if statusCode == 200 {
                if let json = response.result.value {
                    print("JSON: \(json)")
                }
        
            }
            // Error
            else {
                print("Error status code: ", (response.response?.statusCode)!)
                print(response.result.value!)
                
            }
            // Callback function
            completion(statusCode, response.data)
        }
            
        // HTTP request failed
        else {
            
        }
    }
}
