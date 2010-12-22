(function($){
	$(document).ready(function(){

		Symphony.Language.add({'Filtering': false});

		$('h2:first')
		.append(' <a class="button filtering"/>')
			.find('a.filtering:first')
			.click(function() {
				var panel = $('.filters:first');

				panel[panel.is(':visible') ? 'slideUp' : 'slideDown']('fast');
			})
			.text(Symphony.Language.get('Filtering'));

		$('.filters:first').hide();

		$('.filters .filter select').sb();

		$('.filters .filter select.mode:first').change(function() {
			$(this).parents('.filter')
				.find('input[type=text]:first')
				.attr('disabled', $(this).val() == 2);
		});

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
