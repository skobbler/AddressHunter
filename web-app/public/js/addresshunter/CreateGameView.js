/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.CreateGameView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.CreateGameView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {
	
	// Create a template string for the games:
	gameTemplateString: '${name}',
	
	serviceUrl: "/game/create",
	
	buttonLocked: false,
	
	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
		
		// Add a click handler to the button
		var createGameButton = dijit.byId(this.getElements("create_game_btn", this.domNode)[0].id);
		dojo.connect(createGameButton, "onClick", this, "submitGame");

		for(var i=0; i<=7; i++)
		{
			var incrementButton = dijit.byId(this.getElements("increment", this.domNode)[i].id);
			dojo.connect(incrementButton, "onClick", incrementButton, function(e) {
				
				dojo.stopEvent(e);
				
				var id_arr = this.id.split("_");
				var direction = id_arr[1];
				var target_id = id_arr[0];
				var min = 1;
				var max = 50;
				var step = 1;

				if (target_id == 'gPlayersNo') {
					min = 2;
					max = 50;
				}

				if (target_id == 'gAddrNo') {
					min = 1;
					max = 20;
				}

				if (target_id == 'gRadius') {
					min = 0.5;
					max = 5;
					step = 0.5;
				}

				if (target_id == 'gTimeframe') {
					min = 1;
					max = 24;
				}

				if (direction == 'up') {
					if (dojo.byId(target_id).value < max)
						dojo.byId(target_id).value = parseFloat(dojo.byId(target_id).value) + step;

					if (dojo.byId(target_id).value >= max) {
						dojo.style(this.id + "_span", "opacity", "0.5");
					}
					if (dojo.byId(target_id).value > min)
						dojo.style(target_id+"_down_span", "opacity", 1);
				}

				if (direction == 'down') {
					if (dojo.byId(target_id).value > min)
						dojo.byId(target_id).value = parseFloat(dojo.byId(target_id).value) - step;

					if (dojo.byId(target_id).value <= min) {
						dojo.style(this.id + "_span", "opacity", "0.5");
					}

					if (dojo.byId(target_id).value < max) {
						dojo.style(target_id + "_up_span", "opacity", 1);
					}		
				}
			});
		}
	},

	onAfterTransitionIn: function(moveTo, dir, transition, context, method) {
		this.buttonLocked = false;
		this.hideLoadingSpinner();
	},

	onAfterTransitionOut: function(moveTo, dir, transition, context, method) {
		this.buttonLocked = false;
	},
	
	submitGame: function(e) {
		dojo.stopEvent(e);
		
		if (this.buttonLocked) {
			return;
		}
		
		this.buttonLocked = true;
		this.showLoadingSpinner();
		
		// validating users position
		var now = new Date();
		var posAge = (now.getTime() - addresshunter.USER.posDate.getTime()) / (1000 * 60); // minutes
		
		if (!addresshunter.USER.posX || !addresshunter.USER.posY || posAge > addresshunter.USER.posTimeout) {
			this.hideLoadingSpinner();
			alert("Your GPS position is outdated (older than a minute) or cannot be retrieved");
			this.buttonLocked = false;
			return;
		}
		
		var xhrArgs = {
			url: addresshunter.serverBase + this.serviceUrl,
			handleAs: "json",
			preventCache: true,
			content: {
				gName: dojo.byId("gName").value,
				gPlayersNo: dojo.byId("gPlayersNo").value,
				gAddrNo: dojo.byId("gAddrNo").value,
				gRadius: dojo.byId("gRadius").value,
				gTimeframe: dojo.byId("gTimeframe").value,
				posX: addresshunter.USER.posX,
				posY: addresshunter.USER.posY
			},
			load: dojo.hitch(this, function(response, ioArgs) {
				if (response.status != 200) {
					this.handleError(response.status);
					this.buttonLocked = false;
					this.hideLoadingSpinner();
					return;
				}
				// saving the game data in the namespace
				addresshunter.GAME.id = response.data.id;
				addresshunter.GAME.name = response.data.name;
				addresshunter.GAME.user_points = 0;
				addresshunter.GAME.is_map_locked = true;
				dojo.forEach(this.getElements("playing_game_name"), function(element) {
					element.innerHTML = addresshunter.GAME.name;
				});
				// animation to the next screen (parameters: viewName, direction (1 = forwards, -1 = reverse), transitionType and callback function)
				this.hideLoadingSpinner();
				dijit.byId('create_game_mp').performTransition('#waiting_join', 1, "slide", null);
			}), 
			error: dojo.hitch(this, function(error, ioargs) {
				this.handleError(ioargs.xhr.status);
				this.buttonLocked = false;
				this.hideLoadingSpinner();
			})
		}
		// Call the asynchronous xhrPost
		var deferred = dojo.xhrPost(xhrArgs);
	}
});
