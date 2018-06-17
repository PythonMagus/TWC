-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 17, 2018 at 02:05 PM
-- Server version: 10.1.31-MariaDB-cll-lve
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `maneschi_twc`
--

-- --------------------------------------------------------

--
-- Table structure for table `battles`
--

CREATE TABLE `battles` (
  `id` int(11) NOT NULL,
  `typeid` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `started` datetime NOT NULL,
  `ended` datetime DEFAULT NULL,
  `state` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `challenges`
--

CREATE TABLE `challenges` (
  `id` int(11) NOT NULL,
  `handle` varchar(32) NOT NULL,
  `from` int(11) NOT NULL,
  `to` int(11) NOT NULL,
  `gametypeid` int(11) NOT NULL,
  `tournamentid` int(11) DEFAULT NULL,
  `description` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `gametypes`
--

CREATE TABLE `gametypes` (
  `id` int(11) NOT NULL,
  `type` varchar(32) NOT NULL,
  `name` varchar(128) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ribbons`
--

CREATE TABLE `ribbons` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `image` varchar(32) NOT NULL,
  `family` varchar(16) NOT NULL,
  `level` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` char(128) NOT NULL,
  `set_time` char(10) NOT NULL,
  `data` text NOT NULL,
  `session_key` char(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tournamentawards`
--

CREATE TABLE `tournamentawards` (
  `id` int(11) NOT NULL,
  `tournamentId` int(11) NOT NULL,
  `level` int(11) NOT NULL DEFAULT '0',
  `url` varchar(64) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tournamentbattles`
--

CREATE TABLE `tournamentbattles` (
  `unid` int(11) NOT NULL,
  `battleId` int(11) NOT NULL,
  `tournamentId` int(11) NOT NULL,
  `round` smallint(6) NOT NULL,
  `playoff` smallint(6) NOT NULL,
  `byePlays` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE TABLE `tournaments` (
  `id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `state` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '0',
  `gametypeid` int(11) NOT NULL DEFAULT '0',
  `started` datetime NOT NULL,
  `ended` datetime DEFAULT NULL,
  `numrounds` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tournamentusers`
--

CREATE TABLE `tournamentusers` (
  `unid` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `tournamentId` int(11) NOT NULL,
  `awardid` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `userbattles`
--

CREATE TABLE `userbattles` (
  `userId` int(11) NOT NULL,
  `battleId` int(11) NOT NULL,
  `result` int(11) NOT NULL DEFAULT '0',
  `points` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `userribbons`
--

CREATE TABLE `userribbons` (
  `userId` int(11) NOT NULL,
  `ribbonId` int(11) NOT NULL,
  `awarded` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `realName` varchar(48) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `alias` varchar(32) NOT NULL,
  `email` varchar(32) NOT NULL,
  `password` varchar(41) NOT NULL,
  `rankType` varchar(16) NOT NULL DEFAULT 'military',
  `lastLogin` datetime NOT NULL,
  `created` datetime NOT NULL,
  `state` int(11) NOT NULL DEFAULT '0',
  `battles` int(11) NOT NULL DEFAULT '0',
  `victories` int(11) NOT NULL DEFAULT '0',
  `points` int(11) NOT NULL DEFAULT '0',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `passwordReset` varchar(32) DEFAULT NULL,
  `suspended` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `battles`
--
ALTER TABLE `battles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`) USING BTREE;

--
-- Indexes for table `challenges`
--
ALTER TABLE `challenges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `handle` (`handle`);

--
-- Indexes for table `gametypes`
--
ALTER TABLE `gametypes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type` (`type`);

--
-- Indexes for table `ribbons`
--
ALTER TABLE `ribbons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ByName` (`name`),
  ADD UNIQUE KEY `ByFamily` (`family`,`level`) USING BTREE;

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tournamentawards`
--
ALTER TABLE `tournamentawards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tournamentbattles`
--
ALTER TABLE `tournamentbattles`
  ADD PRIMARY KEY (`unid`),
  ADD KEY `battleId` (`battleId`),
  ADD KEY `tournamentId` (`tournamentId`,`round`,`playoff`) USING BTREE;

--
-- Indexes for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tournamentusers`
--
ALTER TABLE `tournamentusers`
  ADD PRIMARY KEY (`unid`),
  ADD KEY `userid` (`userId`),
  ADD KEY `tournamentId` (`tournamentId`);

--
-- Indexes for table `userbattles`
--
ALTER TABLE `userbattles`
  ADD UNIQUE KEY `Pair` (`userId`,`battleId`);

--
-- Indexes for table `userribbons`
--
ALTER TABLE `userribbons`
  ADD UNIQUE KEY `Pair` (`userId`,`ribbonId`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`email`,`password`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `battles`
--
ALTER TABLE `battles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=218;

--
-- AUTO_INCREMENT for table `challenges`
--
ALTER TABLE `challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `gametypes`
--
ALTER TABLE `gametypes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `ribbons`
--
ALTER TABLE `ribbons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `tournamentawards`
--
ALTER TABLE `tournamentawards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tournamentbattles`
--
ALTER TABLE `tournamentbattles`
  MODIFY `unid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tournamentusers`
--
ALTER TABLE `tournamentusers`
  MODIFY `unid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
