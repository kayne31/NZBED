<?php
/**************************************************
 * nzbed
 * Copyright (c) 2008 Harry Bragg
 * tiberious.org
 **************************************************
 *
 * Full GPL License: <http://www.gnu.org/licenses/gpl.txt>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA
 */
 
require_once( 'Numbers/Roman.php' );

class ed
{
	/**
	 * @var array
	 * @access private
	 */	
	var $_def = array(
		'info' => array(
			'url' => array(
				'tvrage' => '/^(?:http:\/\/)?(?:www\.)?tvrage\.com\/(.+?)\/episodes\/(\d+)/i',
				'imdb' => '/^(?:http:\/\/)?(?:www\.)?imdb\.com\/title\/(tt\d+)/i',
				'gamespot' => '/^(?:http:\/\/)?.+?\.gamespot\.com\/(.+\/.+\/.+\/)/i',
				'amg' => '/^(?:http:\/\/)?(?:www\.)?allmusic.com\/album\/([^\/]+)/i',
        'anidb' => '/^(?:http:\/\/)?(?:www\.)?anidb.net\/(?:perl-bin\/animedb.pl?show=anime&aid=)?(a?\d+)/i',
				'gm' => '/^(?:http:\/\/)?(?:www\.)?google\.com\/musicl\?lid=(.+?)(?:&aid=.+?)?$/i',
				),
			'isTV' => '/^(.+?)(?<!the)(?:\.|\s|\s-\s|\(|\[|\_|\-|)(?:\(|\[|\_|\-)?((?:s|series|season|seizoen|saison|staffel)?\s?(?:\.|\_|\-)?\s?([0-9]+?),?\s?\.?(?:e|x|ep|episode|d|dvd|disc|disk|\-)(?!264)\s?(?:\.|\_|\-)?\s?([0-9]{2,3})(?![0-9]+)|(\d{2,4})\.(\d{2})\.(\d{2,4}))(?:\.|\s\-\s|\s|\)|\]|\_|\-)?(.*)$/i',
//			'TV' => array(			
//				'/^(.+?)(?<!the)(?:\.|\s|\s-\s|\(|\[|\_|\-|)(?:\(|\[|\_|\-)?(?:(?:\.|\s|\-)s|series|season)?\s?(?:\.|\_|\-)?\s?([0-9]{1,2}?)(?:,|\-)?\s?\.?(?:e|x|ep|episode)(?!264)\s?([0-9]+)(?![0-9]+)(?:\.|\s\-\s|\s|\)|\]|\_|\-)?(.*)$/i',
//				'/^(.+?)(?<!the)(?:\.|\s|\s-\s|\(|\[|\_|\-|)(?:\(|\[|\_|\-)?(?:(?:\.|\s|\-)s|series|season)?\s?(?:\.|\_|\-)?\s?([0-9]{1,2}),?\s?\.?(?:\-|e|x|ep|episode)?\s?([0-9]{2})(?![0-9]+)(?:\.|\s\-\s|\s|\)|\]|\_|\-)?(.*)$/i',
//				),
//			'TVMulti' => array(
//                '/^(.+?)(?<!the)(?:\.|\s|\s-\s|\(|\[|\_|\-|)(?:\(|\[|\_|\-)?(?:s|series|season|staffel)?\s?([0-9]{1,2}+),?\s?\.?\s?([0-9ex_\-]{5,})(?![0-9]+)(?:\.|\s\-\s|\s|\)|\]|\_|\-)?(.*)$/i',
//                ),
      'TVMsplit' => '/(\d{2,})/i',
//			'TVDVD' => array(
//				'/^(.+?)(?<!the)(?:\.|\s|\s-\s|\(|\[|\_|\-|)(?:\(|\[|\_|\-)?(?:s|series|season|seizoen|staffel)?\s?(?:\.|\_|\-)?\s?([0-9]{1,2}?),?\s?\.?(?:\(|\[|\_|\-)?,?\s?\.?(?:d|dvd|disc|disk)\s?(?:\.|\_|\-)?\s?([0-9]+)(?![0-9]+)(?:\.|\s\-\s|\s|\)|\]|\_|\-)?(.*)$/i'
//				),
//            'TVDate' => array(
//                '/^(.+?)(?<!the)(?:\.|\s|\s-\s|\(|\[|\_|\-|)(?:\(|\[|\_|\-)?\s*(\d{2,4}+)(?:\.|\-)(\d{2})(?:\.|\-)(\d{2,4}+)(?:\.|\s\-\s|\s|\)|\]|\_|\-)?(.*)$/i',
//                ),
//				'TVPart' => array(
//					'/^(.+?)(?<!the)\s+(?:pt|part)\s*([0-9]{1,2}|[IVX]+)\s*(.*)$/i',
//					),																 
			'isRoman' => '/[IVX]+/i',
			'TV' => array(
				'Date' => array(
					'/^(.+?)(?<!the)\s*(\d{2,4}+)(?:\.|\s)(\d{2})(?:\.|\s)(\d{2,4}+)\s*(.*)$/i',
					),
				'Multi' => array(
					'/^(.+?)(?<!the)\s+(?:s|series|season|saison|staffel)?\s*([0-9]{1,2}+)\s*([0-9ex_\-\s]{5,})(?![0-9]+)\s*(.*)$/i',
					),
				'TV' => array(			
					'/^(.+?)(?<!the)\s+(?:s|series|season|saison|staffel)?\s*([0-9]{1,2}?)\s*(?:e|x|ep|episode)(?!264)\s*([0-9]+)(?![0-9]+)\s*(.*)$/i',
					'/^(.+?)(?<!the)\s+(?:s|series|season|saison|staffel)?\s*([0-9]{1,2})\s*(?:\-|e|x|ep|episode)?\s*([0-9]{2})(?!p|[0-9]+)\s*(.*)$/i',
					),
				'DVD' => array(
					'/^(.+?)(?<!the)\s+(?:s|series|season|seizoen|saison|staffel)\s*([0-9]{1,2}?)\s*(?:d|dvd|disc|disk)\s*([0-9]+)(?![0-9]+)\s*(.*)$/i'
					),				
				'Part' => array(
					'/^(.+?)(?<!the)\s+(?:pt|part)\s*([0-9]{1,2}|[IVX]+)\s*(.*)$/i',
					),
//				'Game' => array(
//					'/^(.+?)(?<!the)\s+(?:g|game)\s*(\d+)\s*(.*)$/i',
//					),
				'Series' => array(
					'/^(.+?)(?<!the)\s+(?:s|series|season|saison|staffel)\s*([0-9]{1,2})\s*(?!e|x|ep|episode)\s+(.*)$/i',
					),
				),
			'Music' => array(
				'/^(.+?)\s*(?:-\s*\d{4}\s*)?-\s*(.+)$/'
				),
            'Anime' => array(
                '/(.+?)\s+-ep.(\d+)(.+?)?$/i',
                '/(.+?)\s*(?:[-_\.]|\s*)(?:ep(?:isode)?)?\s*[-_\.]?\s*((?:\d+\s?-\s?)?\d+)(.+?)?$/i'                
                ),
            'epSplit' => '/(\d+)\s*-\s*(\d+)/i',
			),
		'addPart' => array(
			'from' => array(
				'/ \((\d+)\)$/i', 		//	(1)
				'/,? Part (\d+)$/i',		//	, Part 1
				'/-? Part (\d+)$/i',		//	- Part 1
				'/,? Part ([A-Z]{1,5})+$/i',	//	, Part I
				'/-? Part ([A-Z]{1,5})+$/i'	//	- Part I
				),
			'to' => " (Part $1)"
			),
		'getPart' => '/^(.+) \(Part (\d+)\)$/i',
		'attributes' => array(
			'Region' => array(
				'NTSC' => '/\b(ntsc|usa)\b/i',
				'PAL' => '/\b(pal|eur)\b/i',
				'SECAM' => '/\bsecam\b/i'
				),
			'Source' => array(
				'CAM' => '/\bcam\b/i',
				'Screener' => '/(dvd[.-]?scr|screener)/i',
				'TeleCine' => '/\btc\b/i',
				'R5 Retail' => '/\br5/i',
				'TeleSync' => '/\bts\b/i',
				'VHS' => '/vhs/i',
				'HDTV' => '/(hdtv|\.ts(?!\.))/i',
				'DVD' => '/dvd/i',
				'TV Cap' => '/(tvrip|pdtv|dsr|dvb|sdtv|dtv|satrip)/i',
				'HD-DVD' => '/hd[-.]?dvd/i',
				'Blu-ray' => '/(blu[-. ]?ray|b(d|r|rd)[-.]?(rom|rip))/i',
				'Web-DL' => '/(web[-. ]?dl|hditunes|ituneshd|ithd|webhd)/i'
				),
			'Format' => array(
				'XviD' => '/xvid/i',
                'DVD' => '/dvd(?!rip?.)/i',
				'H.264/x264' => '/((h.?264|x264|avc))/i',
				'AVCHD' => '/avchd/i',
				'HD .TS' => '/\.ts(?!\.)/i',
				'SVCD' => '/svcd/i',
				'VCD' => '/mvcd/i',
				'DivX' => '/divx/i',
				'WMV' => '/w(mv|vc1)/i',
				'ratDVD' => '/ratDVD/i',
				'720p' => '/(720p|\.?720)/i',
				'1080i' => '/1080i/i',
				'1080p' => '/1080p/i',
				'PSP' => '/psp/i',
				'iPod' => '/\b(ipod|iphone|itouch)\b/i',
				),
			'Audio' => array( 
				'AC3/DD' => '/(ac3|dd[25]\.?[01]|5\.1)/i',
				'dts' => '/dts/i',
				'MP3' => '/mp3/i',
				'AAC' => '/aac/i',
				'Ogg' => '/\bogg\b/i',
				'Lossless' => '/(flac|lossless)/i',
				),
			'ConsolePlatform' => array(
				'Xbox' => '/Xbox/i',
				'Xbox360' => '/X(box)?(\.|\-|_|)360/i',
				'Wii' => '/Wii/i',
				'PS3' => '/PS3/i',
				'PS2' => '/PS2/i',
				'PSP' => '/PSP/i',
				'N64' => '/N(intendo)?(\.|\-|_|)64/i',
				'GameCube' => '/(GC|GameCube)/i',
				'Nintendo DS' => '/(\.|\-|_)DS/i',
				'GB Colour' => '/GB Colou?r/i',
				'GB Advance' => '/GB Advance/i',
				'Dreamcast' => '/(DC|DreamCast)/i'
				),
			'Media' => array(
				'DVD Image' => '/DVD/i',
				'CD Image' => '/(!<?clone)CD/i',
				'CloneCD' => '/Clone(\.|\-|_|)CD/i',
				'Alcohol' => '/Alcohol(\.|\-|_|)120%/i'
				),
			'Language' => array(
				'English' => '/((DL)|(multi(5|3)))/i',
				'French' => '/((french)|(multi5))/i',
				'German' => '/((\.+DL\.+)|(german(?!.sub?.))|(deutsch)|(multi(5|3)))/i',
				'Spanish' => '/((spanish)|(multi5))/i',
				'Italian' => '/((italian)|(multi(5|3)))/i',
				'Dutch' => '/((dutch))/i',
                'Polish' => '/((\.+PL\.+))/i'
				),

            'Anime' => array(
                'TV' => '/TV Series/i',
                'OVA' => '/OVA/i',
                ),
            'Subtitle' => array(
                'French' => '/((vostfr)|(vost))/i',
                'German' => '/(german.sub)/i',
                'Dutch' => '/((nlsubs)|(nl.?subbed))/i'
                ),
			),
		'filmMatch' => array(
			'/dvd.+/i',
			'/proper.+/i',
			'/iNTERNAL.*/',
			'/WS/',
			'/HR/'
			),
		'strip' => array( '.', '-', '(', ')', '_', '#', '[', ']','"' ),
		'musicStrip' => array( '.', '(', ')', '_', '#', '[', ']' ),
		'musicReplace' => array(
			'from' => array(
				'/\bvs\b\.?/i',
				'/\-/',
			),
			'to' => array(
				'vs.',
				' - ',
			),
		),
		'siteAttributes' => array(
			'videogenre' => array(
				'Action' => 'Action/Adv',
				'Adventure' => 'Action/Adv',
				'Celebrities' => 'Reality',
				'Dance' => 'Reality',
				//'Discovery/Science' => 'Documentary',
				'Lifestyle' => 'Reality',
				//'Medical' => 'Documentary',
				'Military' => 'War',
				//'Politics' => 'Documentary',
				'Religion' => 'Family',
				'Soaps' => 'Comedy',
				'Sports' => 'Sport',
				'Talent' => 'Reality',
				'Teens' => 'Family',
				),
			'class' => array(
				'Animation' => 'Animation',
				'Reality' => 'Reality',
				'Documentary' => 'Documentary'
				),
			'gamegenre' => array(
				'sports' => 'Sport',
				'action' => 'Action',
				'First-Person' => 'FPS',
				'driving' => 'Racing',
				'rpg' => 'RPG',
				'strategy' => 'Strategy',
				'puzzle' => 'Puzzle',
				'adventure' => 'Adventure',
				'sim' => 'Simulator',
				'Sim' => 'Simulator'
				),
			'consoleplatform' => array(
				'xbox360' => 'Xbox360',
				'ps3' => 'PS3',
				'wii' => 'Wii',
				'ps2' => 'PS2',
				'xbox' => 'Xbox',
				'gc' => 'GameCube',
				'psp' => 'PSP',
				'ds' => 'Nintendo DS',
				'gba' => 'GB Advance'
				),
			'audiogenre' => array(
				'Blues' => 'Blues/Jazz/R&B',
				'Jazz' => 'Blues/Jazz/R&B',
				'R&B' => 'Blues/Jazz/R&B',
				'Electro' => 'Electro/Techno',
				'Techno' => 'Electro/Techno',
				'Electronica' => 'Electro/Techno',
				'Goth' => 'Goth/Industrial',
				'Industrial' => 'Goth/Industrial',
				'Rap' => 'Rap/HipHop',
				'HipHop' => 'Rap/HipHop',
				'Rock' => 'Rock/Pop',
				'Pop' => 'Rock/Pop',
				'Pop/Rock' => 'Rock/Pop'
				),
            ),
		'report' => array(
			'fields' => array(
				'title' => 'ps_title',
				'url' => 'ps_url',
				'category' => 'ps_category',
                'notes' => 'ps_editor_notes'
				),
			'category' => array(
				'Movies' => 6,
				'TV' => 8,
				'Consoles' => 2,
				'Games' => 4,
				'Music' => 7,
                'Anime' => 11,
				),
			'categoryGroups' => array(
				'Movies' => array( 'Format', 'Source', 'VideoGenre', 'Audio', 'Region', 'Language', 'Subtitle' ),
				'TV' => array( 'Format', 'Source', 'VideoGenre', 'Region', 'Language', 'Subtitle' ),
				'Consoles' => array( 'Region', 'GameGenre', 'ConsolePlatform', 'Media', 'Language' ),
				'Games' => array( 'Media', 'GameGenre', 'Language' ),
				'Music' => array( 'Audio', 'AudioGenre' ),
                'Anime' => array( 'Anime', 'Format', 'Language', 'Subtitle' ),
				'All' => array( 'Format', 'Source', 'VideoGenre', 'Audio', 'Region', 'Media', 'ConsolePlatform', 'GameGenre', 'AudioGenre', 'Language', 'Anime', 'Subtitle' ),
				),
			'attributeGroups' => array(
				'Format' => 'ps_rb_video_format',
				'Source' => 'ps_rb_source',
				'VideoGenre' => 'ps_rb_video_genre',
				'Audio' => 'ps_rb_audio_format',
				'Region' => 'ps_rb_region',
				'Media' => 'ps_rb_media',
				'ConsolePlatform' => 'ps_rb_platform_console',
				'GameGenre' => 'ps_rb_game_genre',
				'AudioGenre' => 'ps_rb_audio_genre',
				'Language' => 'ps_rb_language',
                'Anime' => 'ps_rb_anime',
                'Subtitle' => 'ps_rb_subtitle'
				),
			'attributeID' => array(
				'Source' => array(
					'CAM' => 1,
					'Screener' => 2,
					'TeleCine' => 4,
					'R5 Retail' => 1024,
					'TeleSync' => 8,
					'VHS' => 32,
					'HDTV' => 128,
					'DVD' => 64,
					'TV Cap' => 256,
					'HD-DVD' => 512,
					'Blu-ray' => 2048,
					'Web-DL' => 4096
					),
				'Format' => array(
					'XviD' => 16,
					'H.264/x264' => 131072,
					'HD .TS' => 32,
					'SVCD' => 4,
					'VCD' => 8,
					'DivX' => 1,
					'WMV' => 64,
					'ratDVD' => 256,
					'DVD' => 2,
					'720p' => 524288,
					'1080i' => 1048576,
					'1080p' => 2097152,
					'PSP' => 1024,
					'HD-DVD' => 65536,
					'Blu-ray' => 262144,
					'AVCHD' => 4096,
					'iPod' => 512,
					),
				'VideoGenre' => array(
					'Action/Adv' => 1,
					'Animation' => 2,
					'Children' => 131072,
					'Comedy' => 4,
					'Crime' => 64,
					'Documentary' => 8,
					'Drama' => 16,
					'Family' => 8192,
					'Fantasy' => 2048,
					'Horror' => 512,
					'Musical' => 16384,
					'Mystery' => 262144,
					'Sci-Fi' => 32,
					'Sport' => 128,
					'Reality' => 256,
					'Romance' => 1024,
					'Thriller' => 4096,
					'War' => 65536,
					'Western' => 32768,
					),
				'Audio' => array(
					'AC3/DD' => 1,
					'dts' => 128,
					'MP3' => 8,
					'AAC' => 512,
					'Ogg' => 16,
					'Lossless' => 2
					),
				'Region' => array(
					'PAL' => 1,
					'NTSC' => 2,
					'SECAM' => 4
					),
				'Media' => array(
					'CD Image' => 2,
					'Alcohol' => 16,
					'CloneCD' => 8,
					'DVD Image' => 1
					),
				'ConsolePlatform' => array(
					'Dreamcast' => 2048,
					'GB Advance' => 64,
					'GB Colour' => 65536,
					'Nintendo DS' => 131072,
					'GameCube' => 1024,
					'N64' => 32,
					'Playstation' => 4096,
					'PS2' => 8192,
					'PS3' => 524288,
					'PSP' => 16384,
					'Wii' => 1048576,
					'Xbox' => 32768,
					'Xbox360' => 262144
					),
				'GameGenre' => array(
					'Action' => 1,
					'Adventure' => 2,
					'FPS' => 256,
					'Puzzle' => 8,
					'Racing' => 4,
					'RPG' => 16,
					'Simulator' => 32,
					'Sport' => 64,
					'Strategy' => 128				
					),
				'AudioGenre' => array(
					'Blues/Jazz/R&B' => 1,
					'Classical' => 2,
					'Country' => 4,
					'Dance' => 8,
					'Electro/Techno' => 16,
					'Folk' => 32,
					'Goth/Industrial' => 64,
					'Metal' => 128,
					'Punk' => 16384,
					'Radio' => 256,
					'Rap/HipHop' => 512,
					'Reggae' => 2048,
					'Rock/Pop' => 2048,
					'Soundtrack' => 4096
					),
				'Language' => array(
					'English' => 4096,
					'French' => 2,
					'Spanish' => 8,
					'German' => 4,
					'Italian' => 512,
					'Danish' => 16,
					'Dutch' => 32,
					'Japanese' => 64,
					'Cantonese' => 1024,
					'Mandarin' => 131072,
					'Korean' => 128,
					'Russian' => 256,
					'Polish' => 2048,
					'Vietnamese' => 8192,
					'Swedish' => 16384,
					'Norwegian' => 32768,
					'Finnish' => 65536,
					'Turkish' => 262144,
					),
                'Anime' => array(
                    'Game' => 1,
                    'Music' => 4,
                    'Movie' => 2,
                    'OVA' => 8,
                    'TV' => 16,
                    'Hentai' => 32,
                    ),
                'Subtitle' => array(
                    'English' => 4096,
                    'French' => 2,
                    'Spanish' => 8,
                    'German' => 4, 
                    'Italian' => 512,
                    'Danish' => 16,
                    'Dutch' => 32,
                    'Japanese' => 64,
                    'Chinese' => 1024,
                    'Korean' => 128,
                    'Russian' => 256,
                    'Polish' => 2048,
                    'Vietnamese' => 8192,
                    'Swedish' => 16384,
                    'Norwegian' => 32768,
                    'Finnish' => 65536,
                    'Turkish' => 262144,
                    ),
				)
			),
			'attributeExclude' => array(
				'HD-DVD' => array( 'DVD', 'TV Cap' ),
				'Blu-ray' => array( 'DVD', 'TV Cap' ),
				'DVD' => array( 'TV Cap' ),
				'CAM' => array( 'TV Cap' ),
				'TeleCine' => array( 'DVD', 'TV Cap' ),
				'TeleSync' => array( 'DVD', 'TV Cap' ),
				'AAC' => array( 'AC3/DD' ),
				'SVCD' => array( 'XviD', 'DivX' ),
				'Xbox360' => array( 'Xbox' ),
				'AVCHD' => array( 'H.264/x264', 'Blu-ray', 'HD-DVD' ),
				'H.264/x264' => array( 'Blu-ray', 'HD-DVD' ),
			)
		);
	
