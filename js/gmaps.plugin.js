(function(){
	var gMap = function(jElement, customOptions) {
		// current instance
		var instance = null;

		// plugin-options
		var options = {
			"map":{
				center: new google.maps.LatLng(51.163375,10.447683),
				zoom: 7,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			},
			"autocomplete" : {
				"enabled" : false,
				"onPlaceChanged" : null
			},
			"marker" : {}
		};
		
		this.currentCenter = null;
		
		// markers data
		this.markers = [];
		
		// container element (jQuery)
		this.container = $('<div/>');
		if( jElement.length ) this.container = jElement;
		// google map instance
		this.map = null;
		// google places autocomplete instance
		this.autocomplete = null;

		// initialize this plugin with current options
		var init = function(customOptions) {
			var defaultOptions = options;
			if( customOptions.map ) options["map"] = $.extend({}, defaultOptions.map, customOptions.map);
			if( customOptions.autocomplete ) options["autocomplete"] = $.extend({}, defaultOptions.autocomplete, customOptions.autocomplete);
			if( customOptions.marker ) options["marker"] = $.extend({}, defaultOptions.marker, customOptions.marker);
		}
		// helper to set instance after calling contructor
		this._setInstance = function(inst){
			instance = inst;
		}
		
		this.getOption = function(type, key) {
			if( options[ type ] ) {
				if( key && options[ type ][ key ] ) {
					return options[ type ][ key ];
				} else {
					return options[ type ];
				}
			}
			return null;
		}
		
		// creates the map in the configured container
		this.create = function() {
			instance.map = new google.maps.Map(instance.container.get(0), options.map);
			instance.currentCenter = options.map.center;
			google.maps.event.addListener(instance.map, 'center_changed', function(){
				instance.currentCenter = instance.map.getCenter();
			});
			if( instance.markers.length ) {
				setTimeout(function(){
					var curMarker = null;
					for (var i = 0; i < instance.markers.length; i++) {
						curMarker = instance.markers[i];
						setTimeout(function() {
							curMarker.setMap(instance.map);
						}, i * 200);
					}
				}, 1000);
			}
			if( options.autocomplete.enabled && $(options.autocomplete.input).length ) {
				var acOptions = options.autocomplete ? options.autocomplete : {};
				instance.enablePlaceAutocomplete($(options.autocomplete.input).get(0), acOptions);
			}
		}
		
		// add a marker to the map
		this.addMarker = function(markerData) {
			var markerData = $.extend({}, {
					animation: google.maps.Animation.DROP
				}, options.marker, markerData);
			if( instance.map !== null ) {
				markerData.map = instance.map;
			}
			var marker = new google.maps.Marker(markerData);
			if( markerData.info ) {
				var infoWindow = markerData.info.object;
				if( !infoWindow ) {
					infoWindow = new google.maps.InfoWindow();
				}
				google.maps.event.addListener(marker, 'click', function() {
					infoWindow.setContent(markerData.info.content);
					infoWindow.open(instance.map, marker);
					marker.setAnimation(null);
				});
			}
			
			instance.markers.push(marker);
			return marker;
		}
		
		this.enablePlaceAutocomplete = function(input, acOptions) {
			instance.autocomplete = new google.maps.places.Autocomplete(input, acOptions);
			if( options.autocomplete.onPlaceChanged === null ) {
				options.autocomplete.onPlaceChanged = instance.autocompletePlaceChanged
			}
			google.maps.event.addListener(instance.autocomplete, 'place_changed', function(){
				options.autocomplete.onPlaceChanged.apply(this, arguments);
			});
		}
		
		this.autocompletePlaceChanged = function() {
			var place = instance.autocomplete.getPlace();
			instance.map.setZoom(14);
			if( place.geometry.viewport ) {
				instance.map.panToBounds(place.geometry.viewport);
			} else {
				instance.map.panTo(place.geometry.location);
			}
		}
		
		this.tools = {
			getAddressComponentFromType : function(components, type) {
				var result = {};
				$.each(components, function(k){
					if( this.types ) {
						for( i in this.types ) {
							if( this.types[i] == type ) {
								result = components[k];
								return;
							}
						}
					}
				});
				return result;
			}
		}
		
		init(customOptions);
		return this;
	}
	
	// connect plugin to jquery elements and store the instance onto the container element
	$.fn.gMap = function(options) {
		return this.each(function(index,el) {
			var jElement = $(el);
			var instance = new gMap(jElement, options);
			instance._setInstance(instance);
			jElement.data('gMap',instance);
		});
	}
})(jQuery);