// Datepicker

jQuery(function() {
	var jFrom = jQuery('#from');
	if (jFrom.length > 0) {
		jFrom.datepicker();
	}

});

$(function($){
    $.datepicker.regional['de'] = {clearText: 'löschen', clearStatus: 'aktuelles Datum löschen',
            closeText: 'schließen', closeStatus: 'ohne Änderungen schließen',
            prevText: '&#x3c;zurück', prevStatus: 'letzten Monat zeigen',
            nextText: 'Vor&#x3e;', nextStatus: 'nächsten Monat zeigen',
            currentText: 'heute', currentStatus: '',
            monthNames: ['Januar','Februar','März','April','Mai','Juni',
            'Juli','August','September','Oktober','November','Dezember'],
            monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun',
            'Jul','Aug','Sep','Okt','Nov','Dez'],
            monthStatus: 'anderen Monat anzeigen', yearStatus: 'anderes Jahr anzeigen',
            weekHeader: 'Wo', weekStatus: 'Woche des Monats',
            dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
            dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
            dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
            dayStatus: 'Setze DD als ersten Wochentag', dateStatus: 'Wähle D, M d',
            dateFormat: 'yy-mm-dd', firstDay: 1,
            initStatus: 'Wähle ein Datum', isRTL: false};
    $.datepicker.setDefaults($.datepicker.regional['de']);
});


// ************************************************
// Validierung des Eingabeformulars für OAI-Quellen

$(document).ready(function() {
    validateSets();
});

preview_counter = 1;

function validate (mode) {
	var result = false;

	if (!jQuery('form.edit').hasClass('new')
		&& (jQuery('form.edit')[0].elements['edit_abort'].value == 1
			|| jQuery('form.edit')[0].elements['do'].value === 'list_oai_sources'
			|| jQuery('form.edit')[0].elements['do'].value === 'delete_oai_source')){
		// Änderungen sollen nicht gespeichert werden
		result = true;
	}
	else {
		var valid_config = 0;
		var valid_set = false;

		// Name
	    if (document.getElementById("name").value.length > 0) {
			valid_config++;
			document.getElementById("label_name").style.color = "";
		}
		else {
			document.getElementById("label_name").style.color = "red";
		}

		// Land (wird für Preview eigentlich nicht benötigt, aber sollte man ruhig setzen :-)
		if (document.getElementById("country").selectedIndex > 0) {
			valid_config++;
			document.getElementById("label_country").style.color = "";
		}
		else {
			document.getElementById("label_country").style.color = "red";
		}

		if (mode === 'preview') {
			// Für Preview nicht notwendig
			valid_config++;
		}
		else {
			// Harvest-Rhythmus
			if (document.getElementById("harvest_period").value >= 0 && document.getElementById("harvest_period").value != "") {
				valid_config++;
				document.getElementById("label_harvest_period").style.color = "";
			}
			else {
				document.getElementById("label_harvest_period").style.color = "red";
			}
		}

		// Alternativer Link
		if (document.getElementById("identifier_alternative").value != "") {
			valid_config++;
			document.getElementById("label_identifier_alternative").style.color = "";
		}
		else {
			document.getElementById("label_identifier_alternative").style.color = "red";
		}

		// Identifier-Filter
		if (document.getElementById("identifier_filter").value != "") {
			valid_config++;
			document.getElementById("label_identifier_filter").style.color = "";
		}
		else {
			document.getElementById("label_identifier_filter").style.color = "red";
		}

		// Soweit mögilch mindestens ein Set ausgewählt?
		if (document.getElementById("noSetHierarchy")) {
			// Die Quelle unterstützt keine Sets.
			valid_set = true;
		}
		else {
			valid_set = (jQuery('.sets input:checked').length > 0);
		}

		// Ausgabe
		var result = false;
		if (valid_config === 5) {
			if (mode === 'preview') {
				result = true;
			}
			else if (valid_set) {
				if (checkFromDate()) {
					$("#label_from").css('color', '');
					result = true;
				}
				else {
					$("#label_from").css('color', 'red');
					alert("Bitte den Wert vom Datumstool eingetragenen Wert nicht ändern.");
				}
			}
			else {
				alert("Bitte mindestens ein Set zum Harvesten auswählen.");
			}
	    }
	    else {
			alert("Bitte alle rot markierten Felder im Bereich Allgemeine Einstellungen ausfüllen.");
	    }
	}

	return result;
}



