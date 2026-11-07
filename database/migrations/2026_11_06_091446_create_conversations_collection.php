<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $collection) {
            $collection->unique('conversation_key');
        });
    }

    public function down(): void
    {
        Schema::drop('conversations');
    }
};
