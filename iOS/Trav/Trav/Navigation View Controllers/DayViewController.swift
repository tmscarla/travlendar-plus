//
//  DayViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 07/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import SwiftDate
import Alamofire


class DayViewController: UIViewController {

    // OUtlets
    @IBOutlet weak var year: UILabel!
    @IBOutlet weak var month: UILabel!
    @IBOutlet weak var dayString: UILabel!
    @IBOutlet weak var dayNumber: UILabel!
    @IBOutlet weak var menuButton: UIBarButtonItem!
    @IBOutlet weak var eventsTableView: UITableView!
    @IBOutlet weak var loadingView: UIView!
    @IBOutlet weak var spinner: UIActivityIndicatorView!
    @IBOutlet weak var noEventView: UIView!
    @IBOutlet weak var deletionSpinner: UIActivityIndicatorView!
    
    private var isEventElimination: Bool = false
    
    private var selectedEventRow: Int?
    
    private let formatter = DateFormatter()
    
    
    /* VIEWCONTROLLER LIFECYCLE METHODS */
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        //Hide Autolayout Warning
        UserDefaults.standard.setValue(false, forKey:"_UIConstraintBasedLayoutLogUnsatisfiable")
        
        addNavBarImage()
        sideMenu()
        setOutletsForSelectedDay()
        deletionSpinner?.isHidden = true
        
        eventsTableView?.delegate = self
        eventsTableView?.dataSource = self
        
        formatter.dateFormat = "dd/MM/yyyy HH:mm"
        
