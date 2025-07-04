$(document).ready(function () {
   $('#orbit').multiselect({
      enableFiltering: true,
      enableCaseInsensitiveFiltering: true,
      filterPlaceholder: lang_general_word_search,
      templates: {
         button: '<button type="button" class="multiselect dropdown-toggle btn btn-sm btn-secondary me-2 w-auto" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
      },
      numberDisplayed: 1,
      inheritClass: true,
      includeSelectAllOption: true
   });


   $('#orbitel').multiselect({
      enableFiltering: true,
      enableCaseInsensitiveFiltering: true,
      filterPlaceholder: lang_general_word_search,
      templates: {
         button: '<button type="button" class="multiselect dropdown-toggle btn btn-sm btn-secondary me-2 w-auto" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
      },
      numberDisplayed: 1,
      inheritClass: true,
      includeSelectAllOption: true
   });

})
