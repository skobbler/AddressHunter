/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.EndGameView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.EndGameView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {
	
	// Create a template string for the games:
	playerTemplateString: '${nickname} earned ${points} experience points. Rank: ${rank}',
	
	serviceUrl: "/game/results?game_id=${id}",
	
	timerId: null,
	
	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
	},

	onBeforeTransitionIn: function(moveTo, dir, transition, context, method) {
		this.clearResultList();
		this.loadResultsList();
	},
	
	clearResultList: function() {
		var resultList = this.getElements("resultList", this.domNode)[0];
		resultList.innerHTML = null;
	},
	
	renderResultList: function(game) {
		this.clearResultList();
		
		var resultList = this.getElements("resultList", this.domNode)[0];
		if (game.players[0].id == addresshunter.USER.id) {
			dojo.style("results_winning_title", "display", "block");
		} else {
			dojo.style("results_winning_title", "display", "none");
		}
		console.log(game.players);
		dojo.forEach(game.players, function(player) {
			console.log(player);
			// Update the list item's content using our template
			var rank = this.getGeneralUserRank(null, player.tpoints);
			player.rank = rank.rank_name;
			var label = this.substitute(this.playerTemplateString, player);
			var item = new dojox.mobile.ListItem({
				label: label,
				noArrow: true
			});
			item.set("class", "mblVariableHeight");
			item.placeAt(resultList, "last");
		}, this);

	},

	loadResultsList: function() {
		var xhrArgs = {
			url: addresshunter.serverBase +  this.substitute(this.serviceUrl, {
				id: addresshunter.GAME.id
			}),
			handleAs: "json",
			preventCache: true,
			load: dojo.hitch(this, function(response, ioArgs) {
				if (response.status != 200) {
					this.handleError(response.status);
					return;
				}
				this.renderResultList(response.data);
			}), 
			error: dojo.hitch(this, function(error, ioargs) {
				this.handleError(ioargs.xhr.status);
			})
		}

		//Call the asynchronous xhrGet
		var deferred = dojo.xhrGet(xhrArgs);
	}
	
});