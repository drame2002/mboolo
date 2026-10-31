# MBOOLO — Plateforme SaaS de Gestion Scolaire & Vie Scolaire Multi-Établissements

**MBOOLO** est une plateforme SaaS complète, robuste et sécurisée conçue pour interconnecter l'ensemble de la communauté éducative : **Direction**, **Surveillance Générale**, **Professeurs**, **Secrétariat / Intendance**, **Élèves** et **Parents d'élèves**, tout en centralisant la gestion pédagogique, administrative, financière et disciplinaire.

---

## 🌟 Architecture & Fonctionnalités Clés

### 1. Architecture Multi-Tenant Stricte (SaaS)
- Capable d'accueillir plusieurs établissements scolaires indépendants sur la même infrastructure.
- Cloisonnement étanche de l'ensemble des données (utilisateurs, classes, matières, notes, paiements, salaires, communiqués) garanti par `school_id`.

### 2. Contrôle d'Accès Sécurisé (RBAC - 6 Rôles Dédiés)
- **Directeur** : Pilotage stratégique, convocations, supervision des bulletins, audit et sauvegarde BDD.
- **Surveillant Général** : Gestion des emplois du temps avec détection des conflits de créneaux, registre d'assiduité (retards et absences justifiées), registre disciplinaire (sanctions et rapports) et effectifs avec trombinoscope.
- **Professeur** : Emploi du temps personnalisé (durée en minutes), cahier de textes numérique, saisie des évaluations et notes, consultation confidentielle de sa rémunération.
- **Secrétaire / Intendant** : Barème des mensualités, encaissement au guichet avec reçu officiel numéroté, suivi des impayés, traitement des fiches de paie et grand livre comptable.
- **Parent d'Élève** : Rapprochement familial sécurisé par matricule, relevé des notes en direct, téléchargement des bulletins officiels et télépaiement des mensualités (Wave, Orange Money, Carte Bancaire).
- **Élève** : Consultation des moyennes générales pondérées, relevé des évaluations par matière avec jauges statistiques, emploi du temps hebdomadaire et téléchargement des bulletins.

