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
 * Application_Model_Addresshunter_User
 *
 * @Table(name="user")
 * @Entity(repositoryClass="Application_Model_Addresshunter_UserRepository")
 */
class Application_Model_Addresshunter_User
{
	/**
	 * @var string $nickname
	 *
	 * @Column(name="nickname", type="string", length=100)
	 */
	private $nickname;

	/**
	 * @var integer $osmId
	 *
	 * @Column(name="osm_id", type="integer")
	 */
	private $osmId;

	/**
	 * @var string $authToken
	 *
	 * @Column(name="auth_token", type="string", length=255)
	 */
	private $authToken;

	/**
	 * @var datetime $dateCreated
	 *
	 * @Column(name="date_created", type="datetime")
	 */
	private $dateCreated;

	/**
	 * @var datetime $dateLastAccess
	 *
	 * @Column(name="date_last_access", type="datetime")
	 */
	private $dateLastAccess;

	/**
	 * @var integer $theme
	 *
	 * @Column(name="theme", type="integer")
	 */
	private $theme;

	/**
	 * @var integer $totalPoints
	 *
	 * @Column(name="total_points", type="integer")
	 */
	private $totalPoints;

	/**
	 * @var integer $id
	 *
	 * @Column(name="id", type="integer")
	 * @Id
	 * @GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

	/**
	 * Set nickname
	 *
	 * @param string $nickname
	 */
	public function setNickname($nickname)
	{
		$this->nickname = $nickname;
	}

	/**
	 * Get nickname
	 *
	 * @return string $nickname
	 */
	public function getNickname()
	{
		return $this->nickname;
	}

	/**
	 * Set osmId
	 *
	 * @param integer $osmId
	 */
	public function setOsmId($osmId)
	{
		$this->osmId = $osmId;
	}

	/**
	 * Get osmId
	 *
	 * @return integer $osmId
	 */
	public function getOsmId()
	{
		return $this->osmId;
	}

	/**
	 * Set authToken
	 *
	 * @param string $authToken
	 */
	public function setAuthToken($authToken)
	{
		$this->authToken = $authToken;
	}

	/**
	 * Get authToken
	 *
	 * @return string $authToken
	 */
	public function getAuthToken()
	{
		return $this->authToken;
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
	 * Set dateLastAccess
	 *
	 * @param datetime $dateLastAccess
	 */
	public function setDateLastAccess($dateLastAccess)
	{
		$this->dateLastAccess = $dateLastAccess;
	}

	/**
	 * Get dateLastAccess
	 *
	 * @return datetime $dateLastAccess
	 */
	public function getDateLastAccess()
	{
		return $this->dateLastAccess;
	}

	/**
	 * Set theme
	 *
	 * @param integer $theme
	 */
	public function setTheme($theme)
	{
		$this->theme = $theme;
	}

	/**
	 * Get theme
	 *
	 * @return integer $theme
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * Set totalPoints
	 *
	 * @param integer $totalPoints
	 */
	public function setTotalPoints($totalPoints)
	{
		$this->totalPoints = $totalPoints;
	}

	/**
	 * Get totalPoints
	 *
	 * @return integer $totalPoints
	 */
	public function getTotalPoints()
	{
		return $this->totalPoints;
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
	 * Returns as array with the user attributes
	 *
	 * @return array
	 */
	public function toArray()
	{
		$fields = array('id', 'nickname', 'osmId', 'authToken', 'dateCreated', 'dateLastAccess', 'theme', 'totalPoints');
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
