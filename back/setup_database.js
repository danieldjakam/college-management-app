const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');
require('dotenv').config({ path: '.env' });

async function setupDatabase() {
    console.log('🚀 Configuration de la base de données GSBPL...\n');

    // Configuration de connexion
    const config = {
        host: process.env.DB_HOST || 'localhost',
        port: process.env.DB_PORT || 3306,
        user: process.env.DB_USERNAME || 'root',
        password: process.env.DB_PASSWORD || '',
    };

    let connection;

    try {
        // Connexion initiale sans spécifier de base de données
        console.log('📡 Connexion au serveur MySQL...');
        connection = await mysql.createConnection(config);
        console.log('✅ Connexion au serveur MySQL réussie !');

        // Lecture et exécution du script de création de la base de données
        console.log('\n📚 Création de la structure de la base de données...');
        const schemaPath = path.join(__dirname, '..', 'database_schema.sql');
        
        if (!fs.existsSync(schemaPath)) {
            throw new Error(`Le fichier database_schema.sql n'a pas été trouvé à : ${schemaPath}`);
        }

        const schemaSQL = fs.readFileSync(schemaPath, 'utf8');
        
        // Diviser le script en requêtes individuelles
        const queries = schemaSQL
            .split(';')
            .filter(query => query.trim().length > 0)
            .filter(query => !query.trim().startsWith('--'))
            .filter(query => !query.trim().startsWith('/*'));

        for (const query of queries) {
            if (query.trim()) {
                try {
                    await connection.execute(query.trim());
                } catch (error) {
                    // Ignorer les erreurs "database exists" et "table exists"
                    if (!error.message.includes('already exists')) {
                        console.warn(`⚠️  Attention sur la requête: ${error.message}`);
                    }
                }
            }
        }
        
        console.log('✅ Structure de la base de données créée avec succès !');

        // Se reconnecter avec la base de données spécifiée
        await connection.end();
        config.database = process.env.DB_NAME || 'gsbpl_school_management';
        connection = await mysql.createConnection(config);

        // Lecture et exécution du script de données de test
        console.log('\n📊 Insertion des données de test...');
        const seedPath = path.join(__dirname, '..', 'seed_data.sql');
        
        if (!fs.existsSync(seedPath)) {
            throw new Error(`Le fichier seed_data.sql n'a pas été trouvé à : ${seedPath}`);
        }

        const seedSQL = fs.readFileSync(seedPath, 'utf8');
        
        // Diviser le script en requêtes individuelles
        const seedQueries = seedSQL
            .split(';')
            .filter(query => query.trim().length > 0)
            .filter(query => !query.trim().startsWith('--'))
            .filter(query => !query.trim().startsWith('/*'));

        for (const query of seedQueries) {
            if (query.trim()) {
                try {
                    await connection.execute(query.trim());
                } catch (error) {
                    // Ignorer les erreurs de données dupliquées
                    if (!error.message.includes('Duplicate entry')) {
                        console.warn(`⚠️  Attention sur l'insertion: ${error.message}`);
                    }
                }
            }
        }
        
        console.log('✅ Données de test insérées avec succès !');

        // Vérification de l'installation
        console.log('\n🔍 Vérification de l\'installation...');
        
        const [sections] = await connection.execute('SELECT COUNT(*) as count FROM sections');
        const [classes] = await connection.execute('SELECT COUNT(*) as count FROM class');
        const [students] = await connection.execute('SELECT COUNT(*) as count FROM students');
        const [teachers] = await connection.execute('SELECT COUNT(*) as count FROM teachers');
        const [users] = await connection.execute('SELECT COUNT(*) as count FROM users');

        console.log('\n📊 Résumé de l\'installation:');
        console.log(`   • Sections: ${sections[0].count}`);
        console.log(`   • Classes: ${classes[0].count}`);
        console.log(`   • Étudiants: ${students[0].count}`);
        console.log(`   • Enseignants: ${teachers[0].count}`);
        console.log(`   • Utilisateurs admin/comptables: ${users[0].count}`);

        console.log('\n🎉 Installation terminée avec succès !');
        console.log('\n📋 Comptes de test disponibles:');
        console.log('   👨‍💼 Admin: username="admin", password="password123"');
        console.log('   💼 Comptable: username="comptable", password="password123"');
        console.log('   👩‍🏫 Enseignants: matricule="SEM-CP-A", password="5678" (exemple)');
        
        console.log('\n🚀 Vous pouvez maintenant démarrer votre serveur backend !');
        console.log('   npm start ou yarn start');

    } catch (error) {
        console.error('❌ Erreur lors de la configuration de la base de données:');
        console.error(error.message);
        process.exit(1);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

// Test de connexion simple
async function testConnection() {
    console.log('🧪 Test de connexion à la base de données...');
    
    const config = {
        host: process.env.DB_HOST || 'localhost',
        port: process.env.DB_PORT || 3306,
        user: process.env.DB_USERNAME || 'root',
        password: process.env.DB_PASSWORD || '',
        database: process.env.DB_NAME || 'gsbpl_school_management'
    };

    try {
        const connection = await mysql.createConnection(config);
        await connection.execute('SELECT 1');
        await connection.end();
        
        console.log('✅ Connexion à la base de données réussie !');
        return true;
    } catch (error) {
        console.error('❌ Erreur de connexion à la base de données:');
        console.error(error.message);
        return false;
    }
}

// Vérifier les arguments de la ligne de commande
const command = process.argv[2];

if (command === 'test') {
    testConnection();
} else if (command === 'setup' || !command) {
    setupDatabase();
} else {
    console.log('Usage:');
    console.log('  node setup_database.js setup  - Créer la base de données et insérer les données');
    console.log('  node setup_database.js test   - Tester la connexion à la base de données');
}