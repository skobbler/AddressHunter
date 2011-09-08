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
 * Validates the imported addresses
 * (to be run as cronjob)
 *
 * This script performs the second step of the two-step address import (data import and validation).
 * While address import scripts/cronjobs can be many, address validation should be only one that
 * validates in a unified way all imported addresses.
 *
 * The address validation is done by looking them up one by one with different geocoders (ex.
 * Google and MapQuest Nominatim for now) and checking if there are valid (real) and if they
 * are already present in OSM.
 * The results are interpreted by accuracy: found at housenumber level, only street level, or not
 * found at all. Since the Google geocoder gives better results in finding misspelled addresses,
 * in some cases the Google result string is used once more with MQ Noninatim for better results.
 *
 * In the ideal case, the address proves to be real and found at housenumber level with Google,
 * but only at street level with MQ Nominatim. This means the housenumber is missing from OSM
 * and can be used as target in the game.
 *
 * Finally, usable new addresses are copied to the "address" table ready to be used in the game.
 *
 * *** IMPORTANT NOTE! ***
 * Google is used just for looking up addresses, but NO DATA COMING FROM GOOGLE IS EVER STORED
 * (because of legal reasons).
 *
 * All address details proposed and used in the game are originating from OSM, or, in case of the
 * housenumber, from user input (imported data) or the default no "1" set by AddressHunter.
 */

// debug and benchmark constants
define('DEBUG', false);
define('BENCHMARK', false);

// including the init (ZF bootstrap)
require_once 'init.php';

mb_internal_encoding("UTF-8");

// geocoding statuses used in the DB
define('GEOCODE_STATUS_ERROR', -1); //geocoding error or exception
define('GEOCODE_STATUS_NOT_FOUND', 0); // not found at steet level (at least)
define('GEOCODE_STATUS_FOUND_HOUSENR', 1); // found, including the housenumber
define('GEOCODE_STATUS_FOUND_STREET', 2); // found only at street level
define('GEOCODE_STATUS_FOUND_HOUSENR_OTHER', 3); // found at housenumber level, but not exactly the same housenumber (relevant for Google geocoding)

// getting the config
$config = Zend_Registry::get('Zend_Config');

$limit = (int)$config->addressimport->limit;

// TODO: use Doctrine
$dbSettings = $config->doctrine->connection->toArray();
$dbh = mysql_connect($dbSettings['host'], $dbSettings['user'], $dbSettings['password']);
if (!$dbh) {
	error_log('AddressImportValidator ERROR: Could not connect to the database: ' . mysql_error());
	die();
}
if (!mysql_select_db($dbSettings['dbname'], $dbh)) {
	error_log('AddressImportValidator ERROR: Could not select database: ' . mysql_error());
	die();
}
mysql_query("SET NAMES UTF8", $dbh);

// selecting the not yet validated addresses in random order
$query = "SELECT * FROM address_import WHERE status_osm1 IS NULL ORDER BY RAND() LIMIT " . mysql_real_escape_string($limit, $dbh);
// for debugging
//$query = "SELECT * FROM address_import WHERE status_osm1 IS NULL AND country_code='us' ORDER BY RAND() LIMIT " . mysql_real_escape_string($limit, $dbh);
//$query = "SELECT * FROM address_import WHERE id=1";
$result = mysql_query($query, $dbh);

