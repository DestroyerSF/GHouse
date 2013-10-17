# ************************************************************
# Sequel Pro SQL dump
# Version 3408
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: 127.0.0.1 (MySQL 5.1.68)
# Database: ghouse
# Generation Time: 2013-04-09 01:14:04 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table artist
# ------------------------------------------------------------

DROP TABLE IF EXISTS `artist`;

CREATE TABLE `artist` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `slug` varchar(120) NOT NULL DEFAULT '',
  `bio` text NOT NULL,
  `twitter_card_description` varchar(200) DEFAULT NULL,
  `picture_ext` varchar(4) DEFAULT NULL COMMENT 'The file extension of the current artist picture.',
  `twitter_handle` varchar(120) DEFAULT NULL COMMENT 'Used for twitter cards.',
  `location` varchar(120) NOT NULL DEFAULT '',
  `songkick_artist_id` varchar(120) DEFAULT NULL,
  `smart_url` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `artist` WRITE;
/*!40000 ALTER TABLE `artist` DISABLE KEYS */;

INSERT INTO `artist` (`id`, `name`, `slug`, `bio`, `twitter_card_description`, `picture_ext`, `twitter_handle`, `location`, `songkick_artist_id`, `smart_url`)
VALUES
	(1,'M|O|D','m-o-d','The members of the crew include Arnold, REWROTE, LiL TExAS, C.Z., and Yung Satan, all of whom have their own distinct sound and yet fluently mesh perfectly with one another. It\'s apparent that they are the best of friends and yet each keep a competitive edge to improve and one-up the others.','M|O|D on GHouse.','','MODMUSICDOTCO','Boston, MA','6300544',NULL),
	(2,'Lil Texas','lil-texas','','Lil Texas on GHouse.','jpg','LILTEXAS','Dallas, TX','6565183',NULL);

/*!40000 ALTER TABLE `artist` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table artist_link
# ------------------------------------------------------------

DROP TABLE IF EXISTS `artist_link`;

CREATE TABLE `artist_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `artist_id` int(11) NOT NULL,
  `url` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `artist_link` WRITE;
/*!40000 ALTER TABLE `artist_link` DISABLE KEYS */;

INSERT INTO `artist_link` (`id`, `artist_id`, `url`)
VALUES
	(1,1,'http://modmusic.co');

/*!40000 ALTER TABLE `artist_link` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table label
# ------------------------------------------------------------

DROP TABLE IF EXISTS `label`;

CREATE TABLE `label` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `slug` varchar(45) NOT NULL DEFAULT '',
  `stack_order` int(11) NOT NULL,
  `picture_ext` varchar(4) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `label` WRITE;
/*!40000 ALTER TABLE `label` DISABLE KEYS */;

INSERT INTO `label` (`id`, `name`, `slug`, `stack_order`, `picture_ext`)
VALUES
	(1,'M|O|D','m-o-d',5,'jpg'),
	(2,'JASS','jass',4,'jpg'),
	(3,'GHouse','ghouse',3,'png'),
	(4,'banana peel','banana-peel',2,'gif'),
	(5,'Acrobat Sabbatical','acrobat-sabbatical',1,'jpg');

/*!40000 ALTER TABLE `label` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table release
# ------------------------------------------------------------

DROP TABLE IF EXISTS `release`;

CREATE TABLE `release` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `artist_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `slug` varchar(120) NOT NULL DEFAULT '',
  `spotify_uri` text NOT NULL,
  `itunes_url` text NOT NULL,
  `release_date` date NOT NULL COMMENT 'Doesn''t have to be exact…for indexing/ordering purposes only.',
  `picture_ext` varchar(4) NOT NULL DEFAULT '',
  `featured_release` int(1) NOT NULL DEFAULT '0' COMMENT 'If 1, used as the first release on the artist page and as the artist''s default picture if no other is specified.',
  `smart_url` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `release` WRITE;
/*!40000 ALTER TABLE `release` DISABLE KEYS */;

INSERT INTO `release` (`id`, `artist_id`, `label_id`, `name`, `slug`, `spotify_uri`, `itunes_url`, `release_date`, `picture_ext`, `featured_release`, `smart_url`)
VALUES
	(1,1,1,'PENG - 003','peng-003','spotify:album:0NJTk56CRYeEXryP2V1nLp','https://itunes.apple.com/us/album/peng-003-ep/id597092223','2013-01-15','jpg',0,'http://snd.it/d87drw8gh'),
	(2,1,1,'PENG - 002','peng-002','','https://itunes.apple.com/us/album/peng-002-ep/id584998918','2012-12-04','jpg',0,NULL),
	(3,1,1,'PENG - 001','peng-001','spotify:album:51Z33M7P05zJDCFlXKU4l1','https://itunes.apple.com/us/album/peng-001-ep/id583403033','2012-11-20','jpg',0,NULL),
	(4,1,1,'M​|​O|​D IV','m-o-d-iv','spotify:album:2y3IYBd2JnUEGSJp0Ua1qE','','2012-09-25','jpg',0,NULL),
	(5,1,1,'DOTWAV','dotwav','spotify:album:7A241ykpddvMRDd423owph','','2012-09-03','jpg',0,NULL),
	(6,2,1,'Xan Anthem','xan-anthem','spotify:artist:4xxMIXoA1BLPYoL7mPTSwU','https://itunes.apple.com/us/album/xan-anthem-single/id608968898','2012-09-03','jpg',0,NULL);

/*!40000 ALTER TABLE `release` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table spotlight_feature
# ------------------------------------------------------------

DROP TABLE IF EXISTS `spotlight_feature`;

CREATE TABLE `spotlight_feature` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` text,
  `url` text,
  `img_file` text,
  `stack_order` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

LOCK TABLES `spotlight_feature` WRITE;
/*!40000 ALTER TABLE `spotlight_feature` DISABLE KEYS */;

INSERT INTO `spotlight_feature` (`id`, `title`, `url`, `img_file`, `stack_order`)
VALUES
	(1,' MJTIII &mdash; 4U&U&U',NULL,'4U-U-U.jpg',1),
	(2,'B. Lewis &mdash; A Lion\'s Aperture',NULL,'a-lion-s-aperture.jpg',0),
	(3,'Sir Froderick &mdash; Decisions',NULL,'decisions.jpg',2),
	(4,'Yung Satan &mdash; Maserati',NULL,'maserati.jpg',3),
	(5,'Grass Is Green &mdash; Yeddo','','yeddo.jpg',4);

/*!40000 ALTER TABLE `spotlight_feature` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
