<?php
// views/surveillant/discipline.php — Registre de Discipline & Vie Scolaire par le Surveillant
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';
require_once __DIR__ . '/../../src/SupervisorService.php';

Auth::requireRole(['surveillant', 'directeur']);

$schoolId = Auth::schoolId();
$classes = AcademicService::getClassesBySchool($schoolId);
$classesByCycle = [
    'Elementaire' => [],
    'College' => [],
    'Lycee' => []
];
foreach ($classes as $c) {
    $cyc = $c['cycle'] ?? 'College';
    if (!isset($classesByCycle[$cyc])) $classesByCycle[$cyc] = [];
    $classesByCycle[$cyc][] = $c;
}

$selectedClassId = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : null;
$selectedStatus = !empty($_GET['status']) ? $_GET['status'] : null;

// Liste des incidents et sanctions
$records = SupervisorService::getDisciplinaryRecords($schoolId, $selectedClassId, $selectedStatus);

// Liste des élèves pour le formulaire
$pdo = getDbConnection();
$stmtStudents = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, sp.student_matricule, c.id as class_id, c.name as class_name, c.cycle, c.track
    FROM `users` u
    JOIN `student_profiles` sp ON u.id = sp.user_id
    JOIN `classes` c ON sp.class_id = c.id
    WHERE u.school_id = ? AND u.role = 'eleve' AND u.is_active = 1
    ORDER BY c.cycle ASC, c.name ASC, u.last_name ASC
");
$stmtStudents->execute([$schoolId]);
$allStudents = $stmtStudents->fetchAll();

$pageTitle = "Registre de Discipline & Vie Scolaire";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⚖️ Registre de Discipline & Vie Scolaire</h1>
        <p class="page-subtitle">Suivi du comportement des élèves, rapports d'incidents, retenues et sanctions</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="document.getElementById('modal-add-discipline').style.display='flex'">
            ➕ Nouveau Rapport d'Incident
        </button>
    </div>
</div>

