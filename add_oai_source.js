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
preview_counter = 1;

function validate(mode){

	var valid_config = 0;
	var valid_set = false;
	
	$("#label_from").css('color', '');
	
	// Name
    if (document.getElementById("config_name").value.length > 0) {
		valid_config++;
		document.getElementById("label_name").style.color = "";
	} else {
		document.getElementById("label_name").style.color = "red";
	}
	
	// Land (wird für Preview eigentilch nicht benötigt, aber sollte man ruhig setzen :-)
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
			if ($('#config_from').val().length == 10 || $('#config_from').val().length == 0) {
				return true; 
			} else {
				$("#label_from").css('color', 'red');
				alert("Bitte den Wert, der vom Datumstool eingetragen wird, nicht ändern!");
				return false;
			}
		}
		alert("Bitte mindestens ein Set zum Harvesten selektieren");
		return false;
    } else {
		alert("Bitte alle rot markierten Felder ausfüllen (Allgemeine Einstellungen)!");
        return false;
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
		preview[preview_counter] = window.open((url), "_blank");
		preview[preview_counter].focus();
		preview_counter++;
	}
}