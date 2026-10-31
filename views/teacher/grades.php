<?php
// views/teacher/grades.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['professeur']);

$teacherId = Auth::id();
$schoolId = Auth::schoolId();
$teacherCourses = AcademicService::getTeacherClassesAndSubjects($schoolId, $teacherId);
$pageTitle = "Saisie des Notes & Appréciations";

$selectedCsId = isset($_GET['class_subject_id']) ? (int)$_GET['class_subject_id'] : ($teacherCourses[0]['id'] ?? 0);
$selectedEvalId = isset($_GET['eval_id']) ? (int)$_GET['eval_id'] : 0;

$pdo = getDbConnection();

// Récupérer les évaluations pour ce cours
$stmtEvals = $pdo->prepare("
    SELECT * FROM `evaluations` 
    WHERE `class_subject_id` = ? AND `school_id` = ?
    ORDER BY `semester` ASC, `eval_date` DESC
");
$stmtEvals->execute([$selectedCsId, $schoolId]);
$evaluations = $stmtEvals->fetchAll();

if (!$selectedEvalId && !empty($evaluations)) {
    $selectedEvalId = (int)$evaluations[0]['id'];
}

$evaluationDetails = $selectedEvalId ? AcademicService::getEvaluationDetails($schoolId, $selectedEvalId) : null;

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">✍️ Saisie des Notes & Appréciations</h1>
        <p class="page-subtitle">Saisissez les notes d'évaluations et les commentaires individuels pour vos élèves</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openModal('modal-new-eval')">
            ➕ Créer une Nouvelle Évaluation
        </button>
    </div>
</div>

<!-- Modal Nouvelle Évaluation -->
<div id="modal-new-eval" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:2000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:var(--radius-lg); width:100%; max-width:540px; padding:24px; box-shadow:var(--shadow-lg);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="font-size:18px; font-weight:800;">📝 Créer une Nouvelle Évaluation</h3>
            <button onclick="closeModal('modal-new-eval')" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>

        <form action="<?= baseUrl('index.php?action=create_evaluation') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="form-group">
                <label class="form-label" for="cs_id_input">Classe & Matière *</label>
                <select name="class_subject_id" id="cs_id_input" class="form-control" required>
                    <?php foreach ($teacherCourses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $selectedCsId ? 'selected' : '' ?>>
                            <?= e($c['class_name']) ?> — <?= e($c['subject_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="eval_title">Intitulé de l'Évaluation *</label>
                <input type="text" name="title" id="eval_title" class="form-control" placeholder="ex: Devoir Surveillé n°2 ou Composition du 1er Semestre" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="eval_type">Type d'Épreuve *</label>
                    <select name="eval_type" id="eval_type" class="form-control">
                        <option value="devoir">Devoir Surveillé</option>
                        <option value="composition">Composition Harmonisée</option>
                        <option value="interrogation">Interrogation / Contrôle continu</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="semester">Semestre *</label>
                    <select name="semester" id="semester" class="form-control">
                        <option value="Semestre 1">Semestre 1</option>
                        <option value="Semestre 2">Semestre 2</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="weight">Coefficient de l'Épreuve *</label>
                    <input type="number" step="0.5" name="weight" id="weight" class="form-control" value="1.0" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="eval_date">Date de l'Épreuve *</label>
                    <input type="date" name="eval_date" id="eval_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-new-eval')">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer & Commencer la Saisie</button>
            </div>
        </form>
    </div>
</div>

<!-- Sélecteur de Cours et d'Évaluation -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="<?= baseUrl('index.php') ?>" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="page" value="teacher_grades">

            <div style="flex: 1; min-width: 220px;">
                <label class="form-label" style="margin-bottom: 4px;">Classe & Matière :</label>
                <select name="class_subject_id" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($teacherCourses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $selectedCsId ? 'selected' : '' ?>>
                            <?= e($c['class_name']) ?> — <?= e($c['subject_name']) ?> (Coef <?= $c['coefficient'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 220px;">
                <label class="form-label" style="margin-bottom: 4px;">Sélectionner l'Évaluation :</label>
                <select name="eval_id" class="form-control" onchange="this.form.submit()">
                    <?php if (empty($evaluations)): ?>
                        <option value="">Aucune évaluation créée pour ce cours</option>
                    <?php else: ?>
                        <?php foreach ($evaluations as $ev): ?>
                            <option value="<?= $ev['id'] ?>" <?= $ev['id'] == $selectedEvalId ? 'selected' : '' ?>>
                                [<?= e($ev['semester']) ?>] <?= e($ev['title']) ?> (du <?= formatDate($ev['eval_date']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-outline btn-sm" style="height: 42px;">
                🔄 Charger
            </button>
        </form>
    </div>
</div>

<!-- Grille de Saisie des Notes -->
<?php if ($evaluationDetails): ?>
    <div class="card">
        <div class="card-header" style="background: var(--bg-table-header);">
            <div>
                <span class="badge badge-info" style="margin-bottom: 4px;"><?= e($evaluationDetails['semester']) ?></span>
                <h3 class="card-title" style="font-size: 17px;"><?= e($evaluationDetails['title']) ?></h3>
                <div style="font-size: 12px; color: var(--text-muted);">
                    Classe : <strong><?= e($evaluationDetails['class_name']) ?></strong> &bullet; 
                    Date : <strong><?= formatDate($evaluationDetails['eval_date']) ?></strong> &bullet; 
                    Pondération : <strong>x<?= $evaluationDetails['weight'] ?></strong> &bullet; 
                    Barème : <strong>/ <?= $evaluationDetails['max_score'] ?></strong>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form action="<?= baseUrl('index.php?action=save_grades') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="eval_id" value="<?= $evaluationDetails['id'] ?>">
                <input type="hidden" name="class_subject_id" value="<?= $selectedCsId ?>">

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">N°</th>
                                <th style="width: 140px;">Matricule</th>
                                <th>Nom & Prénom de l'Élève</th>
                                <th style="width: 130px;">Note (/20)</th>
                                <th style="width: 110px;">Absent</th>
                                <th>Appréciation Pédagogique</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($evaluationDetails['students'])): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                        Aucun élève inscrit dans cette classe.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $i = 1; foreach ($evaluationDetails['students'] as $st): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><span class="badge badge-secondary"><?= e($st['student_matricule']) ?></span></td>
                                        <td><strong><?= e($st['last_name']) ?></strong> <?= e($st['first_name']) ?></td>
                                        <td>
                                            <input type="number" step="0.25" min="0" max="20" 
                                                   name="grades[<?= $st['student_id'] ?>][score]" 
                                                   value="<?= $st['score'] !== null ? e($st['score']) : '' ?>" 
                                                   class="form-control" style="font-weight: 800; font-size: 15px;" placeholder="/ 20">
                                        </td>
                                        <td style="text-align: center;">
                                            <label style="display: flex; align-items: center; justify-content: center; gap: 4px; font-size: 12px; cursor: pointer;">
                                                <input type="checkbox" name="grades[<?= $st['student_id'] ?>][is_absent]" value="1" <?= $st['is_absent'] ? 'checked' : '' ?>>
                                                <span>Absent</span>
                                            </label>
                                        </td>
                                        <td>
                                            <input type="text" name="grades[<?= $st['student_id'] ?>][appreciation]" 
                                                   value="<?= e($st['appreciation'] ?? '') ?>" 
                                                   class="form-control" placeholder="ex: Bon travail, rigueur dans le raisonnement...">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                    <button type="submit" class="btn btn-primary btn-lg">
                        💾 Valider & Enregistrer Toutes les Notes
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 50px;">
            <div style="font-size: 40px; margin-bottom: 12px;">📝</div>
            <h3 style="font-size: 16px; font-weight: 700;">Aucune évaluation sélectionnée</h3>
            <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">
                Cliquez sur le bouton ci-dessous pour créer une nouvelle épreuve (devoir ou composition) pour cette classe.
            </p>
            <button class="btn btn-primary" onclick="openModal('modal-new-eval')">
                ➕ Créer une Évaluation Maintenant
            </button>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
