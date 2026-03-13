$('#band2').change(function(){
   var band = $("#band2 option:selected").text();
   if (band != "SAT") {
      $("#sats").val('All');
      $("#orbits").val('All');
      $("#satrow").hide();
      $("#orbitrow").hide();
   } else {
      $("#satrow").show();
      $("#orbitrow").show();
   }
});

$('#band2').change();	// trigger the change on fresh-load to hide/show SAT-Params

$('#sats').change(function(){
   var sat = $("#sats option:selected").text();
      $("#band2").val('SAT');
   if (sat != "All") {
   }
});
