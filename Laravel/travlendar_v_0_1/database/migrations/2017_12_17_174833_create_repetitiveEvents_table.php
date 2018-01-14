<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRepetitiveEventsTable extends Migration {

	public function up()
	{
		Schema::create('repetitiveEvents', function(Blueprint $table) {
			$table->bigInteger('eventId')->unique()->unsigned();
			$table->uuid('groupId');
			$table->bigInteger('until')->unsigned();
			$table->string('frequency');
		});
	}

	public function down()
	{
		Schema::drop('repetitiveEvents');
	}
}