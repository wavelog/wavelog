$(function() {
	$('.dxcalendar').DataTable({
		searching: true,
		responsive: true,
		"scrollY": window.innerHeight - $('.container').innerHeight() - 250,
		"scrollCollapse": true,
		"paging": false,
		"scrollX": true,
		ordering: false
	});
});
