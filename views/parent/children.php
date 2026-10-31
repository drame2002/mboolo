<?php
// views/parent/children.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/ParentService.php';
require_once __DIR__ . '/../../src/AcademicService.php';
require_once __DIR__ . '/../../src/FinanceService.php';

Auth::requireRole(['parent']);

$parentId = Auth::id();
$schoolId = Auth::schoolId();
$children = ParentService::getChildren($schoolId, $parentId);
$pageTitle = "Mes Enfants Rattachés";

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">👨‍👩‍👧 Mes Enfants Rattachés</h1>
        <p class="page-subtitle">Suivi complet de la scolarité, des résultats et des mensualités de vos enfants</p>
    </div>
    <div>
        <a href="<?= baseUrl('index.php?page=parent_link_child') ?>" class="btn btn-primary">
            ➕ Rapprocher / Associer un Enfant
        </a>
    </div>
</div>

<?php if (empty($children)): ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 40px;">
            <div style="font-size: 44px; margin-bottom: 12px;">👨‍👩‍👧</div>
            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">Aucun élève n'est encore associé à votre compte</h3>
            <p style="font-size: 14px; color: var(--text-muted); max-width: 520px; margin: 0 auto 20px auto;">
                Pour accéder aux notes, bulletins et règlements de vos enfants, veuillez saisir leur nom ou leur numéro de matricule afin de les rattacher à votre espace.
            </p>
            <a href="<?= baseUrl('index.php?page=parent_link_child') ?>" class="btn btn-primary">
                Associer un Enfant Maintenant &rarr;
            </a>
        </div>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px;">
        <?php foreach ($children as $child): ?>
            <?php
            $gradesOverview = AcademicService::getStudentGradesOverview($schoolId, (int)$child['id'], 'Semestre 1');
            $feeStatus = FinanceService::getStudentFeeStatus($schoolId, (int)$child['id']);
            ?>
            <div class="card">
                <div class="card-header" style="background: var(--bg-table-header); border-bottom: 1px solid var(--border);">
                    <div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <span class="badge badge-info" style="font-family: monospace;">
                                <?= e($child['student_matricule']) ?>
                            </span>
                            <span class="badge badge-secondary">Lien : <?= e($child['relationship_type']) ?></span>
                            <?php if ($child['cycle'] === 'Elementaire'): ?>
                                <span class="badge" style="background: #10b981; color: #fff;">🌱 Élémentaire</span>
                            <?php elseif ($child['cycle'] === 'College'): ?>
                                <span class="badge" style="background: #f59e0b; color: #fff;">📐 Collège</span>
                            <?php else: ?>
                                <span class="badge" style="background: #3b82f6; color: #fff;">🎓 Lycée</span>
                            <?php endif; ?>
                            <?php if (!empty($child['track'])): ?>
                                <span class="badge" style="background: #7c3aed; color: #fff;">Série <?= e($child['track']) ?></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="card-title" style="font-size: 18px;">
                            <?= e($child['first_name'] . ' ' . $child['last_name']) ?>
                        </h3>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                            Classe : <strong><?= e($child['class_name']) ?></strong>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Indicateurs clés -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                        <div style="background: var(--bg-table-hover); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--primary-light); font-weight: 700;">Moyenne S1</span>
                            <div style="font-size: 22px; font-weight: 800; color: var(--text-main);">
                                <?= $gradesOverview['general_average'] ? number_format($gradesOverview['general_average'], 2) . ' / 20' : '-' ?>
                            </div>
                        </div>

                        <div style="background: var(--bg-table-hover); padding: 12px; border-radius: var(--radius-sm); border: 1px solid <?= $feeStatus['balance_remaining'] > 0 ? 'rgba(239, 68, 68, 0.4)' : 'rgba(16, 185, 129, 0.4)' ?>;">
                            <span style="font-size: 11px; text-transform: uppercase; color: <?= $feeStatus['balance_remaining'] > 0 ? 'var(--danger)' : 'var(--success)' ?>; font-weight: 700;">Reste Scolarité</span>
                            <div style="font-size: 18px; font-weight: 800; color: <?= $feeStatus['balance_remaining'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                                <?= formatMoney($feeStatus['balance_remaining']) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Actions pour cet enfant -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="<?= baseUrl('index.php?page=parent_student_grades&child_id=' . $child['id']) ?>" class="btn btn-outline" style="justify-content: flex-start;">
                            📈 Consulter les Notes & Résultats
                        </a>
                        <a href="<?= baseUrl('index.php?page=parent_student_bulletin&child_id=' . $child['id']) ?>" class="btn btn-outline" style="justify-content: flex-start;">
                            📜 Télécharger le Bulletin Scolaire Semestriel
                        </a>
                        <a href="<?= baseUrl('index.php?page=parent_tuition&child_id=' . $child['id']) ?>" class="btn btn-accent" style="justify-content: flex-start;">
                            💳 Régler la Mensualité en Ligne (Wave / OM / CB)
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
