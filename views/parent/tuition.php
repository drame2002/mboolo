<?php
// views/parent/tuition.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/ParentService.php';
require_once __DIR__ . '/../../src/FinanceService.php';

Auth::requireRole(['parent']);

$parentId = Auth::id();
$schoolId = Auth::schoolId();
$children = ParentService::getChildren($schoolId, $parentId);

if (empty($children)) {
    setFlash('warning', "Veuillez d'abord associer un enfant à votre compte pour régler sa scolarité.");
    header("Location: " . baseUrl('index.php?page=parent_link_child'));
    exit;
}

$selectedChildId = isset($_GET['child_id']) ? (int)$_GET['child_id'] : (int)$children[0]['id'];
$status = FinanceService::getStudentFeeStatus($schoolId, $selectedChildId);

// Trouver l'enfant sélectionné
$currentChild = $children[0];
foreach ($children as $c) {
    if ($c['id'] == $selectedChildId) {
        $currentChild = $c;
        break;
    }
}

$pageTitle = "Paiement des Mensualités de " . e($currentChild['first_name']);
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">💳 Mensualités & Paiement en Ligne</h1>
        <p class="page-subtitle">Réglez les frais scolaires de votre enfant en toute sécurité (Wave, Orange Money, Carte Bancaire)</p>
    </div>
    <!-- Sélecteur d'enfant -->
    <div style="display: flex; gap: 10px;">
        <form method="GET" action="<?= baseUrl('index.php') ?>" style="display: flex; gap: 8px;">
            <input type="hidden" name="page" value="parent_tuition">
            <select name="child_id" class="form-control" onchange="this.form.submit()" style="font-weight: 700;">
                <?php foreach ($children as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $selectedChildId ? 'selected' : '' ?>>
                        👤 <?= e($c['first_name'] . ' ' . $c['last_name']) ?> (<?= e($c['class_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <a href="<?= baseUrl('index.php?page=student_history&student_id=' . $selectedChildId) ?>" class="btn btn-outline btn-sm">
            🧾 Historique des Reçus
        </a>
    </div>
</div>

<!-- Synthèse financière -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">📚</div>
        <div>
            <div class="stat-value"><?= formatMoney($status['total_due']) ?></div>
            <div class="stat-label">Total Exigible Scolarité</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#ecfdf5; color:#059669;">✅</div>
        <div>
            <div class="stat-value" style="color:#059669;"><?= formatMoney($status['total_paid']) ?></div>
            <div class="stat-label">Total Déjà Payé</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:<?= $status['balance_remaining'] > 0 ? '#fef2f2' : '#f0fdf4' ?>; color:<?= $status['balance_remaining'] > 0 ? '#dc2626' : '#16a34a' ?>;">
            <?= $status['balance_remaining'] > 0 ? '⏳' : '🎉' ?>
        </div>
        <div>
            <div class="stat-value" style="color:<?= $status['balance_remaining'] > 0 ? '#dc2626' : '#16a34a' ?>;">
                <?= formatMoney($status['balance_remaining']) ?>
            </div>
            <div class="stat-label">Reste à Payer</div>
        </div>
    </div>
</div>

<!-- Modal Paiement Sécurisé en Ligne (Wave / OM / CB) -->
<div id="modal-pay-online" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:2000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); width:100%; max-width:520px; padding:26px; box-shadow:var(--shadow-lg);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="font-size:18px; font-weight:800; color:var(--text-main);">🔒 Règlement Sécurisé MBOOLO Pay</h3>
            <button onclick="closeModal('modal-pay-online')" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <div style="background:var(--bg-table-hover); border:1px solid var(--border); border-radius:var(--radius-md); padding:14px; margin-bottom:18px;">
            <div style="font-size:12px; color:var(--text-muted);">Élève concerné : <strong style="color:var(--text-main);"><?= e($currentChild['first_name'] . ' ' . $currentChild['last_name']) ?></strong></div>
            <div id="modal-fee-title" style="font-size:16px; font-weight:800; color:var(--primary-light); margin-top:2px;">Mensualité</div>
            <div style="font-size:20px; font-weight:800; color:var(--success); margin-top:4px;">
                Montant : <span id="modal-fee-amount">0</span> FCFA
            </div>
        </div>

        <form action="<?= baseUrl('index.php?action=online_payment') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="student_id" value="<?= $selectedChildId ?>">
            <input type="hidden" name="fee_schedule_id" id="modal-fee-id" value="">

            <div class="form-group">
                <label class="form-label">Sélectionnez le prestataire de paiement :</label>
                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; margin-top:8px;">
                    <label style="border:2px solid #3b82f6; border-radius:var(--radius-md); padding:12px 6px; text-align:center; cursor:pointer; background:rgba(59, 130, 246, 0.1);">
                        <input type="radio" name="payment_method" value="wave" checked onchange="togglePaymentInputs('wave')">
                        <div style="font-weight:800; color:#3b82f6; margin-top:4px;">🌊 Wave</div>
                    </label>

                    <label style="border:1px solid var(--border); border-radius:var(--radius-md); padding:12px 6px; text-align:center; cursor:pointer; background:rgba(249, 115, 22, 0.1);">
                        <input type="radio" name="payment_method" value="orange_money" onchange="togglePaymentInputs('orange_money')">
                        <div style="font-weight:800; color:#f97316; margin-top:4px;">🍊 Orange Money</div>
                    </label>

                    <label style="border:1px solid var(--border); border-radius:var(--radius-md); padding:12px 6px; text-align:center; cursor:pointer; background:var(--bg-table-header);">
                        <input type="radio" name="payment_method" value="carte_bancaire" onchange="togglePaymentInputs('carte_bancaire')">
                        <div style="font-weight:800; color:var(--text-main); margin-top:4px;">💳 Carte Bancaire</div>
                    </label>
                </div>
            </div>

            <!-- Champs Wave / OM -->
            <div id="mobile-money-fields">
                <div class="form-group">
                    <label class="form-label" for="phone_number">Numéro de Téléphone Mobile (Sénégal) *</label>
                    <input type="tel" name="phone_number" id="phone_number" class="form-control" placeholder="+221 77 ... ou 78 ...">
                    <p style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                        Vous recevrez une invite de débit instantanée sur votre application mobile pour valider la transaction.
                    </p>
                </div>
            </div>

            <!-- Champs Carte Bancaire -->
            <div id="card-fields" style="display:none;">
                <div class="form-group">
                    <label class="form-label" for="card_number">Numéro de Carte Bancaire *</label>
                    <input type="text" name="card_number" id="card_number" class="form-control" placeholder="4152 8200 1234 5678">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="card_exp">Date d'Expiration *</label>
                        <input type="text" name="card_exp" id="card_exp" class="form-control" placeholder="MM/AA">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="card_cvv">CVV *</label>
                        <input type="password" name="card_cvv" id="card_cvv" class="form-control" placeholder="123" maxlength="3">
                    </div>
                </div>
            </div>

            <div style="background:#ecfdf5; border:1px solid #bbf7d0; border-radius:var(--radius-sm); padding:10px; font-size:12px; color:#166534; margin-bottom:16px;">
                🔒 Transaction vérifiée et confirmée avant édition immédiate de la quittance officielle.
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-pay-online')">Annuler</button>
                <button type="submit" class="btn btn-success" style="padding:10px 22px; font-weight:700;">
                    ✓ Valider & Régler la Mensualité
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Échéancier de l'enfant -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📅 Échéancier des Mensualités de l'Enfant</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mois / Titre</th>
                        <th>Date Limite</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Réf. Paiement</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($status['fees'] as $fee): ?>
                        <tr>
                            <td>
                                <strong><?= e($fee['fee_title']) ?></strong><br>
                                <span style="font-size:12px; color:var(--text-muted);"><?= e($fee['month']) ?> <?= e($fee['year']) ?></span>
                            </td>
                            <td><?= formatDate($fee['due_date']) ?></td>
                            <td style="font-weight: 800; font-size: 15px; color: #1e3a8a;">
                                <?= formatMoney($fee['amount_due']) ?>
                            </td>
                            <td>
                                <?php if ($fee['is_paid']): ?>
                                    <span class="badge badge-success">✓ PAYÉ LE <?= formatDate($fee['paid_date']) ?></span>
                                    <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                                        Mode : <?= strtoupper(e($fee['paid_method'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge badge-warning">⏳ EN ATTENTE DE RÈGLEMENT</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: monospace; font-size: 12px;">
                                <?= e($fee['paid_ref'] ?? '-') ?>
                            </td>
                            <td>
                                <?php if ($fee['is_paid']): ?>
                                    <button class="btn btn-outline btn-sm" onclick="alert('Reçu de scolarité certifié : <?= e($fee['paid_ref']) ?>\nDate : <?= e($fee['paid_date']) ?>\nMontant : <?= formatMoney($fee['amount_due']) ?>')">
                                        🧾 Quittance Payée
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-accent btn-sm" 
                                            onclick="openPaymentModal(<?= $fee['id'] ?>, '<?= addslashes(e($fee['fee_title'])) ?>', '<?= number_format($fee['amount_due'], 0, ',', ' ') ?>')">
                                        💳 Payer en Ligne &rarr;
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openPaymentModal(feeId, feeTitle, amountFormatted) {
    document.getElementById('modal-fee-id').value = feeId;
    document.getElementById('modal-fee-title').innerText = feeTitle;
    document.getElementById('modal-fee-amount').innerText = amountFormatted;
    openModal('modal-pay-online');
}

function togglePaymentInputs(method) {
    const mobileFields = document.getElementById('mobile-money-fields');
    const cardFields = document.getElementById('card-fields');

    if (method === 'carte_bancaire') {
        mobileFields.style.display = 'none';
        cardFields.style.display = 'block';
    } else {
        mobileFields.style.display = 'block';
        cardFields.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
