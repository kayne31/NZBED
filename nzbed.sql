CREATE DATABASE  IF NOT EXISTS `nzbed`;
USE `nzbed`;

--
-- Removal of obsolete tables
--

DROP TABLE IF EXISTS `allmusic_album`, `allmusic_albumsearch`, `allmusic_artistsearch`, `googlemusic_album`, `googlemusic_albumsearch`;

--
-- Alter of TVRAGE columns
--

ALTER TABLE `tvrage_episode` CHANGE `tvrageShowID` `tvShowID` INT NOT NULL;
ALTER TABLE `tvrage_search` CHANGE `tvrageShowID` `tvShowID` INT NOT NULL;
ALTER TABLE `tvrage_show` CHANGE `tvrageShowID` `tvShowID` INT NOT NULL;
 
--
-- Table structure for table `anidb_anime`
--

CREATE TABLE IF NOT EXISTS `anidb_anime` (
  `animeID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `anidbID` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `fname` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`animeID`),
  KEY `anidbID` (`anidbID`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;


--
-- Table structure for table `anidb_search`
--

CREATE TABLE IF NOT EXISTS `anidb_search` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `search` varchar(255) NOT NULL DEFAULT '',
  `anidbID` varchar(255) NOT NULL DEFAULT '',
  `fanidb` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `search` (`search`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=latin1;


--
-- Table structure for table `discogs_album`
--

CREATE TABLE IF NOT EXISTS `discogs_album` (
  `albumID` varchar(255) NOT NULL DEFAULT '',
  `artist` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `year` mediumint(9) NOT NULL DEFAULT '0',
  `genre` text NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`albumID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


--
-- Table structure for table `discogs_search`
--

CREATE TABLE IF NOT EXISTS `discogs_search` (
  `search` varchar(255) NOT NULL,
  `albumID` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`search`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `gamespot_game`
--

CREATE TABLE IF NOT EXISTS `gamespot_game` (
  `gsID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gsUrl` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `year` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `genre` varchar(255) NOT NULL DEFAULT '',
  `platform` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`gsID`),
  KEY `gsUrl` (`gsUrl`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;


--
-- Table structure for table `gamespot_search`
--

CREATE TABLE IF NOT EXISTS `gamespot_search` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `search` varchar(255) NOT NULL DEFAULT '',
  `gsUrl` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;


--
-- Table structure for table `imdb_film`
--

CREATE TABLE IF NOT EXISTS `imdb_film` (
  `filmID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `imdbID` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `year` mediumint(9) NOT NULL DEFAULT '0',
  `genre` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `aka` varchar(255) NOT NULL,
  PRIMARY KEY (`filmID`),
  KEY `imdbID` (`imdbID`)
) ENGINE=MyISAM AUTO_INCREMENT=79 DEFAULT CHARSET=latin1;


--
-- Table structure for table `imdb_search`
--

CREATE TABLE IF NOT EXISTS `imdb_search` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `search` varchar(255) NOT NULL DEFAULT '',
  `imdbID` varchar(255) NOT NULL DEFAULT '',
  `fimdbID` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=68 DEFAULT CHARSET=latin1;


--
-- Table structure for table `mbrainz_album`
--

CREATE TABLE IF NOT EXISTS `mbrainz_album` (
  `albumID` varchar(255) NOT NULL DEFAULT '',
  `artist` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `year` mediumint(9) NOT NULL DEFAULT '0',
  `genre` varchar(255) DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`albumID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


--
-- Table structure for table `mbrainz_search`
--

CREATE TABLE IF NOT EXISTS `mbrainz_search` (
  `search` varchar(255) NOT NULL,
  `albumID` varchar(255) NOT NULL,
  PRIMARY KEY (`search`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `query_fail`
--

CREATE TABLE IF NOT EXISTS `query_fail` (
  `queryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `query` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `IP` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `error` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` int(10) unsigned NOT NULL,
  `status` enum('IGNORE','OPEN','FIXED') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'OPEN',
  PRIMARY KEY (`queryID`)
) ENGINE=MyISAM AUTO_INCREMENT=52 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


--
-- Table structure for table `rovi_album`
--

CREATE TABLE IF NOT EXISTS `rovi_album` (
  `albumID` varchar(255) NOT NULL DEFAULT '',
  `artist` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `year` mediumint(9) NOT NULL DEFAULT '0',
  `genre` text NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`albumID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


--
-- Table structure for table `rovi_search`
--

CREATE TABLE IF NOT EXISTS `rovi_search` (
  `search` varchar(255) NOT NULL,
  `albumID` varchar(255) NOT NULL,
  PRIMARY KEY (`search`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `tmdb_film`
--

CREATE TABLE IF NOT EXISTS `tmdb_film` (
  `filmID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tmdbID` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `year` mediumint(9) NOT NULL DEFAULT '0',
  `genre` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `aka` varchar(255) NOT NULL,
  PRIMARY KEY (`filmID`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;


--
-- Table structure for table `tmdb_search`
--

CREATE TABLE IF NOT EXISTS `tmdb_search` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `search` varchar(255) NOT NULL DEFAULT '',
  `tmdbID` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;


--
-- Table structure for table `tvdb_episode`
--

CREATE TABLE IF NOT EXISTS `tvdb_episode` (
  `episodeID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tvdbEpisodeID` int(10) unsigned NOT NULL DEFAULT '0',
  `tvShowID` int(11) NOT NULL,
  `series` varchar(4) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `episode` tinyint(4) NOT NULL DEFAULT '0',
  `date` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`episodeID`),
  KEY `tvdbEpisodeID` (`tvdbEpisodeID`),
  KEY `tvdbShowID` (`tvShowID`,`series`,`episode`)
) ENGINE=MyISAM AUTO_INCREMENT=55 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


--
-- Table structure for table `tvdb_search`
--

CREATE TABLE IF NOT EXISTS `tvdb_search` (
  `searchID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `search` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tvShowID` int(11) NOT NULL,
  `ftvShowID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`searchID`),
  KEY `search` (`search`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


--
-- Table structure for table `tvdb_show`
--

CREATE TABLE IF NOT EXISTS `tvdb_show` (
  `showID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tvShowID` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `genre` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `class` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`showID`),
  KEY `tvdbTextID` (`tvShowID`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



--
-- Table structure for table `tvrage_episode`
--

CREATE TABLE IF NOT EXISTS `tvrage_episode` (
  `episodeID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tvrageEpisodeID` int(10) unsigned NOT NULL DEFAULT '0',
  `tvShowID` int(11) NOT NULL,
  `series` varchar(4) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `episode` tinyint(4) NOT NULL DEFAULT '0',
  `date` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`episodeID`),
  KEY `tvrageEpisodeID` (`tvrageEpisodeID`),
  KEY `tvShowID` (`tvShowID`,`series`,`episode`)
) ENGINE=MyISAM AUTO_INCREMENT=764 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


--
-- Table structure for table `tvrage_search`
--

CREATE TABLE IF NOT EXISTS `tvrage_search` (
  `searchID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `search` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tvShowID` int(11) NOT NULL,
  `ftvrageShowID` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`searchID`),
  KEY `search` (`search`)
) ENGINE=MyISAM AUTO_INCREMENT=158 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


--
-- Table structure for table `tvrage_show`
--

CREATE TABLE IF NOT EXISTS `tvrage_show` (
  `showID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tvShowID` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `genre` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `class` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`showID`),
  KEY `tvrageTextID` (`tvShowID`)
) ENGINE=MyISAM AUTO_INCREMENT=144 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
