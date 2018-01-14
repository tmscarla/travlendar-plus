<?php 

namespace App\Http\Controllers\api\v1;

use Illuminate\Database\QueryException;
use App\Http\Interfaces\MailInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\ActivationToken;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Http\Helpers\UUID;
use Carbon\Carbon;
use App\User;

/**
* UserController class
*
* This class is a controller that provides methods for handling the routes/endpoints 
* associated with User account management.
* More information about such routes are in the routes/api.php file.
* This controller provides methods to manage User account resources.
* It allows creation, modification and deletion of such resources.
*/
class UserController extends Controller {

  /**
  * Handles the request for profile information of the logged in User
  *
  * This method handles a GET request sent to the api/v1/user endpoint and provides the logged in User data
  *
  *
  * @param a Request object $request (Illuminate\Http\Request)
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message'   => (string) response message.
  *         'user'      => (User) a user object.
  *     }
  */
  public function index(Request $request){
    
    // Gets currently logged in User
    $user = auth()->user();

    // Converts stored preferences string to object
    $user->preferences = json_decode($user->preferences);

    $message = "Request successful";

    return response()->json(array("message" => $message, "user" => $user), 200);


  }

  /**
  * Handles the request for the creation of a new User account
  *
  * This method handles a POST request sent to the api/v1/user endpoint and allows the creation 
  * of a new User account, the account is created as inactive and an email is sent to the provided
  * email address with an activation link.
  * 
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *         'name'      => (string) The username. Required.
  *         'email'     => (string) a valid email address. Required.
  *         'password'  => (string) a password at least 10 characters long. Required.
  *      }
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message'      => (string) response message.
  *         'user'         => (User) an User object.
  *                           (if creation is succesful)
  *     }
  */
  public function store(Request $request){

      $validatedData = $request->validate([
        'name'         =>  'required|string',
        'email'        =>  'required|email',
        'password'     =>  'required|string|min:10'
      ]);

      $newUser = new User();

      $newUser->build(
        $request->input('name'),
        $request->input('email'),
        $request->input('password')
      );

      try{

        DB::beginTransaction();
        $newUser->save();

        $this->sendVerificationEmail($newUser);

        DB::commit();

        $message = "User creation successful";
        return response()->json(array("message" => $message, "user" => $newUser), 200);
        
      } catch (QueryException $e){

        DB::rollBack();

        $message = "Data not correct, possible mail duplicate";
        return response()->json(array("message" => $message), 409);
      }

  }

  /**
  * Calls the interface to the Mail Service to send the verification to the provided email address provided.
  * 
  * @param User $user
  *
  * @return void
  *
  */
  private function sendVerificationEmail(User $user){

    // Create UUID activation token
    $token = UUID::v4();
    
    // Creates new activationToken object
    $activationToken = new ActivationToken();

    $activationToken->build(
      $token,
      $user->id
    );

    // Stores the newly created resource in the database
    $activationToken->save();

    // Calls the Mail Service Interface
    MailInterface::sendVerificationEmail($user->email, $token);
  }

  /**
  * Checks if the password provided is the same as the one stored
  *
  * @param string $password
  *
  * @param user $user
  *
  * @return Boolean
  */
  private function validatePassword($password, User $user){

    return Hash::check($password, $user->password);

  }

  /**
  * Handles the request for the update of User account information
  *
  * This method handles a PUT request sent to the api/v1/user endpoint.
  * Updates the username if a new one is provided.
  * Updates the password if the current password provided is valid and a new valid one is also provided.
  * 
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *         'name'        => (string) the new username.
  *         'oldPassword' => (string) the currently valid password.
  *         'password'    => (string) the new password.
  *      }
  *
  * @param integer $id identifier of the User account
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message'      => (string) response message.
  *         'user'         => (User) an User object.
  *                           (if update is succesful)
  *     }
  */
  public function update(Request $request, $id){

    // Validate the request
    $validatedData = $request->validate([
      'name'            =>  'string',
      'oldPassword'     =>  'string|min:10',
      'newPassword'     =>  'string|min:10',
    ]);

    //Checks if the request comes from the correct id i.e. the same user that is to be modified
    if (auth()->id() != $id) {
      $message = "Provided User id and User token do not match";
      return response()->json(array("message" => $message), 403);
    }

    $user = User::find($id);

    // Updates password if both oldPassword and newPassword are provided and oldPassword and the current password coincide
    if ($request->has("oldPassword") && $request->has("newPassword")) {
      if ($this->validatePassword($request->get("oldPassword"), $user)) {
        $user->password = bcrypt($request->get("newPassword"));
      } else {
        $message = "The password provided does not match the current one";
        return response()->json(array("message" => $message), 403);
      }
    }

    if ($request->has("name")){
      $user->name = $request->get("name");
    }
    
    // Stores the updated resources
    $user->save();

    $user->preferences = json_decode($user->preferences);

    $message = "User successfully modified";
    return response()->json(array("message" => $message, "user" => $user), 200);

  }