	var $ids;
	var $ignoreCache;
	
  var $debug = false;
	
	function ed( $ids = false, $ignoreCache = false )
	{
		$this->ids = $ids;
		$this->ignoreCache = $ignoreCache;
	}

	/*****************************************************
	 * Main functions
	 *****************************************************/
	
	function Query( $string, $type = false )
	{
		// let check it against known urls
		if ( $this->debug ) echo 'Query: '.$string."\n";
		
		foreach( $this->_def['info']['url'] as $name => $regex )
		{
			if ( preg_match( $regex, $string ) )
			{
				if ( $this->debug ) echo 'URL found: '.$name."\n";
				switch ( $name )
				{
					case 'tvrage':
						return $this->tvQuery( $string );
					case 'imdb':
						return $this->filmQuery( $string );
					case 'gamespot':
						return $this->gameQuery( $string, 2 );
					case 'amg':
						return $this->musicQuery( $string );
					case 'gm':
						return $this->musicQuery( $string );
                    case 'anidb':
                        return $this->animeQuery( $string );
				}
			}
		}
		
		
		if ( ( $type == 0 ) || 
		     ( $type == false ) ||
			 ( $type == 6 ) )
		{
			if ( preg_match( $this->_def['info']['isTV'], $string, $matches ) )
			{
				if ( $this->debug ) printf("Detected TV: [regex: %s]\n", $this->_def['info']['isTV'] );
				$type = 'TV';
			}
			else
			{
				$type = 'Movies';
			}
		}
		
		if ( !is_numeric( $type ) )
		{
			$id = $this->_def['report']['category'][$type];
		}
		else
		{
			$id = $type;
		}
		
		switch ( $id )
		{
			case 8:
				return $this->tvQuery( $string );
			case 6:
				return $this->filmQuery( $string );
			case 7:
				return $this->musicQuery( $string );
			case 4:
			case 2:
				return $this->gameQuery( $string, $type );
            case 11:
                return $this->animeQuery( $string );
			default:
				if ( $type > 20 )
				{
					return $this->dumbQuery( $string, substr( $type, 0, -1 ) );
				}
				else
				{
					$this->_error = 'No Category Determined, please select Category manually';
					return false;
				}
		}
	}
	
