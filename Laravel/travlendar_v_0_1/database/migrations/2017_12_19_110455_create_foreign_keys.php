<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;

class CreateForeignKeys extends Migration {

	public function up()
	{
		Schema::table('events', function(Blueprint $table) {
			$table->foreign('userId')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('repetitiveEvents', function(Blueprint $table) {
			$table->foreign('eventId')->references('id')->on('events')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('travels', function(Blueprint $table) {
			$table->foreign('eventId')->references('id')->on('events')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('travels', function(Blueprint $table) {
			$table->foreign('bookingId')->references('id')->on('bookings')
						->onDelete('set null')
						->onUpdate('cascade');
		});
		Schema::table('flexibleEvents', function(Blueprint $table) {
			$table->foreign('eventId')->references('id')->on('events')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
		Schema::table('activationTokens', function(Blueprint $table) {
			$table->foreign('userId')->references('id')->on('users')
						->onDelete('cascade')
						->onUpdate('cascade');
		});
	}

	public function down()
	{
		Schema::table('events', function(Blueprint $table) {
			$table->dropForeign('events_userid_foreign');
		});
		Schema::table('repetitiveEvents', function(Blueprint $table) {
			$table->dropForeign('repetitiveevents_eventid_foreign');
		});
		Schema::table('travels', function(Blueprint $table) {
			$table->dropForeign('travels_eventid_foreign');
		});
		Schema::table('travels', function(Blueprint $table) {
			$table->dropForeign('travels_bookingid_foreign');
		});
		Schema::table('flexibleEvents', function(Blueprint $table) {
			$table->dropForeign('flexibleevents_eventid_foreign');
		});
		Schema::table('activationTokens', function(Blueprint $table) {
			$table->dropForeign('activationtokens_userid_foreign');
		});
	}
}