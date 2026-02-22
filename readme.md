# 🚀 Career Fair 2026 - Deployment Guide

## 1. Environment & SSL Setup
1. **Configure Environment**: Create a `.env` file in the root directory. Use lowercase for database usernames.
2. **Move SSL Certificates**: Place your certificate and private key inside `server/` named `cert` and `pkey` respectively.

## 2. Launch Containers
Execute the following from the project root:
```bash
docker compose up -d --build
```

## 3. Global Initialization (Run Once)
To initialize the database, tables, and default operator accounts securely, run the setup script inside the container:
```bash
docker compose exec server php cli_setup.php
```

> [!IMPORTANT]
> This command will print **random temporary passwords** for the Secretary and Gatekeeper. Copy them immediately!

## 4. Next Steps
1. **Change Operator Passwords**: Log into the Superadmin dashboard at `/costas/os.php` to change the temporary passwords.
2. **Setup Complete**: Your server is now initialized and secured.
