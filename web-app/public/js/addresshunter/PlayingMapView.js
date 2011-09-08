/*
	Copyright (c) 2011 skobbler GmbH (http://www.skobbler.com) All Rights Reserved.
	Available via New BSD License.
	see: http://www.opensource.org/licenses/BSD-3-Clause for details
*/


// Provide the class
dojo.provide("addresshunter.PlayingMapView");

dojo.require("addresshunter._ViewMixin");
dojo.require("dojox.mobile.ScrollableView");

// Declare the class
dojo.declare("addresshunter.PlayingMapView", [dojox.mobile.ScrollableView, addresshunter._ViewMixin], {
	
	serviceUrl: "/game/addresses?game_id=${id}&x=${x}&y=${y}",
	
	templateString: '${name} (~${distance}km)',
	
	map: null, // the map
	playersLayer: null, // the map layer with the players (for now only the current player)
	addressesLayer: null, // the map layer with the addresses (for now only the found addresses)
	playerFeature: null, // the vector feature with the current player

	gameId:null,
	timerId: null,
	mapTimerId: null,
	
	lastX: false,
	lastY: false,
	
	startup: function() {
		// Retain functionality of startup in dojox.mobile.ScrollableView
		this.inherited(arguments);
		
		// create MQ layer
		OpenLayers.Layer.MapQuestOSM = OpenLayers.Class(OpenLayers.Layer.XYZ, {
			name: "MapQuest",
			attribution: 'Map data <a href="http://creativecommons.org/licenses/by-sa/3.0/" target="_blank">CCBYSA</a> 2010 <a href="http://openstreetmap.org" target="_blank">OpenStreetMap.org</a> contributors - Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png" border="0">',
			sphericalMercator: true,
			url: 'http://otile1.mqcdn.com/tiles/1.0.0/osm/${z}/${x}/${y}.png',
			clone: function(obj) {
				if (obj == null) {
					obj = new OpenLayers.Layer.OSM(this.name, this.url, this.getOptions());
				}
				obj = OpenLayers.Layer.XYZ.prototype.clone.apply(this, [obj]);
				return obj;
			},
			CLASS_NAME: "OpenLayers.Layer.MapQuestOSM"
		});

		// create map
		this.map = new OpenLayers.Map({
			div: "osmMap",
			theme: null,
			controls: [
				new OpenLayers.Control.Attribution(),
				new OpenLayers.Control.TouchNavigation({
					dragPanOptions: {
						enableKinetic: true,
						kineticDragging: true,
						autoActivate: true,
						//interval: 100,
						interval: 0 // non-zero kills performance on some mobile phones
					}
				}),
				new OpenLayers.Control.ZoomPanel()
			],
			layers: [
				new OpenLayers.Layer.MapQuestOSM("OpenStreetMap", null, {
					transitionEffect: 'resize',
					tileLoadingDelay: 300
				}),
				
			],
			center: new OpenLayers.LonLat(742000, 5861000),
			zoom: 18
		});

		dragcontrol = new OpenLayers.Control.DragPan({'map':this.map, 'panMapDone':function(xy) {
			if (this.panned) {
				var res = null;
				if (this.kinetic) {
					res = this.kinetic.end(xy);
				}
				this.map.pan(
					this.handler.last.x - xy.x,
					this.handler.last.y - xy.y,
					{dragging: !!res, animate: false}
				);
				if (res) {
					var self = this;
					this.kinetic.move(res, function(x, y, end) {
						self.map.pan(x, y, {dragging: !end, animate: false});
					});
				}
				this.panned = false;
			}
			addresshunter.GAME.is_map_locked = true;
			dojo.style("recenter", "visibility", "visible");

		}});
		dragcontrol.draw();
		this.map.addControl(dragcontrol);
		dragcontrol.activate();

		// adding layer and feature with the player's position
		var p = new OpenLayers.Geometry.Point(742000, 5861000);
		this.playerFeature = new OpenLayers.Feature.Vector(p, {id:addresshunter.USER.id}, {pointRadius: 10, externalGraphic: 'img/pin_green.png'});

		this.playersLayer = new OpenLayers.Layer.Vector("Players");
		this.playersLayer.addFeatures([this.playerFeature]);
		
		this.map.addLayer(this.playersLayer);
		
		// adding layer for the addresses (empty for now)
		this.addressesLayer = new OpenLayers.Layer.Vector("Addresses");
		this.map.addLayer(this.addressesLayer);

		// centering the map
		this.centerMap();

		var recenterPlayerButton = dijit.byId("recenter");
		dojo.connect(recenterPlayerButton, "onClick", this, "recenterPlayer");
	},
	
	onStartView: function() {
		this.centerMap();
		this.mapTimerId = setInterval(dojo.hitch(this, function() {
			this.centerMap();			
		}), 5000);
		this.loadAddressList();
		this.timerId = setInterval(dojo.hitch(this, function() {
			this.loadAddressList();			
		}), 10000);
		dojo.forEach(this.getElements("playing_game_name"), function(element) {
			element.innerHTML = addresshunter.GAME.name;
		});
	},
	
	onBeforeTransitionIn: function(moveTo, dir, transition, context, method) {
		this.onStartView();
	},
	
	onBeforeTransitionOut: function(moveTo, dir, transition, context, method) {
		clearTimeout(this.mapTimerId);
		this.mapTimerId = null;
		clearTimeout(this.timerId);
		this.timerId = null;
	},
	
	// besides the address list the game status is also received and it's used as monitoring
	renderAddressList: function(game) {
		// check if it is a new game
		if (addresshunter.GAME.id != this.gameId) {
			this.clearAddressesFromMap();
			this.gameId = addresshunter.GAME.id;
		}
		// checking game status
		if (game.info.status == 'finished') { // game finished
			clearTimeout(this.mapTimerId);
			this.mapTimerId = null;
			clearTimeout(this.timerId);
			this.timerId = null;
			alert('This game has ended.');
			this.clearAddressesFromMap();
			dijit.byId('playing_map').performTransition('#end_game', 1, "slide", null);
			return;
		} else if (game.info.status == 'expired') { // game expired
			clearTimeout(this.mapTimerId);
			this.mapTimerId = null;
			clearTimeout(this.timerId);
			this.timerId = null;
			alert('This game has expired.');
			this.clearAddressesFromMap();
			dijit.byId('playing_map').performTransition('#home', 1, "slide", null);
			return;
		} else if (game.info.status != 'playing') {
			clearTimeout(this.mapTimerId);
			this.mapTimerId = null;
			clearTimeout(this.timerId);
			this.timerId = null;
			alert('This game is not available anymore.');
			this.clearAddressesFromMap();
			dijit.byId('playing_map').performTransition('#home', 1, "slide", null);
			return;
		}

		var found_no = 0;
		var remaining_no = 0;
		dojo.byId("map_time_remaining").innerHTML = game.info.time_remaining;
		dojo.forEach(game.addresses, function(address) {
			if (address.status == 'active') { // adding not found addresses to the list
				remaining_no++;
			} else { // adding found addresses to the map
				if (address.osmId == addresshunter.USER.osmId) {
					found_no++;
				}
				this.addAddressToMap(address.id, address.finalX, address.finalY);
			}
		}, this);
		dojo.byId("map_remaining").innerHTML = remaining_no;
		dojo.byId("map_collected").innerHTML = found_no;
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
					dijit.byId('playing_map').performTransition('#home', 1, "slide", null);
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
	},
	
	centerMap: function(force) {
		if (typeof(force) == 'undefined' || force != 1) {
			// if no position or position not changed since last time: do nothing (return)
			if (!addresshunter.USER.posX || !addresshunter.USER.posY || this.lastX == addresshunter.USER.posX && this.lastY == addresshunter.USER.posY) {
				return;
			}
		}
		// saving user's last position
		this.lastX = addresshunter.USER.posX;
		this.lastY = addresshunter.USER.posY;

		var newCenter = new OpenLayers.LonLat(addresshunter.USER.posX, addresshunter.USER.posY).transform(
				new OpenLayers.Projection("EPSG:4326"),
				this.map.getProjectionObject()
		);
		
		// centering the map only is the map is not locked
		if (!addresshunter.GAME.is_map_locked) {
			var current_zoom = 	this.map.getZoom();
			this.map.setCenter(newCenter, current_zoom);
		}
 		
		// moving the player's pin
		if (this.playerFeature != null) {
			var geom = this.playerFeature.geometry;
			var oldCenter = geom.getBounds().getCenterLonLat();
			var dx = newCenter.lon - oldCenter.lon;
			var dy = newCenter.lat - oldCenter.lat;
			geom.move(dx, dy);
			this.playerFeature.layer.drawFeature(this.playerFeature);
		} 
	},
	
	addAddressToMap: function(id, x, y) {
		if (this.addressesLayer.getFeaturesByAttribute('id', id).length == 0) {
			var ll = new OpenLayers.LonLat(x, y).transform(
				new OpenLayers.Projection("EPSG:4326"),
				this.map.getProjectionObject()
			);
			var p = new OpenLayers.Geometry.Point(ll.lon, ll.lat);
			var feature = new OpenLayers.Feature.Vector(p, {id:id}, {pointRadius: 16, externalGraphic: 'img/pin_red.png', graphicYOffset: -30});
			this.addressesLayer.addFeatures([feature]);
		}
	},
	
	clearAddressesFromMap: function() {
		this.addressesLayer.destroyFeatures();
	},
	recenterPlayer:function() {
		addresshunter.GAME.is_map_locked = false;
		dojo.style("recenter", "visibility", "hidden");
		this.centerMap(1);
	}
});
