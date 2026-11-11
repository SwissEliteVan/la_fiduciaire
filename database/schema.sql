-- Table des demandes de devis (MySQL 8 / MariaDB, InnoDB, utf8mb4).
-- Aucune donnée n'est encore collectée : ce schéma prépare TASK-006.
-- Aucun identifiant ni mot de passe ne doit figurer dans ce fichier.

CREATE TABLE IF NOT EXISTS demandes_devis (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cree_le       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nom           VARCHAR(120)    NOT NULL,
    email         VARCHAR(180)    NOT NULL,
    telephone     VARCHAR(40)         NULL,
    activite      VARCHAR(160)        NULL,
    canton        VARCHAR(60)         NULL,
    besoin        VARCHAR(60)         NULL,
    message       TEXT            NOT NULL,
    consentement  TINYINT(1)      NOT NULL DEFAULT 0,
    statut        ENUM('nouvelle', 'traitee', 'archivee') NOT NULL DEFAULT 'nouvelle',
    ip_hash       CHAR(64)            NULL,
    PRIMARY KEY (id),
    KEY idx_demandes_cree_le (cree_le),
    KEY idx_demandes_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
