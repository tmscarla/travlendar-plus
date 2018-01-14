//
//  TravelCell.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 13/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit

class TravelCell: UITableViewCell {
    @IBOutlet weak var mean: UILabel!
    @IBOutlet weak var duration: UILabel!
    @IBOutlet weak var distance: UILabel!
    @IBOutlet weak var icon: UIImageView!
    @IBOutlet weak var checkIcon: UIImageView!
    @IBOutlet weak var ecoIcon: UIImageView!
    
    override func awakeFromNib() {
        super.awakeFromNib()
        // Initialization code
    }

    override func setSelected(_ selected: Bool, animated: Bool) {
        super.setSelected(selected, animated: animated)

        // Configure the view for the selected state
    }

}
