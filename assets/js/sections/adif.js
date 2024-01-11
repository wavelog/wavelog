$(document).ready(function(){
	$('#prepare_sub').click(function(e){
		e.preventDefault();
		var fi = document.getElementById("userfile");
		var file = fi.files[0];;
		if (JSZip.support.blob) {	// Check if Browser supports ZIP
		var zip = new JSZip();
			//add all files to zip 
			addFileToZip(file);
			function addFileToZip(n) {
				var arrayBuffer;
				var fileReader = new FileReader();
				fileReader.onloadend = function() {
					arrayBuffer = this.result;
					zip.file(file.name, arrayBuffer, { binary:true });
					zip.generateAsync({type:"blob", compression:"DEFLATE"}).then(function(content){

						//generated zip content to file type
						var files = new File([content], file.name + ".zip");

						const dataTransfer = new DataTransfer();
						dataTransfer.items.add(files);
						//send generated file to server
						fi.files=dataTransfer.files;
						$("#upform").submit();
						return;
					});
				};
				fileReader.readAsArrayBuffer(file);
			}
		} else {
			$("#upform").submit();
		}
	});
	$('#markExportedToLotw').click(function(e){
		let form = $(this).closest('form');
		let station = form.find('select[name=station_profile]');
		if (station.val() == 0) {
			station.addClass('is-invalid');
		}else{
			form.submit();
		}
	})
});
