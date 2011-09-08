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
 * Utilitary class for geographical calculations.
 *
 * TODO: for more accuracy refactor using BCMath functions
 *
 * @copyright Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com)
 * @license   http://www.opensource.org/licenses/BSD-3-Clause	 New BSD License
 */
class Geo_GeoUtil
{
	/** The Earth radius (mean value in kilometers) */
	const EARTH_RADIUS = 6367;

	/**
	 * Computes a BoundingBox around the given geographical point.
	 *
	 * A bounding box is a rectangle on the map, built in such a way that it is
	 * symetrical at least relatively to the x axis. So it can be a square, a
	 * rectangle, an isosceles trapezoid, etc.
	 * The given point will act as an origin and will be the center of the
	 * bounding box, which will span on each axis by the given distance.
	 *
	 * @param double $x0 the X coordinate of the origin
	 * @param double $y0 the Y coordinate of the origin
	 * @param double $distance the distance in kilometers on each axis from the origin point to the margin of the bounding box
	 * @return array
	 */
	public static function getBoundingBox($x0, $y0, $distance)
	{
		if ($y0 < -90 || $y0 > 90 || $x0 < -180 || $x0 > 180) {
			throw new Exception("x must be in the range (-180, 180) and y must be in the range (-90, 90)");
		}
		$safety = self::distance(0, 90, 0, abs($y0));
		if ($safety < 2 * $distance) {
			throw new Exception("too close to the pole or distance too big");
		}
		// convert from degrees to radians
		(float)$x = self::deg2rad($x0);
		(float)$y = self::deg2rad($y0);
		// calculate the deviation based on the latitude (y)
		(float)$delta = $distance / self::EARTH_RADIUS;
		(float)$xdev = $delta / cos($y);
		// calculate the corner values
		(float)$xmin = $x - $xdev;
		(float)$ymin = $y - $delta;
		(float)$xmax = $x + $xdev;
		(float)$ymax = $y + $delta;
		// convert from radians to degrees
		$xmin = self::rad2deg($xmin);
		$ymin = self::rad2deg($ymin);
		$xmax = self::rad2deg($xmax);
		$ymax = self::rad2deg($ymax);
		return array('xmax' => $xmax, 'xmin' => $xmin, 'ymax' => $ymax, 'ymin' => $ymin);
	}

	/**
	 * Computes the geographical distance in kilometres between two points.
	 *
	 * @param double $x1 the X coordinate of the first geographical point
	 * @param double $y1 the Y coordinate of the first geographical point
	 * @param double $x2 the X coordinate of the second geographical point
	 * @param double $y2 the Y coordinate of the second geographical point
	 * @return double the distance in kilometres between the two points
	 */
	public static function distance($x1, $y1, $x2, $y2)
	{
		if ($x1 == $x2 && $y1 == $y2) {
			$dist = 0;
		} else {
			$theta = $x1 - $x2;
			$sy1 = sin(self::deg2rad($y1));
			$sy2 = sin(self::deg2rad($y2));
			$cy1 = cos(self::deg2rad($y1));
			$cy2 = cos(self::deg2rad($y2));
			$cth = cos(self::deg2rad($theta));
			$res = $sy1 * $sy2 + $cy1 * $cy2 * $cth;
			$dist = (float)$res;
			$dist = acos($dist);
			$dist = self::rad2deg($dist);
			$dist = $dist * 60 * 1.1515 * 1.609344;
		}
		return $dist;
	}

	/**
	 * Converts the degrees to radians.
	 *
	 * @param double $deg the degrees to be converted
	 * @return double the radians
	 */
	private static function deg2rad($deg)
	{
		return ($deg * pi() / 180.0);
	}

	/**
	 * Converts the radians to degrees.
	 *
	 * @param double $rad the radians to be converted
	 * @return double the degrees
	 */
	private static function rad2deg($rad)
	{
		return ($rad * 180 / pi());
	}
}
