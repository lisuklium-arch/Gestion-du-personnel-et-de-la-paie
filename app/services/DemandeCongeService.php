<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\DemandeConge;
use App\Models\Journal;
use App\Models\Notification;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PDOException;

/** Dépôt, décision et annulation. Seul endroit qui écrit une demande et son mouvement de solde. */
final class DemandeCongeService
{
    public static function calculerJours(string $debut, string $fin, string $periodeDebut, string $periodeFin): string
    {
        try {
            return SoldeService::joursOuvrables($debut, $fin, $periodeDebut, $periodeFin);
        } catch (InvalidArgumentException $e) {
            throw new DomainException(match ($e->getMessage()) {
                'La date de debut doit preceder la date de fin.' => 'La date de fin doit être postérieure ou égale à la date de début.',
                'Une demande ne peut pas traverser deux annees.' => 'Une demande ne peut pas chevaucher deux années. Déposez une demande par année.',
                'Les periodes de la journee sont inversees.' => 'Sur une même journée, l\'après-midi ne peut pas précéder le matin.',
                'La periode ne contient aucun jour ouvrable.' => 'Cette période ne contient aucun jour ouvrable.',
                default => 'Période de congé invalide.',
            });
        }
    }

    /**
     * @param array{id_type_conge:int,date_debut:string,date_fin:string,periode_debut:string,periode_fin:string,motif:string} $saisie
     * @param array{tmp:string,taille:int,erreur:int}|null $fichier
     */
    public static function deposer(int $idEmploye, int $idUtilisateur, array $saisie, ?array $fichier, ?string $ip): int
    {
        $motif = trim($saisie['motif']);
        if ($motif === '' || mb_strlen($motif) > 255) {
            throw new DomainException('Le motif est obligatoire (255 caractères au plus).');
        }
        $jours = self::calculerJours($saisie['date_debut'], $saisie['date_fin'], $saisie['periode_debut'], $saisie['periode_fin']);
        $aujourdhui = new DateTimeImmutable('today');
        if ($saisie['date_debut'] < $aujourdhui->modify('-7 days')->format('Y-m-d')) {
            throw new DomainException('La date de début ne peut pas remonter à plus de 7 jours.');
        }
        if ($saisie['date_debut'] > $aujourdhui->modify('+365 days')->format('Y-m-d')) {
            throw new DomainException('La date de début est trop éloignée.');
        }

        $chemin = null;
        try {
            return self::transactionnel(static function () use ($idEmploye, $idUtilisateur, $saisie, $fichier, $jours, $motif, $ip, &$chemin): int {
                Database::one('SELECT id_employe FROM employe WHERE id_employe = :id FOR UPDATE', ['id' => $idEmploye]);
                $employe = DemandeConge::contexteEmploye($idEmploye);
                if ($employe === null || (int) $employe['id_utilisateur'] !== $idUtilisateur) {
                    throw new DomainException('Dossier employé introuvable.');
                }
                if ($employe['statut'] !== 'ACTIF') {
                    throw new DomainException('Un employé suspendu ou sorti ne peut pas déposer de congé.');
                }
                $type = DemandeConge::typeActif($saisie['id_type_conge']);
                if ($type === null) {
                    throw new DomainException('Type de congé invalide.');
                }
                $decompte = (int) $type['decompte_solde'] === 1;
                $avisRequis = self::avisDuContexte($employe);
                if ($employe['role_demandeur'] === 'RESPONSABLE_RH') {
                    if (DemandeConge::responsablesRhSauf($idUtilisateur) === []) {
                        throw new DomainException('Aucun autre responsable RH actif ne peut instruire votre demande.');
                    }
                    if (DemandeConge::directeurs() === []) {
                        throw new DomainException('Aucun directeur général actif ne peut trancher un congé RH.');
                    }
                }
                foreach (DemandeConge::periodesOccupees($idEmploye) as $occupee) {
                    if (ChevauchementConge::seChevauchent(
                        $saisie['date_debut'],
                        $saisie['date_fin'],
                        $saisie['periode_debut'],
                        $saisie['periode_fin'],
                        substr((string) $occupee['date_debut'], 0, 10),
                        substr((string) $occupee['date_fin'], 0, 10),
                        (string) $occupee['periode_debut'],
                        (string) $occupee['periode_fin']
                    )) {
                        throw new DomainException('Ces dates chevauchent une demande déjà en cours ou approuvée.');
                    }
                }
                if ((int) $type['justificatif_requis'] === 1 && $fichier === null) {
                    throw new DomainException('Ce type de congé exige un justificatif PDF, JPG ou PNG.');
                }
                $chemin = $fichier === null ? null : self::stocker($fichier);

                $annee = (int) substr($saisie['date_debut'], 0, 4);
                $idSolde = null;
                if ($decompte) {
                    $idSolde = Database::value(
                        'SELECT id_solde FROM solde_conge WHERE id_employe = :e AND id_type_conge = :t AND annee = :a FOR UPDATE',
                        ['e' => $idEmploye, 't' => (int) $type['id_type_conge'], 'a' => $annee]
                    );
                    if ($idSolde === false || $idSolde === null) {
                        throw new DomainException('Aucun solde n\'est ouvert pour cette année. Contactez la RH.');
                    }
                }

                Database::run(
                    'INSERT INTO demande_conge
                        (id_employe, id_type_conge, date_debut, date_fin, periode_debut, periode_fin, nb_jours_ouvres, motif, justificatif, statut)
                     VALUES (:e, :t, :debut, :fin, :pd, :pf, :jours, :motif, :piece, :statut)',
                    [
                        'e' => $idEmploye,
                        't' => (int) $type['id_type_conge'],
                        'debut' => $saisie['date_debut'],
                        'fin' => $saisie['date_fin'],
                        'pd' => $saisie['periode_debut'],
                        'pf' => $saisie['periode_fin'],
                        'jours' => $jours,
                        'motif' => $motif,
                        'piece' => $chemin,
                        'statut' => CircuitConge::statutAuDepot($avisRequis),
                    ]
                );
                $id = (int) Database::pdo()->lastInsertId();
                if ($decompte) {
                    JournalSoldeConge::reserver(
                        (int) $idSolde,
                        $jours,
                        $idUtilisateur,
                        'RES:' . $id,
                        'Dépôt de la demande ' . $id,
                        $id
                    );
                }
                self::informerDepot($employe, $type, $saisie, $jours, $avisRequis, $id);
                Journal::enregistrer($idUtilisateur, 'Dépôt d\'une demande de congé', 'demande_conge', $id, $type['libelle'] . ' · ' . $jours . ' j', $ip);
                return $id;
            });
        } catch (\Throwable $e) {
            self::effacer($chemin);
            throw $e;
        }
    }

