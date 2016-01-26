-- phpMyAdmin SQL Dump
-- version 4.0.10.10
-- http://www.phpmyadmin.net
--
-- Хост: 127.0.0.1:3306
-- Время создания: Янв 26 2016 г., 08:07
-- Версия сервера: 5.6.26
-- Версия PHP: 5.4.44

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `synoptic`
--

-- --------------------------------------------------------

--
-- Структура таблицы `locations`
--

CREATE TABLE IF NOT EXISTS `locations` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `geom` point NOT NULL,
  `source_id` int(10) DEFAULT NULL,
  `temperature` decimal(3,1) DEFAULT NULL,
  `population` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `population` (`population`),
  KEY `sorce_id` (`source_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=35 ;

--
-- Дамп данных таблицы `locations`
--

INSERT INTO `locations` (`id`, `name`, `geom`, `source_id`, `temperature`, `population`) VALUES
(1, 'Київ1', '\0\0\0\0\0\0\0�7��>@��26t3I@', 4, '-10.0', 2804000),
(2, 'Ірпінь', '\0\0\0\0\0\0\0R�h=>@�]gC�AI@', 7, '-12.0', 42924),
(3, 'Магадан', '\0\0\0\0\0\0\0������b@��s���M@', NULL, '-20.4', 95263);

-- --------------------------------------------------------

--
-- Структура таблицы `locations_i18n`
--

CREATE TABLE IF NOT EXISTS `locations_i18n` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `location_id` int(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `lang_code` char(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locale_for_location` (`location_id`,`lang_code`),
  KEY `name` (`name`),
  KEY `location_id` (`location_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=31 ;

--
-- Дамп данных таблицы `locations_i18n`
--

INSERT INTO `locations_i18n` (`id`, `location_id`, `name`, `lang_code`) VALUES
(1, 1, 'Київ', 'ua'),
(2, 1, 'Kiev', 'en'),
(3, 2, 'Ірпінь', 'ua'),
(4, 2, 'Irpen', 'en'),
(5, 3, 'Магадан', 'ua'),
(6, 3, 'Magadan', 'en'),
(7, 1, 'Киев', 'ru'),
(8, 2, 'Ирпень', 'ru'),
(9, 3, 'Магадан', 'ru');

-- --------------------------------------------------------

--
-- Структура таблицы `sources`
--

CREATE TABLE IF NOT EXISTS `sources` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `geom` point NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

--
-- Дамп данных таблицы `sources`
--

INSERT INTO `sources` (`id`, `geom`, `name`) VALUES
(4, '\0\0\0\0\0\0\0��V�/{>@�z�GAI@', 'оз. Опечень (г. Киев)'),
(5, '\0\0\0\0\0\0\0��\Z/��>@����u+I@', 'Аэропорт Борисполь'),
(6, '\0\0\0\0\0\0\0��~j��=@��\Z/�I@', 'с. Пришивальня (Киевская обл., Фастовский р-н)'),
(7, '\0\0\0\0\0\0\0�1>@�@ CMI@', 'Аэропорт Гостомель');

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`source_id`) REFERENCES `sources` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `locations_i18n`
--
ALTER TABLE `locations_i18n`
  ADD CONSTRAINT `locations_i18n_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
