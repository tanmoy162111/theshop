<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theme_application_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_application_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('setting_type')->nullable();
            $table->text('prior_value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('theme_application_items'); }
};
