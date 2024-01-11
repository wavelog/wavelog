$(document).ready(function(){
	$('#prepare_sub').click(function(e){
		e.preventDefault();

		/* Make a zip file here */

		var fi = document.getElementById("userfile");
		var file = fi.files[0];;
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
					var files = new File([content], file.name);

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
