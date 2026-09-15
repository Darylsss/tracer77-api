<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fusionne le concept de "zone de sécurité" dans les lieux existants :
     * un Place peut désormais déclencher une alerte quand le traceur le quitte,
     * avec un délai de grâce pour absorber les trajets normaux (ex : maison -> école).
     */
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->boolean('alerte_sortie')->default(false)->after('rayon');
            $table->unsignedInteger('delai_grace_minutes')->nullable()->after('alerte_sortie');
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn(['alerte_sortie', 'delai_grace_minutes']);
        });
    }
};