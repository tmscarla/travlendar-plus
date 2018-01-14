//
//  SignUpViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 06/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import Alamofire


class SignUpViewController: UIViewController, UITextFieldDelegate {
    
    // Support struct to handle the decoding of an error
    private struct ErrorMessage : Decodable {
        var message: String
        var errors: Error
        
        struct Error: Decodable {
            var email: [String]?
            var password: [String]?
            var name: [String]?
        }
    }
    
    // Support struct to hold user credentials
    struct UserCredentials {
        var fullName: String
        var email: String
        var password: String
    }
    
    // Outlets
    @IBOutlet weak var signUpContainer: UIView!
    @IBOutlet weak var backButton: UIButton!
    @IBOutlet weak var signUpButton: UIButton!
    @IBOutlet weak var tryAgainLabel: UILabel!
    @IBOutlet weak var nameTextField: UITextField!
    @IBOutlet weak var mailTextField: UITextField!
    @IBOutlet weak var pswTextField: UITextField!
    @IBOutlet weak var createAccountLabel: UILabel!
    @IBOutlet weak var spinner: UIActivityIndicatorView!
    
    
    /* VIEW CONTROLLER LIFECYCLE */
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        //Hide Autolayout Warning
        UserDefaults.standard.setValue(false, forKey:"_UIConstraintBasedLayoutLogUnsatisfiable")
        
        // Set delegates
        nameTextField?.delegate = self
        mailTextField?.delegate = self
        pswTextField?.delegate = self
        
        // Hide the spinner
        spinner?.isHidden = true
        
