
 jQuery.fn.fadeSliderToggle = function(settings) {
 	 settings = jQuery.extend({
		speed:500,
		easing : "swing"
	}, settings)
	
	caller = this
 	if(jQuery(caller).css("display") == "none"){
 		jQuery(caller).animate({
 			opacity: 1,
 			height: 'toggle'
 		}, settings.speed, settings.easing);
	}else{
		jQuery(caller).animate({
 			opacity: 0,
 			height: 'toggle'
 		}, settings.speed, settings.easing);
	}
}; 