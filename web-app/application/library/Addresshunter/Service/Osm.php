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
 * @see Zend_Rest_Client
 */
require_once 'Zend/Rest/Client.php';

/**
 * @see Zend_Rest_Client_Result
 */
require_once 'Zend/Rest/Client/Result.php';

/**
 * @see Zend_Oauth_Consumer
 */
require_once 'Zend/Oauth/Consumer.php';

/**
 * Rest client for OSM. Inspired from Zend_Service_Twitter.
 *
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause     New BSD License
 */
class Addresshunter_Service_Osm extends Zend_Rest_Client
{
	/**
	 * @var Zend_Http_CookieJar
	 */
	protected $_cookieJar;

	/**
	 * Username
	 *
	 * @var string
	 */
	protected $_username;

	/**
	 * Current method type (for method proxying)
	 *
	 * @var string
	 */
	protected $_methodType;

	/**
	 * Zend_Oauth Consumer
	 *
	 * @var Zend_Oauth_Consumer
	 */
	protected $_oauthConsumer = null;

	/**
	 * Types of API methods
	 *
	 * @var array
	 */
	protected $_methodTypes = array(
		'user',
		'node',
		'changeset'
	);

	/**
	 * Options passed to constructor
	 *
	 * @var array
	 */
	protected $_options = array();

	/**
	 * Local HTTP Client cloned from statically set client
	 *
	 * @var Zend_Http_Client
	 */
	protected $_localHttpClient = null;

	/**
	 * Constructor
	 *
	 * @param  array $options Optional options array
	 * @return void
	 */
	public function __construct($options = null, Zend_Oauth_Consumer $consumer = null)
	{
		if ($options instanceof Zend_Config) {
			$options = $options->toArray();
		}

		if (!is_array($options)) {
			$options = array();
		}

		$this->_options = $options;

		$this->setUri($options['uri']);

		/*if (isset($options['username'])) {
			$this->setUsername($options['username']);
		}*/

		if (isset($options['oauth']['accessToken'])
		&& $options['oauth']['accessToken'] instanceof Zend_Oauth_Token_Access) {
			$this->setLocalHttpClient($options['oauth']['accessToken']->getHttpClient($options['oauth']));
		} else {
			$this->setLocalHttpClient(clone self::getHttpClient());
			if ($consumer === null) {
				$this->_oauthConsumer = new Zend_Oauth_Consumer($options['oauth']);
			} else {
				$this->_oauthConsumer = $consumer;
			}
		}

	}

	/**
	 * Set local HTTP client as distinct from the static HTTP client
	 * as inherited from Zend_Rest_Client.
	 *
	 * @param Zend_Http_Client $client
	 * @return self
	 */
	public function setLocalHttpClient(Zend_Http_Client $client)
	{
		$this->_localHttpClient = $client;
		$this->_localHttpClient->setHeaders('Accept-Charset', 'ISO-8859-1,utf-8');
		return $this;
	}

	/**
	 * Get the local HTTP client as distinct from the static HTTP client
	 * inherited from Zend_Rest_Client
	 *
	 * @return Zend_Http_Client
	 */
	public function getLocalHttpClient()
	{
		return $this->_localHttpClient;
	}

	/**
	 * Checks for an authorised state
	 *
	 * @return bool
	 */
	public function isAuthorised()
	{
		if ($this->getLocalHttpClient() instanceof Zend_Oauth_Client) {
			return true;
		}
		return false;
	}

	/**
	 * Retrieve username
	 *
	 * @return string
	 */
	public function getUsername()
	{
		return $this->_username;
	}

	/**
	 * Set username
	 *
	 * @param  string $value
	 * @return Addresshunter_Service_Osm
	 */
	public function setUsername($value)
	{
		$this->_username = $value;
		return $this;
	}

	/**
	 * Proxy service methods
	 *
	 * @param  string $type
	 * @return Addresshunter_Service_Osm
	 * @throws Addresshunter_Service_Osm_Exception If method not in method types list
	 */
	public function __get($type)
	{
		if (!in_array($type, $this->_methodTypes)) {
			include_once 'Osm/Exception.php';
			throw new Addresshunter_Service_Osm_Exception(
				'Invalid method type "' . $type . '"'
			);
		}
		$this->_methodType = $type;
		return $this;
	}

	/**
	 * Method overloading
	 *
	 * @param  string $method
	 * @param  array $params
	 * @return mixed
	 * @throws Addresshunter_Service_Osm_Exception if unable to find method
	 */
	public function __call($method, $params)
	{
		if (method_exists($this->_oauthConsumer, $method)) {
			$return = call_user_func_array(array($this->_oauthConsumer, $method), $params);
			if ($return instanceof Zend_Oauth_Token_Access) {
				$this->setLocalHttpClient($return->getHttpClient($this->_options));
			}
			return $return;
		}
		if (empty($this->_methodType)) {
			include_once 'Osm/Exception.php';
			throw new Addresshunter_Service_Osm_Exception(
				'Invalid method "' . $method . '"'
			);
		}
		$test = $this->_methodType . ucfirst($method);
		if (!method_exists($this, $test)) {
			include_once 'Osm/Exception.php';
			throw new Addresshunter_Service_Osm_Exception(
				'Invalid method "' . $test . '"'
			);
		}

		return call_user_func_array(array($this, $test), $params);
	}

