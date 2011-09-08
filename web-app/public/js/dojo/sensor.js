dojo.provide('dojo.sensor');

dojo.sensor = {
		platforms: {
			NATIVE: 1,
			PHONE_GAP: 2,
			JIL: 3,
			BONDI: 4,
			WEBOS: 5
		},
		error: {
			PERMISSION_DENIED: 1,
			POSITION_UNAVAILABLE: 2,
			UNSUPPORTED_FEATURE: 3,
			IMPROPER_IMPLEMENTATION: 4,
			APPLICATION_ERROR: 5,
			DEVICE_ERROR: 6,
			code: 0, // Individual Error Code
			message: "" // Error message for debugging
		},
		getPlatform: function(){
			if( this._platform )
				return this._platform;
		},
		isLoaded: function(){
			if( this._loaded )
				return this._loaded;
			else
				return false;
		}
	};

	dojo.sensor._platform = dojo.sensor.platforms.NATIVE; // Defaults to native
	
	
	dojo.sensor.handleJilException = function(/*Exception*/ e){
		// summary: This function attempts to provide a 1:1 conversion between JIL exceptions and the dojo.sensor
		//			error handling methods.  Returns a dojo.sensor.error object.
		// e: Exception
		//		JIL exception

		if( dojo.sensor.getPlatform() == dojo.sensor.platforms.JIL && e instanceof Widget.Exception ){
			var error = dojo.sensor.error;
			
			if (e.type == Widget.ExceptionTypes.INVALID_PARAMETER) {
				error.code = error.IMPROPER_IMPLEMENTATION;
			}else if (e.type == Widget.ExceptionTypes.SECURITY) {
				error.code = error.PERMISSION_DENIED;
			}else if (e.type == Widget.ExceptionTypes.UNKNOWN) {
				error.code = error.APPLICATION_ERROR;
			}else if (e.type == Widget.ExceptionTypes.UNSUPPORTED) {
				error.code = error.UNSUPPORTED_FEATURE;
			}else{
				error.code = error.IMPROPER_IMPLEMENTATION;
			}
			
			error.message = e.message;
			
			return e;
		}else{
			return false;
		}
	}
	
	dojo.addOnLoad(function(){
		dojo.sensor._loaded = true;
		
		if( typeof(PhoneGap) == "object" ){
			dojo.sensor._platform = dojo.sensor.platforms.PHONE_GAP;
		}else if( typeof(Widget) == "object" ){
			dojo.sensor._platform = dojo.sensor.platforms.JIL;
		}else if( typeof(bondi) == "object" ){
			dojo.sensor._platform = dojo.sensor.platforms.BONDI;
		}else if( typeof(Mojo) == "object" ){
			dojo.sensor._platform = dojo.sensor.platforms.WEBOS;
		}else{
			// No change, defaults to native.
		}

	});