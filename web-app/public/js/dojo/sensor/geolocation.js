dojo.require("dojo.sensor");
dojo.provide("dojo.sensor.geolocation");

//dojo.require("dojo.gears");
/*=====
dojo.sensor.geolocation = { 
  // summary:
  //    provides an interface for accessing the geographical location of a user.
};
=====*/

(function(){
	
	var jilWatch = false;  // Bolean value to determine whether a JIL watchPosition has been initiated
	var geolocationWatchId = []; // Keep track of the various watch ids
	
	var handleError = function(/*Object*/ error){
        //alert('An Error Occured: code' + error.code);
		// This function is called in conjunction with an error callback function.  May be overridden and used for additional error handling.
	}
	
	var determineSupport = function(){
		var platform = dojo.sensor.getPlatform();
		var platforms = dojo.sensor.platforms;
		if( platform == platforms.JIL || platform == platforms.BONDI || navigator.geolocation || platform == platforms.WEBOS ){
			// platform is supported
			return true;
		}else{
			// platform is not supported
			return false;
		}
	}
	
	// Direction Constants
	dojo.sensor.geolocation = {
			NORTH: 1,
			NORTH_EAST: 2,
			EAST: 3,
			SOUTH_EAST: 4,
			SOUTH: 5,
			SOUTH_WEST: 6,
			WEST: 7,
			NORT_WEST: 8,
			last_heading: null
	};
	
	
	dojo.sensor.geolocation.clearWatch = function(/*Mixed*/ watchId){
		// summary:
		//		Conforms to the W3C Geolocation spec (http://dev.w3.org/geo/api/spec-source.html). Emulates W3C functionality for other supported platforms.
		//	description:
		//		Stops the watchPosition function with the specified watchId from monitoring changes in the user's location.
		//	watchId: Mixed
		//		Could be either an Integer value (W3C and Bondi) or an object (Palm WebOS). The API will
		//		dynamically determine which has been used and perform the necessary actions accordingly.
		
		// Clear W3C watches
		if(watchId){
			if( dojo.sensor.getPlatform() == dojo.sensor.platforms.BONDI ){
				bondi.geolocation.clearWatch(watchId);
			}else if( dojo.sensor.getPlatform() == dojo.sensor.platforms.WEBOS){
				watchId.cancel(); // Cancel webOS service subscription.
			}else{
				navigator.geolocation.clearWatch(watchId);
			}
		}
		
		
		// Clear all JIL watches
		jilWatch = false;
	}
	
	dojo.sensor.geolocation.watchPosition = function(/*Function*/callback, /*Object*/ options){
		// summary:
		//		Abstracts the W3C Geolocation Spec (http://dev.w3.org/geo/api/spec-source.html) and attempts to use various
		//		methods depending on the current platform to retrieve the device's current location. If successful, the callback method
		//		will be repeatedly called until the corresponding clearWatch method is called.
		//	callback: Object
		//			Contains one or more callback functions as properties.
		//	options: Object
		//		Contains runtime parameters.Any values can be passed with the options object however three common key values are as follows:
		//		enableHighAccuracy, timeout, maximumAge as they are used in the W3C spec. (not all platforms support all 3).
		//
		//		enableHighAccuracy: boolean
		//			Tells the position methods to attempt to retrieve the most accurate position information possible.  This is commonly
		//			disabled when one wants to preserve battery life on mobile devices as a false value on this key will allow
		//			the geolocation method to make use of more energy efficient methods as opposed to GPS.
		//		timeout: Integer
		//			Specifies the maximum ammount of time that the geolocation methods can spend attempting to find
		//			the correct geolocation information before the error_callback function is called.
		//		maximumAge: Integer
		//			Location information is often cached by the browser.  Maximum age allows the programmer to specify how old
		//			cache information is used before it must be replaced by new information.
		
		if (typeof(options) == "object" ) {
			options.watchPosition = true;
	
		}else{
			options = {
				watchPosition: true
			};
		}
		
		if( dojo.sensor.getPlatform() == dojo.sensor.platforms.JIL ){
			jilWatch = true;
		}
		
		// Keep track of the watch_id value for later use.
		var watch_id = dojo.sensor.geolocation.getPosition(callback, options);
		
		geolocationWatchId.push(watch_id);
		
		options.watchPosition = false;
		return watch_id;
	}
	
	dojo.sensor.geolocation.getPosition = function(/*Object*/ callback, /*PositionOptions*/options, /*Position*/ default_position){
	    // summary:
		//		watchPosition utilizes several methods which will monitor a user's geolocation and will fire a callback
		//		any time their position changes. Utilizes the W3C navigator.geolocation interface.
		// callback: Function
		//		The function that will be called when a location is succesfully found and loaded
		//		Callback should take at most two parameters.
		//			position: A position object as outlined by the W3C spec
		//			location_support: A boolean value which indicates whether a position has been found by geolocation(false) or by 
		//				other means(true) such as specifying a default location.
		//		The function that will be called when an error occurs.  Errors can come from two sources: geolocation methods and improper usage
		//	options: Object
		//		This is a javascript object holding various information for use with this function. Any values can be passed
		//		with the options object however three common key values are as follows: enableHighAccuracy, timeout, maximumAge.
		//
		//		enableHighAccuracy: boolean
		//			Tells the position methods to attempt to retrieve the most accurate position information possible.  This is commonly
		//			disabled when one wants to preserve battery life on mobile devices as a false value on this key will allow
		//			the geolocation method to make use of more energy efficient methods as opposed to GPS.
		//		timeout: Integer
		//			Specifies the maximum ammount of time that the geolocation methods can spend attempting to find
		//			the correct geolocation information before the error_callback function is called.
		//		maximumAge: Integer
		//			Location information is often cached by the browser.  Maximum age allows the programmer to specify how old
		//			cache information is used before it must be replaced by new information.
		//		default_position: Position Object
		//			Conforms to the W3C position interface spec. Allows the programmer to specify a default position to be used
		//			when the user's browser does not support geolocation.  If this is not passed and the browser is not supported,
		//			an error will be generated instead.
		
			
	        var location_support;
			
			if( typeof(callback.success) != 'function' ){
				var error = dojo.sensor.error;
				error.code = error.IMPROPER_IMPLEMENTATION;
				error.message = "Error: callback parameter must be a function - geolocation.getPosition()";
				console.error(error.message); // Notify debugger
				callback.success = function() {}; // Ensure that API does not try to perform a call on something that isn't a function
			}
		
			if( typeof(callback.error) != 'function' ){
				callback.error = function() {};
			}
			
			
	        if( determineSupport() ){
	            // Browser is capable of geolocation
				
				// Allows developers to optionally specify several of the options allowed for in
				// the W3C spec.
				// Abritrary key values can also be passed with options.  Unused option values will be
				// ignored.
	            
	            // BONDI FIX
	            if( dojo.sensor.getPlatform() == dojo.sensor.platforms.BONDI && !options.enableHighAccuracy ){
	            	options.enableHighAccuracy = true; // Default to true;
	            }
	            
				if (options) {
					var position_options = {
						enableHighAccuracy: options.enableHighAccuracy, // Boolean
						timeout: options.timeout, // Long
						maximumAge: options.maximumAge, // Long
						getHeading: options.getHeading, // Boolean
						onHeadingChange: options.onHeadingChange, // Callback function for when the heading changes "significantly"
						frequency: options.frequency
					};
				}else{
					// If no options have been specified, initalize the position options to be passed to
					// the geolocation functions to null to ensure proper parameter initilization.
					var position_options = {};
				}
				
				if( dojo.sensor.getPlatform() == dojo.sensor.platforms.WEBOS ){
					// Attempt to translate webOS geolocation parameters to match the W3C spec.
					
					if( !options.responseTime && options.timeout){
						if( options.timeout > 20000 ){
							options.responseTime = 3;
						}else if( options.timeout <= 20000 && options.timeout > 5000 ){
							options.responseTime = 2;
						}else{
							options.responseTime = 1;
						}
					}else{
						options.responseTime = 2; // Default to 5-20 seconds.
					}
					
					if( !options.accuracy ){
						options.accuracy = 2; // Default to 350 meters or less.
					}
					
					if( options.enableHighAccuracy ){
						options.accuracy = 1; // Translate W3C enableHighAccuracy to webOS accuracy
					}
					
					if( options.maximumAge ){
						options.maximumAge = options.maximumAge/1000;  // Convert W3C maximumAge value(milliseconds) to webOS value(seconds)
					}
				}
				// Default success function
				var success = function(position){
					
					if(position_options.getHeading){
				    	position.coords.simpleHeading = Math.round(position.coords.heading/45 + 1);
				    	if( position.coords.simpleHeading >= 9 ){
				    		position.coords.simpleHeading = 1;
				    	}
				    	
				    	if( typeof(position_options.onHeadingChange) == "function" && dojo.sensor.geolocation.last_heading != null 
			    				&& position.coords.simpleHeading != dojo.sensor.geolocation.last_heading){
				    		position_options.onHeadingChange(position.coords.simpleHeading);
				    	}
				    }
					
					callback.success(position); // Callback Function
					
					dojo.sensor.geolocation.last_heading = position.coords.simpleHeading; // Keep track of the last heading for callback function
					
					return;
				};
				
				// Define default error function
				var err = function(error){
					handleError(error);
					
					return callback.error(error); // Error Callback Function
				};
				
				var WebOSGpsSuccess = function(event){
					var position = packageWebOSLocation(event);
					position.assistant = options.assistant; // Add in the webOS assistant
															// so that the callback function may utilize it.
					success(position);
				}
				
				var WebOSGpsFailure = function(event){
					var error = handleWebOSError(event.errorCode);
					err(error); // Error callback
				}
				
				// Determine whether watch or get should be used
				if (options && options.watchPosition) {
					// watchPosition was called.  Implement geolocation.watchPosition
					switch( dojo.sensor.getPlatform() ){
						case dojo.sensor.platforms.JIL:
							Widget.Device.DeviceStateInfo.onPositionRetrieved = function(loc, method){
							
								if( !loc.latitude && !loc.longitude ){
									var error = dojo.sensor.error;
									error.code = error.POSITION_UNAVAILABLE;
									error.message = "Error: Unable to find location."
									return err(error);
								}
								
								var pos = packageJilLocation(loc);
								
	
								success(pos);
	
								if( position_options.frequency == undefined ){
									position_options.frequency = 1000;
								}
								// Timeout
								position_options.watchPosition = true;
								
								if( jilWatch == true ){
									setTimeout('Widget.Device.DeviceStateInfo.requestPositionInfo("gps")', position_options.frequency);
								}
								else{
									// No timeout.  Last update
								}
						
							};
							Widget.Device.DeviceStateInfo.requestPositionInfo("gps");
						
						break;
						case dojo.sensor.platforms.BONDI:
							var watch_id = bondi.geolocation.watchPosition(success, err, position_options);
						break;
						case dojo.sensor.platforms.WEBOS:
							// Make sure that the webOS assistant was passed as it is necessary.
							if( !options.assistant ){
								error = dojo.sensor.error;
								error.code = error.IMPROPER_IMPLEMENTATION;
								error.message = "Error: webOS requires that you pass the current assistant object as a property of the options parameter.";
								
								handleError(error);
								return callback.error(error);
							}
							
							
							
							var watch_id = options.assistant.controller.serviceRequest('palm://com.palm.location', {
							    method:"startTracking",
							    parameters:{"subscribe": true},
							    onSuccess:WebOSGpsSuccess.bind(options.assistant),
							    onFailure:WebOSGpsFailure.bind(options.assistant)
							    }
							);

						break;
						default:
							// W3C Implementation
							var watch_id = navigator.geolocation.watchPosition(success, err, position_options);
					    break;
					}
					return watch_id;
				}else {
					// watchPosition was not called.  Implement geolocation.getCurrentPosition
					
					switch( dojo.sensor.getPlatform() ){
						case dojo.sensor.platforms.JIL:
								Widget.Device.DeviceStateInfo.onPositionRetrieved = function(loc, method){
								
								/* JIL error handling */
								if( !loc.latitude && !loc.longitude ){
									var error = dojo.sensor.error;
									error.code = error.POSITION_UNAVAILABLE;
									error.message = "Error: Unable to find location.";
									return err(error);
								}
								
								// Convert JIL locationInfo into W3C position object.
								var pos = packageJilLocation(locs);
								
								// success callback function
								success(pos);
							};
							
							Widget.Device.DeviceStateInfo.requestPositionInfo("gps");
						
						break;
						case dojo.sensor.platforms.BONDI:
							bondi.geolocation.getCurrentPosition(success, err, position_options);
						break;
						case dojo.sensor.platforms.WEBOS:
							// Make sure that the webOS assistant was passed as it is necessary.
							if( !options.assistant ){
								error = dojo.sensor.error;
								error.code = error.IMPROPER_IMPLEMENTATION;
								error.message = "Error: webOS requires that you pass the current assistant object as a property of the options parameter.";
								
								handleError(error);
								return callback.error(error);
							}
							
							
							
							options.assistant.controller.serviceRequest('palm://com.palm.location', {
							    method:"getCurrentPosition",
							    parameters:{},
							    onSuccess:WebOSGpsSuccess.bind(options.assistant),
							    onFailure:WebOSGpsFailure.bind(options.assistant)
							    }
							);
							options.assistant.controller.get("app-title").update('fail');
							
							/*options.assistant.controller.get("app-id").update('testing' + callback.success);
							callback.success(options.assistant);
							options.assistant.controller.get("app-id").update('done');*/
						break;
						default:
							// W3C Implementation
							navigator.geolocation.getCurrentPosition(success, err, position_options);
					    break;
					}
				}
				
	        }else if( false ){//dojo.gears.available ){ // google.gears ){
	            // Try Google Gears - Fails in Safari... find workaround
				alert('using Gears - TODO implement gears support');
	            // TODO - Implement Gears support
	        }else{
	          
	            // Browser is incapable of geolocation...
				
				if( options.watchPosition ){
					error = dojo.sensor.error;
					error.code = error.UNSUPPORTED_FEATURE;
					error.message = "Error: watchPosition requires a compatible browser. Default location not supported.";
					
					handleError(error);
					return callback.error(error);
				}
				
				if ( default_position ) {
					
					// Set default coordinates for graceful degredation
					
					/* If a default position was passed, load it into a valid position
					 * object and return it to the callback function.
					 */
					var coords = default_position.coords;
					
					var pos = {
						coords: {
							latitude: coords.latitude,
							longitude: coords.longitude,
							altitude: coords.altitude,
							accuracy: coords.accuracy,
							altitude_accuracy: coords.altitude_accuracy,
							heading: coords.heading, // North
							speed: coords.speed // Not moving
						},
						timestamp: default_position.timestamp
					};
					
					callback.success(pos); // Pass true as the second parameter to indicate that the a default location has been loaded.
				}
				else{
					// If no default coordinates were found return with an error
					
					var error = dojo.sensor.error;
					error.code = error.POSITION_UNAVAILABLE;
					error.message = "Error: browser does not support geolocation and no default position was specified."
					
					// Handle Error
					handleError(error);
					return callback.error(error);
				}
	        }
	    }
	
	function packageGenericLocation(/*Generic Info*/ loc){
		// Package a generic location object that doesn't use the W3C coord property.
		var pos = {
				coords: {
					latitude: loc.latitude,
					longitude: loc.longitude,
					altitude: loc.altitude,
					accuracy: loc.accuracy,
					altitudeAccuracy: loc.altitudeAccuracy,
					heading: loc.heading, // North
					speed: loc.speed // Not moving
				},
				timestamp: loc.timestamp
			};
		return pos;
		
	}
	
	function packageJilLocation(/*JIL locationInfo*/ loc){
		// summary: packages a JIL locationInfo object into a W3C position object
		
		var pos = packageGenericLocation(loc);
		return pos;
		
	}
	
	function packageWebOSLocation(/*WebOS Location Information*/ loc){
		var pos = packageGenericLocation(loc);
		
		// Package WebOS specific properties
		pos.coords.speed = loc.velocity;
		pos.coords.horizAccuracy = loc.horizAccuracy;
		pos.coords.vertAccuracy = loc.vertAccuracy;
		
		return pos;
	}
	
	function handleWebOSError(/*Integer*/ errorCode){
		var error = dojo.sensor.error;
		
		if( errorCode == 0 ){
			return false; // No error
		}
		
		var codes = [true, {'code': error.POSITION_UNAVAILABLE, 'message': 'Error: The application timed out waiting for the position.'},
		             	   {'code': error.POSITION_UNAVAILABLE, 'message': 'Error: The current position is unavailable.'},
		             	   {'code': error.APPLICATION_ERROR, 'message': 'Error: An unknown error has occured.'},
		             	   {'code': error.POSITION_UNAVAILABLE, 'message': 'Error: GPS is permantently unavailable.'},
		             	   {'code': error.DEVICE_ERROR, 'message': 'Error: No Location source available. Both Google and GPS are off.'},
		             	   {'code': error.PERMISSION_DENIED, 'message': 'Error: The user has not accepted the terms of use for the Google Location Service, or the Google Service is off.'},
		             	   {'code': error.APPLICATION_ERROR, 'message': 'Error: A pending location request already exists.'},
		             	   {'code': error.APPLICATION_ERROR, 'message': 'Error: The application has been temporarily blacklisted.'}];
		
		// Validate errorCode
		if( errorCode < 0 && errorCode < 8 ){
			var webOSErr = codes[errorCode];
			
			error.code = webOSErr.code;
			error.message = webOSErr.message;
		}else{
			error.code = error.IMPROPER_IMPLEMENTATION;
			error.message = "Error: Invalid webOS error code.  Must be between 1 and 8 inclusive.";
		}
		
		return error;            
	}
	    
})();