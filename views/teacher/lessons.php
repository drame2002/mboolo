<?php
// views/teacher/lessons.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['professeur']);

$teacherId = Auth::id();
$schoolId = Auth::schoolId();
$teacherCourses = AcademicService::getTeacherClassesAndSubjects($schoolId, $teacherId);
$summaries = AcademicService::getSummariesForTeacher($schoolId, $teacherId);
$pageTitle = "Cahier de Textes & Résumés de Leçons";

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📖 Cahier de Textes Numérique</h1>
        <p class="page-subtitle">Publiez les résumés de cours, synthèses et travaux à faire pour vos élèves</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="openModal('modal-new-lesson')">
            ➕ Publier un Nouveau Résumé de Cours
        </button>
    </div>
</div>

<!-- Modal Nouveau Résumé de Leçon -->
<div id="modal-new-lesson" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:2000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:var(--bg-card); color:var(--text-main); border: 1px solid var(--border); border-radius:var(--radius-lg); width:100%; max-width:640px; padding:24px; box-shadow:var(--shadow-lg);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="font-size:18px; font-weight:800;">📖 Nouveau Résumé de Leçon</h3>
            <button onclick="closeModal('modal-new-lesson')" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>

        <form action="<?= baseUrl('index.php?action=publish_lesson_summary') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="class_subject_id">Classe & Matière *</label>
                    <select name="class_subject_id" id="class_subject_id" class="form-control" required>
                        <?php foreach ($teacherCourses as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= e($c['class_name']) ?> — <?= e($c['subject_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="lesson_date">Date du Cours *</label>
                    <input type="date" name="lesson_date" id="lesson_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="title">Titre / Thème de la Séance *</label>
                <input type="text" name="title" id="title" class="form-control" placeholder="ex: Chapitre 3 : Théorème de l'Énergie Cinétique" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="summary_content">Résumé / Notions Clés Abordées *</label>
                <textarea name="summary_content" id="summary_content" class="form-control" rows="5" placeholder="Synthèse du cours, théorèmes démontrés, définitions..." required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="homework">Travail à Faire / Exercices (Optionnel)</label>
                    <input type="text" name="homework" id="homework" class="form-control" placeholder="ex: Exercices 12 et 15 page 74">
                </div>

                <div class="form-group">
                    <label class="form-label" for="homework_due_date">Date de Rendu</label>
                    <input type="date" name="homework_due_date" id="homework_due_date" class="form-control">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-new-lesson')">Annuler</button>
                <button type="submit" class="btn btn-primary">Publier le Résumé pour la Classe</button>
            </div>
        </form>
    </div>
</div>

<!-- Liste des résumés publiés -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📚 Résumés de Séances & Cahier de Texte</h3>
    </div>
    <div class="card-body">
        <?php if (empty($summaries)): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 30px;">
                Vous n'avez pas encore publié de résumé de séance. Cliquez sur le bouton ci-dessus pour ajouter votre premier cours.
            </p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($summaries as $s): ?>
                    <div style="border: 1px solid var(--border); border-left: 5px solid <?= e($s['subject_color']) ?>; border-radius: var(--radius-md); padding: 18px; background: var(--bg-card);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="badge" style="background: <?= e($s['subject_color']) ?>; color: #fff;">
                                    <?= e($s['subject_name']) ?>
                                </span>
                                <span class="badge badge-secondary"><?= e($s['class_name']) ?></span>
                                <strong style="font-size: 16px;"><?= e($s['title']) ?></strong>
                            </div>
                            <span style="font-size: 13px; color: var(--text-muted); font-weight: 600;">
                                📅 Séance du <?= formatDate($s['lesson_date']) ?>
                            </span>
                        </div>

                        <p style="font-size: 14px; line-height: 1.6; color: var(--text-main); margin-bottom: 12px; white-space: pre-line;">
                            <?= nl2br(e($s['summary_content'])) ?>
                        </p>

                        <?php if (!empty($s['homework'])): ?>
                            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-sm); padding: 10px 14px; font-size: 13px;">
                                <strong style="color: #1e40af;">📝 Travail à faire / Devoirs :</strong> <?= e($s['homework']) ?>
                                <?php if (!empty($s['homework_due_date'])): ?>
                                    &bullet; <span style="color: #dc2626; font-weight: 700;">À rendre pour le : <?= formatDate($s['homework_due_date']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
