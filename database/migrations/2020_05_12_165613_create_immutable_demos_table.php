<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImmutableDemosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('immutable_demos', function (Blueprint $table) {
            $table->increments('id')->index();
            $table->string('hash')->nullable()->index();
            $table->string('previousHash')->nullable()->index();
            $table->string('dummy')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("immutable_demos");
    }
}
