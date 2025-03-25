<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->text('value'); // We'll store all values as text and convert as needed in the application layer
            $table->timestamps();
            
            // Create a unique constraint to prevent duplicate attribute values for the same job
            $table->unique(['job_listing_id', 'attribute_id']);
        });
        
        // Add index for value column with appropriate length limit for MySQL
        DB::statement('ALTER TABLE job_attribute_values ADD INDEX value_index (value(191))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_attribute_values');
    }
};
