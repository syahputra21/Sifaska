-- Supabase / PostgreSQL Schema for SIFASKA

-- Create Custom Enum Types
CREATE TYPE user_role AS ENUM ('dekan', 'mahasiswa', 'pengurus_fakultas');
CREATE TYPE loan_status AS ENUM ('pending', 'approved', 'rejected', 'returned', 'return_request');
CREATE TYPE item_condition AS ENUM ('baik', 'rusak');

-- Users Table
CREATE TABLE users (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role user_role DEFAULT 'mahasiswa',
    fakultas VARCHAR(100) NULL,
    prodi VARCHAR(100) NULL,
    created_at TIMESTAMPTZ DEFAULT now()
);

-- Items Table
CREATE TABLE items (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    nama_barang VARCHAR(100) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    fakultas VARCHAR(100) NULL,
    stok INTEGER NOT NULL DEFAULT 0,
    stok_rusak INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT now()
);

-- Loans Table
CREATE TABLE loans (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    user_id BIGINT NOT NULL,
    item_id BIGINT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    tujuan TEXT NOT NULL,
    status loan_status DEFAULT 'pending',
    kondisi_kembali item_condition DEFAULT 'baik',
    keluhan TEXT NULL,
    created_at TIMESTAMPTZ DEFAULT now(),
    CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);



-- Seed Data (Initial Items)
INSERT INTO items (nama_barang, kategori, stok) VALUES
('Proyektor Epson', 'Elektronik', 5),
('Speaker JBL', 'Audio', 2),
('Kabel HDMI 10m', 'Aksesoris', 10);
