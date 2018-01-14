//
//  User.swift
//  Travlendar+
//
//  Created by Tommaso Scarlatti.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//
//  This is the User model. It has a main struct User which encapsulates all the
//  user-related information such as name, email, profile picture and the token to
//  perform authenticated requests to the server.
//
//  Furthermore, it contains structure to help the decoding of user credentials and
//  owns the function which encapsulates the HTTP request to retrieve them.

import Foundation
import Alamofire

/* SERVER GLOBAL NAME */

// Name stored in the info.plist file
var serverName: String = Bundle.main.object(forInfoDictionaryKey: "ServerName") as! String

/* MAIN STRUCTURE */

struct User: Codable {
    
    static var name: String? {
        get {
            return UserDefaults.standard.string(forKey: "name")
        }
        set(token) {
            UserDefaults.standard.set(token, forKey: "name")
            UserDefaults.standard.synchronize()
        }
    }
    
    static var email: String? {
        get {
            return UserDefaults.standard.string(forKey: "email")
        }
        set(token) {
            UserDefaults.standard.set(token, forKey: "email")
            UserDefaults.standard.synchronize()
        }
    }
    
    static var password: String?
    
    // If the user holds the token, skip the log in
    static var token: String? {
        get {
            return UserDefaults.standard.string(forKey: "token")
        }
        set(token) {
            UserDefaults.standard.set(token, forKey: "token")
            UserDefaults.standard.synchronize()
        }
    }
    
    // If the profile picture is not set, display the default one
    static var profilePicture: UIImage? {
        get {
            if let data = UserDefaults.standard.object(forKey: "profilePicture") as! NSData? {
                return UIImage(data: data as Data)
            } else {
                return #imageLiteral(resourceName: "man-3")
            }
        } set(newImage) {
            if let img = newImage {
                let imageData: NSData = UIImagePNGRepresentation(img)! as NSData
                UserDefaults.standard.set(imageData, forKey: "profilePicture")
            } else {
                UserDefaults.standard.removeObject(forKey: "profilePicture")
            }
        }
    }
    
    static var id: Int? {
        get {
            return Int(UserDefaults.standard.string(forKey: "userId")!)
        }
        set(id) {
            UserDefaults.standard.set(id, forKey: "userId")
            UserDefaults.standard.synchronize()
        }
    }
}


// Struct to help the decoding of the user
struct RawUser : Codable {
    var message: String
    var user: UserInfo
    
    struct UserInfo : Codable {
        var name: String?
        var email: String?
        var id: Int?
        var preferences: RawPreferences?
    }
}

/* USEFUL METHODS */

func deleteUserData() {
    UserDefaults.standard.removeObject(forKey: "token")
    UserDefaults.standard.removeObject(forKey: "profilePicture")
    UserDefaults.standard.removeObject(forKey: "name")
    UserDefaults.standard.removeObject(forKey: "email")
}


/* HTTP REQUESTS */

func getUserCredentials(authenticateWith token: String, completion: @escaping (Int, Data?) -> Void) {
    
    let headers: HTTPHeaders = [
        "Accept": "application/json",
        "Authorization": "Bearer " + token
    ]
    
    Alamofire.request(serverName + "api/v1/user/", method: .get, parameters: [:], headers: headers).responseJSON { response in
        
        if let statusCode = response.response?.statusCode {
        
            // Success
            if statusCode == 200 {
                if let json = response.result.value {
                    print("JSON: \(json)")}
                
                    do {
                        // Decoding
                        let rawUser = try JSONDecoder().decode(RawUser.self, from: response.data!)
                        
                        // User credentials
                        User.email =  rawUser.user.email
                        User.name = rawUser.user.name
                        User.id = rawUser.user.id
                        
                        // Preferences
                        Preferences.busOn = (rawUser.user.preferences?.transit.active) ?? true
                        Preferences.walkingOn = (rawUser.user.preferences?.walking.active) ?? true
                        Preferences.bikeOn = (rawUser.user.preferences?.cycling.active) ?? true
                        Preferences.carOn = (rawUser.user.preferences?.driving.active) ?? true
                        Preferences.uberOn = (rawUser.user.preferences?.uber.active) ?? true
                        
                        Preferences.busMaxDistance = rawUser.user.preferences?.transit.maxDistance
                        Preferences.carMaxDistance = rawUser.user.preferences?.driving.maxDistance
                        Preferences.bikeMaxDistance = rawUser.user.preferences?.cycling.maxDistance
                        Preferences.uberMaxDistance = rawUser.user.preferences?.uber.maxDistance
                        Preferences.walkingMaxDistance = rawUser.user.preferences?.walking.maxDistance
                        
                       
                    } catch let e {print("Error parsing \(e)")}
        
            }
            // Error
            else {
                print("Error status code user credentials")
            }
            // Callback function
            completion((response.response?.statusCode)!, response.data)
            
        }
        
        // HTTP request failed
        else {
            
        }
    }
}

