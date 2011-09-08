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

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Game
 *
 * @Table(name="game")
 * @Entity(repositoryClass="Application_Model_Addresshunter_GameRepository")
 */
class Application_Model_Addresshunter_Game
{
	/**
	 * Gameaddress status constants
	 */
	const STATUS_NEW = 'new';
	const STATUS_PLAYING = 'playing';
	const STATUS_CANCELED = 'canceled';
	const STATUS_FINISHED = 'finished';
	const STATUS_EXPIRED = 'expired';

	/**
	 * @var string $name
	 *
	 * @Column(name="name", type="string", length=255)
	 */
	private $name;

	/**
	 * @var datetime $dateCreated
	 *
	 * @Column(name="date_created", type="datetime")
	 */
	private $dateCreated;

	/**
	 * @var datetime $dateStarted
	 *
	 * @Column(name="date_started", type="datetime")
	 */
	private $dateStarted;

	/**
	 * @var datetime $dateEnded
	 *
	 * @Column(name="date_ended", type="datetime")
	 */
	private $dateEnded;

	/**
	 * @var float $gameX
	 *
	 * @Column(name="game_x", type="float")
	 */
	private $gameX;

	/**
	 * @var float $gameY
	 *
	 * @Column(name="game_y", type="float")
	 */
	private $gameY;

	/**
	 * @var integer $playerAddressNo
	 *
	 * @Column(name="player_address_no", type="integer")
	 */
	private $playerAddressNo;

	/**
	 * @var integer $maxPlayers
	 *
	 * @Column(name="max_players", type="integer")
	 */
	private $maxPlayers;

	/**
	 * @var integer $timeframe
	 *
	 * @Column(name="timeframe", type="integer")
	 */
	private $timeframe;

	/**
	 * @var integer $bonus
	 *
	 * @Column(name="bonus", type="integer")
	 */
	private $bonus;

	/**
	 * @var integer $id
	 *
	 * @Column(name="id", type="integer")
	 * @Id
	 * @GeneratedValue(strategy="IDENTITY")
	 */
	 private $id;

	/**
	 * @var float $radius
	 *
	 * @Column(name="radius", type="float")
	 */
	 private $radius;

	/**
	 * @var Application_Model_Addresshunter_User
	 *
	 * @ManyToOne(targetEntity="Application_Model_Addresshunter_User")
	 * @JoinColumns({
	 *   @JoinColumn(name="created_by", referencedColumnName="id")
	 * })
	 */
	private $createdBy;

	/**
	 *@param \Doctrine\Comon\Collections\ArrayCollection $players
	 *
	 * @OneToMany(targetEntity="Application_Model_Addresshunter_Gameuser", mappedBy="game")
	 */
	private $players;

	/**
	 *@param \Doctrine\Comon\Collections\ArrayCollection $addresses
	 *
	 * @OneToMany(targetEntity="Application_Model_Addresshunter_Gameaddress", mappedBy="game")
	 */
	private $addresses;

	/**
	 * @var string $status
	 *
	 * @Column(name="status", type="string", length=255)
	 */
	private $status;

	/**
	 * @var integer $type
	 *
	 * @Column(name="type", type="integer")
	 */
	 private $type;

	 public function __construct()
	 {
		$this->players = new ArrayCollection();
		$this->addresses = new ArrayCollection();
	 }

	/**
	 * Get players
	 *
	 * @param \Doctrine\Comon\Collections\ArrayCollection $players
	 */
	public function getPlayers()
	{
		return $this->players;
	}

	/**
	 * Get players
	 *
	 * @param \Doctrine\Comon\Collections\ArrayCollection $players
	 */
	public function getActivePlayers()
	{
		return $this->players->filter( function($element){ 	return $element->getStatus() == Application_Model_Addresshunter_Gameuser::STATUS_ACTIVE;});
	}

	/**
	 *
	 * @param Application_Model_Addresshunter_Gameuser $gameuser
	 */
	 public function addPlayer($gameuser)
	{
		$this->players[] = $gameuser;
	}

	/**
	 *
	 * @return Application_Model_Addresshunter_Gameuser $gameuser
	 */
	 public function getPlayer($userOsmId)
	{
		 $players = $this->players->filter( function($element) use (&$userOsmId){ return $element->getUser()->getOsmId() == $userOsmId;});
		 return $players->first();

	}

	/**
	 * Get addresses
	 *
	 * @param \Doctrine\Comon\Collections\ArrayCollection $addresses
	 */
	public function getAddresses()
	{
		return $this->addresses;
	}

	/**
	 * Get addresses
	 *
	 * @param \Doctrine\Comon\Collections\ArrayCollection $addresses
	 */
	public function getActiveAddresses()
	{
		return $this->addresses->filter( function($element){ 	return $element->getStatus() == Application_Model_Addresshunter_Gameaddress::STATUS_ACTIVE;});
	}

