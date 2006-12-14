-- phpMyAdmin SQL Dump
-- version 2.9.0.1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Dec 05, 2006 at 05:10 PM
-- Server version: 5.0.24
-- PHP Version: 5.1.6
-- 
-- Database: `dialerasp`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `parentuser`
-- 

CREATE TABLE `parentuser` (
  `id` int(11) NOT NULL auto_increment,
  `firstname` varchar(50) collate utf8_bin NOT NULL,
  `lastname` varchar(50) collate utf8_bin NOT NULL,
  `login` varchar(50) collate utf8_bin NOT NULL,
  `password` varchar(255) collate utf8_bin NOT NULL,
  `customerid` varchar(255) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1;

-- 
-- Dumping data for table `parentuser`
-- 
