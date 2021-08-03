// Telluswhere javascript module
var telluswhere = (function ($) {
	'use strict';
	
	
	/* Class properties */
	
	// Internal class properties
	var map;	// Should be _map but makes pasting from leaflet examples (which always use map) easier
	var _marker;
	var _icons;
	var _useJsonpTransport;
	var _currentDataLayer;
	var _currentDataLayer2;
	var _geolocationData;
	var _maxZoom;
	
	// baseUrl of application
	var _baseUrl;
	
	// Initial map location
	var _initialLatitude;
	var _initialLongitude;
	var _initialZoom;
	
	// The API endpoint to use for browsing
	var _browsingApiUrl;
	var _browsingApiUrl2;
	
	// The icon to use
	var _useIcon;
	
	// Whether to set a marker initially
	var _setMarkerInitially;
	
	// Selected ID, if any, and whether it is moveable
	var _selectedId;
	
    // Preview mode, which shows a popup before clicking
	var _previewMode = true;
	var _popupTimer = false;
	var _popupDelay = 150; // in milliseconds
	var _permanentPopup = false;
	
	
	return {
		
// Public functions
		
		// Main function
		createMap: function(baseUrl, initialLatitude, initialLongitude, initialZoom, browsingApiUrl, useIcon, setMarkerInitially, markerSetInitiallyIsDraggable, selectedId, browsingApiUrl2, visibleLayers) {
			
			// Set class properties
			_baseUrl = baseUrl;
			_initialLatitude = initialLatitude;
			_initialLongitude = initialLongitude;
			_initialZoom = initialZoom;
			_browsingApiUrl = browsingApiUrl;
			_browsingApiUrl2 = browsingApiUrl2;
			_useIcon = useIcon;
			_setMarkerInitially = setMarkerInitially;
			_selectedId = selectedId;	// ID of selected item
			
			// Max zoom level
			_maxZoom = 18;
			
			// Define available base layers
			// Specify the base layers to show
			var openstreetmap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: "<a href='https://www.openstreetmap.org/'>&copy; OpenStreetMap</a> contributors", maxZoom: _maxZoom})
			var opencyclemap = L.tileLayer('https://{s}.tile.opencyclemap.org/cycle/{z}/{x}/{y}.png', {attribution: "Map: <a href='https://www.opencyclemap.org/'>OpenCycleMap</a>" + " | Data: <a href='https://www.openstreetmap.org/'>&copy; OpenStreetMap</a> contributors", maxZoom: _maxZoom});
			var osopendata = L.tileLayer('https://{s}.os.openstreetmap.org/sv/{z}/{x}/{y}.png', {attribution: "Contains Ordnance Survey data &copy; Crown copyright and database right 2010", maxZoom: _maxZoom});
			var googlesatellite = new L.Google('SATELLITE');	// https://github.com/shramov/leaflet-plugins/blob/master/layer/tile/Google.js
			var baseLayers = {
				"OpenStreetMap": openstreetmap,
				"OpenCycleMap (shows hills)": opencyclemap,
				"OS Open Data": osopendata,
				"Google satellite": googlesatellite,
			};
			
			// Determine if a location cookie has been set on a previous map view
			if (!setMarkerInitially && !selectedId) {
				var cookieLocationString = telluswhere.readCookie('centerLngLatZoom');
				if(cookieLocationString) {
					var cookieLocation = cookieLocationString.split(',');
					_initialLongitude = cookieLocation[0];
					_initialLatitude = cookieLocation[1];
					_initialZoom = cookieLocation[2];
				}
			}
			
			// Set map centre location
			map = L.map('map', {layers: [openstreetmap]});
			map.setActiveArea('activeArea');
			map.setView ([_initialLatitude, _initialLongitude], _initialZoom);
			
			// Add the control
			L.control.layers(baseLayers).addTo(map);

			// Define the icon set; see: http://leafletjs.com/examples/custom-icons.html
			_icons = telluswhere.getIcons();
			
			// Geolocate the user on first run
			if(!_setMarkerInitially && !cookieLocation){
				telluswhere.geolocateUser();
			}
			
			// Determine whether to set the marker initially
			if(_setMarkerInitially){
				var latlng = L.latLng(_initialLatitude, _initialLongitude);
				telluswhere.setMarker(latlng, _useIcon, markerSetInitiallyIsDraggable);
				map.setView(latlng,_initialZoom);
			}
			
//			// Register click handler
//			map.on('click', telluswhere.onMapClick);
			
			// Add the data layer to the map
			_currentDataLayer = L.geoJson(null, {
				pointToLayer: telluswhere.setIcon,
				filter: telluswhere.setIconFilter
			});
			_currentDataLayer.addTo(map);
			
			// Add second data layer to the map if defined
			if(_browsingApiUrl2) {
				_currentDataLayer2 = L.geoJson(null, {
					pointToLayer: telluswhere.setIcon,
				});
				_currentDataLayer2.addTo(map);
			}
			
			// Determine whether to use JSONP transport instead of JSON for the marker layer calls (for older browsers)
			_useJsonpTransport = telluswhere.useJsonpTransport();
			
			// Register moveend
			map.on('moveend', telluswhere.whenMapMoves);
			
			// Preview mode popupclose handler
			map.on ('popupclose', function (e) {telluswhere.setPermanentPopup(false);});
			
			// Add a facility for whether to suppress placeholders
			telluswhere.placeholderSupression();
			
			// Get the data on initial view
			telluswhere.getData();
			
			// Add drawing tools
			telluswhere.addDrawingTools();
			
			// Register reporting link function
			if (_useIcon == 'current') {
				map.on('popupopen', telluswhere.problemForm);
			}
			
			// Show the help text also if the user zooms
			map.on('zoomstart', function() {
				$('#helptext').addClass('display');
			});
			
			// EXIF callback for file upload
			try {
				$('#form_file_0').change(function() {
					$(this).fileExif(telluswhere.exifCallback);
				});
			}
			catch (e) {
				alert(e);
			}
			
			// Return map
			return map;
		},
		
		
// Private functions

		/* Core map functions */
		
		
		// Icon definition
		getIcons: function() {
			
			// Define basic large and small icons
			var largeIcon = L.Icon.extend({
				options: {
					shadowUrl: _baseUrl + '/images/shadow-large.png',
					iconSize:     [34, 40],
					shadowSize:   [51, 38],
					iconAnchor:   [17, 40],
					shadowAnchor: [0, 38],
					popupAnchor:  [0, -36]
				}
			});
			var smallIcon = L.Icon.extend({
				options: {
					shadowUrl: _baseUrl + '/images/shadow-small.png',
					iconSize:     [27, 30],
					shadowSize:   [43, 34],
					iconAnchor:   [13, 30],
					shadowAnchor: [0, 34],
					popupAnchor:  [0, -26]
				}
			});
			var cucapIcon = L.Icon.extend({
				options: {
					shadowUrl: _baseUrl + '/images/shadow.png',
					iconSize:     [19, 26],
					shadowSize:   [24, 14],
					iconAnchor:   [9, 26],
					shadowAnchor: [3, 14],
					popupAnchor:  [0, -26]
				}
			});
			
			// Assemble the icons list
			var icons = {
				oblique: new cucapIcon({iconUrl: _baseUrl + '/images/oblique.png'}),
				oblique_with_photo: new cucapIcon({iconUrl: _baseUrl + '/images/oblique_with_photo.png'}),
				vertical: new cucapIcon({iconUrl: _baseUrl + '/images/vertical.png'}),
				vertical_with_photo: new cucapIcon({iconUrl: _baseUrl + '/images/vertical_with_photo.png'}),
				obliqueLarge: new largeIcon({iconUrl: _baseUrl + '/images/oblique.png'}),
				verticalLarge: new largeIcon({iconUrl: _baseUrl + '/images/vertical.png'})
			};
			
			// Return the icons
			return icons;
		},
		
		
		// Function to provide a placeholder supression facility
		placeholderSupression: function()
		{
			// Define cookie functions; see: http://stackoverflow.com/a/1460174/180733
			function createCookie(name, value, days) {
			    var expires;
			
			    if (days) {
			        var date = new Date();
			        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			        expires = "; expires=" + date.toGMTString();
			    } else {
			        expires = "";
			    }
			    document.cookie = escape(name) + "=" + escape(value) + expires + "; path=/";
			}
			function readCookie(name) {
			    var nameEQ = escape(name) + "=";
			    var ca = document.cookie.split(';');
			    for (var i = 0; i < ca.length; i++) {
			        var c = ca[i];
			        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
			        if (c.indexOf(nameEQ) === 0) return unescape(c.substring(nameEQ.length, c.length));
			    }
			    return null;
			}
			function eraseCookie(name) {
			    createCookie(name, "", -1);
			}
			
			// Add the checkbox
			_browsingApiUrl = _browsingApiUrl.replace(/&supressplaceholders=1/, '');	// Clear any existing value
			var cookieName = 'supressplaceholders';
			var checkedPreviously = readCookie(cookieName);
			if (checkedPreviously) {
				_browsingApiUrl += '&supressplaceholders=1';
			}
			$('#map').before('<p class="supressplaceholders"><label><input type="checkbox" id="supressplaceholders"' + (checkedPreviously ? ' checked="checked"' : '') + ' /> Show locations with images only</label></p>');
			$('#supressplaceholders').change(function(){
				if (this.checked) {
					_browsingApiUrl += '&supressplaceholders=1';
					createCookie(cookieName, 1, 7);
				} else {
					_browsingApiUrl = _browsingApiUrl.replace(/&supressplaceholders=1/, '');
					eraseCookie(cookieName);
				}
				telluswhere.getData();
			});
		},
		
		
		// Function to geolocate the user
		geolocateUser: function()
		{
			map.locate({setView: true, maxZoom: 18});
		},
		
		
		// Create marker and popup when clicking on the map
		onMapClick: function(e)
		{
			
			// Show the help text
			$('#helptext').addClass('display');
			
			// Remove any marker present
			if(telluswhere._marker){
				map.removeLayer(telluswhere._marker);
			}
			
			// Define minimum zoom level to set
			var minZoomLevelToSet = 18;
			
			// Zoom if too far out and end
			if(map.getZoom() < minZoomLevelToSet){
				telluswhere.setFormValues (null, null, null);	// Clear any saved values
				var currentZoomLevel = map.getZoom();
				var zoomBy = (((minZoomLevelToSet - currentZoomLevel) <= 2) ? 1 : 2);	// When very zoomed in, zoom in less far, to avoid disorientation
				var newZoomLevel = currentZoomLevel + zoomBy;
				// alert('Current zoom: ' + currentZoomLevel + '; zooming by: ' + zoomBy + ' to: ' + newZoomLevel);
				map.setZoomAround(e.latlng, newZoomLevel);
				return;
			}
			
			// Set the marker
			telluswhere.setMarker(e.latlng, _useIcon, true);
			
			// Remove the help text
			$('#helptext').removeClass('display').addClass('hide');
		},
		
		
		// Wrapper function to set the marker by supplying raw latitude and longitude markers
		setMarkerLatitudeLongitude: function(latitude, longitude)
		{
			var latlng = L.latLng(latitude, longitude);
			map.setView(latlng, _maxZoom);
			telluswhere.setMarker(latlng, _useIcon, true);
		},
		
		
		// Function to set the marker
		setMarker: function(latlng, useIcon, markerIsDraggable) {
			
			// Clear any previously-set marker
			if(_marker){
				map.removeLayer(_marker);
			}
			
			// Set marker position
			_marker = new L.Marker(latlng, {icon: _icons[useIcon], draggable: markerIsDraggable, zIndexOffset: 1000});
			map.addLayer(_marker);
			//_marker.bindPopup('Location is here').openPopup();
			
			// Register dragend processing function
			_marker.on('dragend', telluswhere.markerDrag);
			
			// Transmit the value to the form
			telluswhere.setFormValues (latlng.lat, latlng.lng, map.getZoom());
		},
		
		
		// After dragging, transmit the value to the form, and reopen the popup
		markerDrag: function(e) {
			telluswhere.setFormValues (e.target._latlng.lat, e.target._latlng.lng, map.getZoom());
			_marker.openPopup();
		},
		
		
		// Function to transmit the values to the form
		setFormValues: function(lat, lng, zoom) {
			if ($('#form_latitude').length > 0) {
				$('#form_latitude').val(lat);
				$('#form_longitude').val(lng);
				$('#form_zoom').val(zoom);
			}
		},
		
		
		/* EXIF image marker setting functions */
		
		// Register function for adding to map
		exifCallback: function(exifObject) {
			if(_marker){
				map.removeLayer(_marker);
			}
			_geolocationData = telluswhere.extractGeolocationData(exifObject);
			if(_geolocationData) {
				telluswhere.setMarkerLatitudeLongitude(_geolocationData.latitude, _geolocationData.longitude);
			}
			//console.log(exifObject);
		},
		
		
		// Function to convert the complex EXIF geolocation data structure into standard lat,lon,bearing; see: https://confluence.videoplaza.org/display/BLOG/2012/07/22/Geolocation+data+from+Images
		extractGeolocationData: function(exifObject) {
			
			// End if no data
			var aLat = exifObject.GPSLatitude;
			var aLon = exifObject.GPSLongitude;
			if (!aLat || !aLon) {return;}
			
			// Convert from minutes/seconds/degrees to decimal
			var strLatRef = exifObject.GPSLatitudeRef || 'N';
			var strLongRef = exifObject.GPSLongitudeRef || 'W';
			var latitude = (aLat[0] + aLat[1]/60 + aLat[2]/3600) * (strLatRef == 'N' ? 1 : -1);
			var longitude = (aLon[0] + aLon[1]/60 + aLon[2]/3600) * (strLongRef == 'W' ? -1 : 1);
			
			// Assemble the object to be returned
			var geolocationData = new Array;
			geolocationData['latitude'] = latitude;
			geolocationData['longitude'] = longitude;
			
			// Return the object
			return geolocationData;
		},
		
		
		
		/* Existing locations browsing functions; see: http://chris-osm.blogspot.co.uk/2013/11/using-leaflet-with-database.html */
		
		
		// Newline-to-breaks helper function
		nl2br: function(str, is_xhtml) {
			var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
			return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
		},
		
		
		// String truncate function to avoid over-long caption texts causing large bubbles
		truncateString: function(str, length) {
			return (str.length > length ? str.substring(0, length - 3) + '...' : str);
		},
		
		
		// Define HTML to be used in the popup
		popupHtml: function(feature) {
			
			var linkToPage = _baseUrl + '/location/' + encodeURIComponent(feature.properties.id.toLowerCase()) + '/';
			var thumbnailSize = 200;
			
			var html = ''
			
			+ (feature.properties.hasPhoto ? '<div class=\"bubble photobubble\">' :  '<div class=\"bubble placeholderbubble nophoto\">')
			+ '<h3>' + '<a title=\"Click for main page on this catalogue entry\" hr' + 'ef=\"' + linkToPage + '\">' + telluswhere.nl2br(feature.properties.name, true) + '</a></h3>'
			+ '<p class=\"peekimage\">' + '<a title=\"Click for main page on this catalogue entry\" hr' + 'ef=\"' + linkToPage + '\">'
			+ (feature.properties.hasPhoto ?
				'<img src="' + feature.properties.hasPhoto + '" alt="' + telluswhere.htmlspecialchars(feature.properties.name) + '" width="' + thumbnailSize + '" />'
			:
				'<span class=\"faded\">(No thumbnail<br />available yet)</span>'
			)
			 + '</a>'
			+ '</p>'
			
			+ '<table class=\"lines compressed metadata\">'
				+ '<tr><td>CUCAP no.: </td><td><strong>' + '<a title=\"Click for main page on this catalogue entry\" hr' + 'ef=\"' + linkToPage + '\">' + telluswhere.nl2br(feature.properties.id, true) + '</a></strong></td></tr>'
				+ '<tr><td>Photo date: </td><td><strong>' + telluswhere.nl2br(feature.properties.photoDate, true) + (feature.properties.photoTime ? ', ' + telluswhere.nl2br(feature.properties.photoTime, true) : '') + '</strong></td></tr>'
				+ '<tr><td>Type: </td><td>' + feature.properties.type + '</td></tr>'
				+ '<tr><td>Film type: </td><td>' + (feature.properties.filmType != null ? feature.properties.filmType : '[Unknown]') + '</td></tr>'
				+ '<tr><td>View direction: </td><td>' + (feature.properties.viewDirection != null ? telluswhere.nl2br(feature.properties.viewDirection, true) + '&deg;' : '[Unknown]') + '</td></tr>'
				+ '<tr><td>Latitude: </td><td>' + feature.geometry.coordinates[1] + '</td></tr>'
				+ '<tr><td>Longitude: </td><td>' + feature.geometry.coordinates[0] + '</td></tr>'
				+ '<tr><td>Eastings: </td><td>' + feature.properties.eastings + '</td></tr>'
				+ '<tr><td>Northings: </td><td>' + feature.properties.northings + '</td></tr>'
//				+ '<tr><td>OS map sheet: </td><td>' + (feature.properties.osMapSheet ? telluswhere.nl2br(feature.properties.osMapSheet, true) : '[Unknown]') + '</td></tr>'
//				+ '<tr><td>Copyright: </td><td>' + telluswhere.nl2br(feature.properties.copyright, true) + '</td></tr>'
			+ '</table>'
			
			+ '</div>';
			
			// Return HTML
			return html;
		},
		
		
		// Function to set the marker and attach a popup
		setIcon: function(feature,latlng) {
			
			// Create a marker and bind the popup to it
			var useIcon = feature.properties.type.toLowerCase();
			if(feature.properties.hasPhoto != false) {
				useIcon += '_with_photo';
			}
			var marker = L.marker(latlng, {icon: _icons[useIcon], opacity: 0.85});
			marker.bindPopup(telluswhere.popupHtml(feature), {autoPanPaddingTopLeft: [50, 60], autoPanPaddingBottomRight: [200, 50]});	// autoPanPadding avoids position clashes with the various overlays
			
		    // Preview mode popus up the icon on hover
		    if (_previewMode) {
	
				// Based on http://stackoverflow.com/questions/13002961/leaflet-different-hover-and-click-events
				marker.on('mouseover', function (evt) {
	
				    // Clear any previously set popup timer
				    clearTimeout(telluswhere._popupTimer);
	
				    // Skip the rest if the opened popup is permanent
				    if (telluswhere.getPermanentPopup ()) {return;}
	
				    // Show the popup after a small delay - to avoid flicker
				    telluswhere._popupTimer = setTimeout(function () {
	
					// evt.target is the marker that is being moused over 
					// evt.target.setIcon(poiIcons['bicycles']);
					evt.target.bindPopup (telluswhere.popupHtml (feature, true), {closeButton: false}).openPopup();
					telluswhere.setPermanentPopup (false);
				    }, telluswhere.getPopupDelay ());
				});
	
				marker.on('mouseout', function(evt) {
				    // Clear any previously set popup timer
				    clearTimeout(telluswhere._popupTimer);
	
				    // Skip the rest if the opened popup is permanent
				    if (telluswhere.getPermanentPopup ()) {return;}
	
				    // Close the popup if it was previewed
				    evt.target.closePopup();
				});
	
				marker.on('click', function(evt) {
				    // again, evt.target will contain the marker that was clicked
				    // console.log('you clicked a marker');
				    evt.target.bindPopup (telluswhere.popupHtml (feature, false), {minWidth: 350}).openPopup();
				    telluswhere.setPermanentPopup (true);
				});
		    }
			
			// Return the marker
			return marker;
		},
		
		
		/* Preview mode: getters and setters */
		getPopupDelay: function () {return _popupDelay;},
		getPermanentPopup: function () {return _permanentPopup;},
		setPreviewMode: function (value) {_previewMode = value;},
		setPermanentPopup: function (value) {_permanentPopup = value;},
		
		
		// Filter to control visibility of items set with setIcon
		setIconFilter: function(feature,layer) {
		
			// If an item is selected, skip, as this will already be on the map
			if (_selectedId) {
				var id = parseInt(feature.properties.id, 10);	// base 10
				if (id == _selectedId) {
					return false;
				}
			}
			
			// Show icon by default
			return true;
		},
		
		
		// Show data layer (wrapper to implementation function)
		showCurrentData: function(ajaxResponse) {
			telluswhere.showCurrentDataLayer (ajaxResponse, _currentDataLayer);
		},
		
		
		// Show second data layer (wrapper to implementation function)
		showCurrentData2: function(ajaxResponse) {
			telluswhere.showCurrentDataLayer (ajaxResponse, _currentDataLayer2);
		},
		
		
		// Inner function to fetch current marker data
		showCurrentDataLayer: function(ajaxResponse, selectedLayer) {
			
			// Remove all markers except those with open popups
			selectedLayer.eachLayer (function (layer) {if (!layer._popup._isOpen) {selectedLayer.removeLayer (layer);}});

			// Add the data
			selectedLayer.addData (ajaxResponse);

			// Markers with opened popups remain - this brings the old ones back on top
			// Note: the previous markers are still there underneath - put are probably benign.
			selectedLayer.eachLayer (function (layer) {if (layer._popup._isOpen) { selectedLayer.bringToFront (layer);}});
			
			// Open the selected popup if required
			if (_selectedId) {
				_currentDataLayer.eachLayer(function (layer) {
				    if (layer.feature.properties.id != _selectedId) {return;}
					layer.openPopup();
					_selectedId = false;	// Prevent the bubble reappearing on map move subsequent to selection of a different icon
				});
				
				map.setView ([_initialLatitude, _initialLongitude], _initialZoom);
			}
		},
		
		
		// Function to determine requirement for IE<=9 to use JSONP instead of JSON; see: http://stackoverflow.com/a/19562445/180733
		useJsonpTransport: function() {
			
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
		},
		
		
		// Wrapper function to fetch current marker data layer/layers
		getData: function() {
			
			// Get data layer (pass to implementation function)
			telluswhere.getDataLayer(_browsingApiUrl, telluswhere.showCurrentData);
			
			// Get second data layer if defined
			if(_browsingApiUrl2) {
				telluswhere.getDataLayer(_browsingApiUrl2, telluswhere.showCurrentData2);
			}
		},
		
		
		// Inner function to fetch current marker data
		getDataLayer: function (browsingApiUrl, successFunction) {
			var data='bbox=' + map.getBounds().toBBoxString();
			$.ajax({
				url: browsingApiUrl,
				dataType: (_useJsonpTransport ? 'jsonp' : 'json'),
				crossDomain: true,	// Needed for IE<=9; see: http://stackoverflow.com/a/12644252/180733
				data: data,
				success: successFunction
			});
		},
		
		
		// Define mapmove action
		whenMapMoves: function(e) {
			
			// Get data
			telluswhere.getData();
			
			// Set cookie for location
			var center = map.getCenter ();
			telluswhere.setCookie ('centerLngLatZoom', center.lng + ',' + center.lat + ',' + map.getZoom (), 7);
		},
		
		
		// Cookie helper function
		setCookie: function (name, value, days) {
			var date, expires = '';
			if (days) {
				date = new Date();
				date.setTime(date.getTime()+(days*24*60*60*1000));
				expires = "; expires="+date.toGMTString();
			}
			value = escape(value);	// Cookie values may not contain a comma: http://www.tutorialspoint.com/javascript/javascript_cookies.htm
			document.cookie = name + '=' + value + expires + '; path=' + _baseUrl + '/';
		},
		
		
		// Cookie helper function - create
		readCookie: function (name) {
			var i, c, nameEQ = name + '=', ca = document.cookie.split(';');
			for (i = 0; i < ca.length; i++) {
				c = ca[i];
				while (c.charAt(0) === ' ') {c = c.substring(1, c.length);}
				if (c.indexOf (nameEQ) === 0) {
					var value = c.substring (nameEQ.length, c.length);
					value = unescape(value);	// Cookie values may not contain a comma: http://www.tutorialspoint.com/javascript/javascript_cookies.htm
					return value;
				}
			}
			return null;
		},
		
		
		// Drawing tools, directly adding Leaflet.draw controls without using its built-in UI http://stackoverflow.com/questions/22730888/how-to-click-a-button-and-start-a-new-polygon-without-using-the-leaflet-draw-ui
		addDrawingTools: function () {
			
			// Create a map drawing layer
			var drawnItems = new L.FeatureGroup();
	        map.addLayer(drawnItems);
			
			// Options for polygon drawing
	        var polygon_options = {
	            showArea: false,
	            shapeOptions: {
	                stroke: true,
	                color: 'blue',
	                weight: 4,
	                opacity: 0.5,
	                fill: true,
	                fillColor: null, //same as color by default
	                fillOpacity: 0.2,
	                clickable: true
	            }
	        }
			
			// Disable the submit button by default, and set to be faded out
			$('#polygon_submit').attr('disabled', true).css({opacity: 0.2});
			
			// Enable the polygon drawing when the button is clicked
			var drawControl = new L.Draw.Polygon(map, polygon_options);
			$('.draw.area').click(function() {
				drawControl.enable();
				
				// Disable the submit button by default, and set to be faded out
				$('#polygon_submit').attr('disabled', true).css({opacity: 0.2});
				
				// Allow only a single polygon at present
				// #!# Remove this when the server-side allows multiple polygons
				drawnItems.clearLayers();
			});
			
			// Handle created polygons
	        map.on('draw:created', function (e) {
	            var type = e.layerType,
	            layer = e.layer;
	            drawnItems.addLayer(layer);
				
				// Enable submit button
				$('#polygon_submit').removeAttr('disabled').css({opacity: 1});
				
				// Send to receiving input form
				$('#issue_loc_json').val(JSON.stringify(drawnItems.toGeoJSON()));
	        });
			
			// Cancel button clears drawn polygon
			$('.edit-clear').click(function() {
				drawnItems.clearLayers();
			});
			
			// Undo button
			$('.edit-undo').click(function() {
				alert('edit');
				drawnItems.revertLayers();
			});
		},
		
		
		// Function run when clicking on the problem link to provide a mini correction updates form
		problemForm: function () {
			
			// If the link is clicked, replace the popup content
			$('p.problem a').click(function(e){
				
				// Create a form
				var formHtml = $("<form />", {name: 'problem', id: 'problem', method: 'POST', action: _baseUrl + '/location/' + $('p.problem a').data('id') + '/problem/'});
				
				// Add input fields to the form
				var formContentHtml = '';
				formContentHtml += '<input type="hidden" name="id" value="' + $('p.problem a').data('id') + '" autofocus="autofocus" />';
				formContentHtml += '<p>What is the issue with this entry?</p>';
				formContentHtml += '<textarea name="message" required="required"></textarea>';
				formContentHtml += '<p>In case we need to contact you for more info, what is your e-mail address?</p>';
				formContentHtml += '<input type="email" name="email" required="required" />';
				formContentHtml += '<p><input type="submit" id="submit" value="Submit" /></p>';
				formHtml.append(formContentHtml);
				
				// Replace the popup content with the form
				$('.leaflet-popup-content').html(formHtml);
				
				// Submit the form via AJAX
				var ajaxform = $('#problem');
				ajaxform.submit(function (e) {
					
					// Determine if form not complete, showing any error
					var thisFormOk = telluswhere.formOk('#problem', e);
					
					// Submit the form if no problem detected; based on: http://stackoverflow.com/questions/1960240/jquery-ajax-submit-form
					if (thisFormOk) {
						$.ajax({
							type: ajaxform.attr('method'),
							url: ajaxform.attr('action'),
							data: ajaxform.serialize(),
							success: function (data) {
								$('.leaflet-popup-content').html('<p>' + data.response + '</p>');
							},
							error: function (xhr, status, error) {
								var data = JSON.parse(xhr.responseText);
								$('.leaflet-popup-content').html('<p>' + data.response + '</p>');
							}
						});
						e.preventDefault();
					}
				});
				
				// Prevent link click taking effect
				e.preventDefault();
			});
		},
		
		
		// Function to check the form is complete; based on: http://toddmotto.com/progressively-enhancing-html5-forms-creating-a-required-attribute-fallback-with-jquery/
		formOk: function (formId, e){
			
			// Do feature detection of 'required' support
			var supportsRequired = 'required' in document.createElement('input');
			
			// Swap 'required' attribute with a class 'required', as non-HTML5 browsers do not see the required attribute
			$(formId + ' [required]').each(function () {
				if (!supportsRequired) {
					var self = $(this);
					self.removeAttr('required').addClass('required');
					//self.parent().append('<span class="form-error">Required</span>');
				}
			});
			
			// Loop through class name required
			var formOk = true;	// No problems at the start
			$(formId + ' .required').each(function () {
				var self = $(this);
				
				// Check shorthand if statement for input[type] detection
				var checked = ((self.is(':checkbox') || self.is(':radio')) 
					? self.is(':not(:checked)') && $('input[name=' + self.attr('name') + ']:checked').length === 0 
					: false);
				
				// Run the empty/not:checked test
				if (self.val() === '' || checked) {
					
					// Show error if the values are empty still (or re-emptied); this will fire after it's already been checked once
					//self.siblings('.form-error').show();
					//self.addClass('required');
					
					// Stop form submitting
					e.preventDefault();
					
					// Register problem
					formOk = false;
					
				// Hide error if passed the check
				} else {
					//self.siblings('.form-error').hide();
				}
			});
			
			// State form problem if not complete
			if (!formOk) {
				if (!$("#formwarning").length){
					$(formId).prepend('<p id="formwarning"></p>');
				}
				$('#formwarning').html('The form is not complete so has not yet been submitted:');
			}
			
			// Return the status
			return formOk;
		},
		
		
		htmlspecialchars: function (text) {
			return text
				.replace(/&/g, "&amp;")
				.replace(/</g, "&lt;")
				.replace(/>/g, "&gt;")
				.replace(/"/g, "&quot;")
				.replace(/'/g, "&#039;");
		}
		
	};
	
})(jQuery);
