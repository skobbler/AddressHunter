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
 * @license   http://www.opensource.org/licenses/BSD-3-Clause     New BSD License
 * @version   $Id$
 */

/**
 * GameController
 *
 * This controller serves as API for all AJAX operations related to the game (creating, joining,
 * canceling, strating, listing game users and game addresses, etc).
 *
 * All operations rely on 'current user' and 'current game' which are retrieved from the session
 * or from input parameters and are stored in the class's context as objects for global access.
 *
 * The response is JSON in most cases.
 *
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause     New BSD License
 */
class GameController extends Zend_Controller_Action
{
	/**
	 * JSON response status codes
	 */
	const STATUS_OK						= 200;
	const STATUS_INVALID_PARAMETER		= 400;
	const STATUS_AUTHENTICATION_FAILED	= 401;
	const STATUS_ERROR					= 500;
	const STATUS_UNAVAILABLE_GAME		= 601;
	const STATUS_UNAVAILABLE_USER		= 602;
	const STATUS_TO_MANY_PLAYERS		= 603;
	const STATUS_NO_ACCESS_RIGHTS		= 609;
	const STATUS_ALREADY_FOUND			= 611;
	const STATUS_MISSING_ADDRESSES		= 612;
	const STATUS_NO_AVAILABLE_GAMES		= 620;

	/**
	 * Identifier of current game
	 *
	 * @var int
	 */
	private $gameId = 0;

	/**
	 * Stores the osm-nickname of current game user
	 *
	 * @var string
	 */
	private $nickname = null;

	/**
	 * Stores the osm-id of current game user
	 *
	 * @var int
	 */
	private $osmId = null;

	/**
	 * Application_Model_Addresshunter_User object
	 * holds current user information
	 *
	 * @var Application_Model_Addresshunter_User
	 */
	private $user = null;

	/**
	 * Application_Model_Addresshunter_Game object
	 * holds current game information
	 *
	 * @var Application_Model_Addresshunter_Game
	 */
	private $game = null;

	/**
	 * GameController init
	 *
	 * Checks OSM authentication based on session data.
	 * Retrives the current user and - if available - the current game.
	 *
	 * NOTE: Relies on POST data (game_id)
	 *
	 * NOTE: All actions rely on this init for the current user and current game data
	 *
	 * @return void
	 */
	public function init()
	{
		$session = new Zend_Session_Namespace('addresshunter');
		$registry = Zend_Registry::getInstance();
		$this->_em = $registry->doctrine->_em;
		$this->_logger = $registry->logger;

		// checking games, users and addresses for inconsistency
		$this->monitorAction();

		// checking against the DB if the user's authentication token is still valid
		$authenticated = false;
		try {
			if (!empty($session->osmId) && !empty($session->accessToken)) {
				$this->osmId = $session->osmId;
				$this->nickname = $session->nickname;

				$token = unserialize($session->accessToken);
				$dbUser = $this->_em->getRepository('Application_Model_Addresshunter_User')->findOneByOsmId($session->osmId);
				if (!empty($dbUser) && $dbUser->getAuthToken() == $token) {
					$this->user = $dbUser;
					$authenticated = true;

					if ($this->getRequest()->has('game_id')){
						$gameId = (int) $this->getRequest()->getParam('game_id');
						if (!empty($gameId)) {
							try {
								$this->game = $this->_em->getRepository('Application_Model_Addresshunter_Game')->findOneById($gameId);
							} catch (Exception $e) {
								$this->_logger->log('find Game Init: ' . $e->getMessage(), Zend_Log::ERR);
							}
						}
					}
				}
			}
		} catch (Exception $e) {
			$this->_logger->err('AddressHunter /game/init error: ' . $e->getMessage());
		}

		if (!$authenticated) {
			// logging out
			Zend_Session::namespaceUnset('addresshunter');
			$data = array('status' => self::STATUS_AUTHENTICATION_FAILED);
			$this->_helper->json($data, true);
		}
	}

	/**
	 * Index action
	 *
	 * Does nothing. Returns error code as an invalid API entry point.
	 * TODO: serve an API manual here?
	 *
	 * @return void
	 */
	public function indexAction()
	{
		$data = array(
			'status' => self::STATUS_ERROR
		);
		$this->_helper->json($data, true);
	}

