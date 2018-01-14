<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFlexibleEventsTable extends Migration {

	public function up()
	{
		Schema::create('flexibleEvents', function(Blueprint $table) {
			$table->bigInteger('eventId')->unique()->unsigned();
			$table->bigInteger('lowerBound')->unsigned();
			$table->bigInteger('upperBound')->unsigned();
			$table->bigInteger('duration')->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('flexibleEvents');
	}
}