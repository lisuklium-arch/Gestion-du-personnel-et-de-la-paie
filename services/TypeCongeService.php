<?php
class TypeCongeService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function tous(): array
    {
        return $this->pdo->query('SELECT * FROM type_conge ORDER BY actif DESC, libelle')->fetchAll();
    }

    public function trouver(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM type_conge WHERE id = ?');
        $st->execute([$id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function enregistrer(array $d, ?int $id = null): int
    {
        $code = strtoupper(trim((string) ($d['code'] ?? '')));
        $libelle = trim((string) ($d['libelle'] ?? ''));
        if ($code === '' || $libelle === '') {
            throw new RuntimeException('Code et libellé obligatoires.');
        }
        $fields = [
            'code' => $code,
            'libelle' => $libelle,
            'description_type' => trim((string) ($d['description_type'] ?? '')) ?: null,
            'categorie' => in_array($d['categorie'] ?? '', ['paye', 'non_paye', 'special'], true) ? $d['categorie'] : 'paye',
            'decompte_solde' => !empty($d['decompte_solde']) ? 1 : 0,
            'droit_annuel_jours' => (float) ($d['droit_annuel_jours'] ?? 18),
            'duree_min_jours' => (float) ($d['duree_min_jours'] ?? 0.5),
            'duree_max_jours' => ($d['duree_max_jours'] ?? '') === '' ? null : (float) $d['duree_max_jours'],
            'preavis_jours' => (int) ($d['preavis_jours'] ?? 0),
            'justificatif_requis' => !empty($d['justificatif_requis']) ? 1 : 0,
            'fractionnable' => !empty($d['fractionnable']) ? 1 : 0,
            'reportable' => !empty($d['reportable']) ? 1 : 0,
            'actif' => !empty($d['actif']) ? 1 : 0,
        ];
        if ($id) {
            $sql = 'UPDATE type_conge SET code=?, libelle=?, decompte_solde=?, droit_annuel_jours=?, justificatif_requis=?, actif=?';
            $params = [$fields['code'], $fields['libelle'], $fields['decompte_solde'], $fields['droit_annuel_jours'], $fields['justificatif_requis'], $fields['actif'], $id];
            // colonnes optionnelles si migration jouée
            if ($this->col('description_type')) {
                $sql = 'UPDATE type_conge SET code=?, libelle=?, description_type=?, categorie=?, decompte_solde=?, droit_annuel_jours=?,
                        duree_min_jours=?, duree_max_jours=?, preavis_jours=?, justificatif_requis=?, fractionnable=?, reportable=?, actif=? WHERE id=?';
                $params = [
                    $fields['code'], $fields['libelle'], $fields['description_type'], $fields['categorie'],
                    $fields['decompte_solde'], $fields['droit_annuel_jours'], $fields['duree_min_jours'], $fields['duree_max_jours'],
                    $fields['preavis_jours'], $fields['justificatif_requis'], $fields['fractionnable'], $fields['reportable'],
                    $fields['actif'], $id,
                ];
            } else {
                $sql .= ' WHERE id=?';
            }
            $this->pdo->prepare($sql)->execute($params);
            return $id;
        }
        if ($this->col('description_type')) {
            $this->pdo->prepare(
                'INSERT INTO type_conge (code, libelle, description_type, categorie, decompte_solde, droit_annuel_jours,
                  duree_min_jours, duree_max_jours, preavis_jours, justificatif_requis, fractionnable, reportable, actif)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $fields['code'], $fields['libelle'], $fields['description_type'], $fields['categorie'],
                $fields['decompte_solde'], $fields['droit_annuel_jours'], $fields['duree_min_jours'], $fields['duree_max_jours'],
                $fields['preavis_jours'], $fields['justificatif_requis'], $fields['fractionnable'], $fields['reportable'],
                $fields['actif'],
            ]);
        } else {
            $this->pdo->prepare(
                'INSERT INTO type_conge (code, libelle, decompte_solde, droit_annuel_jours, justificatif_requis, actif)
                 VALUES (?,?,?,?,?,?)'
            )->execute([
                $fields['code'], $fields['libelle'], $fields['decompte_solde'], $fields['droit_annuel_jours'],
                $fields['justificatif_requis'], $fields['actif'],
            ]);
        }
        return (int) $this->pdo->lastInsertId();
    }

    private function col(string $name): bool
    {
        try {
            $st = $this->pdo->prepare(
                'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=\'type_conge\' AND COLUMN_NAME=?'
            );
            $st->execute([$name]);
            return (bool) $st->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}
