//
//  SettingsViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 07/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import CropViewController
import Alamofire

class SettingsViewController: UITableViewController {
    
    // Outlets
    @IBOutlet weak var menuButton: UIBarButtonItem!
    @IBOutlet weak var profilePicture: UIImageView!
    @IBOutlet weak var fullNameTextField: UITextField!
    
    let imagePicker = UIImagePickerController()
    
    // New name to be sent to the server
    var newName: String?
    
    
    /* VIEW CONTROLLER LIFECYCLE */
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        // Hide Autolayout Warning
        UserDefaults.standard.setValue(false, forKey:"_UIConstraintBasedLayoutLogUnsatisfiable")
        
        // Call UI methods
        addNavBarImage()
        sideMenu()
        adjustProfilePicture()
        createSaveButton()
        
        self.hideKeyboardWhenTappedAround()
        
        fullNameTextField?.text = User.name ?? ""
        fullNameTextField?.delegate = self
    }

    
    /* UI METHODS */
    
    func adjustProfilePicture() {
        profilePicture?.image = User.profilePicture
        profilePicture?.layer.borderColor = UIColor(red: 242/255, green: 244/255, blue: 244/255, alpha: 1).cgColor
        profilePicture?.layer.borderWidth = 2
        profilePicture?.layer.cornerRadius = profilePicture.frame.size.width / 2
        profilePicture?.clipsToBounds = true
    }
    
    func sideMenu() {
        if revealViewController() != nil {
            menuButton.target = revealViewController()
            menuButton.action = #selector(SWRevealViewController.revealToggle(_:))
            revealViewController().rearViewRevealWidth = 275
        }
    }
    
    func addNavBarImage() {
        let imageView = UIImageView(image: #imageLiteral(resourceName: "t_plus"))
        imageView.frame = CGRect(x: 0, y: 0, width: 40, height: 40)
        imageView.contentMode = .scaleAspectFit
        navigationItem.titleView = imageView
    }
    
    @IBAction func changeProfilePicture(_ sender: Any) {
        imagePicker.delegate = self
        imagePicker.sourceType = .photoLibrary

        present(imagePicker, animated:true, completion: nil)
    }
    
    func createSaveButton() {
        self.navigationController?.navigationBar.tintColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 1)
        self.navigationItem.rightBarButtonItem = UIBarButtonItem(title: "Save", style: .done, target: self, action: #selector(save))
        self.navigationItem.rightBarButtonItem?.isEnabled = true
    }
    
    @objc private func save() {
        saveSettings(authenticateWith: (User.token ?? "")) { _ in }
    }
    
    
    /* HTTP REQUESTS */
    
    /**
     Send the user settings to the server and update the user credentials.
     
     - Parameter token: the Travlendar+ user token.
     
     - Parameter completion: callback function to be executed after the asynchronous HTTP request has done.
     - Parameter statusCode: the HTTP status code returned from the POST request.
     */
    @objc func saveSettings(authenticateWith token: String, completion: @escaping (_ statusCode: Int) -> Void) {
        let headers: HTTPHeaders = [
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        ]
        
        let parameters: Parameters = [
            "name": newName ?? " ",
        ]
        
        Alamofire.request(serverName + "api/v1/user/\(User.id!)", method: .put, parameters: parameters,  encoding: JSONEncoding.default, headers: headers).responseJSON { response in
            
            if let statusCode = response.response?.statusCode {
            
                // Success
                if statusCode == 200 {
                    print(response.result.value!)
                    
                    // Update user credentials
                    getUserCredentials(authenticateWith: token) { _,_ in }
                    
                    // Display alert controller
                    let alertController = UIAlertController(title: "Success", message: "Settings successfully updated!", preferredStyle: .alert)
                    
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
                      print(response.result.value!)
                    print("Error status code: \(response.response!.statusCode)")
                    let alertController = UIAlertController(title: "Error", message: "An error occurred while trying to update your settings. Please check your connection and retry.", preferredStyle: .alert)
                    
                    let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                    alertController.addAction(defaultAction)
                    
                    self.present(alertController, animated: true, completion: nil)
                }
                
                completion(statusCode)
            }
                
            // HTTP request failed
            else {
                // Display alert controller
                let alertController = UIAlertController(title: "Error", message: "An error occurred while trying to update your settings. Please check your connection and retry.", preferredStyle: .alert)
                
                let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
                alertController.addAction(defaultAction)
                
                self.present(alertController, animated: true, completion: nil)
            }
        }
        
    }
    

    /* TABLE VIEW DELEGATE */
    
    func tableView(tableView: UITableView, heightForHeaderInSection section: Int) -> CGFloat {
        return 50;
    }
    
    func tableView(tableView: UITableView, viewForHeaderInSection section: Int) -> UIView? {
        let headerView = UIView(frame: CGRect(x: 0, y: 0, width: self.tableView.frame.size.width, height: 50))
        let label = UILabel(frame: headerView.frame)
        label.text = "TESTING"
        label.textAlignment = NSTextAlignment.center
        headerView.addSubview(label)
        
        return headerView;
    }
    
    override func tableView(_ tableView: UITableView, didSelectRowAt indexPath: IndexPath) {
        if indexPath.section == 1 {
            let alertController = UIAlertController(title: "We are sorry", message: "This function is currently unavailable.", preferredStyle: .alert)
            
            let defaultAction = UIAlertAction(title: "OK", style: .default, handler: nil)
            alertController.addAction(defaultAction)
            
            self.present(alertController, animated: true, completion: nil)
        }
    }
}

/* NAVIGATION CONTROLLER DELEGATE */

extension SettingsViewController: UINavigationControllerDelegate, UIImagePickerControllerDelegate {
    func imagePickerControllerDidCancel(_ picker: UIImagePickerController) {
        dismiss(animated: true, completion: nil)
    }

    func imagePickerController(_ picker: UIImagePickerController, didFinishPickingMediaWithInfo info: [String : Any]) {
        if let selectedImage = info[UIImagePickerControllerOriginalImage] as? UIImage {
            // Set the crop controller
            let cropController = CropViewController(croppingStyle: .circular, image: selectedImage)
            cropController.delegate = self
            cropController.aspectRatioPreset = .presetSquare;
            cropController.aspectRatioLockEnabled = true
            cropController.resetAspectRatioEnabled = false
            cropController.aspectRatioPickerButtonHidden = true
            self.navigationController!.pushViewController(cropController, animated: true)
        }

        dismiss(animated: true, completion: nil)
        
    }

}


/* TEXT FIELD DELEGATE */

extension SettingsViewController: UITextFieldDelegate {
    func textFieldDidEndEditing(_ textField: UITextField) {
        newName = textField.text
        User.name = textField.text
    }
    
    func textFieldShouldReturn(_ textField: UITextField) -> Bool {
        textField.resignFirstResponder()
        return true
    }
}


/* CROP VIEW CONTROLLER */

// Crop the image to a circle
extension SettingsViewController: CropViewControllerDelegate {
    func cropViewController(_ cropViewController: CropViewController, didCropToImage image: UIImage, withRect cropRect: CGRect, angle: Int) {
        profilePicture.image = image
        User.profilePicture = image
        
        // Save image locally
        let imageData: NSData = UIImagePNGRepresentation(image)! as NSData
        UserDefaults.standard.set(imageData, forKey: "profilePicture")
        
        self.navigationController?.popViewController(animated: true)
    }
}

