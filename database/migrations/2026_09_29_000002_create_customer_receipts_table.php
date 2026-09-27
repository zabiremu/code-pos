<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Due collected from a customer. One receipt is spread over their unpaid
     * bills oldest-first as ordinary payments rows (payments.customer_receipt_id),
     * so each bill's balance stays the single source of truth.
     */
    public function up(): void
    {
        Schema::create('customer_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 30)->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('receipt_date');
            $table->decimal('amount', 14, 2);
            $table->string('method', 20)->default('cash');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('customer_receipt_id')->nullable()->after('bill_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_receipt_id'));
        Schema::dropIfExists('customer_receipts');
    }
};
