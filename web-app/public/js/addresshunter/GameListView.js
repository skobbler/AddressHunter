/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.GameListView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.GameListView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {
	
	// Create a template string for the games:
	gameTemplateString: "${name}<br /><span>(${players}/${maxPlayers} players) ~${distance_str} away</span>",
	
	serviceUrl: "/game/list",
	
	timerId: null,
	
	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
	},

	onBeforeTransitionIn: function(moveTo, dir, transition, context, method) {
		this.clearGameList();
		this.loadGameList();
		this.timerId = setInterval(dojo.hitch(this, function() {
			this.loadGameList();			
		}), 10000);
	},
	
	onBeforeTransitionOut: function(moveTo, dir, transition, context, method) {
		clearTimeout(this.timerId);
		this.timerId = null;
	},
	
	clearGameList: function() {
		var gameList = this.getElements("gameList", this.domNode)[0];
		gameList.innerHTML = null;	
	},
	
	renderGameList: function(games) {
		this.clearGameList();
		
		var gameList = this.getElements("gameList", this.domNode)[0];
		
		dojo.forEach(games, function(game) {
			// Update the list item's content using our template
			var label = this.substitute(this.gameTemplateString, game);
			var item = new dojox.mobile.ListItem({
				moveTo: "game_details",
				transition: "slide",
				label: label,
				game: game,
				callback: function() {} // this is needed otherwise the game obj is not transmitted through the context
			});
			item.set("class", "mblVariableHeight");
			item.domNode.firstChild.firstChild.innerHTML = label;
			item.placeAt(gameList, "first");
		}, this);
	},

	loadGameList: function() {
		var xhrArgs = {
			url: addresshunter.serverBase + this.serviceUrl,
			content: {
				posX: addresshunter.USER.posX,
				posY: addresshunter.USER.posY
			},
			handleAs: "json",
			preventCache: true,
			load: dojo.hitch(this, function(response, ioArgs) {
				if (response.status != 200) {
					if (response.status == 620) {
						var gameList = this.getElements("gameList", this.domNode)[0];
						gameList.innerHTML = null;
						var item = new dojox.mobile.ListItem({
							label: "There are no available games in your area."
						}).placeAt(gameList, "first");
						return;
					} else {
						this.handleError(response.status);
						return;
					}
				}
				this.renderGameList(response.data);
			}), 
			error: dojo.hitch(this, function(error, ioargs) {
				this.handleError(ioargs.xhr.status);
			})
		}
		//Call the asynchronous xhrGet
		var deferred = dojo.xhrGet(xhrArgs);
	}
});