<?php
// views/student/history.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FinanceService.php';

Auth::requireRole(['eleve', 'parent']);

$schoolId = Auth::schoolId();
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : Auth::id();

if (Auth::role() === 'eleve') {
    $studentId = Auth::id();
}

$history = FinanceService::getStudentPaymentHistory($schoolId, $studentId);
$pageTitle = "Historique des Règlements & Reçus";

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">🧾 Historique des Paiements & Reçus</h1>
        <p class="page-subtitle">Relevé officiel de toutes les transactions et quittances de scolarité enregistrées</p>
    </div>
    <div>
        <a href="<?= baseUrl('index.php?page=student_payments' . (Auth::role() === 'parent' ? '&student_id=' . $studentId : '')) ?>" class="btn btn-primary btn-sm">
            💳 Payer une mensualité
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📜 Journal des Quittances & Reçus Officiels</h3>
    </div>
    <div class="card-body">
        <?php if (empty($history)): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 40px;">
                Aucun reçu de paiement enregistré pour cet élève.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>N° Reçu</th>
                            <th>Date & Heure</th>
                            <th>Frais Scolaire Réglé</th>
                            <th>Mode de Règlement</th>
                            <th>Montant Payé</th>
                            <th>Réf. Transaction Prestataire</th>
                            <th>Validé Par</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td style="font-family: monospace; font-weight: 800; color: #1e3a8a;">
                                    <?= e($h['receipt_number']) ?>
                                </td>
                                <td><?= formatDate($h['payment_date'], true) ?></td>
                                <td>
                                    <strong><?= e($h['fee_title']) ?></strong><br>
                                    <span style="font-size:11px; color:var(--text-muted);"><?= e($h['month']) ?> <?= e($h['year']) ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?= strtoupper(e($h['payment_method'])) ?></span>
                                </td>
                                <td style="font-weight: 800; font-size: 15px; color: #166534;">
                                    <?= formatMoney($h['amount_paid']) ?>
                                </td>
                                <td style="font-family: monospace; font-size: 12px;">
                                    <?= e($h['transaction_ref']) ?>
                                </td>
                                <td style="font-size: 12px;">
                                    <?= $h['received_by_user_id'] ? 'Mme ' . e($h['receiver_last_name']) . ' (Secrétariat)' : 'Passerelle Digitale Sécurisée' ?>
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
