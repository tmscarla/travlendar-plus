<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use App\User;


/*
    This class contains tests concerning the creation and management of user related information, preferences and credentials.
*/

class UserTest extends TestCase{

    /*
        Each test is wrapped in a database transaction and therefore is indipendent.
    */
    use DatabaseTransactions;

    /*
        The following functions are used to populate the database in order to test different scenarios and they are not considered as tests.
    */

    /* START HELPER FUNCTIONS */

    private function createTestUser($name, $email, $password){

        $payload = [
            'name' => $name,
            'email' => $email,
            'password' => $password
        ];

        $endpoint = 'api/v1/user';
        $method = 'POST';
        
        return $this->call($method, $endpoint,  $payload ,[],[], ['HTTP_Accept' => 'application/json'], [] );

    }

    /* END HELPER FUNCTIONS */

    /*
        The following functions are the tests performed.
    */

    /* START TEST FUNCTIONS */

    /*
     Tests user creation with correct and valid data
    */
    public function testUserCreationSuccesful(){	

        $username = 'test_user';
        $email = 'test@test.test';
        $password = 'testpassword';

        $response = $this->createTestUser($username, $email , $password);

        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("User creation successful", $content->message);
        $this->assertEquals($username, $content->user->name);
        $this->assertEquals($email, $content->user->email);
        $this->assertDatabaseHas('users', ['name' => $username, 'email' => $email]);

    }

    /*
     Tests user creation when the provided mail is already present in the database
    */
    public function testUserCreationDuplicateMail(){

        $username = 'test_user';
        $email = 'test@test.test';
        $password = 'testpassword';

        $this->createTestUser($username, $email , $password);

        $this->assertDatabaseHas('users', ['email' => $email]);

        $response = $this->createTestUser($username, $email , $password);

        $content = json_decode($response->getContent());

        $this->assertEquals(409, $response->status());
        $this->assertEquals("Data not correct, possible mail duplicate", $content->message);

    }

    /*
     Tests user creation when the password is too short (less than 10 characters)
    */
    public function testUserCreationShortPassword(){

        $username = 'test_user';
        $email = 'test@test.test';
        $password = 'test';

        $response = $this->createTestUser($username, $email , $password);

        $content = json_decode($response->getContent());

        $this->assertEquals(422, $response->status());
        $this->assertEquals("The given data was invalid.", $content->message);
        $this->assertEquals("The password must be at least 10 characters.", $content->errors->password[0]);
        $this->assertDatabaseMissing('users', ['name' => $username, 'email' => $email]);

    }

    /*
     Tests username change
    */
    public function testChangeNameSuccess(){

        $email = "testUser@test.test";
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api');

        $newName = "testUserTmp";
        $payload = [
            "name" => $newName
        ];

        $endpoint = 'api/v1/user/'.$user->id;
        $method = 'PUT';

        $response = $this->call($method, $endpoint, $payload ,[],[], ['HTTP_Accept' => 'application/json'], [] );
        
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("User successfully modified", $content->message);
        $this->assertDatabaseMissing('users', ['name' => $user->name, 'email' => $email, 'id' => $user->id]);
        $this->assertDatabaseHas('users', ['name' => $newName, 'email' => $email, 'id' => $user->id]);

    }

    /*
     Tests preferences change with valid data
    */
    public function testChangePreferencesSuccess(){

        $email = "testUser@test.test";
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api');

        $preferences = "{\"transit\":{\"active\":false,\"maxDistance\":10000},\"uber\":{\"active\":true,\"maxDistance\":10000},\"walking\":{\"active\":true,\"maxDistance\":10000},\"driving\":{\"active\":true,\"maxDistance\":10000},\"cycling\":{\"active\":true,\"maxDistance\":10000}}";

        $payload = json_decode($preferences, true);

        $endpoint = 'api/v1/preferences';
        $method = 'PUT';
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ['HTTP_Accept' => 'application/json'], [] );
        
        $content = json_decode($response->getContent());

        $this->assertEquals(200, $response->status());
        $this->assertEquals("Preferences successfully modified", $content->message);
        $this->assertEquals($preferences, User::where('email', $email)->first()->preferences);
        $this->assertNotEquals($preferences, $user->preferences);

    }

    /*
     Tests preferences change with invalid data
    */
    public function testChangePreferencesInvalidData(){

        $email = "testUser@test.test";
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api');

        $preferences = "[]";

        $payload = json_decode($preferences, true);

        $endpoint = 'api/v1/preferences';
        $method = 'PUT';
        
        $response = $this->call($method, $endpoint, $payload ,[],[], ['HTTP_Accept' => 'application/json'], [] );

        $content = json_decode($response->getContent());

        $this->assertEquals(422, $response->status());
        $this->assertEquals("The given data was invalid.", $content->message);
        $this->assertNotEquals($preferences, User::where('email', $email)->first()->preferences);

    }

    /*
     Tests the request of User information
    */
    public function testGetUserInformation(){
        $email = "testUser@test.test";
        $user = User::where('email', $email)->first();

        $this->actingAs($user, 'api');

        $endpoint = 'api/v1/user';
        $method = 'GET';

        $response = $this->call($method, $endpoint, [],[],[], ['HTTP_Accept' => 'application/json'], [] );

        $this->assertEquals(200, $response->status());

    }

    /* END TEST FUNCTIONS */

}
