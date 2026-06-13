USE simpekabjmk;

ALTER TABLE pegawai 
ADD COLUMN golongan VARCHAR(20) NULL AFTER posisi,
ADD COLUMN pendidikan VARCHAR(50) NULL AFTER golongan,
ADD COLUMN jenis_asn ENUM('PNS', 'PPPK', 'Non-ASN') NOT NULL DEFAULT 'PNS' AFTER pendidikan,
ADD COLUMN nik VARCHAR(20) NULL AFTER nip,
ADD COLUMN npwp VARCHAR(30) NULL AFTER nik;

-- Update existing data with defaults
UPDATE pegawai SET golongan = 'III/a', pendidikan = 'S1', jenis_asn = 'PNS' WHERE id > 0;
