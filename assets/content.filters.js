(function($){
	$(document).ready(function(){

		$('.filters .filter select').sb();

		$('.filters input[type=submit]').bind('click keypress', function(event) {

			if (!event.keyCode || event.keyCode == '13') {
				$('form').unbind('submit');
			}

		});

		$('.filters input[type=text]').bind('keypress', function(event) {

			if (event.keyCode == '13') {
				$('.filters > input[type=submit]').click();
			}

		});

	});
})(jQuery);
