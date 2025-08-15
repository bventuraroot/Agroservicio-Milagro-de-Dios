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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_expiration')->default(false)->after('description');
            $table->integer('expiration_days')->nullable()->after('has_expiration');
            $table->string('expiration_type')->default('days')->after('expiration_days'); // days, months, years
            $table->text('expiration_notes')->nullable()->after('expiration_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'has_expiration',
                'expiration_days',
                'expiration_type',
                'expiration_notes'
            ]);
        });
    }
};
