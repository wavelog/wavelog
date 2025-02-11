const notes = new EasyMDE({element: document.getElementById('notes'), forceSync: true, spellChecker: false, placeholder: 'Gebe die Notiz ein...', maxHeight: '350px', 
    toolbar: [
        {
            name: "bold",
            action: EasyMDE.toggleBold,
            className: "fa fa-bold",
            title: "Fett",
        },
        {
            name: "italic",
            action: EasyMDE.toggleItalic,
            className: "fa fa-italic",
            title: "Kursiv",
        },
        {
            name: "heading",
            action: EasyMDE.toggleHeadingSmaller,
            className: "fa fa-header",
            title: "Überschrift"
        },
        "|",
        {
            name: "quote",
            action: EasyMDE.toggleBlockquote,
            className: "fa fa-quote-left",
            title: "Zitat"
        },
        {
            name: "unordered-list",
            action: EasyMDE.toggleUnorderedList,
            className: "fa fa-list-ul",
            title: "Unsortierte Liste"
        },
        {
            name: "ordered-list",
            action: EasyMDE.toggleOrderedList,
            className: "fa fa-list-ol",
            title: "Sortierte Liste"
        },
        "|",
        {
            name: "link",
            action: EasyMDE.drawLink,
            className: "fa fa-link",
            title: "Link"
        },
        {
            name: "image",
            action: EasyMDE.drawImage,
            className: "fa fa-image",
            title: "Bild"
        },
        "|",
        {
            name: "preview",
            action: EasyMDE.togglePreview,
            className: "fa fa-eye no-disable",
            title: "Vorschau"
        },
        "|",
        {
            name: "guide",
            action: "https://www.markdownguide.org/basic-syntax/", // link
            className: "fa fa-question-circle",
            title: "Markdown Guide"
        }
    ]
});
