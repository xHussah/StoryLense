-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 02, 2025 at 08:25 PM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `storylense`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adminID` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profilePicture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default_admin.png',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`adminID`, `username`, `email`, `password`, `profilePicture`, `createdAt`) VALUES
(1, 'admin_lina', 'lina.admin@example.com', '$2y$10$voR061U5ykmo1cC3mgbUFOdLLjmuER9xw9EhLEOgq506huBY/Umau', 'user.png', '2025-11-27 14:24:05'),
(2, 'admin_sara', 'sara.admin@example.com', '$2y$10$um3MTRAr9eMSLE5eMaN4U.ycZ1eXKpb7EpVFSFHa3pjj6eKYktpfS', 'user.png', '2025-11-27 14:24:05'),
(3, 'admin_fares', 'fares.admin@example.com', '$2y$10$n7ilQLaCjAX4duBWlS/bQ.nrhIUt/qQrWSd8cHKgEA51zlM8NCgZ.', 'user.png', '2025-11-27 14:24:05');

-- --------------------------------------------------------

--
-- Table structure for table `movie`
--

CREATE TABLE `movie` (
  `movieID` int(11) NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `genre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` int(11) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `posterURL` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `releaseDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `movie`
--

INSERT INTO `movie` (`movieID`, `title`, `genre`, `duration`, `description`, `posterURL`, `releaseDate`) VALUES
(2, 'Spirited Away', 'Fantasy', 125, 'A girl in a spirit world', 'images/Spirited Away.jpeg', '2001-07-20'),
(3, 'The Dark Knight', 'Action', 152, 'Batman vs Joker', 'images/The Dark Knight poster.jpg', '2008-07-18'),
(4, '12 Angry Men', 'Drama', 96, 'A jury debates the guilt of a young defendant in a tense courtroom drama.', 'images/12 Angry Men Poster.jpg', '1957-04-10'),
(5, 'Avatar', 'Sci-Fi', 162, 'A paraplegic Marine discovers a new world on the planet Pandora.', 'images/avatar poster.jpeg', '2009-12-18'),
(6, 'Good Will Hunting', 'Drama', 126, 'A gifted janitor is discovered by a professor at MIT.', 'images/Good Will Hunting Poster.jpg', '1997-12-05'),
(7, 'Interstellar', 'Sci-Fi', 169, 'A team of explorers travel through a wormhole in space to save humanity.', 'images/Interstellar Poster.jpg', '2014-11-07'),
(8, 'Joker', 'Thriller', 122, 'A failed comedian descends into madness and becomes the infamous Joker.', 'images/Joker Poster.jpg', '2019-10-04'),
(9, 'Kill Bill: Vol. 1', 'Action', 111, 'A former assassin seeks revenge on her ex-colleagues.', 'images/Kill Bill Poster.jpg', '2003-10-10'),
(10, 'La La Land', 'Musical', 128, 'A jazz musician and an aspiring actress fall in love in Los Angeles.', 'images/La La Land Poster.jpg', '2016-12-09'),
(11, 'Ocean\'s 8', 'Crime', 110, 'A group of women plans an impossible heist at the Met Gala.', 'images/Ocean\'s 8 Poster.jpg', '2018-06-08'),
(12, 'Parasite', 'Thriller', 132, 'Two families form a symbiotic relationship that spirals out of control.', 'images/Parasite Poster.jpg', '2019-05-30'),
(13, 'Past Lives', 'Romance', 106, 'Two childhood friends reunite after decades apart.', 'images/Past Lives Poster.jpg', '2023-06-02'),
(14, 'Inception', 'Sci-Fi', 148, 'A thief who steals information through dreams takes on his toughest job yet.', 'images/Inception Poster.jpg', '2010-07-16'),
(15, 'Pride & Prejudice', 'Romance', 129, 'Elizabeth Bennet navigates love and society in Georgian England.', 'images/Pride And Prejudice Poster.jpg', '2005-09-16'),
(16, 'Prisoners', 'Thriller', 153, 'A father takes matters into his own hands when his daughter goes missing.', 'images/Prisoners Poster.jpg', '2013-09-20'),
(17, 'Superman', 'Action', 150, 'A modern retelling of the Man of Steel as he rises to protect humanity.', 'images/Superman Poster.jpg', '2025-07-11'),
(18, 'Whiplash', 'Drama', 107, 'A young drummer faces abusive training from his instructor.', 'images/Whiplash Poster.jpg', '2014-10-10');