$i = 0;
while ($row = mysql_fetch_array($result)) {
	// sleeping for 1 second after each 10 request to prevent geocoder overload
	if (++$i % 10 == 0) {
		sleep(1);
	}

	// output for debug
	if (DEBUG) {
		print "\n\n\n##############################################################################\n\n\n";
		print "[" . $row['id'] . "] " . $row['addressline'] . "\n";
	}

	try{

		// ############################### STEP 1 ###########################################
		// Geocoding with OSM (MQ Nominatim)
		// we stop only if complete address (housenumber) is found

		$osmResult = Geo_MqNominatimGeocoder::geocode($row['addressline']);
		
		if (DEBUG) {
			print "\n-------- STEP 1 ----------\n";
			print_r($osmResult);
			// the coords in this format are useful for lookup in maps.google.com or www.openstreetmap.org
			print "\nosmCoords: " . @implode(',', @$osmResult->coordinates) . "\n";
		}
		
		// evaluating the geocoding result
		$step1Status = GEOCODE_STATUS_NOT_FOUND;
		$stop = false;
		if (is_object($osmResult)) {
			switch ($osmResult->accuracy) {
				case Geo_Geolocation::ACCURACY_ADDRESS:
					$step1Status = GEOCODE_STATUS_FOUND_HOUSENR;
					$stop = true; // complete address already on OSM
					break;
				case Geo_Geolocation::ACCURACY_STREET:
					$step1Status = GEOCODE_STATUS_FOUND_STREET;
					break;
			}
		} elseif ($osmResult === false) {
			$step1Status = GEOCODE_STATUS_ERROR;
			$stop = true;
		}

		// storing the status in the DB
		$updateQuery = "UPDATE address_import SET status_osm1 = '" . $step1Status . "' WHERE id = '" . $row['id'] . "'";
		if (!mysql_query($updateQuery, $dbh)) {
			error_log('AddressImportValidator ERROR: Error updating address_import data: ' . mysql_error());
			die();
		}

		if ($stop) {
			continue; // skip further steps and continue with the next address
		}

		// ############################### STEP 2 ###########################################
		// Geocoding with Google
		// we continue only if complete address (housenumber) is found

		$gArgs = array(
			'key' => $config->google->geocoder->key,
			'client' => $config->google->geocoder->client,
			'timeout' => $config->addressimport->timeout
		);
		$gResult = Geo_GoogleGeocoder::geocode($row['addressline'], $gArgs);
		
		if (DEBUG) {
			print "\n-------- STEP 2 ----------\n";
			print_r($gResult);
			print "\ngCoords: " . @implode(',', @$gResult->coordinates) . "\n";
		}
		
		// evaluating the geocoding result
		$step2Status = GEOCODE_STATUS_NOT_FOUND;
		$stop = true;
		if (is_object($gResult)) {
			switch ($gResult->accuracy) {
				case Geo_Geolocation::ACCURACY_ADDRESS:
					// checking if the found housenumber is exactly the same as in the initial user input
					if ($gResult->houseNumber == $row['housenumber']) {
						$step2Status = GEOCODE_STATUS_FOUND_HOUSENR;
						$stop = false; // continue only if Google found the exact same housenumber (otherwise we cannot use it for legal reasons)
					} else {
						$step2Status = GEOCODE_STATUS_FOUND_HOUSENR_OTHER;
					}
					break;
				case Geo_Geolocation::ACCURACY_STREET:
					$step2Status = GEOCODE_STATUS_FOUND_STREET;
					break;
			}
		} elseif ($gResult === false) {
			$step2Status = GEOCODE_STATUS_ERROR;
		}

		// storing the status in the DB
		$updateQuery = "UPDATE address_import SET status_google = '" . $step2Status . "' WHERE id = '" . $row['id'] . "'";
		if (!mysql_query($updateQuery, $dbh)) {
			error_log('AddressImportValidator ERROR: Error updating address_import data: ' . mysql_error());
			die();
		}

		if ($stop) {
			continue; // skip further steps and continue with the next address
		}

		// ############################### STEP 3 ###########################################
		// Geocoding with OSM (MQ Nominatim) again, but this time with the Google result string and relying on Google's spelling correction
		// (only if the OSM address found so far is not acceptable)

		if ($step1Status == GEOCODE_STATUS_NOT_FOUND || $step1Status == GEOCODE_STATUS_FOUND_STREET && !isAcceptable($osmResult, $gResult)) {
			$gAddressline = $gResult->addressline;
			$osmResult = Geo_MqNominatimGeocoder::geocode($gAddressline);
			
			if (DEBUG) {
				print "\n-------- STEP 3a ----------\n";
				print_r($osmResult);
				print "\nosmCoords: " . @implode(',', @$osmResult->coordinates) . "\n";
			}
			
			// evaluating the geocoding result
			$step3Status = GEOCODE_STATUS_NOT_FOUND;
			$stop = false;
			if (is_object($osmResult)) {
				switch ($osmResult->accuracy) {
					case Geo_Geolocation::ACCURACY_ADDRESS:
						$step3Status = GEOCODE_STATUS_FOUND_HOUSENR;
						$stop = true; // complete address already on OSM
						break;
					case Geo_Geolocation::ACCURACY_STREET:
						$step3Status = GEOCODE_STATUS_FOUND_STREET;
						break;
				}
			} elseif ($osmResult === false) {
				$step3Status = GEOCODE_STATUS_ERROR;
				$stop = true;
			}

			if ($stop) {
				// storing the status in the DB
				$updateQuery = "UPDATE address_import SET status_osm2 = '" . $step3Status . "' WHERE id = '" . $row['id'] . "'";
				if (!mysql_query($updateQuery, $dbh)) {
					error_log('AddressImportValidator ERROR: Error updating address_import data: ' . mysql_error());
					die();
				}
				continue;
			}

			if ($step3Status == GEOCODE_STATUS_NOT_FOUND || $step3Status == GEOCODE_STATUS_FOUND_STREET && !isAcceptable($osmResult, $gResult)) {

				// STEP 3b: geocoding again with MQ Nominatim (like in step 3), but this time with the postal code stripped down
				$x = explode(',', $gAddressline);
				if (isset($x[count($x)-2])) {
					$y = explode(' ', $x[count($x)-2]);
					if (is_array($y)) {
						$removed = false;
						foreach ($y as $key => $value) {
							if (preg_match('/[0-9]+/', $value)) {
								unset($y[$key]);
								$removed = true;
							}
						}
						if ($removed) {
							$x[count($x)-2] = implode(' ', $y);
							$gAddressline = implode(',', $x);
						}
					}
				}
				if (DEBUG) {
					print "\n >>> after postcode strip: " . $gAddressline . "\n\n";
				}

				$osmResult = Geo_MqNominatimGeocoder::geocode($gAddressline);
				
				if (DEBUG) {
					print "\n-------- STEP 3b ----------\n";
					print_r($osmResult);
					print "\nosmCoords: " . @implode(',', @$osmResult->coordinates) . "\n";
				}

				if ($osmResult === false) {
					$step3Status = GEOCODE_STATUS_ERROR;
					// storing the status in the DB
					$updateQuery = "UPDATE address_import SET status_osm2 = '" . $step3Status . "' WHERE id = '" . $row['id'] . "'";
					if (!mysql_query($updateQuery, $dbh)) {
						error_log('AddressImportValidator ERROR: Error updating address_import data: ' . mysql_error());
						die();
					}
					continue;
				} elseif (!is_object($osmResult) || $osmResult->accuracy != Geo_Geolocation::ACCURACY_STREET || !isAcceptable($osmResult, $gResult)) {
					// storing the status in the DB
					$updateQuery = "UPDATE address_import SET status_osm2 = '" . $step3Status . "' WHERE id = '" . $row['id'] . "'";
					if (!mysql_query($updateQuery, $dbh)) {
						error_log('AddressImportValidator ERROR: Error updating address_import data: ' . mysql_error());
						die();
					}
					continue;
				} else {
					$step3Status = GEOCODE_STATUS_FOUND_STREET;
				}
			}

			// storing the status in the DB
			$updateQuery = "UPDATE address_import SET status_osm2 = '" . $step3Status . "' WHERE id = '" . $row['id'] . "'";
			if (!mysql_query($updateQuery, $dbh)) {
				error_log('AddressImportValidator ERROR: Error updating address_import data: ' . mysql_error());
				die();
			}
		}

		// if we reached here, we have an acceptable OSM result to copy to "address" table

		$osmCoords = $osmResult->coordinates;
		$gCoords = $gResult->coordinates;

		// checking and fixing the postcode:
		// - if the OSM and Google postcodes are identical (or one is substring of another): use it
		// - if they differ: perform another OSM geocoding with the Google coordinates to get a more
		//   accurate postcode (otherwise the initial OSM postcode might be 5km away)
		$osmPostcode = str_replace(' ', '', strtoupper($osmResult->postalCode)); // normalizing UK postcodes
		$gPostcode = str_replace(' ', '', strtoupper($gResult->postalCode));

		if (!empty($osmPostcode) && !empty($gPostcode) && ($osmPostcode == $gPostcode || strpos($gPostcode, $osmPostcode) === 0 || strpos($osmPostcode, $gPostcode) === 0)) {
			// leave the postcode unchanged
		} elseif (!empty($osmPostcode)) {
			$osmResult1 = Geo_MqNominatimGeocoder::geocode($gCoords['lat'] . ',' . $gCoords['lon']);
			if (is_object($osmResult1)) {
				$osmPostcodeNew = $osmResult1->postalCode;
				if (!empty($osmPostcodeNew)) {
					$osmResult->postalCode = $osmPostcodeNew;
				}
			}
		}

		// final address to use in "address" table
		$finalAddress = array(
			'housenumber' => trim($gResult->houseNumber),
			'street' => trim($osmResult->streetName),
			'postcode' => trim($osmResult->postalCode),
			'city' => trim($osmResult->locality),
			'country' => strtoupper(trim($osmResult->countryCode)),
			'approx_x' => $osmCoords['lon'],
			'approx_y' => $osmCoords['lat'],
			'full' => $osmResult->addressline
		);

		if (empty($finalAddress['city'])) {
			$finalAddress['city'] = trim($osmResult->sublocality);
		}

		// calculating the MD5 address hash (format: "housenumber street postcode city country")
		$finalAddress['hash'] = md5(strtolower(
			$finalAddress['housenumber'] . ' ' .
			$finalAddress['street'] . ' ' .
			$finalAddress['postcode'] . ' ' .
			$finalAddress['city'] . ' ' .
			$finalAddress['country']
		));

		$insertQuery = "INSERT INTO address (country, city, postcode, street, housenumber, address_hash, approx_x, approx_y, full) VALUES
			('" . mysql_real_escape_string($finalAddress['country'], $dbh) . "',
			'" . mysql_real_escape_string($finalAddress['city'], $dbh) . "',
			'" . mysql_real_escape_string($finalAddress['postcode'], $dbh) . "',
			'" . mysql_real_escape_string($finalAddress['street'], $dbh) . "',
			'" . mysql_real_escape_string($finalAddress['housenumber'], $dbh) . "',
			'" . mysql_real_escape_string($finalAddress['hash'], $dbh) . "',
			'" . mysql_real_escape_string($finalAddress['approx_x'], $dbh) . "',
			'" . mysql_real_escape_string($finalAddress['approx_y'], $dbh) . "',
			'" . mysql_real_escape_string($finalAddress['full'], $dbh) . "')
		";

		if (!mysql_query($insertQuery, $dbh)) {
			$used = 0;
			$err = mysql_error();
			if (strpos($err, 'Duplicate entry') === false) {
				error_log('AddressImportValidator ERROR: Error inserting new address: ' . $err);
				die();
			}
		} else {
			$used = 1;
		}

		$updateQuery = "UPDATE address_import SET used = '" . mysql_real_escape_string($used, $dbh) . "' WHERE id = '" . mysql_real_escape_string($row['id'], $dbh) . "'";
		if (!mysql_query($updateQuery, $dbh)) {
			error_log('AddressImportValidator ERROR: Error updating address_import used field: ' . mysql_error());
			die();
		}

		if (DEBUG) {
			print "\n\n!!!!!!!!!!!!!!!!!!!!!!          F O U N D         !!!!!!!!!!!!!!!!!!!!!!\n\n";
		}

	} catch(Exception $e) {
		error_log('AddressImportValidator ERROR: ' . $e->getMessage() . ' in ' . $request);
	}
}

mysql_close($dbh);

/**
 * Convenience function to decide if an OSM Geolocation is acceptable by comparing it to a given
 * Google Geolocation and checking if they are the same (approximately).
 * Note: postcode and other comparisons were removed because we cannot rely on them
 */
function isAcceptable($osmGeoloc, $gGeoloc) {
	// checking the distance
	$osmCoords = $osmGeoloc->coordinates;
	$gCoords = $gGeoloc->coordinates;
	$distance = Geo_GeoUtil::distance($osmCoords['lon'], $osmCoords['lat'], $gCoords['lon'], $gCoords['lat']);
	// converting the distance into meters
	$distance = round($distance * 1000);
	if (DEBUG) {
		print "\nDistance: " . number_format($distance, 0, '.', '') . 'm';
	}
	return ($distance < 5000);
}
