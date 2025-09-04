#!/bin/bash

# Setup HTTPS avec mkcert (plus simple et trusted)
echo "🔒 Configuration HTTPS avec mkcert..."

# Vérifier si mkcert est installé
if ! command -v mkcert &> /dev/null; then
    echo "📦 Installation de mkcert..."
    
    # Détecter l'OS
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        if command -v brew &> /dev/null; then
            brew install mkcert
        else
            echo "❌ Homebrew non installé. Installez mkcert manuellement:"
            echo "https://github.com/FiloSottile/mkcert#installation"
            exit 1
        fi
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Linux
        sudo apt-get update
        sudo apt-get install mkcert
    else
        echo "❌ OS non supporté. Installez mkcert manuellement:"
        echo "https://github.com/FiloSottile/mkcert#installation"
        exit 1
    fi
fi

# Installer l'autorité de certification locale
echo "🔧 Installation de l'autorité de certification..."
mkcert -install

# Créer le dossier certs
mkdir -p certs

# Générer les certificats pour localhost
echo "📜 Génération des certificats pour localhost..."
mkcert -key-file certs/localhost.key -cert-file certs/localhost.crt localhost 127.0.0.1

echo "✅ Configuration HTTPS terminée !"
echo "🚀 Démarrez l'application avec: npm start"
echo "🌐 Accédez à: https://localhost:3006"
echo "✨ Aucun avertissement de sécurité avec mkcert !"