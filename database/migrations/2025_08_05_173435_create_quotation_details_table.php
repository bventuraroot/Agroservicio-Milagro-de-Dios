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
        Schema::create('quotation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity'); // Cantidad
            $table->decimal('unit_price', 10, 2); // Precio unitario
            $table->decimal('discount_percentage', 5, 2)->default(0); // Descuento por producto
            $table->decimal('discount_amount', 10, 2)->default(0); // Descuento en monto
            $table->decimal('subtotal', 10, 2); // Subtotal sin impuestos
            $table->decimal('tax_rate', 5, 2)->default(13.00); // Tasa de impuesto (IVA)
            $table->decimal('tax_amount', 10, 2)->default(0); // Monto del impuesto
            $table->decimal('total', 10, 2); // Total con impuestos
            $table->text('description')->nullable(); // Descripción adicional del producto
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
        Schema::dropIfExists('quotation_details');
    }
};
