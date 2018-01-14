//
//  Event.swift
//  Travlendar+
//
//  Created by Tommaso Scarlatti.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//
//  This is the event model. It contains a main struct Event which is used for
//  the coding/decoding of the json to send to or receive from the server.
//  This structure contains all the event-related information, included the optional
//  associated travel option.
//
//  It also takes into account the current event selected in the DayViewController and
//  the list of associated travels.


import Foundation
import MapKit
import Alamofire


/// Event structure for the JSON coding and decoding.
struct Event: Codable {
    var id: Int?
    var userId: Int?
    var start: Int
    var end: Int
    var title: String
    var description: String?
    var category: String
    var latitude: Double
    var longitude: Double
    var flexible_info: FlexibleInfo?
    var repetitive_info: RepetitiveInfo?
    var travel: Travel?
    
    /// Inner strucure of **Event** for additional infos of flexible events
    struct FlexibleInfo: Codable {
        var lowerBound: Int?
        var upperBound: Int?
        var duration: Int?
    }
    
    /// Inner strucure of **Event** for additional infos of reccurent events
    struct RepetitiveInfo: Codable {
        var until: Int?
        var frequency: String?
    }
}

/// Event selected from the current day view to display details.
var selectedEvent: Event?

/// Array of travel options associated with *selectedEvent*.
var travelsOfSelectedEvent: [RawTravelOption]? = []

/// Map color name to corresponding predefined color.
let lookupColor: [String:UIColor] = [
    "green": UIColor(red: 135/255, green: 207/255, blue: 39/255, alpha: 1),
    "blue": UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 1),
    "yellow": UIColor(red: 229/255, green: 215/255, blue: 18/255, alpha: 1),
    "orange": UIColor(red: 239/255, green: 146/255, blue: 25/255, alpha: 1),
    "red": UIColor(red: 206/255, green: 15/255, blue: 1/255, alpha: 1),
    "violet": UIColor(red: 154/255, green: 59/255, blue: 189/255, alpha: 1),
    "grey": UIColor(red: 190/255, green: 190/255, blue: 190/255, alpha: 1),
    "black": UIColor(red: 0/255, green: 0/255, blue: 0/255, alpha: 1)
]

