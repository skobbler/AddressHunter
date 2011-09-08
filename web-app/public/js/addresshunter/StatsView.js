/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.StatsView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.StatsView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {

	serviceUrl: "/game/user",
	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
			
	},

	onBeforeTransitionIn: function(moveTo, dir, transition, context, method) {
		this.getUserStats();
	},

	getUserStats: function() {
		var xhrArgs = {
			url:  addresshunter.serverBase + this.serviceUrl,
			handleAs: "json",
			preventCache: true,
			load: dojo.hitch(this, function(response, ioArgs) {
				if (response.status != 200) {
					this.handleError(response.status);
					return;
				}
				this.renderUserStats(response.data);
			}),
			error: function(error, ioargs) {
				this.handleError(ioargs.xhr.status);
			}
		}

		// Call the asynchronous xhrGet
		var deferred = dojo.xhrGet(xhrArgs);
	},
	renderUserStats: function(user) {
		// Update the list item's content using our template
		// var content = this.substitute(this.templateString, user);
		// dojo.byId("userStats").innerHTML = content;
	}

});