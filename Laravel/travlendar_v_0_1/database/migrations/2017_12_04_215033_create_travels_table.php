<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTravelsTable extends Migration {

	public function up()
	{
		Schema::create('travels', function(Blueprint $table) {
			$table->bigInteger('eventId')->unsigned();
			$table->string('mean');
			$table->bigInteger('duration')->unsigned();
			$table->bigInteger('bookingId')->unsigned()->nullable();
			$table->decimal('startLongitude', 9,6);
			$table->decimal('startLatitude', 9,6);
			$table->bigInteger('distance');
		});
	}

	public function down()
	{
		Schema::drop('travels');
	}
}