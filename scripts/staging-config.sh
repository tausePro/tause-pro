#!/bin/bash

# ⚙️ Configuración de Variables para Staging
# Este archivo contiene las variables de configuración para staging
# Ejecuta: source scripts/staging-config.sh para cargar las variables

# Información de la instancia staging existente
export STAGING_HOST="13.218.39.31"
export STAGING_USER="ubuntu"
export STAGING_INSTANCE_ID="i-06113402909fd6b57"
export STAGING_KEY_FILE="staging-tausepro-key.pem"

# Información de producción
export PROD_HOST="34.207.248.220"
export PROD_USER="ubuntu"
export PROD_KEY_FILE="magicai-tause-key.pem"
export PROD_PATH="/var/www/magicai"

# Configuración del proyecto staging
export PROJECT_NAME="magicai-staging"
export STAGING_PATH="/var/www/${PROJECT_NAME}"
export DOMAIN="test.tause.pro"  # Subdominio de staging

# Configuración de base de datos
export DB_NAME="magicai_staging"
export DB_USER="magicai_staging"
export PROD_DB_NAME="magicai"

# Git
export GIT_BRANCH="main"

echo "✅ Variables de staging cargadas:"
echo "   STAGING_HOST: $STAGING_HOST"
echo "   STAGING_USER: $STAGING_USER"
echo "   PROJECT_NAME: $PROJECT_NAME"
echo "   DOMAIN: $DOMAIN"

