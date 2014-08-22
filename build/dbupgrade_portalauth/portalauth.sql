-- phpMyAdmin SQL Dump
-- version 3.4.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 09, 2014 at 08:06 AM
-- Server version: 5.1.59
-- PHP Version: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"
$$$

--
-- Database: 'portalauth'
--

-- --------------------------------------------------------

--
-- Table structure for table 'oauth_access_token'
--

CREATE TABLE IF NOT EXISTS oauth_access_token (
  token_id varchar(256) DEFAULT NULL,
  token blob,
  authentication_id varchar(256) DEFAULT NULL,
  user_name varchar(256) DEFAULT NULL,
  client_id varchar(256) DEFAULT NULL,
  authentication blob,
  refresh_token varchar(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1
$$$

-- --------------------------------------------------------

--
-- Table structure for table 'oauth_client_details'
--

CREATE TABLE IF NOT EXISTS oauth_client_details (
  client_id varchar(256) NOT NULL,
  resource_ids varchar(256) DEFAULT NULL,
  client_secret varchar(256) DEFAULT NULL,
  scope varchar(256) DEFAULT NULL,
  authorized_grant_types varchar(256) DEFAULT NULL,
  web_server_redirect_uri varchar(256) DEFAULT NULL,
  authorities varchar(256) DEFAULT NULL,
  access_token_validity int(11) DEFAULT NULL,
  refresh_token_validity int(11) DEFAULT NULL,
  additional_information varchar(4096) DEFAULT NULL,
  autoapprove varchar(256) DEFAULT NULL,
  PRIMARY KEY (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1
$$$

-- --------------------------------------------------------

--
-- Table structure for table 'oauth_client_token'
--

CREATE TABLE IF NOT EXISTS oauth_client_token (
  token_id varchar(256) DEFAULT NULL,
  token blob,
  authentication_id varchar(256) DEFAULT NULL,
  user_name varchar(256) DEFAULT NULL,
  client_id varchar(256) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1
$$$

-- --------------------------------------------------------

--
-- Table structure for table 'oauth_code'
--

CREATE TABLE IF NOT EXISTS oauth_code (
  `code` varchar(256) DEFAULT NULL,
  authentication blob
) ENGINE=InnoDB DEFAULT CHARSET=latin1
$$$

-- --------------------------------------------------------

--
-- Table structure for table 'oauth_refresh_token'
--

CREATE TABLE IF NOT EXISTS oauth_refresh_token (
  token_id varchar(256) DEFAULT NULL,
  token blob,
  authentication blob
) ENGINE=InnoDB DEFAULT CHARSET=latin1
$$$

ALTER TABLE oauth_access_token ADD INDEX ( client_id , user_name )
$$$

ALTER TABLE oauth_access_token ADD INDEX ( authentication_id )
$$$

ALTER TABLE oauth_access_token ADD INDEX ( token_id )
$$$

-- ------------------------------------------------------
-- add table for database versioning used by upgrade_databases.php

CREATE TABLE `dbupgrade` (
 `id` varchar(20) NOT NULL,
 `version` varchar(20) NOT NULL,
 `lastUpdateMs` bigint(20) NOT NULL,
 `status` varchar(20) NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO `dbupgrade` 
  VALUES ('portalauth', '11.0/1', (UNIX_TIMESTAMP() * 1000), 'none');

-- ------------------------------------------------------
-- NO MORE BELOW HERE!!! use upgrade_databases
