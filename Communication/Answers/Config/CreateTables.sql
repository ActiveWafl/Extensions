CREATE TABLE if not exists `{$PREFIX}Questions` (
  `QuestionId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Question` text NOT NULL,
  `Details` text NOT NULL,
  `DateAsked` int(10) unsigned NOT NULL,
  `UserId` int(10) unsigned NOT NULL,
  `Tags` text,
  `DateModerated` int(10) unsigned DEFAULT NULL,
  `IsApproved` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`QuestionId`),
  FULLTEXT KEY `fti_question` (`Question`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;

CREATE TABLE if not exists `{$PREFIX}Answers` (
  `AnswerId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `QuestionId` int(10) unsigned NOT NULL,
  `UserId` int(10) unsigned NOT NULL,
  `Answer` text NOT NULL,
  `DateAnswered` int(10) unsigned NOT NULL,
  `AnswerAccepted` tinyint(1) unsigned NOT NULL,
  `UpVotes` int(10) unsigned NOT NULL,
  `DownVotes` int(10) unsigned NOT NULL,
  PRIMARY KEY (`AnswerId`),
  KEY `fkAnswers_User_idx` (`UserId`),
  KEY `fkAnswers_Question_idx` (`QuestionId`),
  CONSTRAINT `fkAnswers_User` FOREIGN KEY (`UserId`) REFERENCES `Users` (`UserId`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

CREATE TABLE if not exists `{$PREFIX}AnswerComments` (
  `AnswerCommentId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UserId` int(10) unsigned NOT NULL,
  `AnswerId` int(10) unsigned NOT NULL,
  `CommentDate` int(10) unsigned NOT NULL,
  `Comment` text NOT NULL,
  PRIMARY KEY (`AnswerCommentId`),
  KEY `fkAnswerComments_Answer_idx` (`AnswerId`),
  KEY `fkAnswerComments_User_idx` (`UserId`),
  CONSTRAINT `fkAnswerComments_Answer` FOREIGN KEY (`AnswerId`) REFERENCES `{$PREFIX}Answers` (`AnswerId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fkAnswerComments_User` FOREIGN KEY (`UserId`) REFERENCES `Users` (`UserId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE if not exists `{$PREFIX}Categories` (
  `CategoryId` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '',
  `Title` VARCHAR(250) NOT NULL COMMENT '',
  PRIMARY KEY (`CategoryId`)  COMMENT '');

INSERT INTO `{$PREFIX}Categories` (`CategoryId`, `Title`) VALUES ('999', 'General Questions');

ALTER TABLE `{$PREFIX}Questions`
ADD COLUMN `CategoryId` INT UNSIGNED NOT NULL DEFAULT 999 COMMENT '' AFTER `IsApproved`;
