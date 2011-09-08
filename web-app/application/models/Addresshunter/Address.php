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
 * Model_Address
 *
 * @Table(name="address")
 * @Entity(repositoryClass="Application_Model_Addresshunter_AddressRepository")
 */
class Application_Model_Addresshunter_Address
{
	/**
	 * @var string $country
	 *
	 * @Column(name="country", type="string", length=2)
	 */
	private $country;

	/**
	 * @var string $city
	 *
	 * @Column(name="city", type="string", length=255)
	 */
	private $city;

	/**
	 * @var string $postcode
	 *
	 * @Column(name="postcode", type="string", length=255)
	 */
	private $postcode;

	/**
	 * @var string $street
	 *
	 * @Column(name="street", type="string")
	 */
	private $street;

	/**
	 * @var string $housenumber
	 *
	 * @Column(name="housenumber", type="string", length=10)
	 */
	private $housenumber;

	/**
	 * @var string $addressHash
	 *
	 * @Column(name="address_hash", type="string", length=32)
	 */
	private $addressHash;

	/**
	 * @var float $approxX
	 *
	 * @Column(name="approx_x", type="float")
	 */

	private $approxX;

	/**
	 * @var float $approxY
	 *
	 * @Column(name="approx_y", type="float")
	 */
	private $approxY;

	/**
	 * @var boolean $isAvailable
	 *
	 * @Column(name="is_available", type="boolean")
	 */
	private $isAvailable;

	/**
	 * @var string $full
	 *
	 * @Column(name="full", type="string")
	 */
	private $full;

	/**
	 * @var integer $id
	 *
	 * @Column(name="id", type="integer")
	 * @Id
	 * @GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

	/**
	 * Set country
	 *
	 * @param string $country
	 */
	public function setCountry($country)
	{
		$this->country = $country;
	}

	/**
	 * Get country
	 *
	 * @return string $country
	 */
	public function getCountry()
	{
		return $this->country;
	}

	/**
	 * Set city
	 *
	 * @param string $city
	 */
	public function setCity($city)
	{
		$this->city = $city;
	}

	/**
	 * Get city
	 *
	 * @return string $city
	 */
	public function getCity()
	{
		return $this->city;
	}

	/**
	 * Set zip
	 *
	 * @param string $postcode
	 */
	public function setPostcode($postcode)
	{
		$this->postcode = $postcode;
	}

	/**
	 * Get postcode
	 *
	 * @return string $postcode
	 */
	public function getPostcode()
	{
		return $this->postcode;
	}

	/**
	 * Set street
	 *
	 * @param string $street
	 */
	public function setStreet($street)
	{
		$this->street = $street;
	}

	/**
	 * Get street
	 *
	 * @return string $street
	 */
	public function getStreet()
	{
		return $this->street;
	}

	/**
	 * Set housenumber
	 *
	 * @param string $housenumber
	 */
	public function setHousenumber($housenumber)
	{
		$this->houseNr = $houseNr;
	}

	/**
	 * Get housenumber
	 *
	 * @return string $housenumber
	 */
	public function getHousenumber()
	{
		return $this->housenumber;
	}

	/**
	 * Set addressHash
	 *
	 * @param string $addressHash
	 */
	public function setAddressHash($addressHash)
	{
		$this->addressHash = $addressHash;
	}

	/**
	 * Get addressHash
	 *
	 * @return string $addressHash
	 */
	public function getAddressHash()
	{
		return $this->addressHash;
	}

	/**
	 * Set approxX
	 *
	 * @param float $approxX
	 */
	public function setApproxX($approxX)
	{
		$this->approxX = $approxX;
	}

	/**
	 * Get approxX
	 *
	 * @return float $approxX
	 */
	public function getApproxX()
	{
		return $this->approxX;
	}

	/**
	 * Set approxY
	 *
	 * @param float $approxY
	 */
	public function setApproxY($approxY)
	{
		$this->approxY = $approxY;
	}

	/**
	 * Get approxY
	 *
	 * @return float $approxY
	 */
	public function getApproxY()
	{
		return $this->approxY;
	}

	/**
	 * Set isAvailable
	 *
	 * @param boolean $isAvailable
	 */
	public function setIsAvailable($isAvailable)
	{
		$this->isAvailable = $isAvailable;
	}

	/**
	 * Get isAvailable
	 *
	 * @return boolean $isAvailable
	 */
	public function getIsAvailable()
	{
		return $this->isAvailable;
	}

	/**
	 * Set full
	 *
	 * @param string $full
	 */
	public function setFull($full)
	{
		$this->full = $full;
	}

	/**
	 * Get full
	 *
	 * @return string $full
	 */
	public function getFull()
	{
		return $this->full;
	}

	/**
	 * Get id
	 *
	 * @return integer $id
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * Returns the address as displayable string (as displayed in the game).
	 *
	 * Pattern: "STREET HOUSENUMBER, POSTCODE CITY"
	 * TODO: improve, ex. customize pattern by country or by external parameter
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->getStreet() . ' ' . $this->getHousenumber() . ', ' . $this->getPostcode() . ' ' . $this->getCity();
	}
}
