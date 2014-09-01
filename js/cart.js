var cart;
(function($){ 
	cart = {
		data : {},
		defaultData : {
			free_amount : 0,
			donation_amount : 0,
			incentive : {}
		},
		config : {
			onCartChanged : function(){},
			onCartClosed : function(){}
		},
		popin : null,
		projectLinkCaption : '',
		projectId : 0,
		branding : false,
		cookieData : null,
		init : function(){
			cart.resetData();
			if( typeof project !== "undefined" && project.link_caption ) {
				cart.projectLinkCaption = project.link_caption;
				cart.projectId = project.id;
			}
			$('a[data-action=checkout]').on('click',function(){ cart.callCheckout.apply(this, arguments); return false; });
			$(':submit[data-action=checkout]').parents('form').add('form[data-action=checkout]').on('submit', function(){ cart.callCheckout.apply(this, arguments); return false; });
			if( typeof sessionStorage === "undefined" && cart.cookieData === null ) {
				cart.loadCookieData();
			}
		},
		setOption : function(option, value) {
			cart.config[ option ] = value;
		},
		setProject : function(linkCaption) {
			cart.projectLinkCaption = linkCaption;
		},
		loadCookieData : function() {
			var requestData = [{
				"name" : 'action',
				"value" : 'loadItems'
			}];
			$.ajax({
				"url":myty.basePath+'/ajax/storage/request.php',
				"data":requestData,
				"dataType":'json',
				"type":'get',
				"async":false,
				"success":function(response){
					cart.cookieData = {};
					if( response.success && response.items ) {
						cart.cookieData = response.items;
					}
				}
			});
		},
		storeCookieData : function() {
			var requestData = [{
				"name" : 'action',
				"value" : 'storeItems'
			}];
			$.each(cart.cookieData, function(k){
				requestData.push({
					"name":"items["+k+"]",
					"value":this
				});
			});
			$.ajax({
				"url":myty.basePath+'/ajax/storage/request.php',
				"data":requestData,
				"dataType":'json',
				"type":'post'
			});
		},
		setCookie : function(name, value){
			var store = false;
			if( cart.cookieData === null || typeof cart.cookieData[ 'cart_'+name ] === "undefined" || cart.cookieData[ 'cart_'+name ] != value ) {
				if( cart.cookieData === null ) cart.cookieData = {};
				cart.cookieData[ 'cart_'+name ] = value;
				store = true;
			}
			if( store ) {
				if( typeof sessionStorage !== "undefined" ) {
					sessionStorage.setItem('cart_'+name, value);
				} else {
					cart.storeCookieData();
				}
			}
		},
		getCookie : function(name){
			if( typeof sessionStorage !== "undefined" ) {
				return sessionStorage.getItem('cart_'+name);
			} else {
				if( cart.cookieData === null ) cart.loadCookieData();
				return cart.cookieData[ 'cart_'+name ];
			}
		},
		addFreeAmount : function(amount, toActiveCart) {
			var toActiveCart = ( typeof toActiveCart !== "undefined" ? toActiveCart : true );
			cart.setCookie('free_amount_'+cart.projectId, amount);
			if( toActiveCart ) cart.data.free_amount = amount;
		},
		getFreeAmount : function() {
			var amt = cart.getCookie('free_amount_'+cart.projectId);
			//cart.data.free_amount = amt;
			return amt;
		},
		enableFreeAmount : function() {
			cart.setCookie('free_amount_active_'+cart.projectId, 1);
		},
		disableFreeAmount : function() {
			cart.setCookie('free_amount_active_'+cart.projectId, null);
		},
		isFreeAmountEnabled : function() {
			return ( cart.getCookie('free_amount_active_'+cart.projectId) == 1 );
		},
		addDonationAmount : function(amount, toActiveCart) {
			var toActiveCart = ( typeof toActiveCart !== "undefined" ? toActiveCart : true );
			cart.setCookie('donation_amount_'+cart.projectId, amount);
			if( toActiveCart ) cart.data.donation_amount = amount;
		},
		getDonationAmount : function() {
			var amt = cart.getCookie('donation_amount_'+cart.projectId);
			//cart.data.donation_amount = amt;
			return amt;
		},
		addIncentive : function(id, count, toActiveCart) {
			var toActiveCart = ( typeof toActiveCart !== "undefined" ? toActiveCart : true );
			cart.setCookie('incentive_'+id, count);
			if( toActiveCart ) cart.data.incentive[ id ] = count;
		},
		removeIncentive : function(id) {
			cart.setCookie('incentive_'+id, null);
			delete cart.data.incentive[ id ];
		},
		resetData : function() {
			cart.data = $.extend(true, {}, cart.defaultData);
		},
		getUrlParams : function(baseUrl) {
			var params = $.param(cart.data);
			return (baseUrl.indexOf('?') > 0?'&':'?')+params;
		},
		callCheckout : function() {
			var url;
			if( $(this).attr('href') ) {
				url = $(this).attr('href');
			} else if( $(this).attr('action') ) {
				url = $(this).attr('action');
			} else {
				if( cart.projectLinkCaption === '' ) {
					alert('project not set');
				}
				url = '/checkout/'+cart.projectLinkCaption;
			}
			if( url !== '' ) {
				var basketValid = false;
				if(cart.getDonationAmount() > 0 && typeof cart.getDonationAmount() !== 'undefined') basketValid = true;
				if(cart.getFreeAmount() > 0 && typeof cart.getFreeAmount() !== 'undefined') basketValid = true;
				for (var key in cart.data.incentive) {
					 if( cart.data.incentive[ key ] > 0 ) basketValid = true;
				}
				
				if(basketValid) {
					url += cart.getUrlParams(url);
					cart.popin = new $.simplePopIn({
						"popin_container_id": "checkout_container",
						"beforeClose":cart.config.onCartClosed
					});
					cart.popin.message('<iframe src="'+url+'" frameborder="0" width="680" height="550"/>');
					return true;
				}
			}
			return false;
		},
		closeCheckout : function() {
			cart.popin.close();
		}
	};
	$(function(){ cart.init.apply(this, arguments); });
})(jQuery);