<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
   
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn('min_stock');
    });
}

};
