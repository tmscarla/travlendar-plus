<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBookingsTable extends Migration {

	public function up()
	{
		Schema::create('bookings', function(Blueprint $table) {
			$table->increments('id');
			$table->string('service');
			$table->json('bookingInfo');
		});
	}

	public function down()
	{
		Schema::drop('bookings');
	}
}