//
//  BookingViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 11/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import Alamofire
import UberCore

protocol HandleBookingSelected {
    func passBooking(booking: BookingOption)
    func reloadTable()
}

class BookingViewController: UIViewController, LoginButtonDelegate {
    
    // Outlets
    @IBOutlet weak var bookingTableView: UITableView!
    @IBOutlet weak var noBookingView: UIView!
    @IBOutlet weak var spinner: UIActivityIndicatorView!
    @IBOutlet weak var loadingView: UIView!
    @IBOutlet weak var topView: UIView!
    @IBOutlet weak var uberView: UIView!
    
    // Index of the selected booking option
    var selectedIndexRow: Int?
    
    // Event selected to get booking options
    var eventToGetBookingOptions: Event?
    
    // Booking selected to be sent to the server
    var bookingSelected: BookingOption?
    
    var handleBookigSelectedDelegate: HandleBookingSelected?
    
    var aToken: String?
    
    /* VIEW CONTROLLER LIFECYCLE */
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        // Set delegates
        bookingTableView?.delegate = self
        bookingTableView?.dataSource = self
        
        // Set login button and login manager
        let scopes: [UberScope] = [.request]
        let loginManager = LoginManager(loginType: .native)
        
        let loginButton = LoginButton(frame: CGRect.zero, scopes: scopes, loginManager: loginManager)
        loginButton.presentingViewController = self
        loginButton.delegate = self
        
        // Add button to the uber view
        loginButton.center.y = uberView.center.y - loginButton.frame.size.height/3
        loginButton.center.x = uberView.center.x
        loginButton.colorStyleDidUpdate(.white)
        uberView.addSubview(loginButton)

        // Display the right button and set navbar color
        self.navigationItem.rightBarButtonItem = UIBarButtonItem(title: "Confirm", style: .done, target: self, action: #selector(book))
        self.navigationItem.rightBarButtonItem?.isEnabled = false
        self.navigationController?.navigationBar.tintColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 1)
        
