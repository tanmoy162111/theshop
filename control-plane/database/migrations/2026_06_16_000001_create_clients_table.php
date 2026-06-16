<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('contact_email');
            $table->string('primary_domain')->unique();
            $table->enum('status', ['pending','active','warning','locked_admin','maintenance','rejected'])
                  ->default('pending');
            $table->enum('commission_type', ['percent','per_order'])->default('percent');
            $table->decimal('commission_rate', 20, 2)->default(0);
            $table->string('token');
            $table->string('app_version')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('last_report_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('clients'); }
};
