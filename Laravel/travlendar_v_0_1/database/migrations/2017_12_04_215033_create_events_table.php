<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEventsTable extends Migration {

	public function up()
	{
		Schema::create('events', function(Blueprint $table) {
			$table->increments('id');
			$table->bigInteger('userId')->unsigned();
			$table->string('title');
			$table->bigInteger('start')->unsigned();
			$table->bigInteger('end')->unsigned();
			$table->string('category')->nullable();
			$table->string('description')->nullable();
            $table->decimal('longitude', 9,6);
            $table->decimal('latitude', 9,6);
        });
	}

	public function down()
	{
		Schema::drop('events');
	}
}