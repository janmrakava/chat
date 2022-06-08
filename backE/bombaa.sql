-- import to SQLite by running: sqlite3.exe db.sqlite3 -init sqlite.sql

PRAGMA journal_mode = MEMORY;
PRAGMA synchronous = OFF;
PRAGMA foreign_keys = OFF;
PRAGMA ignore_check_constraints = OFF;
PRAGMA auto_vacuum = NONE;
PRAGMA secure_delete = OFF;
BEGIN TRANSACTION;

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
DROP TABLE IF EXISTS `in_room`;

CREATE TABLE `in_room` (
`id_users` INTEGER  NOT NULL,
`id_rooms` INTEGER  NOT NULL,
`last_message` datetime NOT NULL,
`entered` datetime NOT NULL,
FOREIGN KEY (`id_users`) REFERENCES `users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (`id_rooms`) REFERENCES `rooms` (`id_rooms`) ON DELETE CASCADE ON UPDATE CASCADE
);
DROP TABLE IF EXISTS `messages`;

CREATE TABLE `messages` (
`id_messages` INTEGER  NOT NULL ,
`id_rooms` INTEGER  NOT NULL,
`id_users_from` INTEGER  NOT NULL,
`id_users_to` INTEGER  DEFAULT NULL,
`created` datetime NOT NULL,
`message` TEXT  NOT NULL,
PRIMARY KEY (`id_messages`),
FOREIGN KEY (`id_rooms`) REFERENCES `rooms` (`id_rooms`) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (`id_users_from`) REFERENCES `users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (`id_users_to`) REFERENCES `users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE
);
DROP TABLE IF EXISTS `rooms`;

CREATE TABLE `rooms` (
`id_rooms` INTEGER  NOT NULL ,
`created` datetime NOT NULL,
`title` TEXT  NOT NULL,
`id_users_owner` INTEGER  DEFAULT NULL,
`lock` TEXT   NOT NULL DEFAULT 'false',
PRIMARY KEY (`id_rooms`),
FOREIGN KEY (`id_users_owner`) REFERENCES `users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE
);
DROP TABLE IF EXISTS `room_kick`;

CREATE TABLE `room_kick` (
`id_users` INTEGER  NOT NULL,
`id_rooms` INTEGER  NOT NULL,
`created` datetime NOT NULL,
PRIMARY KEY (`id_users`,`id_rooms`),
FOREIGN KEY (`id_users`) REFERENCES `users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE,
FOREIGN KEY (`id_rooms`) REFERENCES `rooms` (`id_rooms`) ON DELETE CASCADE ON UPDATE CASCADE
);
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
`id_users` INTEGER  NOT NULL ,
`login` TEXT  NOT NULL,
`email` TEXT  NOT NULL,
`password` TEXT  NOT NULL,
`name` TEXT  NOT NULL,
`surname` TEXT  NOT NULL,
`gender` TEXT   NOT NULL,
`registered` datetime NOT NULL,
`role` TEXT  NOT NULL DEFAULT 'user',
PRIMARY KEY (`id_users`)
);



CREATE UNIQUE INDEX `in_room_id_users_UNIQUE` ON `in_room` (`id_users`,`id_rooms`);
CREATE INDEX `in_room_fk_in_room_users1_idx` ON `in_room` (`id_users`);
CREATE INDEX `in_room_fk_in_room_rooms1_idx` ON `in_room` (`id_rooms`);
CREATE INDEX `messages_fk_messages_rooms1_idx` ON `messages` (`id_rooms`);
CREATE INDEX `messages_fk_messages_users1_idx` ON `messages` (`id_users_from`);
CREATE INDEX `messages_fk_messages_users2_idx` ON `messages` (`id_users_to`);
CREATE INDEX `rooms_fk_rooms_users1_idx` ON `rooms` (`id_users_owner`);
CREATE INDEX `room_kick_id_rooms` ON `room_kick` (`id_rooms`);
CREATE UNIQUE INDEX `users_login_UNIQUE` ON `users` (`login`);
CREATE UNIQUE INDEX `users_email_UNIQUE` ON `users` (`email`);

COMMIT;
PRAGMA ignore_check_constraints = ON;
PRAGMA foreign_keys = ON;
PRAGMA journal_mode = WAL;
PRAGMA synchronous = NORMAL;
