(function($){
	
	function click(e) {
		
		var t = $(this),
			isUnpin = t.hasClass('unpin'),
			row = t.parents('tr'),
			select = $('select[name=with-selected]'),
			apply = $('div.actions>input[type=submit]');
		
		// selects the row
		row.click();
		
		// set action
		select.val(isUnpin ? 'unpin' : 'pin');
		
		// submit form
		apply.click();
	};
	
	function init() {
		
		$('td>img.pin').click(click);
		
	};
	
	$(init);
	
})(jQuery);