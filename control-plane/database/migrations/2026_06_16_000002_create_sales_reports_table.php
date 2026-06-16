<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross_sales', 20, 2)->default(0);
            $table->unsignedInteger('order_count')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('received_at');
            $table->timestamps();
            $table->unique(['client_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void { Schema::dropIfExists('sales_reports'); }
};
