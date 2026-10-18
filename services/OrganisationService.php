<?php

require_once __DIR__ . '/../models/Employe.php';
require_once __DIR__ . '/../models/Departement.php';
require_once __DIR__ . '/../models/Poste.php';
require_once __DIR__ . '/../models/Contrat.php';

class OrganisationService
{
    private PDO $pdo;
    private Employe $employes;
    private Departement $departements;
    private Poste $postes;
    private Contrat $contrats;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->employes = new Employe($pdo);
        $this->departements = new Departement($pdo);
        $this->postes = new Poste($pdo);
        $this->contrats = new Contrat($pdo);
    }

    public function lister(
        string $ressource,
        ?int $employeId = null,
        ?string $recherche = null
    ): array
    {
        return match ($ressource) {
            'employes' => $this->employes->lister($recherche),
            'departements' => $this->departements->lister(),
            'postes' => $this->postes->lister(),
            'contrats' => $this->contrats->lister($employeId),
            default => throw new InvalidArgumentException('Ressource inconnue.'),
        };
    }

    public function trouver(string $ressource, int $id): ?array
    {
        return match ($ressource) {
            'employes' => $this->employes->trouver($id),
            'departements' => $this->departements->trouver($id),
            'postes' => $this->postes->trouver($id),
            'contrats' => $this->contrats->trouver($id),
            default => throw new InvalidArgumentException('Ressource inconnue.'),
        };
    }

    public function enregistrer(string $ressource, string $action, array $donnees): int|bool
    {
        $id = $action === 'creer' ? 0 : $this->entier($donnees['id'] ?? null, 'Identifiant');

        return match ($ressource) {
            'employes' => $this->enregistrerEmploye($action, $id, $donnees),
            'departements' => $this->enregistrerDepartement($action, $id, $donnees),
            'postes' => $this->enregistrerPoste($action, $id, $donnees),
            'contrats' => $this->enregistrerContrat($action, $id, $donnees),
            default => throw new InvalidArgumentException('Ressource inconnue.'),
        };
    }

    private function enregistrerEmploye(string $action, int $id, array $donnees): int|bool
    {
        if ($action === 'supprimer') {
            $this->exigerExistant($this->employes->trouver($id));
            $this->pdo->beginTransaction();
            try {
                $this->employes->desactiverCompteLie($id);
                $this->employes->supprimer($id);
                $this->pdo->commit();
            } catch (Throwable $e) {
                $this->pdo->rollBack();
                throw $e;
            }
            return true;
        }

        $employe = [
            'matricule' => $this->texte($donnees, 'matricule', 20),
            'nom' => $this->texte($donnees, 'nom', 100),
            'prenom' => $this->texte($donnees, 'prenom', 100),
            'date_naissance' => $this->dateOptionnelle($donnees['date_naissance'] ?? null, 'Date de naissance'),
            'telephone' => $this->texteOptionnel($donnees['telephone'] ?? null, 30, 'Téléphone'),
            'photo_chemin' => $this->texteOptionnel($donnees['photo_chemin'] ?? null, 255, 'Photo'),
            'date_embauche' => $this->date($donnees['date_embauche'] ?? null, 'Date d’embauche'),
            'statut' => $this->choix($donnees, 'statut', ['actif', 'suspendu', 'sorti']),
            'departement_id' => $this->entier($donnees['departement_id'] ?? null, 'Département'),
            'poste_id' => $this->entier($donnees['poste_id'] ?? null, 'Poste'),
        ];

        if ($action === 'creer') {
            $email = $this->email($donnees['email'] ?? null);
            $motDePasse = $donnees['mot_de_passe'] ?? null;
            if (!is_string($motDePasse) || strlen($motDePasse) < 12) {
                throw new InvalidArgumentException('Le mot de passe initial doit contenir au moins 12 caractères.');
            }
            $role = $donnees['role'] ?? 'employe';
            if (!is_string($role) || !in_array($role, ['employe', 'chef'], true)) {
                throw new InvalidArgumentException('Rôle employé invalide.');
            }

            $this->pdo->beginTransaction();
            try {
                $utilisateurId = $this->employes->creerUtilisateurLie(
                    $email,
                    password_hash($motDePasse, PASSWORD_DEFAULT),
                    $role
                );
                $employe['utilisateur_id'] = $utilisateurId;
                $nouvelId = $this->employes->creer($employe);
                $this->pdo->commit();
                return $nouvelId;
            } catch (Throwable $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        }

        if ($action !== 'modifier') {
            throw new InvalidArgumentException('Action inconnue.');
        }
        $existant = $this->employes->trouver($id);
        $this->exigerExistant($existant);
        $email = $this->email($donnees['email'] ?? null);
        $this->pdo->beginTransaction();
        try {
            $this->employes->modifier($id, $employe);
            $this->employes->modifierEmailUtilisateur($id, $email);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return true;
    }

    private function enregistrerDepartement(string $action, int $id, array $donnees): int|bool
    {
        if ($action === 'supprimer') {
            $this->exigerExistant($this->departements->trouver($id));
            return $this->departements->supprimer($id);
        }
        $departement = [
            'nom' => $this->texte($donnees, 'nom', 100),
            'description' => $this->texteOptionnel($donnees['description'] ?? null, 255, 'Description'),
            'parent_id' => $this->entierOptionnel($donnees['parent_id'] ?? null, 'Département parent'),
            'chef_id' => $this->entierOptionnel($donnees['chef_id'] ?? null, 'Chef'),
        ];
        if ($action === 'creer') {
            return $this->departements->creer($departement);
        }
        if ($action !== 'modifier') {
            throw new InvalidArgumentException('Action inconnue.');
        }
        $this->exigerExistant($this->departements->trouver($id));
        if ($departement['parent_id'] === $id) {
            throw new InvalidArgumentException('Un département ne peut pas être son propre parent.');
        }
        if ($departement['parent_id'] !== null) {
            $parents = [];
            foreach ($this->departements->lister() as $existant) {
                $parents[(int) $existant['id']] = $existant['parent_id'] === null
                    ? null
                    : (int) $existant['parent_id'];
            }
            $parentId = $departement['parent_id'];
            $visites = [];
            while ($parentId !== null) {
                if ($parentId === $id || isset($visites[$parentId])) {
                    throw new InvalidArgumentException('La hiérarchie des départements ne peut pas contenir de cycle.');
                }
                $visites[$parentId] = true;
                $parentId = $parents[$parentId] ?? null;
            }
        }
        $this->departements->modifier($id, $departement);
        return true;
    }

    private function enregistrerPoste(string $action, int $id, array $donnees): int|bool
    {
        if ($action === 'supprimer') {
            $this->exigerExistant($this->postes->trouver($id));
            return $this->postes->supprimer($id);
        }
        $poste = [
            'intitule' => $this->texte($donnees, 'intitule', 100),
            'description' => $this->texteOptionnel($donnees['description'] ?? null, 255, 'Description'),
        ];
        if ($action === 'creer') {
            return $this->postes->creer($poste);
        }
        if ($action !== 'modifier') {
            throw new InvalidArgumentException('Action inconnue.');
        }
        $this->exigerExistant($this->postes->trouver($id));
        $this->postes->modifier($id, $poste);
        return true;
    }

    private function enregistrerContrat(string $action, int $id, array $donnees): int|bool
    {
        if ($action === 'supprimer') {
            $this->exigerExistant($this->contrats->trouver($id));
            return $this->contrats->supprimer($id);
        }

        $type = $this->choix($donnees, 'type', ['CDI', 'CDD', 'stage', 'prestation']);
        $dateDebut = $this->date($donnees['date_debut'] ?? null, 'Date de début');
        $dateFin = $this->dateOptionnelle($donnees['date_fin'] ?? null, 'Date de fin');
        if ($dateFin !== null && $dateFin < $dateDebut) {
            throw new InvalidArgumentException('La date de fin doit être postérieure ou égale à la date de début.');
        }

        $salaire = $this->nombre($donnees['salaire_base'] ?? null, 'Salaire de base');
        $heures = $this->nombre($donnees['heures_mensuelles'] ?? null, 'Heures mensuelles');
        if ($salaire < 0 || $heures <= 0) {
            throw new InvalidArgumentException('Le salaire doit être positif ou nul et les heures mensuelles strictement positives.');
        }
        $contrat = [
            'employe_id' => $this->entier($donnees['employe_id'] ?? null, 'Employé'),
            'type' => $type,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'salaire_base' => $salaire,
            'heures_mensuelles' => $heures,
            'statut' => $this->choix($donnees, 'statut', ['en_cours', 'termine', 'resilie']),
            'fichier_chemin' => $this->texteOptionnel($donnees['fichier_chemin'] ?? null, 255, 'Fichier'),
        ];
        if ($action === 'creer') {
            return $this->contrats->creer($contrat);
        }
        if ($action !== 'modifier') {
            throw new InvalidArgumentException('Action inconnue.');
        }
        $this->exigerExistant($this->contrats->trouver($id));
        $this->contrats->modifier($id, $contrat);
        return true;
    }

    private function texte(array $donnees, string $champ, int $maximum): string
    {
        $valeur = $donnees[$champ] ?? null;
        if (!is_string($valeur) || trim($valeur) === '' || mb_strlen(trim($valeur), 'UTF-8') > $maximum) {
            throw new InvalidArgumentException('Valeur invalide pour « ' . $champ . ' ».');
        }
        return trim($valeur);
    }

    private function texteOptionnel(mixed $valeur, int $maximum, string $libelle): ?string
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }
        if (!is_string($valeur) || mb_strlen(trim($valeur), 'UTF-8') > $maximum) {
            throw new InvalidArgumentException($libelle . ' invalide.');
        }
        return trim($valeur) === '' ? null : trim($valeur);
    }

    private function entier(mixed $valeur, string $libelle): int
    {
        $id = filter_var($valeur, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            throw new InvalidArgumentException($libelle . ' invalide.');
        }
        return $id;
    }

    private function entierOptionnel(mixed $valeur, string $libelle): ?int
    {
        return $valeur === null || $valeur === '' ? null : $this->entier($valeur, $libelle);
    }

    private function nombre(mixed $valeur, string $libelle): float
    {
        if (!is_numeric($valeur)) {
            throw new InvalidArgumentException($libelle . ' invalide.');
        }
        return (float) $valeur;
    }

    private function choix(array $donnees, string $champ, array $possibles): string
    {
        $valeur = $donnees[$champ] ?? null;
        if (!is_string($valeur) || !in_array($valeur, $possibles, true)) {
            throw new InvalidArgumentException('Valeur invalide pour « ' . $champ . ' ».');
        }
        return $valeur;
    }

    private function email(mixed $valeur): string
    {
        if (!is_string($valeur) || filter_var(trim($valeur), FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Adresse e-mail invalide.');
        }
        return trim($valeur);
    }

    private function date(mixed $valeur, string $libelle): string
    {
        if (!is_string($valeur)) {
            throw new InvalidArgumentException($libelle . ' invalide.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);
        if ($date === false || $date->format('Y-m-d') !== $valeur) {
            throw new InvalidArgumentException($libelle . ' invalide.');
        }
        return $valeur;
    }

    private function dateOptionnelle(mixed $valeur, string $libelle): ?string
    {
        return $valeur === null || $valeur === '' ? null : $this->date($valeur, $libelle);
    }

    private function exigerExistant(?array $ressource): void
    {
        if ($ressource === null) {
            throw new OutOfBoundsException('Élément introuvable.');
        }
    }
}
