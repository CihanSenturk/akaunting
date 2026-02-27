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
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('code')->nullable()->after('company_id');
            $table->string('accountable_type')->nullable()->after('parent_id');
            $table->unsignedBigInteger('accountable_id')->nullable()->after('accountable_type');

            $table->index(['accountable_type', 'accountable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['accountable_type', 'accountable_id']);
            $table->dropColumn(['code', 'accountable_type', 'accountable_id']);
        });
    }
};
