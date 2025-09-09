// Debug détaillé pour le numéro +23755773402

function debugPhoneValidation(phone) {
    
    if (!phone || phone.trim() === '') {
        return { isValid: true, message: '' };
    }
    
    // Remove all non-digit characters except +
    const cleanPhone = phone.replace(/[^+\d]/g, '');
    
    // Check basic format
    const phoneRegex = /^(\+)?[1-9]\d{7,14}$/;
    const basicValid = phoneRegex.test(cleanPhone);
    
    if (!basicValid) {
        const message = 'Format de téléphone invalide. Utilisez 8-15 chiffres avec ou sans indicatif (+237...)';
        return { isValid: false, message };
    }
    
    // Additional validation for Cameroon numbers
    if (cleanPhone.startsWith('+237') || cleanPhone.startsWith('237')) {
        
        const number = cleanPhone.replace(/^\+?237/, '');
        
        if (number.length !== 9) {
            const message = `Numéro camerounais invalide: longueur ${number.length} au lieu de 9. Format attendu: +237 6XX XXX XXX`;
            return { isValid: false, message };
        }
        
        if (!number.startsWith('6')) {
            const message = `Numéro camerounais invalide: commence par ${number[0]} au lieu de 6. Format attendu: +237 6XX XXX XXX`;
            return { isValid: false, message };
        }
        
    } else {
    }
    
    return { isValid: true, message: '' };
}

// Test du numéro problématique
const testPhone = '+23755773402';
const result = debugPhoneValidation(testPhone);
if (!result.isValid) {
}

// Test d'autres variantes
[
    '23755773402',      // Sans +
    '+237 557 734 02',  // Avec espaces  
    '+237655773402',    // Version correcte avec 6
    '+237 655 773 402', // Version correcte avec espaces
].forEach(phone => {
    const result = debugPhoneValidation(phone);
});