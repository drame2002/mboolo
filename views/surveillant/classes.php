<?php
// views/surveillant/classes.php — Réorganisation Complète : Classes & Effectifs Scolaires
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['surveillant', 'directeur']);

$schoolId = Auth::schoolId();
$pdo = getDbConnection();
$selectedCycle = $_GET['cycle'] ?? 'all';
$selectedClassId = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$classDetails = null;
$classStudents = [];

// Détails de la classe sélectionnée (Trombinoscope / Effectif)
if ($selectedClassId > 0) {
    $stmtC = $pdo->prepare("SELECT * FROM `classes` WHERE `id` = ? AND `school_id` = ?");
    $stmtC->execute([$selectedClassId, $schoolId]);
    $classDetails = $stmtC->fetch();
    
    if ($classDetails) {
        $stmtSt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.gender,
                   sp.student_matricule,
                   (SELECT p.phone FROM parent_students ps JOIN users p ON ps.parent_id = p.id WHERE ps.student_id = u.id LIMIT 1) as parent_phone,
                   (SELECT COUNT(*) FROM student_attendance sa WHERE sa.student_id = u.id AND sa.status = 'absent') as absences_count,
                   (SELECT COUNT(*) FROM student_attendance sa WHERE sa.student_id = u.id AND sa.status = 'retard') as retards_count,
                   (SELECT COUNT(*) FROM disciplinary_records dr WHERE dr.student_id = u.id) as incidents_count
            FROM `users` u
            JOIN `student_profiles` sp ON u.id = sp.user_id
            WHERE sp.class_id = ? AND u.school_id = ? AND u.role = 'eleve' AND u.is_active = 1
            ORDER BY u.last_name ASC, u.first_name ASC
        ");
        $stmtSt->execute([$selectedClassId, $schoolId]);
        $classStudents = $stmtSt->fetchAll();
    }
}

