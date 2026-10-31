<?php
// views/secretary/record_payment.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/FinanceService.php';

Auth::requireRole(['secretaire', 'directeur']);

$schoolId = Auth::schoolId();
$pdo = getDbConnection();

// Récupération de tous les élèves de l'école
$stmtStudents = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, sp.student_matricule, c.name as class_name, c.cycle, c.track
    FROM `users` u
    JOIN `student_profiles` sp ON u.id = sp.user_id
    JOIN `classes` c ON sp.class_id = c.id
    WHERE u.school_id = ? AND u.role = 'eleve' AND u.is_active = 1
    ORDER BY c.cycle ASC, c.name ASC, u.last_name ASC
");
$stmtStudents->execute([$schoolId]);
$students = $stmtStudents->fetchAll();

// Récupération des échéances de frais
$stmtFees = $pdo->prepare("
    SELECT fs.*, c.name as class_name
    FROM `fee_schedules` fs
    LEFT JOIN `classes` c ON fs.class_id = c.id
    WHERE fs.school_id = ?
    ORDER BY fs.due_date ASC
");
$stmtFees->execute([$schoolId]);
$fees = $stmtFees->fetchAll();

$preselectedFeeId = isset($_GET['fee_id']) ? (int)$_GET['fee_id'] : 0;
$preselectedStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

$pageTitle = "Enregistrement d'un Paiement au Guichet";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">💵 Enregistrer un Paiement au Guichet</h1>
        <p class="page-subtitle">Émission immédiate d'un reçu officiel de scolarité pour règlement physique</p>
    </div>
    <div>
        <a href="<?= baseUrl('index.php?page=secretary_unpaid') ?>" class="btn btn-outline">
            ⏳ Liste des Impayés & Restes Dûs
        </a>
    </div>
</div>

<div style="max-width: 760px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🧾 Bordereau d'Encaissement au Comptoir Administratif</h3>
        </div>
        <div class="card-body" style="padding: 28px;">
            <form action="<?= baseUrl('index.php?action=record_counter_payment') ?>" method="POST" id="paymentForm">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <div class="form-group">
                    <label class="form-label" for="student_id">Sélectionner l'Élève Bénéficiaire *</label>
                    <select name="student_id" id="student_id" class="form-control" required>
                        <option value="">-- Choisissez un élève inscrit --</option>
                        <?php foreach ($students as $st): ?>
                            <option value="<?= $st['id'] ?>" <?= $st['id'] == $preselectedStudentId ? 'selected' : '' ?>>
                                [<?= e($st['student_matricule']) ?>] <?= e($st['last_name']) ?> <?= e($st['first_name']) ?> — <?= e($st['class_name']) ?><?= !empty($st['track']) ? ' (Série ' . e($st['track']) . ')' : '' ?> (<?= e($st['cycle']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="fee_schedule_id">Mensualité / Échéance Concernée *</label>
                        <select name="fee_schedule_id" id="fee_schedule_id" class="form-control" onchange="autoFillAmount()" required>
                            <option value="">-- Sélectionnez l'échéance --</option>
                            <?php foreach ($fees as $f): ?>
                                <option value="<?= $f['id'] ?>" data-amount="<?= $f['amount_due'] ?>" <?= $f['id'] == $preselectedFeeId ? 'selected' : '' ?>>
                                    <?= e($f['fee_title']) ?> (<?= formatMoney($f['amount_due']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="amount_paid">Montant Encaissé (FCFA) *</label>
                        <input type="number" step="500" name="amount_paid" id="amount_paid" class="form-control" required placeholder="ex: 45000">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="payment_method">Mode d'Encaissement *</label>
                        <select name="payment_method" id="payment_method" class="form-control">
                            <option value="especes">Espèces (Comptant au guichet)</option>
                            <option value="cheque">Chèque bancaire</option>
                            <option value="virement">Virement bancaire direct</option>
                            <option value="wave">Wave (Règlement assisté)</option>
                            <option value="orange_money">Orange Money (Règlement assisté)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="receipt_number">Numéro de Reçu Officiel (Généré automatiquement)</label>
                        <input type="text" name="receipt_number" id="receipt_number" class="form-control" value="REC-<?= date('Y') ?>-<?= rand(10000, 99999) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="notes">Observations / Références du Payeur (Parent, tuteur)</label>
                    <input type="text" name="notes" id="notes" class="form-control" placeholder="ex: Réglé par le père M. Diop - Reçu remis en mains propres">
                </div>

                <div style="background: var(--bg-table-hover); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px; margin-bottom: 20px; font-size: 13px; color: var(--text-muted);">
                    🔒 Cette opération est enregistrée sous votre responsabilité d'agent comptable. Elle mettra immédiatement à jour le solde restant dû de l'élève et sera archivée dans le journal d'audit.
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <a href="<?= baseUrl('index.php?page=secretary_tuition') ?>" class="btn btn-outline">Annuler</a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        ✓ Enregistrer & Émettre le Reçu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function autoFillAmount() {
    const select = document.getElementById('fee_schedule_id');
    const selectedOption = select.options[select.selectedIndex];
    const amount = selectedOption.getAttribute('data-amount');
    if (amount) {
        document.getElementById('amount_paid').value = amount;
    }
}
document.addEventListener('DOMContentLoaded', autoFillAmount);
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