        // Reload events
        getEvents(authenticateWith: (User.token ?? ""), of: Day.dayDate) { _,_ in}
    }
    
    override func viewDidAppear(_ animated: Bool) {
        
    }
    
    
    /* UI METHODS */
    
    // Shows the leftside menu
    func sideMenu() {
        if revealViewController() != nil {
            menuButton.target = revealViewController()
            menuButton.action = #selector(SWRevealViewController.revealToggle(_:))
            revealViewController().rearViewRevealWidth = 270
        }
    }
    
    // Set all the outlets in the view according to the local timezone
    func setOutletsForSelectedDay() {
        let localDate = DateInRegion(absoluteDate: Day.dayDate)
       
        self.year.text = "\(localDate.year)"
        self.month.text = localDate.monthName
        self.dayNumber.text = "\(localDate.day)"
        self.dayString.text = dayUnitString[localDate.weekday]
    }
    
    // Add travlendar logo
    func addNavBarImage() {
        let imageView = UIImageView(image: #imageLiteral(resourceName: "t_plus"))
        imageView.frame = CGRect(x: 0, y: 0, width: 30, height: 30)
        imageView.contentMode = .scaleAspectFit
        imageView.clipsToBounds = true
        navigationItem.titleView = imageView
    }
    
    private func setLoadingScreen() {
        loadingView?.isHidden = false
        spinner?.startAnimating()
    }
    
    private func removeLoadingScreen() {
        loadingView?.isHidden = true
        spinner?.stopAnimating()
    }
    
    
    /* PREPARE FOR SEGUE */
    
    override func prepare(for segue: UIStoryboardSegue, sender: Any?) {
        
        // Add Event View Controller
        if segue.identifier == "addEvent" {
            let addEventVC = segue.destination as! AddEventViewController
            addEventVC.handleAddEventDelegate = self
            addEventVC.isEditingMode = false
        }
        
        // Add Event View Controller - Editing Mode
        else if segue.identifier == "editEvent" {
            let editEventVC = segue.destination as! AddEventViewController
            editEventVC.handleAddEventDelegate = self
            editEventVC.isEditingMode = true
            editEventVC.selectedEventRow = selectedEventRow
            editEventVC.tableView.reloadData()
        }
        
        // Booking View Controller
        else if (segue.identifier == "showBookingOptions") {
            let bookingVC = segue.destination as! BookingViewController
            bookingVC.handleBookigSelectedDelegate = self
            bookingVC.eventToGetBookingOptions = Day.eventsOfTheDay?[selectedEventRow!]
        }
    }
    
    
    /* HTTP REQUESTS */
    
    /**
     Send the user credentials to the server in order to retrieve the user
     token form the server. The token is needed to perform any further request.
     
     - Parameter token: the Travlendar+ user token.
     
     - Parameter dayDate: the date of the requested day.
     
     - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
     - Parameter statusCode: the HTTP status code returned from the POST request.
     - Parameter data: the data returned from the server if the request was successfully made.
     */
    func getEvents(authenticateWith token: String, of dayDate: Date, completion: @escaping (_ statusCode: Int, _ data: Data?) -> Void) {
        
        // Do not set the loading screen if is during an event elimination
        if !isEventElimination {
            setLoadingScreen()
        }
        isEventElimination = false
        
        let headers: HTTPHeaders = [
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        ]
        
        let parameters: Parameters = [
            "from": Int(dayDate.timeIntervalSince1970),
            "to": Int(DateInRegion(absoluteDate: dayDate).endOfDay.absoluteDate.timeIntervalSince1970)
        ]
        
        Alamofire.request(serverName + "api/v1/event", method: .get, parameters: parameters, headers: headers).responseJSON { response in
            
            if let statusCode = response.response?.statusCode {
            
                // Success
                if statusCode == 200 {
                    if let json = response.result.value {
                        print("Events of the day: ", dayDate)
                        print("JSON: \(json)") // serialized json response
                        
                        do {
                            // Decoding
                            let raw = try JSONDecoder().decode(RawDay.self, from: response.data!)
                            // Orders the events according to their start date
                            Day.eventsOfTheDay = raw.events.sorted(by: { (a, b) in
                                var startA: Int
                                var startB: Int
                                
                                if a.flexible_info != nil {
                                    startA = a.flexible_info!.lowerBound!
                                } else {
                                    startA = a.start
                                }
                                
                                if b.flexible_info != nil {
                                    startB = b.flexible_info!.lowerBound!
                                } else {
                                    startB = b.start
                                }
                                
                                return startA < startB
                            })
                            
                            // Reloads event table
                            self.eventsTableView?.reloadData()
                            
                        } catch let e {print("Error parsing \(e)")}
                    }
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
                    print("Error status code: \(statusCode)")
                }
                
                // Callback function
                completion(statusCode, response.data)
            }
            
            // HTTP request failed
            else {
                // Display an alert view controller
                let alertController = UIAlertController(title: "Connection failed", message: "Your events could not be retrieved. Please check your connection and retry.", preferredStyle: .alert)
                
                let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                alertController.addAction(defaultAction)
                
                self.present(alertController, animated: true, completion: nil)
            }
            
            // Remove loading screen after request
            self.removeLoadingScreen()
            
            // Check if the are events in the specific day
            if Day.eventsOfTheDay!.count == 0 {
                self.noEventView?.isHidden = false
            } else {
                self.noEventView?.isHidden = true
            }
        }
    }
    
    
    /**
     Delete the event from the server and reload the events of the day.
     
     - Parameter token: the Travlendar+ user token.
     
     - Parameter id: the id of the event.
     
     - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
     - Parameter statusCode: the HTTP status code returned from the DELETE request.
     - Parameter data: the data returned from the server if the request was successfully made.
     */
    func deleteEvent(authenticateWith token: String, event id: Int, completion: @escaping (_ statusCode: Int) -> Void) {
        
        deletionSpinner?.startAnimating()
        deletionSpinner?.isHidden = false
        
        let headers: HTTPHeaders = [
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        ]
        
        Alamofire.request(serverName + "api/v1/event/\(id)", method: .delete, parameters: [:], headers: headers).responseJSON { response in
            
            if let statusCode = response.response?.statusCode {
            
                // Success
                if statusCode == 200 {
                    if let json = response.result.value {
                        print("JSON: \(json)")
                    }
                    // Reload events after the deletion
                    self.getEvents(authenticateWith: token, of: Day.dayDate) { _,_ in}
                }
                // Error
                else {
                    self.removeLoadingScreen()
                    print("Error status code: \(response.response!.statusCode)")
                    print(response.result.value!)
                    
                    let alertController = UIAlertController(title: "Event cannot be deleted", message: "Check your internet connection and retry.", preferredStyle: .alert)
                    
                    let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                    alertController.addAction(defaultAction)
                    self.present(alertController, animated: true, completion: nil)
                }
                
                // Callback
                completion(statusCode)
            }
            
            // HTTP request failed
            else {
                
            }
            
            // Stop the spinner
            self.deletionSpinner?.stopAnimating()
            self.deletionSpinner?.isHidden = true
        }
        
    }

}


