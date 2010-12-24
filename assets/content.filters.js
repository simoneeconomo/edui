(function($){
	$(document).ready(function(){

		/* Init */

		Symphony.Language.add({ 'Filters': false });

		$('h2:first')
		.append(' <a class="button filtering"/>')
			.find('a.filtering:first')
			.click(function() {
				var panel = $('.filters:first');

				panel[panel.is(':visible') ? 'slideUp' : 'slideDown']('fast');
			})
			.text(Symphony.Language.get('Filters'));

		if (window.location.toString().indexOf("?filter=") == -1) {
			$('.filters:first').hide();
		}

		$('.filters .filter select').sb();

		$('.filters .filter').each(function() {
			if ($(this).find('select.mode:first').val() == 2)
				$(this).find('input[type=text]:first')
					.attr('disabled', true);
		});

		/* Handlers */

		$('.filters .filter select.mode:first').bind('change', function() {

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
