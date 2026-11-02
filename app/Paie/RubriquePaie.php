<?php
declare(strict_types=1);

namespace App\Paie;

final class RubriquePaie extends Modele
{
    public const NATURES = ['PRIME', 'RETENUE', 'HEURES_SUP'];
    public const MODES = ['MONTANT_FIXE', 'POURCENTAGE_BRUT'];

    public function lister(bool $actifsSeulement = true): array
    {
        try {
            $sql = 'SELECT * FROM type_rubrique';
            if ($actifsSeulement) {
                $sql .= ' WHERE actif = 1';
            }
            $sql .= ' ORDER BY libelle';
            return $this->requete($sql)->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function trouverParId(int $id): ?array
    {
        try {
            $row = $this->requete('SELECT * FROM type_rubrique WHERE id_type_rubrique = ?', [$id])->fetch();
            return $row === false ? null : $row;
        } catch (\Throwable) {
            return null;
        }
    }

    public function creer(string $code, string $libelle, string $nature, string $mode, float $valeur): int
    {
        $this->verifierEnum($nature, self::NATURES, 'nature');
        $this->verifierEnum($mode, self::MODES, 'mode_calcul');
        $this->requete(
            'INSERT INTO type_rubrique (code, libelle, nature, mode_calcul, valeur_par_defaut, actif) VALUES (?, ?, ?, ?, ?, 1)',
            [trim($code), trim($libelle), $nature, $mode, $valeur]
        );
        return (int) $this->pdo->lastInsertId();
    }

    public function changerActif(int $id, bool $actif): bool
    {
        return $this->requete('UPDATE type_rubrique SET actif = ? WHERE id_type_rubrique = ?', [$actif ? 1 : 0, $id])->rowCount() > 0;
    }
}
