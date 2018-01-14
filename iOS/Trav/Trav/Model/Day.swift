//
//  Day.swift
//  Travlendar+
//
//  Created by Tommaso Scarlatti.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//
//  This is the day model. It holds all the day-related data such as the date of
//  day currently selected by the user and the events list of the day.
//
//  It also contains methods and structures to make easier the json coding/decoding
//  and the string formatting.


import Foundation
import MapKit
import Alamofire
import SwiftDate


struct Day {
    /// The date of the day currently displayed in the
    static var dayDate: Date = DateInRegion().startOfDay.absoluteDate
    static var eventsOfTheDay: [Event]? = []
}

// Structure to decode the json
struct RawDay: Codable {
    var message: String
    var events: [Event]
}

// Convert a day unit between 0-6 in a identifier of three capital letters
let dayUnitString: [Int:String] = [
    1 : "SUN",
    2 : "MON",
    3 : "TUE",
    4 : "WED",
    5 : "THU",
    6 : "FRI",
    7 : "SAT"
]

// Display correctly 00.03 instead of 0.3
func adjustDate(_ date: Date) -> String {
    let localDate = DateInRegion(absoluteDate: date)
    
    let hour: String
    let minute: String
    
    if localDate.hour < 10 {
        hour = "0" + "\(localDate.hour)"
    } else {
        hour = "\(localDate.hour)"
    }
    if localDate.minute < 10 {
        minute = "0" + "\(localDate.minute)"
    } else {
        minute = "\(localDate.minute)"
    }
    
    return hour + ":" + minute
}



