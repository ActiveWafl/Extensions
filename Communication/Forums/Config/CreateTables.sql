SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `Threads`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `Threads` (
  `ThreadId` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `Title` VARCHAR(400) NOT NULL ,
  `ParentForumId` INT UNSIGNED NOT NULL ,
  `MinimumPrivilegeLevel` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `UserId` INT UNSIGNED NOT NULL ,
  `DateCreated` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `PostCount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `LastPostId` INT UNSIGNED NULL ,
  `IsSticky` TINYINT UNSIGNED NOT NULL DEFAULT 0 ,
  `IsLocked` TINYINT UNSIGNED NOT NULL DEFAULT 0 ,
  `IsApproved` TINYINT UNSIGNED NOT NULL DEFAULT 1 ,
  PRIMARY KEY (`ThreadId`) ,
  CONSTRAINT `fkThreadsParentForum`
    FOREIGN KEY (`ParentForumId` )
    REFERENCES `Forums` (`ForumId` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;

CREATE INDEX `fkThreadsParentForum` ON `Threads` (`ParentForumId` ASC) ;


-- -----------------------------------------------------
-- Table `Posts`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `Posts` (
  `PostId` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `ParentThreadId` INT UNSIGNED NOT NULL ,
  `UserId` INT UNSIGNED NOT NULL ,
  `Post` TEXT NOT NULL ,
  `PostDate` INT UNSIGNED NOT NULL ,
  `PostParsed` TEXT NOT NULL ,
  PRIMARY KEY (`PostId`) ,
  CONSTRAINT `fkPostsParentThread`
    FOREIGN KEY (`ParentThreadId` )
    REFERENCES `Threads` (`ThreadId` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;

CREATE INDEX `fkPostsParentThread` ON `Posts` (`ParentThreadId` ASC) ;


-- -----------------------------------------------------
-- Table `Categories`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `Categories` (
  `CategoryId` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `Title` VARCHAR(75) NULL ,
  `Description` TEXT NULL ,
  `DisplayOrder` INT UNSIGNED NOT NULL DEFAULT 9999 ,
  PRIMARY KEY (`CategoryId`) )
ENGINE = InnoDB;

CREATE UNIQUE INDEX `Title_UNIQUE` ON `Categories` (`Title` ASC) ;


-- -----------------------------------------------------
-- Table `Forums`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `Forums` (
  `ForumId` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `Title` VARCHAR(45) NOT NULL ,
  `Description` TEXT NOT NULL ,
  `ParentForumId` INT UNSIGNED NULL ,
  `MinimumPrivilegeLevel` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `DateCreated` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `UserId` INT UNSIGNED NULL ,
  `ThreadCount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `LastThreadId` INT UNSIGNED NULL ,
  `PostCount` INT UNSIGNED NOT NULL DEFAULT 0 ,
  `LastPostId` INT UNSIGNED NULL ,
  `ParentCategoryId` INT UNSIGNED NOT NULL ,
  `DisplayOrder` INT UNSIGNED NOT NULL DEFAULT 9999 ,
  PRIMARY KEY (`ForumId`) ,
  CONSTRAINT `fkForumsLastThread`
    FOREIGN KEY (`LastThreadId` )
    REFERENCES `Threads` (`ThreadId` )
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fkForumsLastPost`
    FOREIGN KEY (`LastPostId` )
    REFERENCES `Posts` (`PostId` )
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fkForumsParentCategory`
    FOREIGN KEY (`ParentCategoryId` )
    REFERENCES `Categories` (`CategoryId` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;

CREATE INDEX `fkForumsLastThread` ON `Forums` (`LastThreadId` ASC) ;

CREATE INDEX `fkForumsLastPost` ON `Forums` (`LastPostId` ASC) ;

CREATE INDEX `fkForumsParentCategory` ON `Forums` (`ParentCategoryId` ASC) ;


-- -----------------------------------------------------
-- Table `UserThreadViews`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `UserThreadViews` (
  `UserThreadViewId` INT NOT NULL AUTO_INCREMENT ,
  `UserId` INT UNSIGNED NULL ,
  `ThreadId` INT UNSIGNED NULL ,
  `LastPostId` INT UNSIGNED NULL ,
  PRIMARY KEY (`UserThreadViewId`) ,
  CONSTRAINT `fkUserThreadViewsThread`
    FOREIGN KEY (`ThreadId` )
    REFERENCES `Threads` (`ThreadId` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fkUserThreadViewsLastPost`
    FOREIGN KEY (`LastPostId` )
    REFERENCES `Posts` (`PostId` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fkUserThreadViewsThread` ON `UserThreadViews` (`ThreadId` ASC) ;

CREATE INDEX `fkUserThreadViewsLastPost` ON `UserThreadViews` (`LastPostId` ASC) ;


-- -----------------------------------------------------
-- Table `SearchIndex`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `SearchIndex` (
  `IndexedText` TEXT NOT NULL ,
  `ContentType` INT UNSIGNED NOT NULL ,
  `ContentId` INT UNSIGNED NOT NULL ,
  `ContentParentId` INT UNSIGNED NULL )
ENGINE = MyISAM;

CREATE FULLTEXT INDEX `ftiIndexedText` ON `SearchIndex` (`IndexedText` ASC) ;

CREATE INDEX `idxContentType` ON `SearchIndex` (`ContentType` ASC) ;


-- -----------------------------------------------------
-- Table `Smilies`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `Smilies` (
  `SmileyKey` VARCHAR(45) NOT NULL ,
  `Title` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`SmileyKey`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `PostIcons`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `PostIcons` (
  `PostIconKey` VARCHAR(45) NOT NULL ,
  `Title` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`PostIconKey`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `Settings`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `Settings` (
  `SettingName` VARCHAR(45) NOT NULL ,
  `SettingValue` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`SettingName`) )
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
