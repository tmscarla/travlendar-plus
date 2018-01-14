//
//  CalendarCell.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 08/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import JTAppleCalendar

class CalendarCell: JTAppleCell {
    @IBOutlet weak var dateLabel: UILabel!
    @IBOutlet weak var selectedView: UIView!
    @IBOutlet weak var eventView: UIView!
    
    override func awakeFromNib() {
        super.awakeFromNib()
        layoutIfNeeded()
        //eventView.layer.borderWidth = 1
        eventView.layer.borderColor = lookupColor["blue"]?.cgColor
        eventView.layer.masksToBounds = false
        eventView.layer.cornerRadius = eventView.frame.size.width / 2
        eventView.clipsToBounds = true
    }
}
