jQuery.fn.imgToolTip = function(size) {
	if ( ! size )
		size = 150;
	return this.each(function() {
		var title = this.title;
		var src = this.src;
		jQuery(this).tooltip({
			delay: 500,
			showURL: false,
			track: true,
			bodyHandler: function() {
				// creating the image from html makes ie scale it correctly
				// this did not work correctly in IE
				// opts = { src: this.src , height: 150 };
				// return($("<img/>").attr(opts));
				if ( title )
					ret = '<fieldset><legend>' + title + '</legend><img height="' + size + '" src="' + src + '" /></fieldset>';
				else
					ret = '<img height="150" src="' + src + '" />';
				return(ret);
			}
		});
	});
};
