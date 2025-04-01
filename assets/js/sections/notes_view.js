const notes_view = new EasyMDE({element: document.getElementById('notes_view'), forceSync: true, spellChecker: false, toolbar: false, maxHeight: '350px', readOnly: true});
notes_view.togglePreview();
