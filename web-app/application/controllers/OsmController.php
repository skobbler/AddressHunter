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
 * OsmController
 *
 * Handles operations related to OSM-authentication (via OAuth).
 * Since it's always called either inside of an iframe or by ajax, the output is either a redirect or JSON.
 *
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause	 New BSD License
 */
class OsmController extends Zend_Controller_Action
{
	/**
	 * OsmController init
	 *
	 * @return void
	 */
	public function init()
	{
		$registry = Zend_Registry::getInstance();
		$this->_em = $registry->doctrine->_em;
		$this->_logger = $registry->logger;
		
		// disabling layout (not to load the main template)
		$this->_helper->layout->disableLayout();
	}

	/**
	 * IndexAction
	 *
	 * Checks if the user is authenticated and redirects accordingly (to authenticate or
	 * IndexController's index action).
	 *
	 * @return mixed
	 */
	public function indexAction()
	{
		// default namespace
		$session = new Zend_Session_Namespace('addresshunter');
		
		//$session = null; // for debugging
		try {
			// checking if the user has the OAuth access token
			if (empty($session->accessToken)) {
				Zend_Session::namespaceUnset('addresshunter');
				// if not, redirecting to authenticate action
				return $this->_helper->redirector('authenticate');
			}
			
			// unserializing the access token
			$token = unserialize($session->accessToken); 
			
			// preparing a config array with the OAuth access token and config options
			$config = $this->getOsmConfig();
			$config['oauth']['accessToken'] = $token;
			
			// performing a call to OSM (retrieving user details) to check if the OAuth token
			// is valid (there's no simpler way in OSM to check authentication, afaik)
			$osm = new Addresshunter_Service_Osm($config);
			$result = $osm->user->details();
			
			// if the user is logged in: redirecting to IndexController's index action
			if (!empty($result)) {
				return $this->_helper->redirector('index', 'index');
			}

		} catch (Exception $e) {
			$this->_logger->err('Addresshunter /osm/index error: ' . $e->getMessage());
			Zend_Session::namespaceUnset('addresshunter');
			return $this->_helper->redirector('authenticate');
		}
	}

	/**
	 * AuthenticateAction
	 *
	 * Instantiates an OAuth consumer, asks for a request token and saves it on the session.
	 * Finally, redirects the user to the OAuth provider's website (OSM) for logging in.
	 *
	 * @return mixed
	 */
	public function authenticateAction()
	{
		$config = $this->getOsmConfig();
		
		// instantiate OAuth consumer with config options
		$consumer = new Zend_Oauth_Consumer($config['oauth']);
		
		// storing the request token serialized
		$session = new Zend_Session_Namespace('addresshunter');
		try {
			$session->requestToken = serialize($consumer->getRequestToken());
		} catch (Exception $e) {
			$this->_helper->layout->setLayout('simple');
			return;
		}
		
		// redirecting the user to OSM
		$consumer->redirect();
	}
	
	/**
	 * CallbackAction
	 *
	 * After the user successfully loggs in on the OAuth provider's site and authorizes the access
	 * to his account, he is directed back to this callback action.
	 * This action intantiates again a connection to the OAuth provider and, using the request
	 * token, asks for an access token. This access token is saved on the session instead of the 
	 * request token (which is not needed anymore).
	 * Upon success, the user's OSM account is retrieved and the data is stored/updated in the
	 * database and session. If this is the first login of the user, this account in the DB is created,
	 * otherwise the access token is updated.
	 * At the end the user is redirected to the IndexController's index action.
	 *
	 * @return mixed
	 */
	public function callbackAction()
	{
		$config = $this->getOsmConfig();
		
		// instantiate OAuth consumer with config options
		$consumer = new Zend_Oauth_Consumer($config['oauth']);
		
		$session = new Zend_Session_Namespace('addresshunter');
		
		// making sure the user doens't already have a request token
		if (!empty($_GET) && !empty($session->requestToken)) {
			
			// requesting for the access token
			$token = $consumer->getAccessToken($this->_request->getQuery(), unserialize($session->requestToken));
			
			// storing the access token on a session (serialized)
			$session->accessToken = serialize($token);
			
			// removing the request token (not needed anymore)
			unset($session->requestToken);

			$config['oauth']['accessToken'] = $token;
			// retrieving user details from OSM
			try {
				$osm = new Addresshunter_Service_Osm($config);
				$osmUserDetails = $osm->user->details();
			} catch (Exception $e) {
				unset($session->accessToken);
				Zend_Session::namespaceUnset('addresshunter');
				$this->view->error = "Oops! You have to authorize this app to read your user preferences in order to continue.";
				$this->_helper->layout->setLayout('simple');
				return;
			}
			$userDetails = $osmUserDetails->user;
			
			// retrieving user object from the DB
			if ($user = $this->_em->getRepository('Application_Model_Addresshunter_User')->findOneBy(array('osmId' => $userDetails['id']))) {
				// updating authtoken info
				$user->setAuthToken($token);
			} else {
				// if the user is not yet in the DB, adding it
				$user = new Application_Model_Addresshunter_User;
				$user->setNickname((string)$userDetails['display_name']);
				$user->setAuthToken($token);
				$user->setDateCreated(new DateTime());
				$user->setOsmId((int)$userDetails['id']);
			}
			$this->_em->persist($user);
			$this->_em->flush();

			// saving user details on the session
			$session->nickname = $user->getNickname();
			$session->osmId = $user->getOsmId();

			// not working in Safari unless all cookies are enabled
			Zend_Session::rememberMe();
			
			// redirecting to IndexController's index action
			return $this->_helper->redirector('index', 'index');
		} else {
			Zend_Session::namespaceUnset('addresshunter');
			$this->view->error = 'An unexpected error occured. Please restart the application.';
			$this->_helper->layout->setLayout('simple');
		}
	}
	
	/**
	 * LogoutAction
	 *
	 * Performs a logout by removing the relevant session data (OAuth tokens, etc) and redirects
	 * to the IndexController's index action.
	 *
	 * NOTE: the user is logged out only from the Addresshunter application, not from the OAuth
	 * provider!
	 *
	 * @return mixed
	 */
	public function logoutAction()
	{
		Zend_Session::namespaceUnset('addresshunter');
		
		// not working in Safari unless all cookies are enabled
		Zend_Session::forgetMe();
		
		// redirecting to index action
		return $this->_helper->redirector('index', 'index');
	}
	
	/**
	 * Convenience method for getting OSM config data
	 *
	 * Reads and converts to array the config data related to OSM from the application's config.
	 * Adds the URL for OAuth callback.
	 *
	 * @return array
	 */
	private function getOsmConfig()
	{
		$zendConfig = Zend_Registry::get('Zend_Config');
		// converting to array the OSM configs
		$config = $zendConfig->osm->toArray();
		// adding the absolute URL for the OAuth callback
		$config['oauth']['callbackUrl'] = 
			$this->getRequest()->getScheme() . 
			'://' . 
			$this->getRequest()->getHttpHost() . 
			$this->view->url(array('controller' => 'osm', 'action' => 'callback'));
		return $config;
	}
}