	/**
	 * Creates a new game.
	 *
	 * The parameters are validated and upon success a new game is created.
	 * The game creator is set as first player in the game.
	 *
	 * NOTE: Relies on POST data (gName, posX, posY, gAddrNo, gPlayersNo gTimeframe, gRadius)
	 *
	 * @access public
	 * @return mixed JSON formatted response
	 */
	public function createAction()
	{
		$data = array();

		// cleaning up the game name (or generating one)
		$gameName = $this->getRequest()->getParam('gName', 'AddressHunter ' . time());
		$gameName = trim($gameName);
		$gameName = preg_replace("/[^A-Za-z0-9\*\-()\\/@#+_?| ]/", '', $gameName);
		if (empty($gameName)) {
			$gameName = 'ADST' . time();
		}
		if (strlen($gameName) > 15) {
			$gameName = substr($gameName, 0, 15);
		}

		// validating game coordinates
		$gameX = $this->getRequest()->getParam('posX', '');
		$gameY = $this->getRequest()->getParam('posY', '');
		if (empty($gameX) || empty($gameY)) {
			$data['status'] = self::STATUS_INVALID_PARAMETER;
		}

		// validating number of addresses per player
		$playerAddressNo = (int) $this->getRequest()->getParam('gAddrNo', 3);
		$playerAddressNo = (empty($playerAddressNo) ? 3 : $playerAddressNo);
		if ($playerAddressNo < 1 || $playerAddressNo > 20) {
			$data['status'] = self::STATUS_INVALID_PARAMETER;
		}

		// validating max number of players in the game
		$maxPlayers = (int) $this->getRequest()->getParam('gPlayersNo', 5);
		$maxPlayers = (empty($maxPlayers) ? 5 : $maxPlayers);
		if ($maxPlayers < 2 || $maxPlayers > 50) {
			$data['status'] = self::STATUS_INVALID_PARAMETER;
		}

		// validating game timeframe (hours)
		$timeframe = (int) $this->getRequest()->getParam('gTimeframe', 1);
		$timeframe = (empty($timeframe) ? 1 : $timeframe);
		if ($timeframe < 1 || $timeframe > 24) {
			$data['status'] = self::STATUS_INVALID_PARAMETER;
		}

		// validating game radius (km)
		$radius = $this->getRequest()->getParam('gRadius', 2);
		$radius = (empty($radius) ? 2 : $radius);
		if ($radius < 0.5 || $radius > 5) {
			$data['status'] = self::STATUS_INVALID_PARAMETER;
		}

		// TODO: single player version
		$gameType = 2; // multiplayer

		if (empty($data['status'])) {
			try {
				if ($this->_em->getRepository('Application_Model_Addresshunter_Gameuser')->findOneBy(array(
						'user' => $this->user->getId(),
						'status' => Application_Model_Addresshunter_Gameuser::STATUS_ACTIVE
				))) {
					// the user is already active in another game
					$data['status'] = self::STATUS_UNAVAILABLE_USER;
				}
				else {
					// creating and saving the new game
					$game = new Application_Model_Addresshunter_Game;
					$game->setName($gameName);
					$game->setGameX($gameX);
					$game->setGameY($gameY);
					$game->setPlayerAddressNo($playerAddressNo);
					$game->setMaxPlayers($maxPlayers);
					$game->setTimeframe($timeframe);
					$game->setRadius($radius);
					$game->setStatus(Application_Model_Addresshunter_Game::STATUS_NEW);
					$game->setType($gameType);
					$game->setCreatedBy($this->user);
					$this->_em->persist($game);
					$this->_em->flush();

					// storing the game obj in the context for global access
					$this->gameId = $game->getId();
					$this->game = $game;

					// forwarding to joinAction
					$this->joinAction();

					// TODO: remove?
					$data = array(
						'status' => self::STATUS_OK,
						'data' => array(
							'id' => $game->getId(),
							'name' => $game->getName()
						)
					);
				}
			} catch (Exception $e) {
				$this->_logger->log('create Action: ' . $e->getMessage(), Zend_Log::ERR);
				$data = array('status' => self::STATUS_ERROR);
			}
		}

		$this->_helper->json($data, true);
	}

	/**
	 * Internal method. Generates addresses for the current game.
	 *
	 * This method is called when a game is started (not when is created, since at that time
	 * we don't yet know now many players will be joining).
	 *
	 * The address selection is based on the game's attributes (centroid, radius, joined players, etc).
	 *
	 * If not enough addresses are found within the given radius the game cannot start.
	 * In this case the return value is FALSE.
	 *
	 * Upon success, the return value is TRUE.
	 *
	 * NOTE: the address selection algorythm is quite simple for now. Always the same addresses
	 * are selected for the same area unless they are consumed. Randomized selection is not possible
	 * with Doctrine :(
	 *
	 * TODO: improve the algorythm, future solutions might include not only randomization,
	 * but also an even distribution.
	 *
	 * @access private
	 * @return boolean
	 */
	private function generateGameAddresses()
	{
		// building up the parameters
		$distance = number_format($this->game->getRadius() / sqrt(2), 2);
		$bbox = Geo_GeoUtil::getBoundingBox($this->game->getGameX(), $this->game->getGameY(), $distance);
		$gameAddressNo = $this->game->getActivePlayers()->count() * $this->game->getPlayerAddressNo();

		// generating the addresses
		$addresses = $this->_em->getRepository('Application_Model_Addresshunter_Address')->getAvailableAddresses($bbox, $gameAddressNo);

		if (count($addresses) == $gameAddressNo) {
			// adding the addresses to the game one by one
			foreach ($addresses as $newAddress) {
				try {
					$gameAddress = new Application_Model_Addresshunter_Gameaddress();
					$gameAddress->setAddress($newAddress);
					$gameAddress->setGame($this->game);
					$gameAddress->setStatus(Application_Model_Addresshunter_Gameaddress::STATUS_ACTIVE);
					$this->_em->persist($gameAddress);
					$this->_em->flush();

					// marking the address as unavailable for other games
					$newAddress->setIsAvailable(false);
					$this->_em->persist($newAddress);
					$this->_em->flush();
				}
				catch (Exception $e) {
					$this->_logger->log('generate game Addresses: ' . $e->getMessage(), Zend_Log::ERR);
					return false;
				}
			}
		}
		else {
			return false;
		}
		return true;
	}