        // Call UI methods
        adjustUI()
        self.hideKeyboardWhenTappedAround()
    }
    
    
    /* UI METHODS */
    
    func textFieldShouldReturn(_ scoreText: UITextField) -> Bool {
        self.view.endEditing(true)
        return false
    }
    
    // Checks if the email entered in the textfield is in a valid format
    func isValidEmailAddress(_ email: String) -> Bool {
        let emailRegEx = "(?:[a-z0-9!#$%\\&'*+/=?\\^_`{|}~-]+(?:\\.[a-z0-9!#$%\\&'*+/=?\\^_`{|}"+"~-]+)*|\"(?:[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x21\\x23-\\x5b\\x5d-\\"+"x7f]|\\\\[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f])*\")@(?:(?:[a-z0-9](?:[a-"+"z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\\[(?:(?:25[0-5"+"]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-"+"9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x21"+"-\\x5a\\x53-\\x7f]|\\\\[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f])+)\\])"
        
        let emailTest = NSPredicate(format:"SELF MATCHES[c] %@", emailRegEx)
        return emailTest.evaluate(with: email)
    }
    
    // Displays an error message according to the text field edited
    func textFieldDidEndEditing(_ textField: UITextField) {
        
        if textField == mailTextField {
            if !isValidEmailAddress(mailTextField.text!) {
                tryAgainLabel.text = "Invalid email address."
                tryAgainLabel.isHidden = false
            } else {
                tryAgainLabel.isHidden = true
            }
        }
        else if textField == pswTextField {
            if pswTextField.text!.count < 10 {
                tryAgainLabel.text = "Password must be at least 10 characters."
                tryAgainLabel.isHidden = false
            } else {
                tryAgainLabel.isHidden = true
            }
        }
        else if textField == nameTextField {
            if nameTextField.text!.count == 0 {
                tryAgainLabel.text = "Full name must not be empty."
                tryAgainLabel.isHidden = false
            } else {
                tryAgainLabel.isHidden = true
            }
        }
    }
    
    func textFieldDidBeginEditing(_ textField: UITextField) {
        tryAgainLabel.isHidden = true
    }
    
    func adjustUI() {
        // Container
        signUpContainer.layer.cornerRadius = 10
        let border = CALayer()
        border.frame = CGRect.init(x: 0, y: createAccountLabel.frame.origin.y*2 + createAccountLabel.frame.height, width: signUpContainer.frame.width, height: 2)
        border.backgroundColor = UIColor.white.cgColor
        view.layoutIfNeeded()
        signUpContainer.layer.addSublayer(border)
        
        // Buttons
        signUpButton.layer.cornerRadius = 10
        signUpButton.clipsToBounds = true
        backButton.layer.cornerRadius = 10
        backButton.clipsToBounds = true
        
        // Text Fields
        nameTextField.borderStyle = UITextBorderStyle.roundedRect
        mailTextField.borderStyle = UITextBorderStyle.roundedRect
        pswTextField.borderStyle = UITextBorderStyle.roundedRect
    }
    
    
    /* SIGN UP */
    
    // Calls the createUser method when the sign up button is tapped
    @IBAction func signUpAction(_ sender: UIButton) {
        createUser(with: UserCredentials(fullName: nameTextField.text ?? "", email: mailTextField.text ?? "", password: pswTextField.text ?? ""), completion: signUp)
    }
    
    /**
     Performs a segue to the Start View Controller if the HTTP response from the server
     was equal to 200.
     
     - Parameter statusCode: the HTTP status code returned from the POST request.
     - Parameter userId: the unique id of the new user created returned from the server.
     */
    func signUp(_ statusCode: Int, _ userId: Int?) {
        if statusCode == 200 {
            self.performSegue(withIdentifier: "signUpSegue", sender: self)
        }
    }
    
    /**
     Create a new user from credentials and send it to the server.
     
     - Parameter credentials: user credentials
        - name,
        - email
        - password

     - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
     - Parameter statusCode: the HTTP status code returned from the POST request.
     - Parameter newUserId: the unique id of the new user created returned from the server.
     */
    func createUser(with credentials: UserCredentials, completion: @escaping (_ statusCode: Int, _ newUserId: Int?) -> Void) {
        
        // Set the spinner active
        spinner?.isHidden = false
        spinner?.startAnimating()
        tryAgainLabel?.isHidden = true
        
        let headers: HTTPHeaders = [
            "Content-Type": "application/json",
            "Accept": "application/json"
        ]
        
        let parameters: Parameters = [
            "name": credentials.fullName,
            "email": credentials.email,
            "password": credentials.password
        ]
        
        var newUserId: Int?
        
        Alamofire.request(serverName + "api/v1/user", method: .post, parameters: parameters, encoding: JSONEncoding.default, headers: headers).responseJSON { response in
            
            // Check if the HTTP request was effectively performed
            if let statusCode = response.response?.statusCode {
            
                // Success
                if statusCode == 200 {
                    if let json = response.result.value {
                        print("JSON: \(json)")
                    }
                    // Decoding
                    do {
                        let userData = try JSONDecoder().decode(RawUser.self, from: response.data!)
                        newUserId = userData.user.id
                    } catch { print("Error parsing the user creation response")}
                    
                    // Callback execution
                    completion(statusCode, newUserId)
                }
                // Error
                else {
                    if let json = response.result.value {
                        print("JSON: \(json)")
                        do {
                            let errorMessage = try JSONDecoder().decode(ErrorMessage.self, from: response.data!)
                            
                            // Display alert
                            var alertMessage: String = ""
                            
                            if let name = errorMessage.errors.name?[0] {
                                alertMessage += name + "\n"
                            }
                            if let email = errorMessage.errors.email?[0] {
                                alertMessage += email + "\n"
                            }
                            if let password = errorMessage.errors.password?[0] {
                                alertMessage += password
                            }
                        
                            let alertController = UIAlertController(title: errorMessage.message, message: alertMessage, preferredStyle: .alert)
                            
                            let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                            alertController.addAction(defaultAction)
                            
                            self.present(alertController, animated: true, completion: nil)
                            
                        } catch { print("Error parsing json signup")}
                    }
                    // Callback execution
                    completion(statusCode, nil)
                }
            }
            
            // If the HTTP request failed
            else {
                
                // Present alert view controller
                let alertController = UIAlertController(title: "Connection error", message: "Please check your internet connection and retry.", preferredStyle: .alert)
                
                let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                alertController.addAction(defaultAction)
                
                self.present(alertController, animated: true, completion: nil)
            }
            
            // Stop the spinner
            self.spinner?.isHidden = true
            self.spinner?.startAnimating()
        }
    }

}
