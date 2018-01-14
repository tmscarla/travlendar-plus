<?php 

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\ActivationToken;
use Illuminate\Http\Request;

/**
* ActivationController class
*
* This class is a controller class that provides methods for handling the routes/endpoints 
* associated with the account activation.
* More information about such routes are in the routes/web.php file.
*/

class ActivationController extends Controller {

  /**
  * Handles the request for the account activation.
  *
  * This method handles a GET request sent to the /v1/activate endpoint and activates the account 
  * associated with the provided activation token.
  * 
  *
  * @param a Request object $request (Illuminate\Http\Request) containing as url parameters
  *    {
  *    		'token'     => (string) activation token. Required.
  *    }
  *
  * @return the web page associated with the 'activation' tag
  */
  public function activateAccount(Request $request){
  	try{
	  	$validatedData = $request->validate([
	      'token' => 'required|string',
	    ]);

	  	$token = $request->get('token');
	   	$user = ActivationToken::find($token)->user;
	    $user->active = true;
	    $user->save();
	    $success = true;
    } catch (QueryException $e) {
    	$success = false;
    } finally {
    	return view('activation', ['success' => $success]);
    }
  }

}