-- phpMyAdmin SQL Dump
-- version 2.9.0.1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Dec 15, 2006 at 03:04 PM
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
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `customerid` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;
