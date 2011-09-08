/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.AddressfindView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.AddressfindView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {
	
	serviceUrl: "/game/findaddress",
	serviceUrl2: "/game/photoupload",
	
	photoData: null,
	
	buttonLocked: false,

	posX: null,
	posY: null,
	
	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
		// Add a click handler to the photo capture button
		var photoCaptureButton = dijit.byId(this.getElements("photo_capture_btn", this.domNode)[0].id);
		dojo.connect(photoCaptureButton, "onClick", this, "capturePhoto");
		// Add a click handler to the submit button
		var submitButton = dijit.byId(this.getElements("address_find_btn", this.domNode)[0].id);
		dojo.connect(submitButton, "onClick", this, "submitAddress");
	},
	
	onBeforeTransitionIn: function(moveTo, dir, transition, context, method) {
		this.posX = null;
		this.posY = null;
		this.buttonLocked = false;
		this.clearAndHidePhoto();
		this.hideSubmitButton();
	},
	
	onAfterTransitionIn: function(moveTo, dir, transition, context, method) {
		dojo.byId("founded_address").innerHTML = addresshunter.USER.lastAddrName;
	},
	
	onAfterTransitionOut: function(moveTo, dir, transition, context, method) {
		this.posX = null;
		this.posY = null;
		this.buttonLocked = false;
		this.clearAndHidePhoto();
		this.hideSubmitButton();
		this.hideLoadingSpinner();
	},
	
	capturePhoto: function() {
		// validating users position
		var now = new Date();
		var posAge = (now.getTime() - addresshunter.USER.posDate.getTime()) / (1000 * 60); // minutes

		if (addresshunter.USER.posX && addresshunter.USER.posY && posAge <= addresshunter.USER.posTimeout) {
			this.posX = addresshunter.USER.posX;
			this.posY = addresshunter.USER.posY;
		}
		
		// >>>>>>>>>>>>>>>>>>>>>>>>>> for develeopment only to bypass photo capturing on a real device
		/*data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABlBMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDrEX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
		this.photoData = data;
		dojo.byId("photo").src = "data:image/jpeg;base64," + data;
		dojo.style("photo", "visibility", "visible");
		this.showSubmitButton();
		return;*/
		// >>>>>>>>>>>>>>>>>>>>>>>>>>

		dojo.sensor.media.captureImage({
			success: dojo.hitch(this, function(data) {
				// storing photo data
				this.photoData = data;
				// displaying image preview
				dojo.byId("photo").src = "data:image/jpeg;base64," + data;
				dojo.style("photo", "visibility", "visible");
				
				// validating users position
				posAge = (now.getTime() - addresshunter.USER.posDate.getTime()) / (1000 * 60); // minutes
				if (addresshunter.USER.posX  && addresshunter.USER.posY && posAge <= addresshunter.USER.posTimeout) {
					this.posX = addresshunter.USER.posX;
					this.posY = addresshunter.USER.posY;
				}
				
				// displaying the submit button
				this.showSubmitButton();
			}),
			error: function(error) {
				alert('Unable to retrieve image data.');
			}
		},
		{quality:5});
	},
	
	submitAddress: function(e) {
		dojo.stopEvent(e);
		
		if (this.buttonLocked) {
			return;
		}
		
		this.buttonLocked = true;
		this.showLoadingSpinner();

		// try to use gps pos from the capturePhoto
		if (!(this.posX && this.posY)) {
			// use current user gps pos
			// validating users position
			var now = new Date();
			var posAge = (now.getTime() - addresshunter.USER.posDate.getTime()) / (1000 * 60); // minutes

			if (!addresshunter.USER.posX || !addresshunter.USER.posY || posAge > addresshunter.USER.posTimeout) {
				alert("Your GPS position is outdated (older than a minute) or cannot be retrieved");
				this.buttonLocked = false;
				this.hideLoadingSpinner();
				return;
			}
			this.posX = addresshunter.USER.posX;
			this.posY = addresshunter.USER.posY;
		}
		
		var xhrArgs = {
			url: addresshunter.serverBase + this.serviceUrl,
			handleAs: "json",
			preventCache: true,
			content: {
				gameaddress_id: addresshunter.USER.lastAddrId,
				posX: this.posX,
				posY: this.posY
			},
			load: dojo.hitch(this, function(response, ioArgs) {
				if (response.status != 200) {
					this.hideLoadingSpinner();
					this.handleError(response.status);
					this.buttonLocked = false;
					if (response.status != 401) {
						dijit.byId("addressfind_mp").performTransition('#playing_map', 1, "slide", null);
					}
					return;
				}

				// TODO: decide if diffrent response is needed in those cases
				if (response.data.status != 'uploaded' && response.data.status != 'uploaded_before') {
					alert("+1 experience points, but the address was not loaded to OSM. Next time you login, please authorize access to modify the map.");
				}
				addresshunter.USER.points = response.data.points;
				addresshunter.GAME.user_points = response.data.g_points;

				// start uploading the photo
				this.uploadPhoto();
				
				this.hideLoadingSpinner();
				this.hideSubmitButton();
				
				// animation to the next screen (parameters: viewName, direction (1 = forwards, -1 = reverse), transitionType and callback function)
				dijit.byId("addressfind_mp").performTransition('#playing_map', 1, "slide", null);
			}),
			error: dojo.hitch(this, function(error, ioargs) {
				this.handleError(ioargs.xhr.status);
				this.buttonLocked = false;
				this.hideLoadingSpinner();
				this.clearAndHidePhoto();
				this.hideSubmitButton();
				dijit.byId("addressfind_mp").performTransition('#playing_map', 1, "slide", null);
			})
		}

		// Call the asynchronous xhrPost
		var deferred = dojo.xhrPost(xhrArgs);
	},
	
	uploadPhoto: function() {		
		var xhrArgs1 = {
			url: addresshunter.serverBase + this.serviceUrl2,
			handleAs: "json",
			preventCache: true,
			headers: {
				"Content-Type": "application/x-www-form-urlencoded",
				"Content-Encoding": "UTF-8"
			},
			content: {
				gameaddress_id: addresshunter.USER.lastAddrId,
				photo: this.photoData
			},
			load: dojo.hitch(this, function(response, ioArgs) {
				if (response.status != 200) {
					// alert("Error uploading the photo.");
				} else {
					// alert('Photo uploaded successfully.');
				}
				this.clearAndHidePhoto();
			}),
			error: dojo.hitch(this, function(error, ioargs) {
				this.handleError(ioargs.xhr.status);
				this.clearAndHidePhoto();
			})
		}

		var deferred1 = dojo.xhrPost(xhrArgs1);
	},
	
	clearAndHidePhoto: function() {
		this.photoData = null;
		dojo.byId("photo").src = "";
		dojo.style("photo", "visibility", "hidden");
	},
	
	showSubmitButton: function() {
		dojo.style("address_find_btn", "visibility", "visible");
	},
	
	hideSubmitButton: function() {
		dojo.style("address_find_btn", "visibility", "hidden");
	}
	
});