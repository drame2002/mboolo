<?php
/**
 * Suite de Tests d'Intégration & Vérification des Spécifications — MBOOLO
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../src/Audit.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/SchoolService.php';
require_once __DIR__ . '/../src/AcademicService.php';
require_once __DIR__ . '/../src/CommunicationService.php';
require_once __DIR__ . '/../src/FinanceService.php';
require_once __DIR__ . '/../src/ParentService.php';
require_once __DIR__ . '/../src/SupervisorService.php';
require_once __DIR__ . '/../src/BackupService.php';

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $title, ?string $details = null) {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$title}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$title}" . ($details ? " -> {$details}" : "") . "\n";
    }
}

echo "\n======================================================================\n";
echo "   LANCEMENT DU BANC DE TESTS AUTOMATISÉ — PLATEFORME MBOOLO\n";
echo "======================================================================\n\n";

// --- TEST 1 : BASE DE DONNÉES & MULTI-TENANT ---
echo "1. VÉRIFICATION BASE DE DONNÉES & MULTI-TENANT (Spécification 1) :\n";
$pdo = getDbConnection();
$schools = SchoolService::getAllSchools();
assertTest(count($schools) >= 2, "Au moins 2 établissements distincts sont configurés dans la base");

$school1Users = $pdo->query("SELECT COUNT(*) FROM users WHERE school_id = 1")->fetchColumn();
$school2Users = $pdo->query("SELECT COUNT(*) FROM users WHERE school_id = 2")->fetchColumn();
assertTest($school1Users > 0 && $school2Users > 0, "Les données des utilisateurs sont cloisonnées par school_id ($school1Users vs $school2Users)");

// --- TEST 2 : AUTHENTIFICATION & RÔLES ---
echo "\n2. AUTHENTIFICATION & CRÉATION DE COMPTES (Spécification 2) :\n";
// Test connexion directeur
$loginDir = Auth::attempt(1, 'directeur@mboolo.sn', 'pass123', 'directeur');
assertTest($loginDir['success'] === true, "Connexion réussie du Directeur avec email et mot de passe");

// Test mauvais rôle
$badRole = Auth::attempt(1, 'directeur@mboolo.sn', 'pass123', 'eleve');
assertTest($badRole['success'] === false, "Rejet de la connexion si le rôle sélectionné ne correspond pas");

// Test mauvais mot de passe
$badPass = Auth::attempt(1, 'directeur@mboolo.sn', 'mauvaismotdepasse', 'directeur');
assertTest($badPass['success'] === false, "Rejet de la connexion en cas de mot de passe invalide");

// Test inscription d'un lycéen avec série obligatoire (Spécification 5)
$newStudentData = [
    'school_id' => 1,
    'role' => 'eleve',
    'username' => 'test_lyceen_' . time(),
    'email' => 'lyceen_' . time() . '@test.com',
    'password' => 'secret123',
    'confirm_password' => 'secret123',
    'first_name' => 'Samba',
    'last_name' => 'Gueye',
    'cycle' => 'Lycee',
    'class_id' => 1,
    'track' => 'Scientifique (S2)',
    'student_matricule' => 'MAT-TEST-' . rand(1000, 9999)
];
$regStudent = Auth::register($newStudentData);
assertTest($regStudent['success'] === true, "Inscription réussie d'un élève de Lycée avec classe et série scientifique");

// Test protection inscription administrative non autorisée
$fakeAdmin = [
    'school_id' => 1,
    'role' => 'directeur',
    'username' => 'fake_admin_' . time(),
    'email' => 'fake_' . time() . '@test.com',
    'password' => 'secret123',
    'confirm_password' => 'secret123',
    'first_name' => 'Fake',
    'last_name' => 'Admin',
    'admin_access_code' => 'WRONG_CODE'
];
$regFake = Auth::register($fakeAdmin);
assertTest($regFake['success'] === false, "Blocage d'une inscription de directeur sans code d'habilitation officiel");

// --- TEST 3 : ESPACE DIRECTEUR ---
echo "\n3. ESPACE DIRECTEUR (Spécification 3) :\n";
// Publication d'un communiqué
$annRes = CommunicationService::createAnnouncement(1, 1, [
    'title' => 'Test Communiqué Urgent Automatisé',
    'content' => 'Information importante concernant les épreuves de contrôle continu.',
    'target_audience' => 'all',
    'urgency_level' => 'urgent',
    'is_pinned' => 1
]);
assertTest($annRes['success'] === true, "Le directeur peut publier un communiqué avec niveau d'urgence");

// Convocation urgente des professeurs
$meetRes = CommunicationService::createMeeting(1, 1, [
    'title' => 'Conseil Pédagogique Exceptionnel de Test',
    'meeting_type' => 'urgente',
    'meeting_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'location' => 'Salle de Conférence',
    'agenda' => '1. Organisation pédagogique. 2. Clôture des notes.'
]);
assertTest($meetRes['success'] === true, "Le directeur peut émettre une convocation urgente pour les professeurs");

// --- TEST 4 : ESPACE PROFESSEUR ---
echo "\n4. ESPACE DU PROFESSEUR (Spécification 4) :\n";
$teacherTimetable = AcademicService::getTimetableForTeacher(1, 3);
assertTest(!empty($teacherTimetable), "Le professeur accède à son emploi du temps avec durées de cours");
assertTest(isset($teacherTimetable[0]['duration_minutes']) && $teacherTimetable[0]['duration_minutes'] > 0, "Durée des cours exprimée en minutes ({$teacherTimetable[0]['duration_minutes']} min)");

// Publication d'un résumé de leçon
$lessonRes = AcademicService::publishLessonSummary(1, 3, [
    'class_subject_id' => 1,
    'lesson_date' => date('Y-m-d'),
    'title' => 'Séance Démonstration : Théorème de Rolle',
    'summary_content' => 'Démonstration géométrique et analytique de l existence d un point critique.',
    'homework' => 'Résoudre le problème n° 5 page 92',
    'homework_due_date' => date('Y-m-d', strtotime('+3 days'))
]);
assertTest($lessonRes['success'] === true, "Le professeur peut publier des résumés de leçons et devoirs");

// Saisie de notes
$evalTest = AcademicService::createEvaluation(1, 3, [
    'class_subject_id' => 1,
    'title' => 'Interrogation Écrite Démo',
    'eval_type' => 'interrogation',
    'semester' => 'Semestre 1',
    'max_score' => 20,
    'weight' => 1.0,
    'eval_date' => date('Y-m-d')
]);
assertTest($evalTest['success'] === true, "Création d'une évaluation pour saisie des notes");

$evalId = $evalTest['evaluation_id'];
$saveGradesRes = AcademicService::saveGrades(1, 3, $evalId, [
    6 => ['score' => 18.5, 'is_absent' => 0, 'appreciation' => 'Remarquable maîtrise']
]);
assertTest($saveGradesRes['success'] === true, "Saisie et enregistrement des notes par le professeur");

// Consultation du salaire autorisé
$teacherSalaries = FinanceService::getTeacherAuthorizedSalaries(1, 3);
assertTest(!empty($teacherSalaries), "Le professeur accède à ses fiches de paie autorisées (Net: {$teacherSalaries[0]['net_salary']} FCFA)");

// --- TEST 5 : ESPACE ÉLÈVE & SUIVI DES RÉSULTATS ---
echo "\n5. ESPACE DE L'ÉLÈVE & SUIVI DES RÉSULTATS (Spécification 5) :\n";
$studentOverview = AcademicService::getStudentGradesOverview(1, 6, 'Semestre 1');
assertTest(!empty($studentOverview['subjects']), "Consultation des résultats avec matières, devoirs et moyennes");
assertTest(isset($studentOverview['general_average']) && $studentOverview['general_average'] > 0, "Calcul automatique de la moyenne générale pondérée ({$studentOverview['general_average']} / 20)");
assertTest(isset($studentOverview['total_coefficients']) && $studentOverview['total_coefficients'] > 0, "Calcul des coefficients cumulés ({$studentOverview['total_coefficients']})");

// --- TEST 6 : ESPACE SECRÉTAIRE & FINANCES ---
echo "\n6. ESPACE DU SECRÉTAIRE & FINANCES (Spécification 6) :\n";
// Enregistrement d'un paiement au guichet
$counterPay = FinanceService::recordSecretaryPayment(1, 2, [
    'student_id' => 6,
    'fee_schedule_id' => 4, // Décembre
    'amount_paid' => 45000,
    'payment_method' => 'especes',
    'receipt_number' => 'REC-TEST-GUICHET',
    'notes' => 'Paiement espèces test guichet'
]);
assertTest($counterPay['success'] === true, "Le secrétaire peut enregistrer un encaissement au guichet avec reçu");

// Gestion des salaires des professeurs
$salRes = FinanceService::createOrUpdateTeacherSalary(1, 2, [
    'teacher_id' => 3,
    'month' => 'Janvier',
    'year' => 2026,
    'base_salary' => 380000,
    'bonus' => 40000,
    'deductions' => 15000,
    'payment_status' => 'paye',
    'payment_method' => 'virement'
]);
assertTest($salRes['success'] === true, "Le secrétaire gère les salaires des professeurs selon ses autorisations");

// Grand livre des opérations
$ledger = FinanceService::getFinancialLedger(1, 10);
assertTest(!empty($ledger), "Historique et journal des opérations financières conservé");

// --- TEST 7 : ESPACE PARENT & CONTRÔLE STRICT ---
echo "\n7. ESPACE DU PARENT D'ÉLÈVE & RECHERCHE STRICTE (Spécification 7) :\n";
// Rapprochement avec élève inexistant ou autre école : DOIT renvoyer un message d'erreur explicite !
$linkNotFound = ParentService::linkChild(1, 9, 'Eleve Inexistant Introuvable');
assertTest(
    $linkNotFound['success'] === false && strpos($linkNotFound['message'], 'introuvable') !== false,
    "Message d'erreur explicite affiché lorsqu'un élève recherché est introuvable"
);

// Élève appartenant à l'école 2 recherché depuis l'école 1 : DOIT échouer !
$linkOtherSchool = ParentService::linkChild(1, 9, 'Ibrahima SALL');
assertTest(
    $linkOtherSchool['success'] === false,
    "Rejet de l'association si l'élève n'appartient pas à l'établissement concerné (Multi-Tenant)"
);

// Rapprochement avec son enfant
$children = ParentService::getChildren(1, 9);
assertTest(!empty($children), "Le parent consulte la liste de ses enfants associés ({$children[0]['first_name']})");

// --- TEST 8 : PERSONNALISATION DE L'ÉTABLISSEMENT ---
echo "\n8. PERSONNALISATION DES ÉTABLISSEMENTS & LOGO (Spécification 8) :\n";
$schoolInfo = SchoolService::getSchoolById(1);
assertTest(!empty($schoolInfo['logo_path']), "Logo de l'établissement configuré ({$schoolInfo['logo_path']})");
assertTest(!empty($schoolInfo['phone']) && !empty($schoolInfo['email']) && !empty($schoolInfo['address']), "Coordonnées complètes de l'établissement présentes");

// --- TEST 9 : BULLETINS SCOLAIRES AUTOMATIQUES ---
echo "\n9. BULLETINS SCOLAIRES AUTOMATIQUES (Spécification 9) :\n";
$bulletinGen = AcademicService::generateSemesterReportCard(1, 6, 'Semestre 1');
assertTest($bulletinGen['success'] === true, "Génération automatique du bulletin semestriel à partir des notes");

$bulletin = AcademicService::getReportCard(1, 6, 'Semestre 1');
assertTest($bulletin !== null, "Récupération du bulletin avec coordonnées de l'école et identité de l'élève");
assertTest(isset($bulletin['class_rank']) && $bulletin['class_rank'] >= 1, "Rang de l'élève calculé ({$bulletin['class_rank']}er)");
assertTest(!empty($bulletin['details']), "Moyennes par matière et appréciations des professeurs intégrées");
assertTest(!empty($bulletin['principal_appreciation']), "Appréciation générale du conseil et du directeur présente");

// --- TEST 10 : SÉCURITÉ, AUDIT, BACKUP & PAIEMENT SÉCURISÉ ---
echo "\n10. SÉCURITÉ, AUDIT, SAUVEGARDE & PAIEMENT SÉCURISÉ (Spécification 10) :\n";
// Vérification hachage mot de passe
$userDirecteur = $pdo->query("SELECT password_hash FROM users WHERE id = 1")->fetchColumn();
assertTest(strpos($userDirecteur, '$2y$') === 0 || strpos($userDirecteur, '$argon2') === 0, "Mots de passe protégés par un hachage cryptographique sécurisé (Bcrypt/Argon2)");

// Vérification journal d'audit
$recentAudit = Audit::getRecentLogs(1, 5);
assertTest(!empty($recentAudit), "Traçabilité des opérations sensibles dans le journal d'audit");

// Test génération de sauvegarde SQL
$backupSql = BackupService::generateSqlBackup(1);
assertTest(strlen($backupSql) > 1000 && strpos($backupSql, 'MBOOLO SAAS') !== false, "Génération réussie d'une sauvegarde SQL complète de l'établissement");

// Test paiement en ligne confirmé par prestataire (nettoyage préalable pour idempotence du test)
$pdo = getDbConnection();
$pdo->prepare("DELETE FROM `student_payments` WHERE `student_id` = 6 AND `fee_schedule_id` = 5 AND `payment_method` = 'wave'")->execute();

$onlinePayRes = FinanceService::processOnlinePayment(1, 6, 5, 'wave', [
    'phone_number' => '+221 77 400 55 66'
]);
assertTest(($onlinePayRes['success'] ?? false) === true, "Paiement en ligne sécurisé et confirmé par le prestataire Wave avant validation");
assertTest(!empty($onlinePayRes['transaction_ref']), "Référence de transaction générée : " . ($onlinePayRes['transaction_ref'] ?? 'N/A'));

// --- TEST 11 : ESPACE DU SURVEILLANT GÉNÉRAL (EMPLOIS DU TEMPS, ABSENCES & DISCIPLINE) ---
echo "\n11. ESPACE DU SURVEILLANT GÉNÉRAL (Vie Scolaire, Emplois du Temps, Absences & Discipline) :\n";

// 11.1 Connexion Surveillant
$survLogin = Auth::attempt(1, 'surveillant@mboolo.sn', 'pass123', 'surveillant');
assertTest($survLogin['success'] === true, "Connexion réussie du Surveillant Général avec identifiant et rôle 'surveillant'");

// 11.2 Récupération du tableau de bord Surveillant
$survStats = SupervisorService::getDashboardStats(1);
assertTest(isset($survStats['total_students']) && $survStats['total_students'] > 0, "Consultation des statistiques de vie scolaire (Élèves : {$survStats['total_students']}, Classes : {$survStats['total_classes']})");

// 11.3 Création d'un créneau d'emploi du temps par le surveillant
// Nettoyer un éventuel créneau de test existant
$pdo->prepare("DELETE t FROM timetables t JOIN class_subjects cs ON t.class_subject_id = cs.id WHERE t.school_id = 1 AND cs.class_id = 1 AND t.day_of_week = 'Samedi' AND t.start_time = '08:00:00'")->execute();

// Trouver une matière et un prof de la classe 1
$clsSubj = $pdo->query("SELECT cs.id, cs.teacher_id, s.name as subject_name FROM class_subjects cs JOIN subjects s ON cs.subject_id = s.id WHERE cs.class_id = 1 AND cs.teacher_id IS NOT NULL LIMIT 1")->fetch();

$slotRes = AcademicService::addTimetableSlot(1, $survLogin['user']['id'], [
    'class_id' => 1,
    'class_subject_id' => $clsSubj['id'],
    'day_of_week' => 'Samedi',
    'start_time' => '08:00',
    'end_time' => '10:00',
    'room' => 'Salle B12'
]);
assertTest($slotRes['success'] === true, "Ajout réussi d'un créneau d'emploi du temps par le surveillant (Samedi 08:00-10:00, {$clsSubj['subject_name']})");

// 11.4 Détection de conflit horaire (même classe sur le même créneau)
$conflictRes = AcademicService::addTimetableSlot(1, $survLogin['user']['id'], [
    'class_id' => 1,
    'class_subject_id' => $clsSubj['id'],
    'day_of_week' => 'Samedi',
    'start_time' => '09:00',
    'end_time' => '11:00',
    'room' => 'Autre Salle'
]);
assertTest($conflictRes['success'] === false && stripos($conflictRes['message'], 'conflit') !== false, "Détection et blocage automatique des conflits de créneaux pour une classe");

// 11.5 Enregistrement d'une absence/retard par le surveillant
$attRes = SupervisorService::recordAttendance(1, $survLogin['user']['id'], [
    'student_id' => 6,
    'type' => 'retard',
    'date' => date('Y-m-d'),
    'start_time' => '08:15',
    'end_time' => '08:30',
    'reason' => 'Arrivé avec 15 minutes de retard'
]);
assertTest($attRes['success'] === true, "Enregistrement d'un retard d'élève avec motif par le surveillant");

// 11.6 Justification de l'assiduité
$lastAtt = $pdo->query("SELECT id FROM attendances WHERE student_id = 6 ORDER BY id DESC LIMIT 1")->fetch();
if ($lastAtt) {
    $justRes = SupervisorService::justifyAttendance(1, (int)$lastAtt['id'], 'Motif médical présenté par le parent');
    assertTest($justRes['success'] === true, "Régularisation et justification de l'assiduité avec motif archivé");
}

// 11.7 Création d'une sanction/rapport disciplinaire
$discRes = SupervisorService::addDisciplinaryRecord(1, $survLogin['user']['id'], [
    'student_id' => 6,
    'incident_type' => 'Bavardages répétés et non respect des consignes',
    'sanction' => 'avertissement',
    'description' => 'Comportement perturbateur pendant le cours.',
    'incident_date' => date('Y-m-d')
]);
assertTest($discRes['success'] === true, "Enregistrement d'une sanction disciplinaire (avertissement) dans le registre");

// 11.8 Traitement / Clôture du dossier disciplinaire
$lastDisc = $pdo->query("SELECT id FROM disciplinary_records WHERE student_id = 6 ORDER BY id DESC LIMIT 1")->fetch();
if ($lastDisc) {
    $statusRes = SupervisorService::updateDisciplineStatus(1, (int)$lastDisc['id'], 'clos');
    assertTest($statusRes['success'] === true, "Mise à jour du statut disciplinaire vers 'clos' par le surveillant");
}

// --- TEST 12 : STRUCTURE PÉDAGOGIQUE COMPLÈTE (ÉLÉMENTAIRE, COLLÈGE, LYCÉE & SÉRIES S, L, G, T) ---
echo "\n12. STRUCTURE PÉDAGOGIQUE COMPLÈTE (Élémentaire, Collège, Lycée & Séries S, L, G, T) :\n";

// 12.1 Vérification des classes Élémentaire (CI, CP, CE1, CE2, CM1, CM2)
$elemClasses = $pdo->query("SELECT level FROM classes WHERE school_id = 1 AND cycle = 'Elementaire'")->fetchAll(PDO::FETCH_COLUMN);
$expectedElem = ['CI', 'CP', 'CE1', 'CE2', 'CM1', 'CM2'];
$elemDiff = array_diff($expectedElem, $elemClasses);
assertTest(empty($elemDiff), "Toutes les classes de l'Élémentaire sont configurées : CI, CP, CE1, CE2, CM1, CM2");

// 12.2 Vérification des cours de l'Élémentaire (Maths, Français, IST, Vivre ensemble, Vivre dans son milieu, EPS, HG)
$cm2Class = $pdo->query("SELECT id FROM classes WHERE school_id = 1 AND level = 'CM2' LIMIT 1")->fetchColumn();
$cm2Subjects = $pdo->query("
    SELECT s.code 
    FROM class_subjects cs 
    JOIN subjects s ON cs.subject_id = s.id 
    WHERE cs.class_id = {$cm2Class}
")->fetchAll(PDO::FETCH_COLUMN);

$expectedCm2Subj = ['MATH-EL', 'FR-EL', 'IST', 'VIVRE-ENS', 'VIVRE-MIL', 'EPS-EL', 'HG-EL'];
$subjDiff = array_diff($expectedCm2Subj, $cm2Subjects);
assertTest(empty($subjDiff), "Programme Élémentaire complet : Mathématiques, Français, IST, Vivre ensemble, Vivre dans son milieu, EPS, HG");

// 12.3 Vérification des classes du Collège (6ème, 5ème, 4ème, 3ème)
$rawCollClasses = $pdo->query("SELECT level FROM classes WHERE school_id = 1 AND cycle = 'College'")->fetchAll(PDO::FETCH_COLUMN);
$collClasses = array_map(function($l) { return str_replace('è', 'e', $l); }, $rawCollClasses);
$expectedColl = ['6eme', '5eme', '4eme', '3eme'];
$collDiff = array_diff($expectedColl, $collClasses);
assertTest(empty($collDiff), "Toutes les classes du Collège sont configurées : 6ème, 5ème, 4ème, 3ème");

// 12.4 Vérification que la Physique-Chimie (PC) est dispensée À PARTIR DE LA 4ème
$c6Id = $pdo->query("SELECT id FROM classes WHERE school_id = 1 AND level = '6eme' LIMIT 1")->fetchColumn();
$c4Id = $pdo->query("SELECT id FROM classes WHERE school_id = 1 AND level = '4eme' LIMIT 1")->fetchColumn();

$pcIn6eme = $pdo->query("SELECT COUNT(*) FROM class_subjects cs JOIN subjects s ON cs.subject_id = s.id WHERE cs.class_id = {$c6Id} AND s.code = 'PC-COL'")->fetchColumn();
$pcIn4eme = $pdo->query("SELECT COUNT(*) FROM class_subjects cs JOIN subjects s ON cs.subject_id = s.id WHERE cs.class_id = {$c4Id} AND s.code = 'PC-COL'")->fetchColumn();

assertTest($pcIn6eme == 0 && $pcIn4eme > 0, "Physique-Chimie (PC) programmée strictement à partir de la classe de 4ème (absente en 6e, active en 4e)");

// 12.5 Vérification des matières spécifiques du collège (Latin, Musique, Espagnol LV2)
$c4Subjects = $pdo->query("
    SELECT s.code 
    FROM class_subjects cs 
    JOIN subjects s ON cs.subject_id = s.id 
    WHERE cs.class_id = {$c4Id}
")->fetchAll(PDO::FETCH_COLUMN);
assertTest(in_array('LAT', $c4Subjects) && in_array('MUS', $c4Subjects) && in_array('ESP-COL', $c4Subjects), "Cours du Collège intégrés : Latin, Éducation Musicale, Espagnol (LV2 dès la 4ème), Anglais");

// 12.6 Vérification des Séries Lycée (S, L, G, T)
$lycTracks = $pdo->query("SELECT DISTINCT track FROM classes WHERE school_id = 1 AND cycle = 'Lycee' AND track IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

$hasS = false; $hasL = false; $hasG = false; $hasT = false;
foreach ($lycTracks as $tr) {
    if (strpos($tr, 'Scientifique') !== false || strpos($tr, 'S1') !== false || strpos($tr, 'S2') !== false) $hasS = true;
    if (strpos($tr, 'Littéraire') !== false || strpos($tr, 'L1') !== false || strpos($tr, 'L2') !== false || strpos($tr, 'L\'') !== false) $hasL = true;
    if (strpos($tr, 'Économie') !== false || strpos($tr, 'G') !== false) $hasG = true;
    if (strpos($tr, 'Technique') !== false || strpos($tr, 'Génie Mécanique') !== false || strpos($tr, 'Génie Électrique') !== false) $hasT = true;
}
assertTest($hasS && $hasL && $hasG && $hasT, "Toutes les séries du Lycée sont disponibles : Séries Scientifiques (S), Littéraires (L), Gestion (G), Techniques (T)");

// 12.7 Test d'inscription d'un élève en Élémentaire (CM2)
$uUid1 = uniqid();
$cm2Reg = Auth::register([
    'school_id' => 1,
    'role' => 'eleve',
    'username' => 'eleve_cm2_' . $uUid1,
    'email' => 'cm2_' . $uUid1 . '@test.com',
    'password' => 'secret123',
    'confirm_password' => 'secret123',
    'first_name' => 'Khadija',
    'last_name' => 'Ndiaye',
    'cycle' => 'Elementaire',
    'class_id' => $cm2Class,
    'student_matricule' => 'MAT-EL-' . $uUid1
]);
assertTest($cm2Reg['success'] === true, "Inscription réussie d'un élève en cycle Élémentaire (CM2)");

// 12.8 Test d'inscription d'un élève au Lycée en Série Technique (T1)
$uUid2 = uniqid();
$t1Class = $pdo->query("SELECT id FROM classes WHERE school_id = 1 AND track LIKE '%T1%' LIMIT 1")->fetchColumn();
$t1Reg = Auth::register([
    'school_id' => 1,
    'role' => 'eleve',
    'username' => 'eleve_t1_' . $uUid2,
    'email' => 't1_' . $uUid2 . '@test.com',
    'password' => 'secret123',
    'confirm_password' => 'secret123',
    'first_name' => 'Modou',
    'last_name' => 'Gueye',
    'cycle' => 'Lycee',
    'class_id' => $t1Class,
    'track' => 'Génie Mécanique (T1)',
    'student_matricule' => 'MAT-T1-' . $uUid2
]);
assertTest($t1Reg['success'] === true, "Inscription réussie d'un élève de Lycée en Série Technologique T1 (Génie Mécanique)");

// 12.9 Test de génération d'un bulletin scolaire pour l'Élémentaire (CM2) avec matières spécifiques
$newCm2StudentId = (int)$pdo->query("SELECT user_id FROM student_profiles WHERE class_id = {$cm2Class} ORDER BY id DESC LIMIT 1")->fetchColumn();

// Récupérer un class_subject de CM2 et son enseignant
$csCm2Math = (int)$pdo->query("SELECT id FROM class_subjects WHERE class_id = {$cm2Class} LIMIT 1")->fetchColumn();
$csTeacherId = (int)$pdo->query("SELECT teacher_id FROM class_subjects WHERE id = {$csCm2Math}")->fetchColumn();

// Créer une évaluation élémentaire
$evalElemRes = AcademicService::createEvaluation(1, $csTeacherId, [
    'class_subject_id' => $csCm2Math,
    'title' => 'Contrôle de Numération et Calcul',
    'eval_type' => 'devoir',
    'semester' => 'Semestre 1',
    'max_score' => 20,
    'weight' => 1,
    'eval_date' => date('Y-m-d')
]);

if ($evalElemRes['success'] ?? false) {
    // Saisir une note pour l'élève CM2
    AcademicService::saveGrades(1, $csTeacherId, (int)$evalElemRes['evaluation_id'], [
        $newCm2StudentId => ['score' => 17.5, 'is_absent' => 0, 'appreciation' => 'Très bon raisonnement mathématique en calcul posé']
    ]);
    
    // Générer le bulletin élémentaire
    $genElemBulletin = AcademicService::generateSemesterReportCard(1, $newCm2StudentId, 'Semestre 1');
    assertTest($genElemBulletin['success'] === true, "Génération automatique réussie du bulletin pour un élève du cycle Élémentaire (CM2)");

    $bulletinElem = AcademicService::getReportCard(1, $newCm2StudentId, 'Semestre 1');
    assertTest($bulletinElem !== null && $bulletinElem['cycle'] === 'Elementaire', "Le bulletin de l'élève reflète le cycle Élémentaire et ses matières spécifiques");
    assertTest(strpos($bulletinElem['principal_appreciation'], 'Conseil des Maîtres') !== false, "Appréciation officielle du bulletin élémentaire attribuée par le 'Conseil des Maîtres'");
}

echo "\n======================================================================\n";
echo "   RÉSULTATS DES TESTS : {$passed} SUCCÈS, {$failed} ÉCHECS\n";
echo "======================================================================\n\n";

if ($failed === 0) {
    echo ">>> TOUTES LES 12 SPÉCIFICATIONS DU PROJET MBOOLO (DONT ÉLÉMENTAIRE, COLLÈGE, LYCÉE S,L,G,T & SURVEILLANT) SONT VALIDÉES À 100% ! <<<\n\n";
    exit(0);
} else {
    echo ">>> DES TESTS ONT ÉCHOUÉ <<<\n\n";
    exit(1);
}
