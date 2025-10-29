# 💻 Web Peramalan Penjualan AdiComp

![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-Database-orange?logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-Frontend-purple?logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green)
![Status](https://img.shields.io/badge/Status-Final_Project-success)
![Last Commit](https://img.shields.io/github/last-commit/rhmtfzn/web-peramalan-penjualan-adicomp)

---

Aplikasi ini dikembangkan sebagai bagian dari penelitian akademik yang membahas penerapan metode peramalan penjualan berbasis website. 
Website ini dikembangkan menggunakan **PHP Native** dan **MySQL** untuk membantu toko melakukan **peramalan penjualan** berdasarkan data historis penjualan.

---

## 🚀 Fitur Utama
- Input dan kelola data penjualan per produk.  
- Peramalan penjualan menggunakan dua metode:
  - **Weighted Moving Average (WMA)**
  - **Single Exponential Smoothing (SES)**  
- Perbandingan hasil akurasi kedua metode menggunakan **Mean Squared Error (MSE)**.  
- Fitur **rekomendasi stok** berdasarkan hasil prediksi.  
- Dashboard informatif dengan **Bootstrap**.

---

## 🧩 Teknologi yang Digunakan
| Komponen | Teknologi |
|-----------|------------|
| Frontend  | HTML, CSS, JavaScript, Bootstrap |
| Backend   | PHP Native |
| Database  | MySQL |
| Tools     | XAMPP, phpMyAdmin |

---

## ⚙️ Cara Menjalankan Project
1. Clone repository ini:
   ```bash
   git clone https://github.com/rhmtfzn/web-peramalan-penjualan-adicomp.git
   
2. Pindahkan folder hasil clone ke direktori C:\xampp\htdocs\.

3. Import database adicomp.sql ke phpMyAdmin.

4. Jalankan XAMPP (Apache & MySQL).

5. Akses melalui browser: http://localhost/adicomp


🔐 Informasi Login (Contoh akun)

Admin

Username: admin

Password: admin

Karyawan

Username: karyawan

Password: karyawan

Catatan: Untuk keamanan, ganti password default ini sebelum digunakan di lingkungan produksi.


📊 Tujuan

Membantu pengusaha toko menganalisis tren penjualan dan menentukan strategi stok yang lebih efisien berdasarkan hasil peramalan yang akurat menggunakan metode WMA dan SES.