	function tvQuery( $string )
	{
		global $api;
		
		if ( $this->debug ) printf("tvQuery( string:%s )\n", $string );
		
		// $string = str_replace( $this->_def['strip'], ' ', $string );
		
		// check for a url
		if ( preg_match( $this->_def['info']['url']['tvrage'], $string, $matches ) )
		{
			// XML: this line needs to be uncommented
			$showquery = str_replace( $this->_def['strip'], ' ', $matches[1] );
			//$showquery = $matches[1];
			if ( ( $show = $api->tvrage->getShow( $showquery, $this->ignoreCache, true ) ) !== false )
			{		
				if ( ( $ep = $api->tvrage->getIDEpisode( $show->tvrageShowID, $matches[2], $this->ignoreCache ) ) !== false )
				{
					// search for episode properties
					return $this->tvGetReport( $show, $ep );
				}
				else
				{
					$this->_error = sprintf( 'Could not find episodeID: %d for show: %s', $matches[2], $matches[1] );
					return false;
				}
			}
			else
			{
				$this->_error = sprintf( 'Invalid tvrage show ID: %s', $matches[1] );
				return false;
			}
		}
		
		$stripedString = str_replace( $this->_def['strip'], ' ', $string );
		
		if ( $this->debug ) printf("stripedString = %s\n", $stripedString );
		
		$typematches = array();
		$typematched = array();
		
		if ( isset( $this->_def['info']['TV'] ) )
		{
			foreach( $this->_def['info']['TV'] as $name => $arr )
			{
				foreach ( $arr as $reg )
				{
					if ( $this->debug ) printf( "regex: %s\n", $reg );
					if ( preg_match( $reg, $stripedString, $matches ) )
					{
						$typematches[$name] = $matches;
						$typematched[$name] = true;
						$hasmatched = true;
						if ( $this->debug )
						{
							printf( "regex search: %s Found %s: \n", $reg, $name );
							var_dump( $matches );
						}
						break;
					}
				}
			}
		}
	
		
		if ( !$hasmatched )
		{
			return $this->dumbQuery( $string, 8 );
		}
		
		if ( $typematched['Multi'] )
		{		
        // do possibiliy checks
			$min = 9999;
			$max = -1;
			$thresh = 10;
	
			// split up the episodes
			preg_match_all( $this->_def['info']['TVMsplit'], $typematches['Multi'][3], $epList );

			if ( $this->debug ) print_r( $epList );
			
			if ( count( $epList[1] ) == 0 )
			{
				if ( $this->debug ) printf(" no episode range found, not Multi\n");
				$typematched['Multi'] = false;
			}
			else
			{	
				$min = Min($epList[1]);
				$max = Max($epList[1]);
		
				if ( $max-$min > $thresh )
				{
					if ( $this->debug ) printf(" range plausability check failed, %d-%d\n", $min, $max );
					$typematched['Multi'] = false;
				}
			}
		}
		
		if ( $typematched['Date'] )
		{
			if ( ( ( $typematches['Date'][2] > 100 ) &&
					 ( ( $typematches['Date'][3] > 12 ) ||
						 ( $typematches['Date'][4] > 31 ) ||
						 ( $typematches['Date'][2] > date('Y')+1 ) ||
						 ( $typematches['Date'][2] < 1900 ) ) ) ||
					 ( ( $typematches['Date'][2] < 100 ) &&
						 ( ( $typematches['Date'][2] > 12 ) ||
							 ( $typematches['Date'][3] > 31 ) ||
							 ( $typematches['Date'][4] > date('y')+1 ) ) ) )
			{
				if ( $this->debug ) printf(" date plausability check failed, %d/%d/%d\n", $matches[2], $matches[3], $matches[4] );
				$typematched['Date'] = false;
			}
		}
		
		foreach( $typematched as $name => $isMatch )
		{
			if ( $isMatch )
			{
				$matches = $typematches[$name];
				$mUsed = $name;
				break;
			}
		}
        
		// using tv rage to parse the information
		
//		$showquery = str_replace( $this->_def['strip'], ' ', $matches[1] );
		$showquery = $matches[1];
		
		if ( ( $show = $api->tvrage->getFShow( $showquery, $this->ignoreCache ) ) !== false )
		{
			if ( $this->debug ) var_dump( $show );
			
			if ( $mUsed == 'Date' )
			{
				if ( $matches[2] >= 100 )
				{
					$date = mktime( 0,0,0, $matches[3], $matches[4], $matches[2] );
				}			                                                                                                
				else			                                                                                                                
				{                                                                                                                                
					// dumb ass american date standard
					$date = mktime( 0,0,0, $matches[2], $matches[3], $matches[4] );
				}
				if ( $this->debug ) printf(" Found Date: %s\n", date('d/m/Y', $date ) );
				
				// get ID
				if ( ( $ep = $api->tvrage->getDateEpisode( $show->tvrageShowID, $date, $this->ignoreCache ) ) !== false )
				{                                                                                                                              
					if ( $this->debug ) var_dump( $ep );                                                                                                                 
					if ( $ep->date == 0 )
					{                     
						$ep->date = $date;                          
					}                                                                     
					return $this->tvGetReportDate( $show, $ep, $matches[5] );
				}                                                                                                                           
				else                                                                                                                                        
				{                                                                                                                                                               
					if ( $this->debug ) printf(" Unable to find episode from date %d/%d/%d", $matches[2], $matches[3], $matches[4] );
					$ep = (object)array(
						'date' => $date,
						'title' => '',
						'url' => sprintf( '%s', $show->url ) );
					return $this->tvGetReportDate( $show, $ep, $matches[5] );
				}                                                                                                                    
			}
			
			else if ( $mUsed == 'TV' )
			{
			
			// if standard naming scheme 
//				list( $nSeries, $nEpisode ) = $this->tvNewSEp( $show, $matches[2], $matches[3], true ); 
				if ( ( $ep = $api->tvrage->getEpisode( $show->tvrageShowID, $matches[2], $matches[3], 
					$this->ignoreCache ) ) !== false )
				{
					if ( $this->debug ) var_dump( $ep ); 
					// search for episode properties
					return $this->tvGetReport( $show, $ep, $matches[4] );
				}
				else
				{
					$ep = (object)array(
						'series' => sprintf("%d", $matches[2] ),
						'episode' => $matches[3],
						'title' => sprintf( 'Season %d, Episode %d', $matches[2], $matches[3] ),
						'url' => sprintf( '%s/episode_list/%d', $show->url, $matches[2] ) );
					if ( $this->debug ) var_dump( $ep );				
					return $this->tvGetReport( $show, $ep, $matches[4] );
				}
			}
			else if ( $mUsed == 'DVD' )
			{
//				list( $nSeries, $nEpisode ) = $this->tvNewSEp( $show, $matches[2], false, true );
//				list( $rSeries, $rEpisode ) = $this->tvNewSEp( $show, $nSeries, false, false );			
				$ep = (object)array(
					'series' => $matches[2],
					'episode' => $matches[3],
					'url' => sprintf( '%s/episode_list/%d', $show->url, $matches[2] ) );
				return $this->tvGetReportDVD( $show, $ep, $matches[4] );
			}
			else if ( $mUsed == 'Multi' )
			{
				$min = 9999;
				$max = -1;
				
				// split up the episodes
				preg_match_all( $this->_def['info']['TVMsplit'], $matches[3], $epList );
				
				if ( $this->debug ) print_r( $epList );
				
				$min = Min($epList[1]);    
				$max = Max($epList[1]);
				
				for ( $i = $min; $i <= $max; $i++ )
				{
	//				list( $nSeries, $nEpisode ) = $this->tvNewSEp( $show, $matches[2], $i, true );
					if ( ( $tep = $api->tvrage->getEpisode( $show->tvrageShowID, $matches[2], $i, $this->ignoreCache ) ) !== false )
					{
						if ( $this->debug ) var_dump( $tep ); 
						// search for episode properties
						$ep[] = $tep;
					}
					else
					{
						$tep = (object)array(
							'series' => $matches[2],
							'episode' => $i,
							'title' => sprintf( 'Season %d, Episode %d', $matches[2], $i ),
							'url' => sprintf( '%s/episode_list/%d', $show->url, $matches[2] ) );
						if ( $this->debug ) var_dump( $tep );
						$ep[] = $tep;
					}
				}
				if($min!=$max)
					return $this->tvGetReportMulti( $show, $ep, $min, $max, $matches[4] );
				else{
					if ( ( $ep = $api->tvrage->getEpisode( $show->tvrageShowID, $matches[2], $min, $this->ignoreCache ) ) !== false ){
						if ( $this->debug ) var_dump( $ep ); 
						// search for episode properties
						return $this->tvGetReport( $show, $ep, $matches[4] );
					}
					else{
						$ep = (object)array(
							'series' => sprintf("%d", $matches[2] ),
							'episode' => $matches[3],
							'title' => sprintf( 'Season %d, Episode %d', $matches[2], $matches[3] ),
							'url' => sprintf( '%s/episode_list/%d', $show->url, $matches[2] ) );
						if ( $this->debug ) var_dump( $ep );				
							return $this->tvGetReport( $show, $ep, $matches[4] );
					}
				}
			}
			else if ( $mUsed == 'Part' )
			{
				// assume 1xPart num
				if ( $this->debug ) printf('Doing Part');
				
				// check roman numeral
				if ( preg_match( $this->_def['info']['isRoman'], $matches[2] ) )
				  $num = Numbers_Roman::toNumber($matches[2]); // convert to number
				else
					$num = $matches[2];
					
				if ( ( $ep = $api->tvrage->getEpisode( $show->tvrageShowID, 1, $num, 
					$this->ignoreCache ) ) !== false )
				{
					if ( $this->debug ) var_dump( $ep ); 
					// search for episode properties
					return $this->tvGetReport( $show, $ep, $matches[3] );
				}
				else
				{
					$ep = (object)array(
						'series' => 1,
						'episode' => $num,
						'title' => sprintf( 'Part %d', $num ),
						'url' => sprintf( '%s/episode_list/%d', $show->url, 1 ) );
					if ( $this->debug ) var_dump( $ep );				
					return $this->tvGetReport( $show, $ep, $matches[4] );
				}
			}
			else if ( $mUsed == 'Series' )
			{
				// determine size of series
				$min = 1;
				$max = 50;
				
				for ( $i = $min; $i <= $max; $i++ )
				{
	//				list( $nSeries, $nEpisode ) = $this->tvNewSEp( $show, $matches[2], $i, true );
					if ( ( $tep = $api->tvrage->getEpisode( $show->tvrageShowID, $matches[2], $i, $this->ignoreCache ) ) !== false )
					{
						if ( $this->debug ) var_dump( $tep ); 
						// search for episode properties
						$ep[] = $tep;
					}
					else
					{
						break;
					}
				}
				if ( $i == 0 )
				{
					// poop
				}
				return $this->tvGetReportMulti( $show, $ep, $min, $max, $matches[3] );				
			}
			else if ( $mUsed == 'Game' )
			{
				
			}
		}
		else
		{
			// some other stuff
			if ( !isset( $api->tvrage->error ) )
				$this->_error = 'Invalid show name: '.$showquery;
			else
				$this->_error = $api->tvrage->error;
			return false;
		}
	}
	
