// Script de test pour tester le preview des bulletins
const testBulletinPreview = async () => {
    const baseUrl = 'http://localhost:8000/api';
    
    // Test avec un trimestre
    const trimesterPreviewData = {
        student_id: 1, // ID d'un étudiant de test
        type: 'trimester',
        period_identifier: 'trim2'
    };
    
    try {
        console.log('Testing trimester bulletin preview...');
        const response = await fetch(`${baseUrl}/bulletins/preview`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer YOUR_TOKEN_HERE', // À remplacer par un vrai token
            },
            body: JSON.stringify(trimesterPreviewData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ Trimester preview successful!');
            console.log('Data received:', {
                student: result.data.student?.first_name || 'Unknown',
                trimester: result.data.trimester?.number || 'Unknown',
                average: result.data.average || 0,
                total_points: result.data.total_points || 0,
                subjects_count: result.data.subjects?.length || 0
            });
        } else {
            console.log('❌ Trimester preview failed:', result.error);
        }
    } catch (error) {
        console.error('❌ Error testing trimester preview:', error.message);
    }
    
    // Test avec une séquence
    const sequencePreviewData = {
        student_id: 1,
        type: 'sequence', 
        period_identifier: 'seq1'
    };
    
    try {
        console.log('\nTesting sequence bulletin preview...');
        const response = await fetch(`${baseUrl}/bulletins/preview`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer YOUR_TOKEN_HERE',
            },
            body: JSON.stringify(sequencePreviewData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ Sequence preview successful!');
            console.log('Data received:', {
                student: result.data.student?.first_name || 'Unknown',
                sequence: result.data.sequence?.number || 'Unknown',
                average: result.data.average || 0,
                total_points: result.data.total_points || 0,
                subjects_count: result.data.subjects?.length || 0
            });
        } else {
            console.log('❌ Sequence preview failed:', result.error);
        }
    } catch (error) {
        console.error('❌ Error testing sequence preview:', error.message);
    }
};

// Exporter la fonction pour Node.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { testBulletinPreview };
}

// Pour le navigateur
if (typeof window !== 'undefined') {
    window.testBulletinPreview = testBulletinPreview;
}

console.log('Test script created. You can run testBulletinPreview() to test the bulletin preview functionality.');