#!/bin/bash

# Script de test complet du flux de correction en local
# À exécuter APRÈS l'import de la BD de production

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}===========================================${NC}"
echo -e "${BLUE}TEST COMPLET DE LA CORRECTION EN LOCAL${NC}"
echo -e "${BLUE}===========================================${NC}\n"

# Vérifier que la BD est importée
echo -e "${YELLOW}🔍 Étape 1: Vérification de la connexion à la base de données...${NC}\n"

php artisan tinker --execute='
$count = DB::table("sequences")->count();
if ($count > 0) {
    echo "✅ Connexion OK - $count séquences trouvées\n";
} else {
    echo "❌ Aucune séquence trouvée - Assurez-vous que la BD est importée\n";
    exit(1);
}
'

if [ $? -ne 0 ]; then
    echo -e "\n${RED}❌ Erreur de connexion à la base de données${NC}"
    echo "Assurez-vous que:"
    echo "  1. MySQL est démarré (XAMPP)"
    echo "  2. La base de données est importée"
    echo "  3. Le fichier .env est correctement configuré"
    exit 1
fi

echo ""
read -p "Appuyez sur Entrée pour continuer avec le diagnostic..."

# Diagnostic
echo -e "\n${YELLOW}🔍 Étape 2: Diagnostic des séquences...${NC}\n"
echo -e "${YELLOW}===========================================${NC}\n"

php artisan tinker < scripts/diagnose_sequences_production.php

echo -e "\n${YELLOW}===========================================${NC}"
echo ""
read -p "Appuyez sur Entrée pour continuer avec la correction..."

# Correction
echo -e "\n${GREEN}🔧 Étape 3: Correction des séquences...${NC}\n"
echo -e "${GREEN}===========================================${NC}\n"

php artisan tinker < scripts/fix_sequences_production.php

echo -e "\n${GREEN}===========================================${NC}"
echo ""
read -p "Appuyez sur Entrée pour la vérification finale..."

# Vérification finale
echo -e "\n${BLUE}✅ Étape 4: Vérification finale...${NC}\n"

php artisan tinker --execute='
$sequences = DB::table("sequences")
    ->where("trimester_id", 1)
    ->orderBy("number")
    ->get(["id", "name", "number", "is_composition"]);

echo "Séquences du Trimestre 1:\n\n";
foreach ($sequences as $seq) {
    $type = $seq->is_composition ? "🎯 COMPOSITION" : "📝 SÉQUENCE";
    $icon = $seq->is_composition ? "✅" : "✅";
    echo "$icon ID {$seq->id} | {$seq->name} | Number: {$seq->number} | {$type}\n";
}

echo "\n";

// Vérifier qu il n y a pas de "Séquence 3"
$seq3 = DB::table("sequences")
    ->where("trimester_id", 1)
    ->where("number", 3)
    ->where("is_composition", 0)
    ->first();

if ($seq3) {
    echo "❌ PROBLÈME: \"Séquence 3\" existe encore dans Trimestre 1\n";
} else {
    echo "✅ SUCCÈS: Aucune \"Séquence 3\" dans Trimestre 1\n";
}

// Vérifier que les compositions ont is_composition=1
$wrongComps = DB::table("sequences")
    ->where(function($q) {
        $q->where("name", "LIKE", "%Composition%")
          ->where("is_composition", 0);
    })
    ->count();

if ($wrongComps > 0) {
    echo "❌ PROBLÈME: $wrongComps compositions ont encore is_composition=0\n";
} else {
    echo "✅ SUCCÈS: Toutes les compositions ont is_composition=1\n";
}
'

echo ""
echo -e "${BLUE}===========================================${NC}"
echo -e "${BLUE}TEST TERMINÉ${NC}"
echo -e "${BLUE}===========================================${NC}\n"

echo -e "${GREEN}💡 Prochaines étapes:${NC}"
echo "  1. Testez la génération d'un PV en local:"
echo "     → Allez sur http://localhost:3006/admin/pv"
echo "     → Sélectionnez une classe"
echo "     → Vérifiez que vous voyez 'Composition 1' et non 'Séquence 3'"
echo ""
echo "  2. Si tout fonctionne en local, appliquez la même correction en production:"
echo "     → Connectez-vous en SSH à votre serveur"
echo "     → Exécutez les mêmes scripts"
echo ""
echo "  3. N'oubliez pas de faire un backup de la prod avant!"
echo ""
