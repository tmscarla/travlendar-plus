//
//  LogViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 06/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import Alamofire

// Extends any view controller to dismiss the keyboard touching anywhere
extension UIViewController {
    func hideKeyboardWhenTappedAround() {
        let tap: UITapGestureRecognizer = UITapGestureRecognizer(target: self, action: #selector(UIViewController.dismissKeyboard))
        tap.cancelsTouchesInView = false
        view.addGestureRecognizer(tap)
    }
    
    @objc func dismissKeyboard() {
        view.endEditing(true)
    }
}


class LogViewController: UIViewController, UITextFieldDelegate {
    
    // Access token received from the server
    struct Token: Decodable {
        var access_token: String
    }
    
    // Support struct to hold user credentials
    struct UserCredentials {
        var username: String
        var password: String
    }
    
    // Outlets
    @IBOutlet weak var username: UITextField!
    @IBOutlet weak var password: UITextField!
    @IBOutlet weak var signInButton: UIButton!
    @IBOutlet weak var signUpButton: UIButton!
    @IBOutlet weak var tryAgainLabel: UILabel!
    @IBOutlet weak var spinner: UIActivityIndicatorView!
    
    
    /* VIEW CONTROLLER LIFECYCLE */
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        //Hide Autolayout Warning
        UserDefaults.standard.setValue(false, forKey:"_UIConstraintBasedLayoutLogUnsatisfiable")
        
        // Set delegates
        username?.delegate = self
        password?.delegate = self
        
        // Hides the spinner
        spinner?.isHidden = true
        
        self.hideKeyboardWhenTappedAround()
        
        // Call UI methods
        adjustUI()
        assignbackground()
        
        //Reinitialize
        travelsOfSelectedEvent = []
        
    }
    
    override func viewDidAppear(_ animated: Bool) {
        // Check if the token is present to ignore the log in process
        if User.token != nil {
            getUserCredentials(authenticateWith: (User.token ?? "")) { _,_ in }
            self.performSegue(withIdentifier: "signInSegue", sender: self)
        } else {
            
        }
    }
    
    
    /* UI METHODS */
    
    func adjustUI() {
        // TextFields
        username.borderStyle = UITextBorderStyle.roundedRect
        password.borderStyle = UITextBorderStyle.roundedRect
        
        // Buttons
        signInButton.layer.cornerRadius = 10
        signInButton.clipsToBounds = true
        signUpButton.layer.cornerRadius = 10
        signUpButton.clipsToBounds = true
        
    }
    
    func assignbackground(){
        let background = UIImage(named: "background")
        
        var imageView : UIImageView!
        imageView = UIImageView(frame: view.bounds)
        imageView.contentMode =  UIViewContentMode.scaleAspectFill
        imageView.clipsToBounds = true
        imageView.image = background
        imageView.alpha = 0.5
        imageView.center = view.center
        view.addSubview(imageView)
        self.view.sendSubview(toBack: imageView)
    }
    
    func textFieldShouldReturn(_ scoreText: UITextField) -> Bool {
        self.view.endEditing(true)
        return false
    }

    func tryAgainIsHidden(_ value: Bool) {
        tryAgainLabel.isHidden = value
    }
    
    
    /* SIGN IN */
    
    @IBAction func signInAction(_ sender: UIButton) {
        authenticate(with: UserCredentials(username: username.text ?? "", password: password.text ?? ""), completion: signIn)
    }
    
    /**
     Performs a segue to the Sign Up View Controller if the HTTP response from the server was equal to 200.
     
     - Parameter statusCode: the HTTP status code returned from the POST request.
     - Parameter data: the data returned from the server.
     */
    func signIn(_ statusCode: Int, _ data: Data?) {
        if statusCode == 200 {
            getUserCredentials(authenticateWith: (User.token ?? "")) { _,_ in }
            self.performSegue(withIdentifier: "signInSegue", sender: self)
        }
    }
    
    
    /* HTTP REQUESTS */
    
    /**
     Send the user credentials to the server in order to retrieve the user
     token form the server. The token is needed to perform any further request.
     
     - Parameter userCredentials: user credentials
         - name,
         - email
         - password
     
     - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
     - Parameter statusCode: the HTTP status code returned from the POST request.
     - Parameter data: the data returned from the server if the request was successfully made.
     */
    func authenticate(with userCredentials: UserCredentials, completion: @escaping (_ statusCode: Int, _ data: Data?) -> Void) {
        
        // Set spinner active
        self.spinner?.isHidden = false
        self.spinner?.startAnimating()
        self.tryAgainLabel?.isHidden = true
        
        // Retrieve client secret
        let clientSecret = Bundle.main.object(forInfoDictionaryKey: "Travlendar Client Secret") as! String
        
        let headers: HTTPHeaders = [
            "Content-Type": "application/json",
        ]
        
        let parameters: Parameters = [
            "client_id": 2,
            "client_secret": clientSecret,
            "grant_type" : "password",
            "username": userCredentials.username,
            "password": userCredentials.password
        ]
        
        Alamofire.request(serverName + "oauth/token", method: .post, parameters: parameters, encoding: JSONEncoding.default, headers: headers).responseJSON { response in
            
            // Check if the HTTP request was effectively performed
            if let statusCode = response.response?.statusCode {
            
                // Success
                if statusCode == 200 {
                    if let json = response.result.value {
                        print("JSON: \(json)")
                        
                        // Decoding
                        do { let token = try JSONDecoder().decode(Token.self, from: response.data!)
                            User.token = token.access_token
                            
                        } catch {
                            print("Error parsing user authentication")
                        }
                    }
                }
                // Error
                else {
                    print("Error status code: \(statusCode)", response.result.value!)
                    self.tryAgainLabel.text = "Invalid username or password.\nTry Again."
                    self.tryAgainLabel?.isHidden = false
                }
                
                // Callback function
                completion(statusCode, response.data)
            }
            
            // If the request wasn't performed
            else {
                // Set the error label
                self.tryAgainLabel?.text = "Please check your internet connection\nand try again."
                self.tryAgainLabel?.isHidden = false
            }
            
            // Stop the spinner
            self.spinner?.isHidden = true
            self.spinner?.stopAnimating()
            
        }
    }

}
