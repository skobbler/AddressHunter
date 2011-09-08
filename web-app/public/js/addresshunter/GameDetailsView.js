/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.GameDetailsView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.GameDetailsView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {
	
	// Create a template string for the games:
	templateString: '<h3 style="text-align:center">${name}</h3>'+
					'Created by: ${creator} ${distance_str} away<br />'+
					'Players (max): ${playerNo}<br />'+
					'Players joined: ${players_joined}<br />'+
					'Addresses: ${playerAddrNo} times number of players<br />'+
					'Radius: ${radius} km<br />'+
					'Timeframe: ${timeframe} hours<br /><br /><br />' +
					'Winner of this game can win up to ${bonus_max} experience bonus<br />',
					// TODO: add geocoded game location
	serviceUrl: "/game/details?game_id=${id}&posX=${posX}&posY=${posY}",
	serviceUrl2: "/game/join",
	
	timerId: null,
	buttonLocked: false,
	//TODO: add this attributes to global game and use global game instead of this
	game:{
		name: "",
		distance_str: "0.0km",
		playerNo: "",
		players_joined: "",
		playerAddrNo: "",
		radius: "",
		timeframe: "",
		bonus_max: ""
	},
	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
		
		// Add a click handler to the button
		var joinGameButton = dijit.byId(this.getElements("join_game_btn", this.domNode)[0].id);
		dojo.connect(joinGameButton, "onClick", this, "joinGame");
	},

	onBeforeTransitionIn: function(moveTo, dir, transition, context, method) {
		addresshunter.GAME.id = context.game.id;
		addresshunter.GAME.name = context.game.name;
		addresshunter.GAME.user_points = 0;
		this.clearGameDetails();
		this.loadGameDetails();
		this.timerId = setInterval(dojo.hitch(this, function() {
			this.loadGameDetails();			
		}), 10000);
		this.buttonLocked = false;
		this.hideLoadingSpinner();
	},
	
	onBeforeTransitionOut: function(moveTo, dir, transition, context, method) {
		clearTimeout(this.timerId);
		this.timerId = null;
	},

	onAfterTransitionOut: function(moveTo, dir, transition, context, method) {
		this.buttonLocked = false;
	},
	clearGameDetails: function() {
		dojo.byId("gameDetails").innerHTML = this.substitute(this.templateString, this.game);
	},
	
	renderGameDetails: function(game) {
		// Update the list item's content using our template
		var content = this.substitute(this.templateString, game);
		dojo.byId("gameDetails").innerHTML = content;
	},

	loadGameDetails: function() {
		var xhrArgs = {
			url: addresshunter.serverBase + this.substitute(this.serviceUrl, {
				id: addresshunter.GAME.id,
				posX: addresshunter.USER.posX,
				posY: addresshunter.USER.posY
			}),
			handleAs: "json",
			preventCache: true,
			
			load: dojo.hitch(this, function(response, ioArgs) {
				if (response.status != 200) {
					this.handleError(response.status);
					return;
				}
				this.game = response.data;
				this.renderGameDetails(response.data);
			}), 
			error: dojo.hitch(this, function(error, ioargs) {
				this.handleError(ioargs.xhr.status);
			})
		}

		//Call the asynchronous xhrGet
		var deferred = dojo.xhrGet(xhrArgs);
	},

	joinGame: function(e) {
		
		dojo.stopEvent(e);
		
		if (this.buttonLocked) {
			return;
		}
		
		this.buttonLocked = true;
		this.showLoadingSpinner();
		
		var xhrArgs = {
			url: addresshunter.serverBase + this.serviceUrl2,
			handleAs: "json",
			preventCache: true,
			content: {
				game_id: addresshunter.GAME.id,
				posX: addresshunter.USER.posX,
				posY: addresshunter.USER.posY
			},
			load: dojo.hitch(this, function(response, ioargs) {
				if (response.status != 200) {
					this.handleError(response.status);
					this.buttonLocked = false;
					this.hideLoadingSpinner();
					return;
				}
				//animation to the next screen (parameters: viewName, direction (1 = forwards, -1 = reverse), transitionType and callback function)
				this.hideLoadingSpinner();
				dojo.forEach(this.getElements("playing_game_name"), function(element) {
					element.innerHTML = addresshunter.GAME.name;
				});
				dijit.byId('game_details').performTransition('#waiting_start', 1, "slide", null);
			}), 
			error: dojo.hitch(this, function(error, ioargs) {
				this.handleError(ioargs.xhr.status);
				this.buttonLocked = false;
				this.hideLoadingSpinner();
			})
		}

		//Call the asynchronous xhrPost
		var deferred = dojo.xhrPost(xhrArgs);
	}
});