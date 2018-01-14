//
//  CreditsViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 08/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit

class CreditsViewController: UIViewController {
    @IBOutlet weak var menuButton: UIBarButtonItem!
    
    override func viewDidLoad() {
        super.viewDidLoad()
        addNavBarImage()
        sideMenu()

        // Do any additional setup after loading the view.
    }

    override func didReceiveMemoryWarning() {
        super.didReceiveMemoryWarning()
        // Dispose of any resources that can be recreated.
    }
    
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

}
