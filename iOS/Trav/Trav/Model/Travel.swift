//
//  Travel.swift
//  Travlendar+
//
//  Created by Tommaso Scarlatti.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//
//  This is the Travel model. It has a main struct Travel which encapsulates all the
//  travel-related information such as mean, duration, distance and the id of the
//  associated event.
//
//  Furthermore, it has several structures to code/decode travels.
//  It also has dictionaries to map the means name into icons or titles.


import Foundation
import MapKit


struct Travel: Codable {
    var mean: String?
    var duration: Int?
    var distance: Int?
    var eventId: Int?
    var startLatitude: Double?
    var startLongitude: Double?
    var bookingId: Int?
}

struct RawTravel: Codable {
    var options: [RawTravelOption]?
    
}

struct RawTravelOption: Codable {
    var travel: Travel?
    var adjustements: [String : [Int]]?
}


// Map means string to icon
let travelIconFromString: [String: UIImage] = [
    "transit": #imageLiteral(resourceName: "bus_on"),
    "walking": #imageLiteral(resourceName: "walking_on"),
    "cycling": #imageLiteral(resourceName: "bike_on"),
    "driving": #imageLiteral(resourceName: "car_on"),
    "uber": #imageLiteral(resourceName: "uber_on")
]

// Adjust the means title after the decoding
let travelMeansTitle: [String: String] = [
    "transit": "Transit",
    "walking": "Walking",
    "cycling": "Cycling",
    "driving": "Car",
    "uber": "Uber"
]

func getHighestDistance(for mean: String, in travels: [RawTravelOption]) -> Int {
    var highestDistance = 0
    
    for t in travels {
        if let travel = t.travel {
            if travel.mean! == mean {
                if travel.distance! > highestDistance {
                    highestDistance = travel.distance!
                }
            }
        }
    }
    
    return highestDistance
}
