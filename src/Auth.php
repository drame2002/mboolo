<?php
/**
 * Gestion de l'Authentification et des Droits d'Accès (RBAC) — MBOOLO
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Audit.php';

class Auth {
    
    public static function user(): ?array {
        return $_SESSION['mboolo_user'] ?? null;
    }

    public static function school(): ?array {
        return $_SESSION['mboolo_school'] ?? null;
    }

    public static function check(): bool {
        return isset($_SESSION['mboolo_user']['id']);
    }

    public static function id(): ?int {
        return $_SESSION['mboolo_user']['id'] ?? null;
    }

    public static function schoolId(): ?int {
        return $_SESSION['mboolo_user']['school_id'] ?? ($_SESSION['mboolo_school']['id'] ?? null);
    }

    public static function role(): ?string {
        return $_SESSION['mboolo_user']['role'] ?? null;
    }

    public static function hasRole($roles): bool {
        if (!self::check()) return false;
        $currentRole = self::role();
        if (is_array($roles)) {
            return in_array($currentRole, $roles);
        }
        return $currentRole === $roles;
    }

    public static function requireRole($roles): void {
        if (!self::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header("Location: index.php?page=login");
            exit;
        }

        if (!self::hasRole($roles)) {
            http_response_code(403);
            die("<!DOCTYPE html><html><head><meta charset='utf-8'><title>Accès Refusé — MBOOLO</title><style>body{font-family:sans-serif;text-align:center;padding:50px;background:#f8fafc;color:#1e293b;}</style></head><body><h1>403 — Accès Non Autorisé</h1><p>Votre rôle actuel (" . htmlspecialchars(self::role()) . ") ne vous permet pas d'accéder à cette ressource sécurisée.</p><p><a href='index.php?page=dashboard'>Retourner à mon espace</a> | <a href='index.php?page=logout'>Se déconnecter</a></p></body></html>");
        }
    }

    /**
     * Authentification utilisateur avec vérification du rôle et de l'établissement
     */
    public static function attempt(int $schoolId, string $identifier, string $password, string $role): array {
        $pdo = getDbConnection();

        // 1. Vérification établissement
        $stmtSchool = $pdo->prepare("SELECT * FROM `schools` WHERE `id` = ? AND `is_active` = 1");
        $stmtSchool->execute([$schoolId]);
        $school = $stmtSchool->fetch();

        if (!$school) {
            return ['success' => false, 'message' => "Établissement scolaire introuvable ou inactif."];
        }

        // 2. Recherche de l'utilisateur par email OU identifiant au sein de cet établissement
        $stmt = $pdo->prepare("
            SELECT * FROM `users` 
            WHERE `school_id` = ? 
              AND (`username` = ? OR `email` = ?)
              AND `is_active` = 1
            LIMIT 1
        ");
        $stmt->execute([$schoolId, $identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => "Identifiant ou adresse e-mail introuvable dans cet établissement."];
        }

        // 3. Vérification du rôle choisi
        if ($user['role'] !== $role) {
            return ['success' => false, 'message' => "Le rôle sélectionné ne correspond pas aux privilèges de ce compte."];
        }

        // 4. Vérification du mot de passe
        if (!password_verify($password, $user['password_hash'])) {
            Audit::log($schoolId, $user['id'], 'LOGIN_FAILED', 'users', $user['id'], "Tentative avec mot de passe erroné pour: {$identifier}");
            return ['success' => false, 'message' => "Mot de passe incorrect."];
        }

        // 5. Récupération des informations additionnelles si c'est un élève
        $profile = null;
        if ($user['role'] === 'eleve') {
            $stmtProf = $pdo->prepare("
                SELECT sp.*, c.name as class_name, c.level, c.cycle, c.track 
                FROM `student_profiles` sp 
                JOIN `classes` c ON sp.class_id = c.id 
                WHERE sp.user_id = ?
            ");
            $stmtProf->execute([$user['id']]);
            $profile = $stmtProf->fetch();
        }

        // 6. Enregistrement en session
        $_SESSION['mboolo_user'] = [
            'id' => $user['id'],
            'school_id' => $school['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'phone' => $user['phone'],
            'gender' => $user['gender'],
            'avatar_path' => $user['avatar_path'],
            'student_profile' => $profile
        ];

        $_SESSION['mboolo_school'] = $school;

        // 7. Audit log de connexion
        Audit::log($school['id'], $user['id'], 'LOGIN_SUCCESS', 'users', $user['id'], "Connexion réussie en tant que {$role}");

        return ['success' => true, 'user' => $user, 'school' => $school];
    }

    /**
     * Création de compte pour un nouvel utilisateur avec validation et champs spécifiques au rôle
     */
    public static function register(array $data): array {
        $pdo = getDbConnection();

        $schoolId = (int)($data['school_id'] ?? 0);
        $role = trim($data['role'] ?? '');
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $gender = $data['gender'] ?? 'M';
        $dateOfBirth = !empty($data['date_of_birth']) ? $data['date_of_birth'] : null;
        $address = trim($data['address'] ?? '');

        // Validation du rôle autorisé
        $allowedRoles = ['directeur', 'surveillant', 'professeur', 'secretaire', 'eleve', 'parent'];
        if (!in_array($role, $allowedRoles)) {
            return ['success' => false, 'message' => "Rôle utilisateur non valide."];
        }

        // Sécurité pour la création des rôles d'encadrement et administratifs (Directeur / Secrétaire / Surveillant)
        // Afin d'empêcher les accès non autorisés, un code d'habilitation ou d'autorisation établissement est exigé
        if (in_array($role, ['directeur', 'secretaire', 'surveillant'])) {
            $adminCode = trim($data['admin_access_code'] ?? '');
            if ($adminCode !== 'MBOOLO-ADMIN-SECURE' && $adminCode !== 'ECOLE-2026') {
                return [
                    'success' => false, 
                    'message' => "Code d'habilitation administrative incorrect. Seul le personnel autorisé peut créer un compte {$role}."
                ];
            }
        }

        if (empty($schoolId) || empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
            return ['success' => false, 'message' => "Veuillez renseigner tous les champs obligatoires."];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => "Les mots de passe ne correspondent pas."];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => "Le mot de passe doit comporter au moins 6 caractères."];
        }

        // Vérification unicité dans l'école
        $stmtCheck = $pdo->prepare("SELECT id FROM `users` WHERE `school_id` = ? AND (`username` = ? OR `email` = ?)");
        $stmtCheck->execute([$schoolId, $username, $email]);
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'message' => "Cet identifiant ou cette adresse email est déjà utilisé(e) dans cet établissement."];
        }

        // Si rôle élève : validation classe et matricule
        if ($role === 'eleve') {
            $classId = (int)($data['class_id'] ?? 0);
            $matricule = trim($data['student_matricule'] ?? '');
            
            if (empty($classId) || empty($matricule)) {
                return ['success' => false, 'message' => "Pour un élève, la classe et le matricule scolaire sont obligatoires."];
            }

            // Vérification de la classe
            $stmtC = $pdo->prepare("SELECT * FROM `classes` WHERE `id` = ? AND `school_id` = ?");
            $stmtC->execute([$classId, $schoolId]);
            $classObj = $stmtC->fetch();
            if (!$classObj) {
                return ['success' => false, 'message' => "La classe sélectionnée est invalide pour cet établissement."];
            }

            // Vérification unicité du matricule
            $stmtM = $pdo->prepare("SELECT id FROM `student_profiles` WHERE `school_id` = ? AND `student_matricule` = ?");
            $stmtM->execute([$schoolId, $matricule]);
            if ($stmtM->fetch()) {
                return ['success' => false, 'message' => "Ce matricule d'élève ({$matricule}) existe déjà dans cet établissement."];
            }
        }

        try {
            $pdo->beginTransaction();

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare("
                INSERT INTO `users` (`school_id`, `username`, `email`, `password_hash`, `role`, `first_name`, `last_name`, `phone`, `gender`, `date_of_birth`, `address`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$schoolId, $username, $email, $hash, $role, $firstName, $lastName, $phone, $gender, $dateOfBirth, $address]);
            $newUserId = (int)$pdo->lastInsertId();

            // Création du profil spécifique élève
            if ($role === 'eleve') {
                $stmtSP = $pdo->prepare("
                    INSERT INTO `student_profiles` (`user_id`, `school_id`, `class_id`, `student_matricule`, `admission_date`)
                    VALUES (?, ?, ?, ?, CURDATE())
                ");
                $stmtSP->execute([$newUserId, $schoolId, $data['class_id'], $data['student_matricule']]);
            }

            // Rapprochement automatique du parent s'il a saisi le matricule de son enfant
            if ($role === 'parent' && !empty($data['child_matricule'])) {
                $childMatricule = trim($data['child_matricule']);
                $stmtChild = $pdo->prepare("
                    SELECT sp.user_id FROM `student_profiles` sp 
                    WHERE sp.school_id = ? AND sp.student_matricule = ?
                ");
                $stmtChild->execute([$schoolId, $childMatricule]);
                $child = $stmtChild->fetch();
                if ($child) {
                    $stmtLink = $pdo->prepare("
                        INSERT INTO `parent_students` (`school_id`, `parent_id`, `student_id`, `relationship_type`)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmtLink->execute([$schoolId, $newUserId, $child['user_id'], $data['parent_relation'] ?? 'Tuteur']);
                }
            }

            $pdo->commit();

            Audit::log($schoolId, $newUserId, 'REGISTER_SUCCESS', 'users', $newUserId, "Inscription d'un nouveau compte ({$role}) : {$username}");

            return ['success' => true, 'message' => "Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter."];

        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => "Erreur lors de la création du compte : " . $e->getMessage()];
        }
    }

    public static function logout(): void {
        if (self::check()) {
            Audit::log(self::schoolId() ?? 1, self::id(), 'LOGOUT', 'users', self::id(), "Déconnexion de l'utilisateur");
        }
        unset($_SESSION['mboolo_user']);
        unset($_SESSION['mboolo_school']);
        session_destroy();
    }
}
