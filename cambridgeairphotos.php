<?php

# Cambridge air photos
class cambridgeairphotos extends frontControllerApplication
{
	# Function to assign defaults additional to the general application defaults
	public function defaults ()
	{
		# Specify available arguments as defaults or as NULL (to represent a required argument)
		$defaults = array (
			'applicationName' => 'Cambridge air photos',
			'database' => 'cucap',
			'table' => 'cucap',
			'useTemplating' => true,
			'div' => 'cucap',
			'administrators' => true,
			'defaultLongitude' => 0.1225,
			'defaultLatitude' => 52.208056,
			'defaultZoom' => 7,
			'feedbackRecipient' => 'cucap' . '@geog.cam.ac.uk',
			'authLinkVisibility' => '\.cam\.ac\.uk$',
			'thumbnailSizes' => array (200, 400, 500, 640),
			'disableTabs' => true,
			'logfile' => $this->applicationRoot . '/import/manualUpdates.sql',
			'authLinkVisibility' => false,
			'page404' => false,
			'apiUsername' => false,
			'geocoderApiKey' => NULL,	// This is used also for the geocoder call
		);
		
		# Return the defaults
		return $defaults;
	}
	
	
	# Function to assign supported actions
	public function actions ()
	{
		# Define available tasks
		$actions = array (
			'home' => array (
				'tab' => 'Home',
				'description' => false,
				'url' => '',
				'icon' => 'house',
			),
			'map' => array (
				'tab' => 'Browse the map',
				'description' => false,
				'url' => 'map/',
				'icon' => 'map',
			),
			'listing' => array (
				//'tab' => 'Listing',
				'description' => 'Listing',
				'url' => 'listing/',
				'icon' => 'house',
			),
			'featured' => array (
				'tab' => 'Featured',
				'description' => false,
				'url' => 'featured/',
				'icon' => 'star',
			),
			'themes' => array (
				'tab' => 'Themes',
				'description' => false,
				'url' => 'themes/',
				'icon' => 'application_view_tile',
			),
			'areas' => array (
				'tab' => 'Areas',
				'description' => false,
				'url' => 'areas/',
				'icon' => 'map',
			),
			'search' => array (
				'tab' => 'Search the catalogue',
				'description' => false,
				'url' => 'search/',
				'icon' => 'magnifier',
			),
			'searchExport' => array (
				'url' => 'search/results.csv',
				'export' => true,
			),
			'location' => array (
				'description' => false,
				'url' => 'location/',
				'tab' => NULL,
			),
			'about' => array (
				'tab' => 'About',
				'description' => false,
				'url' => 'about/',
				'icon' => 'information',
			),
			'feedback' => array (
				'tab' => 'Contact us',
				'description' => 'Contact details',
				'url' => 'feedback.html',
				'icon' => 'email',
			),
			'import' => array (
				'description' => 'Import',
				'url' => 'import/',
				'parent' => 'admin',
				'subtab' => 'Import',
				'icon' => 'database_refresh',
				'administrator' => true,
			),
			'api' => array (
				'description' => 'API calls',
				'url' => false,
				'export' => true,
			),
			'rolls' => array (
				'description' => false,
				'url' => 'rolls/',
				'parent' => 'admin',
				'subtab' => 'Film rolls',
				'icon' => 'map',
				'administrator' => true,
			),
		);
		
		# Return the actions
		return $actions;
	}
	
	
	# Database structure definition
	public function databaseStructure ()
	{
		return "
			
			-- Main data table, populated during import
			CREATE TABLE IF NOT EXISTS `cucap` (
			  `id` varchar(11) NOT NULL COMMENT 'Catalogue ID',
			  `natsort` int NOT NULL,
			  `type` enum('Oblique','Vertical') NOT NULL,
			  `photoDate` date DEFAULT NULL,
			  `photoTime` varchar(8) DEFAULT NULL,
			  `subject` varchar(255) DEFAULT NULL,
			  `featuredId` int DEFAULT NULL COMMENT 'Featured item?',
			  `copyright` varchar(255) NOT NULL COMMENT 'Copyright owner',
			  `filmRollNumber` varchar(255) DEFAULT NULL,
			  `photoNumberInFilm` varchar(255) DEFAULT NULL,
			  `cameraType` varchar(10) DEFAULT NULL,
			  `osMapSheet` varchar(255) DEFAULT NULL,
			  `coverTrac` varchar(8) DEFAULT NULL,
			  `mapScale` varchar(7) DEFAULT NULL,
			  `photoType` varchar(255) DEFAULT NULL,
			  `viewDirection` varchar(5) DEFAULT NULL,
			  `centDist` double DEFAULT NULL,
			  `cloudCover` varchar(27) DEFAULT NULL,
			  `gridSquare` varchar(255) DEFAULT NULL,
			  `gridReference` varchar(255) DEFAULT NULL,
			  `filmType` varchar(44) DEFAULT NULL,
			  `eastings` varchar(10) DEFAULT NULL COMMENT 'Eastings (assembled)',
			  `northings` varchar(11) DEFAULT NULL COMMENT 'Northings (assembled)',
			  `gridSystem` enum('uk','irish') NOT NULL,
			  `longitude` float(11,6) DEFAULT NULL COMMENT 'Longitude (looked up)',
			  `latitude` float(10,6) DEFAULT NULL COMMENT 'Latitude (looked up)',
			  `lonLat` point NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
			
			-- Featured themes
			CREATE TABLE IF NOT EXISTS `featured` (
			  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `name` varchar(255) NOT NULL COMMENT 'Theme',
			  `moniker` varchar(255) NOT NULL COMMENT 'URL moniker',
			  `coverId` varchar(11) NOT NULL COMMENT 'Cover item ID',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Featured themes';
			
			-- Themes
			CREATE TABLE IF NOT EXISTS `themes` (
			  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `theme` varchar(255) NOT NULL COMMENT 'Theme',
			  `total` int NOT NULL COMMENT 'Total',
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
		";
	}
	
	
	
	# Additional processing
	function main ()
	{
		# End if in an export mode
		if (isSet ($this->actions[$this->action]['export'])) {
			return;
		}
		
		# Set the body class
		$this->template['action'] = $this->action;
		
		# Set the SSO block
		$this->template['sso'] = pureContent::ssoLinks ('Raven');
		
		# Set the admin
		$this->template['userIsAdministrator'] = $this->userIsAdministrator;
		
		# Set the default title
		$this->template['title'] = 'Aerial photos from around the UK and beyond';
		
		# Set the baseUrl
		$this->template['baseUrl'] = $this->baseUrl;
		
		# Set the application URL
		$this->template['applicationRoot'] = $this->applicationRoot;
		
		# Start the HTML
		$html = '';
		
		# Load stylesheet
		$html .= "\n" . '<link rel="stylesheet" href="' . $this->baseUrl . '/cucap.css" />';
		
		# Load JS libraries
		$html .= "\n" . '<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>';
		$html .= "\n" . "<script type=\"text/javascript\" src=\"{$this->baseUrl}/js/cucap.js\"></script>";
		
		# Assign the list of featured categories
		$this->featuredCategories = $this->getFeaturedCategories (true);
		$this->featuredCategories = array_merge (array (false => 'Featured images'), $this->featuredCategories);
		
		$this->template['assets'] = $html;
		
		$featuredList = array ();
		foreach ($this->featuredCategories as $moniker => $featuredCategory) {
			$featuredList[] = '<a href="' . $this->baseUrl . '/featured/' . ($moniker ? "{$moniker}/" : '') . '">' . htmlspecialchars ($featuredCategory) . '</a>';
		}
		$this->template['featuredListHtml'] = application::htmlUl ($featuredList, false, 'campl-unstyled-list campl-local-dropdown-menu');
		
		# Assign the themes list
		$themes = $this->getThemes (10);
		$themesList = array ();
		$themesList[] = '<a href="' . $this->baseUrl . '/themes/' . '">Themes &mdash; list of all themes</a>';
		foreach ($themes as $theme => $total) {
			$moniker = urlencode ($this->typeToIdMoniker ($theme));
			$themesList[] = '<a href="' . $this->baseUrl . '/themes/' . ($moniker ? "{$moniker}/" : '') . '">' . htmlspecialchars ($theme) . '</a>';
		}
		$this->template['themesListHtml'] = application::htmlUl ($themesList, false, 'campl-unstyled-list campl-local-dropdown-menu');
	}
	
	
	# Home page
	#!# Move array HTML building to views
	public function home ()
	{
		# Get featured categories data
		$featured = $this->getFeaturedCategoriesList ();
		$this->template['featured'] = $this->renderAsGallery ($featured, 'cucapgallery naturalwidth compressed');
		
		# Get the themes
		$data = $this->getThemes ();
		$list = array ();
		foreach ($data as $type => $total) {
			$link = $this->baseUrl . '/themes/' . urlencode ($this->typeToIdMoniker ($type)) . '/';
			$list[] = "<a href=\"{$link}\">" . htmlspecialchars ($type) . ' <span>(' . number_format ($total) . ')</span></a>';
		}
		$this->template['themes'] = application::htmlUl ($list);
		
		# Create list of areas
		$areas = $this->getAreas ();
		$list = array ();
		foreach ($areas as $area) {
			$link = $this->baseUrl . '/areas/' . urlencode ($this->typeToIdMoniker ($area['name'])) . '/';
			$list[] = "<a href=\"{$link}\">" . htmlspecialchars ($area['name']) . ' <span>(' . number_format ($area['total']) . ')</span></a>';
		}
		sort ($list);
		$this->template['areas'] = application::htmlUl ($list);
		
		# Process the template
		$html = $this->templatise ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Map page
	public function map ()
	{
		# Process the template
		$this->template['map'] = $this->locationsMap ();
		
		# Process the template
		$html = $this->templatise ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Map of current locations
	private function locationsMap ($useIcon = false, $markerSetInitiallyIsDraggable = false)
	{
		# Start the HTML
		$html = '';
		
		# By default, no marker is shown
		$setMarkerInitially = false;
		
		# Set default map location
		$mapLocation = array (
			'latitude'	=> $this->settings['defaultLatitude'],
			'longitude'	=> $this->settings['defaultLongitude'],
			'zoom'		=> $this->settings['defaultZoom'],
		);
		
		# Check for cookie
		if ($_COOKIE && isSet ($_COOKIE['centerLngLatZoom'])) {
			list ($longitude, $latitude, $zoom) = explode (',', $_COOKIE['centerLngLatZoom'], 3);
			if ($longitude != '' && $latitude != '') {		// Deal with broken cookie scenario
				$mapLocation = array (
					'latitude'	=> $latitude,
					'longitude'	=> $longitude,
					'zoom'		=> $zoom,
				);
			}
		}
		
		# If a selected ID is supplied, look up its location
		$selectedIdData = false;
		if (isSet ($_GET['id'])) {
			$url = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/v2/location?id=' . urlencode (strtolower ($_GET['id']));
			$json = file_get_contents ($url);
			$selectedIdData = json_decode ($json, true);
			if (isSet ($selectedIdData['error'])) {
				$this->page404 ();
				return false;
			}
			
			$mapLocation = array (
				'latitude'	=> $selectedIdData['latitude'],
				'longitude'	=> $selectedIdData['longitude'],
				'zoom'		=> (isSet ($_GET['zoom']) && ctype_digit ($_GET['zoom']) && ($_GET['zoom'] < 20) ? $_GET['zoom'] : 10),
			);
		}
		
		# If the form is posted, and a map location was set, extract the map location
		#!# This hack is only necessary until ultimateForm has built-in support for a native map widget, which means this whole method can then be replaced
		if (isSet ($_POST['form'])) {
			if (isSet ($_POST['form']['latitude']) && isSet ($_POST['form']['longitude']) && isSet ($_POST['form']['zoom']) && preg_match ('/^[0-9-.]+$/', $_POST['form']['latitude']) && preg_match ('/^[0-9-.]+$/', $_POST['form']['longitude']) && preg_match ('/^[0-9]{1,2}$/', $_POST['form']['zoom'])) {
				$mapLocation = array (
					'latitude'	=> $_POST['form']['latitude'],
					'longitude'	=> $_POST['form']['longitude'],
					'zoom'		=> $_POST['form']['zoom'],
				);
				$setMarkerInitially = true;
			}
		}
		
		# Determine the URL for the browsing API; if a selected ID is requested, request that this always be included in the returned data
		$size = 200;
		$browsingApiUrl = $this->baseUrl . '/api/v2/pois?limit=250&size=' . $size . ($selectedIdData ? '&selectedid=' . $selectedIdData['id'] : '');
		
		# Define a second browsing layer if required
		$browsingApiUrl2 = 'false';
		
		# Create the map application HTML
		$html .= '
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/leaflet.css" />
		<script src="//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/leaflet.js"></script>
		<script src="' . $this->baseUrl . '/js/lib/Leaflet-active-area/src/leaflet.activearea.js"></script>
		<script src="//maps.google.com/maps/api/js?v=3.40"></script>
		<script src="' . $this->baseUrl . '/js/lib/Google.js"></script>
		';
		
		# Load drawing support
		$html .= "\n<link rel=\"stylesheet\" href=\"{$this->baseUrl}/js/lib/Leaflet.draw/dist/leaflet.draw.css\" />";
		$html .= "\n<!--[if lte IE 8]><link rel=\"stylesheet\" href=\"{$this->baseUrl}/js/lib/Leaflet.draw/dist/leaflet.draw.ie.css\" /><![endif]-->";
		$html .= "\n<script src=\"{$this->baseUrl}/js/lib/Leaflet.draw/dist/leaflet.draw.js\"></script>";
		
		# Load the map application Javascript and run it
		$setMarkerInitiallyJs = ($setMarkerInitially ? 'true' : 'false');
		$markerSetInitiallyIsDraggableJs = ($markerSetInitiallyIsDraggable ? 'true' : 'false');
		$selectedIdJs = ($selectedIdData ? "'" . $selectedIdData['id'] . "'" : 'false');
		$visibleLayers = 'opencyclemap,mapnik,osopendata';
		$html .= "\n<script type=\"text/javascript\">
			var map = telluswhere.createMap('{$this->baseUrl}', {$mapLocation['latitude']}, {$mapLocation['longitude']}, {$mapLocation['zoom']}, '{$browsingApiUrl}', '', {$setMarkerInitiallyJs}, {$markerSetInitiallyIsDraggableJs}, {$selectedIdJs}, {$browsingApiUrl2}, '{$visibleLayers}');
		</script>
		";
		
		# Add autocomplete name search
		$geocoderApiUrl = 'https://api.cyclestreets.net/v2/geocoder?key=' . $this->settings['geocoderApiKey'];
		// Libraries available at: https://cdnjs.com/libraries/jqueryui/
		$html .= "\n" . '<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>';
		$html .= "\n" . '<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.3/css/base/jquery-ui.css" />';
		$html .= "\n" . '<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.3/css/base/jquery.ui.autocomplete.css" />';
		$html .= "\n" . "<script type=\"text/javascript\" src=\"{$this->baseUrl}/js/lib/autocomplete.js\"></script>";
		$html .= "\n" . "<script type=\"text/javascript\">
		// Function to determine requirement for IE<=9 to use JSONP instead of JSON; see: https://stackoverflow.com/a/19562445/180733
		function useJsonpTransport () {
			
			// Determine details of the current browser
			var Browser = {
				IsIe: function () {
					return navigator.appVersion.indexOf('MSIE') != -1;
				},
				Navigator: navigator.appVersion,
					Version: function() {
					var version = 999; // we assume a sane browser
					if (navigator.appVersion.indexOf('MSIE') != -1)
					// bah, IE again, lets downgrade version number
					version = parseFloat(navigator.appVersion.split('MSIE')[1]);
					return version;
				}
			};
			
			// Test browser version
			var useJsonpTransport = (Browser.IsIe && Browser.Version() <= 9);
			
			// Return the result
			return useJsonpTransport;
		}
		</script>";
		
		$html .= "\n" . "<script type=\"text/javascript\">
			
			var autoCompleteOptions = {
				sourceUrl: '{$geocoderApiUrl}&bounded=1&bbox=-6.6577,49.9370,1.7797,57.6924&geometries=1',
				dataType: (useJsonpTransport() ? 'jsonp' : 'json'),
				jsonp: (useJsonpTransport() ? 'json_callback' : null),
				select: function (event, ui) {
					var result = ui.item;
					var zoom = 16;
					var geojsonItemLayer = L.geoJson(result.feature);
					map.fitBounds(geojsonItemLayer.getBounds ());
					event.preventDefault();
				}
			};
			
			autocomplete.addTo (\"input[name='location']\", autoCompleteOptions);
		</script>";
		
		# Return the HTML
		return $html;
	}
	
	
	# API endpoint
	public function api ()
	{
		# Determine the method
		$method = $_GET['method'];
		
		# Get the data
		$method = 'api' . ucfirst ($method);
		$data = $this->{$method} ();
		
		# JSON-encode the data
		$json = json_encode ($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);	// Enable pretty-print; see: https://www.vinaysahni.com/best-practices-for-a-pragmatic-restful-api#pretty-print-gzip
		
		# If a callback is specified, convert to JSON-P; See https://www.php.net/json-encode#95667 and https://stackoverflow.com/questions/1678214
		$jsonpCallback = (isSet ($_GET['callback']) && strlen ($_GET['callback']) ? $_GET['callback'] : false);
		if ($jsonpCallback) {
			$json = $jsonpCallback . '(' . $json . ');';
		}
		
		# Send headers
		header ('Content-type: application/json; charset=UTF-8');
		header ('Access-Control-Allow-Origin: *');
		
		# Prevent caching
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header ('Last-Modified: ' . gmdate ('D, d M Y H:i:s') . ' GMT');
		header ('Cache-Control: no-store, no-cache, must-revalidate');
		header ('Cache-Control: post-check=0, pre-check=0', false);
		header ('Pragma: no-cache');
		
		# Echo the JSON
		echo $json;
	}
	
	
	# Get location
	# <baseUrl>/api/v2/location?id=zknix44
	private function apiLocation ()
	{
		# Require an ID
		if (!isSet ($_GET['id'])) {
			return array ('error' => 'No ID was specified');
		}
		$id = $_GET['id'];
		
		# End if ID does not match
		if (!preg_match ('@^([- /a-z0-9]+)$@', $id)) {
			return array ('error' => 'The supplied ID did not match a required pattern.');
		}
		
		# Determine the requested size
		$size = (isSet ($_GET['size']) && ctype_digit ($_GET['size']) ? $_GET['size'] : false);
		
		# Get the data
		$fields = array ('id', 'subject', 'photoDate', 'photoTime', 'type', 'filmType', 'latitude', 'longitude', 'northings', 'eastings', 'osMapSheet', 'copyright', );
		$constraints = array ('id' => $id);
		$constraints['private'] = NULL;
		if (!$data = $this->databaseConnection->selectOne ($this->settings['database'], $this->settings['table'], $constraints, $fields)) {
			return array ('error' => "Location #{$id} does not exist.");
		}
		
		# Determine presence of photo, and assign height/width
		$data['hasPhoto'] = $this->hasPhoto ($data['id'], $size, $data['width'], $data['height']);
		
		# Attach matching theme keywords
		$query = "SELECT theme FROM themes WHERE :subject LIKE CONCAT('%', theme, '%');";
		$preparedStatementValues = array ('subject' => $data['subject']);
		$themesData = $this->databaseConnection->getPairs ($query, false, $preparedStatementValues);
		$themes = array ();
		foreach ($themesData as $theme) {
			$themes[$theme] = $this->baseUrl . '/themes/' . urlencode ($this->typeToIdMoniker ($theme)) . '/';
		}
		$data['themes'] = $themes;
		
		# Return the data
		return $data;
	}
	
	
	# Locations API
	# The algorithm spreads the points evenly around the viewport, maintaining stability as the viewport is moved
	// <baseUrl>/api/v2/pois?bbox=-5.8595557,49.7959232,4.5444971,53.8659179&limit=200
	private function apiPois ()
	{
		#!# This function needs simplification
		# End if no bbox
		if (!isSet ($_GET['bbox'])) {return array ('error' => 'No bbox was specified');}
		if (!isSet ($_GET['limit']) || !ctype_digit ($_GET['limit']) || $_GET['limit'] > 400) {return array ('error' => 'No valid limit was specified');}
		
		# Get the bbox
		$bbox = array ();
		list ($west, $south, $east, $north) = explode (',', $_GET['bbox']);
		
		$preparedStatementValues = array ();
		
		# Set the limit
		$limit = $_GET['limit'];
		
		# Determine the requested size
		$size = (isSet ($_GET['size']) && ctype_digit ($_GET['size']) ? $_GET['size'] : false);
		
		$conditions = array ();
		
		$datasource = "{$this->settings['database']}.{$this->settings['table']} l";
		
		if (!$idcsv = $this->gridIdcsv ($datasource, $conditions, $limit, $west, $south, $east, $north)) {return array ();}
		
		$fields = '*';
		
		$conditions = array ();
		$conditions[] = "l.id in ({$idcsv})";
		$conditions = implode (' AND ', $conditions);
		
		$orderBySql = ($fields == '*' ? 'ORDER BY id' : '');
		
		$selectedId = (isSet ($_GET['selectedid']) ? $_GET['selectedid'] : '');
		if ($selectedId) {
			$conditions = "({$conditions}) OR l.id = :selectedid";
			$preparedStatementValues['selectedid'] = $_GET['selectedid'];
			$orderBySql = ($orderBySql ? str_replace ('ORDER BY ', "ORDER BY (l.id = :selectedid) DESC, ", $orderBySql) : "ORDER BY (l.id = :selectedid) DESC");	// Ensure that the ID is included by forcing it to be at the start: https://stackoverflow.com/questions/7476158/force-to-order-a-certain-item-first
			$limit = $limit + 1;
		}
		
		// Performance
		$fields = array ('id', 'photoDate', 'type', 'filmType', 'latitude', 'longitude', 'northings', 'eastings', 'osMapSheet', 'copyright', );
		$fields = implode (',', $fields);
		
		# Construct the query
		$query = "SELECT {$fields}, subject AS name
					FROM {$datasource}
				   WHERE {$conditions}
						 {$orderBySql}
				   LIMIT {$limit};";
		
		# Get the data
		$pois = $this->databaseConnection->getData ($query, 'l.id', true, $preparedStatementValues);	// The datasource always has l.id as the key name, and this is explicitly supplied to avoid a SHOW FULL FIELDS lookup
		
		# Determine presence of photos
		foreach ($pois as $index => $poi) {
			$pois[$index]['hasPhoto'] = $this->hasPhoto ($poi['id'], $size, $pois[$index]['width'], $pois[$index]['height']);
		}
		
		# Arrange as GeoJSON layout
		$geojsonRenderer = new geojsonRenderer ();
		foreach ($pois as $id => $poi_row) {
			
			// Prepare list of properties, as a clone of the whole row
			$properties = $poi_row;
			
			// Avoid repeating these
			unset ($properties['longitude']);
			unset ($properties['latitude']);
			
			unset ($properties['lonLat']);
			
			// Render as GeoJSON feature
			$geojsonRenderer->point ($poi_row['longitude'], $poi_row['latitude'], $properties);
		}
		
		// Get the data as GeoJSON
		$data = $geojsonRenderer->getData ();
		
		# Return the data
		return $data;
	}
	
	/**
	 * The given region is divided up into approximately $limit cells, and the max(id) of rows in each cell is returned.
	 * The datasource can be a table or table join, but the table containing the rows is expected to have the alias l.
	 * @return string
	 */
	private function gridIdcsv ($datasource, $conditions, $limit, $west, $south, $east, $north)
	{
		// Grid dimensions
		list ($gridEdgeWest, $cell_ew, $gridEdgeSouth, $cell_ns) = $this->grid ($west, $south, $east, $north, $limit);
		if($gridEdgeWest === false || is_null($gridEdgeWest)) {return false;}

		// Use spatial.
		$conditions[] = "mbrwithin(lonLat, ST_LineStringFromText('linestring({$west} {$south}, {$east} {$north})'))";
		
		// Hide private images
		$conditions[] = "private IS NULL";
		
		# Determine whether to suppress placeholders
		$supressplaceholders = (isSet ($_GET['supressplaceholders']) && ($_GET['supressplaceholders'] == '1'));
		if ($supressplaceholders) {
			$conditions[] = "hasPhoto = 'yes'";
		}
		
		// Combine the conditions
		$conditions = implode (' AND ', $conditions);
		
		// The loop is two-pass because of SQL's groupwise max problem. This first pass gets the ids of candidates within each grid cell.
		// Use max(id) because later ids are often newer items - e.g in map_location.
		$query = "SELECT max(l.id) AS id
					FROM {$datasource}
				   WHERE {$conditions}
				GROUP BY floor((longitude - {$gridEdgeWest}) / {$cell_ew}), floor((latitude - {$gridEdgeSouth}) / {$cell_ns})
			/*
				   ORDER BY IF(hasPhoto = 'yes',1,0) DESC
			*/
		;";

		if (!$poiRows = $this->databaseConnection->getData ($query)) {
			return false;
		}
		
		// Flatten the array
		$ids = array();
		foreach ($poiRows as &$poiRow) {
			// Quote where required, as this list is compiled into a list for use in an IN(...) clause
			$ids[] = (is_numeric ($poiRow['id']) ? $poiRow['id'] : "'{$poiRow['id']}'");
		}
		unset($poiRow);
    	
		return implode (',', $ids);
	}
	
	/**
	 * Obtain parameters that divide the region into approximately $limit cells.
	 * The sides of each cell are about the same number of degrees.
	 * Really this should be tweaked to take account of forshortening of longitudes at varying latitude.
	 */
	private function grid ($west, $south, $east, $north, $limit)
	{
		// Its rather complicated trying to incorporate this!
	    // $foreshortening = cos(deg2rad(($north + $south)/2));

		$ew_span = abs($east - $west);
		$ns_span = abs($north - $south);

		if(!$ew_span || !$ns_span) {return false;}

		// Cells should be roughly square, even if the region is rectangular. If y = kx, then to accommodate limit cells, we can equate limit = y * x = k * x^2.
		$k = $ns_span / $ew_span;

		$grid_ew = ceil (sqrt ($limit / $k));
		$grid_ns = ceil ($grid_ew * $k);

		// Grid edge
		$cell_ew = $ew_span / $grid_ew;
		$cell_ns = $ns_span / $grid_ns;

		// Blunt the cell dimension to only a few significant digits, so that small changes are not noticed. This will make the displayed icons appear much more stable.
		$cell_ew = $this->significantDigits ($cell_ew, 2);
		$cell_ns = $this->significantDigits ($cell_ns, 2);

		// Use an absolute 'edge' to the grid to also contribute to icon stability. This effectively makes the grid move with the map, not stick to the viewport.
		$gridEdgeWest  = $cell_ew * floor($west / $cell_ew);
		$gridEdgeSouth = $cell_ns * floor($south / $cell_ns);

		return array ($gridEdgeWest, $cell_ew, $gridEdgeSouth, $cell_ns);
	}
	
	
	# Function to determine whether a photo is present for a location
	private function hasPhoto ($id, $size, &$width = NULL, &$height = NULL)
	{
		# Build the URL
		$location = "/data/thumbnails/{$size}/" . strtolower ($id) . '.jpg';
		
		# Check presence of thumbnail for the specified size
		$file = $this->applicationRoot . $location;
		if (!is_readable ($file)) {
			return false;
		}
		
		# Obtain height and width
		list ($width, $height, $type_ignored, $attr_ignored) = getimagesize ($file);
		
		# Return the location pattern as success
		return $this->baseUrl . $location;
	}
	
	
	# Geocoder
	#!# Need to turn into a proxy
	// <baseUrl>/api/v2/geocoder?bbox=-5.8595557,49.7959232,4.5444971,53.8659179&q=York%20Street
	private function apiGeocoder ()
	{
		# Ensure there is a query
		if (!isSet ($_GET['q'])) {return array ('error' => 'No query specified.');}
		$query = $_GET['q'];
		
		# Provide bbox
		$bbox = false;
		// if (!isSet ($_GET['bbox'])) {return array ('error' => 'No bbox was specified');}
		// $bbox = $_GET['bbox'];
		
		# Get the data from an upstream provider
		$api = "https://api.cyclestreets.net/v2/geocoder?key={$this->settings['geocoderApiKey']}&format=json&geometries=1&q=" . urlencode ($query) . "&bounded=1" . ($bbox ? "&bbox={$bbox}" : '');
		$json = file_get_contents ($api);
		
		# Decode the JSON back to an array
		$data = json_decode ($json, true);
		
		# Return the data
		return $data;
	}
	
	
	/**
	 * Rounds to the given number of significant digits.
	 * @link https://www.php.net/manual/en/function.round.php#93600
	 * @note Avoid calling number_format() on the result as this will strip any decimals unless done carefully.
	 */
	public function significantDigits ($number, $significantDigits)
	{
		if (!$number) {return 0;}
		
		return round ($number, floor ($significantDigits - log10 (abs ($number))));
	}
	
	
	# About page
	public function about ()
	{
		# Templatise
		$html = $this->templatise ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Feedback page
	public function feedback ($id_ignored = NULL, $error_ignored = NULL, $echoHtml = true)
	{
		# Start the HTML
		$html = '';
		
		# Templatise
		$html = $this->templatise ();
		
		# Show the HTML
		echo $html;
		
	}
	
	
	# Browse by film number
	#!# WIP
	public function listing ()
	{
		# Get the unique values
		$query = "SELECT
			YEAR(photoDate) AS 'Year',
			COUNT(*) AS Total
		FROM {$this->settings['database']}
		GROUP BY YEAR(photoDate)
		ORDER BY YEAR(photoDate)
		;";
		$data = $this->databaseConnection->getPairs ($query);
		
		echo count ($data);
		application::dumpData ($data);
		
		
	}
	
	
	# Search page
	public function search ($export = false)
	{
		# Create settings for multisearch
		$settings = array (
			'databaseConnection'				=> $this->databaseConnection,
			'baseUrl'							=> $this->baseUrl . '/' . $this->actions[$this->action]['url'],
			'database'							=> $this->settings['database'],
			'table'								=> $this->settings['table'],
			'orderBy'							=> 'natsort',
			'mainSubjectField'					=> 'subject',
			'excludeFields'						=> array ('natsort', 'lonLat', 'gridSystem', 'imageOriginal', 'imageThumbnail', 'datasource', 'private', 'boundaryId', ),	// Fields should not appear in the search form
			'showFields'						=> array ('id', 'photoDate', 'photoTime', 'subject', 'type', 'latitude', 'longitude', 'northings', 'eastings', 'copyright', /* 'osMapSheet', 'coverTrac',  */'viewDirection', 'centDist', ),
			'recordLink'						=> $this->baseUrl . '/map/?lat=%latitude&lon=%longitude&zoom=14&id=%lower(id)',
			//'recordLink'						=> $this->baseUrl . '/location/%lower(id)/',
			'geographicSearchEnabled'			=> 'loc_json',
			'geographicSearchMapUrl'			=> $this->baseUrl . '/map/?mode=draw',
			'geographicSearchField'				=> 'lonLat',	// #!# Rename this to 'geometry' in the database
			'searchResultsMaximumLimit'			=> 2500,
			'enumRadiobuttons'					=> 2,
			'enumRadiobuttonsInitialNullText'	=> array ('type' => 'Either (Oblique or Vertical)'),
			'fixedConstraintSql'				=> 'private IS NULL',
			'ignoreKeys'						=> array ('x', 'y'),	// Search parameters coming from the surrounding Chrome
		);
		
		# Load and run the multisearch facility
		$multisearch = new multisearch ($settings);
		$html = $multisearch->getHtml ();
		
		# Templatise
		$this->template['contentHtml'] = $html;
		$html = $this->templatise ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Search (file export wrapper, which acts the same but is via a different URL and route so that it can run with export=true)
	public function searchExport ()
	{
		return $this->search (true);
	}
	
	
	# Function to get area data
	private function getAreas ()
	{
		# Get list of supported types, counts and descriptions
		$rawData = $this->databaseConnection->select ($this->settings['database'], 'boundaries', array (), array ('id', 'name', 'description AS type', 'total'), true, 'type,name');
		
		# Return the data
		return $rawData;
	}
	
	
	# Areas page
	public function areas ()
	{
		# Set the default HTML page title
		$this->template['title'] = 'Aerial photos for each area of the UK &ndash; ' . $this->template['title'];
		
		# Get the data
		$rawData = $this->getAreas ();
		
		# Compute totals
		$totals = array ();
		foreach ($rawData as $id => $area) {
			$name = $area['name'];
			$totals[$name] = $area['total'];
		}
		ksort ($totals);
		
		# Compute ID lookups
		$idLookups = array ();
		foreach ($rawData as $id => $area) {
			$name = $area['name'];
			$idLookups[$name] = $id;
		}
		
		# Regroup by type
		$dataByType = array ();
		foreach ($rawData as $id => $area) {
			$type = $area['type'];
			$dataByType[$type][$id] = $area;
		}
		
		// application::dumpData ($totals);
		// application::dumpData ($dataByType);
		
		# Pre-construct a index listing
		$listingHtml = '';
		$listingHtml .= "\n" . '<!-- Enable table sortability: --><script language="javascript" type="text/javascript" src="/sitetech/sorttable.js"></script>';
		$i = 0;
		foreach ($dataByType as $localAuthorityType => $data) {
			$i++;
			
			# Pluralise
			$localAuthorityTypePlural = application::pluralise ($localAuthorityType);
			
			# Convert to area => total, so that the per- Local Authority type list can be sorted
			$areas = array ();
			foreach ($data as $area) {
				$areas[$area['name']] = $area['total'];
			}
			ksort ($areas);
			
			# Construct the listing for this local authority type
			$listingHtml .= "\n<div class=\"listingblock\">";
			$listingHtml .= "\n<h3>" . htmlspecialchars ($localAuthorityTypePlural) . '</h3>';
			$table = array ();
			foreach ($areas as $area => $total) {
				$link = $this->baseUrl . "/areas/" . urlencode ($this->typeToIdMoniker ($area)) . '/';
				$table[] = array (
					'theme' => "<a href=\"{$link}\">" . htmlspecialchars ($area) . '</a>',
					'total' => number_format ($total),
				);
			}
			$listingHtml .= application::htmlTable ($table, array (), $class = 'types lines compressed sortable" id="sortable' . $i, $keyAsFirstColumn = false, $uppercaseHeadings = true, $allowHtml = true, false, $addCellClasses = true);
			$listingHtml .= "\n</div>";
		}
		
		# Determine constraints
		$constraintsSql = 'boundaryId = :boundaryId';
		$preparedStatementValues = array ('boundaryId' => '%id');		// %id gets substituted below
		
		# Generate the listing
		$html = $this->types ($totals, $idLookups, $listingHtml, $constraintsSql, $preparedStatementValues, 'areas', 'areas', 'UK Local Authority area', 'The CUCAP catalogue covers a wide area of the UK and some other areas beyond.', 'in the area of');
		
		$this->template['contentHtml'] = $html;
		$html = $this->templatise ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to get featured categories data
	private function getFeaturedCategoriesList ()
	{
		# Get the list of supported feature categories
		$featured = $this->databaseConnection->select ($this->settings['database'], 'featured', array (), 'moniker AS id, name AS subject, coverId, id AS featuredId');
		
		# Attach hasPhoto attributes
		foreach ($featured as $moniker => $featureCategory) {
			$featured[$moniker]['hasPhoto'] = $this->hasPhoto ($featureCategory['coverId'], 200, $featured[$moniker]['width'], $featured[$moniker]['height']);
		}
		
		# Add URL
		foreach ($featured as $moniker => $featureCategory) {
			$featured[$moniker]['urlLocation'] = $this->baseUrl . '/featured/' . $moniker . '/';
		}
		
		# Remove corner link
		foreach ($featured as $moniker => $featureCategory) {
			$featured[$moniker]['id'] = false;
		}
		
		# Return the array
		return $featured;
	}
	
	
	# Featured items page
	public function featured ()
	{
		# Start the HTML
		$html = '';
		
		# Get featured categories data
		$featured = $this->getFeaturedCategoriesList ();
		
		# If a category is selected, validate it
		$id = (isSet ($_GET['id']) ? $_GET['id'] : false);
		if ($id && !isSet ($featured[$id])) {
			echo $this->page404 ();
			return false;
		}
		
		# Show index if no category
		if (!$id) {
			$html .= "\n<h2>Featured images</h2>";
			$html .= "\n<p>Here you can explore some of the very best images in the collection.</p>";
			$html .= $this->renderAsGallery ($featured, 'cucapgallery naturalwidth compressed');
			
			# Set the page title
			$this->template['title'] = 'Aerial photos &ndash; featured images';
		} else {
			
			# Add a droplist for quick switching between categories
			$droplist = array ();
			foreach ($this->featuredCategories as $moniker => $featuredCategory) {
				$link = $this->baseUrl . '/featured/' . ($moniker ? "{$moniker}/" : '');
				$droplist[$link] = htmlspecialchars ($featuredCategory);
			}
			$html .= application::htmlJumplist ($droplist, $_SERVER['REQUEST_URI'], $action = '', $name = 'jumplist', $parentTabLevel = 0, $class = 'jumplist noprint right', $introductoryText = 'Category:');
			
			# Show the listing
			$html .= "\n<h2>" . htmlspecialchars ($featured[$id]['subject']) . ' &ndash; featured images</h2>';
			
			# Get the items for this listing
			$data = $this->databaseConnection->select ($this->settings['database'], $this->settings['table'], array ('featuredId' => $featured[$id]['featuredId']), 'id, subject, NULL AS hasPhoto');
			
			# Determine presence of photos
			foreach ($data as $index => $location) {
				$data[$index]['hasPhoto'] = $this->hasPhoto ($location['id'], 200, $data[$index]['width'], $data[$index]['height']);
				$data[$index]['hasPhoto640'] = $this->hasPhoto ($location['id'], 640);
			}
			
			# Render as gallery
			$html .= $this->renderAsGallery ($data, 'cucapgallery naturalwidth compressed', true);
			
			# Set the page title
			$this->template['title'] = 'Aerial photos &ndash; ' . lcfirst (htmlspecialchars ($featured[$id]['subject']));
		}
		
		# Templatise
		$this->template['contentHtml'] = $html;
		$html = $this->templatise ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to get themes data
	private function getThemes ($limit = false)
	{
		# Obtain and return the data
		return $totals = $this->databaseConnection->selectPairs ($this->settings['database'], 'themes', array (), array ('theme', 'total'), true, 'total DESC', $limit);
	}
	
	
	# Themes page
	public function themes ()
	{
		# Get the list of supported themes
		$totals = $this->getThemes ();
		
		# Pre-construct a index listing
		$table = array ();
		foreach ($totals as $type => $total) {
			$link = $this->baseUrl . '/themes/' . urlencode ($this->typeToIdMoniker ($type)) . '/';
			$table[] = array (
				'theme' => "<a href=\"{$link}\">" . htmlspecialchars ($type) . '</a>',
				'total' => number_format ($total),
			);
		}
		$listingHtml  = "\n" . '<!-- Enable table sortability: --><script language="javascript" type="text/javascript" src="/sitetech/sorttable.js"></script>';
		$listingHtml .= application::htmlTable ($table, array (), $class = 'types lines compressed sortable" id="sortable', $keyAsFirstColumn = false, $uppercaseHeadings = true, $allowHtml = true, false, $addCellClasses = true);
		
		# Determine constraints
		$constraintsSql = 'subject LIKE :subject1 AND subject REGEXP :subject2';	// Use of LIKE first is an optimisation to minimise the dataset first to avoid a Slow Query
		$preparedStatementValues = array (
			'match' => '%type',		// NB Will be used only if the type has words >3 characters
			'subject1' => '%' . '%type' . '%',
			'subject2' => '\\b' . '%type' . '\\b',	// MySQL8 uses \\b rather than [[:<:]] and [[:>:]] https://stackoverflow.com/a/60906360
		);		// %type gets substituted below
		
		# Generate the listing
		$html = $this->types ($totals, false, $listingHtml, $constraintsSql, $preparedStatementValues, 'themes', 'themes', 'theme', 'The catalogue contains a wide range of themes, in particular historic monuments, coastal areas and archaeology.', 'whose subject matches');
		
		$this->template['contentHtml'] = $html;
		$html = $this->templatise ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to convert a named type into a URL moniker
	private function typeToIdMoniker ($type)
	{
		return htmlspecialchars (strtolower (str_replace ("'", '', $type)));
	}
	
	
	# Function to create a listing by a particular dimension, e.g. theme, area
	private function types ($types, $idLookups, $listingHtml, $constraintsSql, $preparedStatementValues, $subdirectoryName, $namePlural, $nameSingular, $introductionText, $resultDescription)
	{
		# Start the HTML
		$html = '';
		
		# Get the type, if there is one
		$type = false;
		if (isSet ($_GET['id'])) {
			$typesLowercase = array ();
			foreach ($types as $type => $total) {
				$typeMoniker = $this->typeToIdMoniker ($type);
				$typesLowercase[$typeMoniker] = $type;
			}
			if (!strlen ($_GET['id']) || !array_key_exists ($_GET['id'], $typesLowercase)) {
				$this->page404 ();
				return false;
			}
			$type = $typesLowercase[$_GET['id']];
			$typeUrlencoded = urlencode ($this->typeToIdMoniker ($type, false));
			$typeLink = "/{$subdirectoryName}/{$typeUrlencoded}/";
		}
		
		# If no type selected, show the index page
		if (!$type) {
			
			# Compile the HTML
			$html .= "\n<h2>" . ucfirst ($namePlural) . '</h2>';
			$html .= "\n<p>{$introductionText}</p>";
			$html .= "\n<p>You can browse the catalogue by {$nameSingular} here.</p>";
			$html .= $listingHtml;
			
			# Return the HTML
			return $html;
		}
		
		# Get page number
		$page = 1;
		if (isSet ($_GET['page'])) {
			if (!ctype_digit ($_GET['page']) || $_GET['page'] < 1) {
				$this->page404 ();
				return false;
			}
			$page = $_GET['page'];
		}
		
		# Add droplist
		$html .= $this->typesDropList ($types, $type, $subdirectoryName, $nameSingular);
		
		# Show total
		$html .= "\n<h2>" . ucfirst ($namePlural) . ': ' . htmlspecialchars ($type) . '</h2>';
		$html .= "\n<p><a href=\"{$this->baseUrl}/{$subdirectoryName}/\">" . ucfirst ($namePlural) . '</a> &raquo; ' . htmlspecialchars ($type) . '</p>';
		
		# Determine the number to show per page
		$paginationRecordsPerPage = 30;
		
		# Substitute prepared statement placeholders
		foreach ($preparedStatementValues as $field => $string) {
			
			# Special-case match optimisation; this adds in a MATCH AGAINST statement in MySQL at the start of the query; must contain at least one word that is long enough
			if ($field == 'match') {
				$hasLongEnoughWord = false;
				$words = explode (' ', $type);
				foreach ($words as $word) {
					if (mb_strlen (trim ($word)) > 3) {		// 3 is FULLTEXT minimum; if string has no words longer than this, the clause will return false, so no results
						$hasLongEnoughWord = true;
						break;
					}
				}
				if ($hasLongEnoughWord) {
					$constraintsSql = 'MATCH(subject) AGAINST (:match) AND ' . $constraintsSql;		// Prepend optimisation
					$string = str_replace ('%type', $type, $string);
				} else {
					unset ($preparedStatementValues[$field]);
					continue;
				}
			}
			
			# Normal query replacements
			$string = str_replace ('%%type%', "%{$type}%", $string);
			$string = str_replace ('\\b%type\\b', "\\b{$type}\\b", $string);	// Regexp with word boundary version
			if ($idLookups) {
				$string = str_replace ('%id', $idLookups[$type], $string);
			}
			$preparedStatementValues[$field] = $string;
		}
		
		# Define a retrieval query
		$query = "
		SELECT
				id,
				subject,
				NULL as hasPhoto	/* Gets overwritten below */
			FROM {$this->settings['database']}.{$this->settings['table']}
			WHERE {$constraintsSql}
			ORDER BY IF(hasPhoto = 'yes', 1, 0) DESC, natsort
		;";
		
		# Get data for this page
		$pagination = new pagination ($this->settings, $this->baseUrl);
		list ($data, $totalAvailable, $totalPages, $page, $actualMatchesReachedMaximum) = $this->databaseConnection->getDataViaPagination ($query, false, true, $preparedStatementValues, array (), $paginationRecordsPerPage, $page);
		
		# Determine presence of photos
		foreach ($data as $index => $location) {
			$data[$index]['hasPhoto'] = $this->hasPhoto ($location['id'], 200, $data[$index]['width'], $data[$index]['height']);
			$data[$index]['hasPhoto640'] = $this->hasPhoto ($location['id'], 640);
		}
		
		# Compile pagination links HTML
		$paginationLinks = pagination::paginationLinks ($page, $totalPages, $this->baseUrl . $typeLink);
		
		# Show total matches for this type
		$html .= "\n<p>There " . ($totalAvailable == 1 ? 'is one record' : 'are ' . ($totalAvailable ? number_format ($totalAvailable) : 'no') . ' records') . " {$resultDescription} <em>" . htmlspecialchars ($type) . '</em>' . ($data ? ", showing {$paginationRecordsPerPage} per page:" : '.') . '</p>';
		
		# Show records if there are any
		if ($data) {
			
			# Show stats
			$html .= "\n" . '<div id="statistics">';
			$html .= $this->photoStatistics ($type, $constraintsSql, $preparedStatementValues);
			$html .= $this->yearStatistics ($type, $constraintsSql, $preparedStatementValues);
			$html .= "\n" . '</div>';
			
			# Gallery and pagination
			$html .= $paginationLinks;
			$html .= $this->renderAsGallery ($data, 'cucapgallery naturalwidth compressed', true);
			$html .= $paginationLinks;
		}
		
		# Set the default HTML page title
		$this->template['title'] = 'Aerial photos: ' . htmlspecialchars ($type);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create a droplist of types
	private function typesDropList ($types, $selectedType, $subdirectoryName, $nameSingular)
	{
		# Create the droplist entries
		$droplist = array ();
		$sectionBaseUrl = $this->baseUrl . "/{$subdirectoryName}/";
		$droplist[$sectionBaseUrl] = "Select {$nameSingular}:";
		foreach ($types as $type => $total) {
			$link = $sectionBaseUrl . urlencode (htmlspecialchars (strtolower ($type))) . '/';
			$droplist[$link] = htmlspecialchars ($type) . ' (' . number_format ($total) . ')';
		}
		
		# Determine selected item
		$selected = $sectionBaseUrl . urlencode (htmlspecialchars (strtolower ($selectedType))) . '/';
		
		# Compile the HTML and register a processor
		$html = application::htmlJumplist ($droplist, $selected, $sectionBaseUrl, $name = 'types', $parentTabLevel = 0, $class = 'types right', false);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create photo stats
	private function photoStatistics ($type, $constraintsSql, $preparedStatementValues)
	{
		# Compile overall stats
		$query = "SELECT
				IF(hasPhoto, 'Yes', 'No') AS hasPhoto,
				COUNT(*) AS total
			FROM {$this->settings['database']}.{$this->settings['table']}
			WHERE {$constraintsSql}
			GROUP BY hasPhoto
			ORDER BY FIELD( IF(hasPhoto, 'Yes', 'No') , 'Yes', 'No')
		;";
		$data = $this->databaseConnection->getPairs ($query, false, $preparedStatementValues);
		
		# Format numbers
		foreach ($data as $value => $total) {
			$data[$value] = number_format ($total);
		}
		
		# Compile the HTML
		$html  = "\n<h4>Photos:</h4>";
		$html .= application::htmlTableKeyed ($data, array (), false, 'lines compressed statistics', false, true, $addRowKeyClasses = true);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create photo stats
	private function yearStatistics ($type, $constraintsSql, $preparedStatementValues)
	{
		# Compile overall stats
		$query = "SELECT
				IF(`year` IS NULL, '?', `year`) AS `year`,
				COUNT(*) AS total
			FROM {$this->settings['database']}.{$this->settings['table']}
			WHERE {$constraintsSql}
			GROUP BY `year`
			ORDER BY FIELD( IF(`year` IS NULL, '?', `year`), '?') DESC, `year`		/* See: https://stackoverflow.com/a/25433139/180733 */
		;";
		$data = $this->databaseConnection->getPairs ($query, false, $preparedStatementValues);
		
		# Format numbers
		foreach ($data as $value => $total) {
			$data[$value] = number_format ($total);
		}
		
		# Compile the HTML
		$html  = "\n<h4>By year:</h4>";
		$html .= application::htmlTableKeyed ($data, array (), false, 'lines compressed statistics', false, true, $addRowKeyClasses = true);
		
		# Return the HTML
		return $html;
	}
	
	
	# Location page
	public function location ()
	{
		# Get the ID
		if (!$id = (isSet ($_GET['id']) ? $_GET['id'] : false)) {
			$this->page404 ();
			return false;
		}
		
		# Determine page mode
		$supportedModes = array ('view', 'update');
		$mode = (isSet ($_GET['runpage']) && in_array ($_GET['runpage'], $supportedModes) ? $_GET['runpage'] : $supportedModes[0]);
		
		# Get the data
		$size = 500;
		$url = $_SERVER['_SITE_URL'] . $this->baseUrl . '/api/v2/location?id=' . rawurlencode ($id) . '&size=' . $size;
		// echo $url;
		$json = file_get_contents ($url);
		$data = json_decode ($json, true);
		if (isSet ($data['error'])) {
			$this->page404 ();
			return false;
		}
		
		# Start the HTML
		$html = '';
		
		# Start with sidebar
		$html .= "\n" . '<div id="details">';
		
		# Show update link if required
		if ($this->userIsAdministrator) {
			if ($mode == 'view') {
				$html .= "\n" . "<p class=\"right small\"><a href=\"{$this->baseUrl}/location/{$id}/update.html\">Edit</a></p>";
			}
		}
		
		# Show the catalogue number
		$html .= "\n" . '<p id="photonumber">Catalogue number: <strong>' . htmlspecialchars ($data['id']) . '</strong></p>';
		
		# Show the map
		$zoom = 12;
		$html .= "
		<div id=\"mapContainer\">
			<!-- The map panel itself -->
			<div id=\"map\">
			</div>
		</div><!-- /#mapContainer -->
		
		<link rel=\"stylesheet\" href=\"//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/leaflet.css\" />
		<script src=\"//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/leaflet.js\"></script>
		<script type=\"text/javascript\">
			
			// Define icons
			var cucapIcon = L.Icon.extend({
				options: {
					shadowUrl: '" . $this->baseUrl . "/images/shadow.png',
					iconSize:     [19, 26],
					shadowSize:   [24, 14],
					iconAnchor:   [9, 26],
					shadowAnchor: [3, 14],
					popupAnchor:  [0, -26]
				}
			});
			var icons = {
				oblique: new cucapIcon({iconUrl: '" . $this->baseUrl . "/images/oblique.png'}),
				vertical: new cucapIcon({iconUrl: '" . $this->baseUrl . "/images/vertical.png'})
			};
			var icon = icons." . strtolower ($data['type']) . ";
			
			// create a map in the map div, set the view to a given place and zoom
			var map = L.map('map').setView([{$data['latitude']}, {$data['longitude']}], {$zoom});
			
			// add an OpenStreetMap tile layer
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			    attribution: '&copy; <a href=\"https://openstreetmap.org/copyright\">OpenStreetMap</a> contributors'
			}).addTo(map);
			
			// add a marker in the given location, attach some popup content to it and open the popup
			marker = L.marker([{$data['latitude']}, {$data['longitude']}], {icon: icon" . ($mode == 'update'  ? ', draggable: true' : '') . "}).addTo(map);
			
			marker.on ('moveend', function (e) {
				latlng = e.target.getLatLng();
				$('#form_longitude').val(latlng.lng);
				$('#form_latitude').val(latlng.lat);
			});
			
		</script>
		";
		
		# Set location cookie; NB the domain should not be specified otherwise PHP will prefix it automatically with a dot: https://stackoverflow.com/a/745738
		$sevenDays = 7 * 24 * 60 * 60;
		setcookie ('centerLngLatZoom', "{$data['longitude']},{$data['latitude']},14", time () + $sevenDays, $this->baseUrl . '/');
		
		# Link back to map
		$html .= "<p><a href=\"{$this->baseUrl}/map/\">" . '<img src="/images/icons/control_rewind_blue.png" alt="&lt;" class="icon" /> ' . "Browse more on map from here</a></p>";
		
		# Add the table
		$headings = array (
			'id'			=> 'CUCAP no.',
			'photoDate'		=> 'Photo date',
			'type'			=> 'Type',
			'filmType'		=> 'Film type',
			'latitude'		=> 'Latitude',
			'longitude'		=> 'Longitude',
			'northings'		=> 'Northings',
			'eastings'		=> 'Eastings',
			'osMapSheet'	=> 'OS map sheet',
			'copyright'		=> 'Copyright',
		);
		$table = application::arrayFields ($data, array_keys ($headings));
		foreach ($table as $key => $value) {
			$table[$key] = htmlspecialchars ($value);
		}
		$table['id'] = "<strong>{$table['id']}</strong>";
		$table['photoDate'] = ($table['photoDate'] ? "<strong>{$table['photoDate']}" . ($data['photoTime'] ? ", {$data['photoTime']}" : '') . '</strong>' : '<span class="comment">[Unknown]</span>');
		$table['filmType'] = ($table['filmType'] ? $table['filmType'] : '<span class="comment">[Unknown]</span>');
		
		# Show the table as either a table or a form
		switch ($mode) {
			
			# View mode
			case 'view':
				$html .= application::htmlTableKeyed ($table, $headings, $omitEmpty = false, $class = 'metadata lines', $allowHtml = true);
				break;
				
			# Update mode
			case 'update':
				$html .= $this->editForm ($id);
				break;
		}
		
		
		# End sidebar
		$html .= "\n</div><!-- /#details -->";
		
		# Show the title
		$html .= "\n<h2>" . htmlspecialchars ($data['subject']) . "</h2>";
		
		# Show the image
		$html .= "\n" . '<div class="locatedPhoto">';
		if ($data['hasPhoto']) {
			$html .= "<img src=\"{$this->baseUrl}/images/item.gif\" style=\"background-image: url('" . $data['hasPhoto'] . "');\" title=\"" . htmlspecialchars ($data['subject']) . "\" alt=\"Thumbnail\" width=\"{$data['width']}\" height=\"{$data['height']}\" />";
		} else {
			$html .= '
			<div class="noimageavailable">
				<p>No image available at present.</p>
			</div><!-- /.noimageavailable -->
			';
		}
		$html .= "\n" . '</div><!-- /.locatedPhoto -->';
		
		# Add themes
		$themes = array ();
		if ($data['themes']) {
			foreach ($data['themes'] as $theme => $link) {
				$themes[] = "<a href=\"{$link}\">" . htmlspecialchars ($theme) . '</a>';
			}
			$html .= "\n<p class=\"themes\">Browse more in theme: " . implode (' ', $themes) . '</p>';
		}
		
		# More nearby
		$html .= $this->moreNearby ($data['id'], $data['latitude'], $data['longitude']);
		
		# Set the HTML page title
		$this->template['title'] = htmlspecialchars ($data['subject']) . ' &ndash; aerial photo';
		
		$this->template['contentHtml'] = $html;
		$html = $this->templatise ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to get the featured item categories
	private function getFeaturedCategories ($keyAsMoniker = false)
	{
		# Get and return the featured item categories
		return $featured = $this->databaseConnection->selectPairs ($this->settings['database'], 'featured', array (), array (($keyAsMoniker ? 'moniker' : 'id'), 'name'));
	}
	
	
	# Edit form
	public function editForm ($id)
	{
		# Start the HTML
		$html = '';
		
		# Get the data directly from the database
		$data = $this->databaseConnection->selectOne ($this->settings['database'], $this->settings['table'], array ('id' => $id));
		
		# Databind a form
		$form = new form (array (
			'displayRestrictions' => false,
			'formCompleteText' => false,
			'databaseConnection' => $this->databaseConnection,
			'div' => 'ultimateform small update',
			'submitButtonAccesskeyString' => true,
		));
		
		# Databind the form
		$form->dataBinding (array (
			'database' => $this->settings['database'],
			'table' => $this->settings['table'],
			'data' => $data,
			'size' => 25,
			'intelligence' => true,
			// 'exclude' => array ('id', 'natsort', 'longitude', 'latitude', 'lonLat', 'imageOriginal', 'imageThumbnail', 'hasPhoto'),
			'exclude' => array ('id', 'natsort', 'lonLat', 'imageOriginal', 'imageThumbnail', 'hasPhoto'),
			'attributes' => array (
				'photoDate' => array ('picker' => true, 'min' => '1900-01-01'),
				'featuredId' => array ('type' => 'select', 'values' => $this->getFeaturedCategories (), 'nullText' => false, ),
				'northings' => array ('required' => true, 'min' => 6, 'max' => 7, ),
				'eastings' => array ('required' => true, 'min' => 6, 'max' => 7, ),
				#!# Not sure why centDist isn't picking up size like others
				'centDist' => array ('size' => 25, ),
			),
		));
		
		# Process the form or end
		if (!$result = $form->process ($html)) {return $html;}
		
		# Update the record
		$this->updateRecord ($id, $result);
		
		# Return the HTML
		return $html;
	}
	
	
	# Update an entry
	public function updateRecord ($id, $data)
	{
		# Convert eastings/northings
		$conversionsLatLong = new ConversionsLatLong;
		list ($data['latitude'], $data['longitude']) = $conversionsLatLong->osgb36_to_wgs84 ($data['eastings'], $data['northings']);
		
		# Update the data; the change will be logged automatically to $this->settings['logfile']
		$this->databaseConnection->update ($this->settings['database'], $this->settings['table'], $data, array ('id' => $id));
		application::dumpData ($this->databaseConnection->error ());
		
		# Deal with lonLat spatial field; the change will be logged automatically to $this->settings['logfile']
		$preparedStatementValues = array (
			'id' => $id,
			'latitude' => $data['latitude'],
			'longitude' => $data['longitude'],
		);
		$query = "UPDATE {$this->settings['database']}.{$this->settings['table']} SET lonLat = POINTFROMTEXT(CONCAT('point(',:longitude,' ',:latitude,')')) WHERE id = :id;";
		$this->databaseConnection->query ($query, $preparedStatementValues, false, $logChange = true);
		$this->databaseConnection->logChange (true);
		//application::dumpData ($this->databaseConnection->error ());
		
		#!# Need to set flash then display it
		# Redirect to main page
		$location = $_SERVER['_SITE_URL'] . $this->baseUrl . '/location/' . $id . '/';
		application::sendHeader (302, $location);
	}
	
	
	# More nearby
	public function moreNearby ($currentId, $latitude, $longitude, $total = 8)
	{
		# Start the HTML
		$html  = "\n<h3>More nearby</h3>";
		
		# Determine a WSEN bbox around the point to optimise the SQL query by reducing the search space considerably; see: https://www.plumislandmedia.net/mysql/haversine-mysql-nearest-loc/
		$distance = 25;	// km - reduce this is if removing the hasPhoto constraint in the query below
		$w = ( $longitude - ($distance / (111.045 * cos(rad2deg($longitude)))) );
		$s = ( $latitude - ($distance / 111.045) );
		$e = ( $longitude + ($distance / (111.045 * cos(rad2deg($longitude)))) );
		$n = ( $latitude + ($distance / 111.045) );
		
		# Get the data; see: https://stackoverflow.com/a/574736
/*
		#!# Very slow query, typically 1-2 seconds
		#!# Ideally would favour those with images
		$query = "
		SELECT
			id,
			subject,
			NULL as hasPhoto,		-- Gets overwritten below
			( 3959 * acos( cos( radians( :latitude ) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians( :longitude ) ) + sin( radians( :latitude ) ) * sin(radians(latitude)) ) ) AS distance
		FROM {$this->settings['database']}.{$this->settings['table']}
		WHERE
			    id != :currentId
			AND private IS NULL
		HAVING distance < 25		-- km
		ORDER BY distance, id
		LIMIT {$total}
		";
*/
		$query = "
		SELECT
			id,
			subject,
			NULL as hasPhoto,		-- Gets overwritten below
			( 3959 * acos( cos( radians( :latitude ) ) * cos( radians( ST_Y(lonLat) ) ) * cos( radians( ST_X(lonLat) ) - radians( :longitude ) ) + sin( radians( :latitude ) ) * sin(radians( ST_Y(lonLat) )) ) ) AS distance
		FROM {$this->settings['database']}.{$this->settings['table']}
		WHERE
			    id != :currentId
			AND private IS NULL
			AND hasPhoto = 'yes'
			AND ST_Within ( lonLat, ST_GEOMFROMTEXT('POLYGON(( {$w} {$n}, {$w} {$s}, {$e} {$s}, {$e} {$n}, {$w} {$n} ))') )
		HAVING distance < {$distance}		-- km
		ORDER BY distance, id
		LIMIT {$total}
		;";
		//var_dump ($query, $latitude, $longitude, $currentId);
		//$start = microtime(true);
		$data = $this->databaseConnection->getData ($query, false, true, array ('latitude' => $latitude, 'longitude' => $longitude, 'currentId' => $currentId));
		//$time_elapsed_secs = microtime(true) - $start;
		
		# Determine presence of photos
		foreach ($data as $index => $location) {
			$data[$index]['hasPhoto'] = $this->hasPhoto ($location['id'], 200, $data[$index]['width'], $data[$index]['height']);
		}
		
		// application::dumpData ($data);
		
		# Render as a gallery
		$html .= $this->renderAsGallery ($data);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to render locations to a gallery
	private function renderAsGallery ($data, $cssClass = 'cucapgallery', $enableLightbox = false)
	{
		# Start the HTML
		$html = '';
		
		# Enable lightbox if required
		if ($enableLightbox) {
			$html .= $this->lightbox ();
		}
		
		# Show as a gallery
		foreach ($data as $location) {
			$caption = htmlspecialchars ($location['subject']);
			$idLowercase = strtolower ($location['id']);
			$urlLocation = (isSet ($location['urlLocation']) ? $location['urlLocation'] : "{$this->baseUrl}/location/" . htmlspecialchars ($idLowercase) . '/');
			$html .= "\n\n" . ($location['hasPhoto'] ? '<div>' : '<div class="noimage">');
			if ($location['id']) {
				$html .= "\n\t" . "<p class=\"id\"><a href=\"{$urlLocation}\">" . htmlspecialchars ($location['id']) . '</a></p>';
			}
			if ($location['hasPhoto']) {
				$photoLink = ($enableLightbox ? $location['hasPhoto640'] : $urlLocation);
				$html .= "\n\t" . '<a href="' . $photoLink . "\" class=\"lightbox\" title=\"{$caption}\"><img src=\"{$this->baseUrl}/images/item.gif\" style=\"background-image: url('" . $location['hasPhoto'] . "');\" title=\"{$caption}\" alt=\"Thumbnail\" width=\"{$location['width']}\" height=\"{$location['height']}\" /></a>";
			} else {
				$html .= "\n\t<p class=\"noimage\"><a href=\"" . $urlLocation . '"><em>Sorry, no image available yet.</em></a></p>';
			}
			$html .= "\n\t<p class=\"subject\"><a href=\"" . $urlLocation . "\">{$caption}</a></p>";
			$html .= "\n" . '</div>';
		}
		
		# Surround with a div
		$html = "\n" . "<div class=\"{$cssClass}\">" . $html . "\n" . '</div>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to add support for an image lightbox; see: https://avioli.github.io/jquery-lightbox/ and http://web.archive.org/web/20140209184011/http://leandrovieira.com/projects/jquery/lightbox/
	public function lightbox ()
	{
		# Create and return the HTML
		return $html = '
			<script type="text/javascript" src="' . $this->baseUrl . '/js/lib/jquery-lightbox/js/jquery.lightbox-0.5.js"></script>
			<link rel="stylesheet" type="text/css" href="' . $this->baseUrl . '/js/lib/jquery-lightbox/css/jquery.lightbox-0.5.css" media="screen" />
			<script type="text/javascript">
			$(function() {
				$(".lightboxgallery a, a.lightbox").lightBox({
					imageLoading: "' . $this->baseUrl . '/js/lib/jquery-lightbox/images/lightbox-ico-loading.gif",
					imageBtnClose: "' . $this->baseUrl . '/js/lib/jquery-lightbox/images/lightbox-btn-close.gif",
					imageBtnPrev: "' . $this->baseUrl . '/js/lib/jquery-lightbox/images/lightbox-btn-prev.gif",
					imageBtnNext: "' . $this->baseUrl . '/js/lib/jquery-lightbox/images/lightbox-btn-next.gif",
					imageBlank: "' . $this->baseUrl . '/js/lib/jquery-lightbox/images/lightbox-blank.gif"
				});
			});
			</script>
		';
	}
	
	
	// https://stackoverflow.com/a/9944080/180733
	private function getPrevKey ($key, $hash = array ())
	{
	    $keys = array_keys($hash);
	    $found_index = array_search($key, $keys);
	    if ($found_index === false || $found_index === 0)
	        return false;
	    return $keys[$found_index-1];
	}
	private function getNextKey ($key, $hash = array ())
	{
	    $keys = array_keys($hash);
	    $found_index = array_search($key, $keys);
	    if ($found_index === false || $found_index === 0)
	        return false;
	    return $keys[$found_index+1];
	}
	
	
	# Function to provide listings for each film roll
	public function rolls ($id)
	{
		# Start the HTML
		$html = '';
		
		# Set the default HTML page title
		$this->template['title'] = 'Film rolls';
		
		# Get the data
		$rolls = $this->getRolls ();
		
		# Create a URL moniker for each roll
		$urlMonikers = array ();
		foreach ($rolls as $roll => $total) {
			$urlMonikers[$roll] = str_replace (' ', '_', str_replace ('/', '--', strtolower ($roll)));
		}
		
		# Validate the supplied roll, if any
		$id = false;
		$filmRollNumber = false;
		if (isSet ($_GET['id'])) {
			$urlMonikersByMoniker = array_flip ($urlMonikers);
			if (!isSet ($urlMonikersByMoniker[$_GET['id']])) {
				$this->page404 ();
				return false;
			}
			$filmRollNumber = $urlMonikersByMoniker[$_GET['id']];
			$this->template['filmRollNumber'] = $filmRollNumber;
		}
		
		# Create a droplist
		$droplist = array ();
		$droplist["{$this->baseUrl}/rolls/"] = '';
		foreach ($rolls as $roll => $total) {
			$link = "{$this->baseUrl}/rolls/{$urlMonikers[$roll]}/";
			$droplist[$link] = ($roll == '-' ? '[None]' : htmlspecialchars ($roll)) . ' (' . number_format ($total) . ')';
		}
		$this->template['droplist'] = application::htmlJumplist ($droplist, $_SERVER['REQUEST_URI'], $action = '', $name = 'jumplist', $parentTabLevel = 0, $class = 'jumplist noprint right', $introductoryText = 'Film roll:');
		
		# Create a listing and end, if no ID
		if (!$filmRollNumber) {
			
			# Show the listing
			$list = array ();
			foreach ($rolls as $roll => $total) {
				$list[] = "<a href=\"{$this->baseUrl}/rolls/{$urlMonikers[$roll]}/\"><strong>" . ($roll == '-' ? '[None]' : htmlspecialchars ($roll)) . '</strong> &nbsp;(' . number_format ($total) . ')</a>';
			}
			$html .= application::htmlUl ($list, 0, 'splitlist4');
			
		} else {
			
			# Get the next film roll
			#!# These are not resilient to start/end offsets
			$previousRoll = $this->getPrevKey ($filmRollNumber, $urlMonikers);
			$this->template['previousRoll'] = $previousRoll;
			$this->template['previousUrl'] = "{$this->baseUrl}/rolls/" . $urlMonikers[$previousRoll] . '/';
			$nextRoll = $this->getNextKey ($filmRollNumber, $urlMonikers);
			$this->template['nextRoll'] = $nextRoll;
			$this->template['nextUrl'] = "{$this->baseUrl}/rolls/" . $urlMonikers[$nextRoll] . '/';
			$this->template['indexUrl'] = "{$this->baseUrl}/rolls/";
			
			# Get the locations for this roll
			$data = $this->getLocationsInRoll ($filmRollNumber);
			
			# Link IDs in the table
			$tableData = $data;
			foreach ($tableData as $index => $location) {
				$tableData[$index]['id'] = "<a href=\"{$this->baseUrl}/location/" . strtolower (str_replace (' ', '+', $location['id'])) . "/\">{$location['id']}</a>";
			}
			
			# Create the table
			$table = application::htmlTable ($tableData, array (), 'filmlisting lines compressed small', $keyAsFirstColumn = false, false, array ('id'), false, false, false, $onlyFields = array ('id', 'photoDate', 'subject', 'sheet', 'eastings', 'northings'));
			
			# Exclude locations with no lat/lon from the maps
			foreach ($data as $id => $location) {
				if ($location['longitude'] == 0 && $location['latitude'] == 0) {
					unset ($data[$id]);
				}
			}
			
			# Arrange data as GeoJSON points, which will show the photo locations
			$geojsonRenderer = new geojsonRenderer ();
			foreach ($data as $id => $location) {
				$geojsonRenderer->point ($location['longitude'], $location['latitude'], $location);
			}
			$geojsonPoints = $geojsonRenderer->getGeojson ();
			
			# Arrange data as a LineString, which will show the flight path
			$coordinates = array ();
			foreach ($data as $id => $location) {
				$coordinates[] = array ($location['longitude'], $location['latitude']);
			}
			$geojsonRenderer = new geojsonRenderer ();
			$properties = array ('Film roll number' => $filmRollNumber);
			$geojsonRenderer->lineString ($coordinates, $properties);
			$geojsonLine = $geojsonRenderer->getGeojson ();
			
			# Create the map
			$mapHtml = "
			<div id=\"mapContainer\">
				<!-- The map panel itself -->
				<div id=\"map\">
				</div>
			</div><!-- /#mapContainer -->
			
			<link rel=\"stylesheet\" href=\"//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/leaflet.css\" />
			<script src=\"//cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/leaflet.js\"></script>
			
			<link rel=\"stylesheet\" href=\"{$this->baseUrl}/js/lib/leaflet_numbered_markers/leaflet_numbered_markers.css\" />
			<script src=\"{$this->baseUrl}/js/lib/leaflet_numbered_markers/leaflet_numbered_markers.js\"></script>
			
			<script type=\"text/javascript\">
				
				// Create a map in the map div, set the view to a given place and zoom; this is then overriden using the fitBounds call below
				var map = L.map('map').setView([54.000919, -2.44873], 6);
				
				// Add an OpenStreetMap tile layer
				L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				    attribution: '&copy; <a href=\"https://openstreetmap.org/copyright\">OpenStreetMap</a> contributors'
				}).addTo(map);
				
				// Define popup callback
				function onEachFeature(feature, layer) {
					
					// Define popup contents
					var popupContent = '<table class=\"lines compressed\">';
					feature.properties.id = '<a href=\"{$this->baseUrl}/location/' + feature.properties.id.replace(' ', '+').toLowerCase() + '/\">' + feature.properties.id + '</a>';	// Hyperlink ID field
					feature.properties.subject = '<strong>' + feature.properties.subject + '</strong>';
					for (var key in feature.properties) {
						popupContent += '<tr>' + '<td>' + key + ':</td>' + '<td>' + feature.properties[key] + '</td>' + '</tr>';
					}
					popupContent += '</table>';
					
					if (feature.properties && feature.properties.popupContent) {
						popupContent += feature.properties.popupContent;
					}
					layer.bindPopup(popupContent);
				}
				
				// Add the points, showing the photo number in the film within the marker
				var geojsonPoints = jQuery.parseJSON('" . str_replace ("\n", ' ', str_replace ("\r", '', str_replace ("'", "\'", trim ($geojsonPoints)))) . "');
				var geojsonPointsLayer = L.geoJson(geojsonPoints, {
					onEachFeature: onEachFeature,
					pointToLayer: function(feature, latlng) {
						/* https://stackoverflow.com/a/22676351/180733 */
						return new L.Marker(latlng, {
						    icon: new L.NumberedDivIcon({number: feature.properties.photoNumberInFilm})
						});
					},
				});
				geojsonPointsLayer.addTo(map);
				
				// Add the line
				var geojsonLine = jQuery.parseJSON('" . str_replace ("\n", ' ', str_replace ("\r", '', str_replace ("'", "\'", trim ($geojsonLine)))) . "');
				var geojsonLineLayer = L.geoJson(geojsonLine);
				geojsonLineLayer.addTo(map).addTo(map);
				
				// Zoom to bounds
				map.fitBounds(geojsonPointsLayer.getBounds().pad(0.5));
				
			</script>
			";
			
			# Assemble the HTML
			$html .= "\n" . '<div id="filmlisting">' . "\n" . $table . '</div>';
			$html .= "\n" . $mapHtml;
		}
		
		# Templatise
		$this->template['contentHtml'] = $html;
		$html = $this->templatise ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to get all film roll groupings
	private function getRolls ()
	{
		# Get the list of film rolls and the count of items in each roll
		$query = "SELECT IF(filmRollNumber IS NULL, '-', filmRollNumber), COUNT(*) AS total FROM {$this->settings['database']}.{$this->settings['table']} GROUP BY filmRollNumber ORDER BY filmRollNumber;";
		$data = $this->databaseConnection->getPairs ($query);
		
		# Return the data
		return $data;
	}
	
	
	# Function to get all locations for a film roll
	private function getLocationsInRoll ($filmRollNumber)
	{
		# Get the list of film rolls and the count of items in each roll
		#!# Can't use associative as database does not have unique IDs yet
		$query = "SELECT id,photoNumberInFilm,photoDate,subject,osMapSheet AS sheet,latitude,longitude,eastings,northings FROM {$this->settings['database']}.{$this->settings['table']} WHERE filmRollNumber = :filmRollNumber ORDER BY natsort;";
		$data = $this->databaseConnection->getData ($query, $associative = false, true, array ('filmRollNumber' => $filmRollNumber));
		
		# Return the data
		return $data;
	}
	
	
	# Import
	public function import ()
	{
		# Prevent timeouts
		ini_set ('max_execution_time', 0);
		
		# Start the HTML
		$html = '';
		
		# Prevent parallel runs
		$lockfile = $this->applicationRoot . '/import/' . 'lockfile.txt';
		if (file_exists ($lockfile)) {
			$lockfileText = file_get_contents ($lockfile);
			list ($username, $timestamp) = explode (' ', $lockfileText, 2);
			$html  = "\n<div class=\"graybox\">";
			$html .= "\n<p class=\"warning\">An import (which was started by {$username} at {$timestamp}) is currently running; please try again later.</p>";
			$html .= "\n<p class=\"warning\">This page will automatically refresh to show when the import is finished.</p>";
			$html .= "\n<meta http-equiv=\"refresh\" content=\"10;URL=" . htmlspecialchars ($_SERVER['_PAGE_URL']) . "\">";
			$html .= "\n</div>";
			// $html .= $this->showImportSeive ();
			echo $html;
			return;
		}
		
		# Set the start time
		$startTime = time ();
		
		# Start the HTML with instructions
		$html .= "\n" . '<p><strong>Run the import</strong> using the form below.</p>';
		
		# Load the importer
		require_once ('import/import.php');
		$cucapImport = new cucapImport ($this->settings, $this->applicationRoot);
		$stages = $cucapImport->getStages ();
		
		# Create the form
		$form = new form (array (
			'submitButtonText' => 'Begin import!',
			'div' => 'graybox',
			'name' => 'import',
			'requiredFieldIndicator' => false,
			'display' => 'paragraphs',
			'displayRestrictions' => false,
		));
		$form->checkboxes (array (
			'name'		=> 'stages',
			'title'		=> 'Run stages',
			'values'	=> $stages,
			'default'	=> array_keys ($stages),
			'required'	=> true,
			'entities'	=> false,
		));
		$form->input (array (
			'name'			=> 'table',
			'title'			=> 'Table suffix',
			'required'		=> true,
			'default'		=> date ('ymd_His'),
		));
		if (!$result = $form->process ($html)) {
			// $html .= $this->showImportSeive ();
			echo $html;
			return;
		}
		
		# Filter for selected items
		$stages = array ();
		foreach ($result['stages'] as $stage => $selected) {
			if ($selected) {
				$stages[] = $stage;
			}
		}
		
		# Write the lockfile
		file_put_contents ($lockfile, $_SERVER['REMOTE_USER'] . ' ' . date ('Y-m-d H:i:s'));
		
		# Run the import, resetting the HTML
		$importSuccess = $cucapImport->doImport ($result['table'], $stages, $errorMessage);
		if ($importSuccess) {
			$html = "\n<p>{$this->tick} The import stage(s) completed successfully.</p>";
		} else {
			$html = "\n<p>{$this->cross} {$errorMessage}</p>";
		}
		
		# Remove the lockfile
		unlink ($lockfile);
		
		# Show the time the import has taken
		$finishTime = time ();
		$seconds = $finishTime - $startTime;
		$html .= "\n<p>The import took: {$seconds} seconds.</p>";
		
		# Show the seive
		// $html .= $this->showImportSeive ();
		
		# Show the HTML
		echo $html;
	}
	
	
	/*
	# Function to show the SQL seive
	public function showImportSeive ()
	{
		# Get the file contents
		$file = $this->applicationRoot . '/import/import.sql';
		if (is_readable ($file)) {
			$html = file_get_contents ($file);
		} else {
			$html = "\n<p>There was a problem reading the import file.</p>";
		}
		
		# Set as preformatted
		$html = "\n<pre>" . $html . "\n</pre>";
		
		# Surround in box
		$html = "\n<div class=\"graybox\">" . $html . "\n</div>";
		
		# Add title
		$html = "\n<h3>Import routine</h3>" . $html;
		
		# Return the HTML
		return $html;
	}
	*/
}

?>