	/**
	 * Starts the current game. Sets the game to a playing state.
	 *
	 * Since both the number of addresses to play with and the bonus points are depending on the
	 * number of users joined to the game, these are generated/calculated only at this point.
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function startAction()
	{
		if (empty($this->game)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		if (!isset($data['status'])) {
			try {
				$status = self::STATUS_OK;
				if ($this->game->getStatus() == Application_Model_Addresshunter_Game::STATUS_NEW) {
					$totalGameUsers = $this->game->getActivePlayers()->count();
					// calculating the bonus points
					$gameBonus = $totalGameUsers * $this->game->getPlayerAddressNo();
					// generating the addresses (this includes also adding them to the game)
					if (!$this->generateGameAddresses()) {
						$status = self::STATUS_MISSING_ADDRESSES;
					} else {
						// setting the game's status to 'playing'
						$this->game->setStatus(Application_Model_Addresshunter_Game::STATUS_PLAYING);
						$this->game->setDateStarted(new DateTime());
						$this->game->setBonus($gameBonus);
						$this->_em->persist($this->game);
						$this->_em->flush();
					}
				}

				$data = array(
					'status' => $status,
					'data' => array(
						'id' => $this->gameId,
						'status' => $this->game->getStatus()
					)
				);
			} catch (Exception $e) {
				$this->_logger->log('generate game Addresses: ' . $e->getMessage(), Zend_Log::ERR);
				$data = array('status' => self::STATUS_ERROR);
			}
		}

		$this->_helper->json($data, true);
	}

	/**
	 * Cancels the (not yet started) current game.
	 *
	 * This operation is accessible only for the game creator and only before the game is started.
	 * The game's status is set to 'canceled' and all joined users are marked as 'inactive' in this game.
	 * The game's status change will notify the joined users that the game was canceled.
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function cancelAction()
	{
		if (empty($this->game)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		if (!isset($data['status'])) {
			try {
				// changing the game's status
				$this->game->setStatus(Application_Model_Addresshunter_Game::STATUS_CANCELED);
				$this->game->setDateEnded(new DateTime());
				$this->_em->persist($this->game);
				$this->_em->flush();

				// marking the gameusers as 'inactive'
				$players = $this->game->getActivePlayers();
				foreach ($players as $player) {
					$player->setStatus(Application_Model_Addresshunter_Gameuser::STATUS_INACTIVE);
					$this->_em->persist($player);
					$this->_em->flush();
				}

				$data = array(
					'status' => self::STATUS_OK,
					'data' => array(
						'id' => $this->gameId,
						'status' => $this->game->getStatus()
					)
				);
			} catch (Exception $e) {
				$this->_logger->log('cancel Action: ' . $e->getMessage(), Zend_Log::ERR);
				$data = array('status' => self::STATUS_ERROR);
			}
		}

		$this->_helper->json($data, true);
	}

	/**
	 * Current user leaves the current game.
	 *
	 * Leaving a game is possible both before and after the game was started, except for one case:
	 * the game creator cannot leave the game before it's started, only cancel it.
	 * TODO: implement this verification
	 * 
	 * If all players leave the game (there are no active players in the game), the game is
	 * marked as 'expired'.
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function leaveAction()
	{
		if (empty($this->game)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}
		if (empty($this->user)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		if (!isset($data['status'])) {
			try {
				// changing the user's status
				$player = $this->game->getPlayer($this->osmId);
			 	$player->setStatus(Application_Model_Addresshunter_Gameuser::STATUS_CANCELED);
				$this->_em->persist($player);
				$this->_em->flush();

				// checking if there are still active players in the game: if not, setting the game as expired
				$activeUsersInGame = $this->game->getActivePlayers()->count();
				if (!$activeUsersInGame) {
					$this->setGameExpired($this->game);
				}

				$data = array('status' => self::STATUS_OK);
			} catch (Exception $e) {
				// game not found or connection lost
				$data = array('status' => self::STATUS_ERROR);
				$this->_logger->log('leave Game: ' . $e->getMessage(), Zend_Log::ERR);
			}
		}

		$this->_helper->json($data, true);
	}

	/**
	 * Current user joins a new game.
	 *
	 * One user can be active player in only one game at a time. The user can only join if the
	 * max number of players in the game are not yet filled.
	 *
	 * NOTE: Relies on POST data (posX, posY)
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function joinAction()
	{
		// validating parameters
		if (empty($this->game)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}
		if (empty($this->user)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}
		// position/coordinates of the user at the moment of joining
		$posX = $this->getRequest()->getParam('posX', '');
		$posY = $this->getRequest()->getParam('posY', '');
		if (empty($posX) || empty($posY)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		if (!isset($data['status'])) {
			try {
				$activeUsersInGame = 0;
				if (!$this->game->getActivePlayers()->isEmpty()) {
					$activeUsersInGame = $this->game->getActivePlayers()->count();
				}
				// checking if the user is already added to this game (ex. re-joining after a leave is possible)
				if ($gameuser = $this->game->getPlayer($this->osmId)) {
					if ($gameuser->getStatus() == Application_Model_Addresshunter_Gameuser::STATUS_ACTIVE) {
						$data = array(
							'status' => self::STATUS_OK,
							'data' => array(
								'id' => $this->game->getId(),
								'name' => $this->game->getName(),
								'status' => $this->game->getStatus()
							)
						);

						$this->_helper->json($data, true);
					}
				} else {
					$gameuser = new Application_Model_Addresshunter_Gameuser;
				}

				if ($this->game->getStatus() != Application_Model_Addresshunter_Game::STATUS_NEW) {
					// game already started or canceld
					$data = array('status' => self::STATUS_UNAVAILABLE_GAME);
				} elseif ($this->_em->getRepository('Application_Model_Addresshunter_Gameuser')->findOneBy(
						array('user' => $this->user->getId(), 'status' => Application_Model_Addresshunter_Gameuser::STATUS_ACTIVE))) {
					// the user is present as active player in another game
					$data = array('status' => self::STATUS_UNAVAILABLE_USER);
				} elseif ($this->game->getMaxPlayers() <= $activeUsersInGame) {
					// the max number of players in this game are already filled
					$data = array('status' => self::STATUS_TO_MANY_PLAYERS);
				} else {
					// adding the user to the game
					$gameuser->setUser($this->user);
					$gameuser->setGame($this->game);
					$gameuser->setDateJoined(new DateTime());
					$gameuser->setStatus(Application_Model_Addresshunter_Gameuser::STATUS_ACTIVE);
					$gameuser->setJoinX($posX);
					$gameuser->setJoinY($posY);
					$gameuser->setPoints(0);
					$this->_em->persist($gameuser);
					$this->_em->flush();

					$data = array(
						'status' => self::STATUS_OK,
						'data' => array(
							'id' => $this->game->getId(),
							'name' => $this->game->getName(),
							'status' => $this->game->getStatus()
						)
					);
				}
			} catch (Exception $e){
				// game or user not found or connection lost
				$data = array('status' => self::STATUS_ERROR);
				$this->_logger->log('join Action: ' . $e->getMessage(), Zend_Log::ERR);
			}
		}

		$this->_helper->json($data, true);
	}

	/**
	 * Lists all available games around the current user (in 50km radius).
	 *
	 * A game is available if it was not yet started and is not expired.
	 * NOTE: Relies on POST data (posX, posY)
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function listAction()
	{
		if (empty($this->user)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}
		// the user's position
		$posX = $this->getRequest()->getParam('posX', '');
		$posY = $this->getRequest()->getParam('posY', '');
		if (empty($posX) || empty($posY)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		if (!isset($data['status'])) {
			try {
				$result = array();
				// retrieving the available games (in 50km radius, max 25 results)
				$bbox = Geo_GeoUtil::getBoundingBox($posX, $posY, 50);
				$allGames = $this->_em->getRepository('Application_Model_Addresshunter_Game')->getAvailableGames($bbox, 25);
				if (count($allGames) > 0) {
					foreach ($allGames as $game) {
						$distance = Geo_GeoUtil::distance($posX, $posY, $game->getGameX(), $game->getGameY());
						$distanceStr = number_format($distance, 1, '.', '') . ' km';
						array_push($result,
							array(
								'id' => $game->getId(),
								'name' => $game->getName(),
								'maxPlayers' => $game->getMaxPlayers(),
								'players' => $game->getActivePlayers()->count(),
								'distance' => $distance,
								'distance_str' => $distanceStr
							)
						);
					}
					// sorting results by distance
					// TODO: do the sorting in Doctrine
					usort($result, 'cmp_distance');
					$data = array(
						'status' => self::STATUS_OK,
						'data' => $result
					);
				}
				else {
					$data = array('status' => self::STATUS_NO_AVAILABLE_GAMES);
				}
			} catch(Exception $e) {
				$this->_logger->log('list Action:' . $e->getMessage(), Zend_Log::ERR);
				$data = array('status' => self::STATUS_ERROR);
			}
		}

		$this->_helper->json($data);
	}

	/**
	 * Private method to return general info about the current game: name, status and remaining time. 
	 * 
	 * It is used when building the JSON response of many requests.
	 * By checking the game's remainign time it can also change the game's status to 'expired' if the case.
	 *
	 * @access private
	 * @return array
	 */
	private function getGameInfo()
	{
		if (empty($this->game)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}
		try{
			$timeRemaining = '';
			// calculating remaining time, but only for already started games
			if ($this->game->getDateStarted()) {
				$dateStarted = $this->game->getDateStarted();
				$dateGameStarted = new Zend_Date($dateStarted->getTimestamp());
				$dateGameTimeout = $dateGameStarted->addHour($this->game->getTimeframe());
				$current = new Zend_Date();
				if ($dateGameTimeout->compareIso($current) < 0) {
					// setting the game's status to 'expired'
					$this->setGameExpired($this->game);
				}
				$diff = ($dateGameTimeout->getTimestamp() - $current->getTimestamp());
				$remainingHours = (int) ($diff / 3600);
				$remainingMinutes = (int) (($diff - ($remainingHours * 3600)) / 60);
				$remainingMinutes = ($remainingMinutes < 10) ? ('0' . $remainingMinutes) : $remainingMinutes;
				$timeRemaining = $remainingHours . ':' . $remainingMinutes;
			}
			// building up the array with the response
			$result = array(
				'name' => $this->game->getName(),
				'status' => $this->game->getStatus(),
				'time_remaining' => $timeRemaining
			);

		} catch (Exception $e) {
			$this->_logger->log('game Info: ' . $e->getMessage(), Zend_Log::ERR);
			return 0;
		}

		return $result;
	}

