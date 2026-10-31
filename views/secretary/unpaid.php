<?php
// views/secretary/unpaid.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FinanceService.php';

Auth::requireRole(['secretaire', 'directeur']);

$schoolId = Auth::schoolId();
$pdo = getDbConnection();

// Récupération de tous les élèves avec calcul de leur solde restant dû
$stmtStudents = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.phone, sp.student_matricule, sp.class_id,
           c.name as class_name, c.cycle, c.track,
           (SELECT p.phone FROM parent_students ps JOIN users p ON ps.parent_id = p.id WHERE ps.student_id = u.id LIMIT 1) as parent_phone
    FROM `users` u
    JOIN `student_profiles` sp ON u.id = sp.user_id
    JOIN `classes` c ON sp.class_id = c.id
    WHERE u.school_id = ? AND u.role = 'eleve' AND u.is_active = 1
    ORDER BY c.name ASC, u.last_name ASC
");
$stmtStudents->execute([$schoolId]);
$students = $stmtStudents->fetchAll();

$unpaidList = [];
$totalDebt = 0;

foreach ($students as $st) {
    $status = FinanceService::getStudentFeeStatus($schoolId, (int)$st['id']);
    if ($status['balance_remaining'] > 0) {
        $st['total_due'] = $status['total_due'];
        $st['total_paid'] = $status['total_paid'];
        $st['balance_remaining'] = $status['balance_remaining'];
        $unpaidList[] = $st;
        $totalDebt += $status['balance_remaining'];
    }
}

$pageTitle = "Suivi des Impayés & Sommes Restant Dues";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">⏳ Sommes Restant Dues & État des Impayés</h1>
        <p class="page-subtitle">Suivi du recouvrement scolaire et relance des familles</p>
    </div>
    <div>
        <span class="badge badge-danger" style="font-size: 15px; padding: 8px 16px;">
            Total des Impayés : <strong><?= formatMoney($totalDebt) ?></strong>
        </span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">⚠️ Liste des Élèves Présentant un Solde Débiteur (<?= count($unpaidList) ?> élèves)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($unpaidList)): ?>
            <div style="text-align: center; padding: 40px; color: var(--success);">
                <div style="font-size: 40px; margin-bottom: 8px;">🎉</div>
                <h3 style="font-size: 18px; font-weight: 700;">Aucun impayé constaté !</h3>
                <p style="font-size: 13px; color: var(--text-muted);">Toutes les mensualités échues ont été réglées par les familles.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Élève</th>
                            <th>Classe / Série</th>
                            <th>Contact Famille</th>
                            <th>Total Exigible</th>
                            <th>Total Réglé</th>
                            <th>Reste à Payer</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unpaidList as $item): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 700;">
                                    <?= e($item['student_matricule']) ?>
                                </td>
                                <td>
                                    <strong><?= e($item['last_name']) ?></strong> <?= e($item['first_name']) ?>
                                </td>
                                <td>
                                    <strong><?= e($item['class_name']) ?></strong><br>
                                    <span style="font-size: 11px; color: var(--text-muted);"><?= e($item['track'] ?? $item['cycle']) ?></span>
                                </td>
                                <td>
                                    📞 <?= e($item['parent_phone'] ?? ($item['phone'] ?? 'Non renseigné')) ?>
                                </td>
                                <td><?= formatMoney($item['total_due']) ?></td>
                                <td style="color: var(--success); font-weight: 600;"><?= formatMoney($item['total_paid']) ?></td>
                                <td style="font-weight: 800; font-size: 15px; color: var(--danger);">
                                    <?= formatMoney($item['balance_remaining']) ?>
                                </td>
                                <td>
                                    <a href="<?= baseUrl('index.php?page=secretary_record_payment&student_id=' . $item['id']) ?>" class="btn btn-primary btn-sm">
                                        💵 Encaisser &rarr;
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
