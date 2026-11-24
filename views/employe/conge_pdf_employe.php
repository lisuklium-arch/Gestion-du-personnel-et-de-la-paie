<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../models/Employe.php';
require_once __DIR__ . '/../../services/DemandeCongeService.php';
require_once __DIR__ . '/../layout/conges_helpers.php';
exigerRole(['employe', 'chef', 'rh', 'dg']);

$svc = new DemandeCongeService($pdo);
$id = (int) ($_GET['id'] ?? 0);
$d = $svc->trouver($id);
if ($d === null) { http_response_code(404); exit('Demande introuvable.'); }
$uid = (int) $_SESSION['utilisateur_id'];
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['rh', 'dg', 'chef'], true) && (int)($d['employe_utilisateur_id'] ?? 0) !== $uid) {
    http_response_code(403); exit('Accès refusé.');
}
$vals = $svc->validationsDe($id);
[$lib] = conge_statut_badge($d['statut']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Demande de congé #<?= (int)$d['id'] ?></title>
  <style>
    body{font-family:system-ui,sans-serif;max-width:800px;margin:2rem auto;color:#202938}
    h1{font-size:1.25rem;margin:0 0 .5rem}
    table{width:100%;border-collapse:collapse;margin:1rem 0}
    th,td{border:1px solid #DCE1E7;padding:.5rem;text-align:left;font-size:.9rem}
    th{background:#F4F7F6}
    .meta{color:#606B7A;font-size:.85rem}
    @media print{.no-print{display:none}}
  </style>
</head>
<body>
  <p class="no-print"><button onclick="window.print()">Imprimer / Enregistrer en PDF</button></p>
  <h1>PerPay — Demande de congé n°<?= (int)$d['id'] ?></h1>
  <p class="meta">Statut : <?= h($lib) ?> · Édité le <?= h(date('d/m/Y H:i')) ?></p>
  <table>
    <tr><th>Employé</th><td><?= h($d['prenom'].' '.$d['nom']) ?> (<?= h($d['matricule']) ?>)</td></tr>
    <tr><th>Département / Poste</th><td><?= h($d['departement'] ?? '') ?> · <?= h($d['poste'] ?? '') ?></td></tr>
    <tr><th>Type</th><td><?= h($d['type_libelle']) ?></td></tr>
    <tr><th>Période</th><td><?= h(conge_fmt_date($d['date_debut'])) ?> – <?= h(conge_fmt_date($d['date_fin'])) ?> (<?= h(conge_fmt_nb($d['nb_jours'])) ?> j.)</td></tr>
    <tr><th>Motif</th><td><?= h($d['motif'] ?: '—') ?></td></tr>
    <tr><th>Contact</th><td><?= h($d['contact_urgence'] ?? '—') ?></td></tr>
    <tr><th>Soumise le</th><td><?= h($d['date_soumission']) ?></td></tr>
  </table>
  <h2 style="font-size:1rem">Décisions</h2>
  <?php if ($vals === []): ?><p class="meta">Aucune décision.</p>
  <?php else: ?>
  <table>
    <tr><th>Étape</th><th>Décision</th><th>Par</th><th>Date</th><th>Commentaire</th></tr>
    <?php foreach ($vals as $v): ?>
    <tr>
      <td><?= h($v['etape']) ?></td>
      <td><?= h($v['decision']) ?></td>
      <td><?= h(trim(($v['decideur_prenom']??'').' '.($v['decideur_nom']??'')) ?: $v['email']) ?></td>
      <td><?= h($v['date_decision']) ?></td>
      <td><?= h($v['commentaire'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</body>
</html>
