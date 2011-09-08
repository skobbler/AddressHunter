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

use Doctrine\ORM\EntityManager,
	Doctrine\ORM\Configuration,
	Doctrine\DBAL\Event\Listeners\MysqlSessionInit;

/**
 * Bootstrapper, defines what resources and components to initialize
 *
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause	 New BSD License
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	/**
	 * Initialize Doctype
	 *
	 * @return void
	 */
	protected function _initDoctype()
	{
		$doctypeHelper = new Zend_View_Helper_Doctype();
		$doctypeHelper->doctype('HTML5');
	}

	/**
	 * Initialize Zend Config
	 *
	 * @return void
	 */
	protected function _initConfig()
	{
		$zendConfig = new Zend_Config_Ini(APPLICATION_PATH .
				DIRECTORY_SEPARATOR . 'configs' .
				DIRECTORY_SEPARATOR . 'application.ini', APPLICATION_ENV);
		$this->_config = $zendConfig;
		Zend_Registry::set('Zend_Config', $zendConfig);

		$this->_registry = Zend_Registry::getInstance();
		$this->_registry->logs = $this->_config->logs;
	}

	/**
	 * Initialize Zend Session
	 *
	 * @return void
	 */
	protected function _initSession()
	{
		$zendConfig = Zend_Registry::get('Zend_Config');
		$config = $zendConfig->session->toArray();
		Zend_Session::setOptions($config);
		Zend_Session::start();
	}

	/**
	 * Initialize Tmp dir
	 *
	 * @return void
	 */
	protected function _initTmpDirectory()
	{
		// checking if it's writable
		if (!is_writable($this->_registry->logs->tmpDir)) {
			throw new Exception('Error: tmp dir is not writable (' . $this->_registry->logs->tmpDir . '), check folder/file permissions');
		}
	}

	/**
	 * Initialize Logger
	 *
	 * @return void
	 */
	protected function _initLogger()
	{
		$error_log = $this->_registry->logs->tmpDir . DIRECTORY_SEPARATOR . $this->_registry->logs->error;

		// creating log file if it doesn't exist
		if (!file_exists($error_log)) {
			$date = new Zend_Date;
			file_put_contents($error_log, 'Error log file created on: ' . $date->toString('YYYY-MM-dd HH:mm:ss') . "\n\n");
		}

		// check if it's writable
		if (!is_writable($error_log)) {
			throw new Exception('Error: log file is not writable (' . $error_log . '), check folder/file permissions');
		}

		// creating logger object
		$writer = new Zend_Log_Writer_Stream($error_log);
		$logger = new Zend_Log($writer);

		$this->_registry->logger = $logger;
	}

	/**
	 * Initialize Doctrine
	 *
	 * @return void
	 */
	protected function _initDoctrine()
	{
		// doctrine loader
		require_once (APPLICATION_PATH .
			DIRECTORY_SEPARATOR . '..' .
			DIRECTORY_SEPARATOR . 'library' .
			DIRECTORY_SEPARATOR . 'Doctrine' .
			DIRECTORY_SEPARATOR . 'Common' .
			DIRECTORY_SEPARATOR . 'ClassLoader.php'
		);
		$doctrineAutoloader = new \Doctrine\Common\ClassLoader('Doctrine', APPLICATION_PATH .
				DIRECTORY_SEPARATOR . '..' .
				DIRECTORY_SEPARATOR . 'library'
		);
		$doctrineAutoloader->register();

		// doctrine configuration
		$cache = new $this->_config->doctrine->cacheImplementation;
		$config = new Configuration;
		$config->setMetadataCacheImpl($cache);
		$driverImpl = $config->newDefaultAnnotationDriver(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Addresshunter');
		$config->setMetadataDriverImpl($driverImpl);
		$config->setQueryCacheImpl($cache);
		$config->setProxyDir(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Addresshunter' . DIRECTORY_SEPARATOR . 'Proxies');
		$config->setProxyNamespace('Addresshunter\Proxies');
		$config->setAutoGenerateProxyClasses($this->_config->doctrine->autoGenerateProxyClasses);
		//$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

		// database connection
		$this->_registry->doctrine = new stdClass();
		$this->_registry->doctrine->_em = EntityManager::create($this->_config->doctrine->connection->toArray(), $config);
		$this->_registry->doctrine->_em->getEventManager()->addEventSubscriber(new MysqlSessionInit('utf8', 'utf8_unicode_ci')); // TODO: change, deprecated in Doctrine 2.1 (only PHP 5.3.6)
		$this->_registry->doctrine->_em->getConnection()->exec('SET time_zone = "+00:00"');

		Zend_Registry::set('EntityManager', $this->_registry->doctrine->_em);
	}
}
