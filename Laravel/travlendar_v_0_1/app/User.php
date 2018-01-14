<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Models\Event;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    public function build($name, $email, $password){
      $this->name = $name;
      $this->email = $email;
      $this->password = bcrypt($password);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'preferences', 'active'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function events(){
        return $this->hasMany('App\Models\Event', 'userId');
    }
}
