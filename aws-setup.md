# MagicAI AWS Deployment Guide

## Paso 1: Crear EC2 Instance

1. Ve a AWS Console → EC2 → Launch Instance
2. Configuración:
   - Name: magicai-production
   - AMI: Ubuntu Server 22.04 LTS
   - Instance type: t3.small
   - Key pair: Crear nueva o usar existente
   - Security Group: Crear nuevo con estas reglas:
     - SSH (22): Tu IP
     - HTTP (80): 0.0.0.0/0
     - HTTPS (443): 0.0.0.0/0
     - MySQL (3306): Solo desde la instancia
   - Storage: 30GB gp3

3. En "Advanced Details" → User Data, pegar el script de instalación

## Paso 2: Configurar Dominio (Opcional)
- Apuntar tu dominio a la IP pública de la instancia
- O usar la IP pública directamente para testing

## Paso 3: Conectar por SSH
```bash
ssh -i tu-key.pem ubuntu@IP-PUBLICA
```