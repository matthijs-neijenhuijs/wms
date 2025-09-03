<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(false);
            $table->string('email');
            $table->string('vat_number')->nullable()->unique();
            $table->string('coc_number')->nullable()->unique();
            $table->string('debtor_number')->nullable()->unique();
            $table->string('iban_number')->nullable()->unique();
            $table->text('comments')->nullable();
            $table->string('company')->nullable();
            $table->integer('delivery_client_address_id')->unsigned()->nullable();
            $table->integer('bill_client_address_id')->unsigned()->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->timestamps();
        });

		Schema::create('client_addresses', function(Blueprint $table) {		
		 	$table->increments('id');
            $table->foreignId('client_id')->constrained('clients')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->string('company')->nullable();
			$table->enum('gender', array('male', 'female'));
            $table->string('initials')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('street')->nullable();
            $table->bigInteger('housenumber')->nullable();
            $table->string('housenumber_suffix')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
		});

		Schema::table('clients', function(Blueprint $table) {
       		$table->foreign('delivery_client_address_id')->references('id')->on('client_addresses')->onDelete('set null');
       		$table->foreign('bill_client_address_id')->references('id')->on('client_addresses')->onDelete('set null');
		});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
