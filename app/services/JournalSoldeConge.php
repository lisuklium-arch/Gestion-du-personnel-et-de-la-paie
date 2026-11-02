<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\MouvementSoldeConge;
use InvalidArgumentException;
use PDOException;

/** Seul point d'écriture du journal des soldes. */
final class JournalSoldeConge
{
    public static function acquerir(int $idSolde, string $quantite, int $idUtilisateur, string $reference, string $motif, ?int $idDemande = null): int
    {
        return self::poster($idSolde, 'ACQUISITION', $quantite, [], $idUtilisateur, $reference, $motif, $idDemande);
    }

    public static function reserver(int $idSolde, string $quantite, int $idUtilisateur, string $reference, string $motif, ?int $idDemande = null): int
    {
        return self::poster($idSolde, 'RESERVATION', $quantite, [], $idUtilisateur, $reference, $motif, $idDemande);
    }

    public static function liberer(int $idSolde, string $quantite, int $idUtilisateur, string $reference, string $motif, ?int $idDemande = null): int
    {
        return self::poster($idSolde, 'LIBERATION', $quantite, [], $idUtilisateur, $reference, $motif, $idDemande);
    }

    public static function approuver(int $idSolde, string $quantite, int $idUtilisateur, string $reference, string $motif, ?int $idDemande = null): int
    {
        return self::poster($idSolde, 'APPROBATION', $quantite, [], $idUtilisateur, $reference, $motif, $idDemande);
    }

    public static function restituer(int $idSolde, string $quantite, int $idUtilisateur, string $reference, string $motif, ?int $idDemande = null): int
    {
        return self::poster($idSolde, 'RESTITUTION', $quantite, [], $idUtilisateur, $reference, $motif, $idDemande);
    }

    public static function corriger(int $idSolde, string $deltaAcquis, string $deltaReserve, string $deltaPris, int $idUtilisateur, string $reference, string $motif, ?int $idDemande = null): int
    {
        return self::poster($idSolde, 'CORRECTION', '0.0', [
            'delta_acquis' => $deltaAcquis,
            'delta_reserve' => $deltaReserve,
            'delta_pris' => $deltaPris,
        ], $idUtilisateur, $reference, $motif, $idDemande);
    }

    /** @param array{delta_acquis?:string,delta_reserve?:string,delta_pris?:string} $correction */
    private static function poster(int $idSolde, string $type, string $quantite, array $correction, int $idUtilisateur, string $reference, string $motif, ?int $idDemande): int
    {
        if ($idSolde <= 0 || $idUtilisateur <= 0) {
            throw new InvalidArgumentException('Solde ou auteur invalide.');
        }
        if ($idDemande !== null && $idDemande <= 0) {
            throw new InvalidArgumentException('Demande invalide.');
        }
        $reference = self::reference($reference);
        $motif = self::motif($motif);
        $deltas = OperationSolde::deltas($type, $quantite, $correction);

        return self::transactionnel(static function () use ($idSolde, $type, $quantite, $deltas, $idUtilisateur, $reference, $motif, $idDemande): int {
            $solde = Database::one('SELECT id_solde FROM solde_conge WHERE id_solde = :id FOR UPDATE', ['id' => $idSolde]);
            if ($solde === null) {
                throw new InvalidArgumentException('Solde introuvable.');
            }

            $ligne = Database::one(
                'SELECT id_mouvement, id_solde, id_demande_conge, type_mouvement,
                        CAST(delta_acquis AS CHAR) AS delta_acquis,
                        CAST(delta_reserve AS CHAR) AS delta_reserve,
                        CAST(delta_pris AS CHAR) AS delta_pris
                 FROM mouvement_solde_conge WHERE reference_operation = :ref',
                ['ref' => $reference]
            );
            $existant = null;
            if ($ligne !== null) {
                $existant = [
                    'id_solde' => (int) $ligne['id_solde'],
                    'id_demande_conge' => $ligne['id_demande_conge'] === null ? null : (int) $ligne['id_demande_conge'],
                    'type' => (string) $ligne['type_mouvement'],
                    'delta_acquis' => QuantiteJours::depuisNombre($ligne['delta_acquis']),
                    'delta_reserve' => QuantiteJours::depuisNombre($ligne['delta_reserve']),
                    'delta_pris' => QuantiteJours::depuisNombre($ligne['delta_pris']),
                ];
            }

            if (PosteJournal::decider($existant, $idSolde, $idDemande, $type, $deltas) === 'deja') {
                return (int) $ligne['id_mouvement'];
            }

            OperationSolde::verifier($type, $quantite, MouvementSoldeConge::totauxParSolde($idSolde), $deltas);
            Database::run(
                'INSERT INTO mouvement_solde_conge
                    (id_solde, id_demande_conge, id_utilisateur, type_mouvement, delta_acquis, delta_reserve, delta_pris, reference_operation, motif)
                 VALUES (:solde, :demande, :auteur, :type, :acquis, :reserve, :pris, :ref, :motif)',
                [
                    'solde' => $idSolde,
                    'demande' => $idDemande,
                    'auteur' => $idUtilisateur,
                    'type' => $type,
                    'acquis' => $deltas['delta_acquis'],
                    'reserve' => $deltas['delta_reserve'],
                    'pris' => $deltas['delta_pris'],
                    'ref' => $reference,
                    'motif' => $motif,
                ]
            );
            return (int) Database::pdo()->lastInsertId();
        });
    }

    private static function transactionnel(callable $action): int
    {
        $pdo = Database::pdo();
        $interne = !$pdo->inTransaction();
        if ($interne) {
            $pdo->beginTransaction();
        }
        try {
            $id = $action();
            if ($interne) {
                $pdo->commit();
            }
            return $id;
        } catch (PDOException $e) {
            if ($interne && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($interne && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function reference(string $reference): string
    {
        $reference = trim($reference);
        if (!preg_match('/^[A-Za-z0-9:_\\-]{1,100}$/D', $reference)) {
            throw new InvalidArgumentException('Référence de mouvement invalide.');
        }
        return $reference;
    }

    private static function motif(string $motif): string
    {
        $motif = trim($motif);
        if ($motif === '' || mb_strlen($motif) > 255) {
            throw new InvalidArgumentException('Le motif du mouvement est obligatoire (255 caractères au plus).');
        }
        return $motif;
    }
}
