-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 07, 2011 at 03:58 PM
-- Server version: 5.5.8
-- PHP Version: 5.3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `addresshunter`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE IF NOT EXISTS `address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country` varchar(2) NOT NULL COMMENT 'Osm country code of the address.',
  `city` varchar(255) NOT NULL COMMENT 'Osm city of the address.',
  `postcode` varchar(255) DEFAULT NULL COMMENT 'Osm postcode of the address.',
  `street` varchar(255) NOT NULL COMMENT 'Osm street name of the address.',
  `housenumber` varchar(10) NOT NULL COMMENT 'Housenumber of the address.',
  `address_hash` varchar(32) NOT NULL COMMENT 'Unique md5 hash of ''housenumber street postcode city countrycode'' used to avoid duplicate entries ',
  `approx_x` double DEFAULT NULL COMMENT 'Approx coordinates as returned by Osm geocoder',
  `approx_y` double DEFAULT NULL COMMENT 'Approx coordinates as returned by Osm geocoder',
  `is_available` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Availability of the address for new games: 1=available, 0=not available (already found or currently in use in an active game)',
  `full` text COMMENT 'Full address line returned by Osm geocoder on last search and validation',
  PRIMARY KEY (`id`),
  UNIQUE KEY `address_hash` (`address_hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `address_import`
--

CREATE TABLE IF NOT EXISTS `address_import` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `addressline` varchar(255) NOT NULL COMMENT 'The addressline from user input',
  `housenumber` varchar(10) DEFAULT NULL COMMENT 'The housenumber from user input',
  `country_code` varchar(2) DEFAULT NULL COMMENT 'Country code of the address (for statistics)',
  `frequency` int(11) NOT NULL DEFAULT '1' COMMENT 'How many times was the same address searched',
  `status_osm1` tinyint(1) DEFAULT NULL COMMENT 'Status of the first OSM geocoding: -1=error, 0=not found, 1=found with housenr, 2=found street only',
  `status_google` tinyint(1) DEFAULT NULL COMMENT 'Status of Google geocoding: -1=error, 0=not found, 1=found with housenr, 2=found street only, 3=found different houseno',
  `status_osm2` tinyint(4) DEFAULT NULL COMMENT 'Status of the second OSM geocoding (after Google spelling-correction): -1=error, 0=not found, 1=found with housenr, 2=found street only',
  `used` tinyint(1) DEFAULT NULL COMMENT 'Usage in address table: 1=used, 0=not used (duplicate or some error occurred)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `addressline` (`addressline`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `game`
--

CREATE TABLE IF NOT EXISTS `game` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_started` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `date_ended` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `game_x` double NOT NULL,
  `game_y` double NOT NULL,
  `player_address_no` int(11) NOT NULL COMMENT 'Number of addresses for a player in the game ',
  `max_players` int(11) NOT NULL COMMENT 'Maximum number of players in the game',
  `timeframe` int(11) NOT NULL,
  `bonus` int(11) DEFAULT NULL,
  `radius` float NOT NULL DEFAULT '0.5' COMMENT 'Distance in km used to determine the area from the considered center(game_x, game_y) from where to pick addresses for the new game',
  `status` enum('new','playing','canceled','finished','expired') NOT NULL DEFAULT 'new',
  `type` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gameaddress`
--

CREATE TABLE IF NOT EXISTS `gameaddress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `status` enum('active','discovered','expired','uploaded','uploaded_before','valid','invalid') NOT NULL DEFAULT 'active',
  `final_x` double DEFAULT NULL,
  `final_y` double DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Address finder id',
  `date_found` timestamp NULL DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL COMMENT 'Pictire filename taken when an address is found py a player ',
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_id` (`game_id`,`address_id`),
  KEY `address_id` (`address_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `gameuser`
--

CREATE TABLE IF NOT EXISTS `gameuser` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_joined` timestamp NULL DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive','canceled') NOT NULL DEFAULT 'active',
  `join_x` double NOT NULL,
  `join_y` double NOT NULL,
  `last_x` double DEFAULT NULL COMMENT 'Game player''s last position',
  `last_y` double DEFAULT NULL COMMENT 'Game player''s last position',
  `is_winner` tinyint(4) DEFAULT '0' COMMENT 'The game player with the most addresses discovered has at the end of the game value 1 ',
  `points` int(11) DEFAULT NULL COMMENT 'User''s points per game',
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_id` (`game_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nickname` varchar(100) NOT NULL,
  `osm_id` int(11) NOT NULL,
  `auth_token` varchar(255) DEFAULT NULL,
  `date_created` timestamp NULL DEFAULT NULL,
  `date_last_access` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `theme` int(11) DEFAULT NULL,
  `total_points` int(11) DEFAULT NULL COMMENT 'User''s total number of points',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nickname` (`nickname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `game`
--
ALTER TABLE `game`
  ADD CONSTRAINT `game_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`);

--
-- Constraints for table `gameaddress`
--
ALTER TABLE `gameaddress`
  ADD CONSTRAINT `gameaddress_ibfk_1` FOREIGN KEY (`address_id`) REFERENCES `address` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `gameaddress_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `gameaddress_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `gameuser`
--
ALTER TABLE `gameuser`
  ADD CONSTRAINT `gameuser_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `gameuser_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
