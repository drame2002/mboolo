<?php
// views/director/classes.php — Classes & Programmes Pédagogiques (Élémentaire, Collège, Lycée)
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['directeur']);

$schoolId = Auth::schoolId();
$pdo = getDbConnection();
$selectedCycle = $_GET['cycle'] ?? 'all';

// Récupération des classes avec effectifs et liste agrégée des matières
$sql = "
    SELECT c.*, 
           (SELECT COUNT(*) FROM student_profiles sp WHERE sp.class_id = c.id) as students_count,
           (SELECT COUNT(*) FROM class_subjects cs WHERE cs.class_id = c.id) as subjects_count,
           (SELECT GROUP_CONCAT(CONCAT(s.name, ' (Coeff ', cs.coefficient, ')') ORDER BY s.name ASC SEPARATOR ' • ')
            FROM class_subjects cs
            JOIN subjects s ON cs.subject_id = s.id
            WHERE cs.class_id = c.id) as subjects_list
    FROM `classes` c
    WHERE c.school_id = ?
";
$params = [$schoolId];

if ($selectedCycle !== 'all' && in_array($selectedCycle, ['Elementaire', 'College', 'Lycee'])) {
    $sql .= " AND c.cycle = ? ";
    $params[] = $selectedCycle;
}

$sql .= " ORDER BY FIELD(c.cycle, 'Elementaire', 'College', 'Lycee'), c.name ASC";
$stmtClasses = $pdo->prepare($sql);
$stmtClasses->execute($params);
$classes = $stmtClasses->fetchAll();

// Statistiques globales par cycle
$statsQuery = $pdo->prepare("
    SELECT cycle, COUNT(*) as count 
    FROM `classes` 
    WHERE `school_id` = ? 
    GROUP BY cycle
");
$statsQuery->execute([$schoolId]);
$cycleStats = $statsQuery->fetchAll(PDO::FETCH_KEY_PAIR);

$totalClasses = array_sum($cycleStats);
$elemCount = $cycleStats['Elementaire'] ?? 0;
$collCount = $cycleStats['College'] ?? 0;
$lycCount = $cycleStats['Lycee'] ?? 0;

$pageTitle = "Classes & Programmes Pédagogiques";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🏫 Structure Pédagogique de l'Établissement</h1>
        <p class="page-subtitle">Organisation intégrale des cycles : Élémentaire (CI à CM2), Collège (6ème à 3ème) et Lycée (Seconde, Première, Terminale avec séries S, L, G, T)</p>
    </div>
    <div>
        <a href="<?= baseUrl('index.php?page=surveillant_classes') ?>" class="btn btn-primary btn-sm">
            👥 Voir les Effectifs & Trombinoscope
        </a>
    </div>
</div>

<!-- Cartes Métriques par Cycle -->
<div class="stats-grid" style="margin-bottom: 24px;">
    <div class="card stat-card">
        <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">📚</div>
        <div>
            <div class="stat-value"><?= $totalClasses ?></div>
            <div class="stat-label">Total Classes Établissement</div>
        </div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">🎒</div>
        <div>
            <div class="stat-value"><?= $elemCount ?></div>
            <div class="stat-label">Classes Élémentaire (CI à CM2)</div>
        </div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">🏫</div>
        <div>
            <div class="stat-value"><?= $collCount ?></div>
            <div class="stat-label">Classes Collège (6ème à 3ème)</div>
        </div>
    </div>
    <div class="card stat-card">
        <div class="stat-icon" style="background: rgba(139, 92, 246, 0.12); color: #8b5cf6;">🎓</div>
        <div>
            <div class="stat-value"><?= $lycCount ?></div>
            <div class="stat-label">Classes Lycée (Séries S, L, G, T)</div>
        </div>
    </div>
</div>

<!-- Filtres d'onglets de cycle -->
<div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
    <a href="<?= baseUrl('index.php?page=director_classes&cycle=all') ?>" class="btn <?= $selectedCycle === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        Tous les cycles (<?= $totalClasses ?>)
    </a>
    <a href="<?= baseUrl('index.php?page=director_classes&cycle=Elementaire') ?>" class="btn <?= $selectedCycle === 'Elementaire' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        🎒 Élémentaire : CI, CP, CE1, CE2, CM1, CM2 (<?= $elemCount ?>)
    </a>
    <a href="<?= baseUrl('index.php?page=director_classes&cycle=College') ?>" class="btn <?= $selectedCycle === 'College' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        🏫 Collège : 6ème à 3ème (<?= $collCount ?>)
    </a>
    <a href="<?= baseUrl('index.php?page=director_classes&cycle=Lycee') ?>" class="btn <?= $selectedCycle === 'Lycee' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        🎓 Lycée : Séries S, L, G, T (<?= $lycCount ?>)
    </a>
</div>

<!-- Tableau des Classes et Matières -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            📋 Liste des Classes & Cours Dispensés
            <?php if ($selectedCycle !== 'all'): ?>
                <span class="badge badge-info" style="margin-left: 8px;"><?= e($selectedCycle) ?></span>
            <?php endif; ?>
        </h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Niveau / Classe</th>
                        <th>Cycle</th>
                        <th>Série (Lycée)</th>
                        <th>Salle Principale</th>
                        <th>Effectif</th>
                        <th>Matières Dispensées & Coefficients</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($classes)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                Aucune classe trouvée pour ce filtre.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($classes as $c): ?>
                        <tr>
                            <td>
                                <strong style="font-size: 15px; color: var(--primary-light);"><?= e($c['name']) ?></strong><br>
                                <span style="font-size: 11px; color: var(--text-muted);">Niveau : <?= e($c['level']) ?> • Année <?= e($c['academic_year']) ?></span>
                            </td>
                            <td>
                                <?php if ($c['cycle'] === 'Elementaire'): ?>
                                    <span class="badge badge-success">🎒 Élémentaire</span>
                                <?php elseif ($c['cycle'] === 'College'): ?>
                                    <span class="badge badge-warning">🏫 Collège</span>
                                <?php else: ?>
                                    <span class="badge badge-info">🎓 Lycée</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($c['track'])): ?>
                                    <?php 
                                    $trackColor = '#2563eb';
                                    if (strpos($c['track'], 'Littéraire') !== false) $trackColor = '#b45309';
                                    elseif (strpos($c['track'], 'Économie') !== false || strpos($c['track'], 'G') !== false) $trackColor = '#059669';
                                    elseif (strpos($c['track'], 'Technique') !== false || strpos($c['track'], 'T') !== false) $trackColor = '#7c3aed';
                                    ?>
                                    <strong style="color: <?= $trackColor ?>; font-size: 13px;">
                                        <?= e($c['track']) ?>
                                    </strong>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 12px;">Tronc Commun</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                📍 <?= e($c['main_room'] ?? 'Non assignée') ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    <?= $c['students_count'] ?> / <?= $c['capacity'] ?> élèves
                                </span>
                            </td>
                            <td style="max-width: 320px;">
                                <div style="font-weight: 700; font-size: 12px; margin-bottom: 4px;">
                                    <?= $c['subjects_count'] ?> matières associées
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted); line-height: 1.4;">
                                    <?= e($c['subjects_list'] ?? 'Aucune matière rattachée') ?>
                                </div>
                            </td>
                            <td>
                                <a href="<?= baseUrl('index.php?page=director_report_cards&class_id=' . $c['id']) ?>" class="btn btn-outline btn-sm">
                                    📜 Bulletins
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
