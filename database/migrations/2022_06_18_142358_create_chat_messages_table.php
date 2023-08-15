<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->integer('sender_id');
            $table->integer('receiver_id');
            $table->timestamps();
        });


        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->integer('chat_conversations_id');
            $table->integer('user_id');
            $table->text('message')->nullable();
            $table->text('type');
            $table->json('meta');
            $table->dateTime('seen_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_conversations');
        Schema::dropIfExists('chat_messages');

    }
};
