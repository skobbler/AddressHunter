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
 * Init file to be included in all cronjob scripts
 *
 * It's a modified ZendFramework boostrap that makes available all application resources and
 * features except for the MVC part (which is not needed since the cronjobs are run in CLI).
 */

// for benchmarking
if (defined('BENCHMARK') && BENCHMARK) {
	$time = microtime(true);
	$memory = memory_get_usage();
}

// setting the working directory (important, not done by default in CLI)
chdir(dirname(__FILE__));

// setting timezone and charset
date_default_timezone_set('UTC');
mb_internal_encoding("UTF-8");

// define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'development'));

// ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

// Zend_Application
require_once 'Zend/Application.php';

// create application and bootstrap
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
$application->bootstrap();

// for benchmarking
if (defined('BENCHMARK') && BENCHMARK) {
	register_shutdown_function('__shutdown');
}
function __shutdown() {
	global $time, $memory;
	$endTime = microtime(true);
	$endMemory = memory_get_usage();
	echo "\n" . 'Time [' . ($endTime - $time) . '] Memory [' . number_format(( $endMemory - $memory) / 1024) . 'Kb]' . "\n";
}