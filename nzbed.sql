-- phpMyAdmin SQL Dump
-- version 2.11.8.1deb5+lenny3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 19, 2010 at 02:36 PM
-- Server version: 5.0.51
-- PHP Version: 5.2.6-1+lenny4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `nzbed`
--

-- --------------------------------------------------------

--
-- Table structure for table `allmusic_album`
--

DROP TABLE IF EXISTS `allmusic_album`;
CREATE TABLE IF NOT EXISTS `allmusic_album` (
  `albumID` int(10) unsigned NOT NULL auto_increment,
  `amgalbumID` varchar(255) NOT NULL default '',
  `artist` varchar(255) NOT NULL default '',
  `amgartistID` varchar(255) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `year` mediumint(9) NOT NULL default '0',
  `genre` text NOT NULL,
  `url` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`albumID`),
  KEY `amgalbumID` (`amgalbumID`),
  KEY `amgartistID` (`amgartistID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `allmusic_albumsearch`
--

DROP TABLE IF EXISTS `allmusic_albumsearch`;
CREATE TABLE IF NOT EXISTS `allmusic_albumsearch` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `amgartistID` varchar(255) NOT NULL default '',
  `search` varchar(255) NOT NULL default '',
  `amgalbumID` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`),
  KEY `search` (`search`),
  KEY `amgartistID` (`amgartistID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `allmusic_artistsearch`
--

DROP TABLE IF EXISTS `allmusic_artistsearch`;
CREATE TABLE IF NOT EXISTS `allmusic_artistsearch` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `search` varchar(255) NOT NULL default '',
  `amgartistID` varchar(255) NOT NULL default '',
  `famgartistID` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`),
  KEY `search` (`search`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `music_search`
--

DROP TABLE IF EXISTS `music_search`;
CREATE TABLE IF NOT EXISTS `music_search` (
  `search` varchar(255) NOT NULL,
  `albumID` varchar(255) NOT NULL,
  PRIMARY KEY (`search`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8

-- --------------------------------------------------------

--
-- Table structure for table `music_album`
--

DROP TABLE IF EXISTS `music_album`;
CREATE TABLE IF NOT EXISTS `music_album` (
  `albumID` varchar(255) NOT NULL DEFAULT '',
  `artist` varchar(255) NOT NULL DEFAULT '',
  `artistID` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `year` mediumint(9) NOT NULL DEFAULT '0',
  `genre` text NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`albumID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1

-- --------------------------------------------------------

--
-- Table structure for table `anidb_anime`
--

DROP TABLE IF EXISTS `anidb_anime`;
CREATE TABLE IF NOT EXISTS `anidb_anime` (
  `animeID` int(10) unsigned NOT NULL auto_increment,
  `anidbID` varchar(255) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `fname` varchar(255) NOT NULL default '',
  `type` varchar(255) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`animeID`),
  KEY `anidbID` (`anidbID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `anidb_search`
--

DROP TABLE IF EXISTS `anidb_search`;
CREATE TABLE IF NOT EXISTS `anidb_search` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `search` varchar(255) NOT NULL default '',
  `anidbID` varchar(255) NOT NULL default '',
  `fanidbID` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`),
  KEY `search` (`search`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `gamespot_game`
--

DROP TABLE IF EXISTS `gamespot_game`;
CREATE TABLE IF NOT EXISTS `gamespot_game` (
  `gsID` int(10) unsigned NOT NULL auto_increment,
  `gsUrl` varchar(255) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `year` mediumint(8) unsigned NOT NULL default '0',
  `genre` varchar(255) NOT NULL default '',
  `platform` varchar(255) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`gsID`),
  KEY `gsUrl` (`gsUrl`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `gamespot_search`
--

DROP TABLE IF EXISTS `gamespot_search`;
CREATE TABLE IF NOT EXISTS `gamespot_search` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `search` varchar(255) NOT NULL default '',
  `gsUrl` varchar(255) NOT NULL default '',
  `fgsUrl` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `googlemusic_album`
--

DROP TABLE IF EXISTS `googlemusic_album`;
CREATE TABLE IF NOT EXISTS `googlemusic_album` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `gmalbumID` varchar(255) collate utf8_unicode_ci NOT NULL,
  `artist` varchar(255) collate utf8_unicode_ci NOT NULL,
  `title` varchar(255) collate utf8_unicode_ci NOT NULL,
  `year` mediumint(9) NOT NULL,
  `genre` varchar(255) collate utf8_unicode_ci NOT NULL,
  `url` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `googlemusic_albumsearch`
--

DROP TABLE IF EXISTS `googlemusic_albumsearch`;
CREATE TABLE IF NOT EXISTS `googlemusic_albumsearch` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `search` varchar(255) collate utf8_unicode_ci NOT NULL,
  `gmalbumID` varchar(255) collate utf8_unicode_ci NOT NULL,
  `fgmalbumID` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY `search` (`search`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Google music album search';

-- --------------------------------------------------------

--
-- Table structure for table `imdb_film`
--

DROP TABLE IF EXISTS `imdb_film`;
CREATE TABLE IF NOT EXISTS `imdb_film` (
  `filmID` int(10) unsigned NOT NULL auto_increment,
  `imdbID` varchar(255) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `year` mediumint(9) NOT NULL default '0',
  `genre` varchar(255) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `aka` varchar(255) NOT NULL,
  PRIMARY KEY  (`filmID`),
  KEY `imdbID` (`imdbID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `imdb_search`
--

DROP TABLE IF EXISTS `imdb_search`;
CREATE TABLE IF NOT EXISTS `imdb_search` (
  `ID` int(10) unsigned NOT NULL auto_increment,
  `search` varchar(255) NOT NULL default '',
  `imdbID` varchar(255) NOT NULL default '',
  `fimdbID` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `query_fail`
--

DROP TABLE IF EXISTS `query_fail`;
CREATE TABLE IF NOT EXISTS `query_fail` (
  `queryID` int(10) unsigned NOT NULL auto_increment,
  `type` varchar(255) collate utf8_unicode_ci NOT NULL,
  `query` varchar(255) collate utf8_unicode_ci NOT NULL,
  `IP` varchar(255) collate utf8_unicode_ci NOT NULL,
  `error` varchar(255) collate utf8_unicode_ci NOT NULL,
  `date` int(10) unsigned NOT NULL,
  `status` enum('IGNORE','OPEN','FIXED') collate utf8_unicode_ci NOT NULL default 'OPEN',
  PRIMARY KEY  (`queryID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tvrage_episode`
--

DROP TABLE IF EXISTS `tvrage_episode`;
CREATE TABLE IF NOT EXISTS `tvrage_episode` (
  `episodeID` int(10) unsigned NOT NULL auto_increment,
  `tvrageEpisodeID` int(10) unsigned NOT NULL default '0',
  `tvrageShowID` int(11) NOT NULL,
  `series` varchar(4) collate utf8_unicode_ci NOT NULL default '0',
  `episode` tinyint(4) NOT NULL default '0',
  `date` int(10) unsigned NOT NULL default '0',
  `title` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `url` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  PRIMARY KEY  (`episodeID`),
  KEY `tvrageEpisodeID` (`tvrageEpisodeID`),
  KEY `tvrageShowID` (`tvrageShowID`,`series`,`episode`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tvrage_search`
--

DROP TABLE IF EXISTS `tvrage_search`;
CREATE TABLE IF NOT EXISTS `tvrage_search` (
  `searchID` int(10) unsigned NOT NULL auto_increment,
  `search` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `tvrageShowID` int(11) NOT NULL,
  `ftvrageShowID` int(11) NOT NULL,
  PRIMARY KEY  (`searchID`),
  KEY `search` (`search`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tvrage_show`
--

DROP TABLE IF EXISTS `tvrage_show`;
CREATE TABLE IF NOT EXISTS `tvrage_show` (
  `showID` int(10) unsigned NOT NULL auto_increment,
  `tvrageShowID` int(11) NOT NULL,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `nzbName` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `genre` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `class` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `url` varchar(255) collate utf8_unicode_ci NOT NULL,
  `nzbGenre` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `usenetToTvrage` text collate utf8_unicode_ci NOT NULL,
  `tvrageToNewzbin` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`showID`),
  KEY `tvrageTextID` (`tvrageShowID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tmdb_search`
--

DROP TABLE IF EXISTS `tmdb_search`;
CREATE TABLE IF NOT EXISTS `tmdb_search` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `search` varchar(255) NOT NULL DEFAULT '',
  `tmdbID` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8

-- --------------------------------------------------------

--
-- Table structure for table `tmdb_film`
--

DROP TABLE IF EXISTS `tmdb_film`;
CREATE TABLE IF NOT EXISTS `tmdb_film` (
  `filmID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tmdbID` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `year` mediumint(9) NOT NULL DEFAULT '0',
  `genre` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `aka` varchar(255) NOT NULL,
  PRIMARY KEY (`filmID`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8

