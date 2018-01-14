//
//  CalendarViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 08/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import JTAppleCalendar
import SwiftDate
import Alamofire

struct RawDaysWithEvents: Codable {
    var days: [Int]
    var message: String
    var timezone: String
}

var daysWithEvents: [String] = []

class CalendarViewController: UIViewController {
    
    // Outlets
    @IBOutlet weak var menuButton: UIBarButtonItem!
    @IBOutlet weak var calendarView: JTAppleCalendarView!
    @IBOutlet weak var year: UILabel!
    @IBOutlet weak var month: UILabel!
    @IBOutlet weak var loadingView: UIView!
    @IBOutlet weak var spinner: UIActivityIndicatorView!
    
    private var dayNumber: String?
    private var dayString: String?
    
    private let formatter = DateFormatter()

    
    /* VIEW CONTROLLER LIFECYCLE */
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        // Call UI methods
        addNavBarImage()
        sideMenu()
        
        // Set up calendar and get days with events
        setupCalendarView()
        calendarView.scrollToDate(Date())
        getDaysWithEvent(authenticateWith: User.token ?? "") { (statusCode, data) in
            self.calendarView.reloadData()
        }
    }

    
    /* UI METHODS */
    
    private func sideMenu() {
        if revealViewController() != nil {
            menuButton.target = revealViewController()
            menuButton.action = #selector(SWRevealViewController.revealToggle(_:))
            revealViewController().rearViewRevealWidth = 270
        }
    }
    
    private func addNavBarImage() {
        let imageView = UIImageView(image: #imageLiteral(resourceName: "t_plus"))
        imageView.frame = CGRect(x: 0, y: 0, width: 30, height: 30)
        imageView.contentMode = .scaleAspectFit
        imageView.clipsToBounds = true
        navigationItem.titleView = imageView
    }
    
    private func setLoadingView() {
        loadingView?.isHidden = false
        spinner?.startAnimating()
    }
    
    private func removeLoadingView() {
        loadingView?.isHidden = true
        spinner?.stopAnimating()
    }
    
    /* UI CALENDAR */
    
    private func setupCalendarView() {
        calendarView.minimumLineSpacing = 0
        calendarView.minimumInteritemSpacing = 0
        
        calendarView.calendarDelegate = self
        calendarView.calendarDataSource = self
        
        calendarView.visibleDates { (visibleDates) in
            let date = visibleDates.monthDates.first!.date
            
            self.formatter.dateFormat = "yyyy"
            self.year.text = self.formatter.string(from: date)
            
            self.formatter.dateFormat = "MMMM"
            self.month.text = self.formatter.string(from: date)
            
        }
    }
    
    // Select the correct color for the calendar cell
    private func handleCellTextColor(view: JTAppleCell?, cellState: CellState) {
        guard let validCell = view as? CalendarCell else { return }
        
        formatter.dateFormat = "yyyy MM dd"
        let todayDate = Date()
        let todayDateString = formatter.string(from: todayDate)
        let cellDateString = formatter.string(from: cellState.date)
        
        // If the cell was selected
        if cellState.isSelected {
            validCell.dateLabel.textColor = UIColor.white
        }
        // Otherwise
        else {
            // If the date of the cell belongs to the current month
            if cellState.dateBelongsTo == .thisMonth {
                validCell.isUserInteractionEnabled = true
                
                // If is today
                if cellDateString == todayDateString {
                    validCell.dateLabel.textColor = UIColor.red
                } else {
                validCell.dateLabel.textColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha:1)
                }
            }
            
            else {
                validCell.isUserInteractionEnabled = false
                
                if cellDateString == todayDateString {
                    validCell.dateLabel.textColor = UIColor.red.withAlphaComponent(0.3)
                } else {
                    validCell.dateLabel.textColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 0.3)
                }
               
            }
            
        }
        
    }
    
    private func handleCellSelected(view: JTAppleCell?, cellState: CellState) {
        guard let validCell = view as? CalendarCell else { return }
        
        if cellState.isSelected {
            validCell.selectedView.isHidden = false
        } else {
            validCell.selectedView.isHidden = true
        }
    }
    
    // Determines when to show a dot in the calendar cell
    private func handleCellContainsAlmostOneEvent(view: JTAppleCell?, cellState: CellState) {
        guard let validCell = view as? CalendarCell else { return }
        
        formatter.dateFormat = "yyyy-MM-dd"
        let todayDate = formatter.string(from: DateInRegion().startOfDay.absoluteDate)
        let cellDate = formatter.string(from: DateInRegion(absoluteDate: cellState.date).startOfDay.absoluteDate)
        
        // If the date of the cell contains at least one event displays a dot
        if daysWithEvents.contains(cellDate) {
            validCell.eventView.isHidden = false
            
            // If the cell is in the current month
            if cellState.dateBelongsTo == .thisMonth {
                
                // If the cell is the current day
                if cellDate == todayDate {
                    validCell.eventView.backgroundColor = UIColor.red
                } else {
                    validCell.eventView.backgroundColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 1)
                }
            }
            
            // If the cell is outside the current month
            else {
                
                // If the cell is the current day
                if cellDate == todayDate {
                    validCell.eventView.backgroundColor = UIColor.red.withAlphaComponent(0.3)
                } else {
                    validCell.eventView.backgroundColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 0.3)
                }
            }
        }
        
        // Otherwise if it doesn't contain at least one event make it hidden
        else {
            validCell.eventView.isHidden = true
        }
        
    }
    
    
    /* HTTP REQUESTS */
    
    func getDaysWithEvent(authenticateWith token: String, completion: @escaping (Int, Data?) -> Void) {
        
        // Empty the old data
        daysWithEvents = []
        
        // Set loading screen
        self.setLoadingView()
        
        // Initialize date formatter
        let dateFormatter = DateFormatter()
        dateFormatter.dateFormat = "yyyy-MM-dd"
        var secondsFromGMT: Int { return TimeZone.current.secondsFromGMT() }
        
        let headers: HTTPHeaders = [
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        ]
        
        let parameters: Parameters = [
            "from": 0,
            "to": 1545320546,
            "epoch": 1,
            "gmt": secondsFromGMT/3600
        ]
        
        Alamofire.request(serverName + "api/v1/days", method: .get, parameters: parameters, headers: headers).responseJSON { response in
            
            // Check if the HTTP request was effectively performed
            if let statusCode = response.response?.statusCode {
            
                // Success
                if statusCode == 200 {
                    if let json = response.result.value {
                        print("JSON: \(json)")}
                    
                    do {
                        // Decoding
                        let rawDays = try JSONDecoder().decode(RawDaysWithEvents.self, from: response.data!)
                        
                        // Save dates as time intervals
                        for rawDate in rawDays.days {
                            let date = Date(timeIntervalSince1970: TimeInterval(rawDate))
                            daysWithEvents.append(dateFormatter.string(from: date))
                        }
                        
                    } catch let e {print("Error parsing \(e)")}
                    
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
                    print("Error status code: \(statusCode) for days with event request")
                    print(response.result.value!)
                }
                
                // Callback
                completion(statusCode, response.data)
            }
            
            // If the HTTP request failed
            else {
                
                // Display an alert view controller
                let alertController = UIAlertController(title: "Connection failed", message: "Your events could not be retrieved. Please check your connection and retry.", preferredStyle: .alert)
                
                let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                alertController.addAction(defaultAction)
                
                self.present(alertController, animated: true, completion: nil)
            }
            
            self.removeLoadingView()
        }
        
    }

}


