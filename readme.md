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

---

## 🏗️ Deployment Guide

### 1. Environment & SSL Setup
1. **Configure Environment**: Create a `.env` file in the root directory (see `config.php` for vars).
2. **Move SSL Certificates**: Place your certificate and private key inside `server/` named `cert` and `pkey` respectively.

### 2. Launch Containers
Execute the following from the project root:
```bash
docker compose up -d --build
```

### 3. Global Initialization (Run Once)
To initialize the database, tables, and default operator accounts securely, run the setup script inside the container:
```bash
docker compose exec server php cli_setup.php
```

> [!IMPORTANT]
> This command will print **random temporary passwords** for the Secretary and Gatekeeper. Copy them immediately!

### 4. Next Steps
1. **Admin Login**: Access the Superadmin dashboard at `/costas/os.php`.
2. **Change Passwords**: Use the **👥 Operators** tab in the Superadmin page to update the temporary passwords.
3. **Setup Complete**: Your server is now initialized, hardened, and ready for the event.

---
*Created for the UoP Career Fair 2026.*
