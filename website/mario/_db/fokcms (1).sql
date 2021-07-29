-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Июл 29 2021 г., 11:53
-- Версия сервера: 8.0.26-0ubuntu0.20.04.2
-- Версия PHP: 7.4.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `fokcms`
--

-- --------------------------------------------------------

--
-- Структура таблицы `catalog_category`
--

CREATE TABLE `catalog_category` (
  `CategoryID` int UNSIGNED NOT NULL,
  `PageID` int UNSIGNED NOT NULL DEFAULT '0',
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
  `SortOrder` int UNSIGNED NOT NULL DEFAULT '0',
  `Created` datetime DEFAULT NULL,
  `Modified` datetime DEFAULT NULL,
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `catalog_category`
--

INSERT INTO `catalog_category` (`CategoryID`, `PageID`, `Path2Root`, `Title`, `Description`, `CategoryImage`, `CategoryImageConfig`, `TitleH1`, `MetaTitle`, `MetaKeywords`, `MetaDescription`, `StaticPath`, `Content`, `SortOrder`, `Created`, `Modified`, `Active`) VALUES
(6, 30, '#', 'Пицца', '', 'category-1.png', '{\"Width\":243,\"Height\":247}', '', 'Пицца', '', '', 'pizza', '', 0, '2021-07-28 16:16:43', '2021-07-28 17:51:29', 'Y'),
(7, 30, '#', 'Вок', '', 'category-2.png', '{\"Width\":243,\"Height\":247}', '', 'Вок', '', '', 'vok', '', 1, '2021-07-28 16:25:42', '2021-07-28 17:51:38', 'Y'),
(8, 30, '#', 'Супы', '', 'category-3.png', '{\"Width\":243,\"Height\":247}', '', 'Супы', '', '', 'supy', '', 2, '2021-07-28 16:25:47', '2021-07-28 17:51:47', 'Y'),
(9, 30, '#', 'Горячее', '', 'category-4.png', '{\"Width\":243,\"Height\":247}', '', 'Горячее', '', '', 'goryachee', '', 3, '2021-07-28 16:25:53', '2021-07-28 17:51:55', 'Y'),
(10, 30, '#', 'Салаты', '', 'category-5.png', '{\"Width\":243,\"Height\":247}', '', 'Салаты', '', '', 'salaty', '', 4, '2021-07-28 16:25:59', '2021-07-28 17:52:09', 'Y'),
(11, 30, '#', 'Десерт', '', 'category-6.png', '{\"Width\":243,\"Height\":247}', '', 'Десерт', '', '', 'desert', '', 5, '2021-07-28 16:26:06', '2021-07-28 17:52:19', 'Y'),
(12, 30, '#', 'Напитки', '', 'category-7.png', '{\"Width\":243,\"Height\":247}', '', 'Напитки', '', '', 'napitki', '', 6, '2021-07-28 16:26:13', '2021-07-28 17:52:27', 'Y');

-- --------------------------------------------------------

--
-- Структура таблицы `catalog_item`
--

CREATE TABLE `catalog_item` (
  `ItemID` int UNSIGNED NOT NULL,
  `PageID` int UNSIGNED NOT NULL DEFAULT '0',
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
  `SortOrder` int UNSIGNED NOT NULL DEFAULT '0',
  `Created` datetime DEFAULT NULL,
  `Updated` datetime DEFAULT NULL,
  `Featured` enum('Y','N') NOT NULL DEFAULT 'N',
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `Price` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `catalog_item`
--

INSERT INTO `catalog_item` (`ItemID`, `PageID`, `SKU`, `Title`, `Description`, `ItemImage`, `ItemImageConfig`, `FeaturedImage`, `FeaturedImageConfig`, `Content`, `TitleH1`, `MetaTitle`, `MetaKeywords`, `MetaDescription`, `StaticPath`, `ItemDate`, `SortOrder`, `Created`, `Updated`, `Featured`, `Active`, `Price`) VALUES
(4, 30, '', 'Маргарита', 'Description=шампиньоны, свежие томаты, ананасы, перец сладкий, лук репчатый, орегано, сыр \"Моцарелла\", томатный соус', 'img04.png', '{\"Width\":413,\"Height\":416}', NULL, '{\"Width\":0,\"Height\":0}', '<table style=\"border-collapse:collapse; border-spacing:0px; border:0px; color:rgb(33, 22, 13); font-family:trebuchet ms,sans-serif; font-size:14px; margin:0px 0px 20px; padding:0px; width:320px\">\r\n	<caption style=\"text-align:left\">Пищевая ценность:</caption>\r\n	<tbody>\r\n		<tr>\r\n			<td>Белки</td>\r\n			<td>8,5 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Жиры</td>\r\n			<td>10,4 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Углеводы</td>\r\n			<td>28,3 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Калорийность</td>\r\n			<td>244,4 ккал</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n', '520 г.', 'Маргарита', '', '', 'margarita', '2021-07-28', 0, '2021-07-28 16:21:23', '2021-07-29 11:49:39', 'N', 'Y', '290 руб.'),
(5, 30, '', 'Вок с курицей', 'Description=стеклянная лапша, куриное филе, красный перец, красный лук, чеснок, кунжут, соль, стручковая фасоль, соевый соус', 'category-2.png', '{\"Width\":243,\"Height\":247}', NULL, '{\"Width\":0,\"Height\":0}', '<table style=\"border-collapse:collapse; border-spacing:0px; border:0px; color:rgb(33, 22, 13); font-family:trebuchet ms,sans-serif; font-size:14px; margin:0px 0px 20px; padding:0px; width:320px\">\r\n	<caption style=\"text-align:left\">Пищевая ценность:</caption>\r\n	<tbody>\r\n		<tr>\r\n			<td>Белки</td>\r\n			<td>8,5 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Жиры</td>\r\n			<td>10,4 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Углеводы</td>\r\n			<td>28,3 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Калорийность</td>\r\n			<td>244,4 ккал</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n', '210 г.', 'Вок с курицей', '', '', 'vok-s-kuritsey', '2021-07-28', 0, '2021-07-28 16:35:32', '2021-07-29 11:50:08', 'N', 'Y', '240 руб.'),
(6, 30, '', 'Крем-суп \"Грибной\"', 'Description=крем суп с шампиньонами, картофелем, белым луком и сливками, подается с ароматными гренками', 'category-3.png', '{\"Width\":243,\"Height\":247}', NULL, '{\"Width\":0,\"Height\":0}', '<table style=\"border-collapse:collapse; border-spacing:0px; border:0px; color:rgb(33, 22, 13); font-family:trebuchet ms,sans-serif; font-size:14px; margin:0px 0px 20px; padding:0px; width:320px\">\r\n	<caption style=\"text-align:left\">Пищевая ценность:</caption>\r\n	<tbody>\r\n		<tr>\r\n			<td>Белки</td>\r\n			<td>8,5 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Жиры</td>\r\n			<td>10,4 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Углеводы</td>\r\n			<td>28,3 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Калорийность</td>\r\n			<td>244,4 ккал</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n', '159 г.', 'Крем-суп \"Грибной\"', '', '', 'krem-sup-gribnoy', '2021-07-29', 0, '2021-07-29 11:12:55', '2021-07-29 11:50:13', 'N', 'Y', '200 руб.'),
(7, 30, '', 'Вибо-Валентия', 'Description=спагетти нери в сливочно-томатном соусе с добавлением «том кха», креветки, кальмары, брокколи, каперсы, перец халапеньо, украшается икрой тобико', 'category-4.png', '{\"Width\":243,\"Height\":247}', NULL, '{\"Width\":0,\"Height\":0}', '<table style=\"border-collapse:collapse; border-spacing:0px; border:0px; color:rgb(33, 22, 13); font-family:trebuchet ms,sans-serif; font-size:14px; margin:0px 0px 20px; padding:0px; width:320px\">\r\n	<caption style=\"text-align:left\">Пищевая ценность:</caption>\r\n	<tbody>\r\n		<tr>\r\n			<td>Белки</td>\r\n			<td>8,5 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Жиры</td>\r\n			<td>10,4 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Углеводы</td>\r\n			<td>28,3 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Калорийность</td>\r\n			<td>244,4 ккал</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n', '110 г.', 'Вибо-Валентия', '', '', 'vibo-valentiya', '2021-07-29', 0, '2021-07-29 11:13:45', '2021-07-29 11:50:18', 'N', 'Y', '490 руб.'),
(8, 30, '', 'Гроссето', 'Description=индейка, листья салата, манго, артишоки, помидорки черри, творожный сыр, манговая заправка с добавлением горчицы', 'category-5.png', '{\"Width\":243,\"Height\":247}', NULL, '{\"Width\":0,\"Height\":0}', '<table style=\"border-collapse:collapse; border-spacing:0px; border:0px; color:rgb(33, 22, 13); font-family:trebuchet ms,sans-serif; font-size:14px; margin:0px 0px 20px; padding:0px; width:320px\">\r\n	<caption style=\"text-align:left\">Пищевая ценность:</caption>\r\n	<tbody>\r\n		<tr>\r\n			<td>Белки</td>\r\n			<td>8,5 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Жиры</td>\r\n			<td>10,4 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Углеводы</td>\r\n			<td>28,3 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Калорийность</td>\r\n			<td>244,4 ккал</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n', '220 гр.', 'Гроссето', '', '', 'grosseto', '2021-07-29', 0, '2021-07-29 11:14:27', '2021-07-29 11:50:24', 'N', 'Y', '330 руб.'),
(9, 30, '', 'Пудинг', 'Description=печенье савоярди, сыр маскарпоне, сливки, кофе', 'category-6.png', '{\"Width\":243,\"Height\":247}', NULL, '{\"Width\":0,\"Height\":0}', '<table style=\"border-collapse:collapse; border-spacing:0px; border:0px; color:rgb(33, 22, 13); font-family:trebuchet ms,sans-serif; font-size:14px; margin:0px 0px 20px; padding:0px; width:320px\">\r\n	<caption style=\"text-align:left\">Пищевая ценность:</caption>\r\n	<tbody>\r\n		<tr>\r\n			<td>Белки</td>\r\n			<td>8,5 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Жиры</td>\r\n			<td>10,4 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Углеводы</td>\r\n			<td>28,3 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Калорийность</td>\r\n			<td>244,4 ккал</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n', '120 г.', 'Пудинг', '', '', 'puding', '2021-07-29', 0, '2021-07-29 11:15:04', '2021-07-29 11:50:31', 'N', 'Y', '200 руб.'),
(10, 30, '', 'Кока колла', 'Description=стакан кока колы, лёд, сироп', 'category-7.png', '{\"Width\":243,\"Height\":247}', NULL, '{\"Width\":0,\"Height\":0}', '<table style=\"border-collapse:collapse; border-spacing:0px; border:0px; color:rgb(33, 22, 13); font-family:trebuchet ms,sans-serif; font-size:14px; margin:0px 0px 20px; padding:0px; width:320px\">\r\n	<caption style=\"text-align:left\">Пищевая ценность:</caption>\r\n	<tbody>\r\n		<tr>\r\n			<td>Белки</td>\r\n			<td>8,5 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Жиры</td>\r\n			<td>10,4 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Углеводы</td>\r\n			<td>28,3 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Калорийность</td>\r\n			<td>244,4 ккал</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n', '500 мл', 'Кока колла', '', '', 'koka-kolla', '2021-07-29', 0, '2021-07-29 11:15:43', '2021-07-29 11:50:37', 'N', 'Y', '70 руб'),
(11, 30, '', 'Пепперони', 'Description=фирменный соус, колбаски пепперони, итальянские травы и сыр моцарелла', 'category-1.png', '{\"Width\":243,\"Height\":247}', NULL, '{\"Width\":0,\"Height\":0}', '<table style=\"border-collapse:collapse; border-spacing:0px; border:0px; color:rgb(33, 22, 13); font-family:trebuchet ms,sans-serif; font-size:14px; margin:0px 0px 20px; padding:0px; width:320px\">\r\n	<caption style=\"text-align:left\">Пищевая ценность:</caption>\r\n	<tbody>\r\n		<tr>\r\n			<td>Белки</td>\r\n			<td>18,5 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Жиры</td>\r\n			<td>30,4 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Углеводы</td>\r\n			<td>48,3 г</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Калорийность</td>\r\n			<td>344,4 ккал</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n', '520 г.', 'Пепперони', '', '', 'pepperoni', '2021-07-29', 0, '2021-07-29 11:27:35', '2021-07-29 11:50:02', 'N', 'Y', '390 руб.');

-- --------------------------------------------------------

--
-- Структура таблицы `catalog_item2category`
--

CREATE TABLE `catalog_item2category` (
  `Item2CategoryID` int UNSIGNED NOT NULL,
  `ItemID` int UNSIGNED NOT NULL DEFAULT '0',
  `CategoryID` int UNSIGNED NOT NULL DEFAULT '0',
  `SortOrder` int UNSIGNED NOT NULL DEFAULT '0',
  `Real` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `catalog_item2category`
--

INSERT INTO `catalog_item2category` (`Item2CategoryID`, `ItemID`, `CategoryID`, `SortOrder`, `Real`) VALUES
(4, 4, 6, 1, 'Y'),
(5, 5, 7, 1, 'Y'),
(6, 6, 8, 1, 'Y'),
(7, 7, 9, 1, 'Y'),
(8, 8, 10, 1, 'Y'),
(9, 9, 11, 1, 'Y'),
(10, 10, 12, 1, 'Y'),
(11, 11, 6, 2, 'Y');

-- --------------------------------------------------------

--
-- Структура таблицы `catalog_media`
--

CREATE TABLE `catalog_media` (
  `MediaID` int UNSIGNED NOT NULL,
  `ItemID` int UNSIGNED NOT NULL DEFAULT '0',
  `PageID` int UNSIGNED NOT NULL DEFAULT '0',
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `MediaFile` varchar(255) NOT NULL DEFAULT '',
  `MediaFileConfig` text,
  `VideoSnapshot` varchar(255) NOT NULL DEFAULT '',
  `Type` enum('image','video','flash') NOT NULL DEFAULT 'image',
  `SortOrder` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `catalog_media`
--

INSERT INTO `catalog_media` (`MediaID`, `ItemID`, `PageID`, `Title`, `Description`, `MediaFile`, `MediaFileConfig`, `VideoSnapshot`, `Type`, `SortOrder`) VALUES
(1, 4, 30, 'img04.png', '', 'img04.png', '{\"Width\":413,\"Height\":416}', '', 'image', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `gallery_category`
--

CREATE TABLE `gallery_category` (
  `CategoryID` int UNSIGNED NOT NULL,
  `PageID` int UNSIGNED NOT NULL DEFAULT '0',
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
  `SortOrder` int UNSIGNED NOT NULL DEFAULT '0',
  `Created` datetime DEFAULT NULL,
  `Modified` datetime DEFAULT NULL,
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `gallery_media`
--

CREATE TABLE `gallery_media` (
  `MediaID` int UNSIGNED NOT NULL,
  `PageID` int UNSIGNED NOT NULL DEFAULT '0',
  `CategoryID` int UNSIGNED DEFAULT NULL,
  `Title` varchar(255) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `MediaFile` varchar(255) NOT NULL DEFAULT '',
  `MediaFileConfig` text,
  `VideoSnapshot` varchar(255) NOT NULL DEFAULT '',
  `Type` enum('image','video','flash') NOT NULL DEFAULT 'image',
  `SortOrder` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `infoblock_category`
--

CREATE TABLE `infoblock_category` (
  `CategoryID` int UNSIGNED NOT NULL,
  `PageID` int UNSIGNED NOT NULL DEFAULT '0',
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
  `SortOrder` int UNSIGNED NOT NULL DEFAULT '0',
  `Created` datetime DEFAULT NULL,
  `Modified` datetime DEFAULT NULL,
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Структура таблицы `infoblock_item`
--

CREATE TABLE `infoblock_item` (
  `ItemID` int UNSIGNED NOT NULL,
  `PageID` int UNSIGNED NOT NULL DEFAULT '0',
  `CategoryID` int UNSIGNED DEFAULT NULL,
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
  `StaticPath` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `Content` longtext NOT NULL,
  `SortOrder` int UNSIGNED NOT NULL DEFAULT '0',
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `infoblock_item`
--

INSERT INTO `infoblock_item` (`ItemID`, `PageID`, `CategoryID`, `ItemDate`, `Title`, `Description`, `FieldList`, `ItemImage`, `ItemImageConfig`, `TitleH1`, `MetaTitle`, `MetaKeywords`, `MetaDescription`, `StaticPath`, `Content`, `SortOrder`, `Active`) VALUES
(8, 27, NULL, '2021-07-27 07:41:00', 'Испанские рыбаки провели акцию протеста у Гибралтара', 'Испанские рыбаки заплыли в спорные воды близ Гибралтара в знак протеста против искусственного рифа, установленного правительством этой британской территории. Рыбаки заявляют, что риф ограничивает их право заниматься рыболовством. Между тем власти Гибралтара считают, что они не должны рыбачить в этих водах.', '', '2p3jwljci6.jpg', '{\"Width\":200,\"Height\":200}', '', '', '', '', 'ispanskie-rybaki-proveli-aktsiyu-protesta-u-gibraltara', '<div>Испанские рыбаки провели акцию протеста у Гибралтара</div>\r\n\r\n<p>7 августа 2013г.</p>\r\n\r\n<p>Испанские рыбаки заплыли в спорные воды близ Гибралтара в знак протеста против искусственного рифа, установленного правительством этой британской территории. Рыбаки заявляют, что риф ограничивает их право заниматься рыболовством. Между тем власти Гибралтара считают, что они не должны рыбачить в этих водах.</p>\r\n\r\n<div>\r\n<p>В интервью газете O Globo Паэс сказал, что он готов полностью оплатить затраты на производство фильма.</p>\r\n\r\n<p>По словам мэра, он даже передал записку кинорежиссеру через его нью-йоркского соседа, испанского архитектора Сантьяго Калатавру, который занимается несколькими проектами в Рио.</p>\r\n\r\n<p>Рио-де-Жанейро станет местом проведения чемпионата мира по футболу в следующем году и летних Олимпийских игр-2016.</p>\r\n\r\n<div>\r\n<p>Испанские рыболовецкие суда собрались на акцию протеста около места, где для создания искусственного рифа были сброшены 70 бетонных блоков.</p>\r\n</div>\r\n\r\n<div>\r\n<p>Предполагалось, что протестующие постараются убрать эти блоки, однако уже до протеста рыбаки заявили, что не будут этого делать.</p>\r\n</div>\r\n\r\n<div>\r\n<p>Корреспондент Би-би-си Том Барридж передал с борта одного испанского судна, что акция протеста была &quot;хаотичной и напряженной&quot;: между судами рыбаков сновали катера испанской и гибралтарской полиции.</p>\r\n</div>\r\n\r\n<div>\r\n<p>Согласно главному инспектору Королевской полиции Гибралтара Каслу Йейтсу, всего в протесте участвовало около 38 испанских рыбацких и семь или восемь прогулочных судов.</p>\r\n</div>\r\n\r\n<div>\r\n<p>По его словам, они вошли в территориальные воды Гибралтара, где полиция и Королевской военно-морской флот создали кордон. &quot;Они несколько раз постарались прорвать кордон, но безрезультатно&quot;, - сказал Йейтс.</p>\r\n\r\n<div>\r\n<p>Испанские власти обвиняют Гибралтар в установке рифа &quot;без необходимой для этого авторизации&quot; в водах, &quot;которые им не принадлежат&quot;. Риф расположен у западной взлетно-посадочной полосы гибралтарского аэропорта.</p>\r\n</div>\r\n\r\n<div>\r\n<p>По их словам, проводя строительство рифа, Гибралтар нарушает законодательство о защите окружающей среды и наносит ущерб испанской рыболовецкой индустрии, поскольку бетонные блоки могут повредить сети рыбаков.</p>\r\n</div>\r\n\r\n<div>\r\n<p>Но власти Британии говорит, что они, напротив, пытаются помочь развитию морской экосистемы.</p>\r\n</div>\r\n\r\n<div>\r\n<p>Испания в последние недели ужесточила контроль на границе с Гибралтаром, заявив, что дополнительные проверки необходимы для борьбы с контрабандой сигарет. В результате на границе образовались многочасовые очереди.</p>\r\n</div>\r\n\r\n<div>\r\n<p>В ответ Великобритания обвинила Испанию в нарушении правил ЕС о свободе передвижения. В пятницу британский премьер-министр Дэвид Кэмерон призвал президента Европейской комиссии Жозе Мануэля Баррозу помочь разобраться в этой ситуации. Кэмерон подчеркнул, что дополнительные проверки на границе со стороны Испании являются политически мотивированной и непропорциональной мерой.</p>\r\n</div>\r\n\r\n<div>\r\n<p>Великобритания надеется решить спор посредством &quot;политического диалога&quot;, заявил представитель Даунинг-стрит.</p>\r\n</div>\r\n</div>\r\n</div>\r\n', 1, 'Y'),
(9, 27, NULL, '2021-07-27 08:06:00', 'Мэр Рио уговаривает Вуди Аллена снять фильм в городе', 'Мэр Рио-де-Жанейро Эдуардо Паэс заявил, что он готов сделать все для того, чтобы уговорить американского кинорежиссера Вуди Аллена снять фильм в этом бразильском городе.', '', '5ssbj0307a.jpg', '{\"Width\":200,\"Height\":200}', '', '', '', '', 'mer-rio-ugovarivaet-vudi-allena-snyat-film-v-gorode', '<p>7 августа 2013г.</p>\r\n\r\n<p>Мэр Рио-де-Жанейро Эдуардо Паэс заявил, что он готов сделать все для того, чтобы уговорить американского кинорежиссера Вуди Аллена снять фильм в этом бразильском городе.</p>\r\n\r\n<div>\r\n<p>В интервью газете O Globo Паэс сказал, что он готов полностью оплатить затраты на производство фильма.</p>\r\n\r\n<p>По словам мэра, он даже передал записку кинорежиссеру через его нью-йоркского соседа, испанского архитектора Сантьяго Калатавру, который занимается несколькими проектами в Рио.</p>\r\n\r\n<p>Рио-де-Жанейро станет местом проведения чемпионата мира по футболу в следующем году и летних Олимпийских игр-2016.</p>\r\n</div>\r\n', 2, 'Y');

-- --------------------------------------------------------

--
-- Структура таблицы `page`
--

CREATE TABLE `page` (
  `PageID` int UNSIGNED NOT NULL,
  `WebsiteID` int UNSIGNED NOT NULL DEFAULT '0',
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
  `SortOrder` int UNSIGNED NOT NULL DEFAULT '0',
  `Created` datetime DEFAULT NULL,
  `Modified` datetime DEFAULT NULL,
  `Active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `Type` int UNSIGNED NOT NULL DEFAULT '1',
  `Link` varchar(255) DEFAULT NULL,
  `Config` text,
  `Target` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `page`
--

INSERT INTO `page` (`PageID`, `WebsiteID`, `LanguageCode`, `Path2Root`, `MenuImage1`, `MenuImage1Config`, `MenuImage2`, `MenuImage2Config`, `MenuImage3`, `MenuImage3Config`, `Title`, `Description`, `TitleH1`, `MetaTitle`, `MetaKeywords`, `MetaDescription`, `StaticPath`, `Content`, `Template`, `SortOrder`, `Created`, `Modified`, `Active`, `Type`, `Link`, `Config`, `Target`) VALUES
(30, 1, 'ru', '#6#', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', 'Меню', 'Description=', '', '', '', '', 'menu', '', '', 3, '2021-07-28 13:29:49', '2021-07-29 11:29:49', 'Y', 2, 'catalog', 'CategoryURLPrefix=category&ItemURLPrefix=item&ItemsPerPage=1&MediaPerPage=0&ItemsOrderBy=sortorder_asc&FeaturedProductStaticPath=catalog&ItemDescriptions=Description&CategoryImage=100x100|8|Thumb,500x500|8|Main&CategoryImageKeepFileName=1&ItemImage=100x100|8|Thumb,500x500|8|Main&ItemImageKeepFileName=1&FeaturedImage=100x100|8|Thumb,500x500|8|Main&FeaturedImageKeepFileName=1&MediaFile=100x100|8|Thumb,500x500|8|Main&MediaVideo=400x300&MediaKeepFileName=1&ItemDescriptionCount=1&ffmpeg=/ffmpeg.exe&flvtool2=/flvtool2.exe&mencoder=/mencoder.exe', ''),
(11, 1, 'ru', '#6#', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', 'О доставке', 'Description=', '', '', '', '', 'o-dostavke', '<h2>Как заказать пиццу?</h2>\r\n\r\n<ul>\r\n	<li>Позвонить по телефону: (423) 208-11-11</li>\r\n	<li>Воспользоваться сервисом &laquo;Интернет-заказ&raquo;. Для этого в меню рядом с понравившимися Вам блюдами нажимаете &laquo;В корзину&raquo;, а по окончании выбора &laquo;Оформить заказ&raquo;. При заполнении формы не забудьте оставить номер контактного телефона, по которому наш оператор свяжется с Вами для подтверждения заказа.</li>\r\n	<li>Приехать к нам по адресу: Владивосток, ул. Посьетская, 20</li>\r\n</ul>\r\n\r\n<h2><img alt=\"\" src=\"<P_T_R>website/mario/var/custom/file/img05.jpg\" style=\"height:200px; width:200px\" />Как получить заказ?</h2>\r\n\r\n<ul>\r\n	<li>Наша служба доставки привезет его Вам, по указанному Вами адресу в удобное для Вас время.</li>\r\n	<li>Вы можете забрать заказ самостоятельно по адресу: Владивосток, ул. Посьетская, 20</li>\r\n</ul>\r\n\r\n<h2>Способы оплаты товара</h2>\r\n\r\n<ul class=\"delivery\">\r\n	<li>\r\n	<div class=\"caption\">Наличными</div>\r\n	Оплата наличными курьеру или в ресторане при получении заказа.</li>\r\n	<li>\r\n	<div class=\"caption\">Банковской картой</div>\r\n	При оформлении заказа на сайте (сервис доступен для карт: Visa, MasterCard).</li>\r\n	<li>\r\n	<div class=\"caption\">Наличными</div>\r\n	Оплата наличными курьеру или в ресторане при получении заказа.</li>\r\n	<li>\r\n	<div class=\"caption\">Банковской картой</div>\r\n	При оформлении заказа на сайте (сервис доступен для карт: Visa, MasterCard).</li>\r\n</ul>\r\n', 'page-delivery.html', 1, '2021-07-27 10:04:08', '2021-07-27 13:36:39', 'Y', 1, NULL, NULL, ''),
(27, 1, 'ru', '#6#', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', 'Новости', 'Description=', 'Новость', 'НовостиTitle', '', '', 'news', '', '', 2, '2021-07-27 14:39:11', '2021-07-27 17:00:17', 'Y', 2, 'infoblock', 'CategoryImage=100x100|8|Thumb,500x500|8|Main&CategoryImageKeepFileName=0&ItemsPerPage=1&ItemsOrderBy=ItemDateDesc&ItemImage=100x100|8|Thumb,500x500|8|Main&ItemImageKeepFileName=0&AnnouncementCount=3&AnnouncementOrderBy=ItemDateDesc&Generator=mario&Webmaster=info@localhost&FieldList=&AdminMenuIcon=', ''),
(6, 1, 'ru', '#', '', 'null', '', 'null', '', 'null', 'Верхнее меню', '', '', '', '', '', 'top', '', NULL, 0, '2021-07-26 18:03:09', NULL, 'Y', 0, NULL, NULL, ''),
(18, 1, 'ru', '#15#', 'jpikis7ni0.png', '{\"Width\":25,\"Height\":25}', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', 'Одноклассники', 'Description=', '', '', '', '', NULL, '', NULL, 2, '2021-07-27 11:15:20', NULL, 'Y', 3, 'https://ok.ru/grigoryleps', NULL, '_blank'),
(19, 1, 'ru', '#15#', '7j1cg8a1j5.png', '{\"Width\":25,\"Height\":25}', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', 'facebook', 'Description=', '', '', '', '', NULL, '', NULL, 3, '2021-07-27 11:15:50', NULL, 'Y', 3, 'https://www.facebook.com/people/%D0%94%D0%BC%D0%B8%D1%82%D1%80%D0%B8%D0%B9-%D0%9D%D0%B0%D0%B3%D0%B8%D0%B5%D0%B2/100057365504602/', NULL, ''),
(15, 1, 'ru', '#', '1', 'null', '', 'null', '', 'null', 'Социальные сети', '', '', '', '', '', 'social', '', NULL, 1, '2021-07-27 11:03:18', '2021-07-27 11:04:21', 'Y', 0, NULL, NULL, ''),
(16, 1, 'ru', '#15#', 'ih9tz94b6s.png', '{\"Width\":26,\"Height\":25}', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', 'twitter', 'Description=', '', '', '', '', NULL, '', NULL, 0, '2021-07-27 11:04:10', '2021-07-27 11:13:32', 'Y', 3, 'https://twitter.com/rfedortsov', NULL, '_blank'),
(17, 1, 'ru', '#15#', 'lmzvq2rgg9.png', '{\"Width\":25,\"Height\":25}', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', 'Вконтакте', 'Description=', '', '', '', '', NULL, '', NULL, 1, '2021-07-27 11:14:42', NULL, 'Y', 3, 'https://vk.com/id1', NULL, '_blank'),
(20, 1, 'ru', '#', '', 'null', '1', 'null', '', 'null', 'Проекты', '', '', '', '', '', 'project', '', NULL, 2, '2021-07-27 12:19:55', '2021-07-27 12:20:53', 'Y', 0, NULL, NULL, ''),
(21, 1, 'ru', '#20#', NULL, '{\"Width\":0,\"Height\":0}', '633ac830m2.png', '{\"Width\":112,\"Height\":62}', NULL, '{\"Width\":0,\"Height\":0}', 'Проект 1', 'Description=', '', '', '', '', NULL, '', NULL, 0, '2021-07-27 12:20:44', '2021-07-27 12:32:01', 'Y', 3, 'dddddddddd', NULL, '_blank'),
(22, 1, 'ru', '#20#', NULL, '{\"Width\":0,\"Height\":0}', '5auh4wyhly.png', '{\"Width\":112,\"Height\":62}', NULL, '{\"Width\":0,\"Height\":0}', 'Проект 2', 'Description=', '', '', '', '', NULL, '', NULL, 1, '2021-07-27 12:21:46', '2021-07-27 12:32:04', 'Y', 3, 'dweweweaeaw', NULL, '_blank'),
(25, 1, 'ru', '#6#', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', 'Акции', 'Description=', '', '', '', '', 'index', '', 'page.html', 2, '2021-07-27 12:54:18', NULL, 'Y', 1, NULL, NULL, ''),
(28, 1, 'ru', '#', '', 'null', '', 'null', '', 'null', 'Прочее', '', '', '', '', '', 'other', '', NULL, 3, '2021-07-28 09:40:13', NULL, 'Y', 0, NULL, NULL, ''),
(29, 1, 'ru', '#28#', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', NULL, '{\"Width\":0,\"Height\":0}', 'Где мой заказ?', 'Description=', '', '', '', '', 'gde-moy-zakaz', '', 'page-where.html', 0, '2021-07-28 09:41:22', NULL, 'Y', 1, NULL, NULL, '');

-- --------------------------------------------------------

--
-- Структура таблицы `session`
--

CREATE TABLE `session` (
  `SessionID` varchar(32) NOT NULL DEFAULT '',
  `InCookie` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `ExpireDate` datetime DEFAULT NULL,
  `SessionData` text NOT NULL,
  `UserID` int UNSIGNED DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `session`
--

INSERT INTO `session` (`SessionID`, `InCookie`, `ExpireDate`, `SessionData`, `UserID`) VALUES
('5c85ea73c6f2f187aeaa640b7700fe52', 0, '2021-07-29 17:52:27', 'a:1:{s:12:\"LoggedInUser\";a:12:{s:6:\"UserID\";s:1:\"1\";s:5:\"Email\";s:12:\"test@test.ru\";s:4:\"Name\";s:5:\"admin\";s:9:\"UserImage\";N;s:15:\"UserImageConfig\";N;s:5:\"Phone\";s:0:\"\";s:4:\"Role\";s:10:\"integrator\";s:9:\"WebsiteID\";N;s:7:\"Created\";N;s:9:\"LastLogin\";s:19:\"2021-07-27 09:07:41\";s:6:\"LastIP\";s:3:\"::1\";s:9:\"RoleTitle\";s:22:\"Разработчик\";}}', 1),
('75b7c9291f2bca662189fed2673b3e25', 0, '2021-07-30 11:50:38', 'a:1:{s:12:\"LoggedInUser\";a:12:{s:6:\"UserID\";s:1:\"1\";s:5:\"Email\";s:12:\"test@test.ru\";s:4:\"Name\";s:5:\"admin\";s:9:\"UserImage\";N;s:15:\"UserImageConfig\";N;s:5:\"Phone\";s:0:\"\";s:4:\"Role\";s:10:\"integrator\";s:9:\"WebsiteID\";N;s:7:\"Created\";N;s:9:\"LastLogin\";s:19:\"2021-07-28 09:38:58\";s:6:\"LastIP\";s:3:\"::1\";s:9:\"RoleTitle\";s:22:\"Разработчик\";}}', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `user`
--

CREATE TABLE `user` (
  `UserID` int UNSIGNED NOT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Passwd` varchar(32) NOT NULL DEFAULT '',
  `Name` varchar(100) NOT NULL DEFAULT '',
  `UserImage` varchar(255) DEFAULT NULL,
  `UserImageConfig` text,
  `Phone` varchar(50) NOT NULL DEFAULT '',
  `Role` enum('integrator','administrator','moderator','user') NOT NULL DEFAULT 'user',
  `WebsiteID` int UNSIGNED DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `LastLogin` datetime DEFAULT NULL,
  `LastIP` varchar(15) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;

--
-- Дамп данных таблицы `user`
--

INSERT INTO `user` (`UserID`, `Email`, `Passwd`, `Name`, `UserImage`, `UserImageConfig`, `Phone`, `Role`, `WebsiteID`, `Created`, `LastLogin`, `LastIP`) VALUES
(1, 'test@test.ru', '827ccb0eea8a706c4c34a16891f84e7b', 'admin', NULL, NULL, '', 'integrator', NULL, NULL, '2021-07-29 09:49:50', '::1');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `catalog_category`
--
ALTER TABLE `catalog_category`
  ADD PRIMARY KEY (`CategoryID`),
  ADD KEY `Path2Root` (`Path2Root`),
  ADD KEY `StaticPath` (`StaticPath`),
  ADD KEY `PageID` (`PageID`);

--
-- Индексы таблицы `catalog_item`
--
ALTER TABLE `catalog_item`
  ADD PRIMARY KEY (`ItemID`),
  ADD KEY `StaticPath` (`StaticPath`);

--
-- Индексы таблицы `catalog_item2category`
--
ALTER TABLE `catalog_item2category`
  ADD PRIMARY KEY (`Item2CategoryID`),
  ADD UNIQUE KEY `ItemID` (`ItemID`,`CategoryID`);

--
-- Индексы таблицы `catalog_media`
--
ALTER TABLE `catalog_media`
  ADD PRIMARY KEY (`MediaID`);

--
-- Индексы таблицы `gallery_category`
--
ALTER TABLE `gallery_category`
  ADD PRIMARY KEY (`CategoryID`),
  ADD UNIQUE KEY `PageID` (`PageID`,`StaticPath`);

--
-- Индексы таблицы `gallery_media`
--
ALTER TABLE `gallery_media`
  ADD PRIMARY KEY (`MediaID`);

--
-- Индексы таблицы `infoblock_category`
--
ALTER TABLE `infoblock_category`
  ADD PRIMARY KEY (`CategoryID`),
  ADD UNIQUE KEY `PageID` (`PageID`,`StaticPath`);

--
-- Индексы таблицы `infoblock_item`
--
ALTER TABLE `infoblock_item`
  ADD PRIMARY KEY (`ItemID`),
  ADD UNIQUE KEY `PageID` (`PageID`,`CategoryID`,`StaticPath`),
  ADD KEY `ItemDate` (`ItemDate`);

--
-- Индексы таблицы `page`
--
ALTER TABLE `page`
  ADD PRIMARY KEY (`PageID`),
  ADD UNIQUE KEY `UniqueStaticPath` (`WebsiteID`,`LanguageCode`,`Path2Root`,`StaticPath`),
  ADD KEY `StaticPath` (`StaticPath`);

--
-- Индексы таблицы `session`
--
ALTER TABLE `session`
  ADD PRIMARY KEY (`SessionID`);

--
-- Индексы таблицы `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`,`WebsiteID`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `catalog_category`
--
ALTER TABLE `catalog_category`
  MODIFY `CategoryID` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `catalog_item`
--
ALTER TABLE `catalog_item`
  MODIFY `ItemID` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `catalog_item2category`
--
ALTER TABLE `catalog_item2category`
  MODIFY `Item2CategoryID` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT для таблицы `catalog_media`
--
ALTER TABLE `catalog_media`
  MODIFY `MediaID` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `gallery_category`
--
ALTER TABLE `gallery_category`
  MODIFY `CategoryID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `gallery_media`
--
ALTER TABLE `gallery_media`
  MODIFY `MediaID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `infoblock_category`
--
ALTER TABLE `infoblock_category`
  MODIFY `CategoryID` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `infoblock_item`
--
ALTER TABLE `infoblock_item`
  MODIFY `ItemID` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `page`
--
ALTER TABLE `page`
  MODIFY `PageID` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT для таблицы `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
