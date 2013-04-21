
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
-- Table `cross_reference`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `cross_reference` (
  `source_file_id` INT ,
  `referenced_file_id` INT ,
  `match_pattern_id` INT UNSIGNED NOT NULL ,
  `num_references` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`source_file_id`, `referenced_file_id`) ,
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


-- -----------------------------------------------------
-- Table `config`
-- -----------------------------------------------------
-- CREATE  TABLE IF NOT EXISTS `config` (
-- );