// 1. Statistiques Globales Établissement
$stmtGlobal = $pdo->prepare("
    SELECT COUNT(u.id) as total_students,
           SUM(CASE WHEN u.gender = 'M' THEN 1 ELSE 0 END) as total_male,
           SUM(CASE WHEN u.gender = 'F' THEN 1 ELSE 0 END) as total_female
    FROM `users` u
    JOIN `student_profiles` sp ON u.id = sp.user_id
    WHERE u.school_id = ? AND u.role = 'eleve' AND u.is_active = 1
");
$stmtGlobal->execute([$schoolId]);
$globalStats = $stmtGlobal->fetch();
$totalStudents = (int)($globalStats['total_students'] ?? 0);
$totalMale = (int)($globalStats['total_male'] ?? 0);
$totalFemale = (int)($globalStats['total_female'] ?? 0);

// Capacité totale des salles
$stmtCap = $pdo->prepare("SELECT SUM(capacity) FROM `classes` WHERE `school_id` = ?");
$stmtCap->execute([$schoolId]);
$totalCapacity = (int)$stmtCap->fetchColumn();
$globalOccupancy = $totalCapacity > 0 ? round(($totalStudents / $totalCapacity) * 100) : 0;

// Stats par cycle
$statsQuery = $pdo->prepare("SELECT cycle, COUNT(*) as count FROM `classes` WHERE `school_id` = ? GROUP BY cycle");
$statsQuery->execute([$schoolId]);
$cycleStats = $statsQuery->fetchAll(PDO::FETCH_KEY_PAIR);

$totalClasses = array_sum($cycleStats);
$elemCount = $cycleStats['Elementaire'] ?? 0;
$collCount = $cycleStats['College'] ?? 0;
$lycCount = $cycleStats['Lycee'] ?? 0;

// 2. Effectifs détaillés par classe (Garçons / Filles)
$stmtCounts = $pdo->prepare("
    SELECT sp.class_id, 
           COUNT(*) as student_count,
           SUM(CASE WHEN u.gender = 'M' THEN 1 ELSE 0 END) as male_count,
           SUM(CASE WHEN u.gender = 'F' THEN 1 ELSE 0 END) as female_count
    FROM `student_profiles` sp
    JOIN `users` u ON sp.user_id = u.id
    WHERE u.school_id = ? AND u.role = 'eleve' AND u.is_active = 1
    GROUP BY sp.class_id
");
$stmtCounts->execute([$schoolId]);
$classCountsData = [];
while ($row = $stmtCounts->fetch()) {
    $classCountsData[$row['class_id']] = $row;
}

// 3. Récupération des classes ordonnées par cycle et niveau
$sql = "SELECT * FROM `classes` WHERE `school_id` = ?";
$params = [$schoolId];
if ($selectedCycle !== 'all' && in_array($selectedCycle, ['Elementaire', 'College', 'Lycee'])) {
    $sql .= " AND `cycle` = ?";
    $params[] = $selectedCycle;
}
$sql .= " ORDER BY FIELD(`cycle`, 'Elementaire', 'College', 'Lycee'), `name` ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$classes = $stmt->fetchAll();

// Regroupement hiérarchique par cycle
$groupedClasses = [
    'Elementaire' => [
        'title' => '🎒 Cycle Élémentaire (Primaire)',
        'subtitle' => 'Classes du CI au CM2 • Enseignement fondamental',
        'badge' => 'badge-success',
        'classes' => []
    ],
    'College' => [
        'title' => '🏫 Cycle Collège (Moyen)',
        'subtitle' => 'Classes de la 6ème à la 3ème • Enseignement général (PC dès la 4ème)',
        'badge' => 'badge-warning',
        'classes' => []
    ],
    'Lycee' => [
        'title' => '🎓 Cycle Lycée (Secondaire Général & Technique)',
        'subtitle' => 'Seconde, Première, Terminale • Séries S1, S2, S3, L1, L2, L\', G (STEG), T1, T2',
        'badge' => 'badge-info',
        'classes' => []
    ],
];

foreach ($classes as $c) {
    $cyc = $c['cycle'] ?? 'Lycee';
    if (isset($groupedClasses[$cyc])) {
        $groupedClasses[$cyc]['classes'][] = $c;
    }
}

$pageTitle = "Classes & Effectifs Scolaires";
require_once __DIR__ . '/../../templates/header.php';
?>

<!-- EN-TÊTE PRINCIPAL -->
<div class="page-header no-print">
    <div>
        <h1 class="page-title">🏫 Classes & Effectifs Scolaires</h1>
        <p class="page-subtitle">Gestion intégrale des divisions, effectifs, trombinoscopes et listes d'émargement</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="<?= baseUrl('index.php?page=surveillant_attendance') ?>" class="btn btn-primary btn-sm">
            ⏱️ Pointage & Appel du Jour
        </a>
        <a href="<?= baseUrl('index.php?page=surveillant_timetable') ?>" class="btn btn-outline btn-sm">
            📅 Emplois du Temps
        </a>
    </div>
</div>

<!-- 1. CARTES DE SYNTHÈSE (KPIS) -->
<div class="stats-grid no-print" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #3b82f6;">👥</div>
        <div>
            <div class="stat-value"><?= $totalStudents ?></div>
            <div class="stat-label">Total Élèves Inscrits</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                👦 <?= $totalMale ?> Garçons &bullet; 👧 <?= $totalFemale ?> Filles
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">🏫</div>
        <div>
            <div class="stat-value"><?= $totalClasses ?></div>
            <div class="stat-label">Classes Ouvertes</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                🎒 <?= $elemCount ?> Élem. &bullet; 🏫 <?= $collCount ?> Collège &bullet; 🎓 <?= $lycCount ?> Lycée
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">📊</div>
        <div>
            <div class="stat-value"><?= $globalOccupancy ?>%</div>
            <div class="stat-label">Taux Moyen d'Occupation</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                Capacité d'accueil : <?= $totalCapacity ?> places
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(139, 92, 246, 0.12); color: #8b5cf6;">🎯</div>
        <div>
            <div class="stat-value"><?= $totalClasses > 0 ? round($totalStudents / $totalClasses, 1) : 0 ?></div>
            <div class="stat-label">Moyenne Élèves / Classe</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                Taille moyenne des divisions
            </div>
        </div>
    </div>
</div>

<!-- 2. PANNEAU DE DÉTAIL / TROMBINOSCOPE D'UNE CLASSE SÉLECTIONNÉE -->
<?php if ($classDetails): ?>
    <?php
    $cStats = $classCountsData[$classDetails['id']] ?? ['student_count' => count($classStudents), 'male_count' => 0, 'female_count' => 0];
    $cCount = (int)$cStats['student_count'];
    $cCap = (int)($classDetails['capacity'] ?? 40);
    $cPct = $cCap > 0 ? round(($cCount / $cCap) * 100) : 0;
    ?>
    <div id="class-roster-section" class="card" style="margin-bottom: 28px; border: 2px solid var(--primary-light);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                    <span class="badge <?= $classDetails['cycle'] === 'Elementaire' ? 'badge-success' : ($classDetails['cycle'] === 'College' ? 'badge-warning' : 'badge-info') ?>">
                        Cycle <?= e($classDetails['cycle']) ?> • Niveau <?= e($classDetails['level']) ?>
                    </span>
                    <?php if (!empty($classDetails['track'])): ?>
                        <span class="badge" style="background: #7c3aed; color: #fff;">
                            Série <?= e($classDetails['track']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h2 class="card-title" style="font-size: 20px;">
                    👥 Fiche de Classe : <?= e($classDetails['name']) ?>
                </h2>
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                    Effectif : <strong><?= $cCount ?> élève(s)</strong> (👦 <?= (int)$cStats['male_count'] ?> Garçons &bull; 👧 <?= (int)$cStats['female_count'] ?> Filles)
                    &bullet; Capacité : <strong><?= $cCap ?> places</strong> (<?= $cPct ?>% rempli)
                    &bullet; Salle : <strong><?= e($classDetails['main_room'] ?? 'Non assignée') ?></strong>
                </div>
            </div>

            <div class="no-print" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button onclick="window.print()" class="btn btn-outline btn-sm">
                    🖨️ Imprimer la Feuille d'Appel
                </button>
                <a href="<?= baseUrl('index.php?page=surveillant_attendance&class_id=' . $classDetails['id']) ?>" class="btn btn-primary btn-sm">
                    ⏱️ Pointage de la Classe
                </a>
                <a href="<?= baseUrl('index.php?page=surveillant_timetable&class_id=' . $classDetails['id']) ?>" class="btn btn-outline btn-sm">
                    📅 Emploi du Temps
                </a>
                <a href="<?= baseUrl('index.php?page=surveillant_classes' . ($selectedCycle !== 'all' ? '&cycle=' . $selectedCycle : '')) ?>" class="btn btn-outline btn-sm" title="Fermer le volet">
                    &times; Fermer
                </a>
            </div>
        </div>

        <div class="card-body" style="padding: 0;">
            <?php if (empty($classStudents)): ?>
                <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                    <div style="font-size: 32px; margin-bottom: 8px;">📭</div>
                    <strong>Aucun élève n'est encore inscrit dans cette classe.</strong>
                    <p style="font-size: 12px; margin-top: 4px;">Les nouveaux élèves inscrits dans cette division apparaîtront automatiquement ici.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Élève</th>
                                <th>Matricule</th>
                                <th>Sexe</th>
                                <th>Contact Élève / Tuteur</th>
                                <th>Assiduité</th>
                                <th>Discipline</th>
                                <th class="no-print">Actions Rapides</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $index = 1; foreach ($classStudents as $st): ?>
                                <tr>
                                    <td style="color: var(--text-muted); font-size: 12px;"><?= $index++ ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-table-hover); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: var(--primary-light);">
                                                <?= strtoupper(substr($st['first_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong style="color: var(--text-main); font-size: 14px;"><?= e($st['last_name']) ?></strong> <?= e($st['first_name']) ?>
                                                <div style="font-size: 11px; color: var(--text-muted);"><?= e($st['email']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info" style="font-family: monospace;">
                                            <?= e($st['student_matricule']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $st['gender'] === 'M' ? '👦 M' : ($st['gender'] === 'F' ? '👧 F' : '-') ?>
                                    </td>
                                    <td style="font-size: 12px;">
                                        <?php if (!empty($st['parent_phone'])): ?>
                                            <div>📞 Parent : <strong><?= e($st['parent_phone']) ?></strong></div>
                                        <?php endif; ?>
                                        <?php if (!empty($st['phone'])): ?>
                                            <div style="color: var(--text-muted); font-size: 11px;">Mobile : <?= e($st['phone']) ?></div>
                                        <?php endif; ?>
                                        <?php if (empty($st['parent_phone']) && empty($st['phone'])): ?>
                                            <span style="color: var(--text-muted);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 6px;">
                                            <span class="badge <?= $st['absences_count'] > 0 ? 'badge-danger' : 'badge-secondary' ?>" title="Nombre total d'absences">
                                                <?= $st['absences_count'] ?> abs.
                                            </span>
                                            <span class="badge <?= $st['retards_count'] > 0 ? 'badge-warning' : 'badge-secondary' ?>" title="Nombre total de retards">
                                                <?= $st['retards_count'] ?> ret.
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $st['incidents_count'] > 0 ? 'badge-danger' : 'badge-secondary' ?>" title="Nombre d'incidents disciplinaires">
                                            <?= $st['incidents_count'] ?> incident(s)
                                        </span>
                                    </td>
                                    <td class="no-print">
                                        <div style="display: flex; gap: 6px;">
                                            <a href="<?= baseUrl('index.php?page=surveillant_attendance&class_id=' . $classDetails['id']) ?>" class="btn btn-outline btn-sm" title="Pointer assiduité">
                                                ⏱️
                                            </a>
                                            <a href="<?= baseUrl('index.php?page=surveillant_discipline&class_id=' . $classDetails['id']) ?>" class="btn btn-outline btn-sm" title="Consigner incident">
                                                ⚖️
                                            </a>
                                            <a href="<?= baseUrl('index.php?page=student_bulletin&student_id=' . $st['id']) ?>" class="btn btn-outline btn-sm" title="Voir bulletin">
                                                📜
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- 3. BARRE DE CONTRÔLE, FILTRES & RECHERCHE DYNAMIQUE -->
<div class="card no-print" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 14px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
            
            <!-- Filtres par Cycle -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <span style="font-size: 13px; font-weight: 700; color: var(--text-muted); margin-right: 4px;">Cycle :</span>
                <a href="<?= baseUrl('index.php?page=surveillant_classes&cycle=all') ?>" class="btn <?= $selectedCycle === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    Tous (<?= $totalClasses ?>)
                </a>
                <a href="<?= baseUrl('index.php?page=surveillant_classes&cycle=Elementaire') ?>" class="btn <?= $selectedCycle === 'Elementaire' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    🎒 Élémentaire (<?= $elemCount ?>)
                </a>
                <a href="<?= baseUrl('index.php?page=surveillant_classes&cycle=College') ?>" class="btn <?= $selectedCycle === 'College' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    🏫 Collège (<?= $collCount ?>)
                </a>
                <a href="<?= baseUrl('index.php?page=surveillant_classes&cycle=Lycee') ?>" class="btn <?= $selectedCycle === 'Lycee' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    🎓 Lycée (<?= $lycCount ?>)
                </a>
            </div>

            <!-- Recherche instantanée & bascule de vue -->
            <div style="display: flex; gap: 10px; align-items: center;">
                <div style="position: relative; width: 240px;">
                    <input type="text" id="classSearchInput" class="form-control" placeholder="🔍 Filtrer une classe..." onkeyup="filterClassesLive(this.value)" style="padding-left: 12px; height: 36px; font-size: 13px;">
                </div>
                <div style="display: flex; border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden;">
                    <button type="button" id="btn-view-grid" class="btn btn-sm btn-primary" onclick="switchView('grid')" title="Vue en cartes / grille" style="border-radius: 0; padding: 6px 12px;">
                        🔲 Cartes
                    </button>
                    <button type="button" id="btn-view-table" class="btn btn-sm btn-outline" onclick="switchView('table')" title="Vue tableau synthétique" style="border-radius: 0; border: none; padding: 6px 12px;">
                        📋 Tableau
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- 4. CONTENU PRINCIPAL PAR CYCLE (VUE CARTES) -->
<div id="view-mode-grid">
    <?php foreach ($groupedClasses as $cycleKey => $cycleGroup): ?>
        <?php if ($selectedCycle !== 'all' && $selectedCycle !== $cycleKey) continue; ?>
        <?php if (empty($cycleGroup['classes'])) continue; ?>

        <div class="cycle-section-block" style="margin-bottom: 32px;" data-cycle="<?= $cycleKey ?>">
            <!-- Titre de section Cycle -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border); padding-bottom: 10px; margin-bottom: 18px;">
                <div>
                    <h3 style="font-size: 18px; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
                        <?= $cycleGroup['title'] ?>
                        <span class="badge <?= $cycleGroup['badge'] ?>" style="font-size: 11px;">
                            <?= count($cycleGroup['classes']) ?> division(s)
                        </span>
                    </h3>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                        <?= $cycleGroup['subtitle'] ?>
                    </p>
                </div>
            </div>

            <!-- Grille des classes du cycle -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 18px;">
                <?php foreach ($cycleGroup['classes'] as $c): ?>
                    <?php
                    $cStats = $classCountsData[$c['id']] ?? ['student_count' => 0, 'male_count' => 0, 'female_count' => 0];
                    $stCount = (int)$cStats['student_count'];
                    $capacity = (int)($c['capacity'] ?? 40);
                    $pct = $capacity > 0 ? round(($stCount / $capacity) * 100) : 0;
                    $isSelected = ($selectedClassId === (int)$c['id']);

                    // Couleur jauge
                    $gaugeColor = 'var(--success)';
                    if ($pct >= 100) $gaugeColor = 'var(--danger)';
                    elseif ($pct >= 80) $gaugeColor = 'var(--warning)';
                    ?>
                    <div class="card class-card-item" data-class-name="<?= strtolower(e($c['name'])) ?>" data-class-room="<?= strtolower(e($c['main_room'] ?? '')) ?>" data-class-track="<?= strtolower(e($c['track'] ?? '')) ?>" style="<?= $isSelected ? 'border: 2px solid var(--primary-light); box-shadow: 0 0 12px rgba(59, 130, 246, 0.3);' : '' ?>">
                        <div class="card-body" style="padding: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <div>
                                    <span class="badge <?= $cycleGroup['badge'] ?>" style="font-size: 11px; margin-bottom: 4px;">
                                        <?= e($c['level']) ?>
                                    </span>
                                    <h4 style="font-size: 16px; font-weight: 800; color: var(--text-main); margin-top: 2px;">
                                        <?= e($c['name']) ?>
                                    </h4>
                                    <?php if (!empty($c['track'])): ?>
                                        <div style="font-size: 11.5px; font-weight: 700; color: #b45309; margin-top: 2px;">
                                            Série : <?= e($c['track']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size: 24px;">
                                    <?= $c['cycle'] === 'Elementaire' ? '🎒' : ($c['cycle'] === 'College' ? '🏫' : '🎓') ?>
                                </span>
                            </div>

                            <!-- Effectif & Salle -->
                            <div style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 10px; line-height: 1.5;">
                                📍 Salle : <strong><?= e($c['main_room'] ?? 'Non assignée') ?></strong><br>
                                👥 Effectif : <strong style="color: var(--text-main);"><?= $stCount ?> élève(s)</strong> / <?= $capacity ?>
                                <span style="font-size: 11px; color: var(--text-muted);">(<?= (int)$cStats['male_count'] ?>G &bull; <?= (int)$cStats['female_count'] ?>F)</span>
                            </div>

                            <!-- Jauge d'occupation -->
                            <div style="margin-bottom: 14px;">
                                <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">
                                    <span>Taux d'occupation</span>
                                    <strong style="color: <?= $gaugeColor ?>;"><?= $pct ?>%</strong>
                                </div>
                                <div style="background: var(--border); height: 6px; border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?= min($pct, 100) ?>%; height: 100%; background: <?= $gaugeColor ?>;"></div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div style="display: flex; gap: 6px;">
                                <a href="<?= baseUrl('index.php?page=surveillant_classes&class_id=' . $c['id'] . ($selectedCycle !== 'all' ? '&cycle=' . $selectedCycle : '')) ?>#class-roster-section" class="btn <?= $isSelected ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="flex: 1.2; text-align: center; font-size: 12px; padding: 6px 8px;">
                                    👥 Élèves (<?= $stCount ?>)
                                </a>
                                <a href="<?= baseUrl('index.php?page=surveillant_attendance&class_id=' . $c['id']) ?>" class="btn btn-primary btn-sm" style="flex: 1; text-align: center; font-size: 12px; padding: 6px 8px;" title="Pointer assiduité">
                                    ⏱️ Appel
                                </a>
                                <a href="<?= baseUrl('index.php?page=surveillant_timetable&class_id=' . $c['id']) ?>" class="btn btn-outline btn-sm" style="text-align: center; font-size: 12px; padding: 6px 10px;" title="Emploi du temps">
                                    📅
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- 5. VUE TABLEAU SYNTHÉTIQUE (OPTIONNELLE) -->
<div id="view-mode-table" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📋 Répertoire Synthétique des Classes & Effectifs</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table" id="classesDataTable">
                    <thead>
                        <tr>
                            <th>Division / Classe</th>
                            <th>Cycle</th>
                            <th>Niveau</th>
                            <th>Série (Lycée)</th>
                            <th>Salle</th>
                            <th>Effectif Total</th>
                            <th>Garçons</th>
                            <th>Filles</th>
                            <th>Taux de Remplissage</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $c): ?>
                            <?php
                            $cStats = $classCountsData[$c['id']] ?? ['student_count' => 0, 'male_count' => 0, 'female_count' => 0];
                            $stCount = (int)$cStats['student_count'];
                            $capacity = (int)($c['capacity'] ?? 40);
                            $pct = $capacity > 0 ? round(($stCount / $capacity) * 100) : 0;
                            ?>
                            <tr class="table-class-row" data-class-name="<?= strtolower(e($c['name'])) ?>" data-class-room="<?= strtolower(e($c['main_room'] ?? '')) ?>" data-class-track="<?= strtolower(e($c['track'] ?? '')) ?>">
                                <td>
                                    <strong style="color: var(--primary-light); font-size: 14px;"><?= e($c['name']) ?></strong>
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
                                <td><?= e($c['level']) ?></td>
                                <td>
                                    <?php if (!empty($c['track'])): ?>
                                        <strong style="color: #b45309;"><?= e($c['track']) ?></strong>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>📍 <?= e($c['main_room'] ?? 'Non assignée') ?></td>
                                <td>
                                    <strong><?= $stCount ?></strong> / <?= $capacity ?>
                                </td>
                                <td>👦 <?= (int)$cStats['male_count'] ?></td>
                                <td>👧 <?= (int)$cStats['female_count'] ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="flex-grow: 1; height: 6px; background: var(--border); border-radius: 3px; overflow: hidden; width: 60px;">
                                            <div style="height: 100%; width: <?= min($pct, 100) ?>%; background: <?= $pct >= 100 ? 'var(--danger)' : ($pct >= 80 ? 'var(--warning)' : 'var(--success)') ?>;"></div>
                                        </div>
                                        <span style="font-size: 11px; font-weight: 700;"><?= $pct ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="<?= baseUrl('index.php?page=surveillant_classes&class_id=' . $c['id'] . ($selectedCycle !== 'all' ? '&cycle=' . $selectedCycle : '')) ?>#class-roster-section" class="btn btn-outline btn-sm">
                                            👥 Élèves
                                        </a>
                                        <a href="<?= baseUrl('index.php?page=surveillant_attendance&class_id=' . $c['id']) ?>" class="btn btn-primary btn-sm" title="Appel">
                                            ⏱️
                                        </a>
                                        <a href="<?= baseUrl('index.php?page=surveillant_timetable&class_id=' . $c['id']) ?>" class="btn btn-outline btn-sm" title="Horaires">
                                            📅
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS D'INTERACTIVITÉ & FILTRAGE EN TEMPS RÉEL -->
<script>
function filterClassesLive(query) {
    query = (query || '').toLowerCase().trim();
    
    // Filtrage vue cartes
    const cards = document.querySelectorAll('.class-card-item');
    cards.forEach(card => {
        const name = card.getAttribute('data-class-name') || '';
        const room = card.getAttribute('data-class-room') || '';
        const track = card.getAttribute('data-class-track') || '';
        const matches = name.includes(query) || room.includes(query) || track.includes(query);
        card.style.display = matches ? '' : 'none';
    });

    // Cacher les titres de section vides en mode recherche
    document.querySelectorAll('.cycle-section-block').forEach(section => {
        const visibleCards = section.querySelectorAll('.class-card-item:not([style*="display: none"])');
        section.style.display = visibleCards.length > 0 ? '' : 'none';
    });

    // Filtrage vue tableau
    const rows = document.querySelectorAll('.table-class-row');
    rows.forEach(row => {
        const name = row.getAttribute('data-class-name') || '';
        const room = row.getAttribute('data-class-room') || '';
        const track = row.getAttribute('data-class-track') || '';
        const matches = name.includes(query) || room.includes(query) || track.includes(query);
        row.style.display = matches ? '' : 'none';
    });
}

function switchView(mode) {
    const gridView = document.getElementById('view-mode-grid');
    const tableView = document.getElementById('view-mode-table');
    const btnGrid = document.getElementById('btn-view-grid');
    const btnTable = document.getElementById('btn-view-table');

    if (mode === 'table') {
        gridView.style.display = 'none';
        tableView.style.display = 'block';
        btnTable.className = 'btn btn-sm btn-primary';
        btnGrid.className = 'btn btn-sm btn-outline';
        localStorage.setItem('mboolo_classes_view', 'table');
    } else {
        gridView.style.display = 'block';
        tableView.style.display = 'none';
        btnGrid.className = 'btn btn-sm btn-primary';
        btnTable.className = 'btn btn-sm btn-outline';
        localStorage.setItem('mboolo_classes_view', 'grid');
    }
}

// Restauration de la vue préférée
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('mboolo_classes_view');
    if (savedView === 'table') {
        switchView('table');
    }
});
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
