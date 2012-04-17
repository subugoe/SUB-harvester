// Ruft die Startseite auf.
function gotoStart() {
	window.location = 'index.php';
}


// Ruft einen Datensatz zum Bearbeiten auf.
function edit(id) {
	var form = document.getElementById('command');
	form.do.value = 'edit_oai_source';
	form.id.value = id;
	form.submit();
}


// Ruft einen Datensatz zur Ansicht auf.
function show(id) {
	var form = document.getElementById('command');
	form.do.value = 'show_oai_source';
	form.id.value = id;
	form.submit();
}


// Ruft die Löschseite einer OAI-Quelle auf.
function remove(id) {
	var form = document.getElementById('command');
	form.do.value = 'delete_oai_source';
	form.id.value = id;
	form.submit();
}


//
function checkbox_checker(name) {
	if (document.forms[0][name].checked) {
		return "1";
	} else {
		return "0";
	}
}


// Ruft die Previewseite mit den entsprechenden Parametern auf
function preview(setSpec, setName, validationFunction) {
	if (validationFunction('preview')) {
		// URL mit GET-Variablen erzeugen
		var url = "index.php";

		url += "?do=preview_oai_set"
		url += "&name=" + encodeURIComponent(document.forms[0].name.value);
		url += "&url=" + encodeURIComponent(document.forms[0].url.value);
		url += "&country=" + encodeURIComponent(document.forms[0].country.options[document.forms[0].country.selectedIndex].value);

		url += ( (setSpec == null) ? "" : "&setSpec=" + encodeURI(setSpec) );
		url += ( (setName == null) ? "" : "&setName=" + encodeURI(setName) );
/*
		url += "&i_cre=" + checkbox_checker('index_creator');
		url += "&i_con=" + checkbox_checker('index_contributor');
		url += "&i_pub=" + checkbox_checker('index_publisher');
		url += "&i_dat=" + checkbox_checker('index_date');
		url += "&i_ide=" + checkbox_checker('index_identifier');
		url += "&i_rel=" + checkbox_checker('index_relation');
		url += "&i_sub=" + checkbox_checker('index_subject');
		url += "&i_des=" + checkbox_checker('index_description');
		url += "&i_sou=" + checkbox_checker('index_source');

		url += "&v_cre=" + checkbox_checker('view_creator');
		url += "&v_con=" + checkbox_checker('view_contributor');
		url += "&v_pub=" + checkbox_checker('view_publisher');
		url += "&v_dat=" + checkbox_checker('view_date');
		url += "&v_ide=" + checkbox_checker('view_identifier');
*/
		url += "&identifier_alternative=" + encodeURIComponent(document.forms[0].identifier_alternative.value);
		url += "&identifier_filter=" + encodeURIComponent(document.forms[0].identifier_filter.value);
		url += "&identifier_resolver=" + encodeURIComponent(document.forms[0].identifier_resolver.value);
		url += "&identifier_resolver_filter=" + encodeURIComponent(document.forms[0].identifier_resolver_filter.value);

		// Immer ein neues Fenster, daher Array
		preview[preview_counter] = window.open(url, "_blank");
		preview[preview_counter].focus();
		preview_counter++;
	}
}


// Verhindert, dass alle Sets und einzelne Sets gleichzeitig angewählt werden können.
function validateSets() {
	var jPseudoCheckbox = jQuery('.sets.pseudo input:checkbox');
	if (jPseudoCheckbox.length > 0) {
		if (jPseudoCheckbox[0].checked) {
			jQuery('.sets.realSets input:checkbox').attr({'disabled': 'disabled'});
		}
		else {
			jQuery('.sets.realSets input:checkbox').removeAttr('disabled');
		}
	}
}



// Zur Navigation in den Logs
// TODO: Check id parameter i
function navigate(start) {
	var limit = $('#max_hit_display').val();
	var status = $('#show_status_select').val();
	var type =  $('#show_type_select').val();

	var AJAXData = {
		'do': 'display_log',
		'start': start,
		'limit': limit,
		'status': status,
		'type': type
	};

	if (document.getElementById('id') && document.getElementById('id').value) {
		AJAXData['id'] = document.getElementById('id').value;
	}

	$.ajax({
		url: "index.php",
		type: "POST",
		data: AJAXData,
	  	success: function(html){
			$('div.log').replaceWith($(html));
	  	}
	});

	if (start === limit) {
		$('#max_hit_display').attr('disabled', 'disabled');
		$('#show_status_select').attr('disabled', 'disabled');
		$('#show_type_select').attr('disabled', 'disabled');
		$('#goto_first_page').removeAttr('disabled');
	}

	if (start === 0) {
		$('#max_hit_display').removeAttr('disabled');
		$('#show_status_select').removeAttr('disabled');
		$('#show_type_select').removeAttr('disabled');
		$('#goto_first_page').attr('disabled', 'disabled');
	}
}


// Setzt Suchwerte auch für die versteckten Felder,
// damit die Anzeige im der OAI-Quellen-Liste richtig funktioniert.
// (für die Startseite)
function setValues() {
	document.getElementById("form_search").filter_name.value = document.getElementById("form_search").filter_name_input.value;
	document.getElementById("form_search").filter_url.value = document.getElementById("form_search").filter_url_input.value;
	document.getElementById("form_search").filter_bool.value = document.getElementById("form_search").filter_bool_select.options[document.getElementById("form_search").filter_bool_select.selectedIndex].value;
}
