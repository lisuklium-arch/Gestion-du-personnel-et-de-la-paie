<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../services/OrganisationService.php';

header('Content-Type: application/json; charset=utf-8');

function organisationRepondre(int $code, array $contenu): never
{
    http_response_code($code);
    echo json_encode($contenu, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$ressource = $_GET['ressource'] ?? $_POST['ressource'] ?? '';
if (!is_string($ressource)) {
    organisationRepondre(400, ['ok' => false, 'message' => 'Ressource invalide.']);
}

exigerRole($ressource === 'equipe' ? ['chef', 'rh'] : ['rh'], true);
$service = new OrganisationService($pdo);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($ressource === 'equipe') {
            $chefId = filter_var($_GET['chef_id'] ?? null, FILTER_VALIDATE_INT);
            if (roleCourant() === 'chef') {
                $employe = (new Employe($pdo))->trouverParUtilisateurId((int) $_SESSION['utilisateur_id']);
                $chefId = $employe === null ? false : (int) $employe['id'];
            }
            if ($chefId === false || $chefId === null || $chefId < 1) {
                organisationRepondre(400, ['ok' => false, 'message' => 'Chef invalide.']);
            }
            organisationRepondre(200, [
                'ok' => true,
                'data' => (new Employe($pdo))->equipeDuChef((int) $chefId),
            ]);
        }

        $idBrut = $_GET['id'] ?? null;
        if ($idBrut !== null) {
            $id = filter_var($idBrut, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) {
                organisationRepondre(400, ['ok' => false, 'message' => 'Identifiant invalide.']);
            }
            $element = $service->trouver($ressource, $id);
            if ($element === null) {
                organisationRepondre(404, ['ok' => false, 'message' => 'Élément introuvable.']);
            }
            organisationRepondre(200, ['ok' => true, 'data' => $element]);
        }

        $employeIdBrut = $_GET['employe_id'] ?? null;
        $employeId = null;
        if ($employeIdBrut !== null) {
            $employeId = filter_var($employeIdBrut, FILTER_VALIDATE_INT);
            if ($employeId === false || $employeId < 1) {
                organisationRepondre(400, ['ok' => false, 'message' => 'Identifiant employé invalide.']);
            }
        }
        $recherche = $_GET['q'] ?? null;
        if ($recherche !== null && !is_string($recherche)) {
            organisationRepondre(400, ['ok' => false, 'message' => 'Recherche invalide.']);
        }
        organisationRepondre(200, [
            'ok' => true,
            'data' => $service->lister($ressource, $employeId, $recherche),
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        organisationRepondre(405, ['ok' => false, 'message' => 'Méthode non autorisée.']);
    }

    csrfExiger();
    $typeContenu = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($typeContenu, 'application/json') !== false) {
        $corps = file_get_contents('php://input');
        $donnees = json_decode($corps === false ? '' : $corps, true);
        if (!is_array($donnees)) {
            organisationRepondre(400, ['ok' => false, 'message' => 'Corps JSON invalide.']);
        }
    } else {
        $donnees = $_POST;
    }

    $action = $donnees['action'] ?? null;
    if (!is_string($action)) {
        organisationRepondre(400, ['ok' => false, 'message' => 'Action invalide.']);
    }
    $resultat = $service->enregistrer($ressource, $action, $donnees);
    organisationRepondre(200, ['ok' => true, 'data' => $resultat]);
} catch (InvalidArgumentException $e) {
    organisationRepondre(400, ['ok' => false, 'message' => $e->getMessage()]);
} catch (OutOfBoundsException $e) {
    organisationRepondre(404, ['ok' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    error_log('Erreur PDO dans api/organisation.php : ' . $e->getMessage());
    if ($e->getCode() === '23000') {
        organisationRepondre(409, [
            'ok' => false,
            'message' => 'Opération impossible : une valeur existe déjà ou des données liées empêchent cette modification.',
        ]);
    }
    organisationRepondre(500, ['ok' => false, 'message' => 'Erreur lors de l’accès aux données.']);
} catch (Throwable $e) {
    error_log('Erreur dans api/organisation.php : ' . $e->getMessage());
    organisationRepondre(500, ['ok' => false, 'message' => 'Erreur interne.']);
}
