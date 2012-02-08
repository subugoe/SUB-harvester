// ************************************************
// Javascript für Previewseite
// mit JQuery

function show_index(div) {	
	
	$('#' + div + '_index').toggle(true);
	
	$('#' + div).mousemove(function(e) {
		
		$('#' + div + '_index').css('bottom', window.innerHeight - e.pageY + 60);
		$('#' + div + '_index').css('left', e.pageX-400);
		
		//$('#span_test').text("X: " + e.pageX + " | Y: " + e.pageY);	
	})
	
	
}

function hide_index(div) {	
	$('#' + div + '_index').toggle(false);
	$('#' + div).unbind('mousemove');
}



function show_links(div) {
	
	$(div).slideToggle("normal");
	return true;
}