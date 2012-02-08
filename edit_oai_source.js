// Datepicker

$(function() {
	$( "#config_from" ).datepicker();
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

function validate(mode){
	
	if (document.forms[0].elements['edit_abort'].value == 1 || document.forms[0].elements['do'].value == "list_oai_sources" ||  document.forms[0].elements['do'].value == "delete_oai_source") {
		// Änderungen sollen nicht gespeichert werden
		return true;
	
	} else {
	
		// Änderungen werden gespeichert, müssen geprüft werden
		var valid_config = 0;
		var valid_set = false;
		
		// Name
		if (document.getElementById("config_name").value.length > 0) {
			valid_config++;
			document.getElementById("label_name").style.color = "";
		} else {
			document.getElementById("label_name").style.color = "red";
		}
		
		// Land
		if (document.getElementById("config_country").selectedIndex > 0) {
			valid_config++;
			document.getElementById("label_country").style.color = "";
		} else {
			document.getElementById("label_country").style.color = "red";
		}
		
		if (mode == "preview") {
			// Für Preview nicht notwendig
			valid_config++;
		} else {
			// Harvest-Rhythmus
			if (document.getElementById("config_harvest").value >= 0 && document.getElementById("config_harvest").value != "") {
				valid_config++;
				document.getElementById("label_harvest").style.color = "";
			} else {
				document.getElementById("label_harvest").style.color = "red";
			}
		}
		
		// Alternativer Link
		if (document.getElementById("config_alternative").value != "") {
			valid_config++;
			document.getElementById("label_alternative").style.color = "";
		} else {
			document.getElementById("label_alternative").style.color = "red";
		}
	
		// Identifier-Filter
		if (document.getElementById("config_filter").value != "") {
			valid_config++;
			document.getElementById("label_filter").style.color = "";
		} else {
			document.getElementById("label_filter").style.color = "red";
		}
		
		
		// Soweit mögilch mindestens ein Set selektiert?
		if (document.getElementById("noSetHierarchy")) {
			// Die Quelle unterstützt keine Sets.
			valid_set = true;
		} else {		
			for(i = 1 ; document.getElementById("set"+i); i++) {
				if (document.getElementById("set"+i).checked) {
					valid_set = true;
					break;
				}
			}
		}
		
		// Ausgabe
		if (valid_config == 5) {
			
			if (mode == "preview") {
				return true;
			}
			
			if (valid_set) {		
				return checkFromDate();
			}
			alert("Bitte mindestens ein Set zum Harvesten selektieren");
			return false;
		} else {
			alert("Bitte alle rot markierten Felder ausfüllen (Allgemeine Einstellungen)!");
			return false;
		}
	}
}

// Ruft die Startseite auf
function gotoStart() {
	window.location = "index.php";
}

// Verhindert, dass alle Sets und einzelne Sets gleichzeitig angewählt werden können.

function validateSets() {
	set = 2;
	
	if(document.getElementById("set1").checked) {
		for (i = 2; document.getElementById("set"+i); i++) {
			document.getElementById("set"+i).setAttribute("disabled","disabled");
		}
	} else {
		for (i = 2; document.getElementById("set"+i); i++) {
			document.getElementById("set"+i).removeAttribute("disabled");
		}
	}
	
	//alert(document.getElementById("set"+ i).checked);
}

// Ruft die Löschseite einer OAI-Quelle auf
function remove(id) {
	document.forms[0].do.value = 'delete_oai_source';
}

function checkbox_checker(name) {
	if (document.forms[0][name].checked) {
		return "1";
	} else {
		return "0";
	}
}

// Ruft die Previewseite mit den entsprechenden Parametern auf
function preview(setSpec, setName) {
	
	
	if (validate('preview')) {
	
		// URL mit GET-Variablen erzeugen
		var url = "preview_oai_set.php";
		
		url += "?name=" + encodeURIComponent(document.forms[0].name.value);
		url += "&url=" + encodeURIComponent(document.forms[0].url.value);
		url += "&country=" + encodeURIComponent(document.forms[0].country.options[document.forms[0].country.selectedIndex].value);
		
		url += ( (setSpec == null) ? "" : "&setSpec=" + encodeURI(setSpec) );
		url += ( (setName == null) ? "" : "&setName=" + encodeURI(setName) );
	  
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

// Überprüft, ob Daten aus dem Index entfernt werden müssen, da ein neues "from" gesetzt wurde
function checkFromDate() {
	// ggf. Markierung aufheben
	$("#label_from").css('color', '');
	
	// Datumsangaben einlesen
	var new_from_string = $("#config_from").val();
	var current_from_string = $("#current_from").val();
	
	// Für die Anzeige
	var monate = new Array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");
	
	// Evaluation, ob eine Zählung der zu löschenden Indexeinträge nötig ist
	var need_index_count = false;
	
	// Datumsangaben einlesen soweit vorhanden
	if (new_from_string.length == 10) {
		var new_from_date = new Date(new_from_string.substr(0,4), new_from_string.substr(5,2), new_from_string.substr(8,2), 0, 0, 0);
	}
	if (current_from_string.length == 10) {
		var current_from_date = new Date(current_from_string.substr(0,4), current_from_string.substr(5,2), current_from_string.substr(8,2), 0, 0, 0);
	}

	// Sind beide Daten vorhanden? Vergleichen
	if (new_from_string.length == 10 && current_from_string.length == 10) {
		if (new_from_date > current_from_date) {
			// Das neue from-Datum ist neuer, Prüfung ist nötig
			need_index_count = true;
		}
	}
	
	if (new_from_string.length == 10 && current_from_string.length == 0) {
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
		$("#new_from_day_before_id").val(delete_date_string);
		
		// Anzahl der zu löschenden Datensätze ermitteln
		var solr_xml_reply = $.ajax({
			url: "http://www.eromm.org/solr/select",
			async: false,
			type: "GET",
			data: "version=2.2&rows=0&q=oai_repository_id:"+ $("#oai_repository_id").val() +" +oai_datestamp:[* TO "+ delete_date_string +"T23:59:59Z]",
			dataType: "xml"
			}).responseText;
		
		var result_count = $(solr_xml_reply).find("result").attr("numFound");
		
		// Sind Einträge zu löschen, bestätigen lassen.
		if (result_count > 0) {
			save = confirm("Durch die Änderung des Startdatums ('Harvesten ab') auf den "+ new_from_date.getDate() +". "+ monate[new_from_date.getMonth()-1] + " "+ new_from_date.getFullYear() +" werden " + result_count + " Indexeinträge gelöscht. Änderungen trotzdem speichern?" );
			return save;
		}
		// Keine Indexeinträge betroffen, Nutzer wird nicht gefragt.
		return true;
		
	} else if (new_from_string.length == 0 && current_from_string.length == 10) {
		
		// Anzahl der zu löschenden Datensätze ermitteln
		var solr_xml_reply = $.ajax({
			url: "http://www.eromm.org/solr/select",
			async: false,
			type: "GET",
			data: "version=2.2&rows=0&q=oai_repository_id:"+ $("#oai_repository_id").val(),
			dataType: "xml"
			}).responseText;
		
		var result_count = $(solr_xml_reply).find("result").attr("numFound");
		
		save = confirm("Der 'Harvesten ab' Zeitpunkt wurde gelöscht. Im Index befinden sich zurzeit "+ result_count +" Einträge ab dem "+ current_from_date.getDate() +". "+ monate[current_from_date.getMonth()-1] + " "+ current_from_date.getFullYear() +". Soll die Quelle ohne Startzeitpunkt neu geharvested werden?");
		
		if (!save) {
			$("#config_from").val(current_from_string);
			alert("Voheriger 'Harvesten ab' Zeitpunkt wurde wieder eingetragen.");
			return false;
		} else {
			return true;
		}
	
	} else if (new_from_string.length != 0 && new_from_string.length != 10 ) {
		// Markieren
		$("#label_from").css('color', 'red');
		// Wert zurücksetzen
		$("#config_from").val(current_from_string);
		alert("Ungültige Eingabe bei 'Harvsten ab'. Voheriger Zeitpunkt wurde wieder eingetragen.");

		return false;
		
	} else {
		// Kein "from" eingengeben, kein "from" bisher in den Datenbank, ohne Bestätigung speichern
		return true;
	}
}
