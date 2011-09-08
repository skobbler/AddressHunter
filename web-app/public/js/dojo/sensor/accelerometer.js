dojo.require('dojo.sensor');
dojo.provide("dojo.sensor.accelerometer");


/*=====
dojo.sensor.accelerometer = { 
  // summary:
  //    provides an interface for accessing the accelerometer of a given device.
  // LANDSCAPE: Integer
  //	Orientation constant for a device on its side.
  // PORTRAIT: Integer
  // 	Orientaiton constant for a device that is vertical.
};
=====*/


(function(){
	
	// Orientation Constants
	
	// LANDSCAPE: Integer
	//		Orientation constant
	dojo.sensor.accelerometer.LANDSCAPE = 1;
	// PORTRAIT: Integer
	//		Orientation constant
	dojo.sensor.accelerometer.PORTRAIT = 2;
	
	var _orientation = dojo.sensor.accelerometer.PORTRAIT; // default to portrait
	var _orientationCount = 0;// Keeps track of the number of times in a row an orientation is registered
							// used to 'debounce' the orientation change detector.
	
	// Prevents the onShake method from activating after the first acceleration
	var _firstAcceleration = true;

	// Keeps track of the previous acceleration
	var _lastAcceleration = {x:0, y:0, z:0};
	
	var _timer = undefined; // Keeps track of the interval timer for the watch so that it can be cleared later if need be.
	
	var _initShake = false; // Keeps track of whether or not a shake has been initiated. Used to prevent multiple shakes (debounces shake)
	
	var jilWatch = false;  // Bolean value to determine whether a JIL watchAcceleration has been initiated
	var jilClear = false; // Determines whether JIL watchAcceleration was just cleared
	
	var _isFF = undefined; // Keeps track of whether or not the platform is firefox.
	
	var determineOrientation = function(/*Acceleration Object*/ a){
		// summary:
		//		Based on the values passed to it by its parameter, it determines which orientation (Portrait or Landscape)
		//		the device is currently in.  Returns an Integer value in the form of a constant (1=Landscape, 2=Portrait).
		//	a: Acceleration Object
		//		This object contains at least three data members and often times four.  The first three are required and are the
		//		three directional acceleration parameters (x, y, and z).  The fourth variable which is optional is the orientation
		//		constant associated with the three acceleration parameters (1=Landscape, 2=Portrait).
		
		// TODO - Optimize the detection methods

		if( _orientation == dojo.sensor.accelerometer.PORTRAIT ){
			// portrait
			if( ( a.x > 7.5 && a.y < 6 ) || ( a.y < 1.5 && a.x > 3.5 ) ){
				// Orientation is likely portrait
				_orientationCount++;
				
				// Make sure that the device has been in place for _ watch periods to make sure it was intended by the user.
				if( _orientationCount == 2 ){
					_orientation = dojo.sensor.accelerometer.LANDSCAPE;
					_orientationCount = 0;
				}
			}
			 // no change
		}else{
			// Orientation is likely landscape
			
			if( ( a.y > 7.2 && a.x < 6 ) || (a.x < 1.5 && a.y > 3.5) ){
				
				_orientationCount++;
				
				// Make sure that the device has been in place for _ watch periods to make sure it was intended by the user.
				if( _orientationCount == 2 ){
					_orientation = dojo.sensor.accelerometer.PORTRAIT;
					_orientationCount = 0;
				}
			}
			
		}
		
		return _orientation;
	}
	
	var determineShake = function(/*Acceleration Object*/ a){
		// summary:
		//		Uses various algorithmic methods to determine whether the acceleration object passed as a parameter
		//		represents a device on which a shake has occured.
		//	a:Acceleration Object
		//		Represents the current orientation of a device.  Used to determine whether it has been shook.
		
		// Should be true if an orientation change is occuring. Prevents it from being detected as a shake
		// Shake is a rapid change in x, but not y
		var detectOrientChange = Math.abs(a.y - _lastAcceleration.y) > 5;
		
		// Try to determine whether or not the user has been shaking the device. TODO fine tune this
		if( ( a.x > 10 || Math.abs(a.x - _lastAcceleration.x) > 6 ) && _initShake != true && detectOrientChange != true ){

			_initShake = true;
			
			setTimeout(function(){
				_initShake = false;
			},1500); // Prevent further shake operations for a second and a half - TODO test for proper duration	
			
			return true;
		}
		
		return false;
		
	};
	
	var standardizePlatforms = function(/*Acceleration Object*/ a){
		if( dojo.sensor.getPlatform() == dojo.sensor.platforms.PHONE_GAP && // Must be phonegap to use device object
						(device.platform == "iPod touch" || device.platform == "iPhone" || device.platform == "iPad")  ){
			// Translate the iOS values to match with Android/W3C
			a.x *= -10;
			a.y *= -10;
			a.z *= -10;
		}
		
		if( _isFF ){
			var accel = {
				x: a.x,
				y: a.y,
				z: a.z
			}
			a = accel; // Fix readonly glitch of mozilla acceleration object.
			
			a.x *= -10;
			a.y *= 10;
			a.z *= 10;
		}
		
		return a;
	};
	
	dojo.sensor.accelerometer.clearWatch = function(/*Integer*/ watchId){
		// summary:
		//		Attempts to stop the watchAcceleration method with the corresponding watchId.
		// watchId: Integer
		//		Unique identifier for a watchAcceleration watch.
		
		if(watchId){
			clearInterval(watchId);
		}
		
		// Clear all JIL watches
		jilWatch = false;
		jilClear = true;
	}
	
	dojo.sensor.accelerometer.watchAcceleration = function(/*Object*/ callback, /*Object*/ options){
		// summary:
		//		Abstracts the W3C Device Orientation Event spec (http://dev.w3.org/geo/api/spec-source-orientation.html) and attempts to use various
		//		methods depending on the current platform to retrieve the device's current acceleration values and orientation in space. If successful, the callback method
		//		will be repeatedly called until the corresponding clearWatch method is called.
		//	callback: Object
		//			Contains one or more callback functions as properties.
		//		success: Function
		//			function to be called when the acceleration object has successfully been obtained from the device.
		//		error: Function
		//			called when an error occurs
		//		shake: Function
		//			called whenever a shake has been detected by the determineShake private method
		//		orientationChange: Function
		//			called whenever the device switches between orientations (Portrait and Landscape) as determined by the
		//			determineOrientation private method.
		// options: Object
		//			Allows developer to specify the frequency and to request an orientation property be attached to the acceleration object (landscap/portrait)
		// returns:
		//		A unique identifier to be used to stop the watch at a later time.
		
		// Browser Detection.  Accelerometer supported in firefox >=3.6
		var dua = navigator.userAgent;
		_isFF = (parseFloat(dua.split("Firefox/")[1]) || undefined);
		
		if ( (dojo.sensor.getPlatform() == dojo.sensor.platforms.NATIVE || dojo.sensor.getPlatform() == dojo.sensor.platforms.WEBOS) && (!_isFF || _isFF < 3.6) ) {
			error = dojo.sensor.error;
			error.code = error.UNSUPPORTED_FEATURE;
			error.message = "Error: Accelerometer is currently not supported by your native platform. Try Firefox >= 3.6 with a supported device.";
			

			return callback.error(error);
		}else{
			
			if( options != undefined && typeof(options) == "object" ){
				// Set up options object
				var accel_options = {
					getOrientation: false
				};
				
				accel_options.frequency = (options.frequency != undefined)? options.frequency : 1000; // Defaults to 1/10 of a second
				accel_options.getOrientation = (options.getOrientation != undefined)? options.getOrientation : false; // defaults to false
				
				
				// Determine whether the orientationChange callback function has been requested by the programmer
				if( callback.orientationChange != undefined && typeof(callback.orientationChange) == "function" ){
					accel_options.getOrientation = true;
				}

				
			}else{
				accel_options = {};
			}
			
			/* Function to handle, manipulate, and analyze accelerometer data */
			var success = function(a){
				
				a = standardizePlatforms(a);
				
				var blockShake = false;  // Temp value .. goes out of scope
	  			
	  			if( accel_options.getOrientation == true ){
		  			var prevOrientation = _orientation;
		  			 determineOrientation(a); // Sets _orientation to calculated
		  			
		  			a.orientation = _orientation; // Update the acceleration object with the calculated orientation
		  			
		  			if( typeof(callback.orientationChange) == "function"  && _orientation != prevOrientation){
	  					callback.orientationChange(_orientation);
	  					blockShake = true; // Prevent a shake from occuring until the next loop
	  				}
	  			}
	  			
	  			// Determine whether or not a shake has occured
	  			if( typeof(callback.shake) == "function" && _firstAcceleration != true && blockShake != true ){
	  				var shake = determineShake(a);
	  				if( shake == true ){
	  					callback.shake();
	  				}
	  			}
	  			
	  			_firstAcceleration = false;
	  			
	  			
	  			callback.success(a);
	  			
	  			_lastAcceleration = a;
	  			
	  			return;
			}
			
			var err = function(error){
				return callback.error(error);
			}
			
			if( dojo.sensor.getPlatform() == dojo.sensor.platforms.JIL ){
				// JIL implementation of accelerometer
				jilWatch = true;
				
				if( jilClear ){
					jilWatch = false;
					jilClear = false;
					return;
				}
				
				var a = {
					x: Widget.Device.DeviceStateInfo.AccelerometerInfo.xAxis,
					y: Widget.Device.DeviceStateInfo.AccelerometerInfo.yAxis,
					z: Widget.Device.DeviceStateInfo.AccelerometerInfo.zAxis
				}
				
				if( a.x && a.y ){
					success(a);
					if( jilWatch ){
						setTimeout(function(){dojo.sensor.accelerometer.watchAcceleration(callback, options)}, accel_options.frequency);
					}
					return;
				}else{
					error = dojo.sensor.error;
					error.code = error.POSITION_UNAVAILABE;
					error.message = "Error: Unable to retrieve JIL accelerometer data.";
					err(error);
					return;
				}
				// End Jill Implementation
				
			}else if( dojo.sensor.getPlatform() == dojo.sensor.platforms.PHONE_GAP ){
				// PhoneGap implementation of the W3C accelerometer API
		  		_timer = navigator.accelerometer.watchAcceleration(function(a){
		  			
		  			success(a);
		  			
		  			return;
		  
		  		},function(error){
		  			
		  			err(error);
		  			
		  			return ;
		  			
		  		},accel_options);
		  		/* End Phonegap Implementation */
			}else{
				// Attempt to listen to Firefox orientation event
				
				window.addEventListener("MozOrientation", function(a) {
	                   /* 3 values: a.x, a.y, a.y */
					success(a);
	            }, true);

				/*error = dojo.sensor.error;
				error.code = error.UNSUPPORTED_FEATURE;
				error.message = "Error: Accelerometer is currently not supported by this platform.";
				
				err(error);*/
			}
			
		}
	}
	
}
)();