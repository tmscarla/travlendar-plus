//
//  LogTests.swift
//  TravTests
//
//  Created by Tommaso Scarlatti on 29/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import XCTest
@testable import Trav

class LogTests: XCTestCase {
    
    var credentials: LogViewController.UserCredentials?
    var lvc: LogViewController?
    var r: SWRevealViewController?
    
    override func setUp() {
        super.setUp()
        User.token = nil
        r = SWRevealViewController()
        lvc = LogViewController()
        credentials = LogViewController.UserCredentials(username: "scarlattitommaso@gmail.com", password: "travlendar")
    }
    
    override func tearDown() {
        super.tearDown()
    }
    
    // Wait for the request to complete, then check if the status code of
    // the HTTP request is 200
    func testLogIn() {
        
        weak var ex: XCTestExpectation? = self.expectation(description: "Expecting a value of statusCode == 200")
        
        lvc!.authenticate(with: credentials!) { (statusCode, data) in
            XCTAssertEqual(statusCode, 200)
            XCTAssertNotNil(User.token)
            ex?.fulfill()
        }
        
        waitForExpectations(timeout: 10) { (error) in
            if let error = error {
                XCTFail("error: \(error)")
            }
        }
    }

}
