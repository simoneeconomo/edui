(function($){
	$(document).ready(function(){

	var MODE_EQUALS   = 0,
	    MODE_CONTAINS = 1,
	    MODE_EMPTY    = 2;

	var Filtering = {

		init: function() {
			Symphony.Language.add({ 'Filters': false });

			$('h2:first')
			.append(' <a class="button filtering"/>')
				.find('a.filtering:first')
				.text(Symphony.Language.get('Filters'));

			if (!Filtering.hasGETparams()) {
				$('.filters:first').hide();
			}

			$('.filters .filter select').sb();

			$('.filters .filter select.key, .filters .filter select.mode').each(function() {
				Filtering.prepareSelectBox.apply(this);
			});

			/* Handlers */

			$('h2:first .filtering:first').click(function() {
				var panel = $('.filters:first');

				panel[panel.is(':visible') ? 'slideUp' : 'slideDown']('fast');
			});

			$('.filters .filter select.key, .filters .filter select.mode').bind('change', function() {
				Filtering.prepareSelectBox.apply(this);
			});

			$('.filters input[type=submit]').bind('click keypress', function(event) {
				if (!event.keyCode || event.keyCode == '13') {
					$('form').unbind('submit');
				}
			});

			$('.filters .filter input[type=text]').bind('keypress', function(event) {
				if (event.keyCode == '13') {
					$('.filters > input[type=submit]').click();
				}
			});
		},

		hasGETparams: function() {
			var url = window.location.toString();

			return (url.indexOf("?filter=") != -1);
		},

		getGETparams: function() {
			var url = window.location.toString();
			var params = url.split("?filter=")[1].split(";");

			var filters = [];

			for(var i in params) {
				if (params[i] == "") continue;

				var h = params[i].split(":");
				var key, mode, value;

				if (h[0].charAt(0) == "!") {
					key = h[0].substr(1);
					mode = MODE_EMPTY;
				}
				else if (h[0].charAt(h[0].length-1) == "*") {
					key = h[0].substr(0, h[0].length-1);
					mode = MODE_CONTAINS;
				}
				else {
					key = h[0];
					mode = MODE_EQUALS;
				}

				value = h[1];

				filters.push({
					"key":    key,
					"mode":   mode,
					"value":  value
				});
			}

			return filters;
		},

		prepareSelectBox: function() {
			var that = this;
			var parent = $(that).parents('.filter');

			if ($(that).is('.key')) {
				var mode = parent.find('select.mode');

				if (($(that).val() == 'source' || $(that).val() == 'pages') && mode.val() == 0) {

					$.ajax({
						type: 'GET',
						url: Symphony.WEBSITE + '/symphony/extension/edui/fetch',
						data: { 'type': $(that).val() },
						dataType: 'json',
						success: function(result) {

							var old = parent.find('input.value:first, select.value:first');
							var select = $('<select class="value" />')
								.attr('name', old.attr('name'));

							for(var i in result) {
								select.append($('<option />')
									.text(result[i]["title"] || result[i]["name"])
									.attr('value', result[i]['id'])
								);
							}

							if (Filtering.hasGETparams()) {
								var data = Filtering.getGETparams();
								var index = $(select).attr('name').replace("filter-value-", "");

								select.find('option').each(function() {
									if (data[index] && $(this).attr('value') == data[index].value) {
										return $(this).attr('selected', true);
									}
								});
							}

							old.replaceWith(select);
							parent.find('.selectbox.value:first').remove();
							select.sb();

						}
					});

				}
				else {
					if (parent.find('input.value:first').length == 1) return;

					var old = parent.find('select.value:first');
					var input = $('<input class="value" />').attr({
						'name': old.attr('name'),
						'value': old.find('option:selected').text()
					});

					old.replaceWith(input);
					parent.find('.selectbox.value:first').remove();
				}
			}

			else { // if ($(that).is('.mode'))
				var key = parent.find('select.key');

				if (key.val() == 'source' || key.val() == 'pages') {
					key.change();
				}

				parent.find('input[type=text]:first')
					.attr('disabled', $(that).val() == 2);

			}
		}

	};

	Filtering.init();

	});
})(jQuery);
