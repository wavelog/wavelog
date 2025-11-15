// API page clipboard functions

function copyToClipboard(text, targetElement) {
   if (navigator.clipboard && navigator.clipboard.writeText) {
      // Modern Clipboard API
      navigator.clipboard.writeText(text).then(function() {
         targetElement.addClass('flash-copy')
            .delay('1000').queue(function() {
               targetElement.removeClass('flash-copy').dequeue();
            });
      }).catch(function(err) {
         console.error('Failed to copy: ', err);
         alert('Failed to copy to clipboard');
      });
   } else {
      // Fallback for browsers that don't support clipboard API
      var tempInput = document.createElement('input');
      tempInput.value = text;
      document.body.appendChild(tempInput);
      tempInput.select();
      document.execCommand('copy');
      document.body.removeChild(tempInput);

      targetElement.addClass('flash-copy')
         .delay('1000').queue(function() {
            targetElement.removeClass('flash-copy').dequeue();
         });
   }
}

function copyApiKey(apiKey) {
   var apiKeyField = $('#'+apiKey);
   copyToClipboard(apiKey, apiKeyField);
}

function copyApiUrl(urlText) {
   var apiUrlField = $('#apiUrl');
   copyToClipboard(urlText, apiUrlField);
}

$(function () {
   $('[data-bs-toggle="tooltip"]').tooltip({'delay': { show: 500, hide: 0 }, 'placement': 'right'});
});
