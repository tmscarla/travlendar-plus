<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* ActivationToken class
* 
* This class defines the model associated 'activationTokens' table
* This model stores information about account activation tokens.
*
*/
class ActivationToken extends Model {

    /**
    * @var string $table associates a table with the model class 
    */
    protected $table = 'activationTokens';

    /** 
    * @var string $primaryKey specifies a different primary key 
    * when such key is different from the default identifier 'id'
    */
    protected $primaryKey = 'token';

    /** 
    * @var boolean $timestamps whether to include timestamps in the database table
    */
    public $timestamps = false;

    /**
    * ActivationToken object builder
    *
    * @param string $token activation token
    * @param integer $userId identifier of the user
    *
    * @return void
    */
    public function build($token, $userId){
    	$this->token = $token;
    	$this->userId = $userId;
    }

    /**
    * Defines a relation of belonging to the User Model
    * (ActivationToken) userId -> (User) id
    */
    public function user(){
        return $this->belongsTo('App\User', 'userId');
    }

}