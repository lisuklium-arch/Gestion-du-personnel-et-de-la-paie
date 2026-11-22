<?php

require_once __DIR__ . '/../models/Employe.php';
require_once __DIR__ . '/../models/Contrat.php';
require_once __DIR__ . '/CompteService.php';
require_once __DIR__ . '/JournalService.php';

class NouvelEmployeService
{
    private const TYPES_CONTRAT = ['CDI', 'CDD', 'stage', 'prestation'];
    private const TAILLE_MAX_CONTRAT = 5 * 1024 * 1024;

    private PDO $pdo;
    private CompteService $comptes;
    private Employe $employes;
    private Contrat $contrats;
    private JournalService $journalService;
    private string $dossierContrats;

    public function __construct(
        PDO $pdo,
        CompteService $comptes,
        Employe $employes,
        Contrat $contrats,
        JournalService $journalService,
        string $dossierContrats
    ) {
        $this->pdo = $pdo;
        $this->comptes = $comptes;
        $this->employes = $employes;
        $this->contrats = $contrats;
        $this->journalService = $journalService;
        $this->dossierContrats = rtrim($dossierContrats, '/\\');
    }

    public function creer(array $auteur, array $saisie, ?array $fichier = null): array
    {
        $erreur = null;
        $d = $this->controler($saisie, $erreur);
        if ($d === null) {
            return ['ok' => false, 'message' => $erreur];
        }

        $fichierContrat = $this->controlerFichier($fichier, $erreur);
        if ($erreur !== null) {
            return ['ok' => false, 'message' => $erreur];
        }

        if ($this->employes->matriculeExiste($d['matricule'])) {
            return ['ok' => false, 'message' => 'Ce matricule est déjà utilisé.'];
        }

        $cheminAbsolu = null;
        $this->pdo->beginTransaction();

        try {
            $compte = $this->comptes->creer($auteur, $d['email'], $d['role']);
            if (!$compte['ok']) {
                $this->pdo->rollBack();
                return $compte;
            }

            $employeId = $this->employes->creer($compte['utilisateur_id'], $d);

            $d['fichier_chemin'] = null;
            if ($fichierContrat !== null) {
                $nom = bin2hex(random_bytes(16)) . '.pdf';
                $cheminAbsolu = $this->dossierContrats . DIRECTORY_SEPARATOR . $nom;
                if (!move_uploaded_file($fichierContrat, $cheminAbsolu)) {
                    throw new RuntimeException("Le fichier du contrat n'a pas pu être enregistré.");
                }
                $d['fichier_chemin'] = 'contrats/' . $nom;
            }

            $this->contrats->creer($employeId, $d);

            $this->journalService->enregistrer((int) $auteur['id'], 'creation_employe', 'employe', $employeId, null, [
                'matricule' => $d['matricule'],
                'departement_id' => $d['departement_id'],
                'poste_id' => $d['poste_id'],
                'contrat' => $d['type_contrat'],
            ]);

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->annuler($cheminAbsolu);
            if ($e->getCode() === '23000') {
                return ['ok' => false, 'message' => 'Département, poste, matricule ou e-mail invalide ou déjà utilisé.'];
            }
            throw $e;
        } catch (Throwable $e) {
            $this->annuler($cheminAbsolu);
            throw $e;
        }

        $accesEnvoyes = null;
        if ($compte['mot_de_passe_temporaire'] !== null) {
            $accesEnvoyes = $this->comptes->envoyerAcces($compte['utilisateur_id'], $compte['mot_de_passe_temporaire']);
        }

        return [
            'ok' => true,
            'message' => $accesEnvoyes === false
                ? "Dossier et compte créés, mais l'e-mail d'accès n'a pas pu être envoyé."
                : $compte['message'],
            'utilisateur_id' => $compte['utilisateur_id'],
            'employe_id' => $employeId,
            'statut' => $compte['statut'],
        ];
    }

