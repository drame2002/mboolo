<?php
// views/student/grades.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['eleve']);

$studentId = Auth::id();
$schoolId = Auth::schoolId();
$semester = $_GET['semester'] ?? 'Semestre 1';

$data = AcademicService::getStudentGradesOverview($schoolId, $studentId, $semester);
$student = $data['student'] ?? Auth::user();
$pageTitle = "Suivi des Notes & Résultats Scolaires";

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📈 Suivi des Notes & Résultats</h1>
        <p class="page-subtitle">
            Relevé des évaluations et moyennes &bullet; 
            Classe : <strong><?= e($student['class_name'] ?? 'Classe') ?></strong>
            <?php if (($student['cycle'] ?? '') === 'Elementaire'): ?>
                &bullet; <span class="badge badge-success" style="font-size: 11px;">🎒 Élémentaire</span>
            <?php elseif (($student['cycle'] ?? '') === 'College'): ?>
                &bullet; <span class="badge badge-warning" style="font-size: 11px;">🏫 Collège</span>
            <?php else: ?>
                &bullet; <span class="badge badge-info" style="font-size: 11px;">🎓 Lycée</span>
                <?php if (!empty($student['track'])): ?>
                    &bullet; Série : <strong style="color: var(--primary-light);"><?= e($student['track']) ?></strong>
                <?php endif; ?>
            <?php endif; ?>
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <form method="GET" action="<?= baseUrl('index.php') ?>" style="display: flex; gap: 8px;">
            <input type="hidden" name="page" value="student_grades">
            <select name="semester" class="form-control" onchange="this.form.submit()" style="font-weight: 700;">
                <option value="Semestre 1" <?= $semester === 'Semestre 1' ? 'selected' : '' ?>>Semestre 1</option>
                <option value="Semestre 2" <?= $semester === 'Semestre 2' ? 'selected' : '' ?>>Semestre 2</option>
            </select>
        </form>
        <a href="<?= baseUrl('index.php?page=student_bulletin&semester=' . urlencode($semester)) ?>" class="btn btn-primary btn-sm">
            📜 Bulletin Officiel du Semestre &rarr;
        </a>
    </div>
</div>

