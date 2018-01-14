//
//  TravelViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 11/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import MapKit
import Alamofire

/// Protocol to handle the selection of a travel option and pass it to the event view
protocol HandleTravelSelected {
    func passTravel(travelOption: RawTravelOption)
}

/// Support structure to send event information to the server
struct EventToGetTravelOptions {
    var title: String
    var description: String
    var start: Int
    var end: Int
    var longitude: Double
    var latitude: Double
    var category: String
    var travel: Bool
    var flexible: Bool
    var repetitive: Bool
    var startLongitude: Double
    var startLatitude: Double
    var duration: Int
}


class TravelViewController: UIViewController {
    
    // Outlets
    @IBOutlet weak var travelTableView: UITableView!
    @IBOutlet weak var spinner: UIActivityIndicatorView!
    @IBOutlet weak var loadingView: UIView!
    @IBOutlet weak var noEventView: UIView!
    
    /// Index of the selected travel row
    var selectedIndexRow: Int?
    
    var delegate: HandleTravelSelected? = nil
    
    var eventToGetTravelOptions: EventToGetTravelOptions? = nil
    var travelOptionSelected: RawTravelOption? = nil
    
    /// Delegate to add events to a day
    var HandleAddEventDelegate: HandleAddEvent? = nil
    
    
    /* VIEW CONTROLLER LIFECYCLE */

    override func viewDidLoad() {
        super.viewDidLoad()
        
        // Hide Autolayout Warning
        UserDefaults.standard.setValue(false, forKey:"_UIConstraintBasedLayoutLogUnsatisfiable")
        
        self.hideKeyboardWhenTappedAround()
        
        // Set tableView delegates
        travelTableView?.delegate = self
        travelTableView?.dataSource = self
        
        // Display the right button
        self.navigationItem.rightBarButtonItem = UIBarButtonItem(title: "Done", style: .done, target: self, action: #selector(travelChosen))
        self.navigationItem.rightBarButtonItem?.isEnabled = false
        self.navigationController?.navigationBar.tintColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 1)
        
        // Load travels from server
        getTravelOptions(authenticateWith: (User.token ?? "")) { _,_ in
            if travelsOfSelectedEvent?.count == 0 {
                self.setNoEventScreen()
            } else {
                self.removeNoEventScreen()
            }
        }
    }

    @objc func travelChosen() {
        self.delegate?.passTravel(travelOption: travelOptionSelected!)
        navigationController!.popViewController(animated: true)
    }
    
    
    /* OVERLAPPING VIEWS */
    
    private func setLoadingScreen() {
        loadingView?.isHidden = false
        spinner?.startAnimating()
    }
    
    private func removeLoadingScreen() {
        loadingView?.isHidden = true
        spinner?.stopAnimating()
    }
    
    private func setNoEventScreen() {
        noEventView?.isHidden = false
    }
    
    private func removeNoEventScreen() {
        noEventView?.isHidden = true
    }
    
    /* HTTP REQUESTS */
    
    /**
     Send the user credentials to the server in order to retrieve the user
     token form the server. The token is needed to perform any further request.
     
     - Parameter token: the Travlendar+ user token.
     
     - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
     - Parameter statusCode: the HTTP status code returned from the POST request.
     - Parameter data: the data returned from the server if the request was successfully made.
     */
    func getTravelOptions(authenticateWith token: String, completion: @escaping (_ statusCode: Int, _ data: Data?) -> Void) {
        self.setLoadingScreen()
        
        travelsOfSelectedEvent = []
        
        let headers: HTTPHeaders = [
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        ]
        
        let parameters: Parameters = [
            "title": eventToGetTravelOptions!.title,
            "start": eventToGetTravelOptions!.start,
            "end": eventToGetTravelOptions!.end,
            "longitude": eventToGetTravelOptions!.longitude,
            "latitude": eventToGetTravelOptions!.latitude,
            "description": eventToGetTravelOptions!.description,
            "category": eventToGetTravelOptions!.category,
            "travel": eventToGetTravelOptions!.travel,
            "flexible": eventToGetTravelOptions!.flexible,
            "repetitive": eventToGetTravelOptions!.repetitive,
            "startLongitude": eventToGetTravelOptions!.startLongitude,
            "startLatitude": eventToGetTravelOptions!.startLatitude,
            "duration": eventToGetTravelOptions!.duration
        ]
        
        Alamofire.request(serverName + "api/v1/generator", method: .post, parameters: parameters,  encoding: JSONEncoding.default, headers: headers).responseJSON { response in
            
            if let statusCode = response.response?.statusCode {
            
                // Success
                if response.response!.statusCode == 200 {
                    if let json = response.result.value {
                        print("JSON: \(json)")
                    }
                    do {
                        let raw = try JSONDecoder().decode(RawTravel.self, from: response.data!)
                        
                        for option in raw.options! {
                            travelsOfSelectedEvent?.append(option)
                        }
                        
                        self.travelTableView?.reloadData()
                        self.removeLoadingScreen()
                    } catch { print("Error parsing")}
                    
                }
                // Account not active
                else if statusCode == 403 {
                    // Display an alert view controller
                    let alertController = UIAlertController(title: "Request failed", message: "Your account is not active. Please confirm the email we sent to your account.", preferredStyle: .alert)
                    
                    let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                    alertController.addAction(defaultAction)
                    
                    self.present(alertController, animated: true, completion: nil)
                }
                // Error
                else {
                    print("Error status code: \(response.response!.statusCode)")
                    print(response.result.value!)
                    let alertController = UIAlertController(title: "Travel options error", message: "Please check your internet connection and retry", preferredStyle: .alert)
                    
                    let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                    alertController.addAction(defaultAction)
                    
                    self.present(alertController, animated: true, completion: nil)
                }
                // Callback
                completion(response.response!.statusCode, response.data)
            }
            // HTTP request failed
            else {
                // Display an alert view controller
                let alertController = UIAlertController(title: "Connection failed", message: "Your travels could not be retrieved. Please check your connection and retry.", preferredStyle: .alert)
                
                let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                alertController.addAction(defaultAction)
                
                self.present(alertController, animated: true, completion: nil)
            }
        }
        
    }

}


