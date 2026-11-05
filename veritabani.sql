CREATE DATABASE IF NOT EXISTS mesajlasma_sistemi
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE mesajlasma_sistemi;

CREATE TABLE IF NOT EXISTS kullanicilar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    sifre VARCHAR(255) NOT NULL,
    kayit_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS mesajlar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gonderen_id INT NOT NULL,
    alici_id INT NOT NULL,
    mesaj TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    gonderim_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (gonderen_id)
        REFERENCES kullanicilar(id),

    FOREIGN KEY (alici_id)
        REFERENCES kullanicilar(id)
);