/* TABLE VIEW DELEGATE METHODS */

extension DayViewController: UITableViewDataSource, UITableViewDelegate {
    
    // Set rows number
    func tableView(_ tableView: UITableView, numberOfRowsInSection section: Int) -> Int {
        return Day.eventsOfTheDay!.count
    }
    
    // Fulfill cells and set outlets
    func tableView(_ tableView: UITableView, cellForRowAt indexPath: IndexPath) -> UITableViewCell {
        let cell = tableView.dequeueReusableCell(withIdentifier: "EventCell", for: indexPath) as! EventCell
        
        // Set cell dates
        var startDate: Date
        var endDate: Date
        
        // If is a recurrent event
        if Day.eventsOfTheDay![indexPath.row].flexible_info != nil {
            startDate = Date(timeIntervalSince1970: Double(Day.eventsOfTheDay![indexPath.row].flexible_info!.lowerBound!))
            endDate = Date(timeIntervalSince1970: Double(Day.eventsOfTheDay![indexPath.row].flexible_info!.upperBound!))
        } else {
            startDate = Date(timeIntervalSince1970: Double(Day.eventsOfTheDay![indexPath.row].start))
            endDate = Date(timeIntervalSince1970: Double(Day.eventsOfTheDay![indexPath.row].end))
        }
        
        cell.startDate.text = adjustDate(startDate)
        cell.endDate.text = adjustDate(endDate)
        
        // Set title and description
        cell.titleLabel.text = Day.eventsOfTheDay![indexPath.row].title
        cell.descriptionLabel.text = Day.eventsOfTheDay![indexPath.row].description
        
        // Set category color, default == green
        cell.separatorColor = lookupColor[Day.eventsOfTheDay![indexPath.row].category] ?? lookupColor["green"]!
        
        // Set travel option icon
        if let travelOption = Day.eventsOfTheDay![indexPath.row].travel?.mean {
            cell.travelIcon.isHidden = false
            cell.travelIcon.image = travelIconFromString[travelOption
            ]
            cell.travelDuration.isHidden = false
            cell.travelDuration.text = "\(Day.eventsOfTheDay![indexPath.row].travel!.duration! / 60) min"
        } else {
            cell.travelIcon.isHidden = true
            cell.travelDuration.isHidden = true
        }
        
        // Set booking option icon
        if (Day.eventsOfTheDay![indexPath.row].travel?.bookingId) != nil {
            cell.bookingIcon.isHidden = false
            //cell.bookingPrice.isHidden = false
        } else {
            cell.bookingIcon.isHidden = true
            cell.bookingPrice.isHidden = true
        }
        
        // Set background color when selected
        let backgroundView = UIView()
        backgroundView.backgroundColor = UIColor(red: 242/255, green: 244/255, blue: 244/255, alpha: 1)
        cell.selectedBackgroundView = backgroundView
        
        return cell
        
    }
    
    // Make cells editable
    func tableView(_ tableView: UITableView, canEditRowAt indexPath: IndexPath) -> Bool {
        return true
    }
    
