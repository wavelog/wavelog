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

function copyApiV2Url(urlText) {
   var apiUrlField = $('#apiV2Url');
   copyToClipboard(urlText, apiUrlField);
}

function copyNewToken(token) {
   copyToClipboard(token, $('#newTokenValue'));
}

$(function () {
   $('[data-bs-toggle="tooltip"]').tooltip({'delay': { show: 500, hide: 0 }, 'placement': 'right'});

   // One-time reveal of a freshly created API v2 token
   var newTokenModal = document.getElementById('newTokenModal');
   if (newTokenModal) {
      new bootstrap.Modal(newTokenModal).show();
   }

   // Scope group toggle: checks/unchecks every scope of the resource at once.
   // Pure UI convenience - only the individual scope checkboxes submit.
   $('.scope-group-toggle').on('change', function () {
      $('.scope-checkbox[value^="' + $(this).data('group') + ':"]').prop('checked', this.checked);
   });

   // Keep the group toggle in sync with its children (indeterminate when
   // only some scopes of the resource are selected).
   $('.scope-checkbox').on('change', function () {
      var group = this.value.split(':')[0];
      var boxes = $('.scope-checkbox[value^="' + group + ':"]');
      var checked = boxes.filter(':checked').length;
      $('#scopegroup_' + group)
         .prop('checked', checked === boxes.length)
         .prop('indeterminate', checked > 0 && checked < boxes.length);
      if (boxes.filter(':checked').length) {
         $('#scopes-error').hide();
      }
   });

   // Require at least one scope before the create-token form submits.
   $('#createTokenForm').on('submit', function (e) {
      if ($('.scope-checkbox:checked').length === 0) {
         e.preventDefault();
         $('#scopes-error').show();
      }
   });
});
