<?php
// views/communication/announcements.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/CommunicationService.php';

Auth::requireRole(['directeur', 'surveillant', 'professeur', 'secretaire', 'eleve', 'parent']);

$schoolId = Auth::schoolId();
$role = Auth::role();
$announcements = CommunicationService::getAnnouncementsFor($schoolId, $role);
$pageTitle = "Communiqués & Informations Scolaires";

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">📢 Communiqués & Informations Officielles</h1>
        <p class="page-subtitle">Canal d'information officiel émis par la Direction de l'établissement</p>
    </div>
    <?php if ($role === 'directeur'): ?>
        <div>
            <button class="btn btn-primary" onclick="openModal('modal-new-announcement')">
                ➕ Rédiger un Nouveau Communiqué
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Rédaction pour le Directeur -->
<?php if ($role === 'directeur'): ?>
<div id="modal-new-announcement" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); z-index:2000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:var(--bg-card); border: 1px solid var(--border); border-radius:var(--radius-lg); width:100%; max-width:600px; padding:24px; box-shadow:var(--shadow-lg);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="font-size:18px; font-weight:800; color:var(--text-main);">📢 Nouveau Communiqué Officiel</h3>
            <button onclick="closeModal('modal-new-announcement')" style="background:none; border:none; font-size:20px; color:var(--text-muted); cursor:pointer;">&times;</button>
        </div>

        <form action="<?= baseUrl('index.php?action=create_announcement') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="form-group">
                <label class="form-label" for="title">Titre du Communiqué *</label>
                <input type="text" name="title" id="title" class="form-control" required placeholder="ex: Calendrier des Compositions du 1er Semestre">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="target_audience">Public Cible *</label>
                    <select name="target_audience" id="target_audience" class="form-control">
                        <option value="all">Tout le monde (Élèves, Parents, Profs, Personnel)</option>
                        <option value="professeurs">Professeurs uniquement</option>
                        <option value="parents">Parents d'élèves uniquement</option>
                        <option value="eleves">Élèves uniquement</option>
                        <option value="personnel">Personnel administratif uniquement</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="urgency_level">Niveau d'Urgence *</label>
                    <select name="urgency_level" id="urgency_level" class="form-control">
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                        <option value="tres_urgent">Très Urgent / Critique</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="content">Contenu du Communiqué *</label>
                <textarea name="content" id="content" class="form-control" rows="6" required placeholder="Rédigez le texte officiel ici..."></textarea>
            </div>

            <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="is_pinned" id="is_pinned" value="1">
                <label for="is_pinned" style="font-size:13px; font-weight:600; cursor:pointer;">
                    Épingler ce communiqué en haut de liste
                </label>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-new-announcement')">Annuler</button>
                <button type="submit" class="btn btn-primary">Diffuser le Communiqué</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Liste des communiqués -->
<?php if (empty($announcements)): ?>
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 40px;">
            <div style="font-size: 40px; margin-bottom: 12px;">📢</div>
            <h3 style="font-size: 16px; font-weight: 700;">Aucun communiqué actif</h3>
            <p style="color: var(--text-muted); font-size: 13px;">Les annonces officielles apparaîtront ici dès leur publication.</p>
        </div>
    </div>
<?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php foreach ($announcements as $ann): ?>
            <?php
            $isUrgent = in_array($ann['urgency_level'], ['urgent', 'tres_urgent']);
            $borderColor = $ann['urgency_level'] === 'tres_urgent' ? '#dc2626' : ($ann['urgency_level'] === 'urgent' ? '#ea580c' : '#3b82f6');
            ?>
            <div class="card" style="border-left: 5px solid <?= $borderColor ?>; <?= $ann['is_pinned'] ? 'background: var(--bg-table-hover);' : '' ?>">
                <div class="card-header" style="background: transparent;">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <?php if ($ann['is_pinned']): ?>
                            <span class="badge" style="background: rgba(234, 179, 8, 0.2); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.4);">📌 Épinglé</span>
                        <?php endif; ?>

                        <h3 class="card-title" style="font-size: 16px;"><?= e($ann['title']) ?></h3>

                        <span class="badge <?= $isUrgent ? 'badge-danger' : 'badge-secondary' ?>">
                            <?= strtoupper(str_replace('_', ' ', e($ann['urgency_level']))) ?>
                        </span>

                        <span class="badge badge-info" style="font-size: 11px;">
                            Cible : <?= ucfirst(e($ann['target_audience'])) ?>
                        </span>
                    </div>

                    <div style="font-size: 12px; color: var(--text-muted);">
                        Publié le <?= formatDate($ann['published_at'], true) ?>
                    </div>
                </div>

                <div class="card-body">
                    <p style="font-size: 14px; line-height: 1.6; white-space: pre-line; color: var(--text-main);">
                        <?= nl2br(e($ann['content'])) ?>
                    </p>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 18px; padding-top: 12px; border-top: 1px solid var(--border); font-size: 12px; color: var(--text-muted);">
                        <div>
                            Signé par : <strong>M. <?= e($ann['first_name'] . ' ' . $ann['last_name']) ?></strong> (Directeur de l'établissement)
                        </div>

                        <?php if ($role === 'directeur'): ?>
                            <form action="<?= baseUrl('index.php?action=delete_announcement') ?>" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce communiqué ?');">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #ef4444; border-color: #fecaca;">
                                    🗑️ Supprimer
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
