//
//  PreferencesViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 08/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import Alamofire

class PreferencesViewController: UITableViewController {
    
    // Outlets
    @IBOutlet weak var menuButton: UIBarButtonItem!
    
    @IBOutlet weak var busButton: UIButton!
    @IBOutlet weak var carButton: UIButton!
    @IBOutlet weak var walkingButton: UIButton!
    @IBOutlet weak var bikeButton: UIButton!
    @IBOutlet weak var uberButton: UIButton!
    
    @IBOutlet weak var busTextField: UITextField!
    @IBOutlet weak var carTextField: UITextField!
    @IBOutlet weak var walkingTextField: UITextField!
    @IBOutlet weak var bikeTextField: UITextField!
    @IBOutlet weak var uberTextField: UITextField!
    
    // Distance picker (m)
    private let distancePickerView = UIPickerView()
    private var distancePickOption = ["500", "1000", "1500", "2000", "2500", "5000", "7500", "10000"]
    
    // Determine which text field is currently active
    private var activeTextField: UITextField?
    
    
    /* VIEWCONTROLLER LIFECYCLE */
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        //Hide Autolayout Warning
        UserDefaults.standard.setValue(false, forKey:"_UIConstraintBasedLayoutLogUnsatisfiable")
        
        // Call UI methods
        addNavBarImage()
        sideMenu()
        createSaveButton()
        
        let toolBar = createToolBar()
        
        self.hideKeyboardWhenTappedAround()
        
        distancePickerView.delegate = self
        distancePickerView.backgroundColor = UIColor.white
        distancePickerView.showsSelectionIndicator = true
        
