<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/NotificationService.php';

Auth::requireRole(['parent']);

$schoolId = Auth::schoolId();
$parentId = Auth::id();

$filter = $_GET['filter'] ?? 'all';
$unreadOnly = ($filter === 'unread');
$typeFilter = in_array($filter, ['attendance', 'report_card', 'payment']) ? $filter : null;

$notifications = NotificationService::getUserNotifications($schoolId, $parentId, 100, $unreadOnly, $typeFilter);
$unreadCount = NotificationService::getUnreadCount($schoolId, $parentId);
$allCount = count(NotificationService::getUserNotifications($schoolId, $parentId, 200, false));

$pageTitle = "Centre de Notifications & Alertes";
require_once __DIR__ . '/../../templates/header.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div>
        <h1 class="page-title" style="display: flex; align-items: center; gap: 10px;">
            <span>🔔</span> Notifications & Alertes aux Parents
            <?php if ($unreadCount > 0): ?>
                <span class="badge badge-danger" style="font-size: 13px; font-weight: 700;"><?= $unreadCount ?> non lue<?= $unreadCount > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </h1>
        <p class="page-subtitle">
            Suivi en direct de la scolarité de vos enfants : alertes d'assiduité, publication des bulletins et quittances de paiement.
        </p>
    </div>

    <?php if ($unreadCount > 0): ?>
        <div>
            <form action="<?= baseUrl('index.php?action=mark_all_notifications_read') ?>" method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <button type="submit" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px;">
                    <span>✓✓</span> Tout marquer comme lu
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Filtres par catégorie -->
<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px;">
    <a href="<?= baseUrl('index.php?page=parent_notifications&filter=all') ?>" 
       class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 9999px;">
        Toutes (<?= $allCount ?>)
    </a>
    <a href="<?= baseUrl('index.php?page=parent_notifications&filter=unread') ?>" 
       class="btn btn-sm <?= $filter === 'unread' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 9999px;">
        Non lues (<?= $unreadCount ?>)
    </a>
    <a href="<?= baseUrl('index.php?page=parent_notifications&filter=attendance') ?>" 
       class="btn btn-sm <?= $filter === 'attendance' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 9999px;">
        ⏱️ Absences & Retards
    </a>
    <a href="<?= baseUrl('index.php?page=parent_notifications&filter=report_card') ?>" 
       class="btn btn-sm <?= $filter === 'report_card' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 9999px;">
        📑 Bulletins Scolaires
    </a>
    <a href="<?= baseUrl('index.php?page=parent_notifications&filter=payment') ?>" 
       class="btn btn-sm <?= $filter === 'payment' ? 'btn-primary' : 'btn-outline' ?>" style="border-radius: 9999px;">
        💳 Paiements & Quittances
    </a>
</div>

