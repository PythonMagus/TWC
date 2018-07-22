/*
 * 
 *  This file is part of TWCOoR.
 * 
 *  TWCOoR is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License 
 *  as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 *  TWCOoR is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty 
 *  of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 *  You should have received a copy of the GNU General Public License along with TWCOoR. 
 *  If not, see http://www.gnu.org/licenses/.
 * 
 */
-- phpMyAdmin SQL Dump
-- version 4.7.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 15, 2018 at 10:42 AM
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
-- Dumping data for table `gametypes`
--

INSERT INTO `gametypes` (`id`, `type`, `name`) VALUES
(1, 'ATG', 'Slitherine/Matrix\\Advanced Tactics Gold'),
(2, 'WoN', 'AGEOD\\Wars of Napoleon'),
(3, 'ATG-GD1938', 'Slitherine/Matrix\\Global Domination 1938 (WW2 mod for ATG)'),
(4, 'WoS', 'AGEOD\\Wars of Succession'),
(5, 'CW2', 'AGEOD\\Civil War II'),
(6, 'PoN', 'AGEOD\\Pride of Nations'),
(7, 'AJE', 'AGEOD\\Alea Jacta Est'),
(8, 'FoG2', 'Slitherine/Matrix\\Field of Glory 2'),
(9, 'GGWinW', 'Slitherine/Matrix\\Gary Grigsby\'s War in the West & East'),
(10, 'OAoW4', 'Slitherine/Matrix\\The Operational Art of War IV'),
(11, 'SC-WW2', 'Slitherine/Matrix\\Strategic Command WWII: War in Europe'),
(12, 'FC-RS', 'Slitherine/Matrix\\Flashpoint Campaigns: Red Storm'),
(13, 'OOB-WW2', 'Slitherine/Matrix\\Order of Battle: World War II'),
(14, 'MtG', 'Slitherine/Matrix\\March to Glory'),
(15, 'G-TTT', 'Slitherine/Matrix\\Gettysburg: The Tide Turns'),
(16, 'P&S', 'Slitherine/Matrix\\Pike and Shot: Campaigns'),
(17, 'SJ', 'Slitherine/Matrix\\Sengoku Jidai: Shadow of the Shogun'),
(18, 'RoP', 'AGEOD\\Rise of Prussia Gold'),
(19, 'RuSG', 'AGEOD\\Revolution Under Siege Gold'),
(20, 'ECW', 'AGEOD\\English Civil War'),
(21, 'E1936', 'AGEOD\\Espana 1936'),
(22, 'TYW', 'AGEOD\\Thirty Years\' War'),
(23, 'NC', 'AGEOD\\Napoleon\'s Campaigns'),
(24, 'BoA2', 'AGEOD\\Birth of America II: Wars in America'),
(25, 'TEAW', 'AGEOD\\To End All Wars'),
(26, 'NB', 'John Tiller Software/HPS Simulations\\Napoleonic Battles'),
(27, 'FWWC', 'John Tiller Software/HPS Simulations\\First World War Campaigns '),
(28, 'M&P', 'John Tiller Software/HPS Simulations\\Musket and Pike'),
(29, 'CWB', 'John Tiller Software/HPS Simulations\\Civil War Battles'),
(30, 'EAW', 'John Tiller Software/HPS Simulations\\Early American Wars'),
(31, 'PC', 'John Tiller Software/HPS Simulations\\Panzer Campaigns'),
(32, 'PB', 'John Tiller Software/HPS Simulations\\Panzer Battles'),
(33, 'MC', 'John Tiller Software/HPS Simulations\\Modern Campaigns'),
(34, 'SW', 'John Tiller Software/HPS Simulations\\Strategic War'),
(42, 'RTS', 'Realtime Strategy'),
(36, 'SquadB', 'John Tiller Software/HPS Simulations\\Squad Battles'),
(37, 'NavalC', 'John Tiller Software/HPS Simulations\\Naval Campaigns'),
(38, 'MAP', 'John Tiller Software/HPS Simulations\\Modern Air Power'),
(39, 'GS', 'G-S, SoW & HW\\General Staff'),
(40, 'SoW', 'G-S, SoW & HW\\Scourge of War'),
(41, 'HW', 'G-S, SoW & HW\\Hist War');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
