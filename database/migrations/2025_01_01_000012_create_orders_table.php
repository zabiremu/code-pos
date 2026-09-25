<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete(); // null for takeaway
            $table->foreignId('waiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('guest_count')->default(1);
            $table->enum('type', ['dine_in', 'takeaway', 'delivery'])->default('dine_in');
            $table->enum('status', ['open', 'sent', 'served', 'billed', 'closed', 'cancelled'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
