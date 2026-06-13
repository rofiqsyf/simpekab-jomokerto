# Spesifikasi Kebutuhan Sistem (SRS) - SIMPEG Kabupaten
**Proyek:** Sistem Informasi Manajemen Kepegawaian (SIMPEG) Berbasis Integrasi SIASN
**Target Output:** Arsitektur Dashboard & Alur Sinkronisasi Data

---

## 1. Dashboard Pegawai (Self-Service Dashboard)
Dashboard personal untuk seluruh ASN (PNS & PPPK) di lingkungan Pemerintah Kabupaten guna mengelola data mandiri.

### Fitur Utama yang Harus Ada:
* **Biodata & Riwayat Hidup (E-Profile):** Menampilkan data statis (NIP, Nama, Golongan, Jabatan) dan riwayat dinamis (Riwayat Pendidikan, Diklat Struktural/Teknis, Riwayat Keluarga).
* **Manajemen Absensi & Kinerja:** Integration dengan mesin absensi (GPS/Biometrik), pengajuan koreksi absen (lupa absen, tugas luar), dan pengisian Sasaran Kinerja Pegawai (SKP/E-Kinerja).
* **Pengajuan Layanan Mandiri (Layanan Kepegawaian):**
    * Pengajuan Cuti (Tahunan, Sakit, Melahirkan, Alasan Penting).
    * Pengajuan Izin Belajar / Tugas Belajar.
    * Unggah dokumen pendukung untuk Kenaikan Pangkat (KP) atau Kenaikan Gaji Berkala (KGB).
* **Keuangan & Kesejahteraan:** Fitur unduh slip gaji bulanan dan rincian Tunjangan Tambahan Penghasilan (TPP) berdasarkan capaian kinerja dan absensi.
* **E-Wallet Dokumen (Digital Locker):** Tempat menyimpan digitalisasi SK CPNS, SK PNS, SK Jabatan, dan Sertifikat Diklat yang sudah terverifikasi.

---

## 2. Dashboard Atasan Langsung (Approval Dashboard)
Dashboard kendali untuk Pejabat Struktural/Manajerial (Kasi, Kabid, Camat, Kepala Dinas) selaku penanggung jawab unit kerja.

### Fitur Utama yang Harus Ada:
* **Pusat Persetujuan (Approval Center):**
    * Validasi dan *approval/rejection* berjenjang untuk pengajuan cuti, izin, dan tugas luar staf.
    * Sistem notifikasi *real-time* untuk permohonan yang masuk tenggat waktu.
* **Pemantauan Kehadiran Tim (Team Analytics):** Rekapitulasi absensi harian, mingguan, dan bulanan bawahan langsung dalam bentuk grafik performa (tepat waktu vs terlambat).
* **Penilaian Kinerja Berjenjang (E-Kinerja Evaluator):** * Fitur reviu dan pemberian nilai terhadap capaian SKP bulanan/tahunan bawahan.
    * Pemberian catatan/umpan balik (*feedback*) kinerja staf.
* **Delegasi Tugas (Task Cascading):** Fitur untuk mendistribusikan target kinerja organisasi (dinas) menjadi tugas individu staf di bawahnya.

---

## 3. Dashboard Admin BKPSDM / Kepegawaian OPD (Operator & Verifikator)
Dashboard kerja utama untuk pengelola kepegawaian di tingkat OPD (Subbag Umum) dan pusat kabupaten (Badan Kepegawaian dan Pengembangan Sumber Daya Manusia).

### Fitur Utama yang Harus Ada:
* **Manajemen Basis Data ASN (CRUD Master Data):** Hak akses penuh untuk memperbarui data pegawai, riwayat jabatan, penempatan (mutasi internal), dan status keaktifan.
* **Verifikasi Dokumen Elektronik:** Modul verifikasi berkas yang diajukan oleh pegawai (Cuti, KP, KGB, Pensiun) sebelum diterbitkan SK atau diteruskan ke instansi pusat (BKN).
* **Manajemen Formasi & Bezetting:** * Peta Jabatan digital sesuai Analisis Jabatan (Anjab) dan Analisis Beban Kerja (ABK).
    * Deteksi dini pegawai yang memasuki Masa Persiapan Pensiun (MPP) atau Pensiun BUP (Batas Usia Pensiun).