-- --------------------------------------------------------

--
-- Table structure for table `rating`
--

CREATE TABLE `rating` (
  `ratingID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `movieID` int(11) NOT NULL,
  `score` tinyint(4) NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rating`
--

INSERT INTO `rating` (`ratingID`, `userID`, `movieID`, `score`, `type`, `createdAt`) VALUES
(1, 1, 17, 5, 'first', '2025-12-02 23:20:05'),
(2, 1, 17, 2, 'rewatch', '2025-12-02 23:20:08'),
(3, 1, 8, 3, 'first', '2025-12-02 23:20:13'),
(4, 2, 11, 2, 'first', '2025-12-02 23:21:22'),
(5, 2, 9, 4, 'first', '2025-12-02 23:21:29'),
(6, 2, 13, 3, 'first', '2025-12-02 23:21:42'),
(7, 2, 11, 4, 'rewatch', '2025-12-02 23:21:49');

-- --------------------------------------------------------

--
-- Table structure for table `shelf`
--

CREATE TABLE `shelf` (
  `shelfID` int(11) NOT NULL,
  `userID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shelf`
--

INSERT INTO `shelf` (`shelfID`, `userID`) VALUES
(1, 1),
(2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `shelfmovie`
--

CREATE TABLE `shelfmovie` (
  `shelfID` int(11) NOT NULL,
  `movieID` int(11) NOT NULL,
  `addedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shelfmovie`
--

INSERT INTO `shelfmovie` (`shelfID`, `movieID`, `addedAt`) VALUES
(1, 8, '2025-12-02 23:20:13'),
(1, 17, '2025-12-02 23:20:05'),
(2, 9, '2025-12-02 23:21:29'),
(2, 11, '2025-12-02 23:21:22'),
(2, 13, '2025-12-02 23:21:42');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userID` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profilePicture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default.png',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userID`, `username`, `email`, `password`, `profilePicture`, `createdAt`) VALUES
(1, 'Nora', 'Nora@gmail.com', '$2y$10$VtA/SL0n4wIzGpBHIyvA2OGhEUZ64/k8INajwKfaYpgf5lo5H3DRG', 'penguin.jpg', '2025-12-02 20:19:35'),
(2, 'Sara', 'Sara@gmail.com', '$2y$10$PwSdRvWhcIcOKw.Q59O2WO1jvx4/0B.huDCdWutmUlZvmoUMyWpa.', 'rabbit.jpg', '2025-12-02 20:20:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adminID`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `movie`
--
ALTER TABLE `movie`
  ADD PRIMARY KEY (`movieID`);

--
-- Indexes for table `rating`
--
ALTER TABLE `rating`
  ADD PRIMARY KEY (`ratingID`),
  ADD UNIQUE KEY `uq_Rating_user_movie_type` (`userID`,`movieID`,`type`),
  ADD KEY `fk_Rating_movie` (`movieID`);

--
-- Indexes for table `shelf`
--
ALTER TABLE `shelf`
  ADD PRIMARY KEY (`shelfID`),
  ADD UNIQUE KEY `uq_Shelf_user` (`userID`);

--
-- Indexes for table `shelfmovie`
--
ALTER TABLE `shelfmovie`
  ADD PRIMARY KEY (`shelfID`,`movieID`),
  ADD KEY `fk_SM_movie` (`movieID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `adminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `movie`
--
ALTER TABLE `movie`
  MODIFY `movieID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `rating`
--
ALTER TABLE `rating`
  MODIFY `ratingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `shelf`
--
ALTER TABLE `shelf`
  MODIFY `shelfID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rating`
--
ALTER TABLE `rating`
  ADD CONSTRAINT `fk_Rating_movie` FOREIGN KEY (`movieID`) REFERENCES `movie` (`movieID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_Rating_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `shelf`
--
ALTER TABLE `shelf`
  ADD CONSTRAINT `fk_Shelf_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `shelfmovie`
--
ALTER TABLE `shelfmovie`
  ADD CONSTRAINT `fk_SM_movie` FOREIGN KEY (`movieID`) REFERENCES `movie` (`movieID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_SM_shelf` FOREIGN KEY (`shelfID`) REFERENCES `shelf` (`shelfID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
