//
//  Preferences.swift
//  Travlendar+
//
//  Created by Tommaso Scarlatti.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//
//  This is the Preferences model. It has a main struct Preferences which encapsulates
//  all the user preferences for different travel means.
//
//  It also contains support structures to help the coding/decoding process for the
//  data sent/received to the server.

import Foundation

struct Preferences {
    static var eco: Bool = false
    
    static var busOn: Bool = true
    static var walkingOn: Bool = true
    static var uberOn: Bool = true
    static var bikeOn: Bool = true
    static var carOn: Bool = true
    
    static var busMaxDistance: Int?
    static var walkingMaxDistance: Int?
    static var bikeMaxDistance: Int?
    static var carMaxDistance: Int?
    static var uberMaxDistance: Int?
}

struct RawPreferences : Codable {
    var transit: Mean
    var uber: Mean
    var walking: Mean
    var driving: Mean
    var cycling: Mean
}

struct Mean: Codable {
    var active: Bool?
    var maxDistance: Int?
}
