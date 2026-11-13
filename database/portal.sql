-- Migration additive du portail. Appliquer après schema.sql.
CREATE TABLE IF NOT EXISTS lf_dossiers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom VARCHAR(160) NOT NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lf_comptes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    dossier_id BIGINT UNSIGNED NULL,
    email VARCHAR(180) NOT NULL,
    role ENUM('client', 'cabinet') NOT NULL,
    password_hash VARCHAR(255) NULL,
    activation_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    activation_expire DATETIME NULL,
    version_session BIGINT UNSIGNED NOT NULL DEFAULT 1,
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id),
    UNIQUE KEY lf_compte_email (email),
    UNIQUE KEY lf_compte_activation (activation_hash),
    FOREIGN KEY (dossier_id) REFERENCES lf_dossiers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lf_pieces (
    id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    dossier_id BIGINT UNSIGNED NOT NULL,
    compte_id BIGINT UNSIGNED NOT NULL,
    nom VARCHAR(180) NOT NULL,
    mime VARCHAR(40) NOT NULL,
    octets INT UNSIGNED NOT NULL,
    periode CHAR(7) CHARACTER SET ascii NOT NULL,
    categorie VARCHAR(40) NOT NULL,
    statut ENUM('recu', 'traite', 'a_remplacer') NOT NULL DEFAULT 'recu',
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY lf_piece_dossier_date (dossier_id, cree_le, id),
    KEY lf_piece_date (cree_le, id),
    FOREIGN KEY (dossier_id) REFERENCES lf_dossiers (id),
    FOREIGN KEY (compte_id) REFERENCES lf_comptes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lf_evenements_pieces (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    piece_id CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    compte_id BIGINT UNSIGNED NOT NULL,
    action ENUM('depot', 'telechargement', 'statut') NOT NULL,
    statut ENUM('recu', 'traite', 'a_remplacer') NULL,
    cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY lf_evt_piece_date (piece_id, cree_le),
    FOREIGN KEY (piece_id) REFERENCES lf_pieces (id),
    FOREIGN KEY (compte_id) REFERENCES lf_comptes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lf_limites (
    cle CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    fenetre BIGINT UNSIGNED NOT NULL,
    essais INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (cle, fenetre),
    KEY lf_limites_fenetre (fenetre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