	/**
	 *
	 * @param Application_Model_Addresshunter_Gameaddress $gameaddress
	 */
	 public function addAddress($gameaddress)
	{
		$this->addresses[] = $gameaddress;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Get name
	 *
	 * @return string $name
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set dateCreated
	 *
	 * @param datetime $dateCreated
	 */
	public function setDateCreated($dateCreated)
	{
		$this->dateCreated = $dateCreated;
	}

	/**
	 * Get dateCreated
	 *
	 * @return datetime $dateCreated
	 */
	public function getDateCreated()
	{
		return $this->dateCreated;
	}

	/**
	 * Set dateStarted
	 *
	 * @param datetime $dateStarted
	 */
	public function setDateStarted($dateStarted)
	{
		$this->dateStarted = $dateStarted;
	}

	/**
	 * Get dateStarted
	 *
	 * @return datetime $dateStarted
	 */
	public function getDateStarted()
	{
		return $this->dateStarted;
	}

	/**
	 * Set dateEnded
	 *
	 * @param datetime $dateEnded
	 */
	public function setDateEnded($dateEnded)
	{
		$this->dateEnded = $dateEnded;
	}

	/**
	 * Get dateEnded
	 *
	 * @return datetime $dateEnded
	 */
	public function getDateEnded()
	{
		return $this->dateEnded;
	}

	/**
	 * Set gameX
	 *
	 * @param float $gameX
	 */
	public function setGameX($gameX)
	{
		$this->gameX = $gameX;
	}

	/**
	 * Get gameX
	 *
	 * @return float $gameX
	 */
	public function getGameX()
	{
		return $this->gameX;
	}

	/**
	 * Set gameY
	 *
	 * @param float $gameY
	 */
	public function setGameY($gameY)
	{
		$this->gameY = $gameY;
	}

	/**
	 * Get gameY
	 *
	 * @return float $gameY
	 */
	public function getGameY()
	{
		return $this->gameY;
	}

	/**
	 * Set playerAddressNo
	 *
	 * @param integer $playerAddressNo
	 */
	public function setPlayerAddressNo($playerAddressNo)
	{
		$this->playerAddressNo = $playerAddressNo;
	}

	/**
	 * Get playerAddressNo
	 *
	 * @return integer $playerAddressNo
	 */
	public function getPlayerAddressNo()
	{
		return $this->playerAddressNo;
	}

	/**
	 * Set maxPlayers
	 *
	 * @param integer $maxPlayers
	 */
	public function setMaxPlayers($maxPlayers)
	{
		$this->maxPlayers = $maxPlayers;
	}

	/**
	 * Get maxPlayers
	 *
	 * @return integer $maxPlayers
	 */
	public function getMaxPlayers()
	{
		return $this->maxPlayers;
	}

	/**
	 * Set timeframe
	 *
	 * @param integer $timeframe
	 */
	public function setTimeframe($timeframe)
	{
		$this->timeframe = $timeframe;
	}

	/**
	 * Get timeframe
	 *
	 * @return integer $timeframe
	 */
	public function getTimeframe()
	{
		return $this->timeframe;
	}

	/**
	 * Set status
	 *
	 * @param string $status
	 */
	public function setStatus($status)
	{
		if (!in_array($status, array(self::STATUS_NEW, self::STATUS_PLAYING, self::STATUS_CANCELED, self::STATUS_FINISHED, self::STATUS_EXPIRED))) {
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
	 * Set bonus
	 *
	 * @param integer $bonus
	 */
	public function setBonus($bonus)
	{
		$this->bonus = $bonus;
	}

	/**
	 * Get bonus
	 *
	 * @return integer $bonus
	 */
	public function getBonus()
	{
		return $this->bonus;
	}

	/**
	 * Set radius
	 *
	 * @param float $radius
	 */
	public function setRadius($radius)
	{
		$this->radius = $radius;
	}

	/**
	 * Get radius
	 *
	 * @return float $radius
	 */
	public function getRadius()
	{
		return $this->radius;
	}
	/**
	 * Set type
	 *
	 * @param integer $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * Get type
	 *
	 * @return integer $type
	 */
	public function getType()
	{
		return $this->type;
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
	 * Set createdBy
	 *
	 * @param Application_Model_Addresshunter_User $createdBy
	 */
	public function setCreatedBy(\Application_Model_Addresshunter_User $createdBy)
	{
		$this->createdBy = $createdBy;
	}

	/**
	 * Get createdBy
	 *
	 * @return Application_Model_Addresshunter_User $createdBy
	 */
	public function getCreatedBy()
	{
		return $this->createdBy;
	}

	/**
	 * Returns as array with the game attributes
	 *
	 * @return array
	 */
	public function toArray()
	{
		$fields = array('id', 'name', 'dateCreated', 'dateStarted', 'dateEnded', 'gameX', 'gameY',
			'playerAddressNo', 'maxPlayers', 'timeframe', 'status', 'bonus', 'radius', 'type', 'createdBy');
		$result = array();
		foreach ($fields as $field) {
			$method = 'get' . ucfirst($field);
			if (is_callable(array($this, $method))) {
				$result[$field] = $this->$method();
			} else {
				$result[$field] = $this->$field;
			}
		}
		return $result;
	}
}
