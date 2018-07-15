-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 15, 2018 at 10:31 AM
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

--
-- Dumping data for table `ribbons`
--

INSERT INTO `ribbons` (`id`, `name`, `image`, `family`, `level`) VALUES
(1, 'Club General Service & Activity', 'clubservice', 'club', 0),
(2, 'Moderating Club Games (>1v1)', 'moderator', 'club', 1),
(3, 'Played 1 TWC Game', 'play1', 'play', 1),
(4, 'Played 2 TWC games', 'play2', 'play', 2),
(5, 'Played 5 TWC games', 'play5', 'play', 5),
(6, 'Played 15 TWC games', 'play15', 'play', 15),
(7, 'Played 30 TWC games', 'play30', 'play', 30),
(8, 'Played 50 TWC games', 'play50', 'play', 50),
(9, 'Service Ribbon Training Officers', 'trainer', 'unused', 2),
(10, 'Win 1 TWC game', 'win1', 'win', 1),
(11, 'Win 2 TWC games', 'win2', 'win', 2),
(12, 'Win 5 TWC games', 'win5', 'win', 5),
(13, 'Win 10 TWC games', 'win10', 'win', 10),
(14, 'Win 20 TWC games', 'win20', 'win', 20),
(15, 'Win 40 TWC games', 'win40', 'win', 40),
(16, '1 year TWC service', 'twc1year', 'years', 1),
(17, '3 years TWC service', 'twc3years', 'years', 3),
(18, '5 years TWC service', 'twc5years', 'years', 5),
(19, '7 years TWC service', 'twc7years', 'years', 7),
(20, '9 years TWC service', 'twc9years', 'years', 9),
(21, '>10 years TWC service', 'twc10plusyears', 'years', 10),
(22, 'Marshal', 'Marshal', 'military', 75),
(23, 'General', 'General', 'military', 60),
(24, 'Lieutenant General', 'Lieutenant General', 'military', 50),
(25, 'Major General', 'Major General', 'military', 40),
(26, 'Brigadier', 'Brigadier', 'military', 30),
(27, 'Colonel', 'Colonel', 'military', 25),
(28, 'Lieutenant Colonel', 'Lieutenant Colonel', 'military', 20),
(29, 'Major', 'Major', 'military', 15),
(30, 'Captain', 'Captain', 'military', 10),
(31, 'Lieutenant', 'Lieutenant', 'military', 5),
(32, '2nd Lieutenant', '2nd Lieutenant', 'military', 1),
(33, 'Ki', '2nd Lieutenant', 'civil', 1),
(34, 'Tiki', 'Lieutenant', 'civil', 5),
(35, 'Pahtiki', 'Captain', 'civil', 10),
(36, 'Big Pahtiki', 'Major', 'civil', 15),
(37, 'Grand Pahtiki', 'Lieutenant Colonel', 'civil', 20),
(38, 'Great Pahtiki', 'Colonel', 'civil', 25),
(39, 'Bah', 'Brigadier', 'civil', 30),
(40, 'Poobah', 'Major General', 'civil', 40),
(41, 'Big Poobah', 'Lieutenant General', 'civil', 50),
(42, 'Grand Poobah', 'General', 'civil', 60),
(43, 'Great Poobah', 'Marshal', 'civil', 75),
(44, 'Club adminstrative paperwork', 'mentionedInDispatches', 'club', 10),
(45, 'Staff Service Silver', 'staffServiceSilver', 'club', 20),
(46, 'Staff Service Gold', 'staffServiceGold', 'club-old', 21),
(47, 'Civil War 2 -Tourn #1 Winner ', 'TWC2017 cw2', 'tournament', 101),
(48, 'Nap Camp - Tourn #1 Winner', 'TWC2017 NCP', 'tournament', 111),
(49, 'FoG2 - Tourn #2 Winner', 't2_1', 'tournament', 201),
(50, 'FoG2 - Tourn #2 Runner up', 't2_2', 'tournament', 202),
(51, 'FoG2 - Tourn #2 Third', 't2_3', 'tournament', 203),
(52, 'FoG2 - Tourn #2 Played', 't2_p', 'tournament', 209),
(53, 'FoG2 - Tourn #3 Winner', 't3_1', 'tournament', 301),
(54, 'FoG2 - Tourn #3 Runner up', 't3_2', 'tournament', 302),
(55, 'FoG2 - Tourn #3 Third', 't3_3', 'tournament', 303),
(56, 'FoG2 - Tourn #3 Played', 't3_p', 'tournament', 309);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