	/**
	 * Returns the details of the current game.
	 *
	 * NOTE: Relies on POST data (posX, posY)
	 * It uses the user's location/coordinates to determine the distance to the game.
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function detailsAction()
	{
		if (empty($this->game)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		// the user's position
		$posX = $this->getRequest()->getParam('posX', '');
		$posY = $this->getRequest()->getParam('posY', '');
		if (empty($posX) || empty($posY)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		if (!isset($data['status'])) {
			try {
				// calculating the distance between the user and this game
				$distance = Geo_GeoUtil::distance($posX, $posY, $this->game->getGameX(), $this->game->getGameY());
				$distanceStr = number_format($distance, 1, '.', '') . ' km';
				// building up the array with the game details
				$data = array(
					'status' => self::STATUS_OK,
					'data' => array(
						'name' => $this->game->getName(),
						'status' => $this->game->getStatus(),
						'timeframe' => $this->game->getTimeframe(),
						'posX' => $this->game->getGameX(),
						'posY' => $this->game->getGameY(),
						'playerAddrNo' => $this->game->getPlayerAddressNo(),
						'playerNo' => $this->game->getMaxPlayers(),
						'bonus_max' => ($this->game->getPlayerAddressNo() * $this->game->getMaxPlayers()),
						'radius' => $this->game->getRadius(),
						'creator' => $this->game->getCreatedBy()->getNickname(),
						'distance' => $distance,
						'distance_str' => $distanceStr,
						'bonus' => $this->game->getBonus(),
						'players_joined' => $this->game->getActivePlayers()->count()
					)
				);
			} catch (Exception $e) {
				$this->_logger->log('details Action: ' . $e->getMessage(), Zend_Log::ERR);
				$data = array('status' => self::STATUS_ERROR);
			}
		}

		$this->_helper->json($data);
	}

	/**
	 * Returns general details of the current user (name and total points).
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function userAction()
	{
		try{
			$data = array(
				'status' => self::STATUS_OK,
				'data' => array(
					'name' => $this->user->getNickname(),
					'totalPoints' => (int) $this->user->getTotalPoints()
				)
			);
		} catch (Exception $e) {
			$this->_logger->log('user details Action: ' . $e->getMessage(), Zend_Log::ERR);
			$data = array('status' => self::STATUS_ERROR);
		}

		$this->_helper->json($data);
	}

	/**
	 * Returns all addresses associated with the current game.
	 *
	 * All addresses are returned regardless of their status (found or not).
	 * Each address detail contains also it's relative distance to the current user and the osmId
	 * of the user who found it (if it was found).
	 * Game info is also sent as aditional information.
	 *
	 * NOTE: Relies on POST data (x, y)
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function addressesAction()
	{
		if (empty($this->game)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		if (!isset($data['status'])) {
			$result = array();
			try {
				// if the game is already playing, only the users participating in the game have the
				// right to request the game addresses
				if ($this->game->getStatus() == Application_Model_Addresshunter_Game::STATUS_PLAYING) {
					$userStatus = $this->game->getPlayer($this->osmId)->getStatus();
					if (!$userStatus || $userStatus != Application_Model_Addresshunter_Gameuser::STATUS_ACTIVE) {
						$data = array('status' => self::STATUS_NO_ACCESS_RIGHTS);
					}
				}
				if (!isset($data['status'])) {
					$addresses = $this->game->getAddresses();
					// iterating through each gameaddress and building up an associative array with the details
					foreach ($addresses as $gameAddress) {
						$address = $gameAddress->getAddress();
						// calculating the distance between the user and the address's approximate position
						$distance = Geo_GeoUtil::distance(
							$this->getRequest()->getParam('x', 1),
							$this->getRequest()->getParam('y', 1),
							$address->getApproxX(),
							$address->getApproxY()
						);
						$distance = number_format($distance, 1, '.', '');
						$gameAddressData = array(
							'id' => $gameAddress->getId(),
							'name' => $address->getName(),
							'status' => $gameAddress->getStatus(),
							'distance' => $distance,
							'finalX' => $gameAddress->getFinalX(),
							'finalY' => $gameAddress->getFinalY(),
						);
						// adding the osmId of the user who found this address (if the address was already found)
						if ($gameAddress->getUser()) {
							$gameAddressData['osmId'] = $gameAddress->getUser()->getOsmId();
						} else {
							$gameAddressData['osmId'] = 0;
						}

						array_push($result, $gameAddressData);
					}
					$data = array(
						'status' => self::STATUS_OK,
						'data' => array(
							'addresses' => $result,
							'info' => $this->getGameInfo()
						)
					);
				}
			} catch (Exception $e) {
				$this->_logger->log('addresses Action: ' . $e->getMessage(), Zend_Log::ERR);
				$data = array('status' => self::STATUS_ERROR);
			}
		}

		$this->_helper->json($data);
	}

	/**
	 * Returns the list of users joined to the current game.
	 * 
	 * This action is restricted to the users who are also joined to the current game, and is
	 * accessible only before the game is started (when it's in waiting mode to start).
	 * Player details include also their global points and time elapsed since they joined the game.
	 * Game info is also sent as aditional information.
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function playersAction()
	{
		if (empty($this->game)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		if (!isset($data['status'])) {
			try {
				$result = array();
				// retrieving the list of active players
				$players = $this->game->getActivePlayers();
				$currentUserIsInGame = false;
				// iterating through all players and building up the result array
				foreach ($players as $player) {
					// checking if the current user is in this game
					if ($player->getUser()->getOsmId() == $this->osmId) {
						$currentUserIsInGame = true;
					}
					// calculating and formatting the time elapsed since the user joined
					$elapsedTimeStr = '';
					$dateJoined = $player->getDateJoined();
					$current = new Zend_Date();
					$diff = ($current->getTimestamp() - $dateJoined->getTimestamp());
					$hours = (int) ($diff / 3600);
					$minutes = (int) (($diff - ($hours * 3600)) / 60);
					if ($hours == 0) {
						if ($minutes < 2) {
							$elapsedTimeStr = 'now';
						}
						else {
							$elapsedTimeStr = $minutes . ' minutes ago';
						}
					} elseif ($hours == 1) {
						$elapsedTimeStr = $hours . ' hour ago';
					} else {
						$elapsedTimeStr = $hours . ' hours ago';
					}
					// results array
					$playerData = array(
						'id' => $player->getUser()->getId(),
						'nickname' => $player->getUser()->getNickname(),
						'since' => $elapsedTimeStr,
						'points' => (int) $player->getUser()->getTotalPoints()
					);
					array_push($result, $playerData);
				}

				// if the current user is not among the players, returning with error
				if (!$currentUserIsInGame) {
					$data = array('status' => self::STATUS_NO_ACCESS_RIGHTS);
				} else {
					$data = array(
						'status' => self::STATUS_OK,
						'data' =>array(
							'players' => $result,
							'info' => $this->getGameInfo()
						)
					);
				}
			} catch (Exception $e) {
				$this->_logger->log('players Action: ' . $e->getMessage(), Zend_Log::ERR);
				$data = array('status' => self::STATUS_ERROR);
			}
		}

		$this->_helper->json($data);
	}

	/**
	 * Internal method to handle address uploading to OSM.
	 *
	 * Each address is added to OSM as a single 'node', bundled in a new 'changeset'.
	 * Address details sent to OSM: coordinates, city, postcode, street, housenumber (complying to
	 * the most frequently used 'addr:...' OSM keys).
	 * Just before the upload we check once more to make sure the address was not added recently to OSM.
	 * 
	 * Returns -1 in case of unauthorized access;
	 *			0 if the upload fails;
	 *			1 for successful execution;
	 *			2 if the address is already in OSM
	 *
	 * @param float $posX longitude of the location
	 * @param float $posY latitude of the location
	 * @param string $city
	 * @param string $street
	 * @param string $housenumber
	 * @param string $postcode
	 * @param string $full
	 * @access private
	 * @return int
	 */
	private function uploadAddress($posX, $posY, $city, $street, $housenumber, $postcode, $full)
	{
		$session = new Zend_Session_Namespace('addresshunter');
		try {
			// checking if this address is still not present in OSM
			$osmResult = Geo_MqNominatimGeocoder::geocode($housenumber . ' ' . $full);
			if(is_object($osmResult)) {
				if ($osmResult->accuracy == Geo_Geolocation::ACCURACY_ADDRESS) {
					return 2;
				}
			}

			// checking if the user has the OAuth access token
			if (empty($session->accessToken)) {
				return -1;
			}
			// unserializing the token
			$token = unserialize($session->accessToken);

			// preparing a config array with the OAuth access token and config options
			$zendConfig = Zend_Registry::get('Zend_Config');
			$config = $zendConfig->osm->toArray();
			$config['oauth']['accessToken'] = $token;

			// connecting to OSM
			$osm = new Addresshunter_Service_Osm($config);
			// creating a new OSM changeset
			$changesetId = $osm->changeset->create();
			// adding a new node representing the address
			$osm->node->create($changesetId, $posX, $posY, $city, $street, $housenumber, $postcode);
			// closing/saving the changeset
			$osm->changeset->close($changesetId);

		} catch (Exception $e) {
			$this->_logger->log('game Upload address: ' . $e->getMessage(), Zend_Log::ERR);
			return 0;
		}

		return 1;
	}