<!-- Liste des Notifications -->
<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($notifications)): ?>
            <div style="padding: 60px 20px; text-align: center; color: var(--text-muted);">
                <div style="font-size: 42px; margin-bottom: 12px;">📭</div>
                <h3 style="margin-bottom: 6px; color: var(--text-main);">Aucune notification pour le moment</h3>
                <p style="font-size: 14px; max-width: 480px; margin: 0 auto;">
                    Vous recevrez automatiquement ici (ainsi que par SMS et WhatsApp) toute alerte concernant l'assiduité, les notes et les paiements de vos enfants.
                </p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column;">
                <?php foreach ($notifications as $idx => $notif): 
                    $isUnread = empty($notif['is_read']);
                    $bgStyle = $isUnread ? 'background: rgba(var(--primary-rgb), 0.05);' : 'background: transparent;';
                    $borderBottom = ($idx < count($notifications) - 1) ? 'border-bottom: 1px solid var(--border);' : '';
                    
                    // Icône par type
                    $typeIcon = '🔔';
                    $typeBadgeClass = 'badge-primary';
                    $typeLabel = 'Alerte';
                    $linkAction = null;
                    $linkLabel = null;

                    if ($notif['type'] === 'attendance') {
                        $typeIcon = '⏱️';
                        $typeBadgeClass = 'badge-warning';
                        $typeLabel = 'Vie Scolaire';
                        $linkAction = baseUrl('index.php?page=parent_attendance');
                        $linkLabel = 'Voir assiduité';
                    } elseif ($notif['type'] === 'report_card') {
                        $typeIcon = '📄';
                        $typeBadgeClass = 'badge-info';
                        $typeLabel = 'Bulletin';
                        $linkAction = baseUrl('index.php?page=parent_report_cards');
                        $linkLabel = 'Télécharger le bulletin';
                    } elseif ($notif['type'] === 'payment') {
                        $typeIcon = '💳';
                        $typeBadgeClass = 'badge-success';
                        $typeLabel = 'Finance';
                        $linkAction = baseUrl('index.php?page=parent_finance');
                        $linkLabel = 'Voir reçus & paiements';
                    }

                    // Canal badge
                    $channelBadge = '';
                    switch ($notif['channel']) {
                        case 'sms':
                            $channelBadge = '<span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">💬 SMS (+221)</span>';
                            break;
                        case 'whatsapp':
                            $channelBadge = '<span class="badge" style="background: rgba(37, 211, 102, 0.15); color: #25d366; border: 1px solid rgba(37, 211, 102, 0.3);">🟢 WhatsApp</span>';
                            break;
                        case 'email':
                            $channelBadge = '<span class="badge" style="background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3);">📧 Email</span>';
                            break;
                        default:
                            $channelBadge = '<span class="badge badge-secondary">📱 In-App</span>';
                            break;
                    }
                ?>
                    <div style="padding: 18px 24px; <?= $bgStyle ?> <?= $borderBottom ?> display: flex; gap: 16px; align-items: flex-start; transition: background 0.2s ease;">
                        <div style="font-size: 26px; line-height: 1; padding: 10px; border-radius: 12px; background: rgba(255,255,255,0.04); border: 1px solid var(--border);">
                            <?= $typeIcon ?>
                        </div>

                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 6px;">
                                <span class="badge <?= $typeBadgeClass ?>"><?= $typeLabel ?></span>
                                <?= $channelBadge ?>
                                
                                <?php if (!empty($notif['student_first_name'])): ?>
                                    <span style="font-size: 12px; font-weight: 600; color: var(--text-main);">
                                        Élève : <?= e($notif['student_first_name'] . ' ' . $notif['student_last_name']) ?>
                                        <?php if (!empty($notif['class_name'])): ?>
                                            (<?= e($notif['class_name']) ?>)
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>

                                <span style="font-size: 11px; color: var(--text-muted); margin-left: auto;">
                                    🕒 <?= date('d/m/Y à H:i', strtotime($notif['sent_at'] ?? 'now')) ?>
                                </span>
                            </div>

                            <h4 style="font-size: 15px; font-weight: 700; margin: 0 0 4px; color: var(--text-main);">
                                <?= e($notif['title']) ?>
                                <?php if ($isUnread): ?>
                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--primary); margin-left: 6px; vertical-align: middle;"></span>
                                <?php endif; ?>
                            </h4>

                            <p style="font-size: 13.5px; color: var(--text-muted); line-height: 1.5; margin: 0 0 10px; white-space: pre-line;">
                                <?= e($notif['message']) ?>
                            </p>

                            <div style="display: flex; gap: 12px; align-items: center;">
                                <?php if ($linkAction && $linkLabel): ?>
                                    <a href="<?= $linkAction ?>" class="btn btn-outline btn-sm" style="font-size: 12px;">
                                        <?= $linkLabel ?> &rarr;
                                    </a>
                                <?php endif; ?>

                                <?php if ($isUnread): ?>
                                    <form action="<?= baseUrl('index.php?action=mark_notification_read') ?>" method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="notification_id" value="<?= $notif['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" style="font-size: 12px; color: var(--text-muted);">
                                            ✓ Marquer comme lu
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 11px; color: var(--text-muted);">
                                        ✓ Lu le <?= date('d/m/Y à H:i', strtotime($notif['read_at'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
