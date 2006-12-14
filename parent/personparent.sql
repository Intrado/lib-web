-- phpMyAdmin SQL Dump
-- version 2.9.0.1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Dec 07, 2006 at 05:24 PM
-- Server version: 5.0.24
-- PHP Version: 5.1.6
-- 
-- Database: `dialerasp`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `personparent`
-- 

CREATE TABLE `personparent` (
  `personid` int(11) NOT NULL,
  `parentuserid` int(11) NOT NULL,
  UNIQUE KEY `personid` (`personid`,`parentuserid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- 
-- Dumping data for table `personparent`
-- 

