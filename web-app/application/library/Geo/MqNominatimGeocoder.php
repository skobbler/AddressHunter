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
 * Performs a geocoding using MapQuest Nominatim service (http://open.mapquestapi.com/nominatim/)
 *
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause	 New BSD License
 */
class Geo_MqNominatimGeocoder implements Geo_GeocoderInterface {

	/**
	 * Performs the geocoding.
	 *
	 * @param string $addressline The addressline to be geocoded
	 * @param array $invokeArgs Any additional invocation arguments
	 * @return Geo_Geolocation|null|false
	 */
	public static function geocode($addressline, array $invokeArgs = array())
	{
		$mqApiUrl = "http://open.mapquestapi.com/nominatim/v1/search";
		$params = array(
			'format' => 'json',
			'limit' => 1,
			'addressdetails' => 1,
			'source' => 'addresshunter', // custom parameter for MapQuest to identify the request
			'q' => ''
		);

		try{
			// moving the housenumber to the beginning of the addressline
			$x = explode(',', $addressline);
			$y = explode(' ', $x[0]);
			if (!is_numeric($y[0]) && is_numeric($y[count($y)-1])) {
				$k = array_pop($y);
				array_unshift($y, $k);
				$x[0] = implode(' ', $y);
				$addressline = implode(',', $x);
			}

			$params['q'] = $addressline;
			$url = $mqApiUrl . "?" . http_build_query($params, '_', '&');
			//print "\n" . $url;

			$defaults = array(
				CURLOPT_HEADER => 0,
				CURLOPT_URL => $url,
				CURLOPT_FRESH_CONNECT => 1,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_FORBID_REUSE => 1,
				CURLOPT_HTTPHEADER => array(
					"Accept-Language: en-us,en;q=0.5",
				),
				CURLOPT_TIMEOUT => 20,
				//CURLOPT_VERBOSE => true, // for debugging
			);

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

			if (!empty($response[0]) && array_key_exists('address', $response[0])) {

				$result = $response[0];
				$address = $result['address'];

				// creating a Geolocation obj and filling it with data
				$geoloc = new Geo_Geolocation();

				$geoloc->id = $result['place_id'];
				$geoloc->addressline = $result['display_name'];
				$geoloc->coordinates = array(
					'lat' => $result['lat'],
					'lon' => $result['lon']
				);

				if (isset($address['country'])) {
					$geoloc->country = $address['country'];
				}

				if (isset($address['country_code'])) {
					$geoloc->countryCode = strtoupper($address['country_code']);
				}

				if (isset($address['state'])) {
					$geoloc->state = $address['state'];
				}

				if (isset($address['county'])) {
					$geoloc->county = $address['county'];
				}

				if (isset($address['city'])) {
					$geoloc->locality = $address['city'];
				} elseif (isset($address['town'])) {
					$geoloc->locality = $address['town'];
				} elseif (isset($address['village'])) {
					$geoloc->locality = $address['village'];
				} elseif (isset($address['hamlet'])) {
					$geoloc->locality = $address['hamlet'];
				} elseif (isset($address['isolated_dwelling'])) {
					$geoloc->locality = $address['isolated_dwelling'];
				} elseif (isset($address['locality'])) {
					$geoloc->locality = $address['locality'];
				}

				if (isset($address['suburb'])) {
					$geoloc->sublocality = $address['suburb'];
				}

				if (isset($address['postcode'])) {
					$geoloc->postalCode = $address['postcode'];
				}

				if (isset($address['road'])) {
					$geoloc->streetName = $address['road'];
				}

				// pedestrian is used sometimes instead of road
				if (isset($address['pedestrian'])) {
					$geoloc->streetName = $address['pedestrian'];
				}

				if (isset($address['house_number'])) {
					$geoloc->houseNumber = $address['house_number'];
				}

				$geoloc->fillAccuracy();

				return $geoloc;
			}

		} catch (Exception $e) {
			error_log('ERR: ' . $url . ' ' . $e->getMessage());
			print 'ERR: ' . $url . ' ' . $e->getMessage();
			return false;
		}
		return null; 
	}
}
