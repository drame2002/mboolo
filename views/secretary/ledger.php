<?php
// views/secretary/ledger.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FinanceService.php';

Auth::requireRole(['secretaire', 'directeur']);

$schoolId = Auth::schoolId();
$ledger = FinanceService::getFinancialLedger($schoolId, 100);

$totalCredits = 0;
$totalDebits = 0;

foreach ($ledger as $item) {
    if ($item['direction'] === 'CREDIT') {
        $totalCredits += (float)$item['amount'];
    } else {
        $totalDebits += (float)$item['amount'];
    }
}
$netTreasury = $totalCredits - $totalDebits;

$pageTitle = "Journal Général des Opérations Financières";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📑 Journal Général des Opérations Financières</h1>
        <p class="page-subtitle">Grand livre comptable et traçabilité de toutes les entrées et sorties de fonds</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#ecfdf5; color:#059669;">📈</div>
        <div>
            <div class="stat-value" style="color:#059669;">+ <?= formatMoney($totalCredits) ?></div>
            <div class="stat-label">Total Recettes / Encaissements</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fef2f2; color:#dc2626;">📉</div>
        <div>
            <div class="stat-value" style="color:#dc2626;">- <?= formatMoney($totalDebits) ?></div>
            <div class="stat-label">Total Dépenses / Salaires</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">🏦</div>
        <div>
            <div class="stat-value" style="color:<?= $netTreasury >= 0 ? 'var(--primary-light)' : 'var(--danger)' ?>;">
                <?= formatMoney($netTreasury) ?>
            </div>
            <div class="stat-label">Solde Net de Trésorerie</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">📜 Journal Chronologique des Transactions (100 dernières opérations)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($ledger)): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 30px;">Aucune opération financière enregistrée.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Horodatage</th>
                            <th>Référence</th>
                            <th>Description de l'Opération</th>
                            <th>Mode de Règlement</th>
                            <th>Sens</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ledger as $tx): ?>
                            <tr>
                                <td><?= formatDate($tx['op_date'], true) ?></td>
                                <td style="font-family: monospace; font-size: 12px; font-weight: 700;">
                                    <?= e($tx['reference']) ?>
                                </td>
                                <td>
                                    <strong><?= e($tx['description']) ?></strong><br>
                                    <span style="font-size:11px; color:var(--text-muted);"><?= e($tx['operation_type']) ?></span>
                                </td>
                                <td><span class="badge badge-secondary"><?= strtoupper(e($tx['payment_method'])) ?></span></td>
                                <td>
                                    <span class="badge <?= $tx['direction'] === 'CREDIT' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $tx['direction'] === 'CREDIT' ? '+ CRÉDIT' : '- DÉBIT' ?>
                                    </span>
                                </td>
                                <td style="font-weight: 800; font-size: 15px; color: <?= $tx['direction'] === 'CREDIT' ? 'var(--success)' : 'var(--danger)' ?>;">
                                    <?= formatMoney($tx['amount']) ?>
                                </td>
                                <td><span class="badge badge-success">EFFECTUÉ</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