	/**
	 * Handles the finding of an address by the current user.
	 *
	 * Finding an address means reporting it's exact position/coordinates.
	 * In the process, the address's final position is stored, it's status is updated to 'found'
	 * and the current user gets his points. Furthermore, if this address is the last one in this
	 * game, the game ends (forwarding to endGame action).
	 * 
	 * To upload the found address to OSM, uploadAddress() method is called. In case this method
	 * returns with the error that the address was already added to OSM (probably recently) the
	 * flow will not stop: the user will still get his points, just the address will not be
	 * uploaded again.
	 * 
	 * NOTE: Relies on POST data (gameaddress_id, posX, posY)
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function findaddressAction()
	{
		// the id of the game-address
		$gameaddressId = (int)$this->getRequest()->getParam('gameaddress_id');
		if (empty($gameaddressId)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}
		// the exact coordinates
		$finalX = $this->getRequest()->getParam('posX', '');
		$finalY = $this->getRequest()->getParam('posY', '');
		if (empty($finalX) || empty($finalY)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		if (!isset($data['status'])) {
			try{
				$gameaddress = $this->_em->getRepository('Application_Model_Addresshunter_Gameaddress')->findOneById($gameaddressId);
			} catch (Exception $e) {
				$this->_logger->log('findaddress Action: ' . $e->getMessage(), Zend_Log::ERR);
				$data = array('status' => self::STATUS_ERROR);
			}
		}

		if (!isset($data['status'])) {
			try {
				$address = $gameaddress->getAddress();
				$game = $gameaddress->getGame();
				$this->game = $game;

				if ($gameaddress->getStatus() != Application_Model_Addresshunter_Gameaddress::STATUS_ACTIVE) {
					// the address was previously checked/found by someone else
					$data = array('status' => self::STATUS_ALREADY_FOUND);
				} else {
					// setting address coordinates and changing the status
					$gameaddress->setFinalY($finalY);
					$gameaddress->setFinalX($finalX);
					$gameaddress->setDateFound(new DateTime());
					$gameaddress->setStatus(Application_Model_Addresshunter_Gameaddress::STATUS_DISCOVERED);
					$gameaddress->setUser($this->user);
					// uploading the address to OSM
					$uploadResult = $this->uploadAddress($finalX, $finalY, $address->getCity(), $address->getStreet(), $address->getHousenumber(), $address->getPostcode(), $address->getFull());
					if ($uploadResult == 1)	{
						$gameaddress->setStatus(Application_Model_Addresshunter_Gameaddress::STATUS_UPLOADED);
					} elseif ($uploadResult == 2){
						$gameaddress->setStatus(Application_Model_Addresshunter_Gameaddress::STATUS_UPLOADED_BEFORE);
						// NOTE: this case is handled silently
					}
					$this->_em->persist($gameaddress);
					$this->_em->flush();

					// updating the users' points
					$player = $this->game->getPlayer($this->osmId);
					$points = $player->getPoints();
					$points++;
					$player->setPoints($points);
					$this->_em->persist($player);
					$this->_em->flush();

					$totalPoints = $this->user->getTotalPoints();
					$totalPoints++;
					$this->user->setTotalPoints($totalPoints);
					$this->_em->persist($this->user);
					$this->_em->flush();

					// if it's the last address in the game, the game status must change (forwarding to endGame)
					if (!$game->getActiveAddresses()->count()) {
						$this->endGame($game);
					}

					$data = array(
						'status' => self::STATUS_OK,
						'data' => array(
							'id' => $gameaddress->getId(),
							'status' => $gameaddress->getStatus(),
							'g_points' => $points,
							'points' => $totalPoints
						)
					);
				}
			} catch (Exception $e) {
				$this->_logger->log('findaddress Action: ' . $e->getMessage(), Zend_Log::ERR);
				$data = array('status' => self::STATUS_ERROR);
			}
		}

		$this->_helper->json($data);
	}

	/**
	 * Handles uploading a captured photo of the found address.
	 *
	 * Saves the photo as a file and adds the filename to the address information.
	 * The photo data arrives as base64 encoded string. TODO: change to regular FILE post if the 
	 * client-side implementation permits.
	 *
	 * NOTE: Relies on POST data (gameaddress_id, photo)
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function photouploadAction()
	{
		$gameaddressId = (int)$this->getRequest()->getParam('gameaddress_id');
		if (empty($gameaddressId)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		// decoding photo data
		$photoData = base64_decode($this->getRequest()->getParam('photo'));
		if (empty($photoData)) {
			$this->_logger->log('findaddress Action: no photo data', Zend_Log::ERR);
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		if (!isset($data['status'])) {
			try {
				// retrieving the gameaddress
				$gameaddress = $this->_em->getRepository('Application_Model_Addresshunter_Gameaddress')->findOneById($gameaddressId);
			} catch (Exception $e) {
				$this->_logger->log('findaddress Action: ' . $e->getMessage(), Zend_Log::ERR);
				$data = array('status' => self::STATUS_ERROR);
			}
		}

		// TODO: add path and quality settings to the main config
		$filename = '../public/photos/' . $gameaddressId . '.jpg';
		// checking if there's not already an photo for this address
		if (!isset($data['status']) && $gameaddress->getFilename() == '') {
			// creating the image
			$im = @imagecreatefromstring($photoData);
			if ($im !== false) {
				// saving the image
				if (imagejpeg($im, $filename, 60)){
					// storing the filename in the DB
					$gameaddress->setFilename($gameaddressId . '.jpg');
					$this->_em->persist($gameaddress);
					$this->_em->flush();
					$data['status'] = self::STATUS_OK;
				} else {
					$data['status'] = self::STATUS_ERROR;
				}
				// freeing resources
				imagedestroy($im);
			} else {
				$data['status'] = self::STATUS_ERROR;
			}
		}

		$this->_helper->json($data);
	}

	/**
	 * Internal method to handle successful ending of a game.
	 * 
	 * A successfully ended game means that all addresses were found (so the game did not expire
	 * nor was abandoned).
	 * This method sets the game to a finished state, determines the game winner and sets all
	 * players as inactive (in this game).
	 *
	 * The winner of the game is the player with the most addresses found. In case of equality,
	 * the winner is the player who reached first the winning score (who collected first the
	 * last of their address).
	 *
	 * @param object $game
	 * @access private
	 * @return void
	 */
	private function endGame($game)
	{
		try {
			// changing game status and setting end time
			$game->setStatus(Application_Model_Addresshunter_Game::STATUS_FINISHED);
			$game->setDateEnded(new DateTime());
			$this->_em->persist($game);
			$this->_em->flush();

			// finding the game winner
			$gameWinner = $this->_em->getRepository('Application_Model_Addresshunter_Gameuser')->findGameWinner($game);

			// saving the game winner
			$gameWinner->setIsWinner(true);
			$totalGamePoints = $gameWinner->getPoints() + $game->getBonus();
			$gameWinner->setPoints($totalGamePoints);
			$this->_em->persist($gameWinner);
			$this->_em->flush();

			$user = $gameWinner->getUser();
			$userTotalPoints = $user->getTotalPoints();
			$userTotalPoints += $game->getBonus();
			$user->setTotalPoints($userTotalPoints);
			$this->_em->persist($user);
			$this->_em->flush();

			// changing status of all players
			$players = $game->getActivePlayers();
			foreach ($players as $player) {
				$player->setStatus(Application_Model_Addresshunter_Gameuser::STATUS_INACTIVE);
				$this->_em->persist($player);
				$this->_em->flush();
			}
		} catch (Exception $e) {
			$this->_logger->log('game End: ' . $e->getMessage(), Zend_Log::ERR);
		}
	}

