#!/bin/bash

###############################################################################
# Script de connexion à la base de données de production via tunnel SSH
# Collège Polyvalent Bilingue de Douala
###############################################################################

echo "🔐 Création du tunnel SSH vers la base de données de production..."
echo ""
echo "⚠️  ATTENTION: Ce tunnel permet d'accéder directement à la base de production!"
echo "⚠️  Soyez prudent avec les modifications."
echo ""
echo "Une fois le tunnel établi:"
echo "  - Hôte: 127.0.0.1"
echo "  - Port: 3307 (local)"
echo "  - Base: c0admin"
echo "  - User: c0admin_cpb"
echo "  - Pass: Estuaire@2025"
echo ""
echo "Pour arrêter le tunnel: Ctrl+C"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Créer le tunnel SSH
# Format: ssh -L [port_local]:localhost:[port_distant] user@serveur
ssh -L 3307:127.0.0.1:3306 adminChrisDev@31.207.34.69 -N

echo ""
echo "✅ Tunnel fermé."
