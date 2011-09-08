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
 *
 * AboutController
 *
 * This controller handles a standalone 'About' page which currently serves to be linked by
 * 3rd parties (ex. in the OSM OAuth process). As content, it is the same as the game's About
 * screen generated in InxedController (they also resemble visually).
 *
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause	 New BSD License
 */
class AboutController extends Zend_Controller_Action
{
	public function init()
	{
	}

	public function indexAction()
	{
		$this->_helper->layout->setLayout('simple');

		// adding version info to be displayes
		$zendConfig = Zend_Registry::get('Zend_Config');
		$this->view->version = $zendConfig->version->toArray();
		$this->view->version['osm'] = $zendConfig->osm->uri;
	}
}
