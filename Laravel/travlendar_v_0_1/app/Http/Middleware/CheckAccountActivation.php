<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;

/**
*
* This middleware checks whether the account associated with the request is active
*
*/
class CheckAccountActivation {

    protected $except = [

    ];

    /**
    * Handles http requests.
    *
    * Checks the response to 
    * 
    * @param a Request object $request (Illuminate\Http\Request)
    *
    * @param a Closure object $next (App\Models\Event) which is the following handler of the request
    *
    * @return a Response object (Illuminate\Http\Response)
    *
    */
    public function handle($request, Closure $next){
        
        // Deactivate the middleware in a test setting
        if (env('APP_ENV') === 'testing') {
            return $next($request);
        }

        // Check if the User is active
        if(Auth::user() !== null && ! Auth::user()->active){
        	$message = "Account is not active";
    		return response()->json(array("message" => $message), 403);
        }

        // If User is active propagate the request
        return $next($request);

    }
}