	/**
	 * Initialize HTTP authentication
	 *
	 * @return void
	 */
	protected function _init()
	{
		if (!$this->isAuthorised() && $this->getUsername() !== null) {
			require_once 'Osm/Exception.php';
			throw new Addresshunter_Service_Osm_Exception(
				'Osm session is unauthorised. You need to initialize '
				. 'Addresshunter_Service_Osm with an OAuth Access Token or use '
				. 'its OAuth functionality to obtain an Access Token before '
				. 'attempting any API actions that require authorisation'
			);
		}
		$client = $this->_localHttpClient;
		$client->resetParameters();
		if (null == $this->_cookieJar) {
			$client->setCookieJar();
			$this->_cookieJar = $client->getCookieJar();
		} else {
			$client->setCookieJar($this->_cookieJar);
		}
	}

	/**
	 * Call a remote REST web service URI and return the Zend_Http_Response object
	 *
	 * @param  string $path			The path to append to the URI
	 * @throws Zend_Rest_Client_Exception
	 * @return void
	 */
	protected function _prepare($path)
	{
		// Get the URI object and configure it
		if (!$this->_uri instanceof Zend_Uri_Http) {
			require_once 'Zend/Rest/Client/Exception.php';
			throw new Zend_Rest_Client_Exception(
				'URI object must be set before performing call'
			);
		}

		$uri = $this->_uri->getUri();

		if ($path[0] != '/' && $uri[strlen($uri) - 1] != '/') {
			$path = '/' . $path;
		}

		$this->_uri->setPath($path);

		/**
		 * Get the HTTP client and configure it for the endpoint URI.
		 * Do this each time because the Zend_Http_Client instance is shared
		 * among all Zend_Service_Abstract subclasses.
		 */
		$this->_localHttpClient->resetParameters()->setUri((string) $this->_uri);
	}

	/**
	 * Performs an HTTP GET request to the $path.
	 *
	 * @param string $path
	 * @param array  $query Array of GET parameters
	 * @throws Zend_Http_Client_Exception
	 * @return Zend_Http_Response
	 */
	protected function _get($path, array $query = null)
	{
		$this->_prepare($path);
		$this->_localHttpClient->setParameterGet($query);
		return $this->_localHttpClient->request(Zend_Http_Client::GET);
	}

	/**
	 * Performs an HTTP POST request to $path.
	 *
	 * @param string $path
	 * @param mixed $data Raw data to send
	 * @throws Zend_Http_Client_Exception
	 * @return Zend_Http_Response
	 */
	protected function _post($path, $data = null)
	{
		$this->_prepare($path);
		return $this->_performPost(Zend_Http_Client::POST, $data);
	}

	protected function _put($path, $data = null)
	{
		$this->_prepare($path);
		return $this->_performPost(Zend_Http_Client::PUT, $data);
	}

	/**
	 * Perform a POST or PUT
	 *
	 * Performs a POST or PUT request. Any data provided is set in the HTTP
	 * client. String data is pushed in as raw POST data; array or object data
	 * is pushed in as POST parameters.
	 *
	 * @param mixed $method
	 * @param mixed $data
	 * @return Zend_Http_Response
	 */
	protected function _performPost($method, $data = null)
	{
		$client = $this->_localHttpClient;

		if (is_string($data)) {
			$client->setRawData($data);
		} elseif (is_array($data) || is_object($data)) {
			$client->setParameterPost((array) $data);
		}

		return $client->request($method);
	}

	/**
	 * Perform a user details request to OSM API v0.6
	 *
	 * Performs a GET request to OSM API v0.6 geting the home location and the
	 * displayname of the user. This operation requires authorization.
	 *
	 * @return Zend_Rest_Client_Result
	 */
	public function userDetails()
	{
		$this->_init();
		$response = $this->_get('/api/0.6/user/details');
		return new Zend_Rest_Client_Result($response->getBody());
	}

	/**
	 * Request to create one open changeset element to OSM API v0.6.
	 *
	 * Performs a PUT request to OSM API v0.6 to create one changeset
	 * including 'created_by' and 'comment' tags.
	 *
	 * @return mixed
	 */
	public function changesetCreate()
	{
		$this->_init();
		$data = '<osm><changeset><tag k="created_by" v="AddressHunter 0.1"/>
								 <tag k="comment" v="AddressHunter - added address including housenumber"/>
				</changeset></osm>';
		$response = $this->_put('/api/0.6/changeset/create', $data);
		return $response->getBody();
	}

	/**
	 * Request to create one node element to OSM API v0.6
	 *
	 * Performs a PUT request to OSM API v0.6  for creating a node element with
	 * address including housenumber. Since this operation has to reference an
	 * open changeset, this call is preceded by a changeset create operation. This
	 * operation requires authorization.
	 *
	 * @param int $changeset_id
	 * @param float $lon
	 * @param float $lat
	 * @param string $city
	 * @param string $street
	 * @param string $housenumber
	 * @param string $postcode
	 *
	 * @return mixed
	 */
	public function nodeCreate($changeset_id, $lon, $lat, $city, $street, $housenumber, $postcode)
	{
		$this->_init();
		$postcode_data = $postcode ? '<tag k=\'addr:postcode\' v=\''.$postcode.'\' />' : '';

		$data = '<osm>
				<node changeset="'.$changeset_id.'" lat="'.$lat.'" lon="'.$lon.'">
					<tag k=\'addr:city\' v=\''.$city.'\' />
					<tag k=\'addr:street\' v=\''.$street.'\' />
					<tag k=\'addr:housenumber\' v=\''.$housenumber.'\' />'.
					$postcode_data.
				'</node>
				</osm>';
		$response = $this->_put('/api/0.6/node/create', $data);
		return $response->getBody();

	}

	/**
	 * Request to close one open changeset element to OSM API v0.6.
	 *
	 * @param int $changeset_id
	 *
	 * @return void
	 */
	public function changesetClose($changeset_id)
	{
		$this->_init();
		$response = $this->_put('/api/0.6/changeset/'.$changeset_id.'/close');
		//TODO: check response
		return;
	}
}
