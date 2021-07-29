-- phpMyAdmin SQL Dump
-- version 4.0.2
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Дек 30 2015 г., 20:04
-- Версия сервера: 5.6.11-log
-- Версия PHP: 5.4.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `fokcms`
--
CREATE DATABASE IF NOT EXISTS `fokcms` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `fokcms`;

-- --------------------------------------------------------

--
-- Структура таблицы `catalog_category`
--

CREATE TABLE IF NOT EXISTS `catalog_category` (
  `CategoryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PageID` int(10) unsigned NOT NULL DEFAULT '0',
  `Path2Root` varchar(255) NOT NULL DEFAULT '',
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `CategoryImage` varchar(255) DEFAULT NULL,
  `CategoryImageConfig` text,
  `TitleH1` varchar(255) NOT NULL,
  `MetaTitle` varchar(255) NOT NULL DEFAULT '',
  `MetaKeywords` varchar(255) NOT NULL DEFAULT '',
  `MetaDescription` varchar(255) NOT NULL DEFAULT '',
  `StaticPath` varchar(255) NOT NULL DEFAULT '',
  `Content` longtext NOT NULL,
  `SortOrder` int(10) unsigned NOT NULL DEFAULT '0',
  `Created` datetime DEFAULT NULL,
  `Modified` datetime DEFAULT NULL,
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`CategoryID`),
  KEY `Path2Root` (`Path2Root`),
  KEY `StaticPath` (`StaticPath`),
  KEY `PageID` (`PageID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `catalog_item`
--

CREATE TABLE IF NOT EXISTS `catalog_item` (
  `ItemID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PageID` int(10) unsigned NOT NULL DEFAULT '0',
  `SKU` varchar(255) NOT NULL DEFAULT '',
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `ItemImage` varchar(255) DEFAULT NULL,
  `ItemImageConfig` text,
  `FeaturedImage` varchar(255) DEFAULT NULL,
  `FeaturedImageConfig` text,
  `Content` longtext NOT NULL,
  `TitleH1` varchar(255) NOT NULL,
  `MetaTitle` varchar(255) NOT NULL DEFAULT '',
  `MetaKeywords` varchar(255) NOT NULL DEFAULT '',
  `MetaDescription` varchar(255) NOT NULL DEFAULT '',
  `StaticPath` varchar(255) NOT NULL DEFAULT '',
  `ItemDate` date DEFAULT NULL,
  `SortOrder` int(10) unsigned NOT NULL DEFAULT '0',
  `Created` datetime DEFAULT NULL,
  `Updated` datetime DEFAULT NULL,
  `Featured` enum('Y','N') NOT NULL DEFAULT 'N',
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `Price` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ItemID`),
  KEY `StaticPath` (`StaticPath`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `catalog_item2category`
--

CREATE TABLE IF NOT EXISTS `catalog_item2category` (
  `Item2CategoryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ItemID` int(10) unsigned NOT NULL DEFAULT '0',
  `CategoryID` int(10) unsigned NOT NULL DEFAULT '0',
  `SortOrder` int(10) unsigned NOT NULL DEFAULT '0',
  `Real` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`Item2CategoryID`),
  UNIQUE KEY `ItemID` (`ItemID`,`CategoryID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `catalog_media`
--

CREATE TABLE IF NOT EXISTS `catalog_media` (
  `MediaID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ItemID` int(10) unsigned NOT NULL DEFAULT '0',
  `PageID` int(10) unsigned NOT NULL DEFAULT '0',
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `MediaFile` varchar(255) NOT NULL DEFAULT '',
  `MediaFileConfig` text,
  `VideoSnapshot` varchar(255) NOT NULL DEFAULT '',
  `Type` enum('image','video','flash') NOT NULL DEFAULT 'image',
  `SortOrder` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`MediaID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `gallery_category`
--

CREATE TABLE IF NOT EXISTS `gallery_category` (
  `CategoryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PageID` int(10) unsigned NOT NULL DEFAULT '0',
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `CategoryImage` varchar(255) DEFAULT NULL,
  `CategoryImageConfig` text,
  `TitleH1` varchar(255) NOT NULL,
  `MetaTitle` varchar(255) NOT NULL DEFAULT '',
  `MetaKeywords` varchar(255) NOT NULL DEFAULT '',
  `MetaDescription` varchar(255) NOT NULL DEFAULT '',
  `StaticPath` varchar(50) NOT NULL DEFAULT '',
  `Content` longtext NOT NULL,
  `SortOrder` int(10) unsigned NOT NULL DEFAULT '0',
  `Created` datetime DEFAULT NULL,
  `Modified` datetime DEFAULT NULL,
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`CategoryID`),
  UNIQUE KEY `PageID` (`PageID`,`StaticPath`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `gallery_media`
--

CREATE TABLE IF NOT EXISTS `gallery_media` (
  `MediaID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PageID` int(10) unsigned NOT NULL DEFAULT '0',
  `CategoryID` int(10) unsigned DEFAULT NULL,
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `MediaFile` varchar(255) NOT NULL DEFAULT '',
  `MediaFileConfig` text,
  `VideoSnapshot` varchar(255) NOT NULL DEFAULT '',
  `Type` enum('image','video','flash') NOT NULL DEFAULT 'image',
  `SortOrder` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`MediaID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `infoblock_category`
--

CREATE TABLE IF NOT EXISTS `infoblock_category` (
  `CategoryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PageID` int(10) unsigned NOT NULL DEFAULT '0',
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `CategoryImage` varchar(255) DEFAULT NULL,
  `CategoryImageConfig` text,
  `TitleH1` varchar(255) NOT NULL,
  `MetaTitle` varchar(255) NOT NULL DEFAULT '',
  `MetaKeywords` varchar(255) NOT NULL DEFAULT '',
  `MetaDescription` varchar(255) NOT NULL DEFAULT '',
  `StaticPath` varchar(50) NOT NULL DEFAULT '',
  `Content` longtext NOT NULL,
  `SortOrder` int(10) unsigned NOT NULL DEFAULT '0',
  `Created` datetime DEFAULT NULL,
  `Modified` datetime DEFAULT NULL,
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`CategoryID`),
  UNIQUE KEY `PageID` (`PageID`,`StaticPath`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `infoblock_item`
--

CREATE TABLE IF NOT EXISTS `infoblock_item` (
  `ItemID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `PageID` int(10) unsigned NOT NULL DEFAULT '0',
  `CategoryID` int(10) unsigned DEFAULT NULL,
  `ItemDate` datetime DEFAULT NULL,
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `FieldList` longtext,
  `ItemImage` varchar(255) DEFAULT NULL,
  `ItemImageConfig` text,
  `TitleH1` varchar(255) NOT NULL,
  `MetaTitle` varchar(255) NOT NULL DEFAULT '',
  `MetaKeywords` varchar(255) NOT NULL DEFAULT '',
  `MetaDescription` varchar(255) NOT NULL DEFAULT '',
  `StaticPath` varchar(50) NOT NULL DEFAULT '',
  `Content` longtext NOT NULL,
  `SortOrder` int(10) unsigned NOT NULL DEFAULT '0',
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`ItemID`),
  UNIQUE KEY `PageID` (`PageID`,`CategoryID`,`StaticPath`),
  KEY `ItemDate` (`ItemDate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `page`
--

CREATE TABLE IF NOT EXISTS `page` (
  `PageID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `WebsiteID` int(10) unsigned NOT NULL DEFAULT '0',
  `LanguageCode` varchar(2) NOT NULL DEFAULT '',
  `Path2Root` varchar(255) NOT NULL DEFAULT '',
  `MenuImage1` varchar(255) DEFAULT NULL,
  `MenuImage1Config` text,
  `MenuImage2` varchar(255) DEFAULT NULL,
  `MenuImage2Config` text,
  `MenuImage3` varchar(255) DEFAULT NULL,
  `MenuImage3Config` text,
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `TitleH1` varchar(255) NOT NULL,
  `MetaTitle` varchar(255) NOT NULL DEFAULT '',
  `MetaKeywords` varchar(255) NOT NULL DEFAULT '',
  `MetaDescription` varchar(255) NOT NULL DEFAULT '',
  `StaticPath` varchar(50) DEFAULT NULL,
  `Content` longtext NOT NULL,
  `Template` varchar(50) DEFAULT NULL,
  `SortOrder` int(10) unsigned NOT NULL DEFAULT '0',
  `Created` datetime DEFAULT NULL,
  `Modified` datetime DEFAULT NULL,
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `Type` int(10) unsigned NOT NULL DEFAULT '1',
  `Link` varchar(255) DEFAULT NULL,
  `Config` text,
  `Target` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`PageID`),
  UNIQUE KEY `UniqueStaticPath` (`WebsiteID`,`LanguageCode`,`Path2Root`,`StaticPath`),
  KEY `StaticPath` (`StaticPath`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Структура таблицы `session`
--

CREATE TABLE IF NOT EXISTS `session` (
  `SessionID` varchar(32) NOT NULL DEFAULT '',
  `InCookie` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `ExpireDate` datetime DEFAULT NULL,
  `SessionData` text NOT NULL,
  `UserID` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`SessionID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `UserID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Email` varchar(255) DEFAULT NULL,
  `Passwd` varchar(32) NOT NULL DEFAULT '',
  `Name` varchar(100) NOT NULL DEFAULT '',
  `UserImage` varchar(255) DEFAULT NULL,
  `UserImageConfig` text,
  `Phone` varchar(50) NOT NULL DEFAULT '',
  `Role` enum('integrator','administrator','moderator','user') NOT NULL DEFAULT 'user',
  `WebsiteID` int(10) unsigned DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `LastLogin` datetime DEFAULT NULL,
  `LastIP` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Email` (`Email`,`WebsiteID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
