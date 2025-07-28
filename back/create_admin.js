const bcrypt = require('bcrypt');
const mysql = require('mysql2');
const jwt = require('jsonwebtoken');
require('dotenv').config({ path: '.env' });

async function createAdmin() {
    console.log('🔐 Création de l\'utilisateur administrateur...');
    
    const connection = mysql.createConnection({
        host: process.env.DB_HOST || 'localhost',
        user: process.env.DB_USERNAME || 'root',
        password: process.env.DB_PASSWORD || '',
        database: process.env.DB_NAME || 'gsbpl_school_management'
    });

    try {
        // Générer le hash du mot de passe
        const password = 'password123';
        const salt = await bcrypt.genSalt(10);
        const hashedPassword = await bcrypt.hash(password, salt);
        
        // Créer l'ID avec JWT
        const adminId = jwt.sign('admin', process.env.SECRET);
        const compId = jwt.sign('comptable', process.env.SECRET);
        
        console.log('🔑 Hash du mot de passe généré');
        
        // Supprimer les anciens utilisateurs et recréer
        connection.query('DELETE FROM users WHERE username IN ("admin", "comptable")', (err) => {
            if (err) {
                console.error('Erreur lors de la suppression:', err.message);
            }
            
            // Insérer les nouveaux utilisateurs avec les bons mots de passe
            const insertQuery = `
                INSERT INTO users (id, username, email, password, school_id, role) VALUES
                (?, 'admin', 'admin@gsbpl.com', ?, 'GSBPL_001', 'ad'),
                (?, 'comptable', 'comptable@gsbpl.com', ?, 'GSBPL_001', 'comp')
            `;
            
            connection.query(insertQuery, [adminId, hashedPassword, compId, hashedPassword], (error, results) => {
                if (error) {
                    console.error('❌ Erreur lors de la création:', error.message);
                } else {
                    console.log('✅ Utilisateurs créés avec succès !');
                    console.log('\n📋 Comptes créés:');
                    console.log('   👨‍💼 Admin: username="admin", password="password123"');
                    console.log('   💼 Comptable: username="comptable", password="password123"');
                    console.log('\n🔐 Les mots de passe sont maintenant correctement hashés avec bcrypt');
                }
                connection.end();
            });
        });
        
    } catch (error) {
        console.error('❌ Erreur:', error.message);
        connection.end();
    }
}

createAdmin();