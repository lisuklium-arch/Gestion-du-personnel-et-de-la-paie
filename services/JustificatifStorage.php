<?php
/**
 * Stockage serveur des justificatifs (congés / absences).
 * Accessible depuis n'importe quel poste : les fichiers vivent sous storage/justificatifs/
 * et sont servis via public/telecharger_justificatif.php (jamais en accès direct URL).
 */
class JustificatifStorage
{
    public const CONGES = 'conges';
    public const ABSENCES = 'absences';

    private string $racine;

    public function __construct(?string $racineProjet = null)
    {
        $base = $racineProjet ?? dirname(__DIR__);
        $this->racine = $base . '/storage/justificatifs';
    }

    /**
     * Enregistre un upload. Retourne le chemin relatif stocké en base
     * (ex. justificatifs/conges/12_a1b2c3.pdf) ou null.
     */
    public function enregistrer(array $fichier, string $categorie, int $refId): ?string
    {
        if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($fichier['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Échec du téléversement du justificatif.');
        }
        if (!in_array($categorie, [self::CONGES, self::ABSENCES], true)) {
            throw new InvalidArgumentException('Catégorie de justificatif invalide.');
        }
        $tmp = (string) ($fichier['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Fichier upload invalide.');
        }
        $taille = (int) ($fichier['size'] ?? 0);
        if ($taille <= 0 || $taille > 5 * 1024 * 1024) {
            throw new RuntimeException('Justificatif trop volumineux (max. 5 Mo).');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        $exts = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($exts[$mime])) {
            throw new RuntimeException('Format non accepté (PDF, JPG, PNG, WEBP).');
        }
        $dir = $this->racine . '/' . $categorie;
        if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
            throw new RuntimeException('Impossible de créer le dossier de stockage.');
        }
        $nom = $refId . '_' . bin2hex(random_bytes(8)) . '.' . $exts[$mime];
        $cible = $dir . '/' . $nom;
        if (!move_uploaded_file($tmp, $cible)) {
            throw new RuntimeException('Enregistrement du justificatif impossible.');
        }
        @chmod($cible, 0640);
        return 'justificatifs/' . $categorie . '/' . $nom;
    }

    /** Chemin absolu sécurisé, ou null si hors zone / absent. */
    public function cheminAbsolu(string $relatif): ?string
    {
        $relatif = str_replace('\\', '/', $relatif);
        if (!preg_match('#^justificatifs/(conges|absences)/[A-Za-z0-9._-]+$#', $relatif)) {
            return null;
        }
        $base = realpath($this->racine);
        $reel = realpath(dirname($this->racine) . '/' . $relatif);
        if ($base === false || $reel === false || !is_file($reel)) {
            return null;
        }
        if (!str_starts_with($reel, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $reel;
    }
}
