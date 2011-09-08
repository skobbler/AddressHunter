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
 * Interface for geocoding classes
 */
interface Geo_GeocoderInterface
{
	/**
	 * Static method to perform the geocoding.
	 *
	 * @param string $addressline The addressline to be geocoded
	 * @param array $invokeArgs Any additional invocation arguments
	 * @return Geo_Geolocation|null
	 */
	public static function geocode($addressline, array $invokeArgs = array());
}