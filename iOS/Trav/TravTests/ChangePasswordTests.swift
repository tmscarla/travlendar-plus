//
//  ChangePasswordTests.swift
//  TravTests
//
//  Created by Tommaso Scarlatti on 05/01/2018.
//  Copyright © 2018 Tommaso Scarlatti. All rights reserved.
//

import XCTest

@testable import Trav

class ChangePasswordTests: XCTestCase {
    
        let oldPsw = "travlendar"
        let newPsw = "travlendar"
    
        let token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjFjYTY2ZjNkMjcyYjM3YjgzOTc5NjlkOGEyNGQxNDIxMWU1YzM4MjdjYTMxMzEzNGNhYmVlNWNlOGE4YzIzMmZkZDIxNWYwMGIwZTczZGQ0In0.eyJhdWQiOiIyIiwianRpIjoiMWNhNjZmM2QyNzJiMzdiODM5Nzk2OWQ4YTI0ZDE0MjExZTVjMzgyN2NhMzEzMTM0Y2FiZWU1Y2U4YThjMjMyZmRkMjE1ZjAwYjBlNzNkZDQiLCJpYXQiOjE1MTQ4OTEyOTAsIm5iZiI6MTUxNDg5MTI5MCwiZXhwIjoxNTQ2NDI3MjkwLCJzdWIiOiI5MjQiLCJzY29wZXMiOltdfQ.z68dU8bwmGU3mAP1iJcQm3Jad2s3l-Y8i5mNX7WcS0Iu4Uh53NACq-8GAm6nMfHRiNOk712l5b7sxP6UoaUoZVB0A8bbjctLV-iY47Y46fSuV6T7a4jCz0xjbb8HJJzfQFwp8H7NxSMxmltiwwTAN9qPNKEmyMWPElk2r7EaXqm_Ycmz2qVx2JgsadyoEZaHbmpsqf1jK8x2WU6jpENbRZGnyh2JZ_NNNi-fSCJW_lDYVJx5v5fHexKiyMSQvMJo7BMK9YgqwqWGjBjZ29KhMwrxYgpWrhb2VRfKNwNN11idOD-c8_KXlaFzNOAYahMyKxkzvDdcA8uavxcp2I-1L0GkWprRJvTna5eGnooBI9xRWonhVrLVgR8cpIkpO3v-H-ql6-6Vy4a1UYaCyF6lLmSRQyG5XdUt_yfdBjsDbk4TVO26b-UR6f6WozQBroIxjwPVtE7skRt56elGOvCOagy-lhkBxFf9QdvcPmtjRH3JPfAL59_lHGdoWNzo-Hft49FN36do6A07vWZgtmxNfblFKLvo6SkOtvqf9nFbuHxjyWvocBTu__2uau8FBc7VJzfRkBFrcBRdUA2U8-Vvnwof5ojLano0iAO5MlIHPmP3QFW4LTq_Do8FNYMZ_RDl9Cjtn5l3LzVUx-kBVBhBOn_bqLNS8U-Eb_eCQQ7Ukew"
    
    var changePswVC: ChangePasswordViewController?
    
    override func setUp() {
        super.setUp()
        
        changePswVC = ChangePasswordViewController()
    }
    
    override func tearDown() {
        super.tearDown()
    }
    
    func testSetNewPassword() {
        let ex = expectation(description: "Expecting status code == 200")
        
        changePswVC?.changePasswordAndSendToServer(authenticateWith: token, userId: 924, oldPsw: oldPsw, newPsw: newPsw) { (statusCode, data) in
            XCTAssertEqual(statusCode, 200)
            ex.fulfill()
        }
        
        waitForExpectations(timeout: 10) { (error) in
            if let error = error {
                XCTFail("error: \(error)")
            }
        }
    }

}
