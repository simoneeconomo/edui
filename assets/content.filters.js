(function($){
	$(document).ready(function(){

		$('h2:first')
		.append(' <a class="button filtering"/>')
			.find('a.filtering:first')
			.click(function() {
				var panel = $('.filters:first');

				panel[panel.is(':visible') ? 'slideUp' : 'slideDown']('fast');
			})
			.text('Filtering');

		$('.filters:first').hide();

		$('.filters .filter select').sb();

		$('.filters input[type=submit]').bind('click keypress', function(event) {

			if (!event.keyCode || event.keyCode == '13') {
				$('form').unbind('submit');
			}

		});

		$('.filters input[type=text]:first').bind('keypress', function(event) {

			if (event.keyCode == '13') {
				$('.filters > input[type=submit]').click();
			}

		});

	});
})(jQuery);
