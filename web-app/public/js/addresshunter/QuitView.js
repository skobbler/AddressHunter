/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.QuitView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.QuitView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {
	
	serviceUrl: "/game/leave",
	
	// flag if the game has been started (used to separate quit_waiting and quit_game views)
	playing: false,
	
	buttonLocked: false,
	
	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
		 // Add a click handler to the quit button
		var current_el = this.playing ? 'quit_game_btn' : 'quit_waiting_btn';
	 	var quitButton = dijit.byId(this.getElements(current_el, this.domNode)[0].id);
		dojo.connect(quitButton, "onClick", this, "quitGame");
	},


	onBeforeTransitionIn: function(moveTo, dir, transition, context, method) {
		 dojo.byId("g_points").innerHTML = addresshunter.GAME.user_points;
	},
	onAfterTransitionIn: function(moveTo, dir, transition, context, method) {
		this.buttonLocked = false;
		this.hideLoadingSpinner();
	},

	onAfterTransitionOut: function(moveTo, dir, transition, context, method) {
		this.buttonLocked = false;
	},
	
	quitGame: function(e) {
		dojo.stopEvent(e);
		
		if(this.buttonLocked) {
			return;
		}
		
		this.buttonLocked = true;
		this.showLoadingSpinner();
		
		var xhrArgs = {
			url: addresshunter.serverBase + this.serviceUrl,
			handleAs: "json",
			preventCache: true,
			content: {
				game_id: addresshunter.GAME.id
			},
			load: dojo.hitch(this, function(response, ioArgs) {
				if (response.status != 200) {
					this.handleError(response.status);
					this.buttonLocked = false;
					this.hideLoadingSpinner();
					return;
				}
			 	// animation to the next screen (parameters: viewName, direction (1 = forwards, -1 = reverse), transitionType and callback function)
				this.hideLoadingSpinner();
				dijit.byId(this.playing ? 'quit_game': 'quit_waiting').performTransition('#home', 1, "slide", null);
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