$('#band').change(function(){
	var band = $("#band option:selected").text();
	if (band == "SAT") {
        	$('#propmode').val('SAT');
	}
});
