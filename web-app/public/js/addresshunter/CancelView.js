/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.CancelView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.CancelView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {
	
	serviceUrl: "/game/cancel",
	
	buttonLocked: false,
	
	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
		// Add a click handler to the quit button
		var quitButton = dijit.byId(this.getElements('cancel_game_btn', this.domNode)[0].id);
		dojo.connect(quitButton, "onClick", this, "cancelGame");
	},
	
	onAfterTransitionIn: function(moveTo, dir, transition, context, method) {
		this.buttonLocked = false;
		this.hideLoadingSpinner();
	},

	onAfterTransitionOut: function(moveTo, dir, transition, context, method) {
		this.buttonLocked = false;
	},
	
	cancelGame: function(e) {
		dojo.stopEvent(e);
		
		if (this.buttonLocked) {
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
			load: dojo.hitch(this, function(response, ioargs) {
				if (response.status != 200) {
					this.handleError(response.status);
					this.buttonLocked = false;
					this.hideLoadingSpinner();
					return;
				}
				// animation to the next screen (parameters: viewName, direction (1 = forwards, -1 = reverse), transitionType and callback function)
				this.hideLoadingSpinner();
				// TODO: clear addresshunter.GAME.* values?
				dijit.byId('cancel_game').performTransition('#home', 1, "slide", null);
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