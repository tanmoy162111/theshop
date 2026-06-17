<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theme_applications', function (Blueprint $table) {
            $table->id();
            $table->string('vertical');
            $table->boolean('demo_loaded')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('theme_applications'); }
};
