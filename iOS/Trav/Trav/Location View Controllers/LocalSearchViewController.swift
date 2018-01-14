//
//  LocalSearchViewController.swift
//  Trav
//
//  Created by Tommaso Scarlatti on 12/12/2017.
//  Copyright © 2017 Tommaso Scarlatti. All rights reserved.
//

import UIKit
import MapKit

protocol HandleMapSearch {
    func dropPinZoomIn(placemark: MKPlacemark)
}

protocol HandlePlaceSelected {
    func passPosition(place: MKPlacemark)
    func passStartTravelPosition(place: MKPlacemark)
}

class LocalSearchViewController: UIViewController {
    
    var selectedPin:MKPlacemark? = nil
    var delegate: HandlePlaceSelected?
    var isStartTravelPositionRequested: Bool = true {
        didSet {
        }
    }
    
    let locationManager = CLLocationManager()

    @IBOutlet weak var mapView: MKMapView!
    var resultSearchController:UISearchController? = nil
    
    override func viewDidLoad() {
        super.viewDidLoad()
        
        // Add toolbar
        addToolBar()
        
        locationManager.delegate = self
        locationManager.desiredAccuracy = kCLLocationAccuracyBest
        locationManager.requestWhenInUseAuthorization()
        locationManager.requestLocation()

        let locationSearchTable = storyboard!.instantiateViewController(withIdentifier: "LocationSearchTableViewController") as! LocationSearchTableViewController
        resultSearchController = UISearchController(searchResultsController: locationSearchTable)
        resultSearchController?.searchResultsUpdater = locationSearchTable as UISearchResultsUpdating
        
        let searchBar = resultSearchController!.searchBar
        searchBar.sizeToFit()
        searchBar.placeholder = "Search for places"
        navigationItem.titleView = resultSearchController?.searchBar
        resultSearchController?.hidesNavigationBarDuringPresentation = false
        resultSearchController?.dimsBackgroundDuringPresentation = false
        definesPresentationContext = true
        
        locationSearchTable.mapView = mapView
        
        locationSearchTable.handleMapSearchDelegate = self
    }

    override func didReceiveMemoryWarning() {
        super.didReceiveMemoryWarning()
        // Dispose of any resources that can be recreated.
    }
    
    func addToolBar() {
    self.navigationController?.isToolbarHidden = false
        
        var items = [UIBarButtonItem]()
        
        let doneButton = UIBarButtonItem(barButtonSystemItem: .done, target: self, action: #selector(doneSegue))
        doneButton.tintColor = UIColor(red: 32/255, green: 151/255, blue: 207/255, alpha: 1)
        items.append(doneButton)
        doneButton.isEnabled = false
        
        self.toolbarItems = items
    }
    
    @objc func doneSegue() {
        if !isStartTravelPositionRequested {
            self.delegate?.passPosition(place: selectedPin!)
        } else {
            self.delegate?.passStartTravelPosition(place: selectedPin!)
        }
        self.navigationController?.popViewController(animated: true)
    }
    
}

extension LocalSearchViewController : CLLocationManagerDelegate {
    private func locationManager(manager: CLLocationManager, didChangeAuthorizationStatus status: CLAuthorizationStatus) {
        if status == .authorizedWhenInUse {
            locationManager.requestLocation()
        }
    }
    
    func locationManager(_ manager: CLLocationManager, didUpdateLocations locations: [CLLocation]) {
        if let location = locations.first {
            let span = MKCoordinateSpanMake(0.05, 0.05)
            let region = MKCoordinateRegion(center: location.coordinate, span: span)
            mapView.setRegion(region, animated: true)
        }
    }
    
    func locationManager(_ manager: CLLocationManager, didFailWithError error: Error) {
        print("error:: \(error)")
    }
}

extension LocalSearchViewController: HandleMapSearch {
    func dropPinZoomIn(placemark: MKPlacemark) {
        // Cache the pin
        selectedPin = placemark
        
        // Clear existing pins
        mapView.removeAnnotations(mapView.annotations)
        
        // Add annotation
        let annotation = MKPointAnnotation()
        annotation.coordinate = placemark.coordinate
        annotation.title = placemark.name
        if let city = placemark.locality,
            let state = placemark.administrativeArea {
            annotation.subtitle = "\(city) \(state)"
        }
        mapView.addAnnotation(annotation)
        let span = MKCoordinateSpanMake(0.05, 0.05)
        let region = MKCoordinateRegionMake(placemark.coordinate, span)
        mapView.setRegion(region, animated: true)
        
        // Enable done button
        self.toolbarItems?.first?.isEnabled = true
    }
}

extension LocalSearchViewController : MKMapViewDelegate {
    func mapView(_ mapView: MKMapView, viewFor annotation: MKAnnotation) -> MKAnnotationView?{
        if annotation is MKUserLocation {
            // Return nil so map view draws "blue dot" for standard user location
            return nil
        }
        let reuseId = "pin"
        var pinView = mapView.dequeueReusableAnnotationView(withIdentifier: reuseId) as? MKPinAnnotationView
        pinView = MKPinAnnotationView(annotation: annotation, reuseIdentifier: reuseId)
        pinView?.pinTintColor = UIColor.red
        pinView?.canShowCallout = true
        let smallSquare = CGSize(width: 30, height: 30)
        let button = UIButton(frame: CGRect(origin: CGPoint.zero, size: smallSquare))
        button.setBackgroundImage(UIImage(named: "car"), for: .normal)
        button.addTarget(self, action: Selector(("getDirections")), for: .touchUpInside)
        pinView?.leftCalloutAccessoryView = button
        return pinView
    }
}