* **Modul Pemrosesan Massal (Bulk Processing):** Pemrosesan Kenaikan Gaji Berkala (KGB) otomatis secara massal bagi pegawai yang sudah memenuhi syarat masa kerja dan golongan.

---

## 4. Dashboard Eksekutif (Executive / View-Only Dashboard)
Dashboard analitik visual untuk mengambil kebijakan tertinggi (Bupati, Wakil Bupati, Sekda).

### Fitur Utama yang Harus Ada:
* **Visualisasi Data Makro (Business Intelligence):**
    * Grafik komposisi ASN (PNS vs PPPK), sebaran per dinas/kecamatan, dan sebaran eselon/golongan.
    * Tren pensiun pegawai 5 tahun ke depan untuk perencanaan rekrutmen CASN.
* **Indikator Kinerja Utama (KPI Tracking):** Status serapan e-Kinerja kabupaten, tingkat disiplin rata-rata ASN tingkat kabupaten, dan indeks profesionalitas ASN.
* **Proyeksi Anggaran Belanja Pegawai:** Grafik kalkulasi kebutuhan anggaran belanja pegawai (Gaji + TPP) secara *real-time* berdasarkan fluktuasi jumlah pegawai aktif.
* **Executive Report Generator:** Fitur ekspor laporan ringkas (PDF/Excel) komparasi kinerja antar dinas dalam sekali klik.

---

## 5. Dashboard Super Admin (System Administrator)
Dashboard konfigurasi teknis untuk Dinas Kominfo atau tim IT pengembang.

### Fitur Utama yang Harus Ada:
* **Manajemen Hak Akses (RBAC - Role Based Access Control):** Pengaturan hak akses granular (siapa bisa melihat/mengubah apa) dan pembuatan akun admin baru di tingkat dinas.
* **Integrasi API Web Services:** Konfigurasi koneksi data ke SIASN (BKN), Satu Data Indonesia, atau aplikasi internal pemkab lainnya (seperti e-Budgeting / SIPD).
* **Audit Trail & System Logs:** Rekam jejak digital aktivitas sistem (siapa mengubah data apa, kapan, dan dari IP address mana) untuk kebutuhan forensik keamanan.
* **Backup & Recovery Automation:** Fitur untuk menjadwalkan pencadangan basis data ke server lokal maupun *cloud storage* secara berkala.

### Detail Aturan Sinkronisasi:
1.  **Pegawai ➔ Atasan:** Ketika pegawai mengajukan cuti atau input SKP di *Dashboard Pegawai*, data harus langsung memicu notifikasi dan muncul di antrean *Dashboard Atasan*.
2.  **Atasan ➔ Admin BKPSDM:** Setelah mendapat persetujuan atasan langsung, status dokumen berubah menjadi *“Approved by Supervisor”* dan otomatis masuk ke menu verifikasi *Dashboard Admin BKPSDM* tanpa perlu input ulang.
3.  **Admin BKPSDM ➔ Pegawai & Eksekutif:** Begitu Admin menyetujui dan mengunggah SK final, sistem harus otomatis:
    * Memperbarui data profil di *Dashboard Pegawai* (misal: golongan/jabatan baru).
    * Mengubah angka agregat total di grafik *Dashboard Eksekutif* secara *real-time*.
4.  **Admin BKPSDM ➔ SIASN BKN (Koneksi Eksternal):** Setiap perubahan data utama (Mutasi, Pensiun, KP) di Dashboard Admin, wajib menyediakan *trigger* API untuk sinkronisasi dua arah (*two-way sync*) dengan sistem pusat SIASN BKN agar data daerah dan pusat selalu selaras.

---

## 6. Alur Sinkronisasi Antar Dashboard (Alur Data yang Diperlukan)
Programmer wajib membangun jalur komunikasi data (*data event triggers*) antar dashboard sebagai berikut: