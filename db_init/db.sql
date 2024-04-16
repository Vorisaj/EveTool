CREATE DATABASE IF NOT EXISTS eve;

USE eve;

CREATE TABLE IF NOT EXISTS `sessions` (
  `user_id` int(11) NOT NULL,
  `char_id` int(11) NOT NULL,
  `char_name` varchar(100) DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `token` varchar(2000) DEFAULT NULL,
  `refresh_token` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`char_id`)
);

CREATE TABLE IF NOT EXISTS `tracked_market` (
  `order_id` bigint(20) NOT NULL,
  `is_buy` tinyint(1) DEFAULT NULL,
  `price` float DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  `volume_remain` int(11) DEFAULT NULL,
  `volume_total` int(11) DEFAULT NULL,
  PRIMARY KEY (`order_id`)
);

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `passwordhash` varchar(64) NOT NULL,
  `salt` varchar(5) NOT NULL,
  `email` varchar(50) DEFAULT '',
  `is_admin` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
);