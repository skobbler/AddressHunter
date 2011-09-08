// dojo.provide allows pages to use all of the types declared in this resource.
dojo.provide("dojox.geo.openlayers.Layer");

dojo.declare("dojox.geo.openlayers.Layer", null, {
	// summary: 
	//   Base layer class for dojox.geo.openlayers.Map specific layers extending OpenLayers.Layer class.
	//   This layer class accepts Features which encapsulates graphic objects to be added to the map.
	//   This layer class encapsulates an OpenLayers.Layer.
	//   This class provides Feature management such as add, remove and feature access.
	constructor : function(name, options){
		// summary:
		//  Constructs a new Layer.
		// name: String
		//  The name of the layer.
		// options: Object
		//  Options passed to the underlying OpenLayers.Layer object.

		var ol = options ? options.olLayer : null;

		if (!ol)
			ol = dojo.delegate(new OpenLayers.Layer(name, options));

		this.olLayer = ol;
		this._features = null;
		this.olLayer.events.register("moveend", this, dojo.hitch(this, this.moveTo));
	},

	renderFeature : function(/* Feature */f){
		// summary:
		//   Called when rendering a feature is necessary.
		// f : Feature
		//   The feature to draw.
		f.render();
	},

	//	setMap : function(/* OpenLayers.Map */map){
	// summary:
	//   Connects the OpenLayers.Map object to the layer.
	// map: OpenLayers.Maps
	//   the map to connect.
	//		OpenLayers.Layer.prototype.setMap.apply(this, arguments);
	//	},

	getDojoMap : function(){
		return this.olLayer.map._dojoMap;
	},

	addFeature : function(/* Feature | Array */f){
		// summary:
		//   Add a feature or an array of features to the layer.
		// f : Feature or Array
		//   The Feature or array of features to add.
		if (dojo.isArray(f)) {
			dojo.forEach(f, function(item){
				this.addFeature(item);
			}, this);
			return;
		}
		if (this._features == null)
			this._features = [];
		this._features.push(f);
		f._setLayer(this);
	},

	removeFeature : function(/* Feature | Array */f){
		// summary :
		//   Removes a feature or an array of features from the layer.
		// f : Feature or Array
		//   The Feature or array of features to remove.
		var ft = this._features;
		if (ft == null)
			return;
		if (f instanceof Array) {
			f = f.slice(0);
			dojo.forEach(f, function(item){
				this.removeFeature(item);
			}, this);
			return;
		}
		var i = dojo.indexOf(ft, f); // ft.indexOf(f); No indexOf in IE
		if (i != -1)
			ft.splice(i, 1);
		f._setLayer(null);
		f.remove();
	},

	getFeatures : function(){
		// summary:
		//   Retrieves the feature hold by this layer.
		// returns: Array
		//   The untouched array of features hold by this layer.
		return this._features;
	},

	getFeatureAt : function(i){
		// summary:
		//   Returns the i-th feature of this layer.
		// i : int
		//   The index of the feature to return.
		// returns : ibm_maps.maps.Layer
		//   The i-th feature of this layer.
		if (this._features == null)
			return undefined;
		return this._features[i];
	},

	getFeatureCount : function(){
		// summary:
		//   Returns the number of the features contained by this layer.
		// returns: int
		//   The number of the features contained by this layer.
		if (this._features == null)
			return 0;
		return this._features.length;
	},

	clear : function(){
		// summary:
		//   Removes all the features from this layer.
		var fa = this.getFeatures();
		this.removeFeature(fa);
	},

	moveTo : function(event){
		if (event.zoomChanged) {
			if (this._features == null)
				return;
			dojo.forEach(this._features, function(f){
				this.renderFeature(f);
			}, this);
		}
	},

	redraw : function(){
		if (dojo.isIE)
			setTimeout(dojo.hitch(this, function(){
				this.olLayer.redraw();
			}, 0));
		else
			this.olLayer.redraw();
	},

	added : function(){
	// summary:
	//   Called when the layer is added to the map
	}

});