<?php

require_once __DIR__ . '/../models/DemandeDocument.php';
require_once __DIR__ . '/../models/DocumentEmploye.php';
require_once __DIR__ . '/JournalService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class AttestationService
{
    public function __construct(
        private PDO $pdo,
        private DemandeDocument $demandes,
        private DocumentEmploye $documents,
        private JournalService $journal,
        private NotificationService $notifications,
        private string $dossierDocuments
    ) {
        $this->dossierDocuments = rtrim($dossierDocuments, '/\\');
    }

    public function traiter(int $rhId, int $demandeId, string $decision): array
    {
        if (!in_array($decision, ['generee', 'refusee'], true)) return ['ok' => false, 'message' => 'Décision invalide.'];
        $demande = $this->demandes->trouverEnAttente($demandeId);
        if ($demande === null) return ['ok' => false, 'message' => 'Cette demande n’est plus disponible.'];

        $fichier = null;
        try {
            $this->pdo->beginTransaction();
            $documentId = null;
            if ($decision === 'generee') {
                $nomInterne = bin2hex(random_bytes(16)) . '.docx';
                $fichier = $this->dossierDocuments . DIRECTORY_SEPARATOR . $nomInterne;
                $nomOriginal = $this->genererWord($demande, $fichier);
                $documentId = $this->documents->creer((int) $demande['employe_id'], 'attestation', $nomOriginal, 'documents/' . $nomInterne, $rhId);
            }
            if (!$this->demandes->traiter($demandeId, $rhId, $decision, $documentId)) throw new RuntimeException('La demande a déjà été traitée.');
            $this->journal->enregistrer($rhId, 'traitement_demande_attestation', 'demande_document', $demandeId, ['statut' => 'en_attente'], ['statut' => $decision]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            if ($fichier !== null && is_file($fichier)) unlink($fichier);
            error_log('Attestation Word, demande #' . $demandeId . ' : ' . $e->getMessage());
            return ['ok' => false, 'message' => $e->getMessage() === 'La demande a déjà été traitée.' ? $e->getMessage() : 'La génération de l’attestation a échoué.'];
        }
        $libelle = $demande['type'] === 'attestation_salaire' ? 'attestation de salaire' : 'attestation de travail';
        $this->notifications->notifier((int) $demande['utilisateur_id'], 'demande_attestation_' . $decision,
            $decision === 'generee' ? 'Attestation disponible' : 'Demande d’attestation refusée',
            $decision === 'generee' ? 'Votre ' . $libelle . ' est disponible dans votre coffre-fort documentaire.' : 'Votre demande de ' . $libelle . ' a été refusée.',
            '/views/employe/documents_employe.php');
        return ['ok' => true, 'message' => $decision === 'generee' ? 'Attestation Word générée et mise à disposition.' : 'Demande d’attestation refusée.'];
    }

    private function genererWord(array $d, string $fichier): string
    {
        $type = $d['type'] === 'attestation_salaire' ? 'ATTESTATION DE SALAIRE' : 'ATTESTATION DE TRAVAIL';
        $word = new PhpWord();
        $section = $word->addSection(['marginTop' => 1000, 'marginLeft' => 1200, 'marginRight' => 1200]);
        $section->addText('PERPAY', ['bold' => true, 'size' => 16]);
        $section->addText('Direction des Ressources Humaines');
        $section->addTextBreak(2);
        $section->addText($type, ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        $section->addTextBreak(2);
        $nom = $d['prenom'] . ' ' . $d['nom'];
        $section->addText('Nous soussignés, Direction des Ressources Humaines de PerPay, attestons que :');
        $section->addText($nom, ['bold' => true], ['alignment' => 'center']);
        $section->addText('matricule ' . $d['matricule'] . ', occupe le poste de ' . $d['poste'] . ' au sein du département ' . $d['departement'] . '.');
        if ($d['type'] === 'attestation_salaire') $section->addText('La présente attestation est établie à sa demande pour servir et valoir ce que de droit.');
        else $section->addText('La présente attestation de travail est établie à sa demande pour servir et valoir ce que de droit.');
        $section->addTextBreak(3);
        $section->addText('Fait le ' . date('d/m/Y') . '.', [], ['alignment' => 'right']);
        $section->addText('La Direction des Ressources Humaines', ['bold' => true], ['alignment' => 'right']);
        IOFactory::createWriter($word, 'Word2007')->save($fichier);
        return ($d['type'] === 'attestation_salaire' ? 'Attestation_salaire_' : 'Attestation_travail_') . $d['matricule'] . '.docx';
    }
}
