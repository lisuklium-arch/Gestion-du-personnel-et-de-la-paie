<?php
declare(strict_types=1);

namespace App\Paie;

final class AvanceSalaire extends Modele
{
    public function listerADeduire(int $employeId): array
    {
        try {
            return $this->requete(
                'SELECT * FROM avance_salaire WHERE id_employe = ? AND statut = ? ORDER BY date_demande DESC',
                [$employeId, 'VALIDEE']
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function rattacherAFiche(int $id, int $fichePaieId): bool
    {
        try {
            return $this->requete(
                'UPDATE avance_salaire SET id_fiche = ?, statut = ? WHERE id_avance = ?',
                [$fichePaieId, 'DEDUITE', $id]
            )->rowCount() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public function demander(int $employeId, int $montant, ?string $motif = null): int
    {
        $this->requete(
            'INSERT INTO avance_salaire (id_employe, montant, motif, date_demande, statut) VALUES (?, ?, ?, CURDATE(), ?)',
            [$employeId, $montant, $motif ?: null, 'EN_ATTENTE']
        );
        return (int) $this->pdo->lastInsertId();
    }

    public function listerEnAttente(): array
    {
        return $this->requete(
            'SELECT a.*, e.nom, e.prenom FROM avance_salaire a JOIN employe e ON e.id_employe = a.id_employe WHERE a.statut = ? ORDER BY a.date_demande DESC',
            ['EN_ATTENTE']
        )->fetchAll();
    }

    public function accorder(int $id, int $utilisateurId): bool
    {
        return $this->requete('UPDATE avance_salaire SET statut = ? WHERE id_avance = ? AND statut = ?', ['VALIDEE', $id, 'EN_ATTENTE'])->rowCount() > 0;
    }

    public function refuser(int $id, int $utilisateurId): bool
    {
        return $this->requete('UPDATE avance_salaire SET statut = ? WHERE id_avance = ? AND statut = ?', ['REFUSEE', $id, 'EN_ATTENTE'])->rowCount() > 0;
    }
}
