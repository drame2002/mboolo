<?php
// views/student/payments.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FinanceService.php';

Auth::requireRole(['eleve', 'parent']);

$schoolId = Auth::schoolId();
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : Auth::id();

// Si c'est l'élève, forcer son propre ID
if (Auth::role() === 'eleve') {
    $studentId = Auth::id();
}

$status = FinanceService::getStudentFeeStatus($schoolId, $studentId);
$pageTitle = "Paiement en Ligne des Mensualités Scolaires";

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">💳 Paiement en Ligne des Mensualités</h1>
        <p class="page-subtitle">Réglez vos frais de scolarité en toute sécurité via Wave, Orange Money ou Carte Bancaire</p>
    </div>
    <div>
        <a href="<?= baseUrl('index.php?page=student_history' . (Auth::role() === 'parent' ? '&student_id=' . $studentId : '')) ?>" class="btn btn-outline">
            🧾 Historique des Reçus de Paiement
        </a>
    </div>
</div>

<!-- Synthèse financière -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">📚</div>
        <div>
            <div class="stat-value"><?= formatMoney($status['total_due']) ?></div>
            <div class="stat-label">Total Scolarité Exigible</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#ecfdf5; color:#059669;">✅</div>
        <div>
            <div class="stat-value" style="color:#059669;"><?= formatMoney($status['total_paid']) ?></div>
            <div class="stat-label">Total Déjà Réglé</div>
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
            <div class="stat-label">Solde Restant Dû</div>
        </div>
    </div>
</div>

<!-- Modal Paiement Sécurisé en Ligne (Wave / OM / CB) -->
<div id="modal-pay-online" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:2000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); width:100%; max-width:520px; padding:26px; box-shadow:var(--shadow-lg);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="font-size:18px; font-weight:800; color:var(--text-main);">🔒 Passerelle de Paiement Sécurisée MBOOLO</h3>
            <button onclick="closeModal('modal-pay-online')" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <div style="background:var(--bg-table-hover); border:1px solid var(--border); border-radius:var(--radius-md); padding:14px; margin-bottom:18px;">
            <div style="font-size:12px; color:var(--text-muted);">Mensualité sélectionnée :</div>
            <div id="modal-fee-title" style="font-size:16px; font-weight:800; color:var(--primary-light);">Mensualité</div>
            <div style="font-size:20px; font-weight:800; color:var(--success); margin-top:4px;">
                Montant à débiter : <span id="modal-fee-amount">0</span> FCFA
            </div>
        </div>

        <form action="<?= baseUrl('index.php?action=online_payment') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="student_id" value="<?= $studentId ?>">
            <input type="hidden" name="fee_schedule_id" id="modal-fee-id" value="">

            <div class="form-group">
                <label class="form-label">Sélectionnez le mode de paiement :</label>
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
                    <input type="tel" name="phone_number" id="phone_number" class="form-control" placeholder="+221 77 000 00 00 ou 78 ...">
                    <p style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                        Une notification de validation avec code OTP instantané sera envoyée sur votre compte mobile.
                    </p>
                </div>
            </div>

            <!-- Champs Carte Bancaire -->
            <div id="card-fields" style="display:none;">
                <div class="form-group">
                    <label class="form-label" for="card_number">Numéro de Carte Bancaire (Visa / Mastercard) *</label>
                    <input type="text" name="card_number" id="card_number" class="form-control" placeholder="4152 8200 1234 5678">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="card_exp">Date d'Expiration *</label>
                        <input type="text" name="card_exp" id="card_exp" class="form-control" placeholder="MM/AA">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="card_cvv">CVV / Cryptogramme *</label>
                        <input type="password" name="card_cvv" id="card_cvv" class="form-control" placeholder="123" maxlength="3">
                    </div>
                </div>
            </div>

            <div style="background:#ecfdf5; border:1px solid #bbf7d0; border-radius:var(--radius-sm); padding:10px; font-size:12px; color:#166534; margin-bottom:16px;">
                🔒 Transaction 100% sécurisée et chiffrée SSL. Confirmation immédiate par le prestataire.
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-pay-online')">Annuler</button>
                <button type="submit" class="btn btn-success" style="padding:10px 22px; font-weight:700;">
                    ✓ Confirmer & Valider le Paiement
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Échéancier détaillé des mensualités -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📅 Échéancier Officiel des Mensualités & Frais Scolaires</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Période / Titre du Frais</th>
                        <th>Date Limite d'Échéance</th>
                        <th>Montant Exigible</th>
                        <th>Statut de Paiement</th>
                        <th>Réf. Transaction</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($status['fees'] as $fee): ?>
                        <tr>
                            <td>
                                <strong><?= e($fee['fee_title']) ?></strong><br>
                                <span style="font-size:12px; color:var(--text-muted);"><?= e($fee['month'] ?? 'Annuel') ?> <?= e($fee['year']) ?></span>
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
                                    <button class="btn btn-outline btn-sm" onclick="alert('Reçu officiel vérifié : <?= e($fee['paid_ref']) ?>\nDate : <?= e($fee['paid_date']) ?>\nMontant : <?= formatMoney($fee['amount_due']) ?>')">
                                        🧾 Voir Reçu
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
