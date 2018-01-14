//
//  BookingCell.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 02/01/2018.
//  Copyright © 2018 Tommaso Scarlatti. All rights reserved.
//

import UIKit

class BookingCell: UITableViewCell {
    
    // Outlets
    @IBOutlet weak var uberType: UILabel!
    @IBOutlet weak var info: UILabel!
    @IBOutlet weak var price: UILabel!
    @IBOutlet weak var checkIcon: UIImageView!
    
    override func awakeFromNib() {
        super.awakeFromNib()
        // Initialization code
    }

    override func setSelected(_ selected: Bool, animated: Bool) {
        super.setSelected(selected, animated: animated)

        // Configure the view for the selected state
    }

}