        // Get booking options
        getBookingOptions(authenticateWith: (User.token ?? "")) { _,_ in
            
            // Stop the spinner and hide the loading view
            self.spinner?.stopAnimating()
            self.loadingView?.isHidden = true
            
            // Set the no booking view if there is no booking option
            if let bookingOptions = selectedBookingOptions {
                if bookingOptions.count == 0 {
                    self.noBookingView.isHidden = false
                } else {
                    self.noBookingView.isHidden = true
                }
            }
        }
    }

    
    /* BOOK RIDE */
    
    
    @objc func book() {
        
        // Check if the user has a Uber access token
        if let uberAccessToken = TokenManager.fetchToken() {
            bookRide(authenticateWith: (User.token ?? ""), withUberToken: uberAccessToken) { _,_ in
                print("DONE")
            }
        }
        
        // If the user is not logged in with Uber
        else {
            let alertController = UIAlertController(title: "Unauthenticated", message: "Please make sure you are logged into your Uber account before confirming a ride request.", preferredStyle: .alert)
            
            let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
            alertController.addAction(defaultAction)
            
            self.present(alertController, animated: true, completion: nil)
        }
    }
    
    
    /* UBER LOGIN */
    
    func loginButton(_ button: LoginButton, didLogoutWithSuccess success: Bool) {
        // success is true if logout succeeded, false otherwise
    }
    
    func loginButton(_ button: LoginButton, didCompleteLoginWithToken accessToken: AccessToken?, error: NSError?) {
        if let _ = accessToken {
            // AccessToken Saved
        } else if let _ = error {
            // An error occured
        }
    }
    
    
    /* HTTP REQUESTS */
    
    /**
     Get all the booking options available for travel associated with the *eventToGetBookingOptions*
     
     - Parameter token: the Travlendar+ token.
     
     - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
     - Parameter statusCode: the HTTP status code returned from the GET request.
     - Parameter data: the data returned from the server in a JSON format.
     */
    func getBookingOptions(authenticateWith token: String, completion: @escaping (_ statusCode: Int, _ data: Data?) -> Void) {
        
        // Initialize booking options
        selectedBookingOptions = []
        
        // Set the loading view active
        self.loadingView.isHidden = false
        self.spinner.startAnimating()
        
        let headers: HTTPHeaders = [
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        ]
        
        let parameters: Parameters = [
            "event_id": (eventToGetBookingOptions?.id)!,
            "start_latitude": eventToGetBookingOptions?.travel?.startLatitude ?? 0,
            "start_longitude": eventToGetBookingOptions?.travel?.startLongitude ?? 0
        ]
        
        Alamofire.request(serverName + "api/v1/available/", method: .get, parameters: parameters, headers: headers).responseJSON { response in
            
            // Success
            if response.response!.statusCode == 200 {
                if let json = response.result.value {
                    print("JSON: \(json)")}
                
                do {
                    // Decoding
                    let rawBooking = try JSONDecoder().decode(RawBooking.self, from: response.data!)
                    
                    // Update data
                    selectedBookingOptions = rawBooking.available
                    
                    // Reload table
                    self.bookingTableView?.reloadData()
                    
                } catch let e {print("Error parsing \(e)")}
                
            }
            // Error
            else {
                print("Error status code: ", (response.response?.statusCode)!)
                print(response.result.value!)
            }
            // Callback function
            completion((response.response?.statusCode)!, response.data)
        }
    }
    
    /**
     Book the ride of the *bookingSelected* option making a request to the server.
     
     - Parameter token: the Travlendar+ token.
     - Parameter uberAccessToken: the Uber access token given after the log in.
     
     - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
     - Parameter statusCode: the HTTP status code returned from the POST request.
     - Parameter data: the data returned from the server in a JSON format.
     */
    func bookRide(authenticateWith token: String, withUberToken uberAccessToken: AccessToken, completion: @escaping (_ statusCode: Int, _ data: Data?) -> Void) {

        let headers: HTTPHeaders = [
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        ]
        
        let parameters: Parameters = [
            "token": uberAccessToken.tokenString,
            "event_id": eventToGetBookingOptions!.id!,
            "product_id": bookingSelected!.bookingInfo!.product_id!,
            "start_latitude": eventToGetBookingOptions!.travel!.startLatitude!,
            "start_longitude": eventToGetBookingOptions!.travel!.startLongitude!
        ]
        
        Alamofire.request(serverName + "api/v1/book/", method: .post, parameters: parameters, headers: headers).responseJSON { response in
            
            if let statusCode = response.response?.statusCode {
            
                // Success
                if statusCode == 200 {
                    if let json = response.result.value {
                        print("JSON: \(json)")
                    }
                    
                    // Display alert view controller
                    let alertController = UIAlertController(title: "Booking completed", message: "Your ride was successfully booked!", preferredStyle: .alert)
                    
                    let defaultAction = UIAlertAction(title: "OK", style: .default) { _ in
                        self.handleBookigSelectedDelegate?.passBooking(booking: self.bookingSelected!)
                        self.handleBookigSelectedDelegate?.reloadTable()
                        self.navigationController?.popViewController(animated: true)
                    }
                    alertController.addAction(defaultAction)
                    self.present(alertController, animated: true, completion: nil)
                }
                // Error
                else {
                    print("Error status code: ", (response.response?.statusCode)!)
                    print(response.result.value!)
                    
                    // Error Decoding
                    var bookingError: RawBooking?
                    
                    do {
                        bookingError = try JSONDecoder().decode(RawBooking.self, from: response.data!)
                    } catch { print("Error parsing")}
                    
                    //Display alert view controller
                    let alertController = UIAlertController(title: "Booking Error", message: bookingError?.message, preferredStyle: .alert)
                    
                    let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                    alertController.addAction(defaultAction)
                    self.present(alertController, animated: true, completion: nil)
                }
                // Callback function
                completion((response.response?.statusCode)!, response.data)
            }
            
            // HTTP request failed
            else {
                
            }
        }
    }
    
}


/* TABLE VIEW DELEGATE */

extension BookingViewController: UITableViewDelegate, UITableViewDataSource {
    
    func tableView(_ tableView: UITableView, numberOfRowsInSection section: Int) -> Int {
        return selectedBookingOptions?.count ?? 0
    }
    
    func tableView(_ tableView: UITableView, cellForRowAt indexPath: IndexPath) -> UITableViewCell {
        
        // Dequeue a reusable cell
        let cell = tableView.dequeueReusableCell(withIdentifier: "BookingCell", for: indexPath) as! BookingCell
        
        // Set outlets
        if let booking = selectedBookingOptions?[indexPath.row].bookingInfo {
            if booking.type == "Nearest UberBLACK" {
                cell.uberType.text = "UberBLACK"
            } else {
                cell.uberType.text = booking.type
            }
            
            cell.info.text = "\((booking.distance)!) m, \((booking.duration)! / 60) min"
            cell.price.text = "\((booking.price_high)!) €"
        }
        
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
    
        return cell
    }
    
    func tableView(_ tableView: UITableView, didSelectRowAt indexPath: IndexPath) {
        
        if let cell = tableView.cellForRow(at: indexPath) as? BookingCell {
            
            // If the cell is currently not selected
            if cell.checkIcon.image == #imageLiteral(resourceName: "check_off") {
                self.bookingSelected = selectedBookingOptions?[indexPath.row]
                self.navigationItem.rightBarButtonItem?.isEnabled = true
                selectedIndexRow = indexPath.row
            }
                // Otherwise
            else {
                self.bookingSelected = nil
                self.navigationItem.rightBarButtonItem?.isEnabled = false
                selectedIndexRow = nil
            }
        }
        
        // Reload the table to display the selected cell
        tableView.reloadData()
    }
    
}
