CREATE TABLE IF NOT EXISTS `{$USERS_TABLE}` (
  `{$USERID_COLUMN}` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `{$USERNAME_COLUMN}` varchar(245) NOT NULL,
  PRIMARY KEY (`{$USERID_COLUMN}`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$SESSIONS_TABLE}` (
  `{$SESSIONID_COLUMN}` varchar(32) NOT NULL,
  `{$USERID_COLUMN}` int(10) unsigned,
  PRIMARY KEY (`{$SESSIONID_COLUMN}`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;