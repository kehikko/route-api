$(document).ready(function() {
	$('#api-test-call-select').change(function() {
		$('.api-test-call-form').hide();
		var call = $(this).val();
		$('#' + call).show();
		window.location.hash = '#call:' + call;
	});
	$('.api-test-call-selector').change(function() {
		var child = $(this).attr('child');
		var val = $(this).val();
		var first = null;
		if (child !== undefined) {
			console.log('find child '+ child);
			$('.api-test-call-selector[name=' + child + '] option[parent]').each(function() {
				var parent_val = $(this).attr('parent');
				if (val == parent_val) {
					$(this).show();
					if (first === null) {
						first = $(this).val();
					}
				} else {
					$(this).hide();
				}
			});
			$('.api-test-call-selector[name=' + child + ']').val(first);
		}
	});
	$('.api-test-call-action-get').click(function() {
		apiTestCall('GET');
	});
	$('.api-test-call-action-put').click(function() {
		var call = $('#api-test-call-select').val();
		var content = {};
		var auto = $('#api-test-call-var-auto-increment').val();
		var random = $('#api-test-call-var-random').val();
		$('.api-test-call-put-value-' + call).each(function() {
			var name = $(this).attr('name').split(':');
			var value = $(this).val();
			_content = content;
			if (value.length > 0) {
				for (var i = 0; i < (name.length - 1); i++) {
					if (_content[name[i]] === undefined) {
						_content[name[i]] = {};
					}
					_content = _content[name[i]];
				}
				value = value.replace('%auto_increment%', auto);
				value = value.replace('%random%', random);
				_content[name[i]] = value;
			}
		});
		$('.api-test-call-put-checkbox-' + call).each(function() {
			var name = $(this).attr('name').split(':');
			var values = [];
			$(this).find('input[type=checkbox]:checked').each(function() {
				values.push($(this).val());
			});
			_content = content;
			if (values.length > 0) {
				for (var i = 0; i < (name.length - 1); i++) {
					if (_content[name[i]] === undefined) {
						_content[name[i]] = {};
					}
					_content = _content[name[i]];
				}
				_content[name[i]] = values;
			}
		});
		apiTestCall('PUT', content);
	});
	$('.api-test-call-action-delete').click(function() {
		apiTestCall('DELETE');
	});
	apiTestGenerateVariables();
	$('.api-test-call-selector').change();
	if (window.location.hash)
	{
		var call = window.location.hash.slice(1).split(':');
		if (call[1])
		{
			$('#api-test-call-select').val(call[1]);
			$('#api-test-call-select').change();
		}
	}
	$('.api-form').submit(function(event) {
		event.preventDefault();
		apiFormSend(this);
	});
});

function apiTestCall(method, content) {
	var call = $('#api-test-call-select').val();
	var slugs = window['api_test_call_js_slugs_' + call];
	var url = '';
	var auto = $('#api-test-call-var-auto-increment').val();
	var random = $('#api-test-call-var-random').val();
	for (var i = 0; i < slugs.length; i++) {
		var slug = slugs[i];
		if (slug.type == 'static') {
			url += '/' + slug.default;
		} else {
			var error = false;
			var input = $('#api-test-call-slug-' + call + '--' + slug.name).val();
			if (slug.optional) {
				if (input === undefined) {
					break;
				} else if (input.length < 1) {
					break;
				}
			}
			if (input === undefined) {
				error = true;
			} else if (input.length < 1) {
				error = true;
			}
			if (error) {
				alert('Missing value for required slug ' + slug.name);
				return;
			} else {
				input = input.replace('%auto_increment%', auto);
				input = input.replace('%random%', random);
				url += '/' + input;
			}
		}
	}

	/* disable buttons */
	$('.api-test-call-action').prop('disabled', true);
	/* show loader */
	$('.api-test-call-action-loading').show();
	/* hide request time */
	$('.api-test-call-action-time-container').hide();

	url += '/';
	var output = $('#api-test-call-response-' + call);
	output.empty();
	output.append(method + ' ' + url + "\n");
	/* save start time */
	var time_start = 0;
	var time_end = 0;
	$.ajax({
		method: method,
		url: url,
		data: JSON.stringify(content),
		processData: false,
		global: false,
		beforeSend: function() { time_start = new Date().getTime(); }
	}).fail(function(jqxhr) {
		time_end = new Date().getTime();
		var jsonstr = null;
		try {
			jsonstr = JSON.stringify(JSON.parse(jqxhr.responseText), null, 2);
		} catch (err) {
			jsonstr = jqxhr.responseText;
		}
		output.append('Response code: ' + jqxhr.status + ' ' + jqxhr.statusText + "\n");
		output.append("Response content:\n" + jsonstr);
		output.css('color', '#900');
	}).done(function(data, statusText, jqxhr) {
		time_end = new Date().getTime();
		var jsonstr = JSON.stringify(data, null, 2);
		output.append('Response code: ' + jqxhr.status + ' ' + jqxhr.statusText + "\n");
		output.append("Response content:\n" + jsonstr);
		output.css('color', '#050');
	}).always(function() {
		/* enable buttons */
		$('.api-test-call-action').prop('disabled', false);
		/* hide loader */
		$('.api-test-call-action-loading').hide();
		/* show request time */
		var time = time_end - time_start;
		$('.api-test-call-action-time').text(time + ' ms');
		$('.api-test-call-action-time-container').show();
	});
	apiTestGenerateVariables();
}

function apiTestGenerateVariables() {
	var auto = parseInt($('#api-test-call-var-auto-increment').val());
	var random = Math.random().toString(36).substring(3, 11);
	if (isNaN(auto)) {
		auto = 0;
	}
	$('#api-test-call-var-random').val(random);
	$('#api-test-call-var-auto-increment').val(auto + 1);
}

function apiFormSend(form) {
	var url = $(form).attr('action');
	var data = {};
	$(form).find('[name]').filter(':input').each(function() {
		var name = $(this).attr('name');
		var value = $(this).val();
		data[name] = value;
	});
	$.ajax({
		method: 'POST',
		url: url,
		data: data
	}).done(function() {
		BootstrapDialog.show({
			title: 'OK',
			message: 'OK'
		});
	});
}