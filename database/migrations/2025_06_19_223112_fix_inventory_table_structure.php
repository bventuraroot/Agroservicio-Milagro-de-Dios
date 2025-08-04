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
            // Eliminar campos que no deberían estar en la tabla inventory
            if (Schema::hasColumn('inventory', 'sku')) {
                $table->dropColumn('sku');
            }
            if (Schema::hasColumn('inventory', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('inventory', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('inventory', 'price')) {
                $table->dropColumn('price');
            }
            if (Schema::hasColumn('inventory', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('inventory', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('inventory', 'provider_id')) {
                $table->dropForeign(['provider_id']);
                $table->dropColumn('provider_id');
            }
            if (Schema::hasColumn('inventory', 'active')) {
                $table->dropColumn('active');
            }
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
            // Recrear los campos si es necesario hacer rollback
            $table->string('sku')->unique()->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('category')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('provider_id')->nullable()->constrained('providers')->onDelete('restrict');
            $table->boolean('active')->default(true);
        });
    }
};
