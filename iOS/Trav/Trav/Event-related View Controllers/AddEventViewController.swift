//
//  AddEventViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 09/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import MapKit
import SwiftDate
import Alamofire

protocol HandleAddEvent {
    func inserEvent(event: Event)
    func reloadEventsTable()
}

// Support structure to send a new event to the server
struct RawEventToBeAdded {
    static var title: String?
    static var description: String?
    static var category: String?
    
    static var start: Int?
    static var end: Int?
    
    static var until: Int?
    static var frequency: String?
    
    static var duration: Int?
    
    static var mean: String?
    static var travelDuration: Int?
    static var distance: Int?
    
    static var isFlexible: Bool?
    static var isRecurrent: Bool?
    
    static var hasTravelOption: Bool?
    static var adjustements: [String: [Int]]?
}

class AddEventViewController: UITableViewController {
    
    // Support struct to handle the decoding of an error response from the server
    private struct ErrorResponse : Decodable {
        var message: String?
        var feasibility: Feasibility?
    }
    
    private struct Feasibility : Decodable {
        var errors: [Error]?
    }
    
    private struct Error: Decodable {
        var cause: String?
    }
    
    // Outlets
    @IBOutlet weak var titleTextField: UITextField!
    @IBOutlet weak var descriptionTextField: UITextField!
    @IBOutlet weak var position: UITextField!
    @IBOutlet weak var startTravelPosition: UITextField!
    @IBOutlet weak var startTime: UITextField!
    @IBOutlet weak var endTime: UITextField!
    @IBOutlet weak var labelTextField: UITextField!
    @IBOutlet weak var durationTextField: UITextField!
    @IBOutlet weak var travelOptionTextField: UITextField!
    @IBOutlet weak var frequencyTextField: UITextField!
    @IBOutlet weak var untilTextField: UITextField!
    
    @IBOutlet weak var travelOptionIcon: UIImageView!
    
    @IBOutlet weak var startLabel: UILabel!
    @IBOutlet weak var endLabel: UILabel!
    
    @IBOutlet weak var flexibleSwitch: UISwitch!
    @IBOutlet weak var recurrentSwitch: UISwitch!
    @IBOutlet weak var travelSwitch: UISwitch!
    
    @IBOutlet weak var travelOptionCell: UITableViewCell!
    @IBOutlet weak var startCell: UITableViewCell!
    @IBOutlet weak var endCell: UITableViewCell!
    @IBOutlet weak var durationCell: UITableViewCell!
    
    // Variables to check if "Add" button can be enabled
    private var isTitleEmpty: Bool = true
    private var isPositionEmpty: Bool = true
    private var isStartTimeEmpty: Bool = true
    private var isEndTimeEmpty: Bool = true
    private var isStartTravelPositionEmpty: Bool = true
    
    // Label color picker
    private let colorPickerView = UIPickerView()
    private var colorPickOption = ["green", "blue", "yellow", "orange", "red", "violet", "grey", "black"]
    
    // Frequency picker
    private let frequencyPickerView = UIPickerView()
    private let frequencyPickOption = ["every day", "every week", "every month", "every year"]
    private let frequencyStringToJSON: [String:String] = [
        "every day": "day",
        "every week": "week",
        "every month": "month",
        "every year": "year"
    ]
    private let frequencyJSONToString: [String:String] = [
        "day": "every day",
        "week": "every week",
        "month": "every month",
        "year": "every year"
    ]
    
    // Position
    private var positionPlace: MKPlacemark?
    private var startTravelPlace: MKPlacemark?
    private var requestStartTravelPosition: Bool = false
    
    // Event to be send to the server to be added
    private var eventToBeAdded: RawEventToBeAdded?
    
    // Date picker
    let datePicker = UIDatePicker()
    let durationPicker = UIDatePicker()
    let untilPicker = UIDatePicker()
    
    // Travel selected
    var travelOptionSelected: RawTravelOption? = nil
    
    // Delegate to add event
    var handleAddEventDelegate: HandleAddEvent? = nil
    
    // Editing mode
    var isEditingMode: Bool?
    var selectedEventRow: Int?
    
    // Activity indicator
    var activityIndicator: UIActivityIndicatorView?
    
    
    /* VIEW CONTROLLER LIFECYCLE */

