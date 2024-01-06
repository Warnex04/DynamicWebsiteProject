-- phpMyAdmin SQL Dump
-- version 4.5.4.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 06, 2024 at 09:22 PM
-- Server version: 5.7.11
-- PHP Version: 7.0.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mylibrary`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user`, `action`, `date`) VALUES
(1, 'FakeAdmin', 'Added new book', '2024-01-06'),
(2, 'FakeAdmin', 'Added new author', '2024-01-06');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `ID` int(11) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `Mail` varchar(150) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`ID`, `FirstName`, `LastName`, `Mail`, `Password`, `Phone`) VALUES
(0, 'test', 'test', 'test@test.fr', '$2y$10$zNXAtc6iU9UEyyL6Ysdxd.JTZ2fYh5Tt40NryG0Yr7ohnqtQnQ86O', 'test');

-- --------------------------------------------------------

--
-- Table structure for table `author`
--

CREATE TABLE `author` (
  `Num` int(11) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `BirthDate` date NOT NULL,
  `Nationality` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `author`
--

INSERT INTO `author` (`Num`, `FirstName`, `LastName`, `BirthDate`, `Nationality`) VALUES
(1, 'Ernest', 'Hemingway', '1899-07-21', 'American'),
(2, 'Jane', 'Austen', '1775-12-16', 'British'),
(3, 'Gabriel', 'García Márquez', '1927-03-06', 'Colombian');

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

CREATE TABLE `book` (
  `ISSN` varchar(8) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Summary` text NOT NULL,
  `NbPages` int(11) NOT NULL,
  `Category` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `book`
--

INSERT INTO `book` (`ISSN`, `Title`, `Summary`, `NbPages`, `Category`) VALUES
('12345678', 'The Old Man and the Sea', 'A story about an old Cuban fisherman and his battle with a giant marlin', 127, 'Fiction'),
('23456789', 'Pride and Prejudice', 'A romantic novel of manners', 432, 'Classic'),
('34567890', 'One Hundred Years of Solitude', 'A multi-generational story of the Buendía family', 417, 'Magical Realism'),
('45678901', 'Test1', 'Testing insertion', 123, 'Fiction'),
('45678902', 'Test2', 'Testing refresh after insertion', 1234, 'Fiction'),
('45678903', 'Test3', 'Testing activity audit', 12345, 'Fiction');

-- --------------------------------------------------------

--
-- Table structure for table `ecrit`
--

CREATE TABLE `ecrit` (
  `ID` int(11) NOT NULL,
  `Num` int(11) NOT NULL,
  `ISSN` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `ecrit`
--

INSERT INTO `ecrit` (`ID`, `Num`, `ISSN`) VALUES
(1, 1, '12345678'),
(2, 2, '23456789'),
(3, 3, '34567890');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `author`
--
ALTER TABLE `author`
  ADD PRIMARY KEY (`Num`);

--
-- Indexes for table `book`
--
ALTER TABLE `book`
  ADD PRIMARY KEY (`ISSN`);

--
-- Indexes for table `ecrit`
--
ALTER TABLE `ecrit`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `fk_Author_Num` (`Num`),
  ADD KEY `fk_Book_ISSN` (`ISSN`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `author`
--
ALTER TABLE `author`
  MODIFY `Num` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `ecrit`
--
ALTER TABLE `ecrit`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `ecrit`
--
ALTER TABLE `ecrit`
  ADD CONSTRAINT `fk_Author_Num` FOREIGN KEY (`Num`) REFERENCES `author` (`Num`),
  ADD CONSTRAINT `fk_Book_ISSN` FOREIGN KEY (`ISSN`) REFERENCES `book` (`ISSN`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
