<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flow_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('approval_flows')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('ref_id'); // 多態ターゲットのID
            $table->string('system_type'); // 多態ターゲットのクラス名
            $table->string('status')->default('未承認');
            $table->boolean('is_complete')->default(false);
            $table->unsignedBigInteger('node_id')->nullable(); // 現在のノードID
            $table->text('comment')->nullable(); // 申請者のコメント
            $table->text('msg')->nullable(); // 通知メッセージ
            $table->string('link')->nullable(); // リンク先URL
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flow_tasks');
    }
};
