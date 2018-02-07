CREATE TABLE `single_use_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `valid` TINYINT(1) DEFAULT 1,
  `attempts` INT(11) DEFAULT 0,
  `token` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;