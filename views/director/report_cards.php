<?php
// views/director/report_cards.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['directeur']);

$schoolId = Auth::schoolId();
$pdo = getDbConnection();
$classes = AcademicService::getClassesBySchool($schoolId);

$selectedClassId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : ($classes[0]['id'] ?? 0);
$selectedSemester = $_GET['semester'] ?? 'Semestre 1';

// Récupérer les élèves de la classe sélectionnée
$stmtStudents = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, sp.student_matricule, c.name as class_name, c.track
    FROM `users` u
    JOIN `student_profiles` sp ON u.id = sp.user_id
    JOIN `classes` c ON sp.class_id = c.id
    WHERE u.school_id = ? AND sp.class_id = ? AND u.role = 'eleve' AND u.is_active = 1
    ORDER BY u.last_name ASC
");
$stmtStudents->execute([$schoolId, $selectedClassId]);
$students = $stmtStudents->fetchAll();

$pageTitle = "Supervision des Bulletins Scolaires Semestriels";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📜 Bulletins Scolaires Semestriels Automatiques</h1>
        <p class="page-subtitle">Supervision, validation et impression officielle des bulletins de l'établissement</p>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="<?= baseUrl('index.php') ?>" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="page" value="director_report_cards">

            <div style="flex: 1; min-width: 220px;">
                <label class="form-label">Classe d'évaluation :</label>
                <select name="class_id" class="form-control" onchange="this.form.submit()">
                    <?php
                    $classesByCycle = [
                        'Elementaire' => ['label' => '🎒 Cycle Élémentaire (CI, CP, CE1, CE2, CM1, CM2)', 'items' => []],
                        'College'     => ['label' => '🏫 Cycle Collège (6ème, 5ème, 4ème, 3ème)', 'items' => []],
                        'Lycee'       => ['label' => '🎓 Cycle Lycée (Séries Scientifiques S, Littéraires L, Gestion G, Techniques T)', 'items' => []],
                    ];
                    foreach ($classes as $c) {
                        $cyc = $c['cycle'] ?? 'Lycee';
                        if (isset($classesByCycle[$cyc])) {
                            $classesByCycle[$cyc]['items'][] = $c;
                        }
                    }
                    foreach ($classesByCycle as $grp):
                        if (empty($grp['items'])) continue;
                    ?>
                        <optgroup label="<?= e($grp['label']) ?>">
                            <?php foreach ($grp['items'] as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $c['id'] == $selectedClassId ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?><?= $c['track'] ? ' — ' . e($c['track']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 200px;">
                <label class="form-label">Période Semestrielle :</label>
                <select name="semester" class="form-control" onchange="this.form.submit()">
                    <option value="Semestre 1" <?= $selectedSemester === 'Semestre 1' ? 'selected' : '' ?>>Semestre 1</option>
                    <option value="Semestre 2" <?= $selectedSemester === 'Semestre 2' ? 'selected' : '' ?>>Semestre 2</option>
                </select>
            </div>

            <button type="submit" class="btn btn-outline" style="height: 42px;">
                🔄 Actualiser
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📋 Liste des Élèves & Calculs des Bulletins (<?= e($selectedSemester) ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($students)): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 30px;">
                Aucun élève inscrit dans cette classe.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom & Prénom de l'Élève</th>
                            <th>Moyenne Semestrielle</th>
                            <th>Total Coefs</th>
                            <th>Total Points</th>
                            <th>Mention Estimée</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $st): ?>
                            <?php
                            $overview = AcademicService::getStudentGradesOverview($schoolId, (int)$st['id'], $selectedSemester);
                            $genAvg = $overview['general_average'];
                            ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 700;">
                                    <?= e($st['student_matricule']) ?>
                                </td>
                                <td>
                                    <strong><?= e($st['last_name']) ?></strong> <?= e($st['first_name']) ?>
                                </td>
                                <td>
                                    <?php if ($genAvg !== null): ?>
                                        <strong style="font-size: 16px; color: var(--primary-light);">
                                            <?= number_format($genAvg, 2) ?> / 20
                                        </strong>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">En attente de notes</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $overview['total_coefficients'] ?? '-' ?></td>
                                <td><?= $overview['total_points'] ? number_format($overview['total_points'], 2) : '-' ?></td>
                                <td>
                                    <?php if ($genAvg !== null): ?>
                                        <span class="badge <?= $genAvg >= 14 ? 'badge-success' : ($genAvg >= 10 ? 'badge-info' : 'badge-warning') ?>">
                                            <?php
                                            if ($genAvg >= 16) echo "Très Bien";
                                            elseif ($genAvg >= 14) echo "Bien";
                                            elseif ($genAvg >= 12) echo "Assez Bien";
                                            elseif ($genAvg >= 10) echo "Passable";
                                            else echo "Insuffisant";
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= baseUrl('index.php?page=student_bulletin&student_id=' . $st['id'] . '&semester=' . urlencode($selectedSemester)) ?>" class="btn btn-primary btn-sm">
                                        📜 Éditer & Imprimer Bulletin &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
