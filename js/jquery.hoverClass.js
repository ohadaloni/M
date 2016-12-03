jQuery.fn.hoverClass = function(className) {
	return this.each(function() {
			jQuery(this).hover(
				function(){jQuery(this).addClass(className);},
				function(){jQuery(this).removeClass(className);}
			);
	});
};

