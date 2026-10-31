<?php
/**
 * Service pour l'Espace Parents d'Élèves — MBOOLO
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Audit.php';

class ParentService {

    /**
     * Association sécurisée d'un enfant au compte parent
     * Règle stricte : vérifie l'appartenance à l'établissement concerné et affiche une erreur explicite si introuvable
     */
    public static function linkChild(int $schoolId, int $parentId, string $searchQuery, string $relation = 'Tuteur'): array {
        $pdo = getDbConnection();
        $query = trim($searchQuery);

        if (empty($query)) {
            return [
                'success' => false,
                'message' => "Veuillez renseigner le nom, le prénom ou le numéro de matricule de l'élève à associer."
            ];
        }

        // Recherche stricte dans l'établissement concerné
        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.school_id, sp.student_matricule, c.name as class_name
            FROM `users` u
            JOIN `student_profiles` sp ON u.id = sp.user_id
            JOIN `classes` c ON sp.class_id = c.id
            WHERE u.school_id = ?
              AND u.role = 'eleve'
              AND u.is_active = 1
              AND (
                  sp.student_matricule = ?
                  OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?
                  OR CONCAT(u.last_name, ' ', u.first_name) LIKE ?
                  OR u.last_name LIKE ?
                  OR u.first_name LIKE ?
              )
            LIMIT 1
        ");
        $likeQuery = "%{$query}%";
        $stmt->execute([$schoolId, $query, $likeQuery, $likeQuery, $likeQuery, $likeQuery]);
        $student = $stmt->fetch();

        // Si l'élève est introuvable ou n'appartient pas à l'école courante
        if (!$student) {
            return [
                'success' => false,
                'message' => "Élève introuvable : Aucun élève correspondant à \"{$query}\" n'a été trouvé au sein de cet établissement scolaire. Veuillez vérifier l'exactitude du nom ou saisir son numéro de matricule officiel."
            ];
        }

        $studentId = (int)$student['id'];

        // Vérifier si déjà associé
        $stmtCheck = $pdo->prepare("SELECT id FROM `parent_students` WHERE `parent_id` = ? AND `student_id` = ?");
        $stmtCheck->execute([$parentId, $studentId]);
        if ($stmtCheck->fetch()) {
            return [
                'success' => false,
                'message' => "L'élève {$student['first_name']} {$student['last_name']} (Matricule: {$student['student_matricule']}) est déjà rattaché(e) à votre compte parent."
            ];
        }

        try {
            $stmtInsert = $pdo->prepare("
                INSERT INTO `parent_students` (`school_id`, `parent_id`, `student_id`, `relationship_type`, `linked_at`)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmtInsert->execute([$schoolId, $parentId, $studentId, $relation]);

            Audit::log($schoolId, $parentId, 'ENFANT_ASSOCIE', 'parent_students', $studentId, "Association de l'élève {$student['first_name']} {$student['last_name']} au parent #{$parentId}");

            return [
                'success' => true,
                'student' => $student,
                'message' => "L'élève {$student['first_name']} {$student['last_name']} (Classe: {$student['class_name']}) a été associé(e) avec succès à votre compte !"
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur technique : " . $e->getMessage()];
        }
    }

    public static function getChildren(int $schoolId, int $parentId): array {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, sp.student_matricule, sp.class_id, c.name as class_name, c.level, c.cycle, c.track,
                   ps.relationship_type, ps.linked_at
            FROM `parent_students` ps
            JOIN `users` u ON ps.student_id = u.id
            JOIN `student_profiles` sp ON u.id = sp.user_id
            JOIN `classes` c ON sp.class_id = c.id
            WHERE ps.parent_id = ? AND ps.school_id = ?
            ORDER BY u.first_name ASC
        ");
        $stmt->execute([$parentId, $schoolId]);
        return $stmt->fetchAll();
    }
}
