CREATE DATABASE IF NOT EXISTS weida DEFAULT CHARSET utf8mb4;
USE weida;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email_encrypted` TEXT DEFAULT NULL,
  `last_ip_encrypted` TEXT DEFAULT NULL,
  `rating` int(11) DEFAULT 1500,
  `wins` int(11) DEFAULT 0,
  `losses` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

CREATE TABLE `active_rooms` (
  `room_id` varchar(10) NOT NULL,
  `black_id` int(11) DEFAULT NULL,
  `white_id` int(11) DEFAULT NULL,
  `board_data` json DEFAULT NULL,
  `current_turn` tinyint(1) DEFAULT 1,
  `move_history` json DEFAULT NULL,
  `ko` json DEFAULT NULL,
  `captured_black` int(11) DEFAULT 0,
  `captured_white` int(11) DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `last_update` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`room_id`)
);

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` varchar(10) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`)
);

CREATE TABLE `games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` varchar(10) NOT NULL,
  `black_id` int(11) NOT NULL,
  `white_id` int(11) NOT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `loser_id` int(11) DEFAULT NULL,
  `win_type` enum('resign','count','timeout') DEFAULT NULL,
  `sgf` text NOT NULL,
  `move_count` int(11) DEFAULT 0,
  `start_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `end_time` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
);

CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `detail` text,
  `ip` varchar(45) NOT NULL,
  `time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
