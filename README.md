<p align="center">
  <img height="100" src="https://github.com/tmscarla/Travlendar/blob/master/Images/logo_blue.png">
</p>
<p align="center">
  <img width="450" src="https://github.com/tmscarla/Travlendar/blob/master/Images/logo_inline.png">
</p>


# Travlendar+
Travlendar+ is a calendar based application whose goal is to help registered users organize their day by scheduling their appointment and providing the best solutions in terms of mobility.

## Project Structure
The repository is organized in this way:
- **Installation Instructions:** everything you need to know to setup the application.
- **Documentation:** all the project related documentation.
- **iOS:** front-end source code.
- **Laravel:** back-end source code.
- **Images:** all the images created to build up the UI and create the logo.


# Documentation
Each stage of the application development was fully documented. In the Documentation folder you can find:
- **RASD**: Requirements Analysis Specification Document.
- **DD**: Design Document.
- **ITD**: Implementation and Testing Document.
- **ATD**: Acceptance Test Document (of another related project developed by a different team).

# Architecture
Travlendar+ is designed with a multitier architecture.
A multitier architecture is a client–server architecture in which presentation, application processing, and data management functions are physically separated. The most common multi-tier architecture is the three-tier, composed by the following three layers: **Presentation tier**, **Domain logic tier**, **Data storage tier**. We added a fourth tier, the **Web tier**, in order to handle requests from web users of a future web application.

<img src="https://github.com/tmscarla/Travlendar/blob/master/Documentation/DD/img/Overview.png">

# Implementation

## Front-end (iOS)
For the presentation (front end) layer, we chose to develop a native iOS app for iPhones written in Swift. We took this choice mainly for the widespread diffusion of the iOS operative system and for the several advantages of a native application.

### Frameworks
We used several external frameworks to accomplish what we stated in the RASD and DD document in a more elegant and secure way. All the frame- works were installed with CocoaPods, a dependency manager for Swift and Objective-C Cocoa projects.

* **SWRevealViewController**: to add a slide-out side bar menu.
* **JTAppleCalendar**: to build a calendar from scratch and customize
it for our needs.
* **SwiftDate**: to manage Dates and Timezones in Swift.
* **Alamofire**: to make elegant network HTTP requests.
* **CropViewController**: to perform basic manipulation on UIImage objects; specifically cropping and some basic rotations.
* **KeychainSwift**: helper functions for saving text in Keychain securely.
* **UberRides**: to integrate the Uber Rides API into our iOS app.
* **Quick/Nimble**: to help making asynchronous integration tests in a proper way.

<p align="center">
<img height="350" src="https://github.com/tmscarla/Travlendar/blob/master/Documentation/ITD/img/login.png">
<img height="350" src="https://github.com/tmscarla/Travlendar/blob/master/Documentation/ITD/img/signup.png">
<img height="350" src="https://github.com/tmscarla/Travlendar/blob/master/Documentation/ITD/img/calendar.png">
<img height="350" src="https://github.com/tmscarla/Travlendar/blob/master/Documentation/ITD/img/menu.png">
</p>
<p align="center">
<img height="350" src="https://github.com/tmscarla/Travlendar/blob/master/Documentation/ITD/img/preferences.png">
<img height="350" src="https://github.com/tmscarla/Travlendar/blob/master/Documentation/ITD/img/day.png">
<img height="350" src="https://github.com/tmscarla/Travlendar/blob/master/Documentation/ITD/img/event.png">
<img height="350" src="https://github.com/tmscarla/Travlendar/blob/master/Documentation/ITD/img/uber.png">
</p>


## Back-end (Laravel)
For the Business Logic Layer we chose to implement our solution using the Laravel Framework.
Laravel is a free, open-source PHP web framework based on MVC, with an active and growing community behind it.

### Routing
Laravel provides routes management out of the box. It allows easy binding of the routes with the methods responsible for handling incoming requests. It also allows to create routes for the managing of CRUD resources bind- ing each HTTP method to the specified endpoint and to the corresponding methods.

### Authentication
Laravel only offers session based authentication out of the box.
For the development of APIs there is the need of a different type of authen- tication since there is no lasting session, for this reason we use an external package called Passport, that easily integrates with Laravel.
Laravel Passport is an OAuth2 server and API authentication package that provides token based authentication.

## Database Structure
The implemented structure of the Database and therefore of the various Models is depicted below.

<img src="https://github.com/tmscarla/Travlendar/blob/master/Documentation/ITD/img/DBModel.png">

## External APIs
The external services are used in the application to retrieve information concerning available travel options and related travel times. This is done through the use of the Google Maps Directions, Mapbox and Uber APIs.
The application also allows the user to book one of the available taxi option, provided by Uber service.
Furthermore, an external Mail service is used to send an email to the new registered User, in order to confirm the subscription to the application. Summing up, this is the list of all API used in the project:

* Google Maps API
* Mapbox API
* Uber API

## Authors

* **[Guglielmo Menchetti](https://github.com/gmenchetti)**
* **[Lorenzo Norcini](https://github.com/LorenzoNorcini)**
* **[Tommaso Scarlatti](https://github.com/tmscarla)**

## License
