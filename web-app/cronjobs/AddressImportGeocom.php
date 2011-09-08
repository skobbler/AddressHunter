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
 * Imports addresses from skobbler GeoCom logs
 * (to be run as cronjob)
 *
 * This script performs the first step of a two-step address import (data import and validation)
 * and is an example how address data can be imported from various sources. To import data from
 * another source, implement a similar tool to this one.
 *
 * This script parses the logs, extracts the (theoretically) usable addresses and imports them
 * to the database ("address_import" table) in a standardized format.
 * An address is considered usable if it has at least a street name and preferably also a
 * numeric housenumber. If the housenumber is missing, "1" is used instead.
 * The addresses are imported as lowercased addressline (string). Duplicates are discarded, but
 * their frequency is counted.
 */

// debug and benchmark constants
define('DEBUG', false);
define('BENCHMARK', false);

// including the init (ZF bootstrap)
require_once 'init.php';

// getting the config
$config = Zend_Registry::get('Zend_Config');

$geocomLogsPath = $config->addressimport->geocom->path;

// searching for the next logfile to parse
$logfile = false;
if ($dh = opendir($geocomLogsPath)) {
	while (false !== ($file = readdir($dh))) {
		if ($file != "." && $file != ".." && $file != ".svn" && is_file($geocomLogsPath . $file)) {
			$logfile = $file;
			break;
		}
	}
	closedir($dh);
} else {
	// TODO: use Zend logger everywhere
	error_log('AddressImportGeocom ERROR: Error reading from geocom logs directory');
	die();
}

if (!$logfile) {
	// no log file to parse
	die();
}

// array for statistics
$stats = array(
	'regular_has_nr_num' => 0, // regular search with numeric house number
	'regular_has_nr_nan' => 0, // regular search with NaN house number
	'regular_has_str_only' => 0, // regular search with street name only (no house number)
	'regular_has_str_nr' => 0, // regular search with street name which starts or ends with a number (probably a house number, wrongly placed by the user)
	'regular_no_str' => 0, // regular search with no street name (probably only a city, zipcode or country)
	'oneline_has_nr' => 0, // oneline search with at least a numeric character in it
	'oneline_no_nr' => 0, // oneline search with no numeric chars
	'reverse' => 0, // reverse geocodings
);

// TODO: use Doctrine
$dbSettings = $config->doctrine->connection->toArray();
$dbh = mysql_connect($dbSettings['host'], $dbSettings['user'], $dbSettings['password']);
if (!$dbh) {
	error_log('AddressImportGeocom ERROR: Could not connect to the database: ' . mysql_error());
	die();
}
if (!mysql_select_db($dbSettings['dbname'], $dbh)) {
	error_log('AddressImportGeocom ERROR: Could not select database: ' . mysql_error());
	die();
}
mysql_query("SET NAMES UTF8", $dbh);