/* JTAPPLE CALENDAR DELEGATE */

// Configure the calendar
extension CalendarViewController: JTAppleCalendarViewDelegate {
    
    func configureCalendar(_ calendar: JTAppleCalendarView) -> ConfigurationParameters {
        formatter.dateFormat = "yyyy MM dd"
        formatter.timeZone = Calendar.current.timeZone
        formatter.locale = Calendar.current.locale
        
        let startDate = formatter.date(from: "2016 01 01")!
        let endDate = formatter.date(from: "2020 12 31")!
        
        let parameters = ConfigurationParameters(startDate: startDate, endDate: endDate)
        return parameters
    }
    
}

// Menage the calendar 
extension CalendarViewController: JTAppleCalendarViewDataSource {

    func calendar(_ calendar: JTAppleCalendarView, willDisplay cell: JTAppleCell, forItemAt date: Date, cellState: CellState, indexPath: IndexPath) {
       
        let myCustomCell = cell as! CalendarCell
        sharedFunctionToConfigureCell(myCustomCell: myCustomCell, cellState: cellState, date: date)
    }
    
    func calendar(_ calendar: JTAppleCalendarView, cellForItemAt date: Date, cellState: CellState, indexPath: IndexPath) -> JTAppleCell {
        let myCustomCell = calendar.dequeueReusableCell(withReuseIdentifier: "CalendarCell", for: indexPath) as! CalendarCell
        sharedFunctionToConfigureCell(myCustomCell: myCustomCell, cellState: cellState, date: date)
        return myCustomCell
    }
    
    func sharedFunctionToConfigureCell(myCustomCell: CalendarCell, cellState: CellState, date: Date) {
        myCustomCell.dateLabel.text = cellState.text
        handleCellSelected(view: myCustomCell, cellState: cellState)
        handleCellTextColor(view: myCustomCell, cellState: cellState)
        handleCellContainsAlmostOneEvent(view: myCustomCell, cellState: cellState)
    }
    
    // Move to the specific day view
    func calendar(_ calendar: JTAppleCalendarView, didSelectDate date: Date, cell: JTAppleCell?, cellState: CellState) {
        handleCellSelected(view: cell, cellState: cellState)
        let dateInRegion = date.inLocalRegion()
        
        Day.dayDate = dateInRegion.absoluteDate
        
        self.performSegue(withIdentifier: "showDay", sender: self)
    }
    
    func calendar(_ calendar: JTAppleCalendarView, didScrollToDateSegmentWith visibleDates: DateSegmentInfo) {
        let date = visibleDates.monthDates.first!.date
        
        formatter.dateFormat = "yyyy"
        year.text = formatter.string(from: date)
        
        formatter.dateFormat = "MMMM"
        month.text = formatter.string(from: date)
       
    }
}

