#!/bin/bash
set -e

echo "========================================================"
echo "  SMK PGRI 5 SERPONG - Menjalankan Semua Sistem (Docker)"
echo "  [ SMART+ & SMART-CBT ]"
echo "========================================================"
echo ""

# 1. Pastikan .env pada smart+ tersedia
if [ ! -f "smart+/.env" ]; then
    echo "[*] Menyiapkan smart+/.env dari .env.docker..."
    cp "smart+/.env.docker" "smart+/.env"
fi

# 2. Pastikan .env pada smart-cbt tersedia
if [ ! -f "smart-cbt/.env" ]; then
    echo "[*] Menyiapkan smart-cbt/.env dari .env.docker..."
    cp "smart-cbt/.env.docker" "smart-cbt/.env"
fi

echo "[*] Menjalankan build & starting seluruh container..."
docker compose up -d --build

echo ""
echo "[*] Melakukan optimasi & storage link..."
docker compose exec smart-app php artisan storage:link --force || true
docker compose exec smart-cbt-app php artisan storage:link --force || true

echo ""
echo "========================================================"
echo "  SEMUA CONTAINER BERHASIL DIAKTIFKAN!"
echo "========================================================"
echo ""
echo "  1. SMART+ (Aplikasi Sekolah & E-Rapor) : http://127.0.0.1:8002 (https://smart.smkpgri5serpong.sch.id)"
echo "  2. SMART-CBT (Sistem Ujian Online)      : http://127.0.0.1:8003 (https://cbt.smkpgri5serpong.sch.id)"
echo "========================================================"
echo ""
