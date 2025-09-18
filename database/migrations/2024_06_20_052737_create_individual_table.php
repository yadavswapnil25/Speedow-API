<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('individual', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('uid');
            $table->string('background');
            $table->text('categories')->nullable();
            $table->text('address')->nullable();
            $table->string('lat')->nullable();
            $table->string('lng')->nullable();
            $table->string('cid')->nullable();
            $table->text('about')->nullable();
            $table->double('rating', 10, 2)->default(0);
            $table->double('fee_start', 10, 2)->default(0);
            $table->integer('total_rating');
            $table->text('website')->nullable();
            $table->text('timing')->nullable();
            $table->text('images')->nullable();
            $table->text('zipcode')->nullable();
            $table->tinyInteger('verified')->default(1);
            $table->tinyInteger('in_home')->default(1);
            $table->tinyInteger('popular')->default(1);
            $table->tinyInteger('have_shop')->default(1);
            $table->text('extra_field')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('individual');
    }
};
