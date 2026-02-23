# 🚀 Career Fair 2026 - Interview Hub (v2)

A premium, real-time interview management platform designed for the 2026 Career Fair. This version (v2) features enhanced security, a redesigned company dashboard, and persistent session management.

## ✨ Key Features
- **Modern UI**: Full dark-mode/glassmorphism design with global maroon-to-orange gradients.
- **Real-Time Dashboards**: Live student queues and interview timers for both companies and organizers.
- **Persistent Sessions**: Companies stay logged in for 30 days, even after their initial access token expires.
- **Production-Ready Security**: 
    - Full PDO Prepared Statement refactor to prevent SQL Injection.
    - Secure password hashing (BCRYPT) and elimination of insecure setup scripts.
    - CLI-only initialization for maximum backend safety.

## 🏗️ Deployment Guide

### 1. Download the Application
SSH into your server and clone the repository directly from GitHub:

```bash
cd /home/career/
git clone https://github.com/panpapadopoulos/cdiqv2.git uop-cdiq
cd uop-cdiq
```

### 2. Configure Environment & Hidden Files
Because sensitive files are hidden from Git, you must manually create them on the server:

1. **Create the Environment File:**
   ```bash
   nano .env
   ```
   Paste the following into your `.env` file, replacing the passwords and ID:
   ```env
   SERVER_NAME="apps.careerday.fet.uop.gr"
   SERVER_SSL_CERT_FILE="/etc/ssl_for_https/cert"
   SERVER_SSL_PKEY_FILE="/etc/ssl_for_https/pkey"

   DBMS_USERNAME="postgres"
   DBMS_PASSWORD="yournewsecurepassword"
   DBMS_DATABASE="cdiq"

   CANDIDATE_GOOGLE_CLIENT_ID="YOUR_GOOGLE_CLIENT_ID"
   ```
   *Save and exit Nano (`Ctrl+O`, `Enter`, `Ctrl+X`).*

2. **Generate Your Certificates:** 
   Run these commands to instantly generate your SSL certs valid for 1 year:
   ```bash
   cd server
   openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout pkey -out cert -subj "/C=GR/ST=Attica/L=Athens/O=UoP/OU=IT/CN=apps.careerday.fet.uop.gr"
   cd ..
   ```

### 3. Build & Boot the Server
```bash
# Build the main stack and start it in the background
docker compose up -d --build server dbms
```

### 4. Initialize Database and Accounts
Since you are starting a fresh clone, the database must be constructed and your doors (Secretary/Gatekeeper) need accounts.

```bash
# Enter the running server container
docker exec -it uop-cdiq-server-1 bash

# 1. Build all the database tables
php .private/_admin/dbms/create.php

# 2. Create the Operator Accounts (enter secure passwords when prompted)
php .private/_admin/operators.php add secretary gg
php .private/_admin/operators.php add gatekeeper gg

# 3. Exit the container
exit
```
---
*Created for the UoP Career Fair 2026.*
