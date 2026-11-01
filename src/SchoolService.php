<?php
/**
 * Service de Gestion et Personnalisation des Établissements — MBOOLO
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Audit.php';
require_once __DIR__ . '/Auth.php';

class SchoolService {

    public static function getAllSchools(): array {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT * FROM `schools` WHERE `is_active` = 1 ORDER BY `name` ASC");
        return $stmt->fetchAll();
    }

    public static function getSchoolById(int $schoolId): ?array {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM `schools` WHERE `id` = ?");
        $stmt->execute([$schoolId]);
        $school = $stmt->fetch();
        return $school ?: null;
    }

    /**
     * Mise à jour des informations de l'établissement et personnalisation du logo
     */
    public static function updateSchool(int $schoolId, array $data, ?array $logoFile = null): array {
        $pdo = getDbConnection();

        $name = trim($data['name'] ?? '');
        $slogan = trim($data['slogan'] ?? '');
        $address = trim($data['address'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $website = trim($data['website'] ?? '');

        if (empty($name) || empty($address) || empty($phone) || empty($email)) {
            return ['success' => false, 'message' => "Le nom, l'adresse, le téléphone et l'e-mail de l'établissement sont obligatoires."];
        }

        // Gestion de l'upload du logo (PNG, JPG, JPEG, WEBP, SVG)
        $logoPathToUpdate = null;
        if ($logoFile && isset($logoFile['error']) && $logoFile['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($logoFile['error'] === UPLOAD_ERR_INI_SIZE || $logoFile['error'] === UPLOAD_ERR_FORM_SIZE) {
                return ['success' => false, 'message' => "Le fichier sélectionné est trop volumineux. La taille maximale autorisée est de 10 Mo."];
            }
            if ($logoFile['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => "Erreur lors du téléversement du logo (Code d'erreur PHP : " . $logoFile['error'] . ")."];
            }

            $tmpName = $logoFile['tmp_name'];
            $fileSize = $logoFile['size'];
            $ext = strtolower(pathinfo($logoFile['name'], PATHINFO_EXTENSION));

            $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
            if (!in_array($ext, $allowedExtensions)) {
                return ['success' => false, 'message' => "Format de logo non autorisé. Les formats acceptés sont : PNG, JPG, JPEG, WEBP et SVG."];
            }

            if ($fileSize > 10 * 1024 * 1024) { // Max 10MB
                return ['success' => false, 'message' => "La taille du logo ne doit pas dépasser 10 Mo."];
            }

            // Vérification MIME
            $allowedMimes = [
                'image/png', 'image/jpeg', 'image/pjpeg', 'image/x-png',
                'image/jpg', 'image/webp', 'image/svg+xml'
            ];

            $mimeType = null;
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
            } elseif (function_exists('mime_content_type')) {
                $mimeType = mime_content_type($tmpName);
            }

            if ($mimeType && !in_array(strtolower($mimeType), $allowedMimes)) {
                return ['success' => false, 'message' => "Le fichier uploadé ({$mimeType}) n'est pas un format d'image valide."];
            }

            $uploadDir = __DIR__ . '/../public/uploads/logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = 'school_' . $schoolId . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $newFileName;

            $moved = false;
            if (is_uploaded_file($tmpName)) {
                $moved = @move_uploaded_file($tmpName, $destination);
            }
            if (!$moved) {
                $moved = @copy($tmpName, $destination);
            }

            if ($moved) {
                $logoPathToUpdate = 'uploads/logos/' . $newFileName;
            } else {
                return ['success' => false, 'message' => "Échec de l'enregistrement du logo dans le dossier de destination."];
            }
        }

        try {
            if ($logoPathToUpdate !== null) {
                $stmt = $pdo->prepare("
                    UPDATE `schools`
                    SET `name` = ?, `slogan` = ?, `address` = ?, `phone` = ?, `email` = ?, `website` = ?, `logo_path` = ?
                    WHERE `id` = ?
                ");
                $stmt->execute([$name, $slogan, $address, $phone, $email, $website, $logoPathToUpdate, $schoolId]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE `schools`
                    SET `name` = ?, `slogan` = ?, `address` = ?, `phone` = ?, `email` = ?, `website` = ?
                    WHERE `id` = ?
                ");
                $stmt->execute([$name, $slogan, $address, $phone, $email, $website, $schoolId]);
            }

            // Rafraîchir la session si c'est l'école active
            if (isset($_SESSION['mboolo_school']) && $_SESSION['mboolo_school']['id'] == $schoolId) {
                $_SESSION['mboolo_school'] = self::getSchoolById($schoolId);
            }

            $actorId = Auth::id();
            if (!$actorId) {
                $stmtDir = $pdo->prepare("SELECT id FROM `users` WHERE `school_id` = ? AND `role` = 'directeur' AND `is_active` = 1 LIMIT 1");
                $stmtDir->execute([$schoolId]);
                $actorId = (int)$stmtDir->fetchColumn() ?: 0;
            }

            Audit::log($schoolId, $actorId, 'UPDATE_SCHOOL_SETTINGS', 'schools', $schoolId, "Mise à jour des coordonnées et de l'identité visuelle");

            return ['success' => true, 'message' => "Les informations et l'identité visuelle de l'établissement ont été mises à jour avec succès."];

        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur lors de la mise à jour : " . $e->getMessage()];
        }
    }
}
