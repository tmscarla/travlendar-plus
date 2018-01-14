//
//  DayTests.swift
//  TravTests
//
//  Created by Tommaso Scarlatti on 30/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import XCTest
import Nimble
import Quick

@testable import Trav

class DayTests: XCTestCase {
    
    struct EventToTest: Decodable {
        var events: [Event]
        var message: String
    }
    
    var dvc: DayViewController?
    var addvc: AddEventViewController?
    
    var dateFormatter: DateFormatter = DateFormatter()
    var date: Date?
    
    var addedEvents: [Event] = []
    var eventTimeBounds: [(Int, Int)]?
    
    let token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjFjYTY2ZjNkMjcyYjM3YjgzOTc5NjlkOGEyNGQxNDIxMWU1YzM4MjdjYTMxMzEzNGNhYmVlNWNlOGE4YzIzMmZkZDIxNWYwMGIwZTczZGQ0In0.eyJhdWQiOiIyIiwianRpIjoiMWNhNjZmM2QyNzJiMzdiODM5Nzk2OWQ4YTI0ZDE0MjExZTVjMzgyN2NhMzEzMTM0Y2FiZWU1Y2U4YThjMjMyZmRkMjE1ZjAwYjBlNzNkZDQiLCJpYXQiOjE1MTQ4OTEyOTAsIm5iZiI6MTUxNDg5MTI5MCwiZXhwIjoxNTQ2NDI3MjkwLCJzdWIiOiI5MjQiLCJzY29wZXMiOltdfQ.z68dU8bwmGU3mAP1iJcQm3Jad2s3l-Y8i5mNX7WcS0Iu4Uh53NACq-8GAm6nMfHRiNOk712l5b7sxP6UoaUoZVB0A8bbjctLV-iY47Y46fSuV6T7a4jCz0xjbb8HJJzfQFwp8H7NxSMxmltiwwTAN9qPNKEmyMWPElk2r7EaXqm_Ycmz2qVx2JgsadyoEZaHbmpsqf1jK8x2WU6jpENbRZGnyh2JZ_NNNi-fSCJW_lDYVJx5v5fHexKiyMSQvMJo7BMK9YgqwqWGjBjZ29KhMwrxYgpWrhb2VRfKNwNN11idOD-c8_KXlaFzNOAYahMyKxkzvDdcA8uavxcp2I-1L0GkWprRJvTna5eGnooBI9xRWonhVrLVgR8cpIkpO3v-H-ql6-6Vy4a1UYaCyF6lLmSRQyG5XdUt_yfdBjsDbk4TVO26b-UR6f6WozQBroIxjwPVtE7skRt56elGOvCOagy-lhkBxFf9QdvcPmtjRH3JPfAL59_lHGdoWNzo-Hft49FN36do6A07vWZgtmxNfblFKLvo6SkOtvqf9nFbuHxjyWvocBTu__2uau8FBc7VJzfRkBFrcBRdUA2U8-Vvnwof5ojLano0iAO5MlIHPmP3QFW4LTq_Do8FNYMZ_RDl9Cjtn5l3LzVUx-kBVBhBOn_bqLNS8U-Eb_eCQQ7Ukew"
    
    override func setUp() {
        super.setUp()
        dvc = DayViewController()
        addvc = AddEventViewController()
        
        dateFormatter.dateFormat = "yyyy-MM-dd"
        date = dateFormatter.date(from: "2018-12-01")
        print("DATA:", date!)
        
        // Events: 01-12-2018 13.00-12.30, 02-01-2018 15.00-15.30
        eventTimeBounds = [(1543662000, 1543663800), (1543672800, 1543674600)]
        
        let eventsGroup = DispatchGroup()
        
        let ex = expectation(description: "null")
        
        eventsGroup.enter()
        eventsGroup.enter()
        
        for etb in eventTimeBounds! {
            
            RawEventToBeAdded.title = "Test Title"
            RawEventToBeAdded.description = "Test Description"
            RawEventToBeAdded.start = etb.0
            RawEventToBeAdded.end = etb.1
            
            addvc!.addEventToServer(authenticateWith: token) { (statusCode, data) in
                if let json = data {
                    do {
                        let decodedEvent = try JSONDecoder().decode(EventToTest.self, from: json)
                        self.addedEvents.append(decodedEvent.events[0])
                    } catch {}
                }
                eventsGroup.leave()
            }
        }
        
        eventsGroup.notify(queue: .main, execute: {
            print("Finished all creation requests.")
            print(Date().timeIntervalSince1970)
            ex.fulfill()
        })
        
        waitForExpectations(timeout: 10) { (error) in
            if let error = error {
                XCTFail("error: \(error)")
            }
        }
        
        
    }
    
    
    /// In order to make the tests nullable,
    override func tearDown() {
        let ex = expectation(description: "null")
        
        for index in 0 ..< addedEvents.count {
            print(addedEvents[index].id)
            dvc?.deleteEvent(authenticateWith: token, event: (addedEvents[index].id)!) { _ in
                ex.fulfill()
            }
        }
        
        waitForExpectations(timeout: 10) { (error) in
            if let error = error {
                XCTFail("error: \(error)")
            }
        }
        
        super.tearDown()
    }
    
    
    /// Test that the number of events for the day: 01/12/2018 is 2
    func testEventsNumber() {

        let ex = expectation(description: "Expecting a number of events == 2 for the date: 01/12/2018")
        
        dvc?.getEvents(authenticateWith: token, of: date!) { (statusCode, data) in
            XCTAssertEqual(Day.eventsOfTheDay?.count, 2)
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

