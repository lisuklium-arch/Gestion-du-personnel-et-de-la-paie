<?php
require_once __DIR__ . '/../models/Employe.php';
require_once __DIR__ . '/JournalService.php';

class PhotoProfilService
{
    public function __construct(private PDO $pdo, private Employe $employes, private JournalService $journal, private string $dossier)
    {
        $this->dossier = rtrim($dossier, '/\\');
    }

    public function remplacer(int $utilisateurId, ?array $fichier): array
    {
        $employe = $this->employes->trouverParUtilisateurId($utilisateurId);
        if ($employe === null) return ['ok' => false, 'message' => 'Dossier employé introuvable.'];
        $controle = $this->controler($fichier, true, $erreur);
        if ($controle === null) return ['ok' => false, 'message' => $erreur];
        $ancien = (string) (($this->employes->trouver((int) $employe['id']))['photo_chemin'] ?? '');
        $nom = bin2hex(random_bytes(16)) . '.' . $controle['extension'];
        $nouveau = $this->dossier . DIRECTORY_SEPARATOR . $nom;
        if (!move_uploaded_file($controle['temporaire'], $nouveau)) return ['ok' => false, 'message' => 'La photo n’a pas pu être enregistrée.'];
        try {
            $this->employes->definirPhoto((int) $employe['id'], 'documents/' . $nom);
            $this->pdo->prepare('UPDATE rappel_photo_profil SET complete_le = NOW(), masque_jusqua = NULL WHERE employe_id = :id')->execute([':id' => $employe['id']]);
            $this->pdo->prepare("UPDATE notification SET lue = 1 WHERE utilisateur_id = :utilisateur_id AND type = 'photo_profil_requise'")->execute([':utilisateur_id' => $utilisateurId]);
            $this->journal->enregistrer($utilisateurId, 'modification_photo_profil', 'employe', (int) $employe['id'], ['photo' => $ancien !== ''], ['photo' => true]);
            $this->supprimerAncienne($ancien);
        } catch (Throwable $e) { if (is_file($nouveau)) unlink($nouveau); return ['ok' => false, 'message' => 'La photo n’a pas pu être mise à jour.']; }
        return ['ok' => true, 'message' => 'Votre photo de profil a été mise à jour.', 'version' => $nom];
    }

    public function controler(?array $fichier, bool $obligatoire, ?string &$erreur): ?array
    {
        $erreur = null;
        if ($fichier === null || ($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { if ($obligatoire) $erreur = 'Une photo de profil est obligatoire.'; return null; }
        if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($fichier['size'] ?? 0) > 2 * 1024 * 1024) { $erreur = 'La photo est invalide ou dépasse 2 Mo.'; return null; }
        $image = @getimagesize($fichier['tmp_name']); $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if ($image === false || !isset($extensions[$image['mime'] ?? ''])) { $erreur = 'La photo doit être une image JPG ou PNG valide.'; return null; }
        return ['temporaire' => $fichier['tmp_name'], 'extension' => $extensions[$image['mime']]];
    }

    private function supprimerAncienne(string $chemin): void { if (!preg_match('#^documents/[a-f0-9]{32}\.(jpg|png)$#', $chemin)) return; $fichier = $this->dossier . DIRECTORY_SEPARATOR . basename($chemin); if (is_file($fichier)) unlink($fichier); }
}