<!-- Filtres -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="<?= baseUrl('index.php') ?>" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="page" value="surveillant_discipline">

            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="class_id" style="font-size: 13px; font-weight: 700; color: var(--text-main);">🏫 Classe :</label>
                <select name="class_id" id="class_id" class="form-control" onchange="this.form.submit()" style="width: auto;">
                    <option value="">Toutes les classes</option>
                    <?php foreach ($classesByCycle as $cycleName => $cList): ?>
                        <?php if (empty($cList)) continue; ?>
                        <optgroup label="Cycle <?= e($cycleName) ?>">
                            <?php foreach ($cList as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $selectedClassId === $c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?><?= !empty($c['track']) ? ' (Série ' . e($c['track']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="status" style="font-size: 13px; font-weight: 700; color: var(--text-main);">📌 État :</label>
                <select name="status" id="status" class="form-control" onchange="this.form.submit()" style="width: auto;">
                    <option value="">Tous les états</option>
                    <option value="ouvert" <?= $selectedStatus === 'ouvert' ? 'selected' : '' ?>>Ouvert / En cours</option>
                    <option value="traite" <?= $selectedStatus === 'traite' ? 'selected' : '' ?>>Traité / Convoqué</option>
                    <option value="clos" <?= $selectedStatus === 'clos' ? 'selected' : '' ?>>Clos / Sanction exécutée</option>
                </select>
            </div>

            <button type="submit" class="btn btn-outline btn-sm">Filtrer</button>
        </form>
    </div>
</div>

<!-- Tableau du registre -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="card-title">📖 Rapports et Décisions Disciplinaires</h3>
        <span style="font-size: 13px; color: var(--text-muted);">
            Dossiers répertoriés : <strong><?= count($records) ?></strong>
        </span>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($records)): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                <div style="font-size: 32px; margin-bottom: 8px;">🕊️</div>
                <strong>Aucun rapport d'incident disciplinaire répertorié.</strong>
                <p style="font-size: 13px; margin-top: 4px;">Le climat scolaire est serein et conforme au règlement intérieur.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Élève & Classe</th>
                            <th>Motif / Incident</th>
                            <th>Sanction Prononcée</th>
                            <th>Détails & Circonstances</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($r['date'])) ?></td>
                                <td>
                                    <strong><?= e($r['first_name'] . ' ' . $r['last_name']) ?></strong>
                                    <div style="font-size: 11px; color: var(--primary-light); font-weight: 700;">
                                        <?= e($r['class_name']) ?> (<?= e($r['student_matricule']) ?>)
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight: 600; text-transform: capitalize;">
                                        <?= str_replace('_', ' ', e($r['incident_type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $sanctionBadge = 'badge-warning';
                                    if ($r['sanction'] === 'exclusion_temporaire') $sanctionBadge = 'badge-danger';
                                    elseif ($r['sanction'] === 'observation') $sanctionBadge = 'badge-info';
                                    elseif ($r['sanction'] === 'convocation_parent') $sanctionBadge = 'badge-warning';
                                    ?>
                                    <span class="badge <?= $sanctionBadge ?>">
                                        <?= str_replace('_', ' ', strtoupper(e($r['sanction']))) ?>
                                    </span>
                                </td>
                                <td style="font-size: 12px; max-width: 250px;">
                                    <?= nl2br(e($r['details'])) ?>
                                </td>
                                <td>
                                    <?php if ($r['status'] === 'ouvert'): ?>
                                        <span class="badge badge-danger">Ouvert</span>
                                    <?php elseif ($r['status'] === 'traite'): ?>
                                        <span class="badge badge-warning">En traitement</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Clos</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="<?= baseUrl('index.php?action=update_discipline_status') ?>" method="POST" style="display: flex; gap: 4px;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="record_id" value="<?= $r['id'] ?>">
                                        
                                        <select name="status" onchange="this.form.submit()" style="font-size: 11px; padding: 3px 6px; border-radius: 4px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-main);">
                                            <option value="ouvert" <?= $r['status'] === 'ouvert' ? 'selected' : '' ?>>Ouvert</option>
                                            <option value="traite" <?= $r['status'] === 'traite' ? 'selected' : '' ?>>Traité</option>
                                            <option value="clos" <?= $r['status'] === 'clos' ? 'selected' : '' ?>>Clos</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Signalement Incident -->
<div id="modal-add-discipline" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
    <div class="card" style="width: 100%; max-width: 540px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">⚖️ Consigner un Incident Disciplinaire</h3>
            <button type="button" onclick="document.getElementById('modal-add-discipline').style.display='none'" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>
        <div class="card-body">
            <form action="<?= baseUrl('index.php?action=add_disciplinary_record') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label" for="disc_student_id">Élève concerné *</label>
                    <select name="student_id" id="disc_student_id" class="form-control" required>
                        <option value="">-- Sélectionner l'élève --</option>
                        <?php foreach ($allStudents as $st): ?>
                            <option value="<?= $st['id'] ?>">
                                [<?= e($st['class_name']) ?><?= !empty($st['track']) ? ' - ' . e($st['track']) : '' ?>] <?= e($st['last_name']) ?> <?= e($st['first_name']) ?> (<?= e($st['student_matricule']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="disc_date">Date de l'incident *</label>
                        <input type="date" name="date" id="disc_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="incident_type">Nature de l'incident *</label>
                        <select name="incident_type" id="incident_type" class="form-control" required>
                            <option value="indiscipline">Indiscipline / Insolence</option>
                            <option value="retard_repete">Retards répétés</option>
                            <option value="absence_injustifiee">Absence injustifiée</option>
                            <option value="manque_travail">Manque répété de travail</option>
                            <option value="bavardage">Perturbation de cours</option>
                            <option value="degradation">Dégradation de matériel</option>
                            <option value="autre">Autre motif</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="sanction">Sanction ou Mesure Décidée *</label>
                    <select name="sanction" id="sanction" class="form-control" required>
                        <option value="observation">Simple Observation au dossier</option>
                        <option value="avertissement">Avertissement écrit</option>
                        <option value="retenue">Retenue le samedi matin</option>
                        <option value="convocation_parent">Convocation des parents</option>
                        <option value="exclusion_temporaire">Exclusion temporaire (1 à 3 jours)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="details">Détails de l'incident et observations du Surveillant *</label>
                    <textarea name="details" id="details" class="form-control" rows="3" placeholder="Circonstances précises, propos tenus, récidive éventuelle..." required></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-add-discipline').style.display='none'">Annuler</button>
                    <button type="submit" class="btn btn-primary">💾 Consigner l'Incident</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
