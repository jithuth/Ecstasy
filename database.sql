-- Database: pirnav_clone

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

DELETE FROM `users`;
INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL,
  `value` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`key_name`, `value`) VALUES
('site_title', 'Ecstasy Solutions'),
('contact_email', 'hello@ecstasysolutions.com'),
('contact_address', 'Hitech City Rd, Vittal Rao Nagar, Madhapur, Hyderabad, Telangana 500081'),
('about_image', ''),
('about_content', '<p>Hello! We are Ecstasy Solutions, a team of dedicated developers and designers who love creating things that live on the internet.</p>'),
('seo_title', 'Ecstasy Solutions - Web & Mobile App Development'),
('seo_description', 'Ecstasy Solutions is a leading software development company specializing in web and mobile app development.'),
('seo_keywords', 'web development, mobile apps, software, hyderabad, ecstasy solutions');

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`title`, `description`, `image`) VALUES
('Web Development', 'Building responsive and performant web applications using modern technologies.', 'web_dev.jpg'),
('Mobile App Development', 'Creating native and cross-platform mobile applications for iOS and Android.', 'mobile_dev.jpg'),
('Cloud Solutions', 'Architecting and deploying scalable cloud infrastructure on AWS and Azure.', 'cloud.jpg');

--
-- Table structure for table `carousel`
--

CREATE TABLE `carousel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `carousel`
--

INSERT INTO `carousel` (`title`, `subtitle`, `image`, `sort_order`) VALUES
('Ecstasy Solutions', 'We build things for the web.', 'slide1.jpg', 1),
('Digital Innovation', 'Transforming ideas into reality.', 'slide2.jpg', 2);

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