<!-- Bandeau Récapitulatif Style EcoleDirecte -->
<div class="card" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #ffffff; margin-bottom: 24px;">
    <div class="card-body" style="padding: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <span class="badge" style="background: rgba(255,255,255,0.25); color: #fff; margin-bottom: 6px;">
                <?= e($semester) ?> — Année 2025-2026
            </span>
            <h2 style="font-size: 22px; font-weight: 800;"><?= e($student['first_name'] . ' ' . $student['last_name']) ?></h2>
            <div style="font-size: 13px; opacity: 0.9; margin-top: 4px;">
                Matricule : <strong><?= e($student['student_matricule']) ?></strong> &bullet; 
                Total Coefficients : <strong><?= $data['total_coefficients'] ?></strong> &bullet; 
                Total Points : <strong><?= $data['total_points'] ?> pts</strong>
            </div>
        </div>

        <div style="text-align: right; background: rgba(0,0,0,0.2); padding: 14px 24px; border-radius: var(--radius-md);">
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #cbd5e1;">Moyenne Générale Pondérée</div>
            <div style="font-size: 34px; font-weight: 800; color: #fef08a; line-height: 1.1; margin: 4px 0;">
                <?= $data['general_average'] ? number_format($data['general_average'], 2) : '-' ?> <span style="font-size: 18px; color: #ffffff;">/ 20</span>
            </div>
            <div style="font-size: 12px; font-weight: 700; color: #a7f3d0;">
                <?php
                $avg = $data['general_average'];
                if ($avg >= 16) echo "🌟 Mention Très Bien";
                elseif ($avg >= 14) echo "✨ Mention Bien";
                elseif ($avg >= 12) echo "👍 Mention Assez Bien";
                elseif ($avg >= 10) echo "✔️ Admis";
                else echo "⚠️ Doit progresser";
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Grille des Matières et Notes Style EcoleDirecte -->
<div style="display: flex; flex-direction: column; gap: 18px;">
    <?php foreach ($data['subjects'] as $sub): ?>
        <div class="ecoledirecte-card">
            <div class="ed-header">
                <div class="ed-subject-tag">
                    <span class="ed-color-dot" style="background: <?= e($sub['subject_color']) ?>;"></span>
                    <div>
                        <div class="ed-subject-name"><?= e($sub['subject_name']) ?></div>
                        <div style="font-size: 12px; color: var(--text-muted);">Enseignant : <?= e($sub['teacher']) ?></div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px;">
                    <span class="ed-coef">Coefficient <?= $sub['coefficient'] ?></span>
                    <div style="text-align: right;">
                        <span style="font-size: 11px; color: var(--text-muted); display: block;">Moyenne Élève</span>
                        <span style="font-size: 22px; font-weight: 800; color: var(--primary-light);">
                            <?= $sub['student_average'] !== null ? number_format($sub['student_average'], 2) : '-' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Barre de statistiques de la classe (Min, Classe, Max) -->
            <div class="ed-averages-bar">
                <div class="ed-avg-box">
                    <div class="ed-avg-label">Note Min Classe</div>
                    <div class="ed-avg-num" style="color: #64748b;"><?= $sub['class_min'] !== null ? number_format($sub['class_min'], 2) : '-' ?></div>
                </div>

                <div class="ed-avg-box" style="flex-grow: 1; padding: 0 10px;">
                    <div class="ed-avg-label" style="text-align: center;">Moyenne de la Classe : <strong><?= $sub['class_avg'] !== null ? number_format($sub['class_avg'], 2) : '-' ?></strong></div>
                    <div style="height: 6px; background: var(--border); border-radius: 3px; position: relative; margin-top: 6px;">
                        <?php if ($sub['student_average'] !== null): ?>
                            <?php $pct = min(100, max(0, ($sub['student_average'] / 20) * 100)); ?>
                            <div style="position: absolute; left: 0; top: 0; height: 100%; width: <?= $pct ?>%; background: <?= e($sub['subject_color']) ?>; border-radius: 3px;"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ed-avg-box">
                    <div class="ed-avg-label">Note Max Classe</div>
                    <div class="ed-avg-num" style="color: var(--success);"><?= $sub['class_max'] !== null ? number_format($sub['class_max'], 2) : '-' ?></div>
                </div>
            </div>

            <!-- Liste des notes obtenues dans la matière -->
            <div>
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase;">
                    Évaluations du semestre :
                </div>
                <?php if (empty($sub['grades'])): ?>
                    <p style="font-size: 13px; color: var(--text-muted); font-style: italic;">Aucune note saisie pour cette matière ce semestre.</p>
                <?php else: ?>
                    <div class="ed-grades-list">
                        <?php foreach ($sub['grades'] as $g): ?>
                            <div class="ed-grade-chip" title="<?= e($g['appreciation'] ?? '') ?>">
                                <div class="ed-grade-title"><?= e($g['eval_title']) ?></div>
                                <div class="ed-grade-score">
                                    <?php if ($g['is_absent']): ?>
                                        <span style="color: var(--danger); font-size: 12px;">ABS</span>
                                    <?php else: ?>
                                        <?= number_format($g['score'], 2) ?>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 10px; color: var(--text-muted); margin-top: 2px;">
                                    <?= formatDate($g['eval_date']) ?> &bullet; Coef <?= $g['weight'] ?>
                                </div>
                                <?php if (!empty($g['appreciation'])): ?>
                                    <div style="font-size: 10px; color: var(--primary-light); font-style: italic; margin-top: 3px;">
                                        "<?= e(substr($g['appreciation'], 0, 30)) ?>..."
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