  /**
  * Handles the request for the update of User travel preferences
  *
  * This method handles a PUT request sent to the api/v1/preferences endpoint.
  * The preferences specify whether a travel mean is active or not and what its maximum 
  * accepted distance is.
  * 
  * @param a Request object $request (Illuminate\Http\Request) containing as json parameters
  *      {
  *         'transit'   => {
  *                           'active'      => (boolean) whether the mean is active. Required
  *                           'maxDistance' => (integer) max distance accepted for the mean in meters. Required
  *                        }
  *         'walking'   => {
  *                           'active'      => (boolean) whether the mean is active. Required
  *                           'maxDistance' => (integer) max distance accepted for the mean in meters. Required
  *                        }
  *         'driving'   => {
  *                           'active'      => (boolean) whether the mean is active. Required
  *                           'maxDistance' => (integer) max distance accepted for the mean in meters. Required
  *                        }
  *         'cycling'   => {
  *                           'active'      => (boolean) whether the mean is active. Required
  *                           'maxDistance' => (integer) max distance accepted for the mean in meters. Required
  *                        }
  *         'uber'   =>    {
  *                           'active'      => (boolean) whether the mean is active. Required
  *                           'maxDistance' => (integer) max distance accepted for the mean in meters. Required
  *                        }
  *      }
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message'      => (string) response message.
  *         'user'         => (User) an User object.
  *     }
  */
  public function setPreferences(Request $request){

    $validatedData = $request->validate([
      'transit'             => 'required',
      'walking'             => 'required',
      'driving'             => 'required',
      'cycling'             => 'required',
      'uber'                => 'required',
      'transit.active'      => 'required|boolean',
      'walking.active'      => 'required|boolean',
      'driving.active'      => 'required|boolean',
      'cycling.active'      => 'required|boolean',
      'uber.active'         => 'required|boolean',
      'transit.maxDistance' => 'required|integer|min:0',
      'walking.maxDistance' => 'required|integer|min:0',
      'driving.maxDistance' => 'required|integer|min:0',
      'cycling.maxDistance' => 'required|integer|min:0',
      'uber.maxDistance'    => 'required|integer|min:0'
    ]);

    // Get currently logged in user
    $user = auth()->user();

    // Converts preferences json to object
    $newPreferences = json_encode($request->all());

    if($newPreferences === null){
      $message = "Payload not convertible to object";
      return response()->json(array("message" => $message, "user" => $user), 400);
    }

    $user->preferences = $newPreferences;

    // Stores the updated resource
    $user->save();

    $user->preferences = $request->all();

    $message = "Preferences successfully modified";
    return response()->json(array("message" => $message, "user" => $user), 200);

  }

  /**
  * Deletes the logged in User account and all related information.
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message'   => (string) response message.
  *     }
  */
  public function destroy(){

    try{

      auth()->user()->delete();
      $message = "User deletion success";
      return response()->json(array("message" => $message), 200);

    } catch (QueryException $e){

      $message = "User deletion failed";
      return response()->json(array("message" => $message), 500);
    }

  }

  /**
  * *Not Implemented* Just used to conform to laravel resource management structure
  *
  * @param int $id
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message' => (string) response message.
  *     }
  */
  public function show($id){

    $message = "use GET */*/user to obtain User information";
    return response()->json(array("message" => $message), 501);

  }

  /**
  * *Not Implemented* Just used to conform to laravel resource management structure
  *
  * @param int $id
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message' => (string) response message.
  *     }
  */
  public function edit($id, Request $request){

    $message = "use PUT */*/user/ID to edit User information or PUT */*/preferences to edit User preferences";
    return response()->json(array("message" => $message), 501);

  }

  /**
  * *Not Implemented* Just used to conform to laravel resource management structure
  *
  * @param int $id
  *
  * @return a Response object (Illuminate\Http\Response) containing the json representation of
  *     {
  *         'message' => (string) response message.
  *     }
  */
  public function create(){

    $message = "use POST */*/users/ to create a new User";
    return response()->json(array("message" => $message), 501);

  }

  
}
