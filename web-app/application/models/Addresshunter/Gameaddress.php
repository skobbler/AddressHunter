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
 * Gameaddress
 *
 * @Table(name="gameaddress")
 * @Entity(repositoryClass="Application_Model_Addresshunter_GameaddressRepository")
 */
class Application_Model_Addresshunter_Gameaddress
{
	/**
	 * Gameaddress status constants
	 */
	const STATUS_ACTIVE = 'active';
	const STATUS_DISCOVERED = 'discovered';
	const STATUS_EXPIRED = 'expired';
	const STATUS_UPLOADED = 'uploaded';
	const STATUS_UPLOADED_BEFORE = 'uploaded_before';
	const STATUS_VALID = 'valid';
	const STATUS_INVALID = 'invalid';

	/**
	 * @var string $status
	 *
	 * @Column(name="status", type="string", length=255)
	 */
	private $status;

	/**
	 * @var float $finalX
	 *
	 * @Column(name="final_x", type="float")
	 */
	private $finalX;

	/**
	 * @var float $finalY
	 *
	 * @Column(name="final_y", type="float")
	 */
	private $finalY;

	/**
	 * @var string $filename
	 *
	 * @Column(name="filename", type="string")
	 */
	private $filename;

	/**
	 * @var integer $id
	 *
	 * @Column(name="id", type="integer")
	 * @Id
	 * @GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

	/**
	 * @var Application_Model_Addresshunter_Address
	 *
	 * @ManyToOne(targetEntity="Application_Model_Addresshunter_Address")
	 * @JoinColumns({
	 *   @JoinColumn(name="address_id", referencedColumnName="id")
	 * })
	 */
	private $address;

	/**
	 * @var Application_Model_Addresshunter_Game
	 *
	 * @ManyToOne(targetEntity="Application_Model_Addresshunter_Game", inversedBy="addresses")
	 * @JoinColumns({
	 *   @JoinColumn(name="game_id", referencedColumnName="id")
	 * })
	 */
	private $game;

	/**
	 * @var Application_Model_Addresshunter_User
	 *
	 * @ManyToOne(targetEntity="Application_Model_Addresshunter_User")
	 * @JoinColumns({
	 *   @JoinColumn(name="user_id", referencedColumnName="id")
	 * })
	 */
	private $user;

	 /**
	 * @var datetime $dateFound
	 *
	 * @Column(name="date_found", type="datetime")
	 */
	private $dateFound;

	/**
	 * Set status
	 *
	 * @param string $status
	 */
	public function setStatus($status)
	{
		if (!in_array($status, array(self::STATUS_ACTIVE, self::STATUS_DISCOVERED, self::STATUS_UPLOADED, self::STATUS_UPLOADED_BEFORE, self::STATUS_VALID, self::STATUS_INVALID, self::STATUS_EXPIRED))) {
			throw new \InvalidArgumentException("Invalid status");
		}
		$this->status = $status;
	}

	/**
	 * Get status
	 *
	 * @return string $status
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Set finalX
	 *
	 * @param float $finalX
	 */
	public function setFinalX($finalX)
	{
		$this->finalX = $finalX;
	}

	/**
	 * Get finalX
	 *
	 * @return float $finalX
	 */
	public function getFinalX()
	{
		return $this->finalX;
	}

	/**
	 * Set finalY
	 *
	 * @param float $finalY
	 */
	public function setFinalY($finalY)
	{
		$this->finalY = $finalY;
	}

	/**
	 * Get finalY
	 *
	 * @return float $finalY
	 */
	public function getFinalY()
	{
		return $this->finalY;
	}
	
	/**
	 * Set filename
	 *
	 * @param string $filename
	 */
	public function setFilename($filename)
	{
		$this->filename = $filename;
	}

	/**
	 * Get filename
	 *
	 * @return string $filename
	 */
	public function getFilename()
	{
		return $this->filename;
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
	 * Set address
	 *
	 * @param Application_Model_Addresshunter_Address $address
	 */
	public function setAddress(\Application_Model_Addresshunter_Address $address)
	{
		$this->address = $address;
	}

	/**
	 * Get address
	 *
	 * @return Application_Model_Addresshunter_Address $address
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * Set game
	 *
	 * @param Application_Model_Addresshunter_Game $game
	 */
	public function setGame(\Application_Model_Addresshunter_Game $game)
	{
		$game->addAddress($this);
		$this->game = $game;
	}

	/**
	 * Get game
	 *
	 * @return Application_Model_Addresshunter_Game $game
	 */
	public function getGame()
	{
		return $this->game;
	}

	/**
	 * Set user
	 *
	 * @param Application_Model_Addresshunter_User $user
	 */
	public function setUser(\Application_Model_Addresshunter_User $user)
	{
		$this->user = $user;
	}

	/**
	 * Get user
	 *
	 * @return Application_Model_Addresshunter_User $user
	 */
	public function getUser()
	{
		return $this->user;
	}

	 /**
	 * Set dateFound
	 *
	 * @param datetime $dateFound
	 */
	public function setDateFound($dateFound)
	{
		$this->dateFound = $dateFound;
	}

	/**
	 * Get dateFound
	 *
	 * @return datetime $dateFound
	 */
	public function getDateFound()
	{
		return $this->dateFound;
	}
}
