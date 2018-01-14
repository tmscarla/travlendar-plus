//
//  MenuViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 07/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import SwiftDate

class MenuViewController: UIViewController, UITableViewDelegate, UITableViewDataSource {
    
    var menuOptions = [String]()
    var iconeImages = [UIImage]()

    @IBOutlet weak var containerView: UIView!
    @IBOutlet weak var profilePicture: UIImageView!
    @IBOutlet weak var fullNameLabel: UILabel!
    @IBOutlet weak var emailLabel: UILabel!
    
    
    /* VIEW CONTROLLER LIFECYCLE */
    
    override func viewDidLoad() {
        super.viewDidLoad()

        fillMenu()
        setProfilePicture()
        setBottomBorder()
    }
    
    override func viewDidAppear(_ animated: Bool) {
        super.viewDidAppear(true)
        
        profilePicture.image = User.profilePicture ?? #imageLiteral(resourceName: "man-3")
        fullNameLabel.text = User.name ?? ""
        emailLabel.text = User.email ?? ""
    }
    
    
    /* UI METHODS */
    
    private func setProfilePicture() {
        profilePicture.layer.borderColor = UIColor.white.cgColor
        profilePicture.layer.borderWidth = 2
        profilePicture.layer.cornerRadius = profilePicture.frame.size.width / 2
        profilePicture.layer.masksToBounds = false
        profilePicture.clipsToBounds = true
    }
    
    private func setBottomBorder() {
        let border = CALayer()
        border.frame = CGRect.init(x: 0, y: containerView.frame.height - 5, width: containerView.frame.width, height: 5)
        border.backgroundColor = UIColor(red: 135/255, green: 207/255, blue: 39/255, alpha: 1).cgColor
        view.layoutIfNeeded()
        containerView.layer.addSublayer(border)
    }
    
    private func fillMenu() {
        menuOptions = ["Today", "Calendar", "Settings", "Preferences", "Credits", "Logout"]
        iconeImages = [UIImage(named: "today")!,
                       UIImage(named: "calendar")!,
                       UIImage(named: "settings")!,
                       UIImage(named: "preferences")!,
                       UIImage(named: "credits")!,
                       UIImage(named: "logout")!]
    }
    
    /* TABLE VIEW DELEGATE */
    
    func tableView(_ tableView: UITableView, numberOfRowsInSection section: Int) -> Int {
        return menuOptions.count
    }
    
    func tableView(_ tableView: UITableView, cellForRowAt indexPath: IndexPath) -> UITableViewCell {
        let cell = tableView.dequeueReusableCell(withIdentifier: "MenuTableViewCell") as! MenuTableViewCell
        
        cell.imgIcon.image = iconeImages[indexPath.row]
        cell.menuOptionName.text! = menuOptions[indexPath.row]
        cell.sizeThatFits(CGSize(width: 0, height: 44))
        
        let backgroundView = UIView()
        backgroundView.backgroundColor = UIColor(red: 242/255, green: 244/255, blue: 244/255, alpha: 1)
        cell.selectedBackgroundView = backgroundView
        
        return cell
    }
    
    // Determine where to go when a menu option is selected
    func tableView(_ tableView: UITableView, didSelectRowAt indexPath: IndexPath) {
        let revealViewController: SWRevealViewController = self.revealViewController()
        
        let cell: MenuTableViewCell = tableView.cellForRow(at: indexPath) as! MenuTableViewCell
        
        // Today
        if cell.menuOptionName.text! == "Today" {
            let mainStoryboard: UIStoryboard = UIStoryboard(name: "Main", bundle: nil)
            let destinationController = mainStoryboard.instantiateViewController(withIdentifier: "DayViewController") as! DayViewController
            Day.dayDate = Date().startOfDay
           
            let newFrontViewController = UINavigationController.init(rootViewController: destinationController)
            revealViewController.pushFrontViewController(newFrontViewController, animated: true)
        }
        
        // Calendar
        else if cell.menuOptionName.text! == "Calendar" {
            let mainStoryboard: UIStoryboard = UIStoryboard(name: "Main", bundle: nil)
            let destinationController = mainStoryboard.instantiateViewController(withIdentifier: "CalendarViewController") as! CalendarViewController
            let newFrontViewController = UINavigationController.init(rootViewController: destinationController)
            revealViewController.pushFrontViewController(newFrontViewController, animated: true)
        }
        
        // Settings
        else if cell.menuOptionName.text! == "Settings" {
            let mainStoryboard: UIStoryboard = UIStoryboard(name: "Main", bundle: nil)
            let destinationController = mainStoryboard.instantiateViewController(withIdentifier: "SettingsViewController") as! SettingsViewController
            let newFrontViewController = UINavigationController.init(rootViewController: destinationController)
            revealViewController.pushFrontViewController(newFrontViewController, animated: true)
        }
        
        // Preferences
        else if cell.menuOptionName.text! == "Preferences" {
            let mainStoryboard: UIStoryboard = UIStoryboard(name: "Main", bundle: nil)
            let destinationController = mainStoryboard.instantiateViewController(withIdentifier: "PreferencesViewController") as! PreferencesViewController
            let newFrontViewController = UINavigationController.init(rootViewController: destinationController)
            revealViewController.pushFrontViewController(newFrontViewController, animated: true)
        }
        
        // Credits
        else if cell.menuOptionName.text! == "Credits" {
            let mainStoryboard: UIStoryboard = UIStoryboard(name: "Main", bundle: nil)
            let destinationController = mainStoryboard.instantiateViewController(withIdentifier: "CreditsViewController") as! CreditsViewController
            let newFrontViewController = UINavigationController.init(rootViewController: destinationController)
            revealViewController.pushFrontViewController(newFrontViewController, animated: true)
        }
         
        // Logout
        else if cell.menuOptionName.text! == "Logout" {
            // Delete all the data stored in the device
            deleteUserData()
            self.performSegue(withIdentifier: "logoutSegue", sender: self)
        }
    }

}
