<?php

class Contrat
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function creer(int $employeId, array $d): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO contrat
                (employe_id, type, date_debut, date_fin, salaire_base, heures_mensuelles, statut, fichier_chemin)
             VALUES
                (:employe_id, :type, :date_debut, :date_fin, :salaire_base, :heures, 'en_cours', :fichier)"
        );
        $stmt->execute([
            ':employe_id'   => $employeId,
            ':type'         => $d['type_contrat'],
            ':date_debut'   => $d['date_debut'],
            ':date_fin'     => $d['date_fin'],
            ':salaire_base' => $d['salaire_base'],
            ':heures'       => $d['heures_mensuelles'],
            ':fichier'      => $d['fichier_chemin'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}
