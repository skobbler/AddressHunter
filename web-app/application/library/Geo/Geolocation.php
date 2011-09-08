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
 * General class representing a geolocation
 *
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause	 New BSD License
 */
class Geo_Geolocation
{
	/**
	 * @const integer Accuracy status constants
	 */
	const ACCURACY_UNKNOWN = 0;
	const ACCURACY_COUNTRY = 1;
	const ACCURACY_STATE = 2;
	const ACCURACY_COUNTY = 3;
	const ACCURACY_LOCALITY = 4;
	const ACCURACY_POSTCODE = 5;
	const ACCURACY_STREET = 6;
	const ACCURACY_ADDRESS = 7;

	/**
	 * @var integer $id
	 */
	protected $id = null;
	/**
	 * @var string $addressline
	 */
	protected $addressline;
	/**
	 * @var string $country
	 */
	protected $country;
	/**
	 * @var string $countryCode ISO 3166-1 alpha-2 country code
	 */
	protected $countryCode;
	/**
	 * @var string $state
	 */
	protected $state;
	/**
	 * @var string $county
	 */
	protected $county;
	/**
	 * @var string $locality
	 */
	protected $locality;
	/**
	 * @var string $sublocality
	 */
	protected $sublocality;
	/**
	 * @var string $postalCode
	 */
	protected $postalCode;
	/**
	 * @var string $streetName
	 */
	protected $streetName;
	/**
	 * @var string $houseNumber
	 */
	protected $houseNumber;
	/**
	 * @var array $coordinates array with 'lat' and 'lon' keys
	 */
	protected $coordinates = array('lat' => null, 'lon' => null);
	/**
	 * @var integer $accuracy
	 */
	protected $accuracy = self::ACCURACY_UNKNOWN;

	/**
	 * Generic getter
	 *
	 * @param string $prop
	 * @return mixed
	 */
	public function __get($prop)
	{
		return $this->$prop;
	}

	/**
	 * Generic setter
	 *
	 * @param string $prop
	 * @param mixed $val
	 * @return void
	 */
	public function __set($prop, $val)
	{
		$this->$prop = $val;
	}

	/**
	 * Guesses and fills the 'accuracy' based on the data completeness.
	 */
	public function fillAccuracy()
	{
		if ($this->houseNumber != '') {
			$this->accuracy = self::ACCURACY_ADDRESS;
		} elseif ($this->streetName != '') {
			$this->accuracy = self::ACCURACY_STREET;
		} elseif ($this->postalCode != '') {
			$this->accuracy = self::ACCURACY_POSTCODE;
		} elseif ($this->locality != '') {
			$this->accuracy = self::ACCURACY_LOCALITY;
		} elseif ($this->county != '') {
			$this->accuracy = self::ACCURACY_COUNTY;
		} elseif ($this->state != '') {
			$this->accuracy = self::ACCURACY_STATE;
		} elseif ($this->country != '') {
			$this->accuracy = self::ACCURACY_COUNTRY;
		} else {
			$this->accuracy = self::ACCURACY_UNKNOWN;
		}
	}

	/**
	 * Returns an associative array of the stored data.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array(
			'id'					=> $this->id,
			'addressline'			=> $this->addressline,
			'country'				=> $this->country,
			'country_code'			=> $this->countryCode,
			'state'					=> $this->state,
			'county'				=> $this->county,
			'locality'				=> $this->locality,
			'sublocality'			=> $this->sublocality,
			'postal_code'			=> $this->postalCode,
			'street_name'			=> $this->streetName,
			'house_number'			=> $this->houseNumber,
			'coordinates'			=> $this->coordinates,
			'accuracy'				=> $this->accuracy,
		);
	}
}
