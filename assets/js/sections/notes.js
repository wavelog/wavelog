const notes = new EasyMDE({element: document.getElementById('notes'), forceSync: true, spellChecker: false, placeholder: 'Create note...', maxHeight: '350px', 
    toolbar: [
        {
            name: "bold",
            action: EasyMDE.toggleBold,
            className: "fa fa-bold",
            title: "bold",
        },
        {
            name: "italic",
            action: EasyMDE.toggleItalic,
            className: "fa fa-italic",
            title: "italic",
        },
        {
            name: "heading",
            action: EasyMDE.toggleHeadingSmaller,
            className: "fa fa-header",
            title: "heading",
        },
        "|",
        {
            name: "quote",
            action: EasyMDE.toggleBlockquote,
            className: "fa fa-quote-left",
            title: "quote",
        },
        {
            name: "unordered-list",
            action: EasyMDE.toggleUnorderedList,
            className: "fa fa-list-ul",
            title: "unordered list",
        },
        {
            name: "ordered-list",
            action: EasyMDE.toggleOrderedList,
            className: "fa fa-list-ol",
            title: "ordered list",
        },
        "|",
        {
            name: "link",
            action: EasyMDE.drawLink,
            className: "fa fa-link",
            title: "link"
        },
        {
            name: "image",
            action: EasyMDE.drawImage,
            className: "fa fa-image",
            title: "image"
        },
        "|",
        {
            name: "preview",
            action: EasyMDE.togglePreview,
            className: "fa fa-eye no-disable",
            title: "preview"
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
