-- ============================================================
--  Bibliothèque — Script MySQL
--  Basé sur le diagramme de classe (Utilisateur, Membre,
--  Livre, Emprunt, Amende)
-- ============================================================

CREATE DATABASE IF NOT EXISTS bibliotheque
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bibliotheque;

-- ============================================================
-- TABLE : membres  (Utilisateur + Membre fusionnés)
-- ============================================================
CREATE TABLE membres (
    idMembre        INT           NOT NULL AUTO_INCREMENT,
    nom             VARCHAR(100)  NOT NULL,
    email           VARCHAR(150)  NOT NULL UNIQUE,
    motDePasse      VARCHAR(255)  NOT NULL,
    telephone       VARCHAR(20),
    adresse         VARCHAR(255),
    dateInscription DATE          NOT NULL DEFAULT (CURRENT_DATE),
    PRIMARY KEY (idMembre)
);

-- ============================================================
-- TABLE : livres
-- ============================================================
CREATE TABLE livres (
    idLivre     INT           NOT NULL AUTO_INCREMENT,
    titre       VARCHAR(200)  NOT NULL,
    auteur      VARCHAR(150)  NOT NULL,
    categorie   VARCHAR(100),
    quantite    INT           NOT NULL DEFAULT 1 CHECK (quantite >= 0),
    disponible  BOOLEAN       NOT NULL DEFAULT TRUE,
    PRIMARY KEY (idLivre)
);