	/**
	 * Internal method that handles game expiration.
	 * 
	 * A game can expire if it's timeframe is exceeded or all players quit.
	 * This method sets the game's status to 'expired', marks all players as 'inactive' (in this game)
	 * and frees all remaining (not found) addresses so they can be re-used in other games.
	 *
	 * @param object $game
	 * @access private
	 * @return void
	 */
	private function setGameExpired($game)
	{
		try {
			// setting game status to 'expired'
			$game->setStatus(Application_Model_Addresshunter_Game::STATUS_EXPIRED);
			$game->setDateEnded(new DateTime());
			$this->_em->persist($game);
			$this->_em->flush();
			if ($game->getActiveAddresses()->count()) {
				// iterating through all active gameaddresses and setting their status to 'expired'
				foreach ($game->getActiveAddresses() as $gameAddress) {
					$gameAddress->setStatus(Application_Model_Addresshunter_Gameaddress::STATUS_EXPIRED);
					$this->_em->persist($gameAddress);
					$this->_em->flush();
					// changing the address's status also to 'available' 
					$address = $gameAddress->getAddress();
					$address->setIsAvailable(true);
					$this->_em->persist($address);
					$this->_em->flush();
				}
			}
			if (($game->getActivePlayers()->count())) {
				$players = $game->getActivePlayers();
				// iterating through all active players (gameusers) and setting their status to 'inactive'
				foreach ($players as $player) {
					$player->setStatus(Application_Model_Addresshunter_Gameuser::STATUS_INACTIVE);
					$this->_em->persist($player);
					$this->_em->flush();
				}
			}
		} catch (Exception $e) {
			$this->_logger->log('game Expired: ' . $e->getMessage(), Zend_Log::ERR);
		}
	}

