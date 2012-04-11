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
	var form = event.target.form;
	document.forms[0].filter_name.value = form.filter_name_input.value;
	document.forms[0].filter_url.value = form.filter_url_input.value;
	document.forms[0].filter_bool.value = form.filter_bool_select.options[document.forms[0].filter_bool_select.selectedIndex].value;

	setHiddenValues();
	document.forms[0].submit();
}


// Auf die nächste Seite blättern
function next() {
	document.forms[0].start.value = new Number(event.target.form.start.value) + new Number(event.target.form.limit.value);

	setHiddenValues();
	document.forms[0].submit();
}

// Auf die voherige Seite blättern
function previous() {
	document.forms[0].start.value = new Number(event.target.form.start.value) - new Number(event.target.form.limit.value);

	setHiddenValues();
	document.forms[0].submit();
}


// Setzt die Werte in den versteckten Input-Feldern "limit", "show_active", "show_status"
function setHiddenValues(form) {
	var invisibleForm = document.getElementById('limit_select');
	var form = event.target.form;
	invisibleForm.limit.value = form.limit_select.options[form.limit_select.selectedIndex].value;
	invisibleForm.show_active.value = form.show_active_select.options[form.show_active_select.selectedIndex].value;
	invisibleForm.show_status.value = form.show_status_select.options[form.show_status_select.selectedIndex].value;
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
