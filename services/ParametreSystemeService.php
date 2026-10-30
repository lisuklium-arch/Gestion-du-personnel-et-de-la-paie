<?php

require_once __DIR__ . '/../models/ParametreSysteme.php';
require_once __DIR__ . '/JournalService.php';

class ParametreSystemeService
{
    /**
     * Règles de chaque paramètre connu : libellé, unité, bornes et pas de saisie.
     * Une clé absente de cette liste reste modifiable comme texte libre (255 caractères).
     */
    public const REGLES = [
        'majoration_heures_sup_pct' => ['unite' => '%',     'min' => 0,  'max' => 100, 'pas' => 0.01, 'aide' => 'Entre 0 et 100 %.'],
        'heures_mensuelles_defaut'  => ['unite' => 'h',     'min' => 1,  'max' => 400, 'pas' => 0.01, 'aide' => 'Entre 1 et 400 heures.'],
        'cotisation_cnps_pct'       => ['unite' => '%',     'min' => 0,  'max' => 100, 'pas' => 0.01, 'aide' => 'Entre 0 et 100 %.'],
        'seuil_anomalie_net_pct'    => ['unite' => '%',     'min' => 0,  'max' => 100, 'pas' => 0.01, 'aide' => 'Entre 0 et 100 %.'],
        'droit_annuel_defaut_jours' => ['unite' => 'jours', 'min' => 0,  'max' => 60,  'pas' => 0.5,  'aide' => 'Entre 0 et 60 jours, par demi-journée.'],
    ];

    private ParametreSysteme $parametres;
    private JournalService $journal;

    public function __construct(ParametreSysteme $parametres, JournalService $journal)
    {
        $this->parametres = $parametres;
        $this->journal = $journal;
    }

    public function lister(): array
    {
        return $this->parametres->lister();
    }

    public function modifier(int $auteurId, string $cle, $valeurBrute): array
    {
        $courant = $this->parametres->trouver($cle);
        if ($courant === null) {
            return ['ok' => false, 'message' => 'Paramètre introuvable.'];
        }

        $valeur = self::normaliser($cle, $valeurBrute);
        if (!is_string($valeur)) {
            return ['ok' => false, 'message' => $valeur['erreur']];
        }

        if ($valeur === $courant['valeur']) {
            return ['ok' => true, 'message' => 'Aucun changement : la valeur est identique.', 'valeur' => $valeur];
        }

        $this->parametres->modifierValeur($cle, $valeur);
        $this->journal->enregistrer(
            $auteurId, 'modification_parametre', 'parametre_systeme', null,
            ['cle' => $cle, 'valeur' => $courant['valeur']],
            ['cle' => $cle, 'valeur' => $valeur]
        );

        return ['ok' => true, 'message' => 'Paramètre « ' . $cle . ' » enregistré.', 'valeur' => $valeur];
    }

    /** Les paramètres connus servent au calcul de la paie et des congés : on ne les supprime pas. */
    public function supprimer(int $auteurId, string $cle): array
    {
        $courant = $this->parametres->trouver($cle);
        if ($courant === null) {
            return ['ok' => false, 'message' => 'Paramètre introuvable.'];
        }
        if (isset(self::REGLES[$cle])) {
            return ['ok' => false, 'message' => 'Ce paramètre est utilisé par la paie et les congés : il ne peut pas être supprimé. Modifiez sa valeur.'];
        }

        $this->parametres->supprimer($cle);
        $this->journal->enregistrer(
            $auteurId, 'suppression_parametre', 'parametre_systeme', null,
            ['cle' => $cle, 'valeur' => $courant['valeur']], null
        );
        return ['ok' => true, 'message' => 'Paramètre « ' . $cle . ' » supprimé.'];
    }

    /** Retourne la valeur normalisée (string) ou ['erreur' => message]. */
    public static function normaliser(string $cle, $brute)
    {
        if (!is_string($brute) && !is_int($brute) && !is_float($brute)) {
            return ['erreur' => 'Valeur invalide.'];
        }
        $texte = trim((string) $brute);
        $regle = self::REGLES[$cle] ?? null;

        if ($regle === null) {
            if ($texte === '' || mb_strlen($texte) > 255) {
                return ['erreur' => 'La valeur doit contenir entre 1 et 255 caractères.'];
            }
            return $texte;
        }

        $texte = str_replace(',', '.', $texte);
        if (!preg_match('/^\d{1,4}(\.\d{1,2})?$/', $texte)) {
            return ['erreur' => 'Saisissez un nombre positif (2 décimales au maximum).'];
        }
        $nombre = (float) $texte;
        if ($nombre < $regle['min'] || $nombre > $regle['max']) {
            return ['erreur' => $regle['aide']];
        }
        if ($regle['pas'] === 0.5 && fmod($nombre * 2, 1.0) != 0.0) {
            return ['erreur' => $regle['aide']];
        }

        return self::formaterNombre($nombre);
    }

    private static function formaterNombre(float $n): string
    {
        $s = number_format($n, 2, '.', '');
        return rtrim(rtrim($s, '0'), '.');
    }
}
