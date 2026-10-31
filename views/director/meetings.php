<?php
// views/director/meetings.php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/CommunicationService.php';

Auth::requireRole(['directeur']);

$schoolId = Auth::schoolId();
$meetings = CommunicationService::getMeetingsForDirector($schoolId);
$pageTitle = "Convocations & Réunions des Professeurs";

$selectedMeetingId = isset($_GET['meeting_id']) ? (int)$_GET['meeting_id'] : null;
$selectedMeeting = $selectedMeetingId ? CommunicationService::getMeetingDetails($schoolId, $selectedMeetingId) : null;

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">👥 Convocations des Professeurs</h1>
        <p class="page-subtitle">Organisation des réunions ordinaires et urgentes du corps professoral</p>
    </div>
    <div>
        <button class="btn btn-accent" onclick="openModal('modal-new-meeting')">
            ➕ Émettre une Nouvelle Convocation
        </button>
    </div>
</div>

<!-- Modal Convocation de Réunion -->
<div id="modal-new-meeting" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:2000; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:var(--radius-lg); width:100%; max-width:620px; padding:24px; box-shadow:var(--shadow-lg);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
            <h3 style="font-size:18px; font-weight:800;">📨 Convoquer les Professeurs à une Réunion</h3>
            <button onclick="closeModal('modal-new-meeting')" style="background:none; border:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>

        <form action="<?= baseUrl('index.php?action=create_meeting') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="form-group">
                <label class="form-label" for="title">Objet / Intitulé de la Convocation *</label>
                <input type="text" name="title" id="title" class="form-control" required placeholder="ex: Conseil Pédagogique Ordinaire ou Réunion de Crise">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="meeting_type">Type de Réunion *</label>
                    <select name="meeting_type" id="meeting_type" class="form-control">
                        <option value="ordinaire">Réunion Ordinaire (Planifiée)</option>
                        <option value="urgente">Réunion Urgente / Exceptionnelle</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="meeting_date">Date & Heure de la Réunion *</label>
                    <input type="datetime-local" name="meeting_date" id="meeting_date" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="location">Lieu de la Réunion *</label>
                <input type="text" name="location" id="location" class="form-control" required value="Salle des Professeurs">
            </div>

            <div class="form-group">
                <label class="form-label" for="agenda">Ordre du Jour Détaillé *</label>
                <textarea name="agenda" id="agenda" class="form-control" rows="5" required placeholder="1. Émargement et adoption de l'ordre du jour...&#10;2. Harmonisation des épreuves...&#10;3. Divers..."></textarea>
            </div>

            <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px;">
                ℹ️ Cette convocation sera automatiquement transmise à tous les enseignants de l'établissement avec demande d'accusé de réception et confirmation de présence.
            </p>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-new-meeting')">Annuler</button>
                <button type="submit" class="btn btn-accent">Diffuser la Convocation Officielle</button>
            </div>
        </form>
    </div>
</div>

<div style="display: grid; grid-template-columns: <?= $selectedMeeting ? '1.2fr 1.8fr' : '1fr' ?>; gap: 24px;">
    <!-- Liste des réunions -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📅 Historique & Calendrier des Réunions</h3>
        </div>
        <div class="card-body">
            <?php if (empty($meetings)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">Aucune réunion n'a encore été convoquée.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date & Heure</th>
                                <th>Intitulé & Type</th>
                                <th>Présences</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($meetings as $m): ?>
                                <tr style="<?= ($selectedMeeting && $selectedMeeting['id'] == $m['id']) ? 'background:#eff6ff;' : '' ?>">
                                    <td>
                                        <strong><?= formatDate($m['meeting_date'], true) ?></strong><br>
                                        <span style="font-size:11px; color:var(--text-muted);">📍 <?= e($m['location']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= e($m['title']) ?></strong><br>
                                        <span class="badge <?= $m['meeting_type'] === 'urgente' ? 'badge-danger' : 'badge-info' ?>">
                                            <?= strtoupper(e($m['meeting_type'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success"><?= $m['count_present'] ?> Présent(s)</span>
                                        <span class="badge badge-warning"><?= $m['count_excused'] ?> Excusé(s)</span>
                                        <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                                            <?= $m['count_read'] ?> / <?= $m['total_invited'] ?> ont lu
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?= baseUrl('index.php?page=director_meetings&meeting_id=' . $m['id']) ?>" class="btn btn-outline btn-sm">
                                            Voir Émargement &rarr;
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

    <!-- Détail et feuille d'émargement d'une réunion sélectionnée -->
    <?php if ($selectedMeeting): ?>
        <div class="card">
            <div class="card-header" style="background:var(--bg-table-header);">
                <div>
                    <span class="badge <?= $selectedMeeting['meeting_type'] === 'urgente' ? 'badge-danger' : 'badge-info' ?>">
                        <?= strtoupper(e($selectedMeeting['meeting_type'])) ?>
                    </span>
                    <h3 class="card-title" style="margin-top:4px; font-size:16px;">
                        <?= e($selectedMeeting['title']) ?>
                    </h3>
                    <div style="font-size:12px; color:var(--text-muted);">
                        📅 <?= formatDate($selectedMeeting['meeting_date'], true) ?> &bullet; 📍 <?= e($selectedMeeting['location']) ?>
                    </div>
                </div>
                <a href="<?= baseUrl('index.php?page=director_meetings') ?>" class="btn btn-outline btn-sm">&times; Fermer</a>
            </div>
            <div class="card-body">
                <div style="background:var(--bg-table-hover); border-left:4px solid var(--primary-light); padding:12px; margin-bottom:18px; border-radius:var(--radius-sm);">
                    <strong style="font-size:12px; text-transform:uppercase; color:var(--text-muted); display:block; margin-bottom:4px;">Ordre du jour :</strong>
                    <p style="font-size:13px; line-height:1.5; white-space:pre-line;"><?= nl2br(e($selectedMeeting['agenda'])) ?></p>
                </div>

                <h4 style="font-size:14px; font-weight:700; margin-bottom:10px;">📋 Feuille d'Émargement & Réponses des Professeurs</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Professeur</th>
                                <th>Lecture Convocation</th>
                                <th>Statut Réponse</th>
                                <th>Observation / Justification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($selectedMeeting['participants'] as $part): ?>
                                <tr>
                                    <td>
                                        <strong>M. <?= e($part['first_name'] . ' ' . $part['last_name']) ?></strong><br>
                                        <span style="font-size:11px; color:var(--text-muted);"><?= e($part['phone']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($part['read_receipt']): ?>
                                            <span class="badge badge-success">✓ Lu le <?= formatDate($part['read_at'], true) ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Non lu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($part['attendance_status'] === 'present'): ?>
                                            <span class="badge badge-success">Présent</span>
                                        <?php elseif ($part['attendance_status'] === 'excuse'): ?>
                                            <span class="badge badge-warning">Excusé</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:12px; color:var(--text-muted);">
                                        <?= e($part['response_comment'] ?? '-') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
