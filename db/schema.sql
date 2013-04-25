
-- -----------------------------------------------------
-- Table `file`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `file` (
	`id` INTEGER PRIMARY KEY ASC,
	`name` TEXT,
	`location` TEXT
);


-- -----------------------------------------------------
-- Table `filename`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `filename` (
	`id` INTEGER PRIMARY KEY ASC AUTOINCREMENT ,
	`file_id` INT UNSIGNED NOT NULL ,
	`name` TEXT NOT NULL ,
	CONSTRAINT `fk_filename_file`
		FOREIGN KEY (`file_id` )
		REFERENCES `file` (`id` )
		ON DELETE CASCADE
		ON UPDATE CASCADE
);


-- -----------------------------------------------------
-- Table `match_pattern`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `match_pattern` (
	`id` INTEGER PRIMARY KEY ASC AUTOINCREMENT ,
	`pattern` TEXT NOT NULL UNIQUE
);


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
	`referenced_file_id` INT ,
	`match_pattern_id` INT UNSIGNED NOT NULL ,
	`matched_text` TEXT NOT NULL ,
	`context` TEXT NOT NULL ,
	CONSTRAINT `fk_cross_reference_source_file`
		FOREIGN KEY (`source_file_id` )
		REFERENCES `file` (`id` )
		ON DELETE CASCADE
		ON UPDATE CASCADE ,
	CONSTRAINT `fk_cross_reference_referenced_file`
		FOREIGN KEY (`referenced_file_id` )
		REFERENCES `file` (`id` )
		ON DELETE CASCADE
		ON UPDATE CASCADE ,
 CONSTRAINT `fk_cross_reference_match_pattern1`
		FOREIGN KEY (`match_pattern_id` )
		REFERENCES `match_pattern` (`id` )
		ON DELETE CASCADE
		ON UPDATE CASCADE
);

CREATE TRIGGER IF NOT EXISTS delete_dependencies_of_file BEFORE DELETE ON file
	FOR EACH ROW BEGIN
		DELETE FROM filename WHERE OLD.id = filename.file_id;
		DELETE FROM metadata WHERE OLD.id = metadata.file_id;
		DELETE FROM cross_reference WHERE OLD.id = cross_reference.source_file_id OR OLD.id = cross_reference.referenced_file_id;
	END;

CREATE TRIGGER IF NOT EXISTS delete_dependencies_of_match_pattern BEFORE DELETE ON match_pattern
	FOR EACH ROW BEGIN
		DELETE FROM cross_reference WHERE OLD.id = cross_reference.match_pattern_id;
	END;

-- -----------------------------------------------------
-- Table `config`
-- -----------------------------------------------------
-- CREATE  TABLE IF NOT EXISTS `config` (
-- );