    public static function donnerAvis(int $idDemande, int $idValideur, string $decision, string $commentaire, ?string $ip): void
    {
        self::trancher($idDemande, $idValideur, 'AVIS_CHEF', $decision, $commentaire, $ip);
    }

    public static function deciderRh(int $idDemande, int $idValideur, string $decision, string $commentaire, ?string $ip): void
    {
        self::trancher($idDemande, $idValideur, 'DECISION_RH', $decision, $commentaire, $ip);
    }

    public static function deciderDg(int $idDemande, int $idValideur, string $decision, string $commentaire, ?string $ip): void
    {
        self::trancher($idDemande, $idValideur, 'DECISION_DG', $decision, $commentaire, $ip);
    }

    public static function annulerParEmploye(int $idDemande, int $idEmploye, int $idUtilisateur, ?string $ip): void
    {
        self::transactionnel(static function () use ($idDemande, $idEmploye, $idUtilisateur, $ip): void {
            $verrou = DemandeConge::verrouiller($idDemande);
            $demande = DemandeConge::trouver($idDemande);
            if ($verrou === null || $demande === null || (int) $demande['id_employe'] !== $idEmploye) {
                throw new DomainException('Demande introuvable.');
            }
            $decompte = (int) $demande['decompte_solde'] === 1;
            $suite = CircuitConge::annulationEmploye((string) $verrou['statut'], $decompte);
            self::appliquer($demande, $verrou, $suite, $idUtilisateur, null, null, 'Annulation par l\'employé de la demande ' . $idDemande, 'LIB:' . $idDemande);
            $destinataires = self::destinatairesEnAttente($demande);
            $espace = match ($demande['statut']) {
                'EN_ATTENTE_AVIS' => 'chef',
                'EN_ATTENTE_DG' => 'dg',
                default => 'rh',
            };
            self::prevenir($destinataires, 'Demande de congé annulée', $demande['prenom'] . ' ' . $demande['nom'] . ' a annulé sa demande du ' . self::periodeLisible($demande) . '.', $espace, $idDemande, false);
            Journal::enregistrer($idUtilisateur, 'Annulation d\'une demande de congé', 'demande_conge', $idDemande, null, $ip);
        });
    }

