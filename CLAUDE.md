# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**College Management System for "COLLEGE POLYVALENT BILINGUE DE DOUALA"**

A comprehensive school management platform with three main components:
- **back/** - Laravel 12 backend API (PHP 8.2)
- **front/** - React 18 web application (port 3006)
- **mobile/** - Flutter mobile app for teachers
- **docs/** - Generated PDF reports and documents storage

## Development Commands

### Backend (Laravel - back/)

```bash
cd back

# Development
php artisan serve              # Start Laravel dev server (port 8000)
composer dev                   # Start all services (server, queue, logs, vite)
composer test                  # Run PHPUnit tests

# Database
php artisan migrate            # Run migrations
php artisan tinker             # Interactive REPL for database queries

# Queue & Jobs
php artisan queue:listen       # Process queued jobs (WhatsApp, notifications)

# User Management
php artisan create:user        # Create new user interactively
php artisan create:parent-accounts  # Generate parent accounts from students

# Assets
npm run dev                    # Compile frontend assets with Vite
npm run build                  # Build for production
```

### Frontend (React - front/)

```bash
cd front

# Development
npm start                      # Start React dev server on port 3006
npm run build                  # Build for production
npm test                       # Run tests
```

### Mobile (Flutter - mobile/)

```bash
cd mobile

# Development
flutter pub get                # Install dependencies
flutter run                    # Run on connected device/emulator

# Build
flutter build apk              # Build Android APK
flutter build ios              # Build iOS app (requires Mac)
```

## Architecture Overview

### Backend Architecture (Laravel)

**Framework**: Laravel 12 with PHP 8.2
**Authentication**: JWT tokens (tymon/jwt-auth) with role-based access control
**Database**: MySQL (c0admin database)
**Key Services**:
- Queue system for background jobs (WhatsApp notifications, email)
- PDF generation (DomPDF, barryvdh/laravel-dompdf)
- Excel import/export (Maatwebsite/Laravel-Excel)
- QR code generation for ID cards (simplesoftwareio/simple-qrcode)

**Key Packages**:
- `tymon/jwt-auth` - JWT authentication
- `barryvdh/laravel-dompdf` - PDF generation
- `maatwebsite/excel` - Excel import/export
- `simplesoftwareio/simple-qrcode` - QR code generation

**Roles**:
- `admin` - Full access (academic, student management)
- `principal` - Principal dashboard access
- `accountant` - Financial operations only
- `teacher` - Grade entry, assignments viewing
- `supervisor` - Attendance tracking
- `parent` - Student progress viewing

### Database Schema - Two-Level Teacher System

**CRITICAL**: The system uses TWO separate tables for teachers:

1. **`users` table** - Authentication & system access
   - Columns: `id`, `username`, `password`, `email`, `role`, `contact`
   - Used for: Login, permissions, role checks

2. **`teachers` table** - Pedagogical data
   - Columns: `id`, `user_id` (FK to users.id), `first_name`, `last_name`, `phone_number`, `email`
   - Used for: Subject assignments, evaluations, grades, bulletins

**Relationship**: `teachers.user_id` → `users.id`

**IMPORTANT**:
- `teacher_assignments.teacher_id` → `teachers.id` (NOT `users.id`)
- `evaluations.teacher_id` → `teachers.id` (NOT `users.id`)
- Always join through the `teachers` table when working with pedagogical data

**Core Tables**:
- `students` - Student records with class assignments, fee tracking
- `teachers` / `users` - Teacher management (two-level system)
- `school_classes`, `class_series`, `sections`, `levels` - Academic structure
- `subjects`, `class_series_subjects` - Curriculum
- `teacher_assignments` - Teacher-to-subject-to-class assignments
- `evaluations`, `grades` - Academic assessments
- `sequences`, `trimesters`, `academic_periods` - Time periods
- `payments`, `payment_details`, `payment_tranches` - Financial tracking
- `attendance`, `teacher_attendance`, `staff_attendance` - Presence tracking
- `payroll_periods`, `employee_payroll`, `payslips` - Staff payroll

### Frontend Architecture (React)

**Framework**: React 18 with functional components and hooks
**Routing**: React Router v6
**UI Libraries**:
- Bootstrap 5
- Reactstrap
- React Bootstrap Icons
- Chart.js for data visualization

**State Management**: Local state with Context API
**API Integration**: Axios with JWT token interceptors
**Internationalization**: French (primary) & English via `local/lang.js`

**Key Features**:
- Multi-role dashboards (admin, accountant, principal, teacher, parent)
- Real-time student and financial tracking
- PDF bulletin generation and preview
- QR code scanning for attendance
- Drag-and-drop subject group ordering (@dnd-kit)
- Excel import/export functionality

### Mobile App (Flutter)

**Framework**: Flutter 3.9+ (Dart)
**Purpose**: Teacher mobile application for CPB Douala
**Key Features**:
- Biometric authentication (local_auth)
- QR code scanning for student attendance (mobile_scanner)
- Secure credential storage (flutter_secure_storage)
- Network requests (dio, http)
- State management (provider)

## Key Features by Module

### Academic Management
- Student enrollment, transfers, class assignments
- Sequence/Trimester/Annual exam tracking
- APC (Approche Par Compétences) evaluation system
- Bulletin generation (multiple formats: Francophone, Anglophone, Technical)
- Subject group customization with drag-and-drop ordering
- Teacher assignment to classes and subjects
- Main teacher (titulaire) designation per class

### Financial System
- Fee payment tracking with tranches
- Scholarship/discount management (class-level and individual)
- Payment detail redistribution across fee types
- Receipt generation with QR codes
- Financial reporting (daily, monthly, recovery reports)
- Exportable reports (PDF, Excel, CSV)

### Attendance & Staff
- Student attendance tracking (mobile + web)
- Teacher/Staff attendance with QR code scanning
- Geolocation zones for attendance validation
- Department management (Enseignant, Entretien, etc.)
- Payroll management with salary cuts and bonuses
- WhatsApp notifications for payslips

### Document Management
- Hierarchical folder system with permissions
- Document upload and access control
- Role-based document visibility
- Inventory management with tags and movements

### Reports & Exports
- Student age tables by class
- Payment recovery reports
- Mark sheets (devoir sheets)
- Class lists with photos
- Bulletin exports (PDF per student or bulk)
- Excel/CSV exports for all entities
- **Student ID Cards** - Generate school identity cards with:
  - Student photo (3x4 cm), school logo, QR code
  - Student info: matricule, class, date of birth, parent contact
  - Cameroon flag and national motto at top
  - Standard credit card format (85.6 mm × 54 mm)
  - Individual or batch generation (10 cards per A4 page)
  - QR code contains student verification data

## API Structure

**Base URL**: `http://localhost:8000/api`
**Authentication**: JWT Bearer token in `Authorization` header

**Key Route Groups** (from `back/routes/api.php`):
- `/auth/*` - Authentication (login, logout, refresh, profile)
- `/admin/*` - Admin dashboard and stats
- `/principal/*` - Principal dashboard
- `/students/*` - Student CRUD, import/export, transfers
- `/teachers/*` - Teacher management, assignments
- `/classes/*` - Class/section/series/level management
- `/subjects/*` - Subject and curriculum management
- `/payments/*` - Payment processing, receipts, reports
- `/manual-discounts/*` - Individual student manual discounts (NEW)
- `/evaluations/*` - Evaluation and grade management
- `/sequences/*`, `/trimesters/*` - Academic period management
- `/bulletins/*` - Bulletin generation and preview
- `/attendance/*` - Student/teacher/staff attendance
- `/payroll/*` - Employee payroll management
- `/documents/*` - Document management system
- `/parents/*` - Parent portal access

## Important File Locations

### Configuration
- `back/.env` - Environment variables (DB credentials, JWT secret, API keys)
- `back/config/jwt.php` - JWT authentication settings
- `front/src/services/api.js` - Axios API configuration with interceptors

### Templates
- `back/resources/views/` - Blade templates for PDF generation (bulletins, receipts)

### Generated Files
- `back/docs/` - PDF receipts, bulletins, reports
- `back/storage/app/public/` - Uploaded files (photos, documents)

### Documentation
- `back/GUIDE_ANALYSE_BASE_DONNEES.md` - Database structure guide (two-level teacher system)
- `back/GUIDE_GROUPES_MATIERES.md` - Subject group customization guide
- `back/GROUPES_MATIERES_README.md` - Subject group technical documentation
- `back/QUEUE_WHATSAPP_README.md` - WhatsApp queue system documentation
- `back/DEPLOIEMENT_PRODUCTION.md` - Production deployment guide
- `DESIGN_CARTE_IDENTITE.md` - Student ID card design specifications
- `GUIDE_ENSEIGNANT_SAISIE_NOTES.md` - Teacher guide for grade entry
- `OPTIMISATIONS_PERFORMANCE_BULLETINS.md` - Bulletin generation performance optimization guide
- `SOLUTION_BATCH_GENERATION.md` - Batch bulletin generation solution
- `CORRECTION_BOURSES_MULTIPLES.md` - Fix for multiple scholarships bug
- `others/CLAUDE.md` - Legacy project documentation (contains old Node.js backend info)

## Critical Business Logic

### Bulletin Generation System - Academic Structure

The system uses a sophisticated academic calendar based on the French educational system with two evaluation modes depending on the cycle.

#### 📅 Academic Calendar Structure

**Année Scolaire** (School Year) contains 3 **Trimestres** (Trimesters):
- **Trimestre 1** (September - December)
- **Trimestre 2** (January - March)
- **Trimestre 3** (April - June)

Each trimester contains **Séquences** (Sequences) and one **Composition** (exam):

**PREMIER CYCLE** (6ème - 3ème):
- Trimestre 1: Séquence 1 + Séquence 2 + Composition 1
- Trimestre 2: Séquence 3 + Séquence 4 + Composition 2
- Trimestre 3: Composition 3 uniquement

**DEUXIÈME CYCLE** (2nde - Tle):
- Trimestre 1: Séquence 1 + Séquence 2 + Composition 1
- Trimestre 2: Séquence 3 + Séquence 4 + Composition 2
- Trimestre 3: Séquence 5 + Séquence 6 + Composition 3

#### 📊 Grade Calculation Formulas

**Tables Structure:**
- `sequences` - Contains both regular sequences and compositions (`is_composition` flag)
- `trimesters` - Three trimesters per school year
- `evaluations` - Teacher-created assessments within sequences
- `grades` - Individual student scores with fields:
  - `student_id`, `evaluation_id`, `sequence_id`, `trimester_id`
  - `class_series_subject_id` (the subject)
  - `score`, `max_score`, `coefficient`
  - `is_absent` (boolean for absent students)

**PREMIER CYCLE Calculation:**

1. **DS (Devoir Surveillé) Average per Trimester:**
   - **Trimestre 1**: DS1 = (Séquence 1 + Séquence 2) / 2
   - **Trimestre 2**: DS2 = (Séquence 3 + Séquence 4) / 2
   - **Trimestre 3**: Pas de DS (composition uniquement)

2. **Trimester Average per Subject (M/20):**
   - **Trimestre 1**: M/20 = (DS1 + Composition 1) / 2
   - **Trimestre 2**: M/20 = (DS2 + Composition 2) / 2
   - **Trimestre 3**: M/20 = Composition 3 (composition seule, pas de DS)

3. **General Average:**
   - Moyenne Générale = Σ(M/20 × Coefficient) / Σ(Coefficients)

4. **Missing Data Handling:**
   - If NO data at all for a subject → coefficient cancelled (subject ignored)
   - If SOME data exists → missing evaluations count as 0.00 (penalty)
   - Examples:
     - Trimestre 1: Si Seq1=15, Seq2 non saisie → DS1 = (15 + 0) / 2 = 7.5
     - Trimestre 2: Si Seq3=12, Seq4 non saisie → DS2 = (12 + 0) / 2 = 6.0
     - Si DS1=10, Comp1 non saisie → M/20 = (10 + 0) / 2 = 5.0

**DEUXIÈME CYCLE Calculation:**

1. **Trimester Average per Subject:**
   - M/20 = (Séquence 1 + Séquence 2 + Composition) / 3
   - All three evaluations have equal weight

2. **General Average:**
   - Same formula as Premier Cycle: Σ(M/20 × Coefficient) / Σ(Coefficients)

3. **Missing Data Handling:**
   - If ALL three evaluations missing → coefficient cancelled
   - If AT LEAST ONE evaluation exists → missing ones = 0.00

#### 📝 Bulletin Types Generated

The system supports multiple bulletin formats:
- **Bulletin de Séquence** - For individual sequences (Seq 1 or 3 only in Premier Cycle, all sequences in Deuxième Cycle)
- **Bulletin de Trimestre** - End of trimester report with DS + Composition calculations
- **APC Francophone** (Premier Cycle: 6ème-3ème) - Competency-based evaluation format
- **Deuxième Cycle** (2nd-Tle) - Traditional grading with all sequences
- **Anglophone Section** - Adapted for English-speaking curriculum
- **Technical Section** - For technical/vocational tracks

#### 🔄 Bulletin Generation Workflow

1. **Availability Check** (`availableBulletins()` in BulletinController.php:28)
   - Determines cycle type (premier/deuxième) from student's class
   - Premier Cycle: Only sequences 1 and 3 generate sequence bulletins
   - Deuxième Cycle: All sequences (1, 2, 3, 4) generate sequence bulletins
   - Trimester bulletins available when at least one sequence is completed

2. **Data Generation** (`generateSequenceBulletinData()` / `generateTrimesterBulletinData()`)
   - Fetches all grades for student across subjects
   - Calculates subject averages using DS/Composition formulas
   - Computes group averages (customizable subject groupings)
   - Determines class rank based on general average
   - Retrieves class statistics (min, max, average, pass rate)

3. **Template Rendering** (`renderBulletinTemplate()`)
   - Uses Blade templates with conditional logic per cycle type
   - Displays individual sequence grades or DS averages as appropriate
   - Shows ABS (absent) or empty cell for missing data
   - Applies appreciation scales (Excellent, Bien, Assez Bien, Passable, Insuffisant)

4. **PDF Generation** (`generatePDF()`)
   - Converts HTML to PDF using DomPDF
   - Adds school logo, watermarks, and digital signatures
   - Stores in `storage/app/bulletins/` directory
   - Creates database record in `bulletin_generations` table

5. **Batch Generation** (`batchGenerate()`)
   - Optimized for generating bulletins for entire classes (10 students per batch)
   - Uses eager loading to prevent N+1 queries
   - Supports force regeneration with `force=true` parameter
   - Process time: ~1-2 seconds per student

#### 🎯 Completion Percentage Logic

The system tracks bulletin readiness with real-time completion percentages:

**Sequence Completion** (`calculateSequenceCompletion()` in BulletinController.php:613):
- If sequence `is_completed`: 100% if bulletin exists, 0% otherwise (archived)
- If sequence `is_active`: Calculate % based on grades entered (gradedSubjects / totalSubjects × 100)
- If sequence not yet active: 0% (future)

**Trimester Completion** (`calculateTrimesterCompletion()` in BulletinController.php:664):
- For each subject: (DS_completion + Composition_completion) / 2
- DS completion = % of sequence grades entered (e.g., if Seq1=100%, Seq2=0% → DS=50%)
- Updates dynamically as teachers enter grades

#### 📍 Key Status Flags (Sequence Model)

- `is_active` - Sequence is open (teachers can enter grades, always true - no locking system)
- `is_current` - This is the current active sequence (only one at a time, except compositions)
- `is_completed` - Sequence finished, bulletins can be generated
- `is_composition` - This is a composition (exam), not a regular sequence
- `is_locked` - **DEPRECATED** (system doesn't lock sequences anymore)

**Important**: The locking system has been disabled. All sequences remain open for grade entry even after completion.

#### 🔍 Key Files

- `back/app/Http/Controllers/BulletinController.php` - Main bulletin logic (1228 lines)
- `back/app/Services/BulletinService.php` - Grade calculation algorithms
- `back/app/Http/Controllers/SequenceController.php` - Sequence management
- `back/app/Http/Controllers/TrimesterController.php` - Trimester management with DS details
- `back/app/Models/Grade.php` - Grade model with `getScoreOn20()` method
- `back/app/Models/Sequence.php` - Sequence model
- `back/app/Models/Trimester.php` - Trimester model
- `back/resources/views/bulletins/` - Blade templates for PDF generation

#### 🎓 Cycle Detection Logic

Located in `BulletinController.php:determineCycleType()` (line 1199):
- Checks class name for keywords: "seconde", "première", "terminale", "2nde", "1ère", "tle"
- If found → "deuxieme" cycle
- Otherwise → "premier" cycle (default for 6ème-3ème)

### Subject Groups (Groupes de Matières) System

The system organizes subjects into **4 fixed groups** displayed on bulletins. Administrators can dynamically assign subjects to groups and customize group names via a drag-and-drop interface.

#### 📋 The 4 Subject Groups

| Code | Default French Name | Default English Name | Typical Subjects |
|------|--------------------|--------------------|------------------|
| **A** | MATIÈRES LITTÉRAIRES | LITERARY SUBJECTS | Français, Anglais, Histoire, Géographie, Expression Écrite |
| **B** | MATIÈRES SCIENTIFIQUES | SCIENTIFIC SUBJECTS | Mathématiques, Physique, SVT, Sciences |
| **C** | MATIÈRES PRATIQUES | PRACTICAL SUBJECTS | EPS, Informatique, Arts Plastiques, Travail Manuel |
| **D** | AUTRES MATIÈRES | OTHER SUBJECTS | ECM, Éducation Civique et Morale |

#### 🗄️ Database Structure

**Table: `subject_groups`**
- `id` - Primary key
- `code` - Group identifier (A, B, C, D) - **FIXED, cannot be changed**
- `header` - Header displayed on bulletins (FR), e.g., "GROUPE A"
- `header_en` - Header in English, e.g., "GROUP A"
- `name` - Full group name (FR), e.g., "MATIÈRES LITTÉRAIRES"
- `name_en` - Full group name (EN), e.g., "LITERARY SUBJECTS"
- `description` - Description of what the group contains
- `order` - Display order (1-4, fixed)
- `is_active` - Boolean flag (default: true)

**Table: `subjects`**
- `id`, `name`, `code`, `description`
- `group` - ENUM('A','B','C','D') or NULL - **This field links subjects to groups**
- `is_active` - Boolean

**Relationship**: `subjects.group` → `subject_groups.code`

#### 🎨 Frontend - Drag & Drop Interface

**Page**: `front/src/pages/Admin/SubjectGroups.jsx`
**Route**: `/admin/subject-groups`

**Features**:
- **Visual columns** for each group (A, B, C, D) with color coding:
  - Group A: Blue (#3b82f6)
  - Group B: Green (#10b981)
  - Group C: Orange (#f59e0b)
  - Group D: Purple (#8b5cf6)
- **Drag-and-drop**: Move subjects between groups with mouse
- **Real-time counter**: Shows number of subjects per group
- **"Uncategorized" section**: Subjects not yet assigned to a group
- **Bulk save**: All changes saved at once with one click

**Page**: `front/src/pages/Admin/SubjectGroupsSettings.jsx`
**Route**: `/admin/subject-groups/settings`

**Features**:
- **Customize group names**: Edit the header and name for each group (FR + EN)
- **Description field**: Add explanation of what each group contains
- **Live preview**: See how changes will appear on bulletins
- **Bilingual support**: Separate fields for French and English names

#### 🔌 API Endpoints

**1. Get all groups with subjects:**
```http
GET /api/subject-groups
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "subjects": [...all subjects...],
    "grouped": {
      "A": [...subjects in group A...],
      "B": [...subjects in group B...],
      "C": [...subjects in group C...],
      "D": [...subjects in group D...],
      "uncategorized": [...subjects without group...]
    },
    "groups": [
      {"id": 1, "code": "A", "header": "GROUPE A", "name": "MATIÈRES LITTÉRAIRES", ...},
      ...
    ]
  }
}
```

**2. Update a subject's group:**
```http
PUT /api/subject-groups/{subjectId}
Content-Type: application/json
Authorization: Bearer {token}

Body: {"group": "B"}
```

**3. Bulk update multiple subjects:**
```http
POST /api/subject-groups/bulk-update
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "updates": [
    {"id": 5, "group": "A"},
    {"id": 12, "group": "B"}
  ]
}
```

**4. Get all groups (definitions):**
```http
GET /api/subject-groups/groups
Authorization: Bearer {token}
```

**5. Update group name/description:**
```http
PUT /api/subject-groups/groups/{groupId}
Content-Type: application/json
Authorization: Bearer {token}

Body: {
  "header": "GROUPE A",
  "header_en": "GROUP A",
  "name": "MATIÈRES LINGUISTIQUES",
  "name_en": "LINGUISTIC SUBJECTS",
  "description": "Langues et littérature"
}
```

#### 🔄 Integration with Bulletin Generation

**File**: `back/app/Services/BulletinService.php`
**Function**: `groupSubjectsByType()` (line 1256)

**Logic**:
1. For each subject on the bulletin, fetch the `Subject` model by ID
2. Check if `subject->group` field is set (A, B, C, or D)
3. If set → place subject in corresponding group using database value
4. If NULL → **Fallback to name-based detection** (legacy system):
   - "Français", "Anglais", "Histoire" → Group A
   - "Mathématiques", "Physique", "SVT" → Group B
   - "EPS", "Informatique", "Arts" → Group C
   - Everything else → Group D

**Code snippet**:
```php
foreach ($subjects as $subject) {
    $subjectModel = \App\Models\Subject::find($subject['subject_id']);

    if ($subjectModel && $subjectModel->group) {
        // Use database group
        $groupKey = match($subjectModel->group) {
            'A' => 'GROUPE A : MATIÈRES LITTÉRAIRES',
            'B' => 'GROUPE B : MATIÈRES SCIENTIFIQUES',
            'C' => 'GROUPE C : MATIÈRES PRATIQUES',
            'D' => 'GROUPE D : AUTRES MATIÈRES',
        };
        $groups[$groupKey][] = $subject;
    } else {
        // Fallback to name-based detection
        $subjectName = strtolower($subject['name']);
        if (in_array($subjectName, ['anglais', 'français', ...])) {
            $groups['GROUPE A : MATIÈRES LITTÉRAIRES'][] = $subject;
        }
        // ... etc
    }
}
```

**Benefits**:
- ✅ **Fully flexible**: Admin can reorganize groups anytime without code changes
- ✅ **Backward compatible**: Works even if `group` field is NULL
- ✅ **Immediate effect**: Changes reflect on next bulletin generation
- ✅ **Bilingual**: Automatic translation for Anglophone sections

#### 🔐 Permissions

Only these roles can modify groups:
- `admin`
- `principal`
- `directeur_etudes`

Middleware: `auth:api, role:admin,principal,directeur_etudes`

#### 📝 Common Use Cases

**1. Customize for Anglophone section:**
```bash
curl -X PUT "http://localhost:8000/api/subject-groups/groups/1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "header": "GROUP A",
    "name": "ARTS AND HUMANITIES",
    "name_en": "ARTS AND HUMANITIES"
  }'
```

**2. Move "Informatique" from Group C to Group B:**
```bash
# Find subject ID for "Informatique" first
# Then:
curl -X PUT "http://localhost:8000/api/subject-groups/15" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"group": "B"}'
```

**3. Reorganize multiple subjects at once (via interface):**
- Drag "EPS" from C to A
- Drag "Informatique" from C to B
- Drag "Arts Plastiques" from C to A
- Click "Enregistrer les modifications" → Bulk update sent to API

#### ⚙️ Key Files

- **Controller**: `back/app/Http/Controllers/Api/SubjectGroupController.php` (216 lines)
- **Model (SubjectGroup)**: `back/app/Models/SubjectGroup.php` (51 lines)
- **Model (Subject)**: `back/app/Models/Subject.php` - contains `group` field
- **Service Integration**: `back/app/Services/BulletinService.php::groupSubjectsByType()` (line 1256)
- **Frontend (Drag-Drop)**: `front/src/pages/Admin/SubjectGroups.jsx`
- **Frontend (Settings)**: `front/src/pages/Admin/SubjectGroupsSettings.jsx`
- **Documentation**:
  - `back/GROUPES_MATIERES_README.md` - Technical guide
  - `back/GUIDE_GROUPES_MATIERES.md` - User guide

#### ⚠️ Limitations

- **Cannot delete groups**: The 4 groups (A, B, C, D) are fixed
- **Cannot add groups**: Limited to exactly 4 groups
- **Cannot change codes**: A will always be "A", B will always be "B", etc.
- **Order is fixed**: Groups always display in order A → B → C → D

#### 🧪 Testing

**Setup test data:**
```bash
php artisan tinker

# Check current groups
SubjectGroup::all();

# Assign a subject to group B
$subject = Subject::where('name', 'Mathématiques')->first();
$subject->group = 'B';
$subject->save();

# Generate a bulletin to see the change
```

**Via API:**
```bash
# List all subjects with their groups
curl -X GET "http://localhost:8000/api/subject-groups" \
  -H "Authorization: Bearer TOKEN"

# Change group name
curl -X PUT "http://localhost:8000/api/subject-groups/groups/1" \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "MATIÈRES DE LANGUE", "name_en": "LANGUAGE SUBJECTS"}'
```

### Payment System Logic
- **Base Amount**: Set per class (e.g., 150,000 FCFA)
- **Reductions**: Class-level scholarships (percentage-based) or individual scholarships
- **Manual Discounts**: Fixed amount reductions applied to individual students (NEW)
  - Applied only to schooling fees (never inscription)
  - Distributed across tranches starting from the last tranche (descending order)
  - Managed via `student_manual_discounts` table
  - Admin can set fixed discount amount with reason (e.g., "Réduction exceptionnelle - 50000 FCFA")
- **Tranches**: Payments split into installments (e.g., 1st tranche, 2nd tranche)
- **Redistribution**: When a payment is made, amount is distributed across fee types (inscription, schooling, documentary, transport) proportionally
- **Transfers**: When student moves between classes, payments follow with recalculation

**Priority of Discounts**:
1. Class-level scholarship (percentage on base amount)
2. Individual student scholarship (percentage)
3. Manual discount (fixed amount applied after percentages)

Key files:
- `back/app/Http/Controllers/PaymentController.php` - Payment processing
- `back/app/Services/PaymentStatusService.php` - Payment status calculation
- `back/app/Services/ManualDiscountService.php` - Manual discount logic (NEW)
- `back/app/Http/Controllers/StudentManualDiscountController.php` - Manual discount CRUD (NEW)
- `front/src/pages/ManualDiscounts/ManualDiscounts.jsx` - Manual discount UI (NEW)

### Teacher Assignment System
Teachers are assigned to class-subject combinations via `teacher_assignments`:
- One assignment = teacher + class_series_subject + school_year
- Supports multiple teachers per subject (if class has multiple groups)
- Main teacher (titulaire) assigned separately via `main_teachers` table
- Teachers see only their assigned classes and subjects in grade entry

Key files: `back/app/Http/Controllers/TeacherAssignmentController.php`

### Student ID Card System
Generate professional school identity cards with Cameroon national branding:

**Design Specifications**:
- **Format**: 85.6 mm × 54 mm (standard credit card size)
- **Layout**: Landscape orientation, single-sided (recto only)
- **Colors**: Cameroon flag colors (green #009639, red #CE1126, yellow #FCD116)
- **Elements**:
  - Top banner: Cameroon flag + "RÉPUBLIQUE DU CAMEROUN - PAIX TRAVAIL PATRIE"
  - Student photo: 3x4 cm on left side
  - School logo: Top right corner
  - Student information: Name, matricule, class, date of birth, parent contact, school year
  - QR code: 2.5x2.5 cm at bottom center (contains verification data)
  - Signature zone: Bottom right for school director

**Technical Implementation**:
- Uses DomPDF (already installed) with Blade template
- QR code generated with `simplesoftwareio/simple-qrcode` package
- QR data includes: student info, verification URL, school logo URL
- Storage: `storage/app/student-cards/`
- Database table: `student_cards` (tracks generation history)

**Generation Options**:
1. Individual card (1 per A4 page for plastification)
2. Batch mode (10 cards per A4 page - 2 columns × 5 rows)
3. Class-wide generation with single PDF download

**Security Features**:
- Unique matricule on each card
- QR code with encrypted timestamp
- Online verification via unique URL
- Watermark with school logo (subtle background)

Key files:
- Design spec: `DESIGN_CARTE_IDENTITE.md` (detailed visual layout)
- Backend routes: `/api/student-cards/*` (when implemented)
- Template location (planned): `back/resources/views/student-cards/template.blade.php`

## Common Development Workflows

### Adding a New API Endpoint
1. Create/update controller in `back/app/Http/Controllers/`
2. Add route in `back/routes/api.php` with appropriate middleware
3. Update frontend service in `front/src/services/`
4. Create/update React component in `front/src/pages/` or `front/src/components/`

### Database Modifications
1. Create migration: `php artisan make:migration description_of_change`
2. Edit migration file in `back/database/migrations/`
3. Run migration: `php artisan migrate`
4. Update corresponding Eloquent models in `back/app/Models/`

### Debugging Database Issues
Use `php artisan tinker` for interactive queries. Always remember the two-level teacher system:
```php
// Find teacher by username
$user = DB::table('users')->where('username', 'teacher_username')->first();
$teacher = DB::table('teachers')->where('user_id', $user->id)->first();
// Use $teacher->id for assignments/evaluations
```

### Working with Academic Periods (Sequences/Trimesters)

**Activating a Sequence:**
```bash
# Via API: POST /api/sequences/{id}/activate
# Or via tinker:
php artisan tinker
$sequence = Sequence::find(1);
$sequence->update(['is_current' => true, 'is_active' => true]);
```

**Checking Current Academic Status:**
```php
// Get current sequence
$currentSeq = Sequence::where('is_current', true)->where('is_active', true)->first();

// Get all active sequences (including compositions)
$activeSeqs = Sequence::where('is_active', true)->get();

// Check grades for a student in a sequence
$grades = Grade::where('student_id', $studentId)
    ->where('sequence_id', $sequenceId)
    ->where('trimester_id', $trimesterId)
    ->with('classSeriesSubject.subject')
    ->get();
```

**Understanding Grade Storage:**
- Grades are stored with both `sequence_id` AND `trimester_id`
- Always filter by BOTH when querying grades
- `score` and `max_score` are stored as entered (e.g., 15/20 or 30/40)
- Use `$grade->getScoreOn20()` to normalize to /20 scale

### Testing Payment Logic
Test with small amounts first. Check:
1. Payment detail creation in `payment_details` table
2. Amount distribution across fee types
3. Student payment status calculation
4. Receipt generation with correct amounts

### Bulletin Testing & Debugging

**Generate a Single Bulletin (API):**
```bash
# Sequence bulletin
curl -X POST http://localhost:8000/api/bulletins/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"student_id": 1, "bulletin_type": "sequence", "period_identifier": "seq1"}'

# Trimester bulletin
curl -X POST http://localhost:8000/api/bulletins/generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"student_id": 1, "bulletin_type": "trimester", "period_identifier": "trim1"}'
```

**Preview Bulletin HTML (without PDF generation):**
```bash
curl -X POST http://localhost:8000/api/bulletins/preview \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"student_id": 1, "type": "sequence", "period_identifier": "seq1"}'
```

**Batch Generate for Entire Class:**
```bash
curl -X POST http://localhost:8000/api/bulletins/batch-generate \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"class_id": 1, "bulletin_type": "sequence", "period_identifier": "seq1", "force": false}'
```

**Debug Grade Calculations via Tinker:**
```php
php artisan tinker

use App\Services\BulletinService;
$service = new BulletinService();

// Calculate DS for a student
$ds = $service->calculateDSAverage(1, $studentId, $subjectId); // trimester 1

// Calculate trimester average
$avg = $service->calculateTrimesterGrade(1, $studentId, $subjectId, 'premier');

// Get individual sequence grades (Deuxième Cycle)
$seqGrades = $service->getIndividualSequenceGrades(1, $studentId, $subjectId);
// Returns: [seq1_grade, seq2_grade] or [null, null] if no data

// Check composition grade
$compGrade = $service->getCompositionGrade(1, $studentId, $subjectId);
```

**Check Bulletin Completion Status:**
```php
// Get completion percentage for a student's sequence
$controller = new BulletinController(new BulletinService());
$completion = $controller->calculateSequenceCompletion($studentId, $sequenceNumber);
echo "Sequence $sequenceNumber completion: $completion%";

// Get completion for trimester
$trimCompletion = $controller->calculateTrimesterCompletion($studentId, $trimesterNumber);
echo "Trimester $trimesterNumber completion: $trimCompletion%";
```

**Common Bulletin Issues:**

1. **"No grades found" error:**
   - Verify `sequence_id` AND `trimester_id` are both set in grades table
   - Check that `class_series_subject_id` matches student's class
   - Ensure grades have `score` (not null) or `is_absent=true`

2. **DS showing as 0.00:**
   - This is expected if only one sequence has grades (missing seq = 0.00)
   - Check both sequences in the trimester have grades entered

3. **Coefficient cancelled (subject not appearing):**
   - Means ALL evaluations for that subject are missing
   - If at least one grade exists, subject will appear (others = 0.00)

4. **Wrong cycle type applied:**
   - Check class name contains cycle keywords (seconde, première, terminale)
   - Verify `determineCycleType()` logic in BulletinController

**Generated Files Location:**
- PDFs stored in: `storage/app/bulletins/`
- Database records in: `bulletin_generations` table
- Check logs: `storage/logs/laravel.log` for DomPDF errors

## Environment Setup

### Backend Environment Variables
Required in `back/.env`:
- `DB_DATABASE` - MySQL database name (default: c0admin)
- `DB_USERNAME`, `DB_PASSWORD` - Database credentials
- `JWT_SECRET` - JWT signing key (generate with `php artisan jwt:secret`)
- `IMGBB_API_KEY` - For image uploads in WhatsApp notifications
- `QUEUE_CONNECTION=database` - For background job processing

### Frontend Configuration
Update API base URL in `front/src/services/api.js` if backend is not on `localhost:8000`

### Mobile App Configuration
Update API endpoint in mobile app service files (check `mobile/lib/` directory)

## Troubleshooting

### "teacher_id not found" errors
- Remember: Use `teachers.id`, not `users.id` for teacher-related queries
- Always join through `teachers` table when working with pedagogical data
- See `back/GUIDE_ANALYSE_BASE_DONNEES.md` for detailed explanation

### Bulletin & Grade Issues

**Problem: Bulletin shows 0.00 for all subjects**
- Check if grades exist: `Grade::where('student_id', $studentId)->where('trimester_id', $trimId)->count()`
- Verify `sequence_id` is set correctly in grades table
- Ensure `class_series_subject_id` matches student's actual class series
- Check logs for "No grades found" messages

**Problem: DS calculation is incorrect**
- Verify BOTH sequences in trimester have grades: `Grade::where('sequence_id', $seqId)->where('trimester_id', $trimId)->exists()`
- Remember: Missing sequence = 0.00 (not null) in DS calculation
- Check `BulletinService.php::calculateDSAverage()` logs for detailed calculation trace
- Ensure grades belong to correct trimester (Seq 1,2 → Trim 1; Seq 3,4 → Trim 2)

**Problem: Student missing from bulletin generation**
- Verify student has `class_series_id` set: `Student::find($id)->class_series_id`
- Check student is enrolled in current school year
- Ensure student's class has subjects assigned in `class_series_subjects` table
- Look for foreign key constraint errors in logs

**Problem: Composition not appearing in bulletin**
- Check composition evaluation exists: `Evaluation::where('type', 'composition')->where('trimester_id', $trimId)->get()`
- Verify `is_composition=true` flag set on evaluation or sequence
- Ensure composition grade has `trimester_id` matching the bulletin trimester
- Check `BulletinService::getCompositionGrade()` return value

**Problem: Wrong cycle formulas applied (Premier vs Deuxième)**
- Verify class name contains cycle keywords (see `determineCycleType()` in BulletinController:1199)
- Premier Cycle: Classes like "6ème", "5ème", "4ème", "3ème"
- Deuxième Cycle: Classes with "seconde", "2nde", "première", "1ère", "terminale", "tle"
- Override if needed by passing `cycleType` parameter to calculation methods

**Problem: Sequence completion shows 0% but grades exist**
- Check if sequence has `is_active=true` flag
- Verify grades have `whereNotNull('score')` - null scores don't count
- Ensure `class_series_subject_id` matches between grade and subject tables
- Check if sequence `is_completed=true` (archived sequences show 100% if bulletin exists)

**Problem: Batch generation times out**
- Increase `max_execution_time` in `php.ini` or use `set_time_limit(600)` in code
- Reduce batch size from 10 to 5 students per batch in `batchGenerate()` method
- Check for N+1 query issues - use `\DB::enableQueryLog()` to debug
- Generate bulletins during off-peak hours

**Problem: Absent students showing 0.00 instead of "ABS"**
- Ensure `is_absent=true` flag is set in grades table
- Check template logic displays "ABS" for `$grade->is_absent`
- Verify grade record exists even for absent students (score can be null)

### CORS errors (frontend → backend)
- Ensure `config/cors.php` allows frontend origin
- Check middleware in `back/app/Http/Kernel.php`

### Queue jobs not processing
- Run `php artisan queue:listen` in background
- Check `failed_jobs` table for errors
- Verify `QUEUE_CONNECTION=database` in `.env`

### PDF generation issues
- Check `storage/logs/laravel.log` for DomPDF errors
- Verify image paths are absolute in Blade templates
- Ensure fonts are available for special characters
- DomPDF memory limit: increase `memory_limit` in php.ini if generating many bulletins
- Check file permissions on `storage/app/bulletins/` directory (755 or 775)

### Mobile build issues
- Run `flutter clean && flutter pub get`
- Check iOS pods: `cd ios && pod install && cd ..`
- Verify Flutter version: `flutter doctor`

### Manual Discount Issues

**Problem: Manual discount not reflected in payment calculation**
- Verify discount exists: `StudentManualDiscount::where('student_id', $id)->where('school_year_id', $yearId)->first()`
- Check discount is applied AFTER percentage scholarships
- Ensure discount amount doesn't exceed total schooling fees
- Manual discounts only apply to schooling fees (inscription excluded)
- Check `ManualDiscountService::distributeAcrossTranches()` logs

**Problem: Discount distribution incorrect across tranches**
- Verify tranches are sorted by descending order (last tranche first)
- Check that inscription tranche is excluded from discount distribution
- Ensure `required` amount per tranche is positive
- Manual discount applies to unpaid balance, not total fees

**Problem: Multiple discounts conflicting**
- Application order: (1) Class scholarship % → (2) Individual scholarship % → (3) Manual discount fixed amount
- Verify no duplicate manual discounts for same student+year combination
- Check unique constraint on `student_manual_discounts` table: `['student_id', 'school_year_id']`

## Testing

### Backend Testing
```bash
cd back
php artisan test                          # Run all tests
php artisan test --filter=PaymentTest    # Run specific test
```

### Manual Testing Checklist - Core Features
- [ ] Student enrollment and class assignment
- [ ] Teacher assignment to classes/subjects
- [ ] Grade entry for a sequence
- [ ] Bulletin generation (all formats)
- [ ] Payment with tranches and reductions
- [ ] Receipt generation with QR code
- [ ] Attendance marking (student and teacher)
- [ ] Parent account access to student data
- [ ] Excel import/export functionality

### Bulletin System Testing Checklist

**Pre-requisites Setup:**
- [ ] School year created and set as current (`is_current=true`)
- [ ] 3 Trimesters created for school year
- [ ] Sequences created per trimester (Seq 1-4 for Premier, 1-6 for Deuxième + compositions)
- [ ] Classes created with correct cycle names (e.g., "6ème A" or "2nde C")
- [ ] Subjects assigned to class series in `class_series_subjects` with coefficients
- [ ] Students enrolled with `class_series_id` set
- [ ] Teachers assigned to subjects via `teacher_assignments`

**Grade Entry Testing:**
- [ ] Activate sequence: `POST /api/sequences/{id}/activate`
- [ ] Create evaluation for a class/subject: `POST /api/evaluations`
- [ ] Enter grades for multiple students with `sequence_id` AND `trimester_id`
- [ ] Test absent student: set `is_absent=true`, `score=null`
- [ ] Verify grades display in teacher dashboard
- [ ] Test grade update/edit functionality
- [ ] Check grade validation (score ≤ max_score)

**Sequence Bulletin Testing (Premier Cycle):**
- [ ] Complete Sequence 1 grades for all subjects in a class
- [ ] Check completion percentage shows 100%: `GET /api/bulletins/students/{seriesId}/status`
- [ ] Generate Sequence 1 bulletin: `POST /api/bulletins/generate` with `bulletin_type=sequence, period_identifier=seq1`
- [ ] Verify PDF generated in `storage/app/bulletins/`
- [ ] Download and inspect PDF: check student name, grades, class rank
- [ ] Test Sequence 3 bulletin (should work)
- [ ] Verify Sequence 2 bulletin NOT available (Premier Cycle rule)

**Trimester Bulletin Testing (Premier Cycle):**
- [ ] Complete grades for Sequence 1, Sequence 2, Composition 1
- [ ] Verify DS1 = (Seq1 + Seq2) / 2 calculated correctly
- [ ] Generate Trimester 1 bulletin: `bulletin_type=trimester, period_identifier=trim1`
- [ ] Check PDF shows: DS column, Composition column, M/20 = (DS + Comp) / 2
- [ ] Test with missing Sequence 2: verify DS = (Seq1 + 0) / 2
- [ ] Test Trimester 3: verify only Composition used (no DS column)

**Deuxième Cycle Testing:**
- [ ] Create class with "Seconde" or "2nde" in name
- [ ] Complete Seq 1, Seq 2, Composition for Trimester 1
- [ ] Generate trimester bulletin
- [ ] Verify formula: M/20 = (Seq1 + Seq2 + Comp) / 3
- [ ] Check bulletin displays all 3 individual grades
- [ ] Test with missing Seq2: verify M/20 = (Seq1 + 0 + Comp) / 3

**Batch Generation Testing:**
- [ ] Generate bulletins for entire class: `POST /api/bulletins/batch-generate` with `class_id`
- [ ] Monitor generation time (should be ~1-2s per student)
- [ ] Verify all students got bulletins: check `bulletin_generations` table
- [ ] Test force regeneration with `force=true`
- [ ] Check error handling for students with incomplete data

**Edge Cases & Error Handling:**
- [ ] Test student with no grades at all (should show empty or 0.00)
- [ ] Test student with only 1 subject grade (coefficient works correctly)
- [ ] Test bulletin for student who transferred mid-year
- [ ] Test with evaluation deleted after grade entry
- [ ] Verify absent student displays "ABS" not 0
- [ ] Test concurrent bulletin generation (multiple users)
- [ ] Test PDF download after file deleted from disk
- [ ] Verify preview works without creating database record

**Completion Percentage Testing:**
- [ ] Enter 50% of subject grades → check shows ~50% completion
- [ ] Complete all Seq1, none Seq2 → DS completion should be 50%
- [ ] Complete DS, no Composition → Trimester completion should be ~50%
- [ ] Mark sequence as completed (`is_completed=true`) → check completion logic

**Class Rank & Statistics Testing:**
- [ ] Generate bulletins for 5+ students in same class
- [ ] Verify ranks are consecutive (1st, 2nd, 3rd...) with no gaps
- [ ] Check tied students get same rank
- [ ] Verify class average calculated correctly
- [ ] Test min/max values match actual extremes
- [ ] Check pass rate: (students with avg ≥ 10) / total students

## Production Deployment Notes

- Set `APP_ENV=production` and `APP_DEBUG=false` in `back/.env`
- Run `php artisan config:cache` and `php artisan route:cache`
- Set up queue worker as system service (supervisor or systemd)
- Configure web server (Nginx/Apache) for Laravel
- Use `npm run build` for frontend production build
- Secure database credentials and JWT secret
- Enable HTTPS and configure CORS appropriately
- Set up automated database backups

See `back/DEPLOIEMENT_PRODUCTION.md` for detailed deployment instructions.
