#!/bin/bash

# Script pour générer des certificats SSL locaux
# Pour l'accès caméra en développement

echo "🔒 Génération des certificats SSL pour le développement..."

# Créer le dossier certs s'il n'existe pas
mkdir -p certs

# Générer la clé privée
openssl genrsa -out certs/localhost.key 2048

# Générer le certificat auto-signé
openssl req -new -x509 -key certs/localhost.key -out certs/localhost.crt -days 365 -subj "/CN=localhost"

echo "✅ Certificats générés dans le dossier certs/"
echo "📁 localhost.key - Clé privée"
echo "📁 localhost.crt - Certificat"
echo ""
echo "🚀 Vous pouvez maintenant démarrer l'application avec HTTPS :"
echo "npm start"
echo ""
echo "⚠️  Le navigateur affichera un avertissement de sécurité"
echo "   Cliquez sur 'Paramètres avancés' > 'Accéder à localhost (non sécurisé)'"