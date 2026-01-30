-- Tambah kolom poster_file ke tabel events
-- Jalankan query ini di phpMyAdmin atau MySQL client

ALTER TABLE events ADD COLUMN poster_file VARCHAR(255) NULL AFTER poster_url;

-- Kolom ini akan menyimpan path file yang diupload
-- Contoh nilai: /uploads/events/poster_123456.jpg