	function tvNewSEp( $show, $series, $episode, $u2t = true )
	{
		if ( $u2t )
		{
			if ( empty( $show->usenetToTvrage ) )
				return array( $series, $episode );
			parse_str( $show->usenetToTvrage, $parse );
		}
		else
		{
			if ( empty( $show->tvrageToNewzbin ) )
				return array( $series, $episode );		
			parse_str( $show->tvrageToNewzbin, $parse );
		}
		
		foreach( $parse['a'] as $arr )
		{
			if ( ( ( substr( $arr['series'], 0, 1 ) == '>' ) && 
				   ( $series >= substr( $arr['series'], 1 ) ) ) ||
				 ( ( substr( $arr['series'], 0, 1 ) == '<' ) &&
				   ( $series <= substr( $arr['series'], 1 ) ) ) ||
				 ( ( is_numeric( $arr['series'] ) ) && 
				   ( $series == $arr['series'] ) ) )
			{
				if ( ( !isset( $arr['episode'] ) ) ||
					 ( $episode === false ) ||
					 ( ( isset( $arr['episode'] ) ) &&
					   ( ( ( substr( $arr['episode'], 0, 1 ) == '>' ) && 
						   ( $episode >= substr( $arr['episode'], 1 ) ) ) ||
						 ( ( substr( $arr['episode'], 0, 1 ) == '<' ) &&
						   ( $episode <= substr( $arr['episode'], 1 ) ) ) ||
						 ( ( is_numeric( $arr['episode'] ) ) &&
						   ( $episode == $arr['episode'] ) ) ) ) )
				{
					$series += $arr['smod'];
					if ( $episode !== false )
						$episode += $arr['emod'];
					return array( $series, $episode );
				}
			}
		}
		return array( $series, $episode );
	}
	
