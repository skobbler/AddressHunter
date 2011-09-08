/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.WaitingView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.WaitingView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {
	
	// template string:
	templateString: '${nickname} (${rank} ${points}xp) joined ${since}',

	serviceUrl: "/game/players?game_id=${id}",
	serviceUrl2: "/game/start",
	
	// flag if the user is the game creator (used to separate the waiting_join and waiting_start views)
	creator: false,
	
	timerId: null,
	
	// used to lock certain buttons to prevent multiple submits
	buttonLocked: false,
	
	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
		// Add a click handler to the start_game button (only in the game creator view)
		if (this.creator) {
			var startGameButton = dijit.byId(this.getElements("start_game_btn", this.domNode)[0].id);
			dojo.connect(startGameButton, "onClick", this, "startGame");
		}
	},
	
	onStartView: function() {
		this.loadJoinList();
		this.timerId = setInterval(dojo.hitch(this, function() {
			this.loadJoinList();			
		}),5000);
		this.buttonLocked = false;
		this.hideLoadingSpinner();
	},

	onBeforeTransitionIn: function(moveTo, dir, transition, context, method) {
		this.onStartView();
	},
	
	onBeforeTransitionOut: function(moveTo, dir, transition, context, method) {
		clearTimeout(this.timerId);
		this.timerId = null;
	},
	onAfterTransitionOut: function(moveTo, dir, transition, context, method) {
		this.buttonLocked = false;
	},

	// besides the join list the game status is also received and it is used as monitoring for the game start or cancel for the regular players (not the creator)
	renderJoinList: function(game) {	
		// checking game status
		if (!this.creator) {
			if(game.info.status == 'playing') { // game started
				clearTimeout(this.timerId);
				this.timerId = null;
				dijit.byId('waiting_start').performTransition('#playing_map', 1, "slide", null);
				return;
			} else if(game.info.status == 'canceled') { // game canceled
				clearTimeout(this.timerId);
				this.timerId = null;
				alert('This game was canceled by the game creator.');
				dijit.byId('waiting_start').performTransition('#home', 1, "slide", null);
				return;
			} else if(game.info.status != 'new') { // game expired (or finished)
				clearTimeout(this.timerId);
				this.timerId = null;
				alert('This game is not available anymore.');
				dijit.byId('waiting_start').performTransition('#home', 1, "slide", null);
				return;
			}
		}
		dojo.forEach(this.getElements("game_name"), function(element) {
			element.innerHTML = game.info.name;
		});
		var joinList = dojo.byId(this.creator ? 'joinListCreator' : 'joinListPlayers');
		joinList.innerHTML = null;
		
		dojo.forEach(game.players, function(player) {
			// Update the list item's content using our template
			var rank = this.getGeneralUserRank(null, player.points);
			player.rank = rank.rank_name;
			var label = this.substitute(this.templateString, player);
			var item = new dojox.mobile.ListItem({
				label: label,
				noArrow: true
			}).placeAt(joinList, "first");
		}, this);
	},

	loadJoinList: function() {
		var xhrArgs = {
			url: addresshunter.serverBase + this.substitute(this.serviceUrl, {
				id: addresshunter.GAME.id
			}),
			handleAs: "json",
			preventCache: true,
			
			load: dojo.hitch(this, function(response, ioArgs) {
				if (response.status == 401) {
					this.handleError(response.status);
					return;
				}
				// for some reason the user is not in this game any more
				if (response.status == 609) {
					dijit.byId(this.creator ? 'waiting_join' : 'waiting_start').performTransition('#home', 1, "slide", null);
					return;
				}
				this.renderJoinList(response.data);
			  }), 
			error: dojo.hitch(this, function(error, ioargs) {
				this.handleError(ioargs.xhr.status);
			})
		}

		// Call the asynchronous xhrGet
		var deferred = dojo.xhrGet(xhrArgs);
	},

	startGame: function(e) {
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
				game_id: addresshunter.GAME.id
			},
			load: dojo.hitch(this, function(response, ioargs) {
				if (response.status != 200) {
					this.handleError(response.status);
					this.buttonLocked = false;
					this.hideLoadingSpinner();
					return;
				}
				this.hideLoadingSpinner();
				clearTimeout(this.timerId);
				this.timerId = null;
				// animation to the next screen (parameters: viewName, direction (1 = forwards, -1 = reverse), transitionType and callback function)
				dijit.byId('waiting_join').performTransition('#playing_map', 1, "slide", null);
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