	/**
	 * Returns the results of the current (finished) game.
	 *
	 * Returns a list of all players, their points earned in this game and their total points.
	 *
	 * @access public
	 * @return mixed - JSON formatted response
	 */
	public function resultsAction()
	{
		$result = array();
		if (empty($this->game)) {
			$data = array('status' => self::STATUS_INVALID_PARAMETER);
		}

		if (!isset($data['status'])) {
			try {
				// retrieving the list of players
				$players = $this->game->getPlayers();
				$i = 1;
				foreach ($players as $player) {
					$playerData = array(
						'id' => $player->getUser()->getId(),
						'nickname' => $player->getUser()->getNickname(),
						'points' => (int) $player->getPoints(),
						'tpoints' => (int) $player->getUser()->getTotalPoints(),
						'is_winner' => (int) $player->getIsWinner()
					);
					array_push($result, $playerData);
				}
				// sorting the players by their points earned in this game
				usort($result, 'cmp_points');
				$data = array(
					'status' => self::STATUS_OK,
					'data' => array(
						'players' => $result
					)
				);
			} catch (Exception $e) {
				$this->_logger->log('results Action: ' . $e->getMessage(), Zend_Log::ERR);
				$data = array('status' => self::STATUS_ERROR);
			}
		}

		$this->_helper->json($data);
	}

