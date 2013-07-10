
-- -----------------------------------------------------
-- Table `file`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `file` (
	`id` INTEGER PRIMARY KEY ASC,
	`filename` TEXT,
	`doc_id` TEXT,
	`location` TEXT,
	`num_pages` INTEGER
);

-- -----------------------------------------------------
-- Table `file`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `page` (
		`id` INTEGER PRIMARY KEY ASC,
		`file_id` INTEGER NOT NULL,
		`page` INTEGER NOT NULL,
		`include` BOOLEAN NOT NULL DEFAULT TRUE,
		`is_glossary` INT NOT NULL DEFAULT 2, -- 0 for false, 1 for true, 2 for unknown
		CONSTRAINT `fk_page_file`
			FOREIGN KEY (`file_id`)
			REFERENCES `file` (`id`)
			ON DELETE CASCADE
			ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Table `filename`
-- -----------------------------------------------------
--CREATE  TABLE IF NOT EXISTS `filename` (
--	`id` INTEGER PRIMARY KEY ASC AUTOINCREMENT ,
--	`file_id` INT UNSIGNED NOT NULL ,
--	`name` TEXT NOT NULL ,
--	CONSTRAINT `fk_filename_file`
--		FOREIGN KEY (`file_id` )
--		REFERENCES `file` (`id` )
--		ON DELETE CASCADE
--		ON UPDATE CASCADE
--);


-- -----------------------------------------------------
-- Table `match_pattern`
-- -----------------------------------------------------
--CREATE  TABLE IF NOT EXISTS `match_pattern` (
--	`id` INTEGER PRIMARY KEY ASC AUTOINCREMENT ,
--	`pattern` TEXT NOT NULL UNIQUE
--);


-- -----------------------------------------------------
-- Table `metadata`
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `metadata` (
	`id` INTEGER PRIMARY KEY ASC AUTOINCREMENT ,
	`file_id` INT UNSIGNED NOT NULL ,
	`name` TEXT NOT NULL ,
	`value` TEXT NOT NULL ,
	CONSTRAINT `fk_filename_file`
		FOREIGN KEY (`file_id` )
		REFERENCES `file` (`id` )
		ON DELETE CASCADE
		ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Table `cross_reference`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `cross_reference` (
	`id` INTEGER PRIMARY KEY ASC AUTOINCREMENT ,
	`source_file_id` INT NOT NULL ,
	`page_number` INT NOT NULL ,
-- 	`page_is_glossary` INT , -- 0=false, 1=true, 2=page has the word "glossary" but reference may not be in glossary
	`referenced_file_id` TEXT ,
-- 	`match_pattern_id` INT UNSIGNED NOT NULL ,
	`matched_text` TEXT NOT NULL ,
	`matched_offset` INT NOT NULL ,
	`context` TEXT NOT NULL ,
	`reference_type` INT ,
	CONSTRAINT `fk_cross_reference_source_file`
		FOREIGN KEY (`source_file_id` )
		REFERENCES `file` (`id` )
		ON DELETE CASCADE
		ON UPDATE CASCADE ,
	CONSTRAINT `fk_cross_reference_referenced_file`
		FOREIGN KEY (`referenced_file_id` )
		REFERENCES `file` (`id` )
		ON DELETE CASCADE
-- 		ON UPDATE CASCADE ,
--  CONSTRAINT `fk_cross_reference_match_pattern1`
-- 		FOREIGN KEY (`match_pattern_id` )
-- 		REFERENCES `match_pattern` (`id` )
-- 		ON DELETE CASCADE
		ON UPDATE CASCADE
);

CREATE TRIGGER IF NOT EXISTS delete_dependencies_of_file BEFORE DELETE ON file
	FOR EACH ROW BEGIN
-- 		DELETE FROM filename WHERE OLD.id = filename.file_id;
		DELETE FROM metadata WHERE OLD.id = metadata.file_id;
		DELETE FROM cross_reference WHERE OLD.id = cross_reference.source_file_id OR OLD.id = cross_reference.referenced_file_id;
		DELETE FROM page WHERE OLD.id = page.file_id;
	END;

-- CREATE TRIGGER IF NOT EXISTS delete_dependencies_of_match_pattern BEFORE DELETE ON match_pattern
-- 	FOR EACH ROW BEGIN
-- 		DELETE FROM cross_reference WHERE OLD.id = cross_reference.match_pattern_id;
-- 	END;

-- -----------------------------------------------------
-- Sample patterns
-- -----------------------------------------------------

--INSERT OR IGNORE INTO `match_pattern` (`pattern`) VALUES ('/\bFIPS\s+\d{3}\b/s');
--INSERT OR IGNORE INTO `match_pattern` (`pattern`) VALUES ('/\bNIST\s+SP\s+\d{3}(\-\d*)?\b/s');
--INSERT OR IGNORE INTO `match_pattern` (`pattern`) VALUES ('/\bNIST\s+IR\s+\d{4}\b/s');
--INSERT OR IGNORE INTO `match_pattern` (`pattern`) VALUES ('/\b((NIST|FIPS)\s+(SP)?|SP)[^\d]{0,50}\d{3,4}(\-\d+)?([^\d]{1,10}\d+)?/s');

-- -----------------------------------------------------
-- Table `config`
-- -----------------------------------------------------
-- CREATE  TABLE IF NOT EXISTS `config` (
-- );