$fh = fopen($geocomLogsPath . $logfile, "rb");
if ($fh) {
	while (($buffer = fgets($fh, 4096)) !== false) {
		$finalAddressline = false;
		$finalHousenumber = false;
		// ####### regular searches (separate address details)
		if (strpos($buffer, '  message: GEOCOM request received from client: /geocode/regular?') === 0) {
			$query = substr($buffer, 65);
			$arr = array();
			parse_str($query, $arr);
			$arr = array_map('trim', $arr);
			$arr = array_map('mb_strtolower', $arr);
			if (isset($arr['number'])) { // house number specified
				if (preg_match('/[0-9]+/', $arr['number'])) { // numeric house number
					$stats['regular_has_nr_num']++;
				} else { // house number present, but not numeric (we will ignore it)
					$stats['regular_has_nr_nan']++;
					$arr['number'] = '1';
				}
				$finalAddressline = $arr['number'] . ' ' . @$arr['street'] . ', ' . @$arr['city'] . ' ' . @$arr['postal_code'] . ', ' . @$arr['state'] . ', ' . @$arr['country_code'];
				$finalHousenumber = $arr['number'];
			} elseif (isset($arr['street'])) { // no house number specified, but we have street name
				$x = explode(' ', $arr['street']);
				if (preg_match('/[0-9]+/', $x[0])) { // street name starts with a number (we will consider it as house number)
					$stats['regular_has_str_nr']++;
					$arr['number'] = $x[0];
					array_shift($x);
					$arr['street'] = implode(' ', $x);
				} elseif (preg_match('/[0-9]+/', $x[count($x)-1])) { // street name ends with a number (we will consider it as house number)
					$stats['regular_has_str_nr']++;
					$arr['number'] = $x[count($x)-1];
					array_pop($x);
					$arr['street'] = implode(' ', $x);
				} else { // street name with no number: we will search for house no. 1
					$stats['regular_has_str_only']++;
					$arr['number'] = '1';
				}
				$finalAddressline = $arr['number'] . ' ' . @$arr['street'] . ', ' . @$arr['city'] . ' ' . @$arr['postal_code'] . ', ' . @$arr['state'] . ', ' . @$arr['country_code'];
				$finalHousenumber = $arr['number'];
			} else {
				$stats['regular_no_str']++;
			}
		// ####### one-line searches
		} elseif (strpos($buffer, '  message: GEOCOM request received from client: /geocode/oneline?') === 0) {
			$query = substr($buffer, 65);
			$arr = array();
			parse_str($query, $arr);
			if (isset($arr['address'])) {
				$arr['address'] = trim($arr['address']);
				$arr['address'] = mb_strtolower($arr['address']);
				$matches = null;
				// we need to identify the housenumber (if there is one)
				if (preg_match('/[0-9]+/', $arr['address'], $matches)) { // addressline has at least a numerical character in it, otherwise it's discarded
					$stats['oneline_has_nr']++;
					// removing temporarily because we cannot identify the housenumber (reliably) for further comparison
					//$finalAddressline = $arr['address'];
					//$finalHousenumber = '';
				} else {
					$stats['oneline_no_nr']++;
				}
			}
		// ####### reverse geocodings
		} elseif (strpos($buffer, '  message: GEOCOM request received from client: /geocode/reverse?') === 0) {
			$stats['reverse']++;
		} else {
			// other lines from the log file are ignored
		}
		
		// inserting to the DB
		if ($finalAddressline) {
			// cleaning up the addressline
			$finalAddressline = str_replace(array(', , ', '  ', ', , ', ' ', ' ,', "\n", "\r"), array(', ', '  ', ', ', ' ', ',', ' ', ' '), $finalAddressline);
			$finalAddressline = preg_replace('!\s+!', ' ', $finalAddressline);
			$finalAddressline = trim($finalAddressline, ", \t\n\r\0\x0B");
			if (DEBUG) {
				//print "\n" . $finalAddressline;
			}
			$insert = sprintf("INSERT INTO address_import (addressline, housenumber, country_code) VALUES ('%s', '%s', '%s') ON DUPLICATE KEY UPDATE frequency = frequency + 1;",
				mysql_real_escape_string($finalAddressline),
				mysql_real_escape_string($finalHousenumber),
				mysql_real_escape_string(substr($finalAddressline, -2))
			);
			$dbresult = mysql_query($insert, $dbh);
			if (!$dbresult) {
				error_log('AddressImportGeocom ERROR: Error inserting to DB: ' . $insert);
			}
		}
	}
	if (!feof($fh)) {
		error_log('AddressImportGeocom ERROR: unexpected fgets() fail');
	}
	fclose($fh);
} else {
	error_log('AddressImportGeocom ERROR: Error opening logfile' . $logfile);
}

// logging the statistics
$statsLog = $config->addressimport->logs->path . 'geocom_import.log.csv';
file_put_contents(
	$statsLog,
	"\n" . date('Y-m-d H:i:s') . ';' . $logfile . ';' . implode(';', $stats),
	FILE_APPEND
);
if (DEBUG) {
	print "\n";
	print_r($stats);
}

// deleting the successfully processed log file
unlink($geocomLogsPath . $logfile);
