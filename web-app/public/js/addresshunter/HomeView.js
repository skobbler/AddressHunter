/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.HomeView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.HomeView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {

	serviceUrl: "/game/user",
	characterImageUrl: "js/addresshunter/themes/mapers_theme/iphone/images/retina/",

	onBeforeTransitionIn: function(moveTo, dir, transition, context, method) {
		this.getUserRank();
	},

	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
		this.getUserRank();
		var options = {
			timeout: 20000, // wait 10 seconds before returning with an error
			enableHighAccuracy: true, // may drain the battery of mobile devices faster
			maximumAge: 5000
		 }

		 var watch_id = dojo.sensor.geolocation.watchPosition({
				 success: function(position) {
						// storing the position
						addresshunter.USER.posX = position.coords.longitude;
						addresshunter.USER.posY = position.coords.latitude;
						addresshunter.USER.posDate = new Date();
				 },
				 error: function(error) {
					var message = "Error getting user position: Unknown error!";
					if (error.code == 1) {
						message = "Error getting user position: Access is denied!";
					} else if (error.code == 2) {
						message = "Error getting user position: Position is unavailable!";
					} else if (error.code == 3) {
						message = "Error getting user position: Timeout!";
					}
				 }
		 },
		 options);		 
	},

	getUserRank: function() {
		var xhrArgs = {
			url: addresshunter.serverBase + this.serviceUrl,
			handleAs: "json",
			preventCache: true,
			load: dojo.hitch(this, function(response, ioArgs) {
				if (response.status != 200) {
					this.handleError(response.status);
					return;
				}
				this.setUserRank(response.data.totalPoints);
			}),
			error: dojo.hitch(this, function(error, ioargs) {
				this.handleError(ioargs.xhr.status);
			})
		}

		//Call the asynchronous xhrGet
		var deferred = dojo.xhrGet(xhrArgs);
	},
	setUserRank: function(user_points) {
		var user_rank = this.getGeneralUserRank(null, user_points);
		dojo.byId("user_rank").innerHTML = user_rank.rank_name;
		dojo.byId("user_points").innerHTML = user_points;
		dojo.byId("user_rank_character").src = this.characterImageUrl + "character_" + user_rank.rank_id + ".png";
		dojo.byId("user_rank_message").innerHTML = user_rank.message;
		if (user_rank.rank_id < 7) {
			var next_rank = this.getGeneralUserRank(user_rank.rank_id + 1, null);
			dojo.byId("next_rank_points").innerHTML =user_rank.max_points;
			dojo.byId("next_rank").innerHTML = next_rank.rank_name;
			dojo.style("next_rank_content", "visibility", "visible");
		}
	}
});