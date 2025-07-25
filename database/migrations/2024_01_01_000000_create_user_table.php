<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('telegram_id')->unique();
            $table->boolean('subscription')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('users');
    }
};