    public static function annulerParRh(int $idDemande, int $idUtilisateur, ?string $ip): void
    {
        self::transactionnel(static function () use ($idDemande, $idUtilisateur, $ip): void {
            $verrou = DemandeConge::verrouiller($idDemande);
            $demande = DemandeConge::trouver($idDemande);
            if ($verrou === null || $demande === null) {
                throw new DomainException('Demande introuvable.');
            }
            $decompte = (int) $demande['decompte_solde'] === 1;
            $pasCommence = substr((string) $verrou['date_debut'], 0, 10) > (new DateTimeImmutable('today'))->format('Y-m-d');
            $suite = CircuitConge::annulationRh((string) $verrou['statut'], $decompte, $pasCommence);
            self::appliquer($demande, $verrou, $suite, $idUtilisateur, null, null, 'Annulation RH de la demande ' . $idDemande, 'REST:' . $idDemande);
            self::prevenir([(int) $demande['id_utilisateur']], 'Congé annulé par la RH', 'Votre congé du ' . self::periodeLisible($demande) . ' a été annulé. Les jours déjà comptés comme pris sont restitués.', 'employe', $idDemande, true);
            Journal::enregistrer($idUtilisateur, 'Annulation RH d\'un congé approuvé', 'demande_conge', $idDemande, null, $ip);
        });
    }

