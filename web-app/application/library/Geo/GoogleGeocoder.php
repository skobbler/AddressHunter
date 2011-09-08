<?php
/**
 * AddressHunter
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.opensource.org/licenses/BSD-3-Clause
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@addresshunter.net so we can send you a copy immediately.
 *
 * @package   AddressHunter
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause	 New BSD License
 * @version   $Id$
 */

/**
 * Performs a geocoding using Google's Geocoding service
 *
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause	 New BSD License
 */
class Geo_GoogleGeocoder implements Geo_GeocoderInterface {

	/**
	 * Performs the geocoding.
	 *
	 * @param string $addressline The addressline to be geocoded
	 * @param array $invokeArgs Any additional invocation arguments
	 * @return Geo_Geolocation|null
	 */
	public static function geocode($addressline, array $invokeArgs = array())
	{
		$addressline = urlencode($addressline);

		$url = 'http://maps.google.com/maps/api/geocode/json?address=' . $addressline . '&sensor=false';
		
		// adding 'client' param (only for Google Maps API Premier customers)
		if (!empty($invokeArgs['client'])) {
			$url .= '&client=' . $invokeArgs['client'];
		}
		
		// signing the URL (only for Google Maps API Premier customers)
		if (!empty($invokeArgs['key'])) {
			$url = self::signUrl($url, $invokeArgs['key']);
		}

		$defaults = array(
			CURLOPT_HEADER => 0,
			CURLOPT_URL => $url,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FORBID_REUSE => 1,
			CURLOPT_TIMEOUT => 10,
			//CURLOPT_VERBOSE => true, // for debugging
		);

		//print "\n\n" . $url . "\n\n";

		$curlSession = curl_init();
		curl_setopt_array($curlSession, $defaults);
		if (!$response = curl_exec($curlSession)) {
			trigger_error("ADST address import error: " . curl_error($curlSession));
			curl_close($curlSession);
			return false;
		}
		curl_close($curlSession);

		//convert json response to associative array
		$response = json_decode($response, true);
		//print "\n"; print_r($response); print "\n";

		// address_component types of interest
		$types = array(
			'country',
			'postal_code',
			'administrative_area_level_1',
			'administrative_area_level_2',
			'locality',
			'sublocality',
			'route',
			'street_number'
		);

		if ($response['status'] == "OK") {

			$result = $response['results'][0];

			// normalizing address_components for easier access
			$components = array();
			foreach ($result['address_components'] as $component) {
				foreach ($component['types'] as $type) {
					if (in_array($type, $types)) {
						$components[$type] = array(
							'long_name' => $component['long_name'],
							'short_name' => $component['short_name']
						);
					}
				}
			}

			// creating a Geolocation obj based on the result and the normalized address components
			$geoloc = new Geo_Geolocation();

			$geoloc->addressline = $result['formatted_address'];
			$geoloc->coordinates = array(
				'lat' => $result['geometry']['location']['lat'],
				'lon' => $result['geometry']['location']['lng']
			);

			if (isset($components['country'])) {
				$geoloc->country = $components['country']['long_name'];
				$geoloc->countryCode = strtoupper($components['country']['short_name']);
			}
			if (isset($components['administrative_area_level_1'])) {
				$geoloc->state = $components['administrative_area_level_1']['long_name'];
			}
			if (isset($components['administrative_area_level_2'])) {
				$geoloc->county = $components['administrative_area_level_2']['long_name'];
			}
			if (isset($components['locality'])) {
				$geoloc->locality = $components['locality']['long_name'];
			}
			if (isset($components['sublocality'])) {
				$geoloc->sublocality = $components['sublocality']['long_name'];
			}
			if (isset($components['postal_code'])) {
				$geoloc->postalCode = $components['postal_code']['long_name'];
			}
			if (isset($components['route'])) {
				$geoloc->streetName = $components['route']['long_name'];
			}
			if (isset($components['street_number'])) {
				$geoloc->houseNumber = $components['street_number']['long_name'];
			}

			$geoloc->fillAccuracy();

			return $geoloc;

		} else {
			return null;
		}
	}

	// encodes a string to URL-safe base64
	public static function encodeBase64UrlSafe($value)
	{
		return str_replace(array('+', '/'), array('-', '_'), base64_encode($value));
	}

	// decodes a string from URL-safe base64
	public static function decodeBase64UrlSafe($value)
	{
		return base64_decode(str_replace(array('-', '_'), array('+', '/'), $value));
	}

	// signs a URL with a given crypto key (Note: this URL must be properly URL-encoded)
	public static function signUrl($myUrlToSign, $privateKey)
	{
		// parse the url
		$url = parse_url($myUrlToSign);

		$urlPartToSign = $url['path'] . "?" . $url['query'];

		// decode the private key into its binary format
		$decodedKey = self::decodeBase64UrlSafe($privateKey);

		// create a signature using the private key and the URL-encoded
		// string using HMAC SHA1. This signature will be binary.
		$signature = hash_hmac("sha1", $urlPartToSign, $decodedKey, true);

		$encodedSignature = self::encodeBase64UrlSafe($signature);

		return $myUrlToSign . "&signature=" . $encodedSignature;
	}
}
