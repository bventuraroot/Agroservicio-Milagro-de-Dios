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
        Schema::table('inventory', function (Blueprint $table) {
            $table->date('expiration_date')->nullable()->after('location');
            $table->string('batch_number')->nullable()->after('expiration_date');
            $table->integer('expiring_quantity')->default(0)->after('batch_number');
            $table->boolean('expiration_warning_sent')->default(false)->after('expiring_quantity');
            $table->date('last_expiration_check')->nullable()->after('expiration_warning_sent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropColumn([
                'expiration_date',
                'batch_number',
                'expiring_quantity',
                'expiration_warning_sent',
                'last_expiration_check'
            ]);
        });
    }
};
