// ************************************************
// Javascript für Anzeige der OAI-Quellen

// Ruft eine OAI-Quelle zum Editieren auf.
function edit(id) {
	
	document.forms[0].do.value = 'edit_oai_source';
	document.forms[0].id.value = id;
}

// Ruft die Löschseite einer OAI-Quelle auf
function remove(id) {
	
	document.forms[0].do.value = 'delete_oai_source';
	document.forms[0].id.value = id;
}

// Ruft die Startseite auf
function gotoStart() {
	window.location = 'index.php';
}

//Zur Navigation in den Logs
function navigate(start) {
	
	$('#limit').val($('#max_hit_display').val());
	$('#status').val($('#show_status_select').val());
	$('#type').val($('#show_type_select').val());
	
	$('#log_display').empty();
	
	$.ajax({
		url: "log_display.php",
		type: "POST",
		data: "start="+start+"&limit="+$('#limit').val()+"&status="+$('#status').val()+"&type="+$('#type').val()+"&id="+$('#id').val(),
	  	success: function(html){
			$('#log_display').append(html);
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
}