	function tvGetReport( $show, $ep, $aStr = '' )
	{
		$report = array();
	
			$ep->title = preg_replace($this->_def['addPart']['from'], $this->_def['addPart']['to'], $ep->title );
			$pNum_n = $pNum = substr($ep->title,strpos($ep->title, "(Part ")+6,-1);
			if ( preg_match( $this->_def['info']['isRoman'], $pNum ) )
				  $pNum_n = Numbers_Roman::toNumber($pNum); // convert to number
			if($pNum!=$pNum_n)
		 		$ep->title=str_replace("Part ".$pNum, "Part ".$pNum_n, $ep->title);
		
		if ( !empty( $show->nzbName ) )
		{
			$show->name = $show->nzbName;
		}
		if ( !empty( $show->nzbGenre ) )
		{
			$show->genre = $show->nzbGenre;
			$show->class = '';
		}
		
		list( $rSeries, $rEpisode ) = $this->tvNewSEp( $show, $ep->series, $ep->episode, false );
		
		if ( $this->ids )
		{
		
				$report[$this->_def['report']['fields']['title']] = sprintf( '%s - %sx%02d - %s', $show->name, $rSeries, $rEpisode, $ep->title );
		
			$report[$this->_def['report']['fields']['url']] = $ep->url;				
			$report[$this->_def['report']['fields']['category']] = $this->_def['report']['category']['TV'];
		}
		else
		{	
			$report['title'] = sprintf( '%s - %sx%02d - %s', $show->name, $rSeries, $rEpisode, $ep->title );
			$report['url'] = $ep->url;
			$report['category'] = 'TV';
		}				
		
		foreach( $this->_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->_def['report']['categoryGroups']['TV'] ) )
			{
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' ) 
					{
						// denote a negative regex
						if ( ! preg_match( substr( $reg, 1 ), $aStr ) )
						{
							$this->addAttr( $report, 'TV', $attr, $id );
						}							
					}
					else
					{
						if ( preg_match( $reg, $aStr ) )
						{
							$this->addAttr( $report, 'TV', $attr, $id );
						}
					}
				}
			}
		}
		
		if ( $this->isAttr( $report, 'TV', 'Source', 'HDTV' ) )
		{
			$this->addAttr( $report, 'TV', 'Source', 'TV Cap' );
		}
					
		$class = explode( ' | ', $show->class );
		
		$genres = explode( ' | ', $show->genre );
		
		foreach( $genres as $gen )
		{
			if ( isset( $this->_def['siteAttributes']['videogenre'][$gen] ) )
			{
				$this->addAttr( $report, 'TV', 'VideoGenre', $this->_def['siteAttributes']['videogenre'][$gen] );
			}
			else
			{
				$this->addAttr( $report, 'TV', 'VideoGenre', $gen );
			}
		}
		
		foreach( $class as $id => $cl )
		{
			if ( isset( $this->_def['siteAttributes']['class'][$cl] ) )
			{
				$this->addAttr( $report, 'TV', 'VideoGenre', $this->_def['siteAttributes']['class'][$cl] );
			}
		}

		if ( ( !$this->ids ) &&
			 ( is_array( $report['attributes']['VideoGenre'] ) ) )
			sort( $report['attributes']['VideoGenre'] );
		
		return $report;		
	}
	
	function tvGetReportMulti( $show, $listep, $min, $max, $aStr = '' )
	{
		$report = array();

		if ( !empty( $show->nzbName ) )
		{
			$show->name = $show->nzbName;
		}
		if ( !empty( $show->nzbGenre ) )
		{
			$show->genre = $show->nzbGenre;
			$show->class = '';
		}
        
        if ( $this->debug ) print_r( $listep );
		
        for ( $i = 0; $i < count( $listep ); $i++ )
        {              
			$listep[$i]->title = preg_replace( $this->_def['addPart']['from'], $this->_def['addPart']['to'], $listep[$i]->title );
			$pNum_n = $pNum = substr($listep[$i]->title,strpos($listep[$i]->title, "(Part ")+6,-1);
			if ( preg_match( $this->_def['info']['isRoman'], $pNum ) )
				  $pNum_n = Numbers_Roman::toNumber($pNum); // convert to number
			if($pNum!=$pNum_n)
		 		$listep[$i]->title=str_replace("Part ".$pNum, "Part ".$pNum_n, $listep[$i]->title);
			list( $listep[$i]->rSeries, $listep[$i]->rEpisode ) = $this->tvNewSEp( $show, $listep[$i]->series, $listep[$i]->episode, false );
            
            $notes .= sprintf( "%dx%02d - %s: %s\n", $listep[$i]->rSeries, $listep[$i]->rEpisode, $listep[$i]->title, $listep[$i]->url );
		}
        
        if ( $this->debug ) print_r( $listep );
		
        if ( count( $listep ) == 2 )
        {
			// do a check for Part's
			if ( ( preg_match( $this->_def['getPart'], $listep[0]->title, $e0part ) ) &&
			     ( preg_match( $this->_def['getPart'], $listep[1]->title, $e1part ) ) &&
				 ( $e0part[1] == $e1part[1] ) )
			{
				$title = sprintf( '%s - %sx%02d-%sx%02d - %s (Part %d & %d)', $show->name, $listep[0]->rSeries, $listep[0]->rEpisode, $listep[1]->rSeries, $listep[1]->rEpisode, $e0part[1], $e0part[2], $e1part[2] ) ;
			}
			else
			{
	            $title = sprintf( '%s - %dx%02d-%dx%02d - %s / %s', $show->name, $listep[0]->rSeries, $listep[0]->rEpisode, $listep[1]->rSeries, $listep[1]->rEpisode, $listep[0]->title, $listep[1]->title );
			}
        }
        else
        {
            $title = sprintf( '%s - %sx%02d-%sx%02d', $show->name, $listep[0]->rSeries, $listep[0]->rEpisode,
                $listep[count($listep)-1]->rSeries, $listep[count($listep)-1]->rEpisode );                
        }
        
        $url = sprintf( '%s/episode_list/%d', $show->url, $listep[0]->series );
        
		if ( $this->ids )
		{
			$report[$this->_def['report']['fields']['title']] = $title;
			$report[$this->_def['report']['fields']['url']] = $url;
			$report[$this->_def['report']['fields']['category']] = $this->_def['report']['category']['TV'];
            $report[$this->_def['report']['fields']['notes']] = $notes;
		}
		else
		{	
			$report['title'] = $title;
			$report['url'] = $url;
			$report['category'] = 'TV';
            $report['notes'] = $notes;
		}
		
		foreach( $this->_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->_def['report']['categoryGroups']['TV'] ) )
			{
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' ) 
					{
						// denote a negative regex
						if ( ! preg_match( substr( $reg, 1 ), $aStr ) )
						{
							$this->addAttr( $report, 'TV', $attr, $id );
						}							
					}
					else
					{
						if ( preg_match( $reg, $aStr ) )
						{
							$this->addAttr( $report, 'TV', $attr, $id );
						}
					}
				}
			}
		}
		
		if ( $this->isAttr( $report, 'TV', 'Source', 'HDTV' ) )
		{
			$this->addAttr( $report, 'TV', 'Source', 'TV Cap' );
		}		
					
		$class = explode( ' | ', $show->class );
		
		$genres = explode( ' | ', $show->genre );
		
		foreach( $genres as $gen )
		{
			if ( isset( $this->_def['siteAttributes']['videogenre'][$gen] ) )
			{
				$this->addAttr( $report, 'TV', 'VideoGenre', $this->_def['siteAttributes']['videogenre'][$gen] );
			}
			else
			{
				$this->addAttr( $report, 'TV', 'VideoGenre', $gen );
			}
		}
		
		foreach( $class as $id => $cl )
		{
			if ( isset( $this->_def['siteAttributes']['class'][$cl] ) )
			{
				$this->addAttr( $report, 'TV', 'VideoGenre', $this->_def['siteAttributes']['class'][$cl] );
			}
		}

		if ( ( !$this->ids ) &&
			 ( is_array( $report['attributes']['VideoGenre'] ) ) )
			sort( $report['attributes']['VideoGenre'] );
		
        if ( $this->debug ) print_r( $report );
        
		return $report;		
	}

	
	function tvGetReportDVD( $show, $ep, $aStr = '' )
	{
		$report = array();
		
		$ep->title = preg_replace( $this->_def['addPart']['from'], $this->_def['addPart']['to'], $ep->title );

		if ( !empty( $show->nzbName ) )
		{
			$show->name = $show->nzbName;
		}
		if ( !empty( $show->nzbGenre ) )
		{
			$show->genre = $show->nzbGenre;
			$show->class = '';
		}
		
		if ( $this->ids )
		{
			$report[$this->_def['report']['fields']['title']] = sprintf( '%s - Season %d [DVD %d]', $show->name, $ep->series, $ep->episode );
			$report[$this->_def['report']['fields']['url']] = $ep->url;
			$report[$this->_def['report']['fields']['category']] = $this->_def['report']['category']['TV'];
		}
		else
		{	
			$report['title'] = sprintf( '%s - Season %d [DVD %d]', $show->name, $ep->series, $ep->episode );
			$report['url'] = $ep->url;
			$report['category'] = 'TV';
		}				
		
		$this->addAttr( $report, 'TV', 'Source', 'DVD' );
		$this->addAttr( $report, 'TV', 'Format', 'DVD' );
		
		foreach( $this->_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->_def['report']['categoryGroups']['TV'] ) )
			{
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' ) 
					{
						// denote a negative regex
						if ( ! preg_match( substr( $reg, 1 ), $aStr ) )
						{
							$this->addAttr( $report, 'TV', $attr, $id );
						}							
					}
					else
					{
						if ( preg_match( $reg, $aStr ) )
						{
							$this->addAttr( $report, 'TV', $attr, $id );
						}
					}
				}
			}
		}
					
		$class = explode( ' | ', $show->class );
		
		$genres = explode( ' | ', $show->genre );
		
		foreach( $genres as $gen )
		{
			if ( isset( $this->_def['siteAttributes']['videogenre'][$gen] ) )
			{
				$this->addAttr( $report, 'TV', 'VideoGenre', $this->_def['siteAttributes']['videogenre'][$gen] );
			}
			else
			{
				$this->addAttr( $report, 'TV', 'VideoGenre', $gen );
			}
		}
		
		foreach( $class as $id => $cl )
		{
			if ( isset( $this->_def['siteAttributes']['class'][$cl] ) )
			{
				$this->addAttr( $report, 'TV', 'VideoGenre', $this->_def['siteAttributes']['class'][$cl] );
			}
		}

		if ( ( !$this->ids ) &&
			 ( is_array( $report['attributes']['VideoGenre'] ) ) )
			sort( $report['attributes']['VideoGenre'] );
		
		return $report;
	}	

    function tvGetReportDate( $show, $ep, $aStr = '' )
    {
        $report = array();
        
        $ep->title = preg_replace( $this->_def['addPart']['from'], $this->_def['addPart']['to'], $ep->title );
        
        if ( !empty( $show->nzbName ) )
        {
            $show->name = $show->nzbName;
        }
        if ( !empty( $show->nzbGenre ) )
        {
            $show->genre = $show->nzbGenre;
            $show->class = '';
        }
        
        if ( $this->ids )
        {
        	if($ep->title)
            	$report[$this->_def['report']['fields']['title']] = sprintf( '%s - %04d-%02d-%02d - %s', $show->name, date( 'Y', $ep->date ), date( 'm', $ep->date ), date( 'd', $ep->date ), $ep->title );
            else
            	  $report[$this->_def['report']['fields']['title']] = sprintf( '%s - %04d-%02d-%02d', $show->name, date( 'Y', $ep->date ), date( 'm', $ep->date ), date( 'd', $ep->date ));
            $report[$this->_def['report']['fields']['url']] = $ep->url;                
            $report[$this->_def['report']['fields']['category']] = $this->_def['report']['category']['TV'];
        }
        else
        {    
            $report['title'] = sprintf( '%s - %04d-%02d-%02d - %s', $show->name, date( 'Y', $ep->date ), date( 'm', $ep->date ), date( 'd', $ep->date ), $ep->title );
            $report['url'] = $ep->url;
            $report['category'] = 'TV';
        }                
        
        foreach( $this->_def['attributes'] as $attr => $array )
        {
            if ( in_array( $attr, $this->_def['report']['categoryGroups']['TV'] ) )
            {
                foreach( $array as $id => $reg )
                {
                    if ( substr( $reg, 0, 1 ) == '!' ) 
                    {
                        // denote a negative regex
                        if ( ! preg_match( substr( $reg, 1 ), $aStr ) )
                        {
                            $this->addAttr( $report, 'TV', $attr, $id );
                        }                            
                    }
                    else
                    {
                        if ( preg_match( $reg, $aStr ) )
                        {
                            $this->addAttr( $report, 'TV', $attr, $id );
                        }
                    }
                }
            }
        }
        
        if ( $this->isAttr( $report, 'TV', 'Source', 'HDTV' ) )
        {
            $this->addAttr( $report, 'TV', 'Source', 'TV Cap' );
        }
                    
        $class = explode( ' | ', $show->class );
        
        $genres = explode( ' | ', $show->genre );
        
        foreach( $genres as $gen )
        {
            if ( isset( $this->_def['siteAttributes']['videogenre'][$gen] ) )
            {
                $this->addAttr( $report, 'TV', 'VideoGenre', $this->_def['siteAttributes']['videogenre'][$gen] );
            }
            else
            {
                $this->addAttr( $report, 'TV', 'VideoGenre', $gen );
            }
        }
        
        foreach( $class as $id => $cl )
        {
            if ( isset( $this->_def['siteAttributes']['class'][$cl] ) )
            {
                $this->addAttr( $report, 'TV', 'VideoGenre', $this->_def['siteAttributes']['class'][$cl] );
            }
        }

        if ( ( !$this->ids ) &&
             ( is_array( $report['attributes']['VideoGenre'] ) ) )
            sort( $report['attributes']['VideoGenre'] );
        
        return $report;        
    }

	
	function filmQuery( $string )
	{
		global $api;

		$report = array();

		if ( $this->debug ) printf("Using Film routines\n");
		
		// check for a url
		if ( preg_match( $this->_def['info']['url']['imdb'], $string, $matches ) )
		{
			if ( ( $film = $api->imdb->getFilm( $matches[1], $this->ignoreCache ) ) !== false )
			{
				return $this->filmGetReport( $film, $report );
			}
			else
			{
				$this->_error = 'Could not get film ID: '.$matches[1];
				return false;
			}
		}		
		
		// get attributes first
		foreach( $this->_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->_def['report']['categoryGroups']['Movies'] ) )
			{
				$rString = $this->_def['report']['category']['Movies'].$this->_def['report']['attributeGroups'][$attr];
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' ) 
					{
						// denote a negative regex
						if ( !preg_match( substr( $reg, 1 ), $string ) )
						{
							$this->addAttr( $report, 'Movies', $attr, $id );
						}							
					}
					else
					{
						if ( preg_match( $reg, $string ) )
						{
							$this->addAttr( $report, 'Movies', $attr, $id );
						}
					}
				}
			}
		}
		
		$fTitle = $string;
			
		foreach( $this->_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->_def['report']['categoryGroups']['Movies'] ) )
			{				
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) != '!' ) 
					{
						$reg = substr( $reg, 0, -2 ).'.+/i';
						// this is so nothing gets deleted if it is first
						$fTitle = substr( $fTitle, 0, 1 ).preg_replace( $reg, '', substr( $fTitle, 1 ) );
					}
				}
			}
		}
		
		$fTitle = preg_replace( $this->_def['filmMatch'], '', $fTitle );
		
		$fTitle = trim( str_replace( $this->_def['strip'], ' ', $fTitle ) );
		
		while( strcmp( $fTitle, $old ) != 0 )
		{
			if ( ( $film = $api->imdb->getSFilm( $fTitle, $this->ignoreCache ) ) !== false )
			{
				return $this->filmGetReport( $film, $report );
			}
			$old = $fTitle;
			$fTitle = preg_replace( '/\s+\S+$/i', '', $fTitle );
		}
		$this->_error = 'Could not find a matching film';
		return false;
	}

	function filmGetReport( $film, $tmp )
	{
		$report = array();
		if($film->aka){
			$movieTitle=sprintf( '%s (%s) (%d)', $film->aka ,$film->title, $film->year );
		}
		else
			$movieTitle=sprintf( '%s (%d)', $film->title, $film->year );
		
		if ( $this->ids )
		{

			$report[$this->_def['report']['fields']['title']] = $movieTitle;
			$report[$this->_def['report']['fields']['url']] = $film->url;				
			$report[$this->_def['report']['fields']['category']] = $this->_def['report']['category']['Movies'];
		}
		else
		{
			
			$report['title'] = $movieTitle;
			$report['url'] = $film->url;	
			$report['category'] = 'Movies';
		}
		
		// so things are in order ;]
		$report['attributes'] = $tmp['attributes'];
					
		$genres = explode( ', ', $film->genre );
		
		foreach( $genres as $id => $gen )
		{
			if ( isset( $this->_def['siteAttributes']['videogenre'][$gen] ) )
			{
				$this->addAttr( $report, 'Movies', 'VideoGenre', $this->_def['siteAttributes']['videogenre'][$gen] );
			}
			else
			{
				$this->addAttr( $report, 'Movies', 'VideoGenre', $gen );
			}
		}
		
		if ( ( !$this->ids ) &&
			 ( is_array( $report['attributes']['VideoGenre'] ) ) )
			sort( $report['attributes']['VideoGenre'] );
		//print_r($report);
		return $report;	
	}

	function gameQuery( $string, $type )
	{
		global $api;
				
		$report = array();
		
		// check for a url
		if ( preg_match( $this->_def['info']['url']['gamespot'], $string, $matches ) )
		{
			if ( ( $game = $api->game->getGame( $matches[1], $this->ignoreCache ) ) !== false )
			{
				return $this->gameGetReport( $game, $report );
			}
			else
			{
				$this->_error = 'Could not get game from url: '.$string;
				return false;
			}
		}		
		
		// get attributes first
		foreach( $this->_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->_def['report']['categoryGroups']['Consoles'] ) )
			{
				$rString = $this->_def['report']['category']['Consoles'].$this->_def['report']['attributeGroups'][$attr];
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' ) 
					{
						// denote a negative regex
						if ( !preg_match( substr( $reg, 1 ), $string ) )
						{
							$this->addAttr( $report, 'Consoles', $attr, $id );
							if ( $attr == 'ConsolePlatform' )
								$tAppr = $id;
						}
					}
					else
					{
						if ( preg_match( $reg, $string ) )
						{
							$this->addAttr( $report, 'Consoles', $attr, $id );
							if ( $attr == 'ConsolePlatform' )
								$tAppr = $id;
						}
					}
				}
			}
		}
		
		$gTitle = $string;
			
		foreach( $this->_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->_def['report']['categoryGroups']['Consoles'] ) )
			{		
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) != '!' ) 
					{
						$reg = substr( $reg, 0, -2 ).'.+/i';
						if ( preg_match( $reg, $gTitle, $tmp, 0, 2 ) )
							$gTitle = preg_replace( $reg, '', $gTitle );
					}
				}
			}
		}
		
		$gTitle = trim( str_replace( $this->_def['strip'], ' ', $gTitle ) );
		
		while( strcmp( $gTitle, $old ) != 0 )
		{
			// check if a console platform is found and append to title
			if ( isset( $tAppr ) )
				$tmpTitle = $gTitle.' '.$tAppr;
			else
				$tmpTitle = $gTitle.( ( $type == 4 )? ' PC':'');
			
			if ( ( $game = $api->game->getSGame( $tmpTitle, $this->ignoreCache ) ) !== false )
			{
				return $this->gameGetReport( $game, $report );
			}
			$old = $gTitle;
			$gTitle = preg_replace( '/\s+\S+$/i', '', $gTitle );
		}
		$this->_error = 'Could not find a matching game';
		return false;
	}	
	
	function gameGetReport( $game, $tmp )
	{
		$report = array();
		
		if ( $this->ids )
		{
			if ( $game->year > 0 )
				$report[$this->_def['report']['fields']['title']] = sprintf( '%s (%d)', $game->title, $game->year );
			else
				$report[$this->_def['report']['fields']['title']] = $game->title;
			$report[$this->_def['report']['fields']['url']] = $game->url;
			$report[$this->_def['report']['fields']['category']] = ( $game->platform == 'pc' )? $this->_def['report']['category']['Games']:$this->_def['report']['category']['Consoles'];
		}
		else
		{
			if ( $game->year > 0 )
				$report['title'] = sprintf( '%s (%d)', $game->title, $game->year );
			else
				$report['title'] = $game->title;

			$report['url'] = $game->url;
			$report['category'] = ( $game->platform == 'pc' )? 'Games':'Consoles';
		}
		
		// so things are in order ;]
		$report['attributes'] = $tmp['attributes'];
		
		if ( $game->platform == 'pc' )
		{
			if ( is_array( $report['attributes'] ) )
			{
				foreach( $report['attributes'] as $attr => $monkey )
				{
					if ( !in_array( $attr, $this->_def['report']['categoryGroups']['Games'] ) )
					{
						$this->delAttr( $report, 'Games', $attr );
					}
				}
			}
		}
		else
		{
			if ( !$this->isAttr( $report, $report['category'], 'ConsolePlatform' ) )
			{
				if ( isset( $this->_def['siteAttributes']['consoleplatform'][$game->platform] ) )
				{
					$this->addAttr( $report, $report['category'], 'ConsolePlatform', $this->_def['siteAttributes']['consoleplatform'][$game->platform] );
				}
				else
				{
					$this->addAttr( $report, $report['category'], 'ConsolePlatform', $game->platform );
				}
			}
		}
									
		$genres = explode( ' ', $game->genre );
		
		foreach( $genres as $id => $gen )
		{
			if ( isset( $this->_def['siteAttributes']['gamegenre'][$gen] ) )
			{
				$this->addAttr( $report, $report['category'], 'GameGenre', $this->_def['siteAttributes']['gamegenre'][$gen] );
			}
			else
			{
				$this->addAttr( $report, $report['category'], 'GameGenre', $gen );
			}
		}
		
		if ( ( !$this->ids ) &&
			 ( is_array( $report['attributes']['GameGenre'] ) ) )
			sort( $report['attributes']['GameGenre'] );
		
		return $report;	
	}
	
	function musicQuery( $string )
	{
		global $api;
		$report = array();
		// get attributes first
		foreach( $this->_def['attributes'] as $attr => $array )
		{
	
			if ( in_array( $attr, $this->_def['report']['categoryGroups']['Music'] ) )
			{
				
				$rString = $this->_def['report']['category']['Music'].$this->_def['report']['attributeGroups'][$attr];
				foreach( $array as $id => $reg )
				{
					
					if ( substr( $reg, 0, 1 ) == '!' ) 
					{
						// denote a negative regex
						if ( !preg_match( substr( $reg, 1 ), $string ) )
						{
							$this->addAttr( $report, 'Music', $attr, $id );
						}							
					}
					else
					{
						if ( preg_match( $reg, $string ) )
						{
							$this->addAttr( $report, 'Music', $attr, $id );
						}
					}
				}
			}
		}
		
		
		
		// check for a url
		if ( preg_match( $this->_def['info']['url']['gm'], $string, $matches ) )
        {
            if ( ( $album = $api->gm->getAlbum( $matches[1], $this->ignoreCache ) ) !== false )
            {
                // search for episode properties
                return $this->musicGetReport( $album, $report );
            }
            else
            {
				if ( !isset( $api->gm->error ) )
	                $this->_error = sprintf( 'Invalid google music albumID: %s', $matches[1] );
				else
					$this->_error = $api->gm->error;
                return false;
            }
        }


		if ( preg_match( $this->_def['info']['url']['amg'], $string, $matches ) )
		{
			if ( ( $album = $api->amg->getAlbum( $matches[1], $this->ignoreCache ) ) !== false )
			{		
				// search for episode properties
				return $this->musicGetReport( $album , $report);
			}
			else
			{
				$this->_error = sprintf( 'Invalid allmusic albumID: %s', $matches[1] );
				return false;
			}
		}

        if ( isset( $this->_def['info']['Music'] ) )
        {
            foreach( $this->_def['info']['Music'] as $reg )
            {
                if ( preg_match( $reg, $string, $matches ) )
                {
                    $matched = true;
                    break;
                }
            }
        }

        if ( $matched )
        {

	        $artist = str_replace( $this->_def['strip'], ' ', $matches[1] );
	        $album = str_replace( $this->_def['strip'], ' ', $matches[2] );

	        if ( ( $album = $api->amg->getSAlbum( $artist, $album, $this->ignoreCache ) ) !== false )
	        {
	            return $this->musicGetReport( $album, $report );
	        }
	        else
	        {
	            if ( isset( $api->amg->error ) )
	                $tmperror = $api->amg->error;
	        }
		}

		
		// try google music
		$query = str_replace( $this->_def['strip'], ' ', $string );
		if ( ( $album = $api->gm->getSAlbum( $query, $this->ignoreCache ) ) !== false )
		{
			return $this->musicGetReport( $album );
		}
		else
		{
			if ( isset( $api->gm->error ) )
				$this->_error = $api->gm->error;
			else
				$this->_error = $tmperror;

			return false;
		}
		

		
	}
	
	function musicGetReport( $album, $tmp )
	{
		$report = array();

		if ( $this->debug ) var_dump( $album );
							
		if ( $this->ids )
		{
			if ( $album->year > 0 )
				$report[$this->_def['report']['fields']['title']] = sprintf( '%s - %s (%d)', $album->artist, $album->title, $album->year );
			else
				$report[$this->_def['report']['fields']['title']] = sprintf( '%s - %s', $album->artist, $album->title );
			
			$report[$this->_def['report']['fields']['url']] = $album->url;				
			$report[$this->_def['report']['fields']['category']] = $this->_def['report']['category']['Music'];
		}
		else
		{	
			if ( $album->year > 0 )
				$report['title'] = sprintf( '%s - %s (%d)', $album->artist, $album->title, $album->year );
			else
				$report['title'] = sprintf( '%s - %s', $album->artist, $album->title );

			$report['url'] = $album->url;
			$report['category'] = 'Music';
		}
		$report['attributes'] = $tmp['attributes'];
		
		$genres = explode( ', ', $album->genre );

		if ( $this->debug ) var_dump( $genres );

		foreach( $genres as $gen )
		{
			$gen = trim( $gen );
			if ( isset( $this->_def['siteAttributes']['audiogenre'][$gen] ) )
			{
				$this->addAttr( $report, 'Music', 'AudioGenre', $this->_def['siteAttributes']['audiogenre'][$gen] );
			}
			else
			{
				$this->addAttr( $report, 'Music', 'AudioGenre', $gen );
			}
		}
		if ( ( !$this->ids ) &&
			 ( is_array( $report['attributes']['AudioGenre'] ) ) )
			sort( $report['attributes']['AudioGenre'] );
		
		return $report;
	}
    
    function animeQuery( $string )
    {
        global $api;
        
        // check for a url
        if ( preg_match( $this->_def['info']['url']['anidb'], $string, $matches ) )
        {
            if ( ( $anime = $api->anidb->getAnime( $matches[1], $this->ignoreCache ) ) !== false )
            {        
                // search for episode properties
                return $this->animeGetReport( $anime );
            }
            else
            {
                $this->_error = sprintf( 'Invalid anime show ID: %s', $matches[1] );
                return false;
            }
        }
        
        if ( isset( $this->_def['info']['Anime'] ) )
        {
            foreach( $this->_def['info']['Anime'] as $reg )
            {
                if ( preg_match( $reg, $string, $matches ) )
                {
                    $matched = true;
                    break;
                }
            }
        }
        
        if ( !$matched )
        {
            $this->_error = 'Could not match: '.$string.', check category';
            return false;
        }
        
        // using anidb to parse the information
        
        $animequery = str_replace( $this->_def['strip'], ' ', $matches[1] );
        //$animequery = $matches[1];
        
        if ( ( $anime = $api->anidb->getFAnime( $animequery, $this->ignoreCache ) ) !== false )
        {
            if ( $this->debug ) var_dump( $anime );
            $anime->episode = $matches[2];
            return $this->animeGetReport( $anime, $matches[3] );
        }
        else
        {
            $this->_error = 'Invalid anime name: '.$animequery;
            return false;
        }
    }
    
    function animeGetReport( $anime, $aStr = '' )
    {
			global $api;
			
        $report = array();

        if (preg_match( $this->_def['info']['epSplit'], $anime->episode, $split ))
        {
            if ($split[1] > $split[2])
            {
                $anime->name .= $split[1];
								if ( $api->anidb->getEpisode( $anime, $split[2] ) )
                	$title = sprintf( '%s - %02d - %s', $anime->name, $split[2], $anime->ep );
								else
									$title = sprintf( '%s - %02d', $anime->name, $split[2] );								
            }
            else
                $title = sprintf( '%s - %02d-%02d', $anime->name, $split[1], $split[2] );
        }
        else
        {
					if ( $api->anidb->getEpisode( $anime, $anime->episode ) )
						$title = sprintf( '%s - %02d - %s', $anime->name, $anime->episode, $anime->ep );
					else
						$title = sprintf( '%s - %02d', $anime->name, $anime->episode );								
        }
                            
        if ( $this->ids )
        {
            $report[$this->_def['report']['fields']['title']] = $title;
            $report[$this->_def['report']['fields']['url']] = $anime->url;                
            $report[$this->_def['report']['fields']['category']] = $this->_def['report']['category']['Anime'];
        }
        else
        {    
            $report['title'] = $title;
            $report['url'] = $anime->url;
            $report['category'] = 'Anime';
        }
        
        $aStr .= $anime->type;

        foreach( $this->_def['attributes'] as $attr => $array )
        {
            if ( in_array( $attr, $this->_def['report']['categoryGroups']['Anime'] ) )
            {
                foreach( $array as $id => $reg )
                {
                    if ( substr( $reg, 0, 1 ) == '!' ) 
                    {
                        // denote a negative regex
                        if ( !preg_match( substr( $reg, 1 ), $aStr ) )
                        {
                            $this->addAttr( $report, 'Anime', $attr, $id );
                        }                            
                    }
                    else
                    {
                        if ( preg_match( $reg, $aStr ) )
                        {
                            $this->addAttr( $report, 'Anime', $attr, $id );
                        }
                    }
                }
            }
        }
        
        $this->addAttr( $report, 'Anime', 'Language', 'Japanese' );
        $this->addAttr( $report, 'Anime', 'Subtitle', 'English' );
        
        return $report;
    }    
	
	function dumbQuery( $string, $type )
	{
		global $api;
		
		if ( in_array( $type, $this->_def['report']['category'] ) )
		{
			$catname = array_search( $type, $this->_def['report']['category'] );
		}
		else
		{
			$catname = 'All';
		}
		
		if ( $this->debug ) echo $catname.' '.$type;
		
		// get attributes first
		foreach( $this->_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->_def['report']['categoryGroups'][$catname] ) )
			{
				$rString = $this->_def['report']['category'][$catname].$this->_def['report']['attributeGroups'][$attr];
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' ) 
					{
						// denote a negative regex
						if ( !preg_match( substr( $reg, 1 ), $string ) )
						{
							$this->addAttr( $report, $catname, $attr, $id );
						}							
					}
					else
					{
						if ( preg_match( $reg, $string ) )
						{
							$this->addAttr( $report, $catname, $attr, $id );
						}
					}
				}
			}
		}
		
		$fTitle = $string;

		if ( ( $catname == 'TV' ) && ( $this->isAttr( $report, 'TV', 'Source', 'HDTV' ) ) )
		{
			$this->addAttr( $report, 'TV', 'Source', 'TV Cap' );
		}
			
		foreach( $this->_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->_def['report']['categoryGroups'][$catname] ) )
			{				
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) != '!' ) 
					{
						$reg = substr( $reg, 0, -2 ).'.+/i';
						$fTitle = preg_replace( $reg, '', $fTitle );
					}
				}
			}
		}
		
		$fTitle = preg_replace( $this->_def['filmMatch'], '', $fTitle );
		
		if ( $catname == 'Music' )
		{
			$fTitle = trim( str_replace( $this->_def['musicStrip'], ' ', $fTitle ) );
		}
		else
		{
			$fTitle = trim( str_replace( $this->_def['strip'], ' ', $fTitle ) );
		}

        $fTitle = preg_replace( $this->_def['musicReplace']['from'],
            $this->_def['musicReplace']['to'], $fTitle );

        $fTitle = ucwords( $fTitle );

        $fTitle = preg_replace( $this->_def['musicReplace']['from'],
            $this->_def['musicReplace']['to'], $fTitle );


		$fTitle = preg_replace( '/\s+/i', ' ', $fTitle );
		
		if ( $this->ids )
		{
			$report[$this->_def['report']['fields']['title']] = sprintf( '%s', $fTitle );
			if ( $catname != 'All' )
				$report[$this->_def['report']['fields']['category']] = $this->_def['report']['category'][$catname];
		}
		else
		{	
			$report['title'] = sprintf( '%s', $fTitle );
			if ( $catname != 'All' )
				$report['category'] = $catname;
		}
		
		return $report;
		
	}
	
	function addAttr( &$report, $cat, $attr, $val )
	{
		if ( ( ( $attr == 'VideoGenre' ) && 
		       ( !isset( $this->_def['report']['attributeID']['VideoGenre'][$val] ) ) ) ||
			 ( ( $attr == 'GameGenre' ) &&
			   ( !isset( $this->_def['report']['attributeID']['GameGenre'][$val] ) ) ) ||
			 ( ( $attr == 'AudioGenre' ) &&
			   ( !isset( $this->_def['report']['attributeID']['AudioGenre'][$val] ) ) ) )
		{
			if ( $this->debug ) printf( "not found: %s\n", $val );
			return;
		}
		if ( $this->ids )
		{
			//$rString = $this->_def['report']['category'][$cat].$this->_def['report']['attributeGroups'][$attr];
			$rString = $this->_def['report']['attributeGroups'][$attr];
			if ( ( !isset( $report['attributes'][$rString] ) ) ||
				 ( !in_array( $this->_def['report']['attributeID'][$attr][$val], $report['attributes'][$rString] ) ) )
				$report['attributes'][$rString][] = $this->_def['report']['attributeID'][$attr][$val];
		}
		else
		{
			if ( $this->debug ) printf( "%s\n", $val );
			if ( ( !isset( $report['attributes'][$attr] ) ) ||
				 ( !in_array( $val, $report['attributes'][$attr] ) ) )
				$report['attributes'][$attr][] = $val;
		}
		
		if ( isset( $this->_def['attributeExclude'][$val] ) )
		{
			foreach( $this->_def['attributeExclude'][$val] as $dAt )
			{
				$this->delAttr( $report, $cat, $attr, $dAt );
			}
		}
	}
	
	function delAttr( &$report, $cat, $attr, $val = false )
	{
		if ( $this->ids )
		{
			//$rString = $this->_def['report']['category'][$cat].$this->_def['report']['attributeGroups'][$attr];
			$rString = $this->_def['report']['attributeGroups'][$attr];
			
			if ( $val === false )
				unset( $report['attributes'][$rString] );
			else if ( ( $key = array_search( $this->_def['report']['attributeID'][$attr][$val], $report['attributes'][$rString] ) ) !== false )
				unset( $report['attributes'][$rString][$key] );
		}
		else
		{
			if ( $val === false )
				unset( $report['attributes'][$attr] );
			else if ( ( $key = array_search( $val, $report['attributes'][$attr] ) ) !== false )
				unset( $report['attributes'][$attr][$key] );
		}
	}
	
	function isAttr( &$report, $cat, $attr, $val = false )
	{
		if ( $this->ids )
		{
			//$rString = $this->_def['report']['category'][$cat].$this->_def['report']['attributeGroups'][$attr];
			$rString = $this->_def['report']['attributeGroups'][$attr];
			if ( isset( $report['attributes'][$rString] ) )
			{
				if ( $val === false )
					return true;
				if ( in_array( $this->_def['report']['attributeID'][$attr][$val], $report['attributes'][$rString] ) )
					return true;
			}				
		}
		else
		{
			if ( isset( $report['attributes'][$attr] ) )
			{
				if ( $val === false )
					return true;
				if ( in_array( $val, $report['attributes'][$attr] ) )
					return true;
			}
		}	
		return false;
	}
}

?>
