<?php
declare(strict_types=1);

namespace App\Paie;

final class EmployeRubrique extends Modele
{
    public function listerPourPeriode(int $employeId, string $debut, string $fin): array
    {
        try {
            return $this->requete(
                'SELECT * FROM rubrique_employe WHERE id_employe = ? AND date_debut <= ? AND (date_fin IS NULL OR date_fin >= ?) ORDER BY date_debut DESC',
                [$employeId, $fin, $debut]
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function attribuer(int $employeId, int $rubriqueId, float $montant, string $debut, ?string $fin = null, ?string $justification = null): int
    {
        $this->requete(
            'INSERT INTO rubrique_employe (id_employe, id_type_rubrique, montant, date_debut, date_fin, justification) VALUES (?, ?, ?, ?, ?, ?)',
            [$employeId, $rubriqueId, $montant, $debut, $fin ?: null, $justification]
        );
        return (int) $this->pdo->lastInsertId();
    }
}
