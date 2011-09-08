/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.PlayingListView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.PlayingListView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {
	
	serviceUrl: "/game/addresses?game_id=${id}&x=${x}&y=${y}",
	
	templateString: '${name} (~${distance}km away)',
	
	timerId: null,

	// TODO: add button locking?
	//buttonLocked: false,
	
	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
		
		// delegated event handler for all "Found it" buttons
		dojo.query("#addressList").delegate("button", "onclick", function(event) {
			var k = this.title.indexOf('|');
			addresshunter.USER.lastAddrId = this.title.substr(0, k);
			addresshunter.USER.lastAddrName = this.title.substr(k+1);
			dojo.stopEvent(event);
			// animation to the next screen (parameters: viewName, direction (1 = forwards, -1 = reverse), transitionType and callback function)
			dijit.byId('playing_list').performTransition('#addressfind_mp', 1, "slide", null);
			// TODO: add button locking?
		});
	},
	
	onBeforeTransitionIn: function(moveTo, dir, transition, context, method) {
		this.loadAddressList();
		this.timerId = setInterval(dojo.hitch(this, function() {
			this.loadAddressList();			
		}), 5000);
		//this.buttonLocked = false;
		// resetting list scrolling
		this.containerNode.style.top = "0px";
		dojo.style(this.containerNode, "-webkit-transform", "translate3d(0px, 0px, 0px)");
	},
	
	onBeforeTransitionOut: function(moveTo, dir, transition, context, method) {
		clearTimeout(this.timerId);
		this.timerId = null;
	},
	
	// besides the address list the game status is also received and it's used as monitoring
	renderAddressList: function(game) {
		// checking game status
		if (game.info.status == 'finished') { // game finished
			alert('This game has ended.');
			dijit.byId('playing_list').performTransition('#end_game', 1, "slide", null);
			return;
		} else if (game.info.status == 'expired') { // game expired
			alert('This game has expired.');
			dijit.byId('playing_list').performTransition('#home', 1, "slide", null);
			return;
		} else if (game.info.status != 'playing') {
			alert('This game is not available anymore.');
			dijit.byId('playing_list').performTransition('#home', 1, "slide", null);
			return;
		}
		dojo.byId("addresses_time_remaining").innerHTML = game.info.time_remaining;
		var addressList = this.getElements("addressList", this.domNode)[0];
		addressList.innerHTML = null;
		var found_no = 0;
		var remaining_no = 0;
		dojo.forEach(game.addresses, function(address) {
			if (address.status == 'active') { // adding not found addresses to the list
				remaining_no++;
				var label = this.substitute(this.templateString, address);
				// adding the list item
				var item = new dojox.mobile.ListItem({
					label: label,
					noArrow: true
				});
				item.set("class", "mblVariableHeight");
				item.placeAt(addressList, "first");
				
				// adding the "FOUND" button
				var button = new dojox.mobile.Button({
					label: 'Found it',
					btnClass: 'found_address_btn',
					title: (address.id+'|'+address.name)
				});
				button.placeAt(item.domNode, "first");
			} else {
				if (address.osmId == addresshunter.USER.osmId) {
					found_no++;
				}
			}
		}, this);
		dojo.byId("list_remaining").innerHTML = remaining_no;
		dojo.byId("list_collected").innerHTML = found_no;
	},
	
	loadAddressList: function() {
		var xhrArgs = {
			url: addresshunter.serverBase + this.substitute(this.serviceUrl, {
				id: addresshunter.GAME.id,
				x: addresshunter.USER.posX,
				y: addresshunter.USER.posY
			}),
			handleAs: "json",
			preventCache: true,
			
			load: dojo.hitch(this, function(response, ioArgs) {
				if (response.status == 609) {
					dijit.byId('playing_list').performTransition('#home', 1, "slide", null);
					return;
				}
				if (response.status != 200) {
					this.handleError(response.status);
					return;
				}
				this.renderAddressList(response.data);
			}), 
			error: dojo.hitch(this, function(error, ioargs) {
				this.handleError(ioargs.xhr.status);
			})
		}

		// Call the asynchronous xhrGet
		var deferred = dojo.xhrGet(xhrArgs);
	}
	
});