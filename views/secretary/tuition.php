<?php
// views/secretary/tuition.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FinanceService.php';
require_once __DIR__ . '/../../src/AcademicService.php';

Auth::requireRole(['secretaire', 'directeur']);

$schoolId = Auth::schoolId();
$pdo = getDbConnection();
$classes = AcademicService::getClassesBySchool($schoolId);

// Récupération de tous les barèmes de mensualités
$stmtFees = $pdo->prepare("
    SELECT fs.*, c.name as class_name, c.level, c.cycle, c.track,
           (SELECT COUNT(DISTINCT sp.student_id) FROM `student_payments` sp WHERE sp.fee_schedule_id = fs.id AND sp.payment_status = 'paye') as paid_count,
           (SELECT COUNT(u.id) FROM `users` u JOIN `student_profiles` prof ON u.id = prof.user_id WHERE (prof.class_id = fs.class_id OR fs.class_id IS NULL) AND u.school_id = fs.school_id AND u.is_active = 1) as eligible_count
    FROM `fee_schedules` fs
    LEFT JOIN `classes` c ON fs.class_id = c.id
    WHERE fs.school_id = ?
    ORDER BY fs.due_date ASC
");
$stmtFees->execute([$schoolId]);
$feeSchedules = $stmtFees->fetchAll();

$pageTitle = "Gestion des Mensualités & Frais Scolaires";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">💳 Grille des Mensualités & Frais Scolaires</h1>
        <p class="page-subtitle">Suivi des barèmes tarifaires et des échéances de scolarité par classe</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="<?= baseUrl('index.php?page=secretary_record_payment') ?>" class="btn btn-primary">
            💵 Encaisser au Guichet
        </a>
        <a href="<?= baseUrl('index.php?page=secretary_unpaid') ?>" class="btn btn-outline">
            ⏳ Voir les Impayés
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📅 Barème des Échéances de la Scolarité (Année 2025-2026)</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Titre du Frais</th>
                        <th>Classe Applicables</th>
                        <th>Mois / Année</th>
                        <th>Montant Exigible</th>
                        <th>Date Limite</th>
                        <th>Taux d'Encaissement</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feeSchedules as $fs): ?>
                        <?php
                        $eligible = max(1, (int)$fs['eligible_count']);
                        $paid = (int)$fs['paid_count'];
                        $pct = round(($paid / $eligible) * 100);
                        ?>
                        <tr>
                            <td>
                                <strong><?= e($fs['fee_title']) ?></strong>
                            </td>
                            <td>
                                <?php if ($fs['class_name']): ?>
                                    <strong><?= e($fs['class_name']) ?></strong>
                                    <?php if (!empty($fs['track'])): ?>
                                        <span class="badge" style="background:#7c3aed; color:#fff; font-size:10px; margin-left:4px;">Série <?= e($fs['track']) ?></span>
                                    <?php endif; ?>
                                    <div style="font-size:11px; color:var(--text-muted);"><?= e($fs['cycle']) ?></div>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Toutes les classes</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($fs['month']) ?> <?= e($fs['year']) ?></td>
                            <td style="font-weight: 800; font-size: 15px; color: var(--primary-light);">
                                <?= formatMoney($fs['amount_due']) ?>
                            </td>
                            <td><?= formatDate($fs['due_date']) ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex-grow: 1; height: 8px; background: var(--border); border-radius: 4px; overflow: hidden; width: 80px;">
                                        <div style="height: 100%; width: <?= $pct ?>%; background: <?= $pct >= 80 ? '#10b981' : ($pct >= 50 ? '#3b82f6' : '#f97316') ?>;"></div>
                                    </div>
                                    <span style="font-size: 12px; font-weight: 700;"><?= $paid ?> / <?= $eligible ?> (<?= $pct ?>%)</span>
                                </div>
                            </td>
                            <td>
                                <a href="<?= baseUrl('index.php?page=secretary_record_payment&fee_id=' . $fs['id']) ?>" class="btn btn-outline btn-sm">
                                    Encaisser &rarr;
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
