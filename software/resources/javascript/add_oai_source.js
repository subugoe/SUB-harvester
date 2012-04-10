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

function validate_add(mode){

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
