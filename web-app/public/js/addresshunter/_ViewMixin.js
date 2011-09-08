/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter._ViewMixin");

// Declare the class
dojo.declare("addresshunter._ViewMixin", null, {

	// Pushes data into a template - primitive
	substitute: function(template, obj) {
		return template.replace(/\$\{([^\s\:\}]+)(?:\:([^\s\:\}]+))?\}/g, function(match, key) {
			return obj[key];
		});
	},
	
	// Get elements by CSS class name
	getElements: function(cssClass, rootNode) {
		return (rootNode || dojo.body()).getElementsByClassName(cssClass);
	},
	
	// Generic error handler
	handleError: function(errorCode) {
		if (errorCode == 0) {
			alert("No internet connectivity.");
			return;
		}
		if (errorCode == 401) {
			alert("Your login session has been ended.");
			document.location = addresshunter.serverBase + "/osm/logout";
			return;
		}
		if (errorCode == 400) {
			alert("Missing or invalid parameter.");
			return;
		}
		if (errorCode == 500) {
			alert("Oops! An error has occurred. We're sorry for the inconvenience.");
			return;
		}
		if (errorCode == 612) {
			alert("There are not enough addresses in this area to start this game.");
			return;
		}
		if (errorCode == 601) {
			alert("This game is not available anymore.");
			return;
		}
		if (errorCode == 602) {
			alert("You cannot join this game because you are already playing another game.");
			return;
		}
		if (errorCode == 603) {
			alert("You cannot join this game because the maximum number of players already joined.");
			return;
		}
		if (errorCode == 620) {
			alert("There are no available games in your area.");
			return;
		}
		if (errorCode == 611) {
			alert("This address has already been taken.");
			return;
		}
		if (errorCode == 404) {
			alert("The requested page was not found.");
			return;
		}
		if (errorCode == 407) {
			alert("You need to authenticate with a proxy.");
			return;
		}
		/*if (typeof console != 'undefined') {
			console.log("Unknown error.");
		}*/
	},
	getGeneralUserRank: function(rank_id, user_points) {

		var all_ranks_definition = new Array(7);
		all_ranks_definition[1] = { rank_id: 1,
									rank_name : "Drifter",
									max_points:28,
									message: "Master Vagabond"
		};
		all_ranks_definition[2] = { rank_id: 2,
									rank_name : "Nomad",
									max_points:82,
									message: "Saint of Wanderers"
		};
		all_ranks_definition[3] = { rank_id: 3,
									rank_name : "Traveler",
									max_points:244,
									message: "Quester of Truth"
		};
		all_ranks_definition[4] = { rank_id: 4,
									rank_name : "Pathfinder",
									max_points:730,
									message: "Master of Trailblazing"
		};
		all_ranks_definition[5] = { rank_id: 5,
									rank_name : "Discoverer",
									max_points:2188,
									message: "Journeyman of Exploration"
		};
		all_ranks_definition[6] = { rank_id: 6,
									rank_name : "Explorer",
									max_points:6562,
									message: "Seeker of Knowledge"
		};
		all_ranks_definition[7] = { rank_id: 7,
									rank_name : "Cartographer",
									max_points:10000,
									message: "Patron of Travelers"
		};

		if (rank_id != null) {
			return all_ranks_definition[rank_id];
		}
		if (user_points != null) {
			var rank;
			for (rank = 1; rank<=7; rank++)
				if (user_points < all_ranks_definition[rank].max_points) {
					return all_ranks_definition[rank];
			}
		}
		return all_ranks_definition;
	},
	
	showLoadingSpinner: function() {
		dojo.byId('loading_spinner').style.display = 'block';
	},
	
	hideLoadingSpinner: function() {
		dojo.byId('loading_spinner').style.display = 'none';
	}
});