### 3. Structure Pédagogique Intégrale (3 Cycles & Séries Complètes)
- **🎒 Cycle Élémentaire (Primaire)** : CI, CP, CE1, CE2, CM1, CM2 (Français, Mathématiques, IST, Vivre ensemble, Vivre dans son milieu, EPS, HG, Arts). Appréciation officielle émise par le **« Conseil des Maîtres »** et signature de l'Enseignant Titulaire.
- **🏫 Cycle Collège (Moyen)** : 6ème, 5ème, 4ème, 3ème. Programmation stricte de la **Physique-Chimie (PC)** à partir de la classe de 4ème, Latin, Éducation Musicale, Espagnol LV2 et Anglais.
- **🎓 Cycle Lycée (Secondaire)** : Seconde, Première, Terminale avec l'ensemble des séries officielles :
  - **Séries Scientifiques (S)** : S1 (*Maths-Physique*), S2 (*Sciences Expérimentales*), S3 (*Sciences de la Terre/Bio*).
  - **Séries Littéraires (L)** : L1 (*Langues & Littérature*), L2 (*Sciences Humaines & Sociales*), L' (*Arabe/Lettres Classiques*).
  - **Série Gestion & Tertiaire (G)** : STEG (*Sciences & Technologies de l'Économie et de la Gestion*).
  - **Séries Technologiques (T)** : T1 (*Génie Mécanique*), T2 (*Génie Électrotechnique/Électronique*).

### 4. Bulletins Scolaires Automatisés & Normes Officielles
- Calcul dynamique des moyennes pondérées par coefficient, rangs de classe et mentions.
- Mise en page conforme aux spécifications nationales pour **impression A4 PDF** avec logo officiel, coordonnées de l'école et cachet d'homologation.

### 5. Journal d'Audit & Traçabilité Granulaire
- Suivi chronologique de toutes les actions sensibles classées en 5 parties thématiques :
  1. 🔐 **Sécurité & Accès** (Connexions, échecs de mot de passe, déconnexions, inscriptions).
  2. 💳 **Finances & Trésorerie** (Paiements Wave/OM/CB, encaissements comptoir, salaires).
  3. 🎓 **Pédagogie & Évaluations** (Création de devoirs, saisie des notes, résumés de cours, bulletins).
  4. ⏱️ **Vie Scolaire & Discipline** (Emplois du temps, absences, retards, sanctions).
  5. 🏛️ **Administration & Système** (Communiqués, convocations, paramètres et logo de l'école).

### 6. Thème Sombre Pur (100% OLED Black) & Ergonomie
- Thème sombre à fort contraste (noir pur #000000 / #0a0a0a) sans éblouissement.
- Barre latérale rétractable responsive avec navigation optimisée sur mobile, tablette et desktop.

---

## 🛠️ Stack Technique

- **Langage Backend** : PHP 8.4+ (Architecture MVC légère, PDO, Typage strict).
- **Base de Données** : MySQL 8.0+ (Relations strictes, contraintes d'intégrité, indexation).
- **Frontend** : HTML5 sémantique, CSS3 moderne (Variables CSS, Flexbox, CSS Grid), JavaScript ES6.
- **Sécurité** : Bcrypt / Argon2, jetons anti-CSRF, requêtes préparées systématiques, filtrage XSS.
- **Paiements** : Intégration de passerelle de télépaiement (Wave, Orange Money, Carte Bancaire).
- **Tests Automatisés** : Banc de tests natif couvrant 100% des 12 spécifications fonctionnelles (55 tests unitaires et d'intégration).

---

## 🚀 Installation & Démarrage Rapide

### 1. Prérequis
- PHP 8.2 ou supérieur avec extensions `pdo_mysql`, `mbstring`, `fileinfo`.
- Serveur MySQL ou MariaDB.

### 2. Configuration de la base de données
1. Créez une base de données MySQL :
   ```sql
   CREATE DATABASE mboolo_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Importez le schéma de base :
   ```bash
   mysql -u root -p mboolo_db < database/schema.sql
   ```
3. Exécutez le script d'initialisation pédagogique :
   ```bash
   php database/seed_db.php
   ```

### 3. Lancer le serveur de développement
```bash
php -c php.ini -S 127.0.0.1:8000 -t public
```
Rendez-vous sur `http://127.0.0.1:8000`.

---

## 🧪 Exécution des Tests Automatisés

Le projet intègre un banc d'essais complet validant la conformité fonctionnelle et la sécurité multi-tenant :
```bash
php -c php.ini tests/run_tests.php
```
> **Résultat : 55 tests exécutés, 55 succès (100% de réussite).**

---

## 🔑 Comptes de Démonstration (Mot de passe universel : `password123`)

| Rôle | Identifiant / Email | Fonctionnalités clés |
| :--- | :--- | :--- |
| **Directeur** | `directeur@mboolo.sn` | Communiqués, convocations, bulletins, audit, logo |
| **Surveillant Général** | `surveillant@mboolo.sn` | Emplois du temps, assiduité, discipline, effectifs |
| **Professeur** | `prof.diop@mboolo.sn` | Notes, cahier de texte, emploi du temps, salaire |
| **Secrétaire** | `secretaire@mboolo.sn` | Encaissement guichet, impayés, paie des profs |
| **Parent d'Élève** | `parent.diouf@mboolo.sn` | Suivi multi-enfants, notes, paiement en ligne Wave |
| **Élève (Lycée)** | `eleve.lycee@mboolo.sn` | Notes, moyennes générales, emploi du temps, bulletins |
| **Élève (Élémentaire)** | `eleve.elem@mboolo.sn` | Notes élémentaires, bulletin Conseil des Maîtres |

---

## 📄 Licence
Ce projet est développé sous licence propriétaire pour le compte de la plateforme **MBOOLO**.
Tous droits réservés © 2025 - 2026.
