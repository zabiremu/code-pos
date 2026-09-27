<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Money paid to suppliers. Optionally tied to one GRN; untied payments
     * are advances / payments on account. What's owed to a supplier is
     * GRN totals - return totals - payments (see Supplier::balance()).
     */
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no', 30)->nullable()->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('grn_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('method', 20)->default('cash'); // cash, bank, mobile, cheque, other
            $table->string('reference', 100)->nullable();  // cheque no., transaction ID
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
