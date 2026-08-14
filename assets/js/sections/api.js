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

document.addEventListener('submit', function (e) {
   var btn = e.submitter;
   if (btn && btn.dataset.confirm && !confirm(btn.dataset.confirm)) {
      e.preventDefault();
   }
});

$(function () {
   $('[data-bs-toggle="tooltip"]').tooltip({'delay': { show: 500, hide: 0 }, 'placement': 'right'});

   // One-time reveal of a freshly created API v2 token
   var newTokenModal = document.getElementById('newTokenModal');
   if (newTokenModal) {
      new bootstrap.Modal(newTokenModal).show();
   }

   // Reflect the current scope selection in the group toggles (indeterminate
   // when only some scopes of a resource are selected) and highlight the
   // preset button that matches the selection exactly, if any.
   function syncScopeSelection() {
      $('.scope-group-toggle').each(function () {
         var boxes = $('.scope-checkbox[value^="' + $(this).data('group') + ':"]');
         var checked = boxes.filter(':checked').length;
         $(this)
            .prop('checked', checked === boxes.length)
            .prop('indeterminate', checked > 0 && checked < boxes.length);
      });

      var selected = $('.scope-checkbox:checked').map(function () {
         return this.value;
      }).get().sort().join(',');

      $('.scope-preset').each(function () {
         $(this).toggleClass('active', $(this).data('scopes').split(',').sort().join(',') === selected);
      });

      if (selected !== '') {
         $('#scopes-error').hide();
      }
   }

   // Scope group toggle: checks/unchecks every scope of the resource at once.
   // Pure UI convenience - only the individual scope checkboxes submit.
   $('.scope-group-toggle').on('change', function () {
      $('.scope-checkbox[value^="' + $(this).data('group') + ':"]').prop('checked', this.checked);
      syncScopeSelection();
   });

   $('.scope-checkbox').on('change', syncScopeSelection);

   // Preset buttons: replace the selection with the scopes a known tool needs.
   // Nothing is submitted by the buttons themselves, they only tick checkboxes,
   // so the user can still fine-tune the selection afterwards.
   $('.scope-preset').on('click', function () {
      var scopes = $(this).data('scopes').split(',');
      $('.scope-checkbox').each(function () {
         this.checked = scopes.indexOf(this.value) !== -1;
      });
      syncScopeSelection();
   });

   $('#resetScopes').on('click', function () {
      $('.scope-checkbox').prop('checked', false);
      syncScopeSelection();
   });

   // Warn when the token is set to never expire: allowed, but discouraged.
   $('#tokenExpiry').on('change', function () {
      $('#expiry-never-warning').toggle(this.value === 'never');
   }).trigger('change');

   // reset everything
   var createTokenModal = document.getElementById('createTokenModal');
   if (createTokenModal) {
      createTokenModal.addEventListener('show.bs.modal', function () {
         document.getElementById('createTokenForm').reset();
         $('#scopes-error').hide();
         $('#tokenExpiry').trigger('change');
         syncScopeSelection();
      });
   }

   // Require at least one scope before the create-token form submits.
   $('#createTokenForm').on('submit', function (e) {
      if ($('.scope-checkbox:checked').length === 0) {
         e.preventDefault();
         $('#scopes-error').show();
      }
   });
});