    public static function cheminSecurise(string $relatif): ?string
    {
        if (!preg_match('#^storage/justificatifs/[a-f0-9]{32}\.(pdf|jpg|png)$#D', $relatif)) {
            return null;
        }
        $base = realpath(STORAGE_PATH . '/justificatifs');
        $reel = realpath(ROOT_PATH . '/' . $relatif);
        if ($base === false || $reel === false || !is_file($reel) || !str_starts_with($reel, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $reel;
    }

    private static function trancher(int $idDemande, int $idValideur, string $etape, string $decision, string $commentaire, ?string $ip): void
    {
        $commentaire = trim($commentaire);
        if ($decision === 'REFUSE' && $commentaire === '') {
            throw new DomainException('Un refus doit être motivé.');
        }
        if (mb_strlen($commentaire) > 255) {
            throw new DomainException('Le commentaire dépasse 255 caractères.');
        }
        self::transactionnel(static function () use ($idDemande, $idValideur, $etape, $decision, $commentaire, $ip): void {
            $verrou = DemandeConge::verrouiller($idDemande);
            $demande = DemandeConge::trouver($idDemande);
            if ($verrou === null || $demande === null) {
                throw new DomainException('Demande introuvable.');
            }
            self::controlerActeur($demande, $idValideur, $etape);
            $decompte = (int) $demande['decompte_solde'] === 1;
            $demandeurRh = $demande['role_demandeur'] === 'RESPONSABLE_RH';
            $suite = CircuitConge::transition((string) $verrou['statut'], $etape, $decision, $demandeurRh, $decompte);
            if ($suite['statut'] === 'EN_ATTENTE_DG' && DemandeConge::directeurs() === []) {
                throw new DomainException('Aucun directeur général actif ne peut trancher cette demande.');
            }
            $reference = $suite['mouvement'] === 'APPROBATION' ? 'APPR:' . $idDemande : 'LIB:' . $idDemande;
            $motifMouvement = ($decision === 'REFUSE' ? 'Refus de la demande ' : 'Décision sur la demande ') . $idDemande;
            self::appliquer($demande, $verrou, $suite, $idValideur, $etape, $decision, $motifMouvement, $reference, $commentaire === '' ? null : $commentaire);
            self::informerDecision($demande, $suite['statut'], $decision, $etape);
            Journal::enregistrer($idValideur, self::libelleAction($etape, $decision), 'demande_conge', $idDemande, $commentaire === '' ? null : $commentaire, $ip);
        });
    }

    /** @param array{statut:string,mouvement:?string} $suite */
    private static function appliquer(array $demande, array $verrou, array $suite, int $idUtilisateur, ?string $etape, ?string $decision, string $motifMouvement, string $reference, ?string $commentaire = null): void
    {
        if ($etape !== null && $decision !== null) {
            Database::run(
                'INSERT INTO validation (id_valideur, id_demande_conge, etape, decision, commentaire)
                 VALUES (:u, :d, :e, :decision, :c)',
                ['u' => $idUtilisateur, 'd' => (int) $demande['id_demande_conge'], 'e' => $etape, 'decision' => $decision, 'c' => $commentaire]
            );
        }
        $maj = Database::run(
            'UPDATE demande_conge SET statut = :statut WHERE id_demande_conge = :id AND statut = :ancien',
            ['statut' => $suite['statut'], 'id' => (int) $demande['id_demande_conge'], 'ancien' => $verrou['statut']]
        );
        if ($maj !== 1) {
            throw new DomainException('La demande a changé entre-temps. Rechargez la page.');
        }
        if ($suite['mouvement'] === null) {
            return;
        }
        $annee = (int) substr((string) $verrou['date_debut'], 0, 4);
        $idSolde = Database::value(
            'SELECT id_solde FROM solde_conge WHERE id_employe = :e AND id_type_conge = :t AND annee = :a FOR UPDATE',
            ['e' => (int) $verrou['id_employe'], 't' => (int) $verrou['id_type_conge'], 'a' => $annee]
        );
        if ($idSolde === false || $idSolde === null) {
            throw new DomainException('Le solde lié à cette demande est introuvable.');
        }
        $quantite = QuantiteJours::depuisNombre($verrou['nb_jours_ouvres']);
        $idDemande = (int) $demande['id_demande_conge'];
        match ($suite['mouvement']) {
            'LIBERATION' => JournalSoldeConge::liberer((int) $idSolde, $quantite, $idUtilisateur, $reference, $motifMouvement, $idDemande),
            'APPROBATION' => JournalSoldeConge::approuver((int) $idSolde, $quantite, $idUtilisateur, $reference, $motifMouvement, $idDemande),
            'RESTITUTION' => JournalSoldeConge::restituer((int) $idSolde, $quantite, $idUtilisateur, $reference, $motifMouvement, $idDemande),
            default => throw new DomainException('Mouvement de solde inconnu.'),
        };
    }

    private static function controlerActeur(array $demande, int $idValideur, string $etape): void
    {
        if ((int) $demande['id_utilisateur'] === $idValideur && $etape !== 'DECISION_DG') {
            throw new DomainException('Vous ne pouvez pas trancher votre propre demande.');
        }
        $role = Database::value('SELECT role FROM utilisateur WHERE id_utilisateur = :id AND statut = \'ACTIF\'', ['id' => $idValideur]);
        if ($etape === 'AVIS_CHEF') {
            if ((int) ($demande['id_utilisateur_chef'] ?? 0) !== $idValideur) {
                throw new DomainException('Seul le chef du département peut donner cet avis.');
            }
            return;
        }
        if ($etape === 'DECISION_RH' && $role !== 'RESPONSABLE_RH') {
            throw new DomainException('Seul un responsable RH peut prendre cette décision.');
        }
        if ($etape === 'DECISION_DG' && $role !== 'DIRECTEUR_GENERAL') {
            throw new DomainException('Seul le directeur général peut prendre cette décision.');
        }
    }

    private static function avisDuContexte(array $employe): bool
    {
        return CircuitConge::avisRequis(
            $employe['id_chef'] !== null,
            (int) $employe['id_chef'] === (int) $employe['id_employe'],
            ($employe['role_chef'] ?? '') === 'DIRECTEUR_GENERAL',
            $employe['role_demandeur'] === 'RESPONSABLE_RH'
        );
    }

    private static function informerDepot(array $employe, array $type, array $saisie, string $jours, bool $avisRequis, int $id): void
    {
        $texte = $employe['prenom'] . ' ' . $employe['nom'] . ' a déposé une demande de ' . $type['libelle']
            . ' du ' . date_fr($saisie['date_debut']) . ' au ' . date_fr($saisie['date_fin']) . ' (' . nombre($jours) . ' j).';
        if ($avisRequis && (int) ($employe['id_utilisateur_chef'] ?? 0) > 0) {
            self::prevenir([(int) $employe['id_utilisateur_chef']], 'Demande de congé à examiner', $texte, 'chef', $id, false);
            return;
        }
        self::prevenir(DemandeConge::responsablesRhSauf((int) $employe['id_utilisateur']), 'Demande de congé à décider', $texte . ' Demande n° ' . $id . '.', 'rh', $id, false);
    }

    private static function informerDecision(array $demande, string $statut, string $decision, string $etape): void
    {
        $periode = self::periodeLisible($demande);
        $id = (int) $demande['id_demande_conge'];
        if ($etape === 'AVIS_CHEF') {
            $avis = $decision === 'APPROUVE' ? 'favorable' : 'défavorable';
            self::prevenir([(int) $demande['id_utilisateur']], 'Avis du chef', 'Votre demande de congé du ' . $periode . ' a reçu un avis ' . $avis . '. La RH doit encore décider.', 'employe', $id, false);
            self::prevenir(DemandeConge::responsablesRhSauf((int) $demande['id_utilisateur']), 'Demande de congé à décider', $demande['prenom'] . ' ' . $demande['nom'] . ' : avis ' . $avis . ' du chef, en attente de votre décision (' . $periode . ').', 'rh', $id, false);
            return;
        }
        if ($statut === 'EN_ATTENTE_DG') {
            self::prevenir([(int) $demande['id_utilisateur']], 'Congé transmis à la direction', 'Votre demande du ' . $periode . ' a reçu un accord RH. Le directeur général doit encore décider.', 'employe', $id, false);
            self::prevenir(DemandeConge::directeurs(), 'Congé RH à décider', $demande['prenom'] . ' ' . $demande['nom'] . ' : congé du ' . $periode . ' en attente de votre décision.', 'dg', $id, false);
            return;
        }
        $titre = $statut === 'APPROUVE' ? 'Congé approuvé' : 'Congé refusé';
        $texte = $statut === 'APPROUVE'
            ? 'Votre demande de congé du ' . $periode . ' est approuvée.'
            : 'Votre demande de congé du ' . $periode . ' est refusée.';
        self::prevenir([(int) $demande['id_utilisateur']], $titre, $texte, 'employe', $id, true);
    }

    /** @return list<int> */
    private static function destinatairesEnAttente(array $demande): array
    {
        return match ($demande['statut']) {
            'EN_ATTENTE_AVIS' => [(int) ($demande['id_utilisateur_chef'] ?? 0)],
            'EN_ATTENTE_RH' => DemandeConge::responsablesRhSauf((int) $demande['id_utilisateur']),
            'EN_ATTENTE_DG' => DemandeConge::directeurs(),
            default => [],
        };
    }

    /** @param list<int> $ids */
    private static function prevenir(array $ids, string $titre, string $message, string $espace, int $idDemande, bool $parEmail): void
    {
        $lien = self::lien($espace, $idDemande);
        foreach (array_unique($ids) as $id) {
            $idNotification = Notification::creer((int) $id, $titre, $message, 'CONGE', $lien);
            if ($parEmail) {
                CourrierConge::planifier($idNotification);
            }
        }
    }

    private static function lien(string $espace, int $idDemande): string
    {
        $base = match ($espace) {
            'chef' => '/chef/conges/voir?id=',
            'rh' => '/rh/conges/voir?id=',
            'dg' => '/direction/conges/voir?id=',
            default => '/employe/conges/voir?id=',
        };
        return $base . $idDemande;
    }

    private static function libelleAction(string $etape, string $decision): string
    {
        $sens = $decision === 'APPROUVE' ? 'favorable' : 'défavorable';
        return match ($etape) {
            'AVIS_CHEF' => 'Avis ' . $sens . ' sur un congé',
            'DECISION_RH' => 'Décision RH ' . $sens . ' sur un congé',
            default => 'Décision DG ' . $sens . ' sur un congé',
        };
    }

    private static function periodeLisible(array $demande): string
    {
        return date_fr(substr((string) $demande['date_debut'], 0, 10)) . ' au ' . date_fr(substr((string) $demande['date_fin'], 0, 10));
    }

    /** @param array{tmp:string,taille:int,erreur:int} $fichier */
    private static function stocker(array $fichier): string
    {
        if (($fichier['erreur'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new DomainException('Le justificatif n\'a pas pu être reçu.');
        }
        if (($fichier['taille'] ?? 0) <= 0 || $fichier['taille'] > 5 * 1024 * 1024) {
            throw new DomainException('Le justificatif doit faire entre 1 octet et 5 Mo.');
        }
        $tmp = (string) ($fichier['tmp'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new DomainException('Justificatif invalide.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $ext = match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => throw new DomainException('Le justificatif doit être un PDF, un JPG ou un PNG.'),
        };
        $dir = STORAGE_PATH . '/justificatifs';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Dossier des justificatifs inaccessible.');
        }
        $nom = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($tmp, $dir . '/' . $nom)) {
            throw new \RuntimeException('Enregistrement du justificatif impossible.');
        }
        return 'storage/justificatifs/' . $nom;
    }

    private static function effacer(?string $chemin): void
    {
        if ($chemin === null) {
            return;
        }
        $reel = self::cheminSecurise($chemin);
        if ($reel !== null) {
            @unlink($reel);
        }
    }

    private static function transactionnel(callable $action): mixed
    {
        $pdo = Database::pdo();
        $interne = !$pdo->inTransaction();
        if ($interne) {
            $pdo->beginTransaction();
        }
        try {
            $resultat = $action();
            if ($interne) {
                $pdo->commit();
                try {
                    CourrierConge::expedier();
                } catch (\Throwable $e) {
                    error_log('Courrier congé : ' . $e->getMessage());
                }
            }
            return $resultat;
        } catch (PDOException $e) {
            if ($interne && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($interne) {
                CourrierConge::annulerFile();
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($interne && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($interne) {
                CourrierConge::annulerFile();
            }
            throw $e;
        }
    }
}
