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
 * Gameuser
 *
 * @Table(name="gameuser")
 * @Entity(repositoryClass="Application_Model_Addresshunter_GameuserRepository")
 */
class Application_Model_Addresshunter_Gameuser
{
	/**
	 * Gameuser status constants
	 */
	const STATUS_ACTIVE = 'active';
	const STATUS_INACTIVE = 'inactive';
	const STATUS_CANCELED = 'canceled';

	/**
	 * @var datetime $dateJoined
	 *
	 * @Column(name="date_joined", type="datetime")
	 */
	private $dateJoined;

	/**
	 * @var datetime $lastActivity
	 *
	 * @Column(name="last_activity", type="datetime")
	 */
	private $lastActivity;

	/**
	 * @var string $status
	 *
	 * @Column(name="status", type="string", length=255)
	 */
	private $status;

	/**
	 * @var float $joinX
	 *
	 * @Column(name="join_x", type="float")
	 */
	private $joinX;

	/**
	 * @var float $joinY
	 *
	 * @Column(name="join_y", type="float")
	 */
	private $joinY;

	/**
	 * @var float $lastX
	 *
	 * @Column(name="last_x", type="float")
	 */
	private $lastX;

	/**
	 * @var float $lastY
	 *
	 * @Column(name="last_y", type="float")
	 */
	private $lastY;

	/**
	 * @var boolean $isWinner
	 *
	 * @Column(name="is_winner", type="boolean")
	 */
	private $isWinner;

	/**
	 * @var integer $points
	 *
	 * @Column(name="points", type="integer")
	 */
	private $points;

	/**
	 * @var integer $id
	 *
	 * @Column(name="id", type="integer")
	 * @Id
	 * @GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

	/**
	 * @var Application_Model_Addresshunter_Game
	 *
	 * @ManyToOne(targetEntity="Application_Model_Addresshunter_Game", inversedBy="players")
	 * @JoinColumns({
	 *    @JoinColumn(name="game_id", referencedColumnName="id")
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
	 * Set dateJoined
	 *
	 * @param datetime $dateJoined
	 */
	public function setDateJoined($dateJoined)
	{
		$this->dateJoined = $dateJoined;
	}

	/**
	 * Get dateJoined
	 *
	 * @return datetime $dateJoined
	 */
	public function getDateJoined()
	{
		return $this->dateJoined;
	}

	/**
	 * Set dateCanceled
	 *
	 * @param datetime $dateCanceled
	 */
	public function setDateCanceled($dateCanceled)
	{
		$this->dateCanceled = $dateCanceled;
	}

	/**
	 * Get dateCanceled
	 *
	 * @return datetime $dateCanceled
	 */
	public function getDateCanceled()
	{
		return $this->dateCanceled;
	}

	/**
	 * Set lastActivity
	 *
	 * @param datetime $lastActivity
	 */
	public function setLastActivity($lastActivity)
	{
		$this->lastActivity = $lastActivity;
	}

	/**
	 * Get lastActivity
	 *
	 * @return datetime $lastActivity
	 */
	public function getLastActivity()
	{
		return $this->lastActivity;
	}

	/**
	 * Set status
	 *
	 * @param string $status
	 */
	public function setStatus($status)
	{
		if (!in_array($status, array(self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_CANCELED))) {
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
	 * Set joinX
	 *
	 * @param float $joinX
	 */
	public function setJoinX($joinX)
	{
		$this->joinX = $joinX;
	}

	/**
	 * Get joinX
	 *
	 * @return float $joinX
	 */
	public function getJoinX()
	{
		return $this->joinX;
	}

	/**
	 * Set joinY
	 *
	 * @param float $joinY
	 */
	public function setJoinY($joinY)
	{
		$this->joinY = $joinY;
	}

	/**
	 * Get joinY
	 *
	 * @return float $joinY
	 */
	public function getJoinY()
	{
		return $this->joinY;
	}

	/**
	 * Set lastX
	 *
	 * @param float $lastX
	 */
	public function setLastX($lastX)
	{
		$this->lastX = $lastX;
	}

	/**
	 * Get lastX
	 *
	 * @return float $lastX
	 */
	public function getLastX()
	{
		return $this->lastX;
	}

	/**
	 * Set lastY
	 *
	 * @param float $lastY
	 */
	public function setLastY($lastY)
	{
		$this->lastY = $lastY;
	}

	/**
	 * Get lastY
	 *
	 * @return float $lastY
	 */
	public function getLastY()
	{
		return $this->lastY;
	}

	/**
	 * Set isWinner
	 *
	 * @param boolean $isWinner
	 */
	public function setIsWinner($isWinner)
	{
		$this->isWinner = $isWinner;
	}

	/**
	 * Get isWinner
	 *
	 * @return boolean $isWinner
	 */
	public function getIsWinner()
	{
		return $this->isWinner;
	}

	/**
	 * Set points
	 *
	 * @param integer $points
	 */
	public function setPoints($points)
	{
		$this->points = $points;
	}

	/**
	 * Get points
	 *
	 * @return integer $points
	 */
	public function getPoints()
	{
		return $this->points;
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
	 * Set game
	 *
	 * @param Application_Model_Addresshunter_Game $game
	 */
	public function setGame(\Application_Model_Addresshunter_Game $game)
	{
		$game->addPlayer($this);
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
}
