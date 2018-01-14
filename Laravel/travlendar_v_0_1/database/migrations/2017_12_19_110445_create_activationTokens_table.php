<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateActivationTokensTable extends Migration {

	public function up()
	{
		Schema::create('activationTokens', function(Blueprint $table) {
			$table->uuid('token')->primary();
			$table->bigInteger('userId')->unique()->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('activationTokens');
	}
}