        // Matches each text field with its own picker
        busTextField?.inputView = distancePickerView
        busTextField?.inputAccessoryView = toolBar
        walkingTextField?.inputView = distancePickerView
        walkingTextField?.inputAccessoryView = toolBar
        bikeTextField?.inputView = distancePickerView
        bikeTextField?.inputAccessoryView = toolBar
        carTextField?.inputView = distancePickerView
        carTextField?.inputAccessoryView = toolBar
        uberTextField?.inputView = distancePickerView
        uberTextField?.inputAccessoryView = toolBar
        
    }
    
    override func viewDidAppear(_ animated: Bool) {
        super.viewDidAppear(true)
        setOutlets()
    }
    
    /* UI METHODS */
    
    func createToolBar() -> UIToolbar {
        let toolBar = UIToolbar()
        toolBar.barStyle = .default
        let doneButton = UIBarButtonItem(title: "Done", style: .done, target: self, action: #selector(donePressedPickerView))
        toolBar.setItems([doneButton], animated: true)
        toolBar.isUserInteractionEnabled = true
        toolBar.isTranslucent = true
        toolBar.tintColor = lookupColor["blue"]
        toolBar.sizeToFit()
        return toolBar
    }
    
    // Called when the done button is pressed in a picker view
    @objc func donePressedPickerView() {
        self.view.endEditing(true)
    }
    
    func createSaveButton() {
        self.navigationController?.navigationBar.tintColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 1)
        self.navigationItem.rightBarButtonItem = UIBarButtonItem(title: "Save", style: .done, target: self, action: #selector(save))
        self.navigationItem.rightBarButtonItem?.isEnabled = true
    }
    
    @objc func save() {
        savePreferences(authenticateWith: User.token!) {_ in }
    }
    
    // Display side menu on the left
    func sideMenu() {
        if revealViewController() != nil {
            menuButton.target = revealViewController()
            menuButton.action = #selector(SWRevealViewController.revealToggle(_:))
            revealViewController().rearViewRevealWidth = 270
        }
    }
    
    func addNavBarImage() {
        let imageView = UIImageView(image: #imageLiteral(resourceName: "t_plus"))
        imageView.frame = CGRect(x: 0, y: 0, width: 30, height: 30)
        imageView.contentMode = .scaleAspectFit
        imageView.clipsToBounds = true
        navigationItem.titleView = imageView
    }
    
    func findFirstResponder(in view: UIView) -> UIView? {
        for subview in view.subviews {
            if subview.isFirstResponder {
                return subview
            }
            if let firstReponder = findFirstResponder(in: subview) {
                return firstReponder
            }
        }
        return nil
    }
    
    // Set buttons on/off according to the preferences
    func setOutlets() {
        if Preferences.busOn {
            busButton.setImage(#imageLiteral(resourceName: "bus_on"), for: .normal)
        } else {
            busButton.setImage(#imageLiteral(resourceName: "bus_off"), for: .normal)
        }
        if Preferences.carOn {
            carButton.setImage(#imageLiteral(resourceName: "car_on"), for: .normal)
        } else {
            carButton.setImage(#imageLiteral(resourceName: "car_off"), for: .normal)
        }
        if Preferences.walkingOn {
            walkingButton.setImage(#imageLiteral(resourceName: "walking_on"), for: .normal)
        } else {
            walkingButton.setImage(#imageLiteral(resourceName: "walking_off"), for: .normal)
        }
        if Preferences.bikeOn {
            bikeButton.setImage(#imageLiteral(resourceName: "bike_on"), for: .normal)
        } else {
            bikeButton.setImage(#imageLiteral(resourceName: "bike_off"), for: .normal)
        }
        if Preferences.uberOn {
            uberButton.setImage(#imageLiteral(resourceName: "uber_on"), for: .normal)
        } else {
            uberButton.setImage(#imageLiteral(resourceName: "uber_off"), for: .normal)
        }
        
        walkingTextField.text = "\(Preferences.walkingMaxDistance ?? 10000) m"
        busTextField.text = "\(Preferences.busMaxDistance ?? 10000) m"
        bikeTextField.text = "\(Preferences.bikeMaxDistance ?? 10000) m"
        uberTextField.text = "\(Preferences.uberMaxDistance ?? 10000) m"
        carTextField.text = "\(Preferences.carMaxDistance ?? 10000) m"
    }
    
    
    /* SET PREFERENCES */
    
    @IBAction func setBusPreference(_ sender: UIButton) {
        if Preferences.busOn {
            sender.setImage(UIImage(named: "bus_off"), for: .normal)
            Preferences.busOn = false
        } else {
            sender.setImage(UIImage(named: "bus_on"), for: .normal)
            Preferences.busOn = true
        }
        
    }
    
    @IBAction func setWalkingPreference(_ sender: UIButton) {
        if Preferences.walkingOn {
            sender.setImage(UIImage(named: "walking_off"), for: .normal)
            Preferences.walkingOn = false
        } else {
            sender.setImage(UIImage(named: "walking_on"), for: .normal)
            Preferences.walkingOn = true
        }
        
    }
    
    @IBAction func setUberPreference(_ sender: UIButton) {
        if Preferences.uberOn {
            sender.setImage(UIImage(named: "uber_off"), for: .normal)
            Preferences.uberOn = false
        } else {
            sender.setImage(UIImage(named: "uber_on"), for: .normal)
            Preferences.uberOn = true
        }
        
    }
    
    @IBAction func setBikePreference(_ sender: UIButton) {
        if Preferences.bikeOn {
            sender.setImage(UIImage(named: "bike_off"), for: .normal)
            Preferences.bikeOn = false
        } else {
            sender.setImage(UIImage(named: "bike_on"), for: .normal)
            Preferences.bikeOn = true
        }
        
    }
    
    @IBAction func setCarPreference(_ sender: UIButton) {
        if Preferences.carOn {
            sender.setImage(UIImage(named: "car_off"), for: .normal)
            Preferences.carOn = false
        } else {
            sender.setImage(UIImage(named: "car_on"), for: .normal)
            Preferences.carOn = true
        }
        
    }
    
    
    /* TABLE VIEW DELEGATE */
    
    override func tableView(_ tableView: UITableView, didSelectRowAt indexPath: IndexPath) {
        if indexPath.section == 1 {
            let alertController = UIAlertController(title: "We are sorry", message: "This function is currently unavailable.", preferredStyle: .alert)
            
            let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
            alertController.addAction(defaultAction)
            
            self.present(alertController, animated: true, completion: nil)
        }
    }
    
    /* HTTP REQUESTS */
    
    /**
     Send the user preferences to the server and save them if the request was successfully completed.
     
     - Parameter token: the Travlendar+ user token.

     - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
     - Parameter statusCode: the HTTP status code returned from the PUT request.
     */
    func savePreferences(authenticateWith token: String, completion: @escaping (_ statusCode: Int) -> Void) {
        let headers: HTTPHeaders = [
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        ]
        
        let parameters: Parameters = [
            "transit": ["active": Preferences.busOn, "maxDistance": Preferences.busMaxDistance ?? 10000],
            "walking": ["active": Preferences.walkingOn,"maxDistance": Preferences.walkingMaxDistance ?? 10000],
            "driving": ["active": Preferences.carOn,"maxDistance": Preferences.carMaxDistance ?? 10000],
            "cycling": ["active": Preferences.bikeOn,"maxDistance": Preferences.bikeMaxDistance ?? 10000],
            "uber": ["active": Preferences.uberOn,"maxDistance": Preferences.uberMaxDistance ?? 10000],
            ]
        
        Alamofire.request(serverName + "api/v1/preferences", method: .put, parameters: parameters,  encoding: JSONEncoding.default, headers: headers).responseJSON { response in
            print(response.result.value!)
            
            let statusCode = response.response!.statusCode
            
            // Success
            if statusCode == 200 {
                // Update user credentials
                getUserCredentials(authenticateWith: token) { _,_ in }
                
                // Display alert controller
                let alertController = UIAlertController(title: "Success", message: "Preferences successfully saved!", preferredStyle: .alert)
                
                let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                alertController.addAction(defaultAction)
                
                self.present(alertController, animated: true, completion: nil)
                
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
                // Display alert controller
                print("Error status code: \(statusCode)")
                let alertController = UIAlertController(title: "Error", message: "An error occurred while trying to send your preferences to the server. Please check your connection and retry.", preferredStyle: .alert)
                
                let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                alertController.addAction(defaultAction)
                
                self.present(alertController, animated: true, completion: nil)
            }
            
            completion(statusCode)
        }
    }

}


/* PICKER VIEW DELEGATE */

extension PreferencesViewController: UIPickerViewDataSource, UIPickerViewDelegate, UITextViewDelegate {
    func numberOfComponents(in pickerView: UIPickerView) -> Int {
        return 1
    }
    
    func pickerView(_ pickerView: UIPickerView, numberOfRowsInComponent component: Int) -> Int {
        return distancePickOption.count
    }
    
    func pickerView(_ pickerView: UIPickerView, titleForRow row: Int, forComponent component: Int) -> String? {
        return distancePickOption[row]
    }
    
    func pickerView(_ pickerView: UIPickerView, didSelectRow row: Int, inComponent component: Int) {
        let activeTextField = (self.findFirstResponder(in: view) as! UITextField)
        if activeTextField == walkingTextField {
            Preferences.walkingMaxDistance = Int(distancePickOption[row])
            walkingTextField.text = distancePickOption[row] + " m"
        } else if activeTextField == busTextField {
            Preferences.busMaxDistance = Int(distancePickOption[row])
            busTextField.text = distancePickOption[row] + " m"
        } else if activeTextField == carTextField {
            Preferences.carMaxDistance = Int(distancePickOption[row])
            carTextField.text = distancePickOption[row] + " m"
        } else if activeTextField == bikeTextField {
            Preferences.bikeMaxDistance = Int(distancePickOption[row])
            bikeTextField.text = distancePickOption[row] + " m"
        } else if activeTextField == uberTextField {
            Preferences.uberMaxDistance = Int(distancePickOption[row])
            uberTextField.text = distancePickOption[row] + " m"
        }
    
    }
    
}
