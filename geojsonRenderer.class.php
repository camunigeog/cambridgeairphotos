<?php

/**
 * Class to convert a data structure (e.g. a set of geographical points) into a GeoJSON standard data structure
 * https://geojson.org/geojson-spec.html
 */
class geojsonRenderer
{
	# Registry of features
	private $features = array ();
	
	
	
	# Public getter, returning the data structure
	public function getData ()
	{
		# Assemble the data
		$data = array (
			'type'	=> 'FeatureCollection'
		);
		
		# Always include Features
		$data['features'] = $this->features;
		
		# Return the data
		return $data;
	}
	
	
	# Public getter, returning the data structure as a string
	public function getGeojson ()
	{
		# Return the JSON encoded data
		return json_encode ($this->getData ());
	}
	
	
	/**
	 * Adds a point
	 * @param float $longitude
	 * @param float $latitude
	 * @param array $properties (or just the name as a string)
	 * @return void
	 */
	public function point ($longitude, $latitude, $properties)
	{
		# Assemble the co-ordinate as an array
		$coordinate = array ((float) $longitude, (float) $latitude);
		
		# Add
		$this->addFeature (ucfirst (__FUNCTION__), $coordinate, $properties);
	}
	
	
	/**
	 * Adds a linestring
	 * @param array $coordinates in format: 'x1 y1', 'x2 y2', ... , 'xn yn'
	 * @param array $properties
	 * @return void
	 */
	public function lineString ($coordinates, $properties)
	{
		# Convert all to numbers
		foreach ($coordinates as $index => $coordinate) {
			$coordinates[$index] = array ((float) $coordinate[0], (float) $coordinate[1]);
		}
		
		# Add
		$this->addFeature (ucfirst (__FUNCTION__), $coordinates, $properties);
	}
	
	
	/**
	 * Helper function adds the feature to the registry
	 */
	private function addFeature ($geojsonClass, $coordinates, $properties)
	{
		# Assemble the feature
		$feature = array ('type' => 'Feature');
		
		# Add any properties
		if ($properties) {
			$feature['properties'] = $properties;
		}
		
		# Add geometry after propertes to make them easier to find in raw listings
		$feature['geometry'] = array (
			'type'			=> $geojsonClass,
			'coordinates'	=> $coordinates,
		);
		
		# Add the feature to the registry
		$this->features[] = $feature;
	}
}

?>