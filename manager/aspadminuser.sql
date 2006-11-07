-- phpMyAdmin SQL Dump
-- version 2.9.0.1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Oct 27, 2006 at 02:36 PM
-- Server version: 5.0.24
-- PHP Version: 5.1.6
-- 
-- Database: `dialerasp`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `aspadminuser`
-- 

CREATE TABLE `aspadminuser` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(20) collate utf8_bin NOT NULL,
  `password` varchar(255) character set utf8 NOT NULL,
  `firstname` varchar(50) character set utf8 NOT NULL,
  `lastname` varchar(50) character set utf8 NOT NULL,
  `email` varchar(100) character set utf8 NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `login` (`login`,`password`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;

-- 
-- Dumping data for table `aspadminuser`
-- 

