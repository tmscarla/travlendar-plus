<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
*
* The routes defined in this group are assigned to the auth middleware and the active middleware.
* This means that this routes are accessible only to authenticated Users with an active account
*
*/
Route::group(['middleware' => ['auth:api', 'active']], function () {

  /*
  This route provides the binding with the User Controller of all the necessary methods for CRUD management of the User resources. The naming convention for such methods is available on the Laravel documentation or on the Implementation and Testing Document associated with this source code.
  The 'store' route is available without credentials since it is used to register a new User.
  The 'destroy' route is defined below since its parameters are slightly different from the default ones.
  */
  Route::resource('/v1/user', 'api\v1\UserController', ['except' => ['store', 'destroy']]);

  /*
  Binding of the method destroy (resource deletion) of the User Controller to the api/v1/user
  */
  Route::delete('/v1/user', 'api\v1\UserController@destroy');
  
  /*
  Binding of the method setPreferences of the User Controller to the api/v1/preferences
  */
  Route::put('/v1/preferences', 'api\v1\UserController@setPreferences');

  /*
  This route provides the binding with the Schedule Controller of all the necessary methods for CRUD management of the Event resources. The naming convention for such methods is available on the Laravel documentation or on the Implementation and Testing Document associated with this source code.
  */
  Route::resource('/v1/event', 'api\v1\ScheduleController');
  
  /*
  Binding of the method getDaysWithEvents of the Schedule Controller to the api/v1/days
  */
  Route::get('/v1/days', 'api\v1\ScheduleController@getDaysWithEvents');

  /*
  Binding of the method generateEvent of the Event Generation Controller to the api/v1/generator
  */
  Route::post('/v1/generator', 'api\v1\EventGenerationController@generateEvent');

  /*
  This route provides the binding with the Schedule Controller of all the necessary methods for CRUD management of the Booking resources. The naming convention for such methods is available on the Laravel documentation or on the Implementation and Testing Document associated with this source code.
  'store' and 'destroy' are excluded and the bindings are defined below.
  */
  Route::resource('/v1/book', 'api\v1\BookingController', ['except' => ['store', 'destroy']]);

  /*
  Binding of the method createBooking of the Booking Controller to the api/v1/book
  */
  Route::post('/v1/book', 'api\v1\BookingController@createBooking');

  /*
  Binding of the method getAvailableBooking of the Booking Controller to the api/v1/available
  */
  Route::get('/v1/available', 'api\v1\BookingController@getAvailableBooking');

  /*
  Binding of the method getCurrentBooking of the Booking Controller to the api/v1/book/current/booking
  */
  Route::get('/v1/book/current/booking', 'api\v1\BookingController@getCurrentBookingRequest');

  /*
   Binding of the method destroy (resource deletion) of the Booking Controller to the api/v1/book
   */
  Route::delete('/v1/book', 'api\v1\BookingController@destroy');

    /*
   Binding of the method deleteBookingTest (resource deletion) of the Booking Controller
    to the api/v1/book/current/booking. Is mainly use for testing scopes.
   */
  Route::delete('/v1/book/current/delete', 'api\v1\BookingController@deleteBookingTest');

});

/* 
The UserController 'store' route is available without credentials since it is used to register a new User
*/
Route::resource('/v1/user', 'api\v1\UserController', ['only' => ['store']]);