/* TABLE VIEW DELEGATE */

extension TravelViewController: UITableViewDelegate, UITableViewDataSource {
    func tableView(_ tableView: UITableView, numberOfRowsInSection section: Int) -> Int {
        return travelsOfSelectedEvent!.count
    }
    
    func tableView(_ tableView: UITableView, cellForRowAt indexPath: IndexPath) -> UITableViewCell {
        
        // Get highest distance for power-driven means
        var transitHighestDistance = getHighestDistance(for: "transit", in: travelsOfSelectedEvent!)
        var carHighestDistance = getHighestDistance(for: "car", in: travelsOfSelectedEvent!)
        var uberHighestDistance = getHighestDistance(for: "uber", in: travelsOfSelectedEvent!)
        
        // Dequeue a reusable cell
        let cell = tableView.dequeueReusableCell(withIdentifier: "TravelCell", for: indexPath) as! TravelCell
        
        // Set outlets
        cell.mean.text = travelMeansTitle[travelsOfSelectedEvent![indexPath.row].travel!.mean!]?.uppercased()
        cell.duration.text = "\(Int(travelsOfSelectedEvent![indexPath.row].travel!.duration! / 60)) min"
        cell.distance.text = "\(travelsOfSelectedEvent![indexPath.row].travel!.distance!) m"
        cell.icon.image = travelIconFromString[travelsOfSelectedEvent![indexPath.row].travel!.mean!]
        
        // Set background color
        let backgroundView = UIView()
        backgroundView.backgroundColor = UIColor(red: 242/255, green: 244/255, blue: 244/255, alpha: 1)
        cell.selectedBackgroundView = backgroundView
        
        // Set check icon if selected
        if indexPath.row == selectedIndexRow {
            cell.checkIcon.image = #imageLiteral(resourceName: "check_on")
        } else {
            cell.checkIcon.image = #imageLiteral(resourceName: "check_off")
        }
        
        // Set eco mode icon
        
        // Walking
        if Preferences.walkingOn {
            if travelsOfSelectedEvent![indexPath.row].travel!.mean! == "walking" {
                cell.ecoIcon.isHidden = false
            } else {
                cell.ecoIcon.isHidden = true
            }
        }
        // Bike
        else if !Preferences.walkingOn && Preferences.bikeOn {
            if travelsOfSelectedEvent![indexPath.row].travel!.mean! == "cycling" {
                cell.ecoIcon.isHidden = false
            } else {
                cell.ecoIcon.isHidden = true
            }
        }
        // Transit
        else if !Preferences.walkingOn && !Preferences.bikeOn && Preferences.busOn {
            if travelsOfSelectedEvent![indexPath.row].travel!.mean! == "transit" {
                if travelsOfSelectedEvent![indexPath.row].travel!.distance == transitHighestDistance {
                    cell.ecoIcon.isHidden = false
                } else {
                    cell.ecoIcon.isHidden = true
                }
            }
        }
        // Uber
        else if !Preferences.walkingOn && !Preferences.bikeOn && !Preferences.busOn && Preferences.uberOn {
            if travelsOfSelectedEvent![indexPath.row].travel!.mean! == "uber" {
                if travelsOfSelectedEvent![indexPath.row].travel!.distance == uberHighestDistance {
                    cell.ecoIcon.isHidden = false
                } else {
                    cell.ecoIcon.isHidden = true
                }
            }
        }
        // Car
        else if !Preferences.walkingOn && !Preferences.bikeOn && !Preferences.busOn && !Preferences.uberOn && Preferences.carOn {
            if travelsOfSelectedEvent![indexPath.row].travel!.mean! == "car" {
                if travelsOfSelectedEvent![indexPath.row].travel!.distance == carHighestDistance {
                    cell.ecoIcon.isHidden = false
                } else {
                    cell.ecoIcon.isHidden = true
                }
            }
        }
        
        return cell
    }
    
    func tableView(_ tableView: UITableView, didSelectRowAt indexPath: IndexPath) {
        
        if let cell = tableView.cellForRow(at: indexPath) as? TravelCell {
            
            // If the cell is currently not selected
            if cell.checkIcon.image == #imageLiteral(resourceName: "check_off") {
                self.travelOptionSelected = travelsOfSelectedEvent?[indexPath.row]
                self.navigationItem.rightBarButtonItem?.isEnabled = true
                selectedIndexRow = indexPath.row
            }
            // Otherwise
            else {
                self.travelOptionSelected = nil
                self.navigationItem.rightBarButtonItem?.isEnabled = false
                selectedIndexRow = nil
            }
        }
        
        // Reload the table to display the selected cell
        tableView.reloadData()
    }
    
}
