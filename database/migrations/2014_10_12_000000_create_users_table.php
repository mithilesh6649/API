<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->bigInteger('phone_number')->nullable();
            $table->tinyInteger('country_code')->nullable();
            $table->integer('gender')->comment('0:male 1:female')->nullable();
            $table->integer('user_type')->comment('0:ADMIN 1:USER 2:TRAINER')->default(0);
            $table->string('password');
            $table->boolean('phone_verified')->default(0)->comment('0|not verified  1|verified')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->boolean('email_verified')->default(0)->comment('0|not verified  1|verified')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->tinyInteger('wrong_attempt')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('push_notification')->default(0)->comment('0|disable  1|enable')->nullable();
            $table->boolean('email_notification')->default(0)->comment('0|disable  1|enable')->nullable();
            $table->string('fcm_token')->nullable();
            $table->string('device_type')->nullable();
             $table->tinyInteger('status')->default(1)->comment('1|Active  0|Inactive')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