-- ============================================================
-- TABLE : emprunts
-- ============================================================
CREATE TABLE emprunts (
    idEmprunt           INT  NOT NULL AUTO_INCREMENT,
    idMembre            INT  NOT NULL,
    idLivre             INT  NOT NULL,
    dateEmprunt         DATE NOT NULL DEFAULT (CURRENT_DATE),
    dateRetourPrevue    DATE NOT NULL,
    dateRetourReelle    DATE DEFAULT NULL,
    statut              ENUM('en_cours','retourne','en_retard')
                             NOT NULL DEFAULT 'en_cours',
    PRIMARY KEY (idEmprunt),
    CONSTRAINT fk_emprunt_membre
        FOREIGN KEY (idMembre) REFERENCES membres(idMembre)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_emprunt_livre
        FOREIGN KEY (idLivre)  REFERENCES livres(idLivre)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ============================================================
-- TABLE : amendes
-- ============================================================
CREATE TABLE amendes (
    idAmende          INT            NOT NULL AUTO_INCREMENT,
    idEmprunt         INT            NOT NULL UNIQUE,
    montant           DECIMAL(8,2)   NOT NULL CHECK (montant >= 0),
    nombreJoursRetard INT            NOT NULL CHECK (nombreJoursRetard > 0),
    statut            ENUM('impayee','payee') NOT NULL DEFAULT 'impayee',
    datePaiement      DATE           DEFAULT NULL,
    PRIMARY KEY (idAmende),
    CONSTRAINT fk_amende_emprunt
        FOREIGN KEY (idEmprunt) REFERENCES emprunts(idEmprunt)
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- ============================================================
-- INDEX supplémentaires
-- ============================================================
CREATE INDEX idx_emprunts_membre  ON emprunts (idMembre);
CREATE INDEX idx_emprunts_livre   ON emprunts (idLivre);
CREATE INDEX idx_emprunts_statut  ON emprunts (statut);
CREATE INDEX idx_livres_titre     ON livres   (titre);
CREATE INDEX idx_livres_auteur    ON livres   (auteur);
CREATE INDEX idx_amendes_statut   ON amendes  (statut);

-- ============================================================
-- TRIGGERS
-- ============================================================
DELIMITER $$

CREATE TRIGGER trg_after_emprunt_insert
AFTER INSERT ON emprunts
FOR EACH ROW
BEGIN
    UPDATE livres
    SET disponible = (
        quantite > (SELECT COUNT(*) FROM emprunts
                    WHERE idLivre = NEW.idLivre AND statut = 'en_cours')
    )
    WHERE idLivre = NEW.idLivre;
END$$

CREATE TRIGGER trg_after_emprunt_update
AFTER UPDATE ON emprunts
FOR EACH ROW
BEGIN
    IF NEW.statut IN ('retourne', 'en_retard') THEN
        UPDATE livres
        SET disponible = (
            quantite > (SELECT COUNT(*) FROM emprunts
                        WHERE idLivre = NEW.idLivre AND statut = 'en_cours')
        )
        WHERE idLivre = NEW.idLivre;
    END IF;
END$$

-- ============================================================
-- PROCÉDURES STOCKÉES
-- ============================================================
CREATE PROCEDURE calculerAmende(
    IN p_idEmprunt       INT,
    IN p_tarifJournalier DECIMAL(8,2)
)
BEGIN
    DECLARE v_jours   INT;
    DECLARE v_montant DECIMAL(8,2);
    SELECT DATEDIFF(COALESCE(dateRetourReelle, CURRENT_DATE), dateRetourPrevue)
    INTO v_jours FROM emprunts WHERE idEmprunt = p_idEmprunt;
    IF v_jours > 0 THEN
        SET v_montant = v_jours * p_tarifJournalier;
        INSERT INTO amendes (idEmprunt, montant, nombreJoursRetard)
        VALUES (p_idEmprunt, v_montant, v_jours)
        ON DUPLICATE KEY UPDATE montant = v_montant, nombreJoursRetard = v_jours;
    END IF;
END$$

CREATE PROCEDURE verifierRetards()
BEGIN
    UPDATE emprunts SET statut = 'en_retard'
    WHERE statut = 'en_cours' AND dateRetourPrevue < CURRENT_DATE;
END$$

CREATE PROCEDURE retournerLivre(IN p_idEmprunt INT)
BEGIN
    UPDATE emprunts
    SET dateRetourReelle = CURRENT_DATE, statut = 'retourne'
    WHERE idEmprunt = p_idEmprunt AND statut = 'en_cours';
END$$

DELIMITER ;

-- ============================================================
-- DONNÉES DE TEST
-- ============================================================
INSERT INTO membres (nom, email, motDePasse, telephone, adresse, dateInscription) VALUES
('Alice Dupont',  'alice@example.com', SHA2('pass1',256), '0612345678', '12 rue de la Paix, Paris',    '2024-01-10'),
('Bob Martin',    'bob@example.com',   SHA2('pass2',256), '0698765432', '5 avenue Victor Hugo, Lyon',   '2024-03-22'),
('Chloé Bernard', 'chloe@example.com', SHA2('pass3',256), '0754321876', '8 boulevard des Lilas, Lille', '2025-06-01');

INSERT INTO livres (titre, auteur, categorie, quantite, disponible) VALUES
('Le Petit Prince',  'Antoine de Saint-Exupéry', 'Roman',           3, TRUE),
('L''Étranger',      'Albert Camus',              'Roman',           2, TRUE),
('Clean Code',       'Robert C. Martin',           'Informatique',    1, TRUE),
('Dune',             'Frank Herbert',              'Science-fiction', 2, TRUE),
('Harry Potter T1',  'J.K. Rowling',               'Jeunesse',        4, TRUE);

INSERT INTO emprunts (idMembre, idLivre, dateEmprunt, dateRetourPrevue, dateRetourReelle, statut) VALUES
(1, 1, '2025-04-01', '2025-04-15', '2025-04-14', 'retourne'),
(2, 3, '2025-04-10', '2025-04-24', NULL,          'en_retard'),
(3, 4, '2025-05-01', '2025-05-15', NULL,          'en_cours');

INSERT INTO amendes (idEmprunt, montant, nombreJoursRetard, statut) VALUES
(2, 21.00, 14, 'impayee');