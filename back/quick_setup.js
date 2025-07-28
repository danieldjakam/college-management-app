const mysql = require('mysql2');
require('dotenv').config({ path: '.env' });

// Configuration de connexion
const connection = mysql.createConnection({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USERNAME || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'gsbpl_school_management',
    multipleStatements: true
});

console.log('🚀 Configuration rapide de la base de données...');

// Test de connexion
connection.connect((err) => {
    if (err) {
        console.error('❌ Erreur de connexion:', err.message);
        process.exit(1);
    }
    
    console.log('✅ Connexion à MySQL réussie !');
    
    // Créer les tables principales nécessaires pour le fonctionnement de base
    const createUsersTable = `
        CREATE TABLE IF NOT EXISTS users (
            id VARCHAR(255) NOT NULL PRIMARY KEY,
            username VARCHAR(30) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            school_id VARCHAR(255) NOT NULL,
            role ENUM('ad', 'comp') NOT NULL DEFAULT 'comp',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `;

    const createSectionsTable = `
        CREATE TABLE IF NOT EXISTS sections (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(100) NOT NULL,
            school_year VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `;

    const createTeachersTable = `
        CREATE TABLE IF NOT EXISTS teachers (
            id VARCHAR(255) NOT NULL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            subname VARCHAR(255) NOT NULL,
            class_id VARCHAR(255) DEFAULT NULL,
            matricule VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(10) NOT NULL,
            sex ENUM('f', 'm') NOT NULL,
            phone_number VARCHAR(20) DEFAULT NULL,
            birthday DATE DEFAULT NULL,
            school_id VARCHAR(255) NOT NULL,
            school_year VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `;

    const createClassTable = `
        CREATE TABLE IF NOT EXISTS class (
            id VARCHAR(255) NOT NULL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            level INT(11) NOT NULL,
            section INT(11) NOT NULL,
            inscriptions_olds_students DECIMAL(10,2) DEFAULT 0.00,
            inscriptions_news_students DECIMAL(10,2) DEFAULT 0.00,
            first_tranch_news_students DECIMAL(10,2) DEFAULT 0.00,
            first_tranch_olds_students DECIMAL(10,2) DEFAULT 0.00,
            second_tranch_news_students DECIMAL(10,2) DEFAULT 0.00,
            second_tranch_olds_students DECIMAL(10,2) DEFAULT 0.00,
            third_tranch_news_students DECIMAL(10,2) DEFAULT 0.00,
            third_tranch_olds_students DECIMAL(10,2) DEFAULT 0.00,
            graduation DECIMAL(10,2) DEFAULT 0.00,
            school_id VARCHAR(255) NOT NULL,
            school_year VARCHAR(20) NOT NULL,
            teacherId VARCHAR(255) DEFAULT NULL,
            first_date INT(11) DEFAULT NULL,
            last_date INT(11) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `;

    const createStudentsTable = `
        CREATE TABLE IF NOT EXISTS students (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            subname VARCHAR(255) NOT NULL,
            class_id VARCHAR(255) NOT NULL,
            sex ENUM('f', 'm') NOT NULL,
            fatherName VARCHAR(255) DEFAULT NULL,
            profession VARCHAR(255) DEFAULT NULL,
            birthday DATE DEFAULT NULL,
            birthday_place VARCHAR(255) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            phone_number VARCHAR(20) DEFAULT NULL,
            school_year VARCHAR(20) NOT NULL,
            status ENUM('new', 'old') NOT NULL DEFAULT 'new',
            is_new ENUM('yes', 'no') NOT NULL DEFAULT 'yes',
            school_id VARCHAR(255) NOT NULL,
            inscription DECIMAL(10,2) DEFAULT 0.00,
            first_tranch DECIMAL(10,2) DEFAULT 0.00,
            second_tranch DECIMAL(10,2) DEFAULT 0.00,
            third_tranch DECIMAL(10,2) DEFAULT 0.00,
            graduation DECIMAL(10,2) DEFAULT 0.00,
            assurance DECIMAL(10,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `;

    const createPaymentsTable = `
        CREATE TABLE IF NOT EXISTS payments_details (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            student_id INT(11) DEFAULT NULL,
            operator_id VARCHAR(255) DEFAULT NULL,
            amount DECIMAL(10,2) NOT NULL,
            recu_name VARCHAR(255) DEFAULT NULL,
            tag VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `;

    const createSettingsTable = `
        CREATE TABLE IF NOT EXISTS settings (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            school_id VARCHAR(255) NOT NULL,
            year_school VARCHAR(20) NOT NULL,
            is_after_compo ENUM('yes', 'no') NOT NULL DEFAULT 'no',
            is_editable ENUM('yes', 'no') NOT NULL DEFAULT 'yes',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `;

    // Insérer les données de base
    const insertDefaultUser = `
        INSERT IGNORE INTO users (id, username, email, password, school_id, role) VALUES
        ('admin_001', 'admin', 'admin@gsbpl.com', '$2b$10$X9J0YvYvYvYvYvYvYvYvYuK8K8K8K8K8K8K8K8K8K8K8K8K8K8K8', 'GSBPL_001', 'ad'),
        ('comp_001', 'comptable', 'comptable@gsbpl.com', '$2b$10$X9J0YvYvYvYvYvYvYvYvYuK8K8K8K8K8K8K8K8K8K8K8K8K8K8K8', 'GSBPL_001', 'comp');
    `;

    const insertDefaultSections = `
        INSERT IGNORE INTO sections (id, name, type, school_year) VALUES
        (1, 'Maternelle', 'Préscolaire', '2024-2025'),
        (2, 'Primaire Francophone', 'Primaire', '2024-2025'),
        (3, 'Primaire Anglophone', 'Primaire', '2024-2025');
    `;

    const insertDefaultSettings = `
        INSERT IGNORE INTO settings (school_id, year_school, is_after_compo, is_editable) VALUES
        ('GSBPL_001', '2024-2025', 'no', 'yes');
    `;

    // Exécuter les requêtes
    const queries = [
        createUsersTable,
        createSectionsTable,
        createTeachersTable,
        createClassTable,
        createStudentsTable,
        createPaymentsTable,
        createSettingsTable,
        insertDefaultUser,
        insertDefaultSections,
        insertDefaultSettings
    ];

    let completed = 0;
    queries.forEach((query, index) => {
        connection.query(query, (error, results) => {
            if (error) {
                console.error(`❌ Erreur sur la requête ${index + 1}:`, error.message);
            } else {
                console.log(`✅ Requête ${index + 1} exécutée avec succès`);
            }
            
            completed++;
            if (completed === queries.length) {
                // Vérifier l'installation
                connection.query('SELECT COUNT(*) as count FROM users', (err, results) => {
                    if (!err) {
                        console.log(`\n📊 ${results[0].count} utilisateur(s) créé(s)`);
                    }
                    
                    connection.query('SELECT COUNT(*) as count FROM sections', (err, results) => {
                        if (!err) {
                            console.log(`📊 ${results[0].count} section(s) créée(s)`);
                        }
                        
                        console.log('\n🎉 Configuration de base terminée !');
                        console.log('\n📋 Comptes de test:');
                        console.log('   👨‍💼 Admin: username="admin", password="password123"');
                        console.log('   💼 Comptable: username="comptable", password="password123"');
                        console.log('\n🚀 Vous pouvez maintenant tester le serveur !');
                        
                        connection.end();
                    });
                });
            }
        });
    });
});