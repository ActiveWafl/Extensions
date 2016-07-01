CREATE TABLE IF NOT EXISTS `{$PREFIX}UserGroups` (
  `UserGroupId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(145) NOT NULL,
  `PrivilegeLevel` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserGroupId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$PREFIX}Users` (
  `UserId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UserGroupId` int(10) unsigned NOT NULL,
  `EmailAddress` varchar(245) NOT NULL,
  `PasswordHash` varchar(32) NOT NULL,
  `LastLogin` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserId`),
  KEY `fk{$PREFIX}Users_UserGroup` (`UserGroupId`),
  CONSTRAINT `fk{$PREFIX}Users_UserGroup` FOREIGN KEY (`UserGroupId`) REFERENCES `{$PREFIX}UserGroups` (`UserGroupId`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$PREFIX}Sessions` (
  `SessionId` varchar(32) NOT NULL,
  `UserId` int(10) unsigned,
  `StartDate` int(10) unsigned NOT NULL,
  `LastActivityDate` int(10) unsigned NOT NULL,
  PRIMARY KEY (`SessionId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;