    private function annuler(?string $cheminAbsolu): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        if ($cheminAbsolu !== null && is_file($cheminAbsolu)) {
            unlink($cheminAbsolu);
        }
    }

    private function controler(array $s, ?string &$erreur): ?array
    {
        $texte = fn(string $k): string => is_string($s[$k] ?? null) ? trim($s[$k]) : '';

        $nom = $texte('nom');
        $prenom = $texte('prenom');
        if ($nom === '' || $prenom === '' || mb_strlen($nom) > 100 || mb_strlen($prenom) > 100) {
            $erreur = 'Nom et prénom sont obligatoires (100 caractères maximum).';
            return null;
        }

        $naissance = $this->date($texte('naissance'));
        if ($naissance === null || $naissance > date('Y-m-d')) {
            $erreur = 'Date de naissance invalide.';
            return null;
        }

        $telephone = $texte('telephone');
        if (!preg_match('/^\+?[0-9 ]{8,20}$/', $telephone)) {
            $erreur = 'Numéro de téléphone invalide.';
            return null;
        }

        $matricule = $texte('matricule');
        if (!preg_match('/^EMP-[0-9]{4}$/', $matricule)) {
            $erreur = 'Matricule invalide (format EMP-0000).';
            return null;
        }

        $departementId = filter_var($texte('departement_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $posteId = filter_var($texte('poste_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($departementId === false || $posteId === false) {
            $erreur = 'Choisissez un département et un poste.';
            return null;
        }

        $role = $texte('role');
        if (!in_array($role, ['employe', 'chef', 'rh'], true)) {
            $erreur = 'Choisissez un rôle.';
            return null;
        }

        $type = $texte('type_contrat');
        if (!in_array($type, self::TYPES_CONTRAT, true)) {
            $erreur = 'Choisissez un type de contrat.';
            return null;
        }

        $debut = $this->date($texte('date_debut'));
        if ($debut === null) {
            $erreur = 'Date de début de contrat invalide.';
            return null;
        }

        $fin = null;
        if ($type !== 'CDI') {
            $fin = $this->date($texte('date_fin'));
            if ($fin === null || $fin < $debut) {
                $erreur = 'La date de fin est obligatoire hors CDI et doit suivre la date de début.';
                return null;
            }
        }

        $salaire = $texte('salaire_base');
        if (!preg_match('/^[0-9]{1,12}$/', $salaire)) {
            $erreur = 'Salaire de base invalide.';
            return null;
        }

        $heures = $texte('heures_mensuelles');
        if (!is_numeric($heures) || (float) $heures < 1 || (float) $heures > 300) {
            $erreur = 'Heures mensuelles invalides (entre 1 et 300).';
            return null;
        }

        return [
            'nom' => $nom,
            'prenom' => $prenom,
            'naissance' => $naissance,
            'email' => $texte('email'),
            'telephone' => $telephone,
            'matricule' => $matricule,
            'departement_id' => $departementId,
            'poste_id' => $posteId,
            'role' => $role,
            'type_contrat' => $type,
            'date_debut' => $debut,
            'date_embauche' => $debut,
            'date_fin' => $fin,
            'salaire_base' => (int) $salaire,
            'heures_mensuelles' => round((float) $heures, 2),
        ];
    }

    private function controlerFichier(?array $f, ?string &$erreur): ?string
    {
        $erreur = null;
        if ($f === null || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $erreur = ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE)
                ? 'Fichier trop volumineux (5 Mo maximum).'
                : "Le fichier du contrat n'a pas pu être reçu.";
            return null;
        }
        if ($f['size'] > self::TAILLE_MAX_CONTRAT) {
            $erreur = 'Fichier trop volumineux (5 Mo maximum).';
            return null;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if ($mime !== 'application/pdf') {
            $erreur = 'Le contrat signé doit être un fichier PDF.';
            return null;
        }
        return $f['tmp_name'];
    }

    private function date(string $valeur): ?string
    {
        $date = DateTime::createFromFormat('!Y-m-d', $valeur);
        return ($date !== false && $date->format('Y-m-d') === $valeur) ? $valeur : null;
    }
}
