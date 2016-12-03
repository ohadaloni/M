/*------------------------------------------------------------*/
function mPaintRows(context)
{
	$(".mRow", context).hoverClass("hilite");
	$(".mFormRow", context).hoverClass("hilite");
	$(".mHeaderRow", context).addClass("zebra0");
	$(".mFormRow:nth-child(odd)", context).addClass("zebra1");
	$(".mFormRow:nth-child(even)", context).addClass("zebra2");
	$(".mRow:nth-child(odd)", context).addClass("zebra1");
	$(".mRow:nth-child(even)", context).addClass("zebra2");
}

/*------------------------------------------------------------*/
function mConfirmDelete(className, tableName, id, warnMessage)
{
	var theTab;
	if ( ! confirm("Are you sure you want to delete this row from " + tableName + " (" + warnMessage + ") ?") )
		return;

	href = "?className=" + className + "&tableName=" + tableName + "&action=dbDelete&id=" + id ;
		
	if ( theTab ) {
		theTab.html('<img border="0" src="images/loading.gif />');
		theTab.load(href, mBind);
	} else
		document.location = href;
}
/*------------------------------------------------------------*/
