<?php
declare(strict_types=1);

namespace App\Paie;

final class PosteRubrique extends Modele
{
    public function listerParPoste(int $posteId): array
    {
        try {
            return $this->requete(
                'SELECT * FROM rubrique_poste WHERE id_poste = ? ORDER BY id_rubrique_poste',
                [$posteId]
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function definir(int $posteId, int $rubriqueId, float $montant): int
    {
        $this->requete(
            'INSERT INTO rubrique_poste (id_poste, id_type_rubrique, montant) VALUES (?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE montant = VALUES(montant)',
            [$posteId, $rubriqueId, $montant]
        );
        return (int) $this->pdo->lastInsertId();
    }
}
