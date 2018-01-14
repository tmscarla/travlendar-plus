<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration {

	public function up()
	{
		Schema::create('users', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->json('preferences')->nullable();
			$table->string('email')->unique();
			$table->string('name');
			$table->boolean('active')->default(false);
			$table->string('password');
		});
	}

	public function down()
	{
		Schema::drop('users');
	}
}