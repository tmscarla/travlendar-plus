<?php
namespace App\Http\Interfaces;

use Mail;
use App\Mail\MailVerification;

/**
*	Interface with the Mail Service
*/
class MailInterface {

	/**
    * Sends verification mail to the provided address.
    *
    * 
    * @param string $mailAddress the destination email address
    *
    * @param string $token activation token
    *
    * @return void
    *
    */
    static public function sendVerificationEmail($mailAddress, $token) {
        $email = new MailVerification($token);
        Mail::to($mailAddress)->send($email);
    }

}