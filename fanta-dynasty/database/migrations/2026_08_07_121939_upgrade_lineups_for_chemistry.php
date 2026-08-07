<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('real_players', function (Blueprint $table) {
            $table->string('nationality')->default('Italia'); // Nazionalità per l'intesa
        });
        Schema::table('lineup_details', function (Blueprint $table) {
            $table->string('position_key')->nullable(); // Es: 'D_0', 'A_1' per il riposizionamento
        });
    }
    public function down(): void {
        Schema::table('real_players', function (Blueprint $table) { $table->dropColumn('nationality'); });
        Schema::table('lineup_details', function (Blueprint $table) { $table->dropColumn('position_key'); });
    }
};