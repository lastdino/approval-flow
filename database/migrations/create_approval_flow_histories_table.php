<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_flow_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_task_id')->constrained('approval_flow_tasks')->onDelete('cascade');
            $table->unsignedBigInteger('node_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // 申請、承認、却下など
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flow_histories');
    }
};
