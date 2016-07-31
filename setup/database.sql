-- MySQL dump 10.13  Distrib 5.5.46, for debian-linux-gnu (i686)
--
-- Host: localhost    Database: yaam
-- ------------------------------------------------------
-- Server version	5.5.46-0+deb7u1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `call_treatment`
--

DROP TABLE IF EXISTS `call_treatment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `call_treatment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(80) NOT NULL,
  `action` varchar(80) NOT NULL,
  `extension` varchar(80) NOT NULL,
  `description` varchar(80) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_treatment`
--

LOCK TABLES `call_treatment` WRITE;
/*!40000 ALTER TABLE `call_treatment` DISABLE KEYS */;
/*!40000 ALTER TABLE `call_treatment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `cdr`
--

DROP TABLE IF EXISTS `cdr`;
/*!50001 DROP VIEW IF EXISTS `cdr`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `cdr` (
  `id` tinyint NOT NULL,
  `calldate` tinyint NOT NULL,
  `type` tinyint NOT NULL,
  `clid` tinyint NOT NULL,
  `src` tinyint NOT NULL,
  `dst` tinyint NOT NULL,
  `dcontext` tinyint NOT NULL,
  `channel` tinyint NOT NULL,
  `dstchannel` tinyint NOT NULL,
  `lastapp` tinyint NOT NULL,
  `lastdata` tinyint NOT NULL,
  `duration` tinyint NOT NULL,
  `billsec` tinyint NOT NULL,
  `disposition` tinyint NOT NULL,
  `amaflags` tinyint NOT NULL,
  `accountcode` tinyint NOT NULL,
  `route` tinyint NOT NULL,
  `userfield` tinyint NOT NULL,
  `centrocusto` tinyint NOT NULL,
  `cost` tinyint NOT NULL,
  `uniqueid` tinyint NOT NULL
) ENGINE=MyISAM;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `cdr_routes`
--

DROP TABLE IF EXISTS `cdr_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cdr_routes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priority` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `channel` varchar(80) NOT NULL,
  `dcontext` varchar(80) NOT NULL,
  `dst` varchar(80) NOT NULL,
  `src` varchar(80) NOT NULL,
  `dstchannel` varchar(80) NOT NULL,
  `type` varchar(20) NOT NULL,
  `cost` float NOT NULL,
  `min` int(11) NOT NULL,
  `increment` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cdr_routes`
--

LOCK TABLES `cdr_routes` WRITE;
/*!40000 ALTER TABLE `cdr_routes` DISABLE KEYS */;
/*!40000 ALTER TABLE `cdr_routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cnam`
--

DROP TABLE IF EXISTS `cnam`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cnam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(32) DEFAULT NULL,
  `fullname` varchar(120) DEFAULT NULL,
  `cidname` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cnam`
--

LOCK TABLES `cnam` WRITE;
/*!40000 ALTER TABLE `cnam` DISABLE KEYS */;
/*!40000 ALTER TABLE `cnam` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `group` varchar(40) NOT NULL,
  `fullname` varchar(80) NOT NULL,
  PRIMARY KEY (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_config`
--

DROP TABLE IF EXISTS `user_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(40) NOT NULL,
  `keyname` varchar(30) NOT NULL,
  `value` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_config`
--

LOCK TABLES `user_config` WRITE;
/*!40000 ALTER TABLE `user_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user` varchar(40) NOT NULL,
  `fullname` varchar(80) NOT NULL,
  `extension` varchar(80) NOT NULL,
  `pwhash` char(64) NOT NULL,
  `pgroups` varchar(128) NOT NULL DEFAULT 'user',
  `ui_theme` varchar(40) NOT NULL DEFAULT 'default',
  `user_chan` varchar(80) NOT NULL,
  `vbox` varchar(80) NOT NULL,
  PRIMARY KEY (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-07-26 17:47:31
