USE simpekabjmk;

-- Tambah kolom-kolom baru
ALTER TABLE kinerja_skp
  ADD COLUMN bulan VARCHAR(7) NULL AFTER user_id,
  ADD COLUMN kegiatan TEXT NULL AFTER bulan,
  ADD COLUMN target INT NULL AFTER kegiatan,
  ADD COLUMN realisasi INT NULL AFTER target,
  ADD COLUMN capaian INT NULL AFTER realisasi,
  ADD COLUMN nilai_atasan INT NULL AFTER capaian;

-- Perbarui data lama agar selaras jika ada
UPDATE kinerja_skp SET bulan = '2024-01' WHERE bulan IS NULL;