// Überprüft, ob Daten aus dem Index entfernt werden müssen, da ein neues "from" gesetzt wurde
function checkFromDate() {
	// ggf. Markierung aufheben
	$("#label_from").css('color', '');

	// Datumsangaben einlesen
	var new_from_string = $('#from').val();
	var current_from_string = $('#current_from_db').val();

	// Für die Anzeige
	var monate = new Array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");

	// Datumsangaben einlesen soweit vorhanden
	var new_from_date;
	if (new_from_string && new_from_string.length === 10) {
		new_from_date = new Date(new_from_string.substr(0,4), new_from_string.substr(5,2), new_from_string.substr(8,2), 0, 0, 0);
	}
	var current_from_date;
	if (current_from_string && current_from_string.length === 10) {
		current_from_date = new Date(current_from_string.substr(0,4), current_from_string.substr(5,2), current_from_string.substr(8,2), 0, 0, 0);
	}

	// Evaluation, ob eine Zählung der zu löschenden Indexeinträge nötig ist
	var need_index_count = false;

	// Sind beide Daten vorhanden? Vergleichen
	if (new_from_date && current_from_date) {
		if (new_from_date > current_from_date) {
			// Das neue from-Datum ist neuer, Prüfung ist nötig
			need_index_count = true;
		}
	}
	else if (new_from_date && !current_from_date) {
		// Bisher keine from-Datum gesetzt, Prüfung nötig
		need_index_count = true;
	}

	if (need_index_count) {
		// Prüfung ist nötig

		// Tag vor "from" ermitteln (leider kompliziert mit Milisekunden... D:)
		var delete_date = new Date();

		var delete_date_int = new_from_date - (24 * 60 * 60 * 1000);

		delete_date.setTime(delete_date_int);

		var day = "" + delete_date.getDate();
		if (day.length < 2) {
			day = "0" + day;
		}

		var month = "" + delete_date.getMonth();
		if (month.length < 2) {
			month = "0" + month;
		}

		var delete_date_string = "" + delete_date.getFullYear() + "-" + month + "-" + day;

		// Für PHP Script speichern
		$("#new_from_day_before").val(delete_date_string);

		// Anzahl der zu löschenden Datensätze ermitteln
		var solr_xml_reply = $.ajax({
			url: "http://www.eromm.org/solr/select",
			async: false,
			type: "GET",
			data: 'version=2.2&rows=0&q=oai_repository_id:' + $('#id').val() + ' +oai_datestamp:[* TO ' + delete_date_string + 'T23:59:59Z]',
			dataType: "xml"
			}).responseText;

		var result_count = $(solr_xml_reply).find("result").attr("numFound");

		// Sind Einträge zu löschen, bestätigen lassen.
		if (result_count > 0) {
			save = confirm("Durch die Änderung des Startdatums »Harvesten ab« auf den " + new_from_date.getDate() + ". " + monate[new_from_date.getMonth()-1] + " " + new_from_date.getFullYear() + " werden " + result_count + " Indexeinträge gelöscht. Änderungen trotzdem speichern?");
			return save;
		}
		// Keine Indexeinträge betroffen, Nutzer wird nicht gefragt.
		return true;
	}
	else if (!new_from_date && current_from_date) {

		// Anzahl der zu löschenden Datensätze ermitteln
		var solr_xml_reply = $.ajax({
			url: "http://www.eromm.org/solr/select",
			async: false,
			type: "GET",
			data: "version=2.2&rows=0&q=oai_repository_id:"+ $("#oai_repository_id").val(),
			dataType: "xml"
			}).responseText;

		var result_count = $(solr_xml_reply).find("result").attr("numFound");

		save = confirm("Der »Harvesten ab« Zeitpunkt wurde gelöscht. Im Index befinden sich zurzeit "+ result_count +" Einträge ab dem "+ current_from_date.getDate() +". "+ monate[current_from_date.getMonth()-1] + " "+ current_from_date.getFullYear() +". Soll die Quelle ohne Startzeitpunkt neu geharvested werden?");

		if (!save) {
			$("#config_from").val(current_from_string);
			alert("Voheriger »Harvesten ab« Zeitpunkt wurde wieder eingetragen.");
			return false;
		} else {
			return true;
		}

	}
	else if (new_from_string && new_from_string.length != 0 && new_from_string.length != 10 ) {
		// Markieren
		$("#label_from").css('color', 'red');
		// Wert zurücksetzen
		$("#from").val(current_from_string);
		alert("Ungültige Eingabe bei »Harvesten ab«. Voheriger Zeitpunkt wurde wieder eingetragen.");

		return false;
	}
	else {
		// Kein "from" eingengeben, kein "from" bisher in der Datenbank, ohne Bestätigung speichern
		return true;
	}
}