    override func viewDidLoad() {
        super.viewDidLoad()
        
        //Hide Autolayout Warning
        UserDefaults.standard.setValue(false, forKey:"_UIConstraintBasedLayoutLogUnsatisfiable")
        
        self.hideKeyboardWhenTappedAround()
        
        // Call UI methods
        let toolBar = createToolBar()
        createAddButton()
        createStartDatePicker()
        createEndDatePicker()
        createDurationPicker()
        createUntilPicker()
        checkTravelOption()
        
        // Set delegates
        position?.delegate = self
        descriptionTextField?.delegate = self
        startTravelPosition?.delegate = self
        titleTextField?.delegate = self
        colorPickerView.delegate = self
        frequencyPickerView.delegate = self
        travelOptionTextField?.delegate = self
        
        // Set label color picker
        colorPickerView.backgroundColor = .white
        colorPickerView.showsSelectionIndicator = true
        labelTextField?.inputView = colorPickerView
        labelTextField?.inputAccessoryView = toolBar
        
        // Set frequency picker
        frequencyPickerView.backgroundColor = .white
        frequencyPickerView.showsSelectionIndicator = true
        frequencyPickerView.delegate!.pickerView?(frequencyPickerView, didSelectRow: 0, inComponent: 0)
        frequencyTextField?.inputView = frequencyPickerView
        frequencyTextField?.inputAccessoryView = toolBar
        frequencyTextField?.isUserInteractionEnabled = true
        
        // Navbar color
        self.navigationController?.navigationBar.tintColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 1)
        
        // Clear event fields
        clearEventFields()
        
