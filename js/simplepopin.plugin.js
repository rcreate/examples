(function ($) {
	var simplePopIn = function(settings) {
		var inst = this;
		
		this.overlay = [];
		this.container = [];
		this.containerWrap = [];
        this.containerContentScrollbar = [];
        this.containerContentViewport = [];
        this.containerContentViewportOverview = [];
		this.dataContainer = [];
		this.closeBtn = [];
        
        this.bodyOverflow = {};
		
		// Default-Settings
		this.settings = {
			"popin_container_id" : 'simplepopin_container',
			"popin_container_class" : 'simplepopin_container',
			"popin_overlay_class" : 'simplepopin_overlay',
			"popin_wrap_class" : 'simplepopin_wrap',
            "popin_close_btn_class" : 'simplepopin_close_btn',
            "popin_content_viewport_class" : 'viewport',
            "popin_content_overview_class" : 'overview',
			"popin_overlay_opacity" : 0.4,
			"popin_close_btn_content" : "X",
			"canClose" : true,
			"canCloseByOverlay": true,
			"scroll" : false,
			"appendTo" : 'body',
			"beforeOpen" : function() {}, // not used yet
            "afterOpen" : function() {}, // not used yet
            "beforeClose" : function() {}, 
            "afterClose" : function() {}
		};
		
		var init = function(settings) {
			$.extend(inst.settings, settings);
			if( inst.settings.scroll === true && $.isFunction($.fn.tinyscrollbar) !== true ) {
				$.ajax({
					"url":myty.basePath+'/../3rdParty/scripts/jquery/plugins/jquery.tinyscrollbar.min.js',
					"dataType":'script',
					"async":false
				});
				if (document.createStyleSheet){
					document.createStyleSheet(myty.basePath+'/../3rdParty/scripts/jquery/plugins/jquery.tinyscrollbar.css');
				} else {
					$("head").append($('<link rel="stylesheet" href="'+myty.basePath+'/../3rdParty/scripts/jquery/plugins/jquery.tinyscrollbar.css" type="text/css" media="screen" />'));
				}
			}
			if ( $('#'+inst.settings.popin_container_id).length ) {
				inst.container = $('#' + inst.settings.popin_container_id);
				var re = new RegExp(' ', 'g');
				inst.containerWrap = inst.container.children('.' + inst.settings.popin_wrap_class.replace(re,"."));
				if( inst.settings.scroll === true ) {
					inst.containerContentScrollbar = inst.containerWrap.children('.' + inst.settings.popin_scrollbar_class.replace(re,"."));
					inst.containerContentViewport = inst.containerWrap.children('.' + inst.settings.popin_content_viewport_class.replace(re,"."));
					inst.containerContentViewportOverview = inst.containerContentViewport.children('.' + inst.settings.popin_content_overview_class.replace(re,"."));
					inst.dataContainer = inst.containerContentViewportOverview;
				} else {
					inst.dataContainer = inst.containerWrap;
				}
				inst.closeBtn = inst.container.children('.' + inst.settings.popin_close_btn_class);
                inst.overlay = inst.container.prev();
			} else {
				inst.container = $('<div id="' + inst.settings.popin_container_id + '" class="' + inst.settings.popin_container_class + '"/>');
				inst.containerWrap = $('<div class="' + inst.settings.popin_wrap_class + '"/>');
                inst.closeBtn = $('<div class="' + inst.settings.popin_close_btn_class + '">'+inst.settings.popin_close_btn_content+'</div>');
				inst.container.append(inst.closeBtn).append(inst.containerWrap);
				
				if( inst.settings.scroll === true ) {
					inst.containerContentScrollbar = $('<div class="scrollbar"><div class="track"><div class="thumb"><div class="end"></div></div></div></div>');
					inst.containerContentViewport = $('<div class="' + inst.settings.popin_content_viewport_class + '"/>');
					inst.containerContentViewportOverview = $('<div class="' + inst.settings.popin_content_overview_class + '"/>');
					inst.containerContentViewport.append(inst.containerContentViewportOverview);
					inst.containerWrap.append(inst.containerContentScrollbar).append(inst.containerContentViewport);
					inst.dataContainer = inst.containerContentViewportOverview;
				} else {
					inst.dataContainer = inst.containerWrap;
				}
				
				inst.overlay = $('<div class="' + inst.settings.popin_overlay_class + '"/>');
				inst.overlay.add(inst.container).hide();
				$(inst.settings.appendTo).append(inst.overlay);
				$(inst.settings.appendTo).append(inst.container);
			}
			if( inst.settings.canClose == false ) {
				inst.closeBtn.hide();
			}
			inst.container.data('simplePopIn', inst);
            
            inst.bodyOverflow = {
                'both': $('body').css('overflow'),
                'x': $('body').css('overflow-x'),
                'y': $('body').css('overflow-y'),
            };
            inst.bodyScrollTop = $('body').scrollTop();
		};
		
		this.request = function(url, data, type, callback) {
			var data = data || [];
			if( $.isArray(data) ) {
				data.push({name:'popin',value:'1'});
			}
			var _open = function() {
			    inst._beforeOpen.call(this);
			    
				inst.showBackground();
				inst.dataContainer.html('');
				
				if( url.indexOf(' ') > 0 ) {
					var _url = myty.basePath + url.substr(0,url.indexOf(' '));
					var dataType = data.dataType || 'html';
					$.ajax({
			            "url": _url,
			            "data" : data,
						"dataType" : dataType,
						"type" : type,
						"success": function(html){
							if(typeof ga != 'undefined'){
								ga('send','pageview', _url);
							} else if(typeof _gaq != 'undefined'){
								_gaq.push(['_trackPageview', _url]);
							}
							var stuff = $(url.substr((url.indexOf(' ')+1)), html);
							// workaround for problems with jQuery 1.8.1
							if( stuff.length === 0 ) {
								var fragment = $(html);
								var stuff = fragment.filter(url.substr((url.indexOf(' ')+1)));
							}
							if( stuff.length ) {
								inst.dataContainer.empty();
								inst.dataContainer.append(stuff);
								
								// evaluate scripts with attribute "defer"
								$(html).filter('script[defer][src]').each(function(){
									$.getScript($(this).attr('src'));
								});
								$(html).filter('script[defer]').each(function(){
									$('body').append($(this));
								});

								var result = {"result":true};
								inst.processResult.call(this, result);
								inst._afterOpen.call(this, callback, result);
                                inst.initScrollBar();
							}
		                }
			        });
				} else {
					var _url = myty.basePath + url;
					var dataType = data.dataType || 'json';
					$.ajax({
						"url" : _url,
						"data" : data,
						"dataType" : dataType,
						"type" : type,
						"success" : function(result) {
                            if(typeof ga != 'undefined'){
                                ga('send','pageview', _url);
                            } else if(typeof _gaq != 'undefined'){
                                _gaq.push(['_trackPageview', _url]);
                            }
                            if (dataType == 'html') {
                                result = {'data' : {'html' : result}, 'success' : true};
                            }
                            inst.processResult.call(this, result);
                            if ($.isFunction(callback)) {
                                callback.call(this, result);
                            }
                            inst.initScrollBar();
                        },
                        "error" : function(result) {
                            console.log(result);
                        }
					});
				}
			};

			if (inst.container.is(':visible')) {
				inst.close(_open);
			} else {
				_open();
			}
		};
		
		this._beforeOpen = function() {
            var beforeOpen = inst.settings.beforeOpen;
            if ($.isFunction(beforeOpen)) {
                beforeOpen.call(this);
            }
        };
        
        this._afterOpen = function(callback, params) {
            var cbFunction = function(){},
                afterOpen = inst.settings.afterOpen;
                
            if ($.isFunction(callback)) {
                cbFunction = callback;
            } else if ($.isFunction(afterOpen)) {
                cbFunction = afterOpen;
            }
            
            cbFunction.call(this, params);
        };
		
		this.open = function(url, data, callback) {
			return this.request(url, data, 'GET', callback);
		};
		
		this.post = function(url, data, callback) {
			return this.request(url, data, 'POST', callback);
		};
		
		this.message = function(message, callback) {
			var _open = function() {
			    inst._beforeOpen.call(this);
			    
				inst.showBackground();
				inst.dataContainer.html('');
				
				var result = {"result":true, "data" : { "html" : message }};
				inst.processResult.call(null, result);
				
				inst._afterOpen.call(this, callback, result);
			};

			if (inst.container.is(':visible')) {
				inst.close(_open);
			} else {
				_open();
			}
		};
		
		this.showFragment = function(page, selector, callback) {
			var _open = function() {
			    inst._beforeOpen.call(this);
			    
				inst.showBackground();
				inst.dataContainer.empty();
				
				var stuff = $(selector, page);
				// workaround for problems with jQuery 1.8.1
				if( stuff.length === 0 ) {
					var fragment = $(page);
					stuff = fragment.filter(selector);
				}
				if( stuff.length ) {
					inst.dataContainer.append(stuff);

					// evaluate scripts with attribute "defer"
					$(page).filter('script[defer][src]').each(function(){
						$.getScript($(this).attr('src'));
					});
					$(page).filter('script[defer]').each(function(){
						$('body').append($(this));
					});

					var result = {"result":true};
					inst.processResult.call(this, result);
					inst._afterOpen.call(this, callback, result);
					inst.initScrollBar();
				}
			};

			if (inst.container.is(':visible')) {
				inst.close(_open);
			} else {
				_open();
			}
		};
		
		this.showBackground = function() {
			inst.overlay.show().css('opacity', 0).fadeTo('fast', inst.settings.popin_overlay_opacity);
			inst.bodyScrollTop = $('body').scrollTop();
            $('body').css({
                'position': 'fixed',
                'overflow-x': 'hidden',
                'top': -inst.bodyScrollTop + 'px',
                'width': '100%'
            }); //Scrollen deaktivieren
		};
		
		this.processResult = function(res) {
			if ( res.result === true || res.success === true ) {
				if( res.data ) {
					inst.dataContainer.html(res.data.html);
				}
				inst.container.fadeIn('slow');
				inst.center();
				inst.bindEvents();
				
				if( res.data && res.data.complete && $.isFunction(inst[res.data.complete]) ) {
					inst[res.data.complete].call(inst, res);
				}
			} else {
				alert('Es ist ein Fehler aufgetreten. Diese Einstellung kann zur Zeit nicht bearbeitet werden.');
			}
		};
		
		this.center = function(animate) {
		    var cssOptions = {
		        'position': 'absolute',
		        'marginTop': 0
		    };
		    var animateOptions = {
		        'top': (Math.floor(($(window).height() - inst.container.outerHeight()) / 2) + inst.bodyScrollTop)+'px',
                'marginLeft': -(Math.floor(inst.container.outerWidth() / 2))+'px',
		    };
		    if (animate !== true) {
		        $.extend(cssOptions, animateOptions);
		        inst.container.css(cssOptions);
		    } else {
		        inst.container.css(cssOptions);
		        inst.container.animate(animateOptions, 'fast');
		    }
		};
		
		this.bindEvents = function() {
			inst.dataContainer.find('[rel]').each(function(idx) {
				var rel = $(this).attr('rel');
				if (rel.indexOf('action:') == 0) {
					rel = rel.split(':');
					if (typeof inst[ rel[1] ] != "undefined" && $.isFunction( inst[ rel[1] ] )) {
						$(this).click(inst[ rel[1] ]);
					}
				}
			});
			$(window).resize(function(){inst.center(true);});
			if( inst.settings.canClose == true ) {
				inst.dataContainer.find(':reset').click(inst.close);
				inst.closeBtn.click(inst.close);
                if(inst.canCloseByOverlay === true) {
                    inst.overlay.click(function() {
                        if( $(this).is(':visible') ) {
							inst.close();
                        }
                    });
                }
			}
		};
		
		this._beforeClose = function() {
            beforeClose = inst.settings.beforeClose;
            if ($.isFunction(beforeClose)) {
                beforeClose.call(this);
            }
    };
        
        this._afterClose = function(callback) {
            var cbFunction = function(){},
                afterClose = inst.settings.afterClose;
                
            if ($.isFunction(callback)) {
                cbFunction = callback;
            } else if ($.isFunction(afterClose)) {
                cbFunction = afterClose;
            }
            
            cbFunction.call(this);
        };
		
		this.close = function(callback) {
			inst._beforeClose.call(this);
			
			inst.container.fadeOut('fast',function() {
				inst.dataContainer.html('');
			});
			inst.overlay.fadeOut('fast', function() {
			    inst._afterClose.call(this, callback);
		    });
            $('body').css({
                'position': '',
                'overflow-x': inst.bodyOverflow.x, //overflow-Attr wieder auf Ursprung setzen
                'top': '',
                'width': ''
            }).scrollTop(inst.bodyScrollTop);
		};
		
		this.hide = function(callback) {
			var callback = ($.isFunction(callback) ? callback : function(){});
			inst.container.fadeOut('fast');
			inst.overlay.fadeOut('fast', callback);
            $('body').css('overflow-x', inst.bodyOverflow.x); //overflow-Attr wieder auf Ursprung setzen
		};
		
		this.show = function(callback) {
			var callback = ($.isFunction(callback) ? callback : function(){});
			inst.showBackground();
			inst.container.fadeIn('slow', callback);
			inst.center();
			inst.bindEvents();
		};
    
        this.initScrollBar = function(){
            // init scrollbar - css klassen sind fest, da sie im tinyscrollbar nicht umbenannt werden koennen
            if( inst.settings.scroll === true ) {
                inst.containerWrap.tinyscrollbar();
                inst.containerWrap.hover(function(){
                    inst.containerContentScrollbar.fadeIn('slow');
                }, function(){
                    inst.containerContentScrollbar.fadeOut('slow');	
                });
				if( inst.containerWrap.children('h2').length ) {
					inst.containerWrap.find('.pinbox h2').remove();
				} else {
					inst.containerWrap.find('.pinbox h2').prependTo(inst.containerWrap);
				}
                inst.updateScrollBar();
            }
        };
    
        this.updateScrollBar = function() {
            if( inst.settings.scroll === true ) {
                inst.containerWrap.tinyscrollbar_update();
                var images = inst.containerWrap.find('img');
				var imagesCount = images.length;
				if( imagesCount > 0 ) {
                    images.load(function(){
                        if( --imagesCount == 0 ) {
                            inst.containerWrap.tinyscrollbar_update();
                        }
                    });
                }
            }
        };
		
		init(settings);
	};
	
	$.extend({"simplePopIn" : simplePopIn});
	var cssFile = myty.basePath+'/styles/simplepopin.css';
	if (!$('link[href="'+cssFile+'"]').length) $('<link href="'+cssFile+'" rel="stylesheet" type="text/css" />').prependTo("head");
})(jQuery);