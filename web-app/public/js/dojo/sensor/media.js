
dojo.provide("dojo.sensor.media");

/*=====
dojo.sensor.accelerometer = { 
	// summary: The media module attempts to provide a level of abstraction for developers between their applications and the media capture capabilities
	//		of devices on a variety of platforms.
};
=====*/


// Define error constants
	// DO nothing, platform should be supported.
	// TODO: Keep this updated as more platforms are added.
	(function(){
		
		dojo.sensor.media = {
			// summary: The media module attempts to provide a level of abstraction for developers between their applications and the media capture capabilities
			//		of devices on a variety of platforms.
		};
		
		dojo.sensor.media.captureImage = function(callback, options){
			// summary: Attempts to implement the W3C Media Capture API (http://dev.w3.org/2009/dap/camera/Overview-API.html) 
			//		method of the same name across several platforms.
			// callback: Object
			//		Contains one or more callback functions as properties.
			//		success: Function
			//			function to be called when the image data has successfully been obtained from the device.
			//		error: Function
			//			called when an error occurs
			// options: Object
			//		Allows developer to specify several runtime parameters.  Common properties include: quality and maxNumberOfMediaFiles
			
			if( dojo.sensor.isLoaded() && dojo.sensor.getPlatform() == dojo.sensor.platforms.NATIVE ){
				error = dojo.sensor.error;
				error.code = error.UNSUPPORTED_FEATURE;
				error.message = "Error: Camera is currently not supported on any native platforms.";
			}
			
			// START OPTIONS
			
			if( !options || typeof(options) != "object" ){
				options = {};
			}
			
			if( options.quality == undefined ){
				options.quality = 50;
			}
			
			if( options.maxNumberOfMediaFiles == undefined ){
				options.maxNumberOfMediaFiles = 1;
			}
			
			// END OPTIONS
			
			// START CAPTURE
			
			if( dojo.sensor.getPlatform() == dojo.sensor.platforms.PHONE_GAP ){
				navigator.camera.getPicture(function(data){
					
					if( options.saveToDisk != undefined ){
						// Save to disk
						
					}
					
					callback.success(data);
				}, function(error){
					callback.error(error);
				}, { quality: options.quality });
			}else if( dojo.sensor.getPlatform() == dojo.sensor.platforms.JIL ){
				// Capture image with JIL
				
				try{
					var path = Widget.Multimedia.Camera.captureImage("jil.jpg", false);
					
					Widget.Multimedia.Camera.onCameraCaptured = function(fileName){
						var img = new Image();
						img.src = fileName;
						var data = getBase64Image(img);
						return data;
					}
				}catch(e){
					console.log(e);
					console.log(Widget.Exception)
					var error = dojo.sensor.handleJilException(e);
					console.log(error.code + " " + error.message);
				}
				
				
			}else{
				// Attempt a native method ( not currently supported by any known native platform )
				navigator.device.captureImage(function(data){
					callback.success(data);
				},function(error){	
					callback.error(error);
				},
				options);
			}
		}
		
		dojo.sensor.media.captureVideo = function(callback, options){
			// summary: (UNSUPPORTED) Attempts to implement the W3C Media Capture API (http://dev.w3.org/2009/dap/camera/Overview-API.html) 
			//		method of the same name across several platforms.
			// callback: Object
			//		Contains one or more callback functions as properties.
			//		success: Function
			//			function to be called when the video data has successfully been obtained from the device.
			//		error: Function
			//			called when an error occurs
			// options: Object
			//		Allows developer to specify several runtime parameters.  Common properties include: quality and maxNumberOfMediaFiles
			error = dojo.sensor.error;
			error.code = error.UNSUPPORTED_FEATURE;
			error.message = "Error: Function not yet supported.";
		}
		
		dojo.sensor.media.captureAudio = function(callback, options){
			// summary: (UNSUPPORTED) Attempts to implement the W3C Media Capture API (http://dev.w3.org/2009/dap/camera/Overview-API.html) 
			//		method of the same name across several platforms.
			// callback: Object
			//		Contains one or more callback functions as properties.
			//		success: Function
			//			function to be called when the audio data has successfully been obtained from the device.
			//		error: Function
			//			called when an error occurs
			// options: Object
			//		Allows developer to specify several runtime parameters.  Common properties include: quality and maxNumberOfMediaFiles
			error = dojo.sensor.error;
			error.code = error.UNSUPPORTED_FEATURE;
			error.message = "Error: Function not yet supported.";
		}
		
		dojo.sensor.media.getViewFinder = function(callback, options){
			// summary: (UNSUPPORTED) Attempts to implement the W3C Media Capture API (http://dev.w3.org/2009/dap/camera/Overview-API.html) 
			//		method of the same name across several platforms.
			// callback: Object
			//		Contains one or more callback functions as properties.
			//		success: Function
			//			Not supported
			//		error: Function
			//			Not supported
			// options: Object
			//		Allows developer to specify several runtime parameters.  Common properties include: quality and maxNumberOfMediaFiles
			error = dojo.sensor.error;
			error.code = error.UNSUPPORTED_FEATURE;
			error.message = "Error: Function not yet supported";
		}
		
		// Courtesy of http://stackoverflow.com/questions/934012/get-image-data-in-javascript
		function getBase64Image(img) {
		    // Create an empty canvas element
		    var canvas = document.createElement("canvas");
		    canvas.width = img.width;
		    canvas.height = img.height;

		    // Copy the image contents to the canvas
		    var ctx = canvas.getContext("2d");
		    ctx.drawImage(img, 0, 0);

		    // Get the data-URL formatted image
		    // Firefox supports PNG and JPEG. You could check img.src to guess the
		    // original format, but be aware the using "image/jpg" will re-encode the image.
		    var dataURL = canvas.toDataURL("image/png");

		    return dataURL.replace(/^data:image\/(png|jpg);base64,/, "");
		}
		
	})();

