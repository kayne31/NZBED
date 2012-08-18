<?php

require_once( 'Numbers/Roman.php' );

class ed
{ 
	//Main editor array.  It gets pulled to the extensions so any changes made here will reflect there.
	var $_def = array(
			'info' => array(
					'url' => array(
							'matchurl' => '/\b([\w-]+:\/\/?|www[.])?[^\s()<>]+(?:com|net|org)\//i',
					),
					'TVMsplit' => '/(\d{2,})/i',
					'isRoman' => '/[IVX]+/i',
					'epSplit' => '/(\d+)\s*-\s*(\d+)/i',
					'isTV' => '/^(.+?)(?<!the)(?:\.|\s|\s-\s|\(|\[|\_|\-|)(?:\(|\[|\_|\-)?((?:s|series|season|seizoen|saison|staffel)?\s?(?:\.|\_|\-)?\s?([0-9]+?),?\s?\.?(?:e|x|ep|episode|d|dvd|disc|disk|\-)(?!264)\s?(?:\.|\_|\-)?\s?([0-9]{2,3})(?![0-9]+)|(\d{2,4})\.(\d{2})\.(\d{2,4}))(?:\.|\s\-\s|\s|\)|\]|\_|\-)?(.*)$/i',
					'Music' => array('/^(.+?)\s*(?:-\s*\d{4}\s*)?-\s*(.+)$/'),
					'Anime' => array(
							'/(.+?)\s+-ep.(\d+)(.+?)?$/i',
							'/(.+?)\s*(?:[-_\.]|\s*)(?:e(?:ep(?:isode)?)?)?\s*[-_\.]?\s*((?:\d+\s?-\s?)?\d+)(.+?)?$/i'
					),
					'TV' => array(
							'Date' => array('/^(.+?)(?<!the)\s*(\d{2,4}+)(?:\.|\s)(\d{2})(?:\.|\s)(\d{2,4}+)\s*(.*)$/i'),
							'Multi' => array('/^(.+?)(?<!the)\s+(?:s|series|season|saison|staffel)?\s*([0-9]{1,2}+)\s*([0-9ex_\-\s]{5,})(?![0-9]+)\s*(.*)$/i'),
							'TV' => array(
									'/^(.+?)(?<!the)\s+(?:s|series|season|saison|staffel)?\s*([0-9]{1,2}?)\s*(?:e|x|ep|episode)(?!264)\s*([0-9]+)(?![0-9]+)\s*(.*)$/i',
									'/^(.+?)(?<!the)\s+(?:s|series|season|saison|staffel)?\s*([0-9]{1,2})\s*(?:\-|e|x|ep|episode)?\s*([0-9]{2})(?!p|[0-9]+)\s*(.*)$/i'
							),
							'DVD' => array('/^(.+?)(?<!the)\s+(?:s|series|season|seizoen|saison|staffel)\s*([0-9]{1,2}?)\s*(?:d|dvd|disc|disk)\s*([0-9]+)(?![0-9]+)\s*(.*)$/i'),
							'Part' => array('/^(.+?)(?<!the)\s+(?:pt|part)\s*([0-9]{1,2}|[IVX]+)\s*(.*)$/i'),
							'Series' => array('/^(.+?)(?<!the)\s+(?:s|series|season|saison|staffel)\s*([0-9]{1,2})\s*(?!e|x|ep|episode)\s+(.*)$/i'),
					)
			),//end of info array
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
							'NTSC' => '/\b(ntsc|usa|jpn)\b/i',
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
							'Blu-ray' => '/(blu[-. ]?ray|b(d|r|rd)[-.]?(rom|rip)|bd25|bd50)/i',
							'Web-DL' => '/(web[-. ]?dl|hditunes|ituneshd|ithd|webhd)/i'
					),
					'Format' => array(
							'DivX' => '/divx/i',
							'XviD' => '/xvid/i',
							'DVD' => '/dvd(?!rip?.)/i',
							'Blu-ray' => '/\b(blu[-. ]?ray|bd25|bd50)\b/i',
							'HD .TS' => '/\.ts(?!\.)/i',
							'H.264/x264' => '/\b(h.?264|x264|avc)\b/i',
							'AVCHD' => '/\b(avchd|bd5|bd9)\b/i',
							'SVCD' => '/svcd/i',
							'VCD' => '/mvcd/i',
							'WMV' => '/\b(wmv|vc-?1)\b/i',
							'iPod' => '/\b(ipod|iphone|itouch|iPad)\b/i',
							'PSP' => '/psp/i',
							'ratDVD' => '/ratDVD/i',
							'720p' => '/(720p|\.?720)/i',
							'1080i' => '/1080i/i',
							'1080p' => '/1080p/i',
							'3D' => '/\b(3d|sbs|h[-. ]?sbs|half[-. ]?sbs|f[-. ]?sbs|full[-. ]?sbs)\b/i'
					),
					'Audio' => array(
							'AC3/DD' => '/(ac3|dd[25]\.?[01]|5\.1)/i',
							'dts' => '/dts/i',
							'MP3' => '/mp3/i',
							'AAC' => '/aac/i',
							'Ogg' => '/\bogg\b/i',
							'Lossless' => '/\b(flac|lossless|dts-?(hd|hdma)|true-?hd)\b/i'
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
							'Italian' => '/((italian)|(multi(5|3))|((?<!\w)(ita)(?!\w)))/i',
							'Dutch' => '/((dutch))/i',
							'Polish' => '/((\.+PL\.+))/i',
							'Swedish' => '/swedish/i'
					),
	
