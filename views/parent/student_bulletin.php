<?php
// views/parent/student_bulletin.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/ParentService.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['parent']);

$parentId = Auth::id();
$schoolId = Auth::schoolId();
$children = ParentService::getChildren($schoolId, $parentId);

if (empty($children)) {
    setFlash('warning', "Veuillez d'abord associer un enfant à votre compte pour consulter son bulletin.");
    header("Location: " . baseUrl('index.php?page=parent_link_child'));
    exit;
}

$selectedChildId = isset($_GET['child_id']) ? (int)$_GET['child_id'] : (int)$children[0]['id'];
$semester = $_GET['semester'] ?? 'Semestre 1';
$academicYear = '2025-2026';

// Récupération ou calcul automatique du bulletin
$bulletin = AcademicService::getReportCard($schoolId, $selectedChildId, $semester, $academicYear);
if (!$bulletin) {
    AcademicService::generateSemesterReportCard($schoolId, $selectedChildId, $semester, $academicYear);
    $bulletin = AcademicService::getReportCard($schoolId, $selectedChildId, $semester, $academicYear);
}

$pageTitle = "Bulletin Scolaire Officiel — " . e($semester);
$schoolLogo = !empty($bulletin['school_logo']) ? baseUrl($bulletin['school_logo']) : baseUrl('assets/img/logos/mboolo_logo.png');

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header no-print">
    <div>
        <h1 class="page-title">📜 Bulletin Scolaire Officiel de l'Enfant</h1>
        <p class="page-subtitle">Document académique officiel certifié conforme par la Direction</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <form method="GET" action="<?= baseUrl('index.php') ?>" style="display: flex; gap: 8px;">
            <input type="hidden" name="page" value="parent_student_bulletin">
            <select name="child_id" class="form-control" onchange="this.form.submit()" style="font-weight: 700;">
                <?php foreach ($children as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $selectedChildId ? 'selected' : '' ?>>
                        👤 <?= e($c['first_name'] . ' ' . $c['last_name']) ?> (<?= e($c['class_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="semester" class="form-control" onchange="this.form.submit()" style="font-weight: 700;">
                <option value="Semestre 1" <?= $semester === 'Semestre 1' ? 'selected' : '' ?>>Semestre 1</option>
                <option value="Semestre 2" <?= $semester === 'Semestre 2' ? 'selected' : '' ?>>Semestre 2</option>
            </select>
        </form>

        <button id="print-report-card-btn" class="btn btn-primary">
            🖨️ Télécharger en PDF / Imprimer
        </button>
    </div>
</div>

<?php if (!$bulletin): ?>
    <div class="card no-print">
        <div class="card-body" style="text-align: center; padding: 50px;">
            <div style="font-size: 40px; margin-bottom: 12px;">📑</div>
            <h3 style="font-size: 18px; font-weight: 700;">Bulletin en cours de calcul</h3>
            <p style="color: var(--text-muted); font-size: 14px; margin-top: 6px;">
                Les notes de ce semestre ne sont pas encore intégralement clôturées par les enseignants.
            </p>
        </div>
    </div>
<?php else: ?>
    <!-- FORMAT OFFICIEL DU BULLETIN SCOLAIRE (Spécifications 8 & 9) -->
    <div class="official-report-card">
        <!-- En-tête avec Logo de l'établissement -->
        <div class="rc-header">
            <div style="display: flex; align-items: center; gap: 18px;">
                <img src="<?= $schoolLogo ?>" alt="Logo <?= e($bulletin['school_name']) ?>" class="rc-school-logo">
                <div class="rc-school-info">
                    <h2 style="font-size: 18px; font-weight: 800; text-transform: uppercase; color: #1e3a8a; letter-spacing: -0.5px;">
                        <?= e($bulletin['school_name']) ?>
                    </h2>
                    <p style="font-size: 12px; font-weight: 600; color: #64748b; margin-top: 2px;">
                        <?= e($bulletin['school_slogan']) ?>
                    </p>
                    <div style="font-size: 11px; color: #475569; margin-top: 4px;">
                        📍 <?= e($bulletin['school_address']) ?> &nbsp;|&nbsp; 
                        📞 <?= e($bulletin['school_phone']) ?> &nbsp;|&nbsp; 
                        ✉️ <?= e($bulletin['school_email']) ?>
                    </div>
                </div>
            </div>

            <div style="text-align: right; border-left: 2px solid #e2e8f0; padding-left: 18px;">
                <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Année Scolaire</div>
                <div style="font-size: 15px; font-weight: 800; color: #0f172a;"><?= e($bulletin['academic_year']) ?></div>
                <div style="font-size: 11px; color: #10b981; font-weight: 700; margin-top: 4px;">✓ Document Certifié</div>
            </div>
        </div>

        <div class="rc-title-box">
            BULLETIN DE NOTES OFFICIEL — <?= strtoupper(e($bulletin['semester'])) ?>
        </div>

        <div class="rc-student-banner">
            <div>
                <div>Nom & Prénom : <strong style="font-size: 14px; text-transform: uppercase;"><?= e($bulletin['last_name']) ?> <?= e($bulletin['first_name']) ?></strong></div>
                <div>Matricule Scolaire : <strong style="font-family: monospace; color: #1e3a8a;"><?= e($bulletin['student_matricule']) ?></strong></div>
                <div>Date de naissance : <?= formatDate($bulletin['date_of_birth']) ?></div>
            </div>
            <div>
                <div>Classe : <strong style="font-size: 14px; color: #1e3a8a;"><?= e($bulletin['class_name']) ?></strong> (Niveau : <?= e($bulletin['level']) ?>)</div>
                <div>
                    Cycle d'Enseignement : 
                    <?php if ($bulletin['cycle'] === 'Elementaire'): ?>
                        <strong style="color: #059669;">🎒 Élémentaire (Primaire)</strong>
                    <?php elseif ($bulletin['cycle'] === 'College'): ?>
                        <strong style="color: #d97706;">🏫 Collège (Enseignement Moyen)</strong>
                    <?php else: ?>
                        <strong style="color: #2563eb;">🎓 Lycée (Enseignement Secondaire)</strong>
                    <?php endif; ?>
                </div>

                <?php if ($bulletin['cycle'] === 'Lycee' && !empty($bulletin['track'])): ?>
                    <?php
                    $tr = $bulletin['track'];
                    $trIcon = '🎓'; $trColor = '#2563eb';
                    if (strpos($tr, 'Scientifique') !== false || strpos($tr, 'S1') !== false || strpos($tr, 'S2') !== false || strpos($tr, 'S3') !== false) {
                        $trIcon = '🔬'; $trColor = '#2563eb';
                    } elseif (strpos($tr, 'Littéraire') !== false || strpos($tr, 'L1') !== false || strpos($tr, 'L2') !== false || strpos($tr, 'L\'') !== false) {
                        $trIcon = '📚'; $trColor = '#b45309';
                    } elseif (strpos($tr, 'Économie') !== false || strpos($tr, 'Gestion') !== false || strpos($tr, 'G') !== false) {
                        $trIcon = '💼'; $trColor = '#059669';
                    } elseif (strpos($tr, 'Technique') !== false || strpos($tr, 'Génie') !== false || strpos($tr, 'T') !== false) {
                        $trIcon = '⚙️'; $trColor = '#7c3aed';
                    }
                    ?>
                    <div style="margin-top: 3px;">
                        Série du Lycée : 
                        <span class="badge" style="background: <?= $trColor ?>; color: #fff; font-size: 11px; padding: 2px 8px;">
                            <?= $trIcon ?> <?= e($tr) ?>
                        </span>
                    </div>
                <?php elseif ($bulletin['cycle'] === 'College'): ?>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                        Filière : Enseignement Général (Option Latin / Éducation Musicale / LV2)
                    </div>
                <?php elseif ($bulletin['cycle'] === 'Elementaire'): ?>
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                        Programme : Fondamental (Maths, Français, IST, Vivre ensemble & dans son milieu, EPS, HG)
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <table class="rc-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 28%;">Discipline / Matière</th>
                    <th style="width: 20%;">Professeur</th>
                    <th style="width: 10%;">Moyenne /20</th>
                    <th style="width: 8%;">Coef</th>
                    <th style="width: 10%;">Moy. x Coef</th>
                    <th style="width: 9%;">Moy. Classe</th>
                    <th style="text-align: left; width: 25%;">Appréciations du Professeur</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bulletin['details'] as $item): ?>
                    <tr>
                        <td class="bold"><?= e($item['subject_name']) ?></td>
                        <td>M. <?= e($item['teacher_last_name']) ?></td>
                        <td class="text-center bold" style="font-size: 14px; color: #1e3a8a;">
                            <?= number_format($item['subject_average'], 2) ?>
                        </td>
                        <td class="text-center"><?= $item['coefficient'] ?></td>
                        <td class="text-center bold"><?= number_format($item['weighted_average'], 2) ?></td>
                        <td class="text-center" style="color: #64748b;"><?= number_format($item['class_subject_average'], 2) ?></td>
                        <td style="font-size: 12px; font-style: italic; color: #334155;">
                            <?= e($item['teacher_appreciation'] ?? 'Travail régulier et sérieux.') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f1f5f9; font-weight: 800;">
                    <td colspan="3" style="text-align: right; padding-right: 12px;">TOTAUX PONDÉRÉS :</td>
                    <td class="text-center"><?= $bulletin['total_coefficients'] ?></td>
                    <td class="text-center"><?= number_format($bulletin['total_points'], 2) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

        <div class="rc-summary-grid">
            <div class="rc-box" style="background: #f8fafc;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span>Moyenne Générale :</span>
                    <strong style="font-size: 18px; color: #1e3a8a;"><?= number_format($bulletin['general_average'], 2) ?> / 20</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span>Rang de l'Élève :</span>
                    <strong style="font-size: 15px; color: #b45309;"><?= $bulletin['class_rank'] ?>er / 35 élèves</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span>Moyenne de la Classe :</span>
                    <span><?= number_format($bulletin['class_average'], 2) ?> / 20</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px; color: #64748b;">
                    <span>Extrêmes de la classe :</span>
                    <span>Min: <?= number_format($bulletin['class_min_average'], 2) ?> &bullet; Max: <?= number_format($bulletin['class_max_average'], 2) ?></span>
                </div>
            </div>

            <div class="rc-box" style="background: #f8fafc;">
                <div style="margin-bottom: 6px;">
                    <strong>Mention du Semestre :</strong>
                    <span class="badge badge-success" style="font-size: 12px;">
                        <?php
                        $gen = (float)$bulletin['general_average'];
                        if ($gen >= 16) echo "Très Bien (Félicitations)";
                        elseif ($gen >= 14) echo "Bien (Tableau d'Honneur)";
                        elseif ($gen >= 12) echo "Assez Bien (Encouragements)";
                        elseif ($gen >= 10) echo "Passable";
                        else echo "Insuffisant";
                        ?>
                    </span>
                </div>
                <div style="margin-bottom: 6px;">
                    <strong>Conduite :</strong> <?= e($bulletin['conduct']) ?>
                </div>
                <div>
                    <strong>Assiduité :</strong> <?= $bulletin['absences_count'] ?> absence(s)
                </div>
            </div>
        </div>

        <div class="rc-box" style="background: #ffffff; border: 2px solid #0f172a; margin-bottom: 20px;">
            <strong style="text-transform: uppercase; font-size: 12px; color: #1e3a8a; display: block; margin-bottom: 4px;">
                <?= ($bulletin['cycle'] === 'Elementaire') ? "Appréciation Globale du Conseil des Maîtres & Décision de la Direction :" : "Appréciation Globale du Conseil de Classe & Décision du Directeur :" ?>
            </strong>
            <p style="font-size: 13.5px; line-height: 1.5; font-style: italic; color: #0f172a;">
                "<?= e($bulletin['principal_appreciation']) ?>"
            </p>
        </div>

        <div class="rc-signatures">
            <div class="sig-box">
                <?= ($bulletin['cycle'] === 'Elementaire') ? "L'Enseignant(e) Titulaire" : "Le Professeur Principal" ?>
            </div>
            <div class="sig-box" style="position: relative;">
                Le Chef d'Établissement / Direction
                <div style="position: absolute; bottom: 8px; right: 20px; border: 2px dashed #94a3b8; border-radius: 50%; width: 65px; height: 65px; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #64748b; text-transform: uppercase; font-weight: 800; transform: rotate(-10deg);">
                    Sceau Officiel
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