    // Add swipe actions
    func tableView(_ tableView: UITableView,
                   trailingSwipeActionsConfigurationForRowAt indexPath: IndexPath) -> UISwipeActionsConfiguration?
    {
        // Delete action
        let deleteAction = UIContextualAction(style: .normal, title:  "Delete", handler: { (ac:UIContextualAction, view:UIView, success:(Bool) -> Void) in
            self.isEventElimination = true
            self.deleteEvent(authenticateWith: (User.token ?? ""), event: Day.eventsOfTheDay![indexPath.row].id!) { _ in
                
            }
            success(true)
        })
        deleteAction.image = #imageLiteral(resourceName: "delete")
        deleteAction.backgroundColor = .red
        
        // Booking action
        let bookingAction = UIContextualAction(style: .normal, title: "Book", handler: { (ac:UIContextualAction, view:UIView, success:(Bool) -> Void) in
            
            // If the event has a travel option and there is no confirmed booking
            if ((Day.eventsOfTheDay![indexPath.row].travel?.mean) != nil) && Day.eventsOfTheDay![indexPath.row].travel?.bookingId == nil {
                
                // Uber
                if Day.eventsOfTheDay![indexPath.row].travel?.mean == "uber" {
                    // Set selected event
                    self.selectedEventRow = indexPath.row
                    
                    // Move to booking view controller
                    self.performSegue(withIdentifier: "showBookingOptions", sender: self)
                    success(true)
                    
                }
                // Other means
                else {
                    // Display an alert view controller
                    let alertController = UIAlertController(title: "We are sorry", message: "Booking feature is only available for Uber right now.", preferredStyle: .alert)
                    
                    let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                    alertController.addAction(defaultAction)
                    
                    self.present(alertController, animated: true, completion: nil)
                }
                
            } else {
                // Display an alert view controller
                let alertController = UIAlertController(title: "Travel option needed", message: "Sorry, booking is not available for events with no travel option or with a booking already confirmed.", preferredStyle: .alert)
                
                let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                alertController.addAction(defaultAction)
                
                self.present(alertController, animated: true, completion: nil)
            }
            
        })
        bookingAction.image = #imageLiteral(resourceName: "shopping-cart-2")
        bookingAction.backgroundColor = UIColor(red: 236/255, green: 175/255, blue: 11/255, alpha: 1)
        
        // Add actions to cell
        return UISwipeActionsConfiguration(actions: [deleteAction, bookingAction])
    }
    
    func tableView(_ tableView: UITableView, commit editingStyle: UITableViewCellEditingStyle, forRowAt indexPath: IndexPath) {
        if editingStyle == .delete {
            isEventElimination = true
            deleteEvent(authenticateWith: (User.token ?? ""), event: Day.eventsOfTheDay![indexPath.row].id!) { _ in }
        }
    }
    
    // Perform action when a cell is selected
    func tableView(_ tableView: UITableView, didSelectRowAt indexPath: IndexPath) {
        selectedEventRow = indexPath.row
        tableView.deselectRow(at: indexPath as IndexPath, animated: true)
        self.performSegue(withIdentifier: "editEvent", sender: self)
    }
    
}


/* HANDLE ADD EVENT */

extension DayViewController: HandleAddEvent {
    
    // Insert event added in the AddEventViewController to the event table
    func inserEvent(event: Event) {
        Day.eventsOfTheDay!.append(event)
        
        let indexPath = IndexPath(row: Day.eventsOfTheDay!.count-1, section: 0)
        
        eventsTableView.beginUpdates()
        eventsTableView.insertRows(at: [indexPath], with: .automatic)
        eventsTableView.endUpdates()
    }
    
    // Reload table after the event is added
    func reloadEventsTable() {
        getEvents(authenticateWith: (User.token ?? ""), of: Day.dayDate) { _,_ in}
    }
}

/* HANDLE BOOK TRAVEL */

extension DayViewController: HandleBookingSelected {
    
    func passBooking(booking: BookingOption) {
        
    }
    
    func reloadTable() {
        getEvents(authenticateWith: (User.token ?? ""), of: Day.dayDate) { _,_ in}
    }

}
