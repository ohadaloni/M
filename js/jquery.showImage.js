/*------------------------------------------------------------*/

function mShowImg(screenImg)
{
	src = screenImg.src;
	// the image object passed gives the attributes of the image as it appears on screen
	// like in width=70, to get the real size, get the source again

	var img = new Image();
	img.src = src ;
	var height = img.height + 16;
	var width  = img.width + 16;
	/*	alert ('openng with '+width+'*'+height);	*/
	window.open(src, "mShowImg", "scrollbars=no,menubar=no,location=no,status=no,toolbar=no,width="+width+",height="+height);
}

/*------------------------------------------------------------*/

jQuery.fn.showImage = function() {
	return this.addClass("handCursor").
		fadeTo(0, 0.8).
		click(function(event) {
			mShowImg(event.target);
			}).
		hover(
			function(){jQuery(this).fadeTo(0, 1); },
			function(){jQuery(this).fadeTo(0, 0.8); }
		);
 };
 /*------------------------------------------------------------*/
