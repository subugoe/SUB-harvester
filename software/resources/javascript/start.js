// ************************************************
// Javascript für Startseite

// Setzt Suchwerte auch für die versteckten Felder, damit die Anzeige im der OAI-Quellen-Liste richtig funktioniert
function setValues() {
	document.getElementById("form_search").filter_name.value = document.getElementById("form_search").filter_name_input.value;
	document.getElementById("form_search").filter_url.value = document.getElementById("form_search").filter_url_input.value;
	document.getElementById("form_search").filter_bool.value = document.getElementById("form_search").filter_bool_select.options[document.getElementById("form_search").filter_bool_select.selectedIndex].value;
}


// Zur Navigation in den Logs
function navigate(start) {

	$('#limit').val($('#max_hit_display').val());
	$('#status').val($('#show_status_select').val());
	$('#type').val($('#show_type_select').val());

	$.ajax({
		url: "index.php",
		type: "POST",
		data: "do=log_display&start="+start+"&limit="+$('#limit').val()+"&status="+$('#status').val()+"&type="+$('#type').val(),
	  	success: function(html){
			$('#log_display').empty().append(jQuery(html).slice(0,5));
	  	}
	});

	if (start == $('#limit').val()) {
		$('#max_hit_display').attr('disabled', 'disabled');
		$('#show_status_select').attr('disabled', 'disabled');
		$('#show_type_select').attr('disabled', 'disabled');
		$('#goto_first_page').removeAttr('disabled');
	}

	if (start == 0) {
		$('#max_hit_display').removeAttr('disabled');
		$('#show_status_select').removeAttr('disabled');
		$('#show_type_select').removeAttr('disabled');
		$('#goto_first_page').attr('disabled', 'disabled');
	}

	return false;
}

//Ruft einen Datensatz zur Ansicht auf.
function show(id) {
	document.forms[0].id.value = id;
	document.forms[0].submit();
}



