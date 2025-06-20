<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // フロー名
            $table->text('description')->nullable();
            $table->json('flow'); // Drawflowなど
            $table->string('version')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flows');
    }
};
