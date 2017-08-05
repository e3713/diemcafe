-- MySQL dump 10.16  Distrib 10.1.23-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: diem_cafe
-- ------------------------------------------------------
-- Server version	10.1.23-MariaDB-9+deb9u1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `CafeTable`
--

DROP TABLE IF EXISTS `CafeTable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `CafeTable` (
  `TableID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `EventID` smallint(5) unsigned NOT NULL,
  `HostUserID` int(11) NOT NULL DEFAULT '0',
  `LanguageCode` char(2) NOT NULL DEFAULT 'en',
  PRIMARY KEY (`TableID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Conversation`
--

DROP TABLE IF EXISTS `Conversation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Conversation` (
  `ConversationID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `RoundID` smallint(5) unsigned NOT NULL DEFAULT '0',
  `ZoomLink` varchar(255) NOT NULL DEFAULT '',
  `TableID` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ConversationID`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Country`
--

DROP TABLE IF EXISTS `Country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Country` (
  `CountryCode` char(2) NOT NULL DEFAULT '',
  `TranslationLanguage` char(2) NOT NULL DEFAULT 'en',
  `Val` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`CountryCode`,`TranslationLanguage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Event`
--

DROP TABLE IF EXISTS `Event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Event` (
  `EventID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `Start` datetime DEFAULT NULL,
  `Name` varchar(255) NOT NULL DEFAULT '',
  `NumTables` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `RegistrationEnd` datetime DEFAULT NULL,
  `DiscussionTime` smallint(5) unsigned NOT NULL DEFAULT '30',
  `ExtraTime` smallint(5) unsigned NOT NULL DEFAULT '10',
  PRIMARY KEY (`EventID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Language`
--

DROP TABLE IF EXISTS `Language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Language` (
  `LanguageCode` char(2) NOT NULL DEFAULT '',
  `TranslationLanguage` char(2) NOT NULL DEFAULT 'en',
  `Val` varchar(255) NOT NULL DEFAULT '',
  `Priority` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`LanguageCode`,`TranslationLanguage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Round`
--

DROP TABLE IF EXISTS `Round`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Round` (
  `RoundID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `RoundNumber` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `Question` varchar(255) NOT NULL DEFAULT '',
  `SectionID` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`RoundID`),
  KEY `EventID` (`RoundID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Section`
--

DROP TABLE IF EXISTS `Section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Section` (
  `SectionID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) NOT NULL DEFAULT '',
  `EventID` smallint(5) unsigned NOT NULL DEFAULT '0',
  `SectionNumber` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`SectionID`),
  KEY `EventID` (`EventID`,`SectionID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Thought`
--

DROP TABLE IF EXISTS `Thought`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Thought` (
  `ThoughtID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ConversationID` smallint(5) unsigned NOT NULL DEFAULT '0',
  `Val` text NOT NULL,
  `Stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ThoughtID`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `User`
--

DROP TABLE IF EXISTS `User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `User` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Email` varchar(255) NOT NULL DEFAULT '',
  `Password` varchar(255) NOT NULL DEFAULT '',
  `Name` varchar(255) NOT NULL DEFAULT '',
  `CountryCode` char(2) NOT NULL DEFAULT '',
  `DSC` varchar(255) NOT NULL DEFAULT '',
  `City` varchar(255) NOT NULL DEFAULT '',
  `Notify` tinyint(1) NOT NULL DEFAULT '0',
  `Host` tinyint(1) NOT NULL DEFAULT '0',
  `isactive` tinyint(1) NOT NULL DEFAULT '0',
  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `UserConversation`
--

DROP TABLE IF EXISTS `UserConversation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserConversation` (
  `UserID` int(11) unsigned NOT NULL DEFAULT '0',
  `ConversationID` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`,`ConversationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `UserLanguage`
--

DROP TABLE IF EXISTS `UserLanguage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UserLanguage` (
  `LanguageCode` char(2) NOT NULL DEFAULT '',
  `UserID` smallint(5) unsigned NOT NULL DEFAULT '0',
  `LevelID` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`UserID`,`LanguageCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attempts`
--

DROP TABLE IF EXISTS `attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(39) NOT NULL,
  `expiredate` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `setting` varchar(100) NOT NULL,
  `value` varchar(100) DEFAULT NULL,
  UNIQUE KEY `setting` (`setting`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `requests`
--

DROP TABLE IF EXISTS `requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `rkey` varchar(20) NOT NULL,
  `expire` datetime NOT NULL,
  `type` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `hash` varchar(40) NOT NULL,
  `expiredate` datetime NOT NULL,
  `ip` varchar(39) NOT NULL,
  `agent` varchar(200) NOT NULL,
  `cookie_crc` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-08-05 16:30:59
