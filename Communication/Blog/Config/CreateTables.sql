CREATE TABLE `{$PREFIX}BlogCategories` (
  `BlogCategoryId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Title` varchar(200) NOT NULL,
  PRIMARY KEY (`BlogCategoryId`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `{$PREFIX}BlogPosts` (
  `BlogPostId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PostDate` int(10) unsigned NOT NULL,
  `Title` varchar(1000) DEFAULT NULL,
  `UrlTitle` varchar(1000) DEFAULT NULL,
  `Contents` text,
  `UserId` int(10) unsigned NOT NULL,
  `BlogCategoryId` int(10) unsigned NOT NULL,
  `IsPublished` int(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`BlogPostId`),
  KEY `fkBlogPosts_Users_idx` (`UserId`),
  CONSTRAINT `fkBlogPosts_Users` FOREIGN KEY (`UserId`) REFERENCES `Users` (`UserId`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

CREATE TABLE `{$PREFIX}BlogPostTags` (
  `BlogPostTagId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `TagId` int(10) unsigned NOT NULL,
  `BlogPostId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`BlogPostTagId`),
  KEY `fkBlogPostTag_Tag_idx` (`TagId`),
  KEY `fkBlogPostTag_Post_idx` (`BlogPostId`),
  CONSTRAINT `fkBlogPostTag_Post` FOREIGN KEY (`BlogPostId`) REFERENCES `BlogPosts` (`BlogPostId`) ON UPDATE CASCADE,
  CONSTRAINT `fkBlogPostTag_Tag` FOREIGN KEY (`TagId`) REFERENCES `Tags` (`TagId`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;