	/**
	 * Checks all active games, players and game addresses for inconsistency.
	 *
	 * Active games are checked for:
	 *		- their number of players not to be null
	 *		- created one day ago (24h) and are not played
	 * If any inconsistency is found the game is set to an expired state.
	 *
	 * Players in an expired or finished game are set inactive.
	 *
	 * All alocated game addresses that are still active in an expired or finished
	 * game are set available for other games.
	 *
	 * @access private
	 * @return void
	 */
	public function monitorAction()
	{
		try{
			$all_games = $this->_em->getRepository('Application_Model_Addresshunter_Game')->getActiveGames();
			if (count($all_games) > 0) {
				foreach ($all_games as $game) {
					// if the game has no active users - it can not be played
					if (!$game->getActivePlayers()->count()) {
						$this->setGameExpired($game);
					}
					if ($game->getStatus() == Application_Model_Addresshunter_Game::STATUS_NEW) {
						$dateStarted = $game->getDateCreated();
						$dateGameStarted = new Zend_Date($dateStarted->getTimestamp());
						$dateGameTimeout = $dateGameStarted->addHour(24);
						$current = new Zend_Date();
						// checks if the game is expired
						if ($dateGameTimeout->compareIso($current) < 0) {
							$this->setGameExpired($game);
						}
					}
				}
			}

			$players = $this->_em->getRepository('Application_Model_Addresshunter_Gameuser')->findByStatus(Application_Model_Addresshunter_Gameuser::STATUS_ACTIVE);
			foreach ($players as $player) {
				$gameStatus = $player->getGame()->getStatus();
				if ($gameStatus != Application_Model_Addresshunter_Game::STATUS_NEW && $gameStatus != Application_Model_Addresshunter_Game::STATUS_PLAYING) {
					$endDate = $player->getGame()->getDateEnded();
					$endDateTimestamp = new Zend_Date($endDate->getTimestamp());
					$endDateTimeout = $endDateTimestamp->addMinute(1);
					$current = new Zend_Date();

					if ($endDateTimeout->compareIso($current) < 0) {
						$player->setStatus(Application_Model_Addresshunter_Gameuser::STATUS_INACTIVE);
						$this->_em->persist($player);
						$this->_em->flush();
					}
				}
			}

			$addresses = $this->_em->getRepository('Application_Model_Addresshunter_Gameaddress')->findByStatus(Application_Model_Addresshunter_Gameaddress::STATUS_ACTIVE);
			foreach ($addresses as $address) {
				$gameStatus = $address->getGame()->getStatus();
				if ($gameStatus != Application_Model_Addresshunter_Game::STATUS_NEW && $gameStatus != Application_Model_Addresshunter_Game::STATUS_PLAYING) {
					$endDate = $address->getGame()->getDateEnded();
					$endDateTimestamp = new Zend_Date($endDate->getTimestamp());
					$endDateTimeout = $endDateTimestamp->addMinute(1);
					$current = new Zend_Date();

					if ($endDateTimeout->compareIso($current) < 0) {
						$address->setStatus(Application_Model_Addresshunter_Gameaddress::STATUS_EXPIRED);
						$this->_em->persist($address);
						$this->_em->flush();

						$address = $address->getAddress();
						$address->setIsAvailable(true);
						$this->_em->persist($address);
						$this->_em->flush();
					}
				}
			}
		} catch (Exception $e) {
			$this->_logger->log('game monitor Action: ' . $e->getMessage(), Zend_Log::ERR);
		}
		return;
	}
}

/**
 * Custom comparison function to sort arrays containing distances.
 *
 * @param array $a Array containing 'distance' key
 * @param array $b Array containing 'distance' key
 * @return boolean
 */
function cmp_distance($a, $b)
{
	return ($a['distance'] <= $b['distance']);
}

/**
 * Custom comparison function to sort arrays containing game results.
 *
 * @param array $a Array containing 'is_winner' key and 'points' key
 * @param array $b Array containing 'is_winner' key and 'points' key
 * @return boolean
 */
function cmp_points($a, $b)
{
	if($a['is_winner']) return false;
	return ($a['points'] <= $b['points']);
}