        // Editing mode
        setEventDetailsIfEditing()
       
    }
    
    
    /* BUTTONS ACTIONS */

    @objc func addEvent() {
        // If the travel option switch is one but no travel option was selected
        if travelSwitch.isOn && travelOptionTextField.text == "" {
            let alertController = UIAlertController(title: "Event cannot be added", message: "You must provide a valid travel, otherwise disable the travel option.", preferredStyle: .alert)
            
            let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
            alertController.addAction(defaultAction)
            
            self.present(alertController, animated: true, completion: nil)
        }
        // If the travel option switch is off
        else if !travelSwitch.isOn {
            getAdjustements(authenticateWith: (User.token ?? "")) { (statusCode, data) in
                if statusCode == 200 {
                    self.addEventToServer(authenticateWith: (User.token ?? "")) { _,_  in }
                }
            }
        }
        
        else {
            addEventToServer(authenticateWith: (User.token ?? "")) { _,_  in }
        }
        
    }
    
    @objc func editEvent() {
        //TODO editEventAndSendToServer()
        
        let alertController = UIAlertController(title: "We are sorry", message: "This functionality is currently not available", preferredStyle: .alert)
        let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                            alertController.addAction(defaultAction)
        self.present(alertController, animated: true, completion: nil)
    }
    
    
    /* UI METHODS */
    
    // Clear all the event fields
    func clearEventFields() {
        RawEventToBeAdded.title = nil
        RawEventToBeAdded.description = nil
        RawEventToBeAdded.category = nil
        RawEventToBeAdded.start = nil
        RawEventToBeAdded.end = nil
        RawEventToBeAdded.until = nil
        RawEventToBeAdded.frequency = nil
        RawEventToBeAdded.duration = nil
        RawEventToBeAdded.mean = nil
        RawEventToBeAdded.travelDuration = nil
        RawEventToBeAdded.distance = nil
        RawEventToBeAdded.isFlexible = nil
        RawEventToBeAdded.isRecurrent = nil
        RawEventToBeAdded.hasTravelOption = nil
        RawEventToBeAdded.adjustements = nil
    }
    
    func createActivityIndicator() {
        activityIndicator = UIActivityIndicatorView(frame: CGRect(x: 0, y: 0, width: 20, height: 20))
        activityIndicator!.color = lookupColor["blue"]
        let barButton = UIBarButtonItem(customView: activityIndicator!)
        self.navigationItem.setRightBarButton(barButton, animated: true)
        activityIndicator!.startAnimating()
    }
    
    // Set outlets with event details if is in editing mode
    func setEventDetailsIfEditing() {
        if isEditingMode ?? false {
            
            // Set title and description
            titleTextField.text = Day.eventsOfTheDay![selectedEventRow!].title
            descriptionTextField.text = Day.eventsOfTheDay![selectedEventRow!].description
            
            // Set event position
            var eventPositionString: String = ""
            let geoCoder = CLGeocoder()
            let location = CLLocation(latitude: Day.eventsOfTheDay![selectedEventRow!].latitude, longitude: Day.eventsOfTheDay![selectedEventRow!].longitude)
            geoCoder.reverseGeocodeLocation(location, completionHandler: { (placemarks, error) -> Void in
                
                // Place details
                var placeMark: CLPlacemark!
                placeMark = placemarks?[0]
                if let addressDict = placeMark.addressDictionary, let coordinate = placeMark.location?.coordinate {
                    self.positionPlace = MKPlacemark(coordinate: coordinate, addressDictionary: (addressDict as! [String : Any]))
                }
                
                // Location name
                if let locationName = placeMark.addressDictionary!["Name"] as? NSString {
                    eventPositionString = locationName as String
                }
                // City
                if let city = placeMark.addressDictionary!["City"] as? NSString {
                    eventPositionString += ", " + (city as String)
                }
                
                 self.position.text = eventPositionString
            })
            
            // Set date time
            RawEventToBeAdded.start = Day.eventsOfTheDay![selectedEventRow!].start
            RawEventToBeAdded.end = Day.eventsOfTheDay![selectedEventRow!].end
            
            let startDate = Date(timeIntervalSince1970: Double(Day.eventsOfTheDay![selectedEventRow!].start))
            let endDate = Date(timeIntervalSince1970: Double(Day.eventsOfTheDay![selectedEventRow!].end))
            startTime.text = adjustDate(startDate)
            endTime.text = adjustDate(endDate)
            
            // Set flexible information
            if let flex = Day.eventsOfTheDay![selectedEventRow!].flexible_info {
                flexibleSwitch.isOn = true
                setFlexible(flexibleSwitch)
                durationTextField.text = "\(Int(flex.duration! / 60)) min"
                RawEventToBeAdded.duration = flex.duration
            }
            
            // Set recurrent information
            if let rec = Day.eventsOfTheDay![selectedEventRow!].repetitive_info {
                recurrentSwitch.isOn = true
                setRecurrent(recurrentSwitch)
                frequencyTextField.text = frequencyJSONToString[rec.frequency!]
                
                let untilDate = Date(timeIntervalSince1970: Double(rec.until!)).inRegion()
                untilTextField.text = "\(untilDate.day) \(untilDate.monthName.capitalizingFirstLetter()) \(untilDate.year)"
                
                RawEventToBeAdded.frequency = rec.frequency
                RawEventToBeAdded.until = rec.until
            }
            
            // Set travel information
            if let trav = Day.eventsOfTheDay![selectedEventRow!].travel {
                travelSwitch.isOn = true
                showTravel(travelSwitch)
                travelOptionCell.backgroundColor = UIColor.white
                travelOptionTextField.isUserInteractionEnabled = true
                
                var travelPositionString: String = ""
                let travelLocation = CLLocation(latitude: trav.startLatitude!, longitude: trav.startLongitude!)
                let travelGeoCoder = CLGeocoder()
                travelGeoCoder.reverseGeocodeLocation(travelLocation, completionHandler: { (placemarks, error) -> Void in
                    
                    // Place details
                    var placeMark: CLPlacemark!
                    placeMark = placemarks?[0]
                    if let addressDict = placeMark.addressDictionary, let coordinate = placeMark.location?.coordinate {
                        self.startTravelPlace = MKPlacemark(coordinate: coordinate, addressDictionary: (addressDict as! [String : Any]))
                    }
    
                    // Location name
                    if let locationName = placeMark.addressDictionary!["Name"] as? NSString {
                        travelPositionString = locationName as String
                    }
                    // City
                    if let city = placeMark.addressDictionary!["City"] as? NSString {
                        travelPositionString += ", " + (city as String)
                    }
                    
                    self.startTravelPosition.text = travelPositionString
                    RawEventToBeAdded.mean = trav.mean
                    RawEventToBeAdded.travelDuration = trav.duration
                    RawEventToBeAdded.distance = trav.distance
                })
                
                travelOptionIcon.image = travelIconFromString[trav.mean!]
                travelOptionTextField.text = "\(trav.distance!)" + " m" + ", " + " \(Int(trav.duration! / 60)) min"
            }
            
            
        }
    }
    
    func createToolBar() -> UIToolbar {
        let toolBar = UIToolbar()
        toolBar.barStyle = .default
        let doneButton = UIBarButtonItem(title: "Done", style: .done, target: self, action: #selector(donePressedPickerView))
        doneButton.tintColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 1)
        toolBar.setItems([doneButton], animated: true)
        toolBar.isUserInteractionEnabled = true
        toolBar.isTranslucent = true
        toolBar.tintColor = lookupColor["blue"]
        toolBar.sizeToFit()
        return toolBar
    }
    
    func createAddButton() {
        self.navigationController?.navigationBar.backItem?.backBarButtonItem = UIBarButtonItem(title: "Cancel", style: .plain, target: nil, action: nil)
        self.navigationController?.navigationBar.tintColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 1)
        
        if isEditingMode ?? false {
            self.navigationItem.rightBarButtonItem = UIBarButtonItem(title: "Edit", style: .done, target: self, action: #selector(editEvent))
            self.navigationItem.rightBarButtonItem?.isEnabled = true
            self.title = "Edit Event"
        } else {
            self.navigationItem.rightBarButtonItem = UIBarButtonItem(title: "Add", style: .done, target: self, action: #selector(addEvent))
            self.navigationItem.rightBarButtonItem?.isEnabled = false
            self.title = "Add Event"
        }
    }
    
    
    /* PICKERS */
    
    func createStartDatePicker() {
        datePicker.datePickerMode = .time
        datePicker.locale = NSLocale(localeIdentifier: "en_GB") as Locale
        datePicker.minuteInterval = 5
        datePicker.backgroundColor = UIColor.white
        datePicker.setDate(Day.dayDate, animated: true)
        
        // Toolbar
        let toolbar = UIToolbar()
        toolbar.sizeToFit()
        
        // Bar button item
        let doneButton = UIBarButtonItem(barButtonSystemItem: .done, target: nil, action: #selector(donePressedStart))
        toolbar.setItems([doneButton], animated: false)
        startTime?.inputAccessoryView = toolbar
        
        // Assign date picker to text field
        startTime?.inputView = datePicker
    }
    
    // Done pressed after start date chosen
    @objc func donePressedStart() {
        // Format date
        let dateFormatter = DateFormatter()
        dateFormatter.dateStyle = .short
        dateFormatter.timeStyle = .none
        
        RawEventToBeAdded.start = Int(datePicker.date.timeIntervalSince1970)
        startTime.text = adjustDate(datePicker.date)
        isStartTimeEmpty = false
        checkAddButton()
        checkTravelOption()
        
        self.view.endEditing(true)
    }

    func createEndDatePicker() {
        datePicker.datePickerMode = .time
        datePicker.locale = NSLocale(localeIdentifier: "en_GB") as Locale
        datePicker.minuteInterval = 5
        datePicker.backgroundColor = UIColor.white
        datePicker.setDate(Day.dayDate, animated: true)
        
        // Toolbar
        let toolbar = UIToolbar()
        toolbar.sizeToFit()
        
        // Bar button item
        let doneButton = UIBarButtonItem(barButtonSystemItem: .done, target: nil, action: #selector(donePressedEnd))
        toolbar.setItems([doneButton], animated: false)
        endTime?.inputAccessoryView = toolbar
        
        // Assign date picker to text field
        endTime?.inputView = datePicker
    }
    
    // Done pressed after end date chosen
    @objc func donePressedEnd() {
        // Format date
        let dateFormatter = DateFormatter()
        dateFormatter.dateStyle = .short
        dateFormatter.timeStyle = .none

        RawEventToBeAdded.end = Int(datePicker.date.timeIntervalSince1970)
        endTime.text = adjustDate(datePicker.date)
        isEndTimeEmpty = false
        
        checkAddButton()
        checkTravelOption()
        
        self.view.endEditing(true)
    }
    
    func createDurationPicker() {
        durationPicker.datePickerMode = .countDownTimer
        durationPicker.locale = NSLocale(localeIdentifier: "en_GB") as Locale
        durationPicker.minuteInterval = 5
        durationPicker.backgroundColor = UIColor.white
        
        // Toolbar
        let toolbar = UIToolbar()
        toolbar.sizeToFit()
        
        // Bar button item
        let doneButton = UIBarButtonItem(barButtonSystemItem: .done, target: nil, action: #selector(donePressedDuration))
        toolbar.setItems([doneButton], animated: false)
        durationTextField?.inputAccessoryView = toolbar
        
        // Assign date picker to text field
        durationTextField?.inputView = durationPicker
    }
    
    @objc func donePressedDuration() {
        // Format date
        let dateFormatter = DateFormatter()
        dateFormatter.dateStyle = .short
        dateFormatter.timeStyle = .none
        
        if(durationPicker.date.hour > 0) {
            durationTextField.text = "\(durationPicker.date.hour) h" + " \(durationPicker.date.minute) min"
            RawEventToBeAdded.duration = durationPicker.date.hour * 3600 + durationPicker.date.minute * 60
        } else {
            durationTextField.text = "\(durationPicker.date.minute) min"
            RawEventToBeAdded.duration = durationPicker.date.minute * 60
        }
        
        self.view.endEditing(true)
        
        checkAddButton()
        checkTravelOption()
    }
    
    func createUntilPicker() {
        untilPicker.datePickerMode = .date
        untilPicker.locale = NSLocale(localeIdentifier: "en_GB") as Locale
        
        // Toolbar
        let toolbar = UIToolbar()
        toolbar.sizeToFit()
        
        // Bar button item
        let doneButton = UIBarButtonItem(barButtonSystemItem: .done, target: nil, action: #selector(donePressedUntil))
        toolbar.setItems([doneButton], animated: false)
        untilTextField?.inputAccessoryView = toolbar
        
        // Assign date picker to text field
        untilTextField?.inputView = untilPicker
    }
    
    @objc func donePressedUntil() {
        // Format date
        let dateFormatter = DateFormatter()
        dateFormatter.dateStyle = .short
        dateFormatter.timeStyle = .none
        
        RawEventToBeAdded.until = Int(untilPicker.date.timeIntervalSince1970)
        untilTextField.text = "\(untilPicker.date.day) " + untilPicker.date.monthName + " \(untilPicker.date.year)"
        
        self.view.endEditing(true)
        
        checkAddButton()
    }
    
    @objc func donePressedPickerView() {
        self.view.endEditing(true)
    }
    
    
    /* BUTTONS ENABLING CONDITIONS */
    
    // Check conditions to make the done button enabled
    func checkAddButton() {
        if (isTitleEmpty || isPositionEmpty || isStartTimeEmpty || isEndTimeEmpty) || (flexibleSwitch.isOn && durationTextField.text == "") ||
            (recurrentSwitch.isOn && (untilTextField.text == "" || frequencyTextField.text == "")) ||
            (travelSwitch.isOn && startTravelPosition.text == "")
            {
            self.navigationItem.rightBarButtonItem?.isEnabled = false
        } else {
            self.navigationItem.rightBarButtonItem?.isEnabled = true
        }
    }
    
    // Check if the travel option cell is selectable
    func checkTravelOption() {
        if (isPositionEmpty || isStartTimeEmpty || isEndTimeEmpty || isStartTravelPositionEmpty) {
            travelOptionCell?.backgroundColor = UIColor(red: 242/255, green: 244/255, blue: 244/255, alpha: 1)
            travelOptionTextField?.isUserInteractionEnabled = false
        } else {
            travelOptionCell?.backgroundColor = UIColor.white
            travelOptionTextField?.isUserInteractionEnabled = true
        }
    }
    
    
    /* SWITCH BUTTONS */
    
    @IBAction func setFlexible(_ sender: UISwitch) {
        if sender.isOn {
            RawEventToBeAdded.isFlexible = true
            startLabel.text = "From"
            endLabel.text = "To"
        } else {
            RawEventToBeAdded.isFlexible = false
            startLabel.text = "Start"
            endLabel.text = "End"
        }
        tableView.beginUpdates()
        tableView.endUpdates()
        checkAddButton()
    }
    
    @IBAction func setRecurrent(_ sender: UISwitch) {
        if sender.isOn {
            RawEventToBeAdded.isRecurrent = true
        } else {
            RawEventToBeAdded.isRecurrent = false
        }
        tableView.beginUpdates()
        tableView.endUpdates()
        checkAddButton()
    }
    
    @IBAction func showTravel(_ sender: UISwitch) {
        if sender.isOn {
            RawEventToBeAdded.hasTravelOption = true
        } else {
            RawEventToBeAdded.hasTravelOption = false
        }
        tableView.beginUpdates()
        tableView.endUpdates()
        checkAddButton()
        checkTravelOption()
    }
    
    
    /* TABLE VIEW DELEGATE METHODS */
    
    override func tableView(_ tableView: UITableView, heightForRowAt indexPath: IndexPath) -> CGFloat {
        // Check if is flexible
        if indexPath.section == 2 && indexPath.row == 3 && flexibleSwitch.isOn == false {
            return 0
        }
        // Check if is recurrent
        if indexPath.section == 2 && (indexPath.row == 5 || indexPath.row == 6) && recurrentSwitch.isOn == false {
            return 0
        }
        // Check if travel option is selected
        if indexPath.section == 3 && (indexPath.row == 1 || indexPath.row == 2) && travelSwitch.isOn == false {
            return 0
        }
        // Check if is editing mode
        if indexPath.section == 5 {
            return 0
        }

        return super.tableView(tableView, heightForRowAt: indexPath)
    }
    
    
    /* HTTP REQUESTS */
    
    /**
     Collects the data set in the *RawEventToBeAdded* structure and send it to the server to add a new event.
     
     
     - Parameter token: the Travlendar+ user token.
     
     - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
     - Parameter statusCode: the HTTP status code returned from the POST request.
     - Parameter data: the data returned from the server if the request was successfully made.
     */
    func addEventToServer(authenticateWith token: String, completion: @escaping (_ statusCode: Int, _ data: Data?) -> Void) {
        
        createActivityIndicator()
        
        let headers: HTTPHeaders = [
            "Accept": "application/json",
            "Content-Type": "application/json",
            "Authorization": "Bearer " + token
        ]
        
        let parameters: Parameters = [
            "title": RawEventToBeAdded.title ?? " ",
            "start": RawEventToBeAdded.start!,
            "end": RawEventToBeAdded.end!,
            "longitude": Double(positionPlace?.coordinate.longitude ?? 0),
            "latitude": Double(positionPlace?.coordinate.latitude ?? 0),
            "description": RawEventToBeAdded.description ?? descriptionTextField.text!,
            "category": labelTextField?.text! ?? "green",
            "repetitive": RawEventToBeAdded.isRecurrent ?? false,
            "until": RawEventToBeAdded.until ?? 0,
            "frequency": RawEventToBeAdded.frequency ?? "day",
            "flexible": RawEventToBeAdded.isFlexible ?? false,
            "duration": RawEventToBeAdded.duration ?? 0,
            "upperBound": RawEventToBeAdded.end!,
            "lowerBound": RawEventToBeAdded.start!,
            "travel": RawEventToBeAdded.hasTravelOption ?? false,
            "mean": RawEventToBeAdded.mean ?? "",
            "startLongitude": Double(startTravelPlace?.coordinate.longitude ?? 0),
            "startLatitude":  Double(startTravelPlace?.coordinate.latitude ?? 0),
            "travelDuration": RawEventToBeAdded.travelDuration ?? 0,
            "distance": RawEventToBeAdded.distance ?? 0,
            "adjustements": RawEventToBeAdded.adjustements ?? []
        ]
        
        Alamofire.request(serverName + "api/v1/event", method: .post, parameters: parameters,  encoding: JSONEncoding.default, headers: headers).responseJSON { response in
            
            // Check if the HTTP request was effectively performed
            if let statusCode = response.response?.statusCode {
            
                // Success
                if statusCode == 200 {
                    if let json = response.result.value {
                        print("JSON: \(json)")
                    }
                    // Reload events table and pop the view controller
                    self.handleAddEventDelegate?.reloadEventsTable()
                    self.navigationController?.popViewController(animated: true)
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
                    print(response.result.value!)
                    print("Error status code: \(statusCode)")
                    
                    var error: ErrorResponse?
                    
                    do {
                        error = try JSONDecoder().decode(ErrorResponse.self, from: response.data!)
                    } catch let e { print("Error parsing \(e)")}
                    
                    let alertController = UIAlertController(title: error?.message!, message: error?.feasibility?.errors?[0].cause, preferredStyle: .alert)
                    
                    let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                    alertController.addAction(defaultAction)
                    
                    self.present(alertController, animated: true, completion: nil)
                }
                
                // Callback function
                completion(statusCode, response.data)
            }
                
            // HTTP request failed
            else {
                // Display an alert view controller
                let alertController = UIAlertController(title: "Connection failed", message: "Your event cannot be added. Please check your connection and retry.", preferredStyle: .alert)
                
                let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                alertController.addAction(defaultAction)
                
                self.present(alertController, animated: true, completion: nil)
            }
            
            self.createAddButton()
            self.checkAddButton()
        }
    }
    
    func getAdjustements (authenticateWith token: String, completion: @escaping (_ statusCode: Int, _ data: Data?) -> Void) {
        
        let headers: HTTPHeaders = [
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        ]
        
        let parameters: Parameters = [
            "title": RawEventToBeAdded.title ?? "",
            "start": RawEventToBeAdded.start ?? 0,
            "end": RawEventToBeAdded.end ?? 0,
            "longitude": Double(positionPlace?.coordinate.longitude ?? 0),
            "latitude": Double(positionPlace?.coordinate.latitude ?? 0),
            "description": RawEventToBeAdded.description ?? "",
            "category": RawEventToBeAdded.category ?? "green",
            "travel": false,
            "flexible": RawEventToBeAdded.isFlexible ?? false,
            "repetitive": RawEventToBeAdded.isRecurrent ?? false,
            "startLongitude": Double(startTravelPlace?.coordinate.longitude ?? 0),
            "startLatitude": Double(startTravelPlace?.coordinate.latitude ?? 0),
            "duration": RawEventToBeAdded.duration ?? 0
        ]
        
        Alamofire.request(serverName + "api/v1/generator", method: .post, parameters: parameters,  encoding: JSONEncoding.default, headers: headers).responseJSON { response in
            
            
            if let statusCode = response.response?.statusCode {
                // Success
                if statusCode == 200 {
                    
                
                    if let json = response.result.value {
                        print("JSON: \(json)")
                    }
                    do {
                        let raw = try JSONDecoder().decode(RawTravel.self, from: response.data!)
                        RawEventToBeAdded.adjustements = raw.options?[0].adjustements
                        
                    } catch let e { print("Error parsing \(e)")}
                    
                }
                // Error
                else {
                    
                }
                // Callback
                completion(statusCode, response.data)
                
            }
            // HTTP request failed
            else {
                    
            }

        }
    }
    
//    // FIXME Edit event and send it to the server (CURRENTLY NOT AVAILABLE)
//    func editEventAndSendToServer() {
//        let headers: HTTPHeaders = [
//            "Accept": "application/json",
//            "Authorization": "Bearer " + User.token!
//        ]
//
//        let parameters: Parameters = [
//            "title": titleTextField.text!,
//            "start": RawEventToBeAdded.start!,
//            "end": RawEventToBeAdded.end!,
//            "longitude": Double(positionPlace!.coordinate.longitude),
//            "latitude": Double(positionPlace!.coordinate.latitude),
//            "description": descriptionTextField.text!,
//            "category": labelTextField.text!,
//            "repetitive": recurrentSwitch.isOn,
//            "until": RawEventToBeAdded.until ?? 0,
//            "frequency": RawEventToBeAdded.frequency ?? 0,
//            "flexible": flexibleSwitch.isOn,
//            "duration": RawEventToBeAdded.duration ?? 0,
//            "upperBound": RawEventToBeAdded.end!,
//            "lowerBound": RawEventToBeAdded.start!,
//            "travel": travelSwitch.isOn,
//            "mean": RawEventToBeAdded.mean ?? "",
//            "startLongitude": Double(startTravelPlace?.coordinate.longitude ?? 0) ,
//            "startLatitude":  Double(startTravelPlace?.coordinate.latitude ?? 0),
//            "travelDuration": RawEventToBeAdded.travelDuration ?? 0,
//            "distance": RawEventToBeAdded.distance ?? 0,
//            "bookingId": 0,
//            "adjustements": []
//            ]
//
//        Alamofire.request(serverName + "api/v1/event/\(Day.eventsOfTheDay![selectedEventRow!].id!)", method: .put, parameters: parameters,  encoding: JSONEncoding.default, headers: headers).responseJSON { response in
//
//            if let statusCode = response.response?.statusCode {
//
//                if statusCode == 200 {
//                    if let json = response.result.value {
//                        print("JSON: \(json)")
//                    }
//                    let alertController = UIAlertController(title: "Operation completed", message: "Event successfully edited!", preferredStyle: .alert)
//
//                    let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
//                    alertController.addAction(defaultAction)
//
//                    self.present(alertController, animated: true, completion: nil)
//
//                } else {
//                    print(response.result.value!)
//                    print("Error status code: \(response.response!.statusCode)")
//                    let alertController = UIAlertController(title: "We are sorry", message: "This functionality is currently not available", preferredStyle: .alert)
//
//                    let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
//                    alertController.addAction(defaultAction)
//
//                    self.present(alertController, animated: true, completion: nil)
//                }
//            }
//        }
//    }
    
    
    /* PREPARE FOR SEGUE */
    
    override func prepare(for segue: UIStoryboardSegue, sender: Any?) {
        
        // Get Position
        if (segue.identifier == "getPosition") {
            let localSearchVC = segue.destination as! LocalSearchViewController
            localSearchVC.delegate = self
            localSearchVC.isStartTravelPositionRequested = requestStartTravelPosition
        }
        
        // Show travel options
        else if (segue.identifier == "showTravelOptions") {
            let travelVC = segue.destination as! TravelViewController
            travelVC.delegate = self
            travelVC.eventToGetTravelOptions = EventToGetTravelOptions(
                title: titleTextField.text!,
                description: descriptionTextField.text!,
                start: RawEventToBeAdded.start!,
                end: RawEventToBeAdded.end!,
                longitude: Double((self.positionPlace?.coordinate.longitude) ?? 0),
                latitude: Double((self.positionPlace?.coordinate.latitude) ?? 0),
                category: labelTextField.text!,
                travel: travelSwitch.isOn,
                flexible: flexibleSwitch.isOn,
                repetitive: recurrentSwitch.isOn,
                startLongitude: Double((self.startTravelPlace?.coordinate.longitude) ?? 0),
                startLatitude: Double((self.startTravelPlace?.coordinate.latitude) ?? 0),
                duration: RawEventToBeAdded.duration ?? 0)
        }
    }
    
}


/* PROTOCOLS CONFORMATION */

// Handle both the event position selection and the start travel position selection and displays the result
extension AddEventViewController : HandlePlaceSelected {
    func passStartTravelPosition(place: MKPlacemark) {
        self.startTravelPlace = place
        let infos = getInformation(from: place)
        
        startTravelPosition.text = (startTravelPlace?.name)! + ", " + infos.0 + " " + infos.1
        isStartTravelPositionEmpty = false
        
        checkAddButton()
        checkTravelOption()
    }
    
    func passPosition(place: MKPlacemark) {
        self.positionPlace = place
        let infos = getInformation(from: place)
        
        position.text = (positionPlace?.name)! + ", " + infos.0 + " " + infos.1
        isPositionEmpty = false
        
        checkAddButton()
        checkTravelOption()
    }
    
    // Support function to display the place information in an elegant way
    func getInformation(from place: MKPlacemark) -> (String, String, String, String) {
        let addressDictionary = place.addressDictionary
        
        let address = addressDictionary!["Street"]
        let city = addressDictionary!["City"]
        let state = addressDictionary!["State"]
        let zip = addressDictionary!["ZIP"]
        
        return ("\(address ?? "")",
            "\(city ?? "")",
            "\(state ?? "")",
            "\(zip ?? "")")
    }
    
}

// Handle the travel selection and displays the chosen travel
extension AddEventViewController: HandleTravelSelected {
    func passTravel(travelOption: RawTravelOption) {
        self.travelOptionSelected = travelOption
        travelOptionTextField.text = "\(travelOption.travel!.distance!)" + " m" + ", " + " \(Int(travelOption.travel!.duration! / 60)) min"
        travelOptionIcon.image = travelIconFromString[travelOption.travel!.mean!]
        
        RawEventToBeAdded.distance = travelOption.travel!.distance!
        RawEventToBeAdded.travelDuration = travelOption.travel!.duration!
        RawEventToBeAdded.mean = travelOption.travel!.mean!
        RawEventToBeAdded.adjustements = travelOption.adjustements
    }
}


/* TEXT FIELD DELEGATE */

extension AddEventViewController: UITextFieldDelegate {
    func textFieldShouldBeginEditing(_ textField: UITextField) -> Bool {
        // Check the texfield type

        if textField == startTravelPosition {
            requestStartTravelPosition = true
            self.performSegue(withIdentifier: "getPosition", sender: self)
            return false
        } else if textField == position {
            requestStartTravelPosition = false
            self.performSegue(withIdentifier: "getPosition", sender: self)
            return false
        } else if textField == travelOptionTextField {
            self.performSegue(withIdentifier: "showTravelOptions", sender: self)
            return false
        }
        
        return true
    }
    
    func textFieldDidEndEditing(_ textField: UITextField) {
        if textField == titleTextField {
            if titleTextField.text == "" {
                isTitleEmpty = true
            } else {
                isTitleEmpty = false
            }
            RawEventToBeAdded.title = textField.text
        } else if textField == descriptionTextField {
            RawEventToBeAdded.description = textField.text
        }
        
        checkAddButton()
        checkTravelOption()
    }
    
    func textFieldShouldReturn(_ scoreText: UITextField) -> Bool {
        self.view.endEditing(true)
        return false
    }
    
}

// Implements all the methods to make the pickerviews work
extension AddEventViewController: UIPickerViewDataSource, UIPickerViewDelegate {
    func numberOfComponents(in pickerView: UIPickerView) -> Int {
        return 1
    }
    
    func pickerView(_ pickerView: UIPickerView, numberOfRowsInComponent component: Int) -> Int {
        if pickerView == colorPickerView {
            return colorPickOption.count
        } else {
            return frequencyPickOption.count
        }
        
    }
    
    func pickerView(_ pickerView: UIPickerView, titleForRow row: Int, forComponent component: Int) -> String? {
        if pickerView == colorPickerView {
            return colorPickOption[row]
        } else {
            return frequencyPickOption[row]
        }
    }
    
    func pickerView(_ pickerView: UIPickerView, didSelectRow row: Int, inComponent component: Int) {
        if pickerView == colorPickerView {
            labelTextField.text = colorPickOption[row]
            labelTextField.textColor = lookupColor[colorPickOption[row]]
        } else {
            frequencyTextField.text = frequencyPickOption[row]
            RawEventToBeAdded.frequency = frequencyStringToJSON[frequencyPickOption[row]]
        }
    }
    
}

// To make the first letter capital
extension String {
    func capitalizingFirstLetter() -> String {
        return prefix(1).uppercased() + dropFirst()
    }
    
    mutating func capitalizeFirstLetter() {
        self = self.capitalizingFirstLetter()
    }
}

