USE simpekabjmk;

-- ============================================================
-- SEED DATA: pengajuan_layanan
-- ============================================================
INSERT INTO pengajuan_layanan (user_id, jenis, tanggal_mulai, tanggal_selesai, keterangan, status) VALUES
(6, 'Cuti Tahunan', DATE_FORMAT(CURDATE() + INTERVAL 5 DAY, '%Y-%m-%d'), DATE_FORMAT(CURDATE() + INTERVAL 7 DAY, '%Y-%m-%d'), 'Cuti tahunan untuk acara keluarga', 'pending_atasan'),
(7, 'Cuti Sakit', DATE_FORMAT(CURDATE() - INTERVAL 1 DAY, '%Y-%m-%d'), DATE_FORMAT(CURDATE() + INTERVAL 1 DAY, '%Y-%m-%d'), 'Demam berdarah, surat dokter menyusul', 'approved_atasan'),
(8, 'Izin Belajar', DATE_FORMAT(CURDATE() + INTERVAL 14 DAY, '%Y-%m-%d'), DATE_FORMAT(CURDATE() + INTERVAL 14 DAY, '%Y-%m-%d'), 'Ujian tengah semester', 'approved_bkpsdm'),
(9, 'Cuti Melahirkan', DATE_FORMAT(CURDATE() + INTERVAL 30 DAY, '%Y-%m-%d'), DATE_FORMAT(CURDATE() + INTERVAL 120 DAY, '%Y-%m-%d'), 'Persiapan kelahiran anak pertama', 'pending_atasan');

-- ============================================================
-- SEED DATA: kinerja_skp
-- ============================================================
INSERT INTO kinerja_skp (user_id, periode, nilai, catatan_atasan, status) VALUES
(6, '2024-Q1', 85, 'Kinerja baik, tingkatkan inisiatif', 'reviewed'),
(6, '2024-Q2', NULL, NULL, 'draft'),
(7, '2024-Q1', 90, 'Sangat memuaskan, pertahankan', 'reviewed'),
(8, '2024-Q1', 78, 'Perlu perbaikan dalam ketepatan waktu laporan', 'reviewed'),
(9, '2024-Q1', NULL, NULL, 'submitted'),
(10, '2024-Q1', NULL, NULL, 'submitted');
