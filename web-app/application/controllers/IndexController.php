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
 * @package   Addresshunter
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause     New BSD License
 * @version   $Id$
 */

/**
 * IndexController
 *
 * This is the main entry point to the game that generates the game's interface and sets up
 * it's overall status depending if the user is authenticated and if the user is currently
 * active in a game.
 *
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause	 New BSD License
 */
class IndexController extends Zend_Controller_Action
{
	/**
	 * IndexController init
	 *
	 * @return void
	 */
	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_em = $registry->doctrine->_em;
		$this->_logger = $registry->logger;
	}

	/**
	 * IndexAction
	 *
	 * Checks if the user is authenticated. If yes, retrieves details about the user and his
	 * gaming status and sends them to the view so that the proper game interface can be
	 * constructed in the main template for the user.
	 *
	 * @return void
	 */
	public function indexAction()
	{
		$this->_helper->layout->setLayout('game');

		// getting user's authentication status
		$authenticated = false;

		$session = new Zend_Session_Namespace('addresshunter');
		$zendConfig = Zend_Registry::get('Zend_Config');

		try {
			// checking if the user has the OAuth access token
			if (!empty($session->accessToken)) {
				$token = unserialize($session->accessToken);

				// preparing a config array with the OAuth access token and config options
				$config = $zendConfig->osm->toArray();
				$config['oauth']['callbackUrl'] = $this->getRequest()->getScheme() . '://' . 
					$this->getRequest()->getHttpHost() . 
					$this->view->url(array('controller' => 'osm', 'action' => 'callback'));
				$config['oauth']['accessToken'] = $token;

				// performing a call to OSM (retrieving user details) to check if the OAuth token
				// is valid (there's no simpler way in OSM to check authentication)
				$osm = new Addresshunter_Service_Osm($config);
				$result = $osm->user->details();

				if (isset($result->user) && is_object($result->user)) {
					// retrieving user details from the DB
					$user = $this->_em->getRepository('Application_Model_Addresshunter_User')->findOneByOsmId($result->user['id']);
					if ($user) {
						$authenticated = true;
						$this->view->user = array(
							'id' => $user->getId(),
							'osmId' => $user->getOsmId(),
							'nickname' => $user->getNickname(),
							'points' => $user->getTotalPoints(),
							'rank' => '', // TODO
							'theme' => $user->getTheme()
						);
					}
				}
			}
		} catch (Exception $e) {
			$this->_logger->err($e->getMessage());
		}
		$this->view->authenticated = $authenticated;

		// getting the user's gaming status
		if ($authenticated) {
			// checking if the user is present as 'active' in a game
			$result = $this->_em->getRepository('Application_Model_Addresshunter_Gameuser')->findOneBy(array('user' => $user->getId(), 'status'=> Application_Model_Addresshunter_Gameuser::STATUS_ACTIVE));

			if ($result) {
				$game = $result->getGame();
				// special cases when the interface must 'jump' directly in the middle of the game:
				// when the user has joined a game and is waiting to start or when it's already
				// playing in a game (but the application was closed for some reason)
				if ($game->getStatus() == Application_Model_Addresshunter_Game::STATUS_NEW || $game->getStatus() == Application_Model_Addresshunter_Game::STATUS_PLAYING) {
					$this->view->currentGame = array(
						'id' => $game->getId(),
						'name' => $game->getName(),
						'status' => $game->getStatus(),
						'g_points' => (int) $result->getPoints(),
						'isCreator' => ($game->getCreatedBy()->getId() == $user->getId())
					);
				}
			}
		} else {
			// clearing the session - just for sure
			Zend_Session::namespaceUnset('addresshunter');
		}

		// adding version info (to be displayed in the game's 'About' screen)
		$this->view->version = $zendConfig->version->toArray();
		$this->view->version['osm'] = $zendConfig->osm->uri;
	}
}
