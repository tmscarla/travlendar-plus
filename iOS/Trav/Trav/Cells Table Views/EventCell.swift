//
//  EventCell.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 09/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit

class EventCell: UITableViewCell {

    @IBOutlet weak var leftView: UIView!
    @IBOutlet weak var startDate: UILabel!
    @IBOutlet weak var endDate: UILabel!
    @IBOutlet weak var titleLabel: UILabel!
    @IBOutlet weak var descriptionLabel: UILabel!
    @IBOutlet weak var travelIcon: UIImageView!
    @IBOutlet weak var bookingIcon: UIImageView!
    @IBOutlet weak var bookingPrice: UILabel!
    @IBOutlet weak var travelDuration: UILabel!
    
    
    var separatorColor: UIColor = UIColor(red: 135/255, green: 207/255, blue: 39/255, alpha: 1) {
        didSet {
            separatorBorder.backgroundColor = separatorColor.cgColor
        }
    }
    
    let separatorBorder = CALayer()
    
    override func awakeFromNib() {
        super.awakeFromNib()
        addSeparatorBar()
    }

    override func setSelected(_ selected: Bool, animated: Bool) {
        super.setSelected(selected, animated: animated)
    }

    func addSeparatorBar() {
        separatorBorder.frame = CGRect.init(x: leftView.frame.width - 5, y: 2.5, width: 5, height: leftView.frame.height - 5)
        separatorBorder.backgroundColor = separatorColor.cgColor
        
        leftView.layoutIfNeeded()
    leftView.layer.addSublayer(separatorBorder)
    }
    
}


