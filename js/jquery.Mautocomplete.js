jQuery.fn.Mautocomplete = function() {
	return this.each(function() {
			jQuery(this).autocomplete("?className=Mmodel&action=autocomplete&id=" + this.id);
	});
};

