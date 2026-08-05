<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('real_players', function (Blueprint $table) {
            $table->integer('quotation')->default(1); // La quotazione che cambia
        });
    }
    public function down(): void {
        Schema::table('real_players', function (Blueprint $table) {
            $table->dropColumn('quotation');
        });
    }
};