// ************************************************
// Javascript für Anzeige der OAI-Quellen

document.onkeyup = checkFilter;

// Überträgt einfach das Formular mit allen Daten

function refresh() {
	setHiddenValues();
	document.forms[0].action = 'index.php#filter';
	document.forms[0].submit();
}

// Überträgt das Formular und ändert die Sortierung

function changeSort(sortby, sorthow) {

	document.forms[0].sortby.value = sortby;
	document.forms[0].sorthow.value = sorthow;

	setHiddenValues();
	document.forms[0].action = 'index.php#filter';
	document.forms[0].submit();
}

// Auf die erste Seite blättern (mit den Optionen)

function gotoFirstPage() {
	document.forms[0].start.value = 0;
	setHiddenValues();
	document.forms[0].submit();
}


// Filterung nach Name und / oder URL

function filter() {
	document.forms[0].filter_name.value = document.forms[0].filter_name_input.value;
	document.forms[0].filter_url.value = document.forms[0].filter_url_input.value;
	document.forms[0].filter_bool.value = document.forms[0].filter_bool_select.options[document.forms[0].filter_bool_select.selectedIndex].value;
	setHiddenValues();
	document.forms[0].submit();
}


// Auf die nächste Seite blättern

function next() {

	document.forms[0].start.value = new Number(document.forms[0].start.value) + new Number(document.forms[0].limit.value);
	setHiddenValues();
	document.forms[0].submit();
}

// Auf die voherige Seite blättern

function previous() {

	document.forms[0].start.value = new Number(document.forms[0].start.value) - new Number(document.forms[0].limit.value);
	setHiddenValues();
	document.forms[0].submit();
}


// Setzt die Werte in den versteckten Input-Feldern "limit", "show_active", "show_status"

function setHiddenValues() {
	document.forms[0].limit.value = document.forms[0].limit_select.options[document.forms[0].limit_select.selectedIndex].value;
	document.forms[0].show_active.value = document.forms[0].show_active_select.options[document.forms[0].show_active_select.selectedIndex].value;
	document.forms[0].show_status.value = document.forms[0].show_status_select.options[document.forms[0].show_status_select.selectedIndex].value;
}

// Prüft ob ein Filter bereits gesetzt ist oder nicht und macht es farblich kenntlich.
function checkFilter() {
	if (document.getElementById('filter_table')
			&& ((document.forms[0].filter_name && document.forms[0].filter_name_input)
				|| (document.forms[0].filter_url && document.forms[0].filter_url_input))) {
		if (document.forms[0].filter_name.value != document.forms[0].filter_name_input.value
				|| document.forms[0].filter_url.value != document.forms[0].filter_url_input.value) {
			document.getElementById('filter_table').style.backgroundColor = '#04D038';
		} else {
			document.getElementById('filter_table').style.backgroundColor = '#B1D0B9';
		}
	}
}
