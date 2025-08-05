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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique(); // Número de cotización
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Usuario que creó la cotización
            $table->date('quote_date'); // Fecha de la cotización
            $table->date('valid_until'); // Válida hasta
            $table->string('status')->default('pending'); // pending, approved, rejected, converted, expired
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('taxes', 10, 2)->default(0); // IVA
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('notes')->nullable(); // Notas adicionales
            $table->text('terms_conditions')->nullable(); // Términos y condiciones
            $table->string('payment_terms')->nullable(); // Términos de pago
            $table->string('delivery_time')->nullable(); // Tiempo de entrega
            $table->string('currency')->default('USD'); // Moneda
            $table->decimal('discount_percentage', 5, 2)->default(0); // Descuento en porcentaje
            $table->decimal('discount_amount', 10, 2)->default(0); // Descuento en monto
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
        Schema::dropIfExists('quotations');
    }
};
