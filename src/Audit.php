<?php
/**
 * Service de Traçabilité et Audit — MBOOLO
 * Enregistrement, classification et restitution des journaux d'opérations
 */
require_once __DIR__ . '/../config/database.php';

class Audit {
    /**
     * Catégories officielles de classification du journal d'audit
     */
    public const CATEGORY_SECURITY    = 'security';
    public const CATEGORY_FINANCE     = 'finance';
    public const CATEGORY_PEDAGOGY    = 'pedagogy';
    public const CATEGORY_DISCIPLINE  = 'discipline';
    public const CATEGORY_ADMIN       = 'admin';

    /**
     * Enregistrer une trace d'opération dans le journal d'audit
     */
    public static function log(int $schoolId, ?int $userId, string $action, ?string $table = null, ?int $recordId = null, ?string $details = null): void {
        try {
            $pdo = getDbConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("INSERT INTO `audit_logs` (`school_id`, `user_id`, `action`, `target_table`, `record_id`, `details`, `ip_address`) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$schoolId, $userId, $action, $table, $recordId, $details, $ip]);
        } catch (Exception $e) {
            // Ne pas interrompre le flux principal si le log échoue
            error_log("Erreur Audit::log: " . $e->getMessage());
        }
    }

    /**
     * Déterminer la catégorie, l'icône, le libellé et le badge visuel d'une action
     */
    public static function getActionCategory(string $action): array {
        $actionUpper = strtoupper(trim($action));

        // 1. Sécurité & Authentification
        if (in_array($actionUpper, ['LOGIN_SUCCESS', 'LOGIN_FAILED', 'LOGOUT', 'REGISTER_SUCCESS', 'PASSWORD_RESET', 'PASSWORD_CHANGE'])) {
            $isFailed = ($actionUpper === 'LOGIN_FAILED');
            return [
                'key'         => self::CATEGORY_SECURITY,
                'label'       => 'Sécurité & Accès',
                'icon'        => $isFailed ? '🚨' : '🔐',
                'badge_class' => $isFailed ? 'badge-danger' : 'badge-info',
                'color'       => $isFailed ? 'var(--danger)' : '#2563eb',
                'action_name' => match ($actionUpper) {
                    'LOGIN_SUCCESS'    => 'Connexion réussie',
                    'LOGIN_FAILED'     => 'Échec de connexion (Alerte)',
                    'LOGOUT'           => 'Déconnexion utilisateur',
                    'REGISTER_SUCCESS' => 'Création / Inscription compte',
                    default            => 'Événement de sécurité'
                }
            ];
        }

        // 2. Opérations Financières & Trésorerie
        if (in_array($actionUpper, ['PAIEMENT_EN_LIGNE_CONFIRME', 'PAIEMENT_GUICHET_ENREGISTRE', 'SALAIRE_TRAITE', 'PAIEMENT_ANNULE', 'REMBOURSEMENT'])) {
            return [
                'key'         => self::CATEGORY_FINANCE,
                'label'       => 'Finances & Trésorerie',
                'icon'        => '💳',
                'badge_class' => 'badge-success',
                'color'       => '#10b981',
                'action_name' => match ($actionUpper) {
                    'PAIEMENT_EN_LIGNE_CONFIRME'   => 'Paiement en ligne validé',
                    'PAIEMENT_GUICHET_ENREGISTRE'  => 'Encaissement au guichet',
                    'SALAIRE_TRAITE'               => 'Ordonnancement salaire',
                    default                        => 'Transaction financière'
                }
            ];
        }

        // 3. Pédagogie & Évaluations
        if (in_array($actionUpper, ['EVALUATION_CREEE', 'NOTES_SAISIES', 'RESUME_LECON_PUBLIE', 'BULLETIN_GENERE', 'MODIFICATION_NOTES'])) {
            return [
                'key'         => self::CATEGORY_PEDAGOGY,
                'label'       => 'Pédagogie & Notes',
                'icon'        => '🎓',
                'badge_class' => 'badge-warning',
                'color'       => '#f59e0b',
                'action_name' => match ($actionUpper) {
                    'EVALUATION_CREEE'   => 'Création d\'évaluation',
                    'NOTES_SAISIES'      => 'Saisie / Mise à jour notes',
                    'RESUME_LECON_PUBLIE'=> 'Cahier de textes / Résumé',
                    'BULLETIN_GENERE'    => 'Génération bulletin officiel',
                    default              => 'Acte pédagogique'
                }
            ];
        }

        // 4. Vie Scolaire, Assiduité & Discipline
        if (in_array($actionUpper, [
            'AJOUT_CRENEAU_EMPLOI_DU_TEMPS', 'SUPPRESSION_CRENEAU_EMPLOI_DU_TEMPS', 
            'ENREGISTREMENT_ASSIDUITE', 'JUSTIFICATION_ASSIDUITE', 
            'SIGNALEMENT_DISCIPLINE', 'CLOTURE_DISCIPLINE'
        ])) {
            return [
                'key'         => self::CATEGORY_DISCIPLINE,
                'label'       => 'Vie Scolaire & Discipline',
                'icon'        => '⏱️',
                'badge_class' => 'badge-secondary',
                'color'       => '#8b5cf6',
                'action_name' => match ($actionUpper) {
                    'AJOUT_CRENEAU_EMPLOI_DU_TEMPS'       => 'Créneau horaire ajouté',
                    'SUPPRESSION_CRENEAU_EMPLOI_DU_TEMPS' => 'Créneau horaire supprimé',
                    'ENREGISTREMENT_ASSIDUITE'            => 'Absence / Retard consigné',
                    'JUSTIFICATION_ASSIDUITE'             => 'Assiduité justifiée',
                    'SIGNALEMENT_DISCIPLINE'              => 'Rapport d\'incident / Sanction',
                    'CLOTURE_DISCIPLINE'                  => 'Dossier disciplinaire clos',
                    default                               => 'Vie scolaire'
                }
            ];
        }

        // 5. Administration & Système
        return [
            'key'         => self::CATEGORY_ADMIN,
            'label'       => 'Administration & Système',
            'icon'        => '🏛️',
            'badge_class' => 'badge-info',
            'color'       => '#3b82f6',
            'action_name' => match ($actionUpper) {
                'COMMUNIQUE_PUBLIE'     => 'Diffusion communiqué officiel',
                'CONVOCATION_CREEE'     => 'Convocation de réunion',
                'ENFANT_ASSOCIE'        => 'Rapprochement compte parent',
                'UPDATE_SCHOOL_SETTINGS'=> 'Paramètres & Logo école',
                default                 => str_replace('_', ' ', $actionUpper)
            }
        ];
    }

    /**
     * Récupérer les journaux récents (maintien de compatibilité avec tests)
     */
    public static function getRecentLogs(int $schoolId, int $limit = 30): array {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, u.first_name, u.last_name, u.role, u.username
            FROM `audit_logs` a
            LEFT JOIN `users` u ON a.user_id = u.id
            WHERE a.school_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $schoolId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Récupérer les logs classés par filtre et catégorie
     */
    public static function getFilteredLogs(int $schoolId, ?string $category = null, ?string $role = null, int $limit = 100): array {
        $logs = self::getRecentLogs($schoolId, $limit);

        return array_values(array_filter($logs, function($log) use ($category, $role) {
            // Filtre de catégorie
            if ($category && $category !== 'all') {
                $catInfo = self::getActionCategory($log['action']);
                if ($catInfo['key'] !== $category) {
                    return false;
                }
            }
            // Filtre de rôle
            if ($role && $role !== 'all') {
                if (($log['role'] ?? '') !== $role) {
                    return false;
                }
            }
            return true;
        }));
    }

    /**
     * Compter le nombre d'événements par catégorie
     */
    public static function getCategoryCounts(int $schoolId): array {
        $logs = self::getRecentLogs($schoolId, 300);
        $counts = [
            'all'                        => count($logs),
            self::CATEGORY_SECURITY    => 0,
            self::CATEGORY_FINANCE     => 0,
            self::CATEGORY_PEDAGOGY    => 0,
            self::CATEGORY_DISCIPLINE  => 0,
            self::CATEGORY_ADMIN       => 0,
        ];

        foreach ($logs as $log) {
            $cat = self::getActionCategory($log['action'])['key'];
            if (isset($counts[$cat])) {
                $counts[$cat]++;
            }
        }

        return $counts;
    }
}
