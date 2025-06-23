<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. approval_flows
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // フロー名
            $table->text('description')->nullable();
            $table->json('flow'); // Drawflowなど
            $table->string('version')->nullable();
            $table->timestamps();
        });

        // 2. approval_flow_tasks
        Schema::create('approval_flow_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('approval_flows')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('ref_id'); // 多態ターゲットのID
            $table->string('system_type');        // 多態ターゲットのクラス名
            $table->string('status')->default('未承認');
            $table->boolean('is_complete')->default(false);
            $table->unsignedBigInteger('node_id')->nullable(); // 現在のノードID
            $table->text('comment')->nullable(); // 申請者のコメント
            $table->text('msg')->nullable();     // 通知メッセージ
            $table->string('link')->nullable();  // リンク先URL
            $table->timestamps();
        });

        // 3. approval_flow_histories
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
        Schema::dropIfExists('approval_flow_tasks');
        Schema::dropIfExists('approval_flows');
    }
};
