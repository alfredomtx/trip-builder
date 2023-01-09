<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFlightsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->integer('number');
            $table->date('departure_date');
            $table->time('departure_time');
            $table->date('arrival_date');
            $table->time('arrival_time');
            $table->decimal('price', 6, 2);
            $table->timestamps();

            $table->foreignId('airline_id');
            $table->foreign('airline_id')->on('airlines')->references('id')->cascadeOnDelete();
            $table->foreignId('departure_airport_id');
            $table->foreign('departure_airport_id')->on('airports')->references('id')->cascadeOnDelete();
            $table->foreignId('arrival_airport_id');
            $table->foreign('arrival_airport_id')->on('airports')->references('id')->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flights');
    }
}
