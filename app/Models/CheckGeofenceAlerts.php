<?php

namespace App\Console\Commands;

use App\Models\Enfant;
use Illuminate\Console\Command;

/**
 * À adapter aux noms réels de tes relations/colonnes si elles diffèrent
 * (ex: Enfant::positions(), Position::latitude/longitude, Place::rayon en mètres).
 *
 * Logique : un enfant est "en sécurité" s'il se trouve dans AU MOINS UNE de ses
 * zones actives (Place où alerte_sortie = true). Quand il sort de TOUTES les
 * zones à la fois, on démarre le délai de grâce de la zone qu'il vient de
 * quitter avant d'envoyer l'alerte — pour absorber les trajets normaux
 * (ex : maison -> école) sans fausse alerte.
 */
class CheckGeofenceAlerts extends Command
{
    protected $signature = 'geofence:check';
    protected $description = "Vérifie la sortie des zones de sécurité et déclenche les alertes après le délai de grâce";

    public function handle(): int
    {
        Enfant::with(['zoneActuelle', 'lastPosition'])
            ->whereHas('places', fn ($q) => $q->where('alerte_sortie', true))
            ->chunk(50, function ($enfants) {
                foreach ($enfants as $enfant) {
                    $this->checkEnfant($enfant);
                }
            });

        return self::SUCCESS;
    }

    private function checkEnfant(Enfant $enfant): void
    {
        $derniere = $enfant->lastPosition;
        if (!$derniere) {
            return;
        }

        $zonesActives = $enfant->places()->where('alerte_sortie', true)->get();

        $zoneTrouvee = $zonesActives->first(function ($zone) use ($derniere) {
            return $this->distanceMetres(
                $derniere->latitude,
                $derniere->longitude,
                $zone->latitude,
                $zone->longitude,
            ) <= $zone->rayon;
        });

        if ($zoneTrouvee) {
            // Dans une zone connue -> on annule tout minuteur en cours
            if ($enfant->zone_actuelle_id !== $zoneTrouvee->id || $enfant->hors_zone_depuis !== null) {
                $enfant->update([
                    'zone_actuelle_id' => $zoneTrouvee->id,
                    'hors_zone_depuis' => null,
                    'delai_grace_courant' => null,
                    'alerte_sortie_envoyee_a' => null,
                ]);
            }
            return;
        }

        // Hors de toutes les zones actives
        if ($enfant->hors_zone_depuis === null) {
            // Premier constat de sortie : on démarre le délai de grâce
            // de la dernière zone connue (ou 15 min par défaut si aucune)
            $delai = optional($enfant->zoneActuelle)->delai_grace_minutes ?? 15;

            $enfant->update([
                'hors_zone_depuis' => now(),
                'delai_grace_courant' => $delai,
            ]);
            return;
        }

        if ($enfant->alerte_sortie_envoyee_a !== null) {
            // Alerte déjà envoyée pour cette sortie, on attend le retour en zone
            return;
        }

        $echeance = $enfant->hors_zone_depuis->copy()->addMinutes($enfant->delai_grace_courant ?? 15);

        if (now()->greaterThanOrEqualTo($echeance)) {
            $this->declencherAlerte($enfant);
            $enfant->update(['alerte_sortie_envoyee_a' => now()]);
        }
    }

    private function declencherAlerte(Enfant $enfant): void
    {
        // TODO : brancher ici l'envoi FCM + SMS (via le gateway SIM800L de l'infra)
        // et la création de l'entrée Alerte en base (Zone -> Alerte du diagramme de classes).
        // Ex : NotificationService::alerteSortieZone($enfant);
    }

    private function distanceMetres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $rayonTerre = 6371000; // mètres
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $rayonTerre * $c;
    }
}