(function(){
	var projectLinkCaptionVerification = function(element, settings) {
		var inst = this;
		
		this.element = element;
		this.form = null;
		this.titleElement = null;
		this.timeout = null;
		this.linkCaptionValue = '';
		this.config = {
			"titleSelector" : 'form :input[name=project_title],form :input[name=page_title]',
			"disableButton" : true,
			"initialValue" : ''
		};
		
		var init = function(settings) {
			$.extend(inst.config, settings);
		
			inst.element.bind('change keyup', function(){
				var val = $(this).val();
				if (val !== inst.linkCaptionValue){
					inst.linkCaptionValue = val;
					inst.verify($(this).val());
				}
			});
			inst.linkCaptionValue = inst.element.val();
			
			// try to find title form field
			var titleField = $( inst.config.titleSelector );
			if( titleField.length ) {
				inst.titleElement = titleField;
			}
			
			// get form
			var form = inst.element.parents('form');
			if( form.length ) {
				inst.form = form;
				
				if( inst.config.disableButton === true ) {
					var formSubmitButton = form.find(':submit');
					if( formSubmitButton.length && inst.config.initialValue !== '' && $.trim(inst.element.val()) !== inst.config.initialValue ) {
						formSubmitButton.attr("disabled", "disabled").addClass('mytyFormBtnDisabled');
					}
				}
			}
			
			// bind events
			if( inst.titleElement.length ) {
				inst.titleElement.bind('change keyup', function(){
					var val = $.trim($(this).val());
					val = val.toLowerCase()
							 .replace(/\s+/g, '-')
							 .replace(/ä/ig, 'ae')
							 .replace(/ö/ig, 'oe')
							 .replace(/ü/ig, 'ue')
							 .replace(/ß/ig, 'ss')
							 .replace(/[^a-z0-9\-]/ig, "").replace(/[\-]{2,10}/,'-');

					var currentVal = inst.element.val();
					if	(currentVal !== val && inst.linkCaptionValue === ''){
						inst.element.val(val);
						inst.verify(val);
					}
				});
			}
			
			// show error messages in edit mode
			inst.element.parents('.mytyFormBox').find(".mytyFormElementErrorMsg,.mytyFormElementSuccessMsg .tyEditable").parent().show();
			
			if( inst.config.initialValue !== '' && $.trim(inst.element.val()) !== inst.config.initialValue ) {
				inst.verify(inst.element.val());
			}
		};
		
		this.verify = function(caption) {
			inst.element.parents('.mytyFormBox').find(".mytyFormElementErrorMsg,.mytyFormElementSuccessMsg").hide();
			if (inst.timeout) clearTimeout(inst.timeout);
			inst.timeout = setTimeout(function(){
				caption = $.trim(caption).toLowerCase();
				if( caption.length && caption !== inst.config.initialValue ){
					$.ajax({
						url : myty.basePath + '/ajax/project/request.php',
						dataType : 'json',
						data : 'action=verifyLinkCaption&caption=' + encodeURIComponent(caption),
						success : function(response){
							var formSubmitButton = false;
							if( inst.form.length && inst.config.disableButton === true ) {
								formSubmitButton = inst.form.find(':submit');
							}
							if( response.captionExists ){
								if( formSubmitButton ) formSubmitButton.attr("disabled", 'disabled').addClass('mytyFormBtnDisabled');
								inst.element.parents('.mytyFormBox').find(".mytyFormElementErrorMsg").fadeIn('fast');
							} else {
								if( formSubmitButton ) formSubmitButton.removeAttr("disabled").removeClass('mytyFormBtnDisabled');
								inst.element.parents('.mytyFormBox').find(".mytyFormElementSuccessMsg").fadeIn('fast');
							}
						}
					});
				} else if( caption === inst.config.initialValue ){
					$("button[type='submit']").removeAttr("disabled").removeClass('mytyFormBtnDisabled');
				} else {
					$("button[type='submit']").attr("disabled", "disabled").addClass('mytyFormBtnDisabled');
				}			
			}, 1000);
		};
		
		init(settings);
	};
	
	$.fn.projectLinkCaptionVerification = function(settings) {
		return this.each(function(idx,element){
			var element = $(element);
			var instance = new projectLinkCaptionVerification(element, settings);
			element.data('projectLinkCaptionVerification', instance);
		});
	};
})(jQuery);