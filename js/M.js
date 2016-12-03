/*------------------------------------------------------------*/
$tabs = null ;
theTab = null ;
/*------------------------------------------------------------*/
$(function() {
	initTabs();
	mBindAll(document);
});
/*------------------------------------------------------------*/
function mBind()
{
	mBindAll(this);
}
/*------------------------------------------------------------*/
function containingTab(obj)
{
	cur = $(obj).parent();

	while ( true ) {
		if ( ! cur || cur.size() == 0 ) {
			return(null);
		}
		if ( $(cur).hasClass("ui-tabs-panel") )
			return(cur);
		cur = cur.parent();
	}
}
/*------------------------------------------------------------*/
function mBindAll(context)
{
	if ( ! context )
		context = document;

	// hijax all links to load to their tab
	// except those marked with a noHijax class
	$('a', context).not(".noHijax").click(function() {
		theTab = containingTab(this);
		if ( theTab ) {
			theTab.html('<img border="0" src="images/loading.gif" />');
			href = this.href;
			if ( href.indexOf("?") >= 0 )
				href += "&";
			else
				href += "?";
			href += "Ajax=Ajax";
			theTab.load(href, mBind);
			return(false);
		}
		return(true);
	});

	mPaintRows(context);

	$(".mRow", context).click(function(){
		$(".mRow").not(this).removeClass("keepHilited");
		$(this).addClass("keepHilited");
	});
	if (typeof Mautocomplete != 'undefined' ) {
		$(".autocomplete", context).Mautocomplete();
	if (typeof editable != 'undefined' ) {
		$(".jeditable", context).editable("?className=Mcontroller&action=saveFieldInfo", {
			placeholder:'',
			tooltip:'Click to Edit'
		});
		$(".jeditableText", context).editable('?className=Mcontroller&action==saveFieldInfo', { 
			type      : 'wysiwyg',
			tooltip   : 'Click to Edit...',
			onblur    : 'ignore', /* don't set this to cancel with wysiwyg */
			submit    : 'OK',
			placeholder    : '',
			cancel    : 'Cancel'
			});
	}

	if (typeof datepicker != 'undefined' ) {
		$(".datepicker", context).datepicker({
			changeYear: true 
		});
		$(".DOBdatepicker", context).datepicker({
			changeYear: true 
			,defaultDate: '-30y'
		});
	}

	if (typeof validate != 'undefined' ) {
		$(".validateForm", context).validate();
		/* must bind the validation separetaly to forms appearing on the same page */
		/* or the plugin gets confused */

		$(".validateForm", context).not(".noTabs", context).submit(submitFormInTab);
		$("#newRefererForm", context).submit(submitFormInTab);
		$("#searchRefererForm", context).submit(submitFormInTab);
	}


	/*	$("#formId #fieldName", context).change(alert("changed formId fieldName");	*/

	if (typeof showImage != 'undefined' )
		$(".showImage").showImage();
	if (typeof imgToolTip != 'undefined' )
		$(".imgToolTip").imgToolTip();
}
/*------------------------------------------------------------*/
function submitFormInTab()
{
	if ( ! $(this).valid() ) {
		return(false);
	}

	theTab = containingTab(this);
	if ( ! theTab )
		return(true);
	
	data = $(this).serialize();
	href = "?" + data ;
	theTab.html('<img border="0" src="images/loading.gif" />');
	theTab.load(href, mBind);
	return(false);
}
/*------------------------------------------------------------*/
function tabClicked()
{
	$('.ui-tabs-panel:visible').html('<img border="0" src="images/loading.gif" />');
	index = $tabs.tabs('option', 'selected');
	$tabs.tabs('load', index);
}
/*------------------------------*/
function afterTabLoad(event, ui)
{
	mBindAll(ui.panel);
}
/*------------------------------*/
function initTabs()
{
    $tabs = $(".tabs").tabs({
		load:afterTabLoad
		,spinner:'<img border="0" src="images/loadingSpinner.gif" />'
		});
	$(".tabLabel").click(tabClicked);
}
/*------------------------------------------------------------*/
