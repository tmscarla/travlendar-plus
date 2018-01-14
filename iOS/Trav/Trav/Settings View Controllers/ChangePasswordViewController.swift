//
//  ChangePasswordViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 20/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import Alamofire

class ChangePasswordViewController: UIViewController {
    
    // Outlets
    @IBOutlet weak var password: UITextField!
    @IBOutlet weak var newPassword: UITextField!
    
    
    /* VIEW CONTROLLER LIFECYCLE */
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        self.hideKeyboardWhenTappedAround()
    }
    
    
    /* CHANGE PASSWORD */
    
    /**
     Send the user preferences to the server and save them if the request was successfully completed.
     
     - Parameter token: the Travlendar+ user token.
     
     - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
     - Parameter statusCode: the HTTP status code returned from the PUT request.
     */
    @IBAction func changePassword(_ sender: UIButton) {
        changePasswordAndSendToServer(authenticateWith: (User.token ?? ""), userId: (User.id ?? 0), oldPsw: password.text!, newPsw: newPassword.text!) { _,_ in
            
            // Once the password is changed, reload user credentials
            getUserCredentials(authenticateWith: (User.token ?? "")) { _,_ in }
        }
    }
    
    /* HTTP REQUESTS */
    
    func changePasswordAndSendToServer(authenticateWith token: String, userId: Int, oldPsw: String, newPsw: String, completion: @escaping (_ statusCode: Int, _ data: Data?) -> Void) {
        let headers: HTTPHeaders = [
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        ]
        
        let parameters: Parameters = [
            "oldPassword": oldPsw,
            "newPassword": newPsw
        ]
        
        Alamofire.request(serverName + "api/v1/user/\(userId)", method: .put, parameters: parameters,  encoding: JSONEncoding.default, headers: headers).responseJSON { response in
            
            if let statusCode = response.response?.statusCode {
                
                // Success
                if statusCode == 200 {
                    if let json = response.result.value {
                        print("JSON: \(json)")
                    }
                    // Display alert controller
                    let alertController = UIAlertController(title: "Success", message: "Password successfully changed!", preferredStyle: .alert)
                    
                    let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                    alertController.addAction(defaultAction)
                    
                    self.present(alertController, animated: true, completion: nil)
                    
                    self.password?.text = ""
                    self.newPassword?.text = ""
                    
                }
                // Error
                else {
                    // Display alert controller
                    print("Error status code: \(response.response!.statusCode)")
                    print(response.result.value!)
                    let alertController = UIAlertController(title: "Error", message: "An error occurred while trying to change your password. Please check your connection and retry.", preferredStyle: .alert)
                    
                    let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                    alertController.addAction(defaultAction)
                    
                    self.present(alertController, animated: true, completion: nil)
                }
                
                // Callback function
                completion(statusCode, response.data)
            }
        }
    }
    

}
