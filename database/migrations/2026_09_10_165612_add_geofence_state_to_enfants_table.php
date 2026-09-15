<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * État nécessaire pour la logique "OR entre zones + délai de grâce" :
     * on retient dans quelle zone de sécurité l'enfant se trouvait en dernier,
     * et depuis quand il n'est plus dans aucune zone (pour compter le délai
     * de grâce propre à cette zone avant de déclencher l'alerte).
     */
    public function up(): void
    {
        Schema::table('enfants', function (Blueprint $table) {
            $table->foreignId('zone_actuelle_id')
                ->nullable()
                ->after('id')
                ->constrained('places')
                ->nullOnDelete();
            $table->timestamp('hors_zone_depuis')->nullable()->after('zone_actuelle_id');
            $table->unsignedInteger('delai_grace_courant')->nullable()->after('hors_zone_depuis');
            $table->timestamp('alerte_sortie_envoyee_a')->nullable()->after('delai_grace_courant');
        });
    }

    public function down(): void
    {
        Schema::table('enfants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_actuelle_id');
            $table->dropColumn(['hors_zone_depuis', 'delai_grace_courant', 'alerte_sortie_envoyee_a']);
        });
    }
};