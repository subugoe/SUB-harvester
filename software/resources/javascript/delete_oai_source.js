// ************************************************
// Javascript für das Löschen einer OAI-Quelle

// Ruft eine OAI-Quelle zum Editieren auf.
function edit(id) {
	document.forms[0].do.value = 'edit_oa_source';
}

// Zeigt eine OAI-Quelle an
function show(id) {
	document.forms[0].do.value = 'show_oai_source';
}

// Ruft die Löschseite einer OAI-Quelle auf
function removeSource(id) {
	document.forms[0].do.value = 'delete_oai_source';
}

// Ruft die Startseite auf
function gotoStart() {
	window.location = 'index.php';
}