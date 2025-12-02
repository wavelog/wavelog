// API page clipboard functions

function copyApiKey(apiKey) {
   copyToClipboard(apiKey);
   $('#'+apiKey).addClass('flash-copy').delay('1000').queue(function() {
      $(this).removeClass('flash-copy').dequeue();
   });
}

function copyApiUrl(urlText) {
   copyToClipboard(urlText);
   $('#apiUrl').addClass('flash-copy').delay('1000').queue(function() {
      $(this).removeClass('flash-copy').dequeue();
   });
}

$(function () {
   $('[data-bs-toggle="tooltip"]').tooltip({'delay': { show: 500, hide: 0 }, 'placement': 'right'});
});