					'Anime' => array(
							'TV' => '/TV Series/i',
							'OVA' => '/OVA/i'
					),
					'Subtitle' => array(
							'French' => '/((vostfr)|(vost))/i',
							'German' => '/(german.sub)/i',
							'Dutch' => '/((nlsubs)|(nl.?subs)|(nl.?subbed))/i'
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
							'/\-/'
					),
					'to' => array(
							'vs.',
							' - '
					),
			),
			'siteAttributes' => array(
					'videogenre' => array(
							'Action' => 'Action/Adv',
							'Action & Adventure' => 'Action/Adv',
							'Action and Adventure' => 'Action/Adv',						
							'Adventure' => 'Action/Adv',
							'Animation' => 'Animation',							
							'Arts & Crafts' => 'Family',
							'Automobiles' => 'Family',
							'Biography' => 'Documentary',							
							'Buy, Sell & Trade' => 'Reality',
							'Celebrities' => 'Reality',
							'Children' => 'Children',
							'Comedy' => 'Comedy',
							'Cooking/Food' => 'Reality',
							'Crime' => 'Crime',
							'Current Events' => 'Reality',
							'Dance' => 'Family',
							'Debate' => 'Reality',
							'Design/Decorating' => 'Reality',
							'Disaster' => 'Action/Adv',
							'Discovery/Science' => 'Documentary',
							'Documentary' => 'Documentary',
							'Drama' => 'Drama',
							'Education' => 'Documentary',
							'Educational' => 'Documentary',
							'Family' => 'Family',
							'Fantasy' => 'Fantasy',
							'Fashion/Makeup' => 'Family',
							'Financial/Business' => 'Reality',
							'Fitness' => 'Family',
							'Game-Show' => 'Family',
							'Game Show' => 'Reality',							
							'Garden/Landscape' => 'Reality',
							'History' => 'Documentary',
							'Home and Garden' => 'Family',							
							'Horror' => 'Horror',							
							'Horror/Supernatural' => 'Horror',
							'Housing/Building' => 'Reality',
							'How To/ Do It Yourself' => 'Reality',
							'Interview' => 'Reality',
							'Kids' => 'Children',							
							'Lifestyle' => 'Reality',
							'Military/War' => 'War',
							'Music' => 'Musical',
							'Musical' => 'Musical',							
							'Mystery' => 'Mystery',
							'News' => 'Reality',							
							'Pets/Animals' => 'Family',
							'Politics' => 'Documentary',
							'Reality' => 'Reality',
							'Reality-TV' => 'Reality',												
							'Religion' => 'Family',
							'Romance' => 'Romance',							
							'Romance/Dating' => 'Romance',
							'Science Fiction' => 'SciFi',
							'Science-Fiction' => 'SciFi',							
							'Sci-Fi' => 'SciFi',
							'Sketch/Improv' => 'Comedy',
							'Soap' => 'Drama',							
							'Soaps' => 'Drama',
							'Sport' => 'Sport',							
							'Sports' => 'Sport',
							'Sporting Event' => 'Sport',
							'Sports Film' => 'Sport',													
							'Super Heroes' => 'Action/Adv',
							'Suspense' => 'Thriller',							
							'Talent' => 'Reality',
							'Talk-Show' => 'Reality',
							'Talk Show' => 'Family',												
							'Tech/Gaming' => 'Reality',
							'Teens' => 'Family',
							'Thriller' => 'Thriller',
							'Travel' => 'Reality',
							'War' => 'War',							
							'Western' => 'Western',
							'Wildlife' => 'Documentary'
					),
					'class' => array(
							'Animation' => 'Animation',
							'Reality' => 'Reality',
							'Documentary' => 'Documentary'
					),
					'gamegenre' => array(
							'Sports' => 'Sport',
							'Action' => 'Action',
							'First-Person' => 'FPS',
							'Driving' => 'Racing',
							'rpg' => 'RPG',
							'Role-Playing' => 'RPG',
							'Strategy' => 'Strategy',
							'Puzzle' => 'Puzzle',
							'Adventure' => 'Adventure',
							'sim' => 'Simulator',
							'Sim' => 'Simulator',
							'Simulation' => 'Simulator'
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
							'Anime' => 11
					),
					'categoryGroups' => array(
							'Movies' => array( 'Format', 'Source', 'VideoGenre', 'Audio', 'Region', 'Language', 'Subtitle' ),
							'TV' => array( 'Format', 'Source', 'VideoGenre', 'Region', 'Language', 'Subtitle' ),
							'Consoles' => array( 'Region', 'GameGenre', 'ConsolePlatform', 'Media', 'Language' ),
							'Games' => array( 'Media', 'GameGenre', 'Language' ),
							'Music' => array( 'Audio', 'AudioGenre' ),
							'Anime' => array( 'Anime', 'Format', 'Language', 'Subtitle' ),
							'All' => array( 'Format', 'Source', 'VideoGenre', 'Audio', 'Region', 'Media', 'ConsolePlatform', 'GameGenre', 'AudioGenre', 'Language', 'Anime', 'Subtitle' )
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
									'3D' => 4194304
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
									'Western' => 32768
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
									'Turkish' => 262144
							),
							'Anime' => array(
									'Game' => 1,
									'Music' => 4,
									'Movie' => 2,
									'OVA' => 8,
									'TV' => 16,
									'Hentai' => 32
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
									'Turkish' => 262144
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
					'H.264/x264' => array( 'Blu-ray', 'HD-DVD' )
			)
	);
	
	var $ids;
	var $ignoreCache;
	var $_debug = false;

	function ed( $ids = false, $ignoreCache = false )
	{
		$this->ids = $ids;
		$this->ignoreCache = $ignoreCache;
		
	}

	/*****************************************************
	 * Main functions
	*****************************************************/

	function Query( $string, $cat = false)
	{
		global $api;
		if ( $this->_debug ) echo 'Query: '.$string."\n";
		//Check to see if it is a url
		if ( preg_match ( $this->_def['info']['url']['matchurl'],$string ) )
		{
			foreach ( $api->getextArray() as $mainext )
			{
				if( $mainext->ismyurl( $string ) )
				{
					return $mainext->getfromurl( $string, $this->ignoreCache );
				}
			}
			//if we got here then it's a url that we don't use yet so reject it
			$this->_error = sprintf( 'That URL is unknown. URL:%s',$string );
			return false;
		}
		//Search is not a url so let's check if there is a category
		if ( ( $cat == 0 ) || ( $cat == false ) || ( $cat == 6 ) )
		{
			if ( preg_match( $this->_def['info']['isTV'], $string, $matches ) )
			{
				if ( $this->_debug ) printf("Detected TV: [regex: %s]\n", $this->_def['info']['isTV'] );
				$cat = '8';
			}
			else
			{
				$cat = '6';
			}
		}
		$id = $cat;
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
				return $this->gameQuery( $string, $cat );
			case 11:
				return $this->animeQuery( $string );
			default:
				if ( $cat > 20 )
				{
					return $this->dumbQuery( $string, substr( $cat, 0, -1 ) );
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
		if ( $this->_debug ) printf("tvQuery( string:%s )\n", $string );
		if ( $result = $api->tv->search( $string, $this->ignoreCache ) )
		{
			if ( $this->_debug ) var_dump($result);
			return $result;
		}
		else
		{
			// some other stuff
			if ( !isset( $api->tv->_error ) )
				$this->_error = 'Invalid show name: '.$string;
			else
				$this->_error = $api->tv->_error;
			return false;
		}
	}
	
	function filmQuery( $string )
	{
		global $api;
		$old = '';
		while( strcmp( $string, $old ) != 0 )
		{
			if ( ( $film = $api->movies->search( $string, $this->ignoreCache ) ) !== false )
			{
				return $film;
			}
			$old = $string;
			$string = preg_replace( '/\s+\S+$/i', '', $string );
		}
		$this->_error = 'Could not find a matching film';
		return false;
	}

	

	function gameQuery( $string, $type )
	{
		global $api;
		$old= '';
		while( strcmp( $string, $old ) != 0 )
		{
			if ( ( $game = $api->games->search( $string, $type, $this->ignoreCache ) ) !== false )
			{
				return $game;
			}
			$old = $string;
			$string = preg_replace( '/\s+\S+$/i', '', $string );
		}
		$this->_error = 'Could not find a matching game';
		return false;
	}

	function musicQuery( $string )
	{
		global $api;
		if ( ( $album = $api->music->search( $string, $this->ignoreCache ) ) !== false )
		{
			return $album;
		}
		else
		{
			$this->_error = 'Can\'t find Album';
			return false;
		}
	}

	function animeQuery( $string )
	{
		global $api;
		if ( ( $anime = $api->anime->search( $string, $this->ignoreCache ) ) !== false )
		{
			return $anime;
		}
		else
		{
			$this->_error = 'Invalid anime name: '.$animequery;
			return false;
		}
	}

	function dumbQuery( $string, $type )
	{
		global $api;
		if ( in_array( $type, $this->_def['report']['category'] ) )
		{
			$typename = array_search( $type, $this->_def['report']['category'] );
		}
		else
		{
			$typename = 'All';
		}

		if ( $this->_debug ) echo $typename.' '.$type;
		// get attributes first
		foreach( $this->_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->_def['report']['categoryGroups'][$typename] ) )
			{
				$rString = $this->_def['report']['category'][$typename].$this->_def['report']['attributeGroups'][$attr];
				foreach( $array as $id => $reg )
				{
					if ( substr( $reg, 0, 1 ) == '!' )
					{
						// denote a negative regex
						if ( !preg_match( substr( $reg, 1 ), $string ) )
						{
							$this->addAttr( $report, $typename, $attr, $id );
						}
					}
					else
					{
						if ( preg_match( $reg, $string ) )
						{
							$this->addAttr( $report, $typename, $attr, $id );
						}
					}
				}
			}
		}

		$fTitle = $string;
		if ( ( $typename == 'TV' ) && ( $this->isAttr( $report, 'TV', 'Source', 'HDTV' ) ) )
		{
			$this->addAttr( $report, 'TV', 'Source', 'TV Cap' );
		}
		foreach( $this->_def['attributes'] as $attr => $array )
		{
			if ( in_array( $attr, $this->_def['report']['categoryGroups'][$typename] ) )
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
		if ( $typename == 'Music' )
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
			if ( $typename != 'All' )
				$report[$this->_def['report']['fields']['category']] = $this->_def['report']['category'][$typename];
		}
		else
		{
			$report['title'] = sprintf( '%s', $fTitle );
			if ( $typename != 'All' )
				$report['category'] = $typename;
		}
		return $report;
	}

	function addAttr( &$report, $type, $attr, $val )
	{
		if ( ( ( $attr == 'VideoGenre' ) &&
				( !isset( $this->_def['report']['attributeID']['VideoGenre'][$val] ) ) ) ||
				( ( $attr == 'GameGenre' ) &&
						( !isset( $this->_def['report']['attributeID']['GameGenre'][$val] ) ) ) ||
				( ( $attr == 'AudioGenre' ) &&
						( !isset( $this->_def['report']['attributeID']['AudioGenre'][$val] ) ) ) )
		{
			if ( $this->_debug ) printf( "not found: %s\n", $val );
			return;
		}
		if ( $this->ids )
		{
			//$rString = $this->_def['report']['category'][$type].$this->_def['report']['attributeGroups'][$attr];
			$rString = $this->_def['report']['attributeGroups'][$attr];
			if ( ( !isset( $report['attributes'][$rString] ) ) ||
					( !in_array( $this->_def['report']['attributeID'][$attr][$val], $report['attributes'][$rString] ) ) )
				$report['attributes'][$rString][] = $this->_def['report']['attributeID'][$attr][$val];
		}
		else
		{
			if ( $this->_debug ) printf( "%s\n", $val );
			if ( ( !isset( $report['attributes'][$attr] ) ) ||
					( !in_array( $val, $report['attributes'][$attr] ) ) )
				$report['attributes'][$attr][] = $val;
		}

		if ( isset( $this->_def['attributeExclude'][$val] ) )
		{
			foreach( $this->_def['attributeExclude'][$val] as $dAt )
			{
				$this->delAttr( $report, $type, $attr, $dAt );
			}
		}
	}

	function delAttr( &$report, $type, $attr, $val = false )
	{
		if ( $this->ids )
		{
			//$rString = $this->_def['report']['category'][$type].$this->_def['report']['attributeGroups'][$attr];
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

	function isAttr( &$report, $type, $attr, $val = false )
	{
		if ( $this->ids )
		{
			//$rString = $this->_def['report']['category'][$type].$this->_def['report']['attributeGroups'][$attr];
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
	
	function get_def(){
		return $this->_def;
	}
}

?>
