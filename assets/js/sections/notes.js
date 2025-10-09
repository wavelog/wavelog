// Initialize EasyMDE for the notes textarea if on add/edit page
if (typeof EasyMDE !== 'undefined') {
    const notes = new EasyMDE({
        element: document.getElementById('notes'),
        forceSync: true,
        spellChecker: false,
        placeholder: 'Create note...',
        maxHeight: '350px',
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

    // Always sync EasyMDE content to textarea before form submit
    var notesForm = document.getElementById('notes_add');
    if (notesForm) {
        notesForm.addEventListener('submit', function(e) {
            if (notes && notes.codemirror) {
                notes.codemirror.save(); // forceSync
            }
        });
    }
    // If validation fails and textarea has value, restore it to EasyMDE
    var notesTextarea = document.getElementById('notes');
    if (notesTextarea && notesTextarea.value && notes.value() !== notesTextarea.value) {
        notes.value(notesTextarea.value);
    }
}

// Main notes page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Replace 0 with Ø in inputTitle as user types
    var inputTitle = document.getElementById('inputTitle');
    if (inputTitle) {
        inputTitle.addEventListener('input', function() {
            var caret = inputTitle.selectionStart;
            var newValue = inputTitle.value.replace(/0/g, 'Ø');
            if (inputTitle.value !== newValue) {
                inputTitle.value = newValue;
                inputTitle.setSelectionRange(caret, caret);
            }
        });
    }
    // Early exit if we're not on a notes page
    var notesTableBody = document.querySelector('#notesTable tbody');
    var isNotesMainPage = notesTableBody !== null;

    var base_url = window.base_url || document.body.getAttribute('data-baseurl') || '/';

    // Constants
    const NOTES_PER_PAGE = 15;
    const SEARCH_MIN_LENGTH = 3;
    const SORT_COLUMN_MAP = ['cat', 'title', 'creation_date', 'last_modified', null];

    // Cache frequently used DOM elements to avoid repeated queries
    var domCache = {
        notesTableBody: notesTableBody,
        notesTable: document.getElementById('notesTable'),
        categoryButtons: document.querySelectorAll('.category-btn'),
        searchBox: document.getElementById('notesSearchBox'),
        resetBtn: document.getElementById('notesSearchReset'),
        titleInput: document.getElementById('inputTitle'),
        catSelect: document.getElementById('catSelect'),
        saveBtn: document.querySelector('button[type="submit"]'),
        form: document.getElementById('notes_add'),
        paginationContainer: document.getElementById('notesPagination')
    };

    // Create pagination container if it doesn't exist
    if (!domCache.paginationContainer) {
        domCache.paginationContainer = document.createElement('div');
        domCache.paginationContainer.id = 'notesPagination';
        domCache.paginationContainer.className = 'd-flex justify-content-center my-3';
        var notesTableContainer = document.getElementById('notesTableContainer');
        if (notesTableContainer) {
            notesTableContainer.appendChild(domCache.paginationContainer);
        }
    }

    // Initialize existing UTC time cells and tooltips on page load
    // Helper function to initialize table elements after rendering
    function initializeTableElements() {
        // Convert UTC times to local time
        document.querySelectorAll('#notesTable td[data-utc]').forEach(function(td) {
            var utc = td.getAttribute('data-utc');
            td.textContent = utcToLocal(utc);
        });

        // Initialize Bootstrap tooltips for note titles
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('#notesTable a[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
    }

    // Run initialization for existing table content
    initializeTableElements();

    // Duplicate Contacts note check for add/edit pages
    var modal;
    function showModal(msg) {
        if (!modal) {
            modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = '<div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Duplicate Contacts Note</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>' + msg + '</p></div></div></div>';
            document.body.appendChild(modal);
        }
        modal.querySelector('.modal-body p').textContent = msg;
        $(modal).modal('show');
    }

    // Reload category counters via AJAX
    function reloadCategoryCounters() {
        fetch(base_url + 'index.php/notes/get_category_counts', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                domCache.categoryButtons.forEach(function(btn) {
                    var cat = btn.getAttribute('data-category');
                    var countSpan = btn.querySelector('.badge');
                    if (countSpan) {
                        if (cat === '__all__') {
                            // Handle "All Categories" button
                            countSpan.textContent = data.all_notes_count;
                        } else if (data.category_counts && data.category_counts[cat] !== undefined) {
                            // Handle specific category buttons
                            countSpan.textContent = data.category_counts[cat];
                        }
                    }
                });
            });
    }    // Helper: Convert UTC string to browser local time
    function utcToLocal(utcString) {
        if (!utcString) return '';
        // Parse as UTC
        var utcDate = new Date(utcString + ' UTC');
        if (isNaN(utcDate.getTime())) return utcString;
        return utcDate.toLocaleString();
    }

    // Get currently active category
    function getActiveCategory() {
        var activeBtn = document.querySelector('.category-btn.active');
        if (activeBtn) {
            var cat = activeBtn.getAttribute('data-category');
            return cat === '__all__' ? '' : cat;
        }
        return '';
    }

    // Perform search and update table
    function performNotesSearch() {
			var searchTerm = searchBox ? searchBox.value.trim() : '';
			var selectedCat = getActiveCategory();
			var formData = new FormData();
			formData.append('cat', selectedCat);
			// Only send search if 3+ chars, else send empty search
			if (searchTerm.length >= 3) {
				formData.append('search', searchTerm);
			} else {
				formData.append('search', '');
			}
			fetch(base_url + 'index.php/notes/search', {
				method: 'POST',
				body: formData
			})
			.then(response => {
				if (!response.ok) throw new Error('Network response was not ok');
				return response.json();
			})
			.then(data => {
				var tbody = '';
                if (data.length === 0) {
                    tbody = '<tr><td colspan="5" class="text-center text-muted">' + lang_notes_not_found + '</td></tr>';
                } else {
					data.forEach(function(note) {
						tbody += '<tr>' +
							'<td>' + (note.cat ? note.cat : '') + '</td>' +
							'<td><a href="' + base_url + 'index.php/notes/view/' + (note.id ? note.id : '') + '">' + (note.title ? note.title : '') + '</a></td>' +
							'<td>' + (note.last_modified ? note.last_modified : '') + '</td>' +
						'</tr>';
					});
				}
				if (notesTableBody) {
					notesTableBody.innerHTML = tbody;
				}
			})
			.catch(error => {
				if (notesTableBody) {
					notesTableBody.innerHTML = '<tr><td colspan="5">' + lang_notes_error_loading + ': ' + error.message + '</td></tr>';
				}
			});
    }

    // Sorting logic for notes table
    var sortState = {
        column: 3, // Default to 'Last Modification' column
        direction: 'desc' // Show latest modified at top
    };
    var columnHeaders = domCache.notesTable ? domCache.notesTable.querySelectorAll('thead th') : [];

    // Add sorting indicators and click handlers (only for supported columns)
    columnHeaders.forEach(function(th, idx) {
        var span = document.createElement('span');
        span.className = 'dt-column-order';
        th.appendChild(span);
        if (SORT_COLUMN_MAP[idx]) {
            span.setAttribute('role', 'button');
            span.setAttribute('aria-label', th.textContent + ': ' + lang_notes_sort);
            span.setAttribute('tabindex', '0');
            th.style.cursor = 'pointer';
            th.addEventListener('click', function() {
                if (sortState.column !== idx) {
                    sortState.column = idx;
                    sortState.direction = 'asc';
                } else if (sortState.direction === 'asc') {
                    sortState.direction = 'desc';
                } else if (sortState.direction === 'desc') {
                    sortState.direction = null;
                    sortState.column = null;
                } else {
                    sortState.direction = 'asc';
                }
                updateSortIndicators();
                performNotesSearch();
            });
        } else {
            th.style.cursor = 'default';
        }
    });

    //  Update sort indicators in the header
    function updateSortIndicators() {
        columnHeaders.forEach(function(th, idx) {
            var span = th.querySelector('.dt-column-order');
            if (!span) return;
            span.textContent = '';
            if (sortState.column === idx) {
                if (sortState.direction === 'asc') {
                    span.textContent = '▲';
                } else if (sortState.direction === 'desc') {
                    span.textContent = '▼';
                }
            }
        });
    }

    // Server-side pagination, sorting, and search integration
    var currentPage = 1;
    var totalPages = 1;
    var lastResponseTotal = 0;
    window.lastNotesData = [];

    // Render pagination controls
    function renderPagination() {
        if (!domCache.paginationContainer) return;
        domCache.paginationContainer.innerHTML = '';
        if (totalPages <= 1) return;
        var ul = document.createElement('ul');
        ul.className = 'pagination pagination-sm';
        for (var i = 1; i <= totalPages; i++) {
            var li = document.createElement('li');
            li.className = 'page-item' + (i === currentPage ? ' active' : '');
            var a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = i;
            a.addEventListener('click', function(e) {
                e.preventDefault();
                var page = parseInt(this.textContent);
                if (page !== currentPage) {
                    currentPage = page;
                    performNotesSearch();
                }
            });
            li.appendChild(a);
            ul.appendChild(li);
        }
        domCache.paginationContainer.appendChild(ul);
    }

    // Simple Markdown to plain text conversion for tooltip preview
    function markdownToText(md) {
        // Remove code blocks
        md = md.replace(/```[\s\S]*?```/g, '');
        // Remove inline code
        md = md.replace(/`[^`]*`/g, '');
        // Remove images
        md = md.replace(/!\[.*?\]\(.*?\)/g, '');
        // Remove links but keep text
        md = md.replace(/\[([^\]]+)\]\([^\)]+\)/g, '$1');
        // Remove headings
        md = md.replace(/^#{1,6}\s*/gm, '');
        // Remove blockquotes
        md = md.replace(/^>\s?/gm, '');
        // Remove emphasis
        md = md.replace(/(\*\*|__)(.*?)\1/g, '$2');
        md = md.replace(/(\*|_)(.*?)\1/g, '$2');
        // Remove lists
        md = md.replace(/^\s*([-*+]|\d+\.)\s+/gm, '');
        // Remove horizontal rules
        md = md.replace(/^(-{3,}|\*{3,}|_{3,})$/gm, '');
        // Remove HTML tags
        md = md.replace(/<[^>]+>/g, '');
        // Collapse whitespace
        md = md.replace(/\s+/g, ' ').trim();
        return md;
    }

    // Render notes table with data
    function renderNotesTable(data) {
        // Helper function to get translated category name
        function getTranslatedCategory(categoryKey) {
            if (window.categoryTranslations && window.categoryTranslations[categoryKey]) {
                return window.categoryTranslations[categoryKey];
            }
            return categoryKey || '';
        }

        var tbody = '';
        if (data.length === 0) {
            tbody = '<tr><td colspan="5" class="text-center text-muted">' + lang_notes_not_found + '</td></tr>';
        } else {
            data.forEach(function(note) {
                    // Strip HTML/Markdown and truncate to 100 chars for tooltip
                    var rawContent = note.note ? note.note : '';
                    // Use a more robust Markdown-to-text conversion
                    var plainContent = markdownToText(rawContent);
                    // Truncate to 100 chars
                    var preview = plainContent.length > 100 ? plainContent.substring(0, 100) + '…' : plainContent;
                    tbody += '<tr>' +
                        '<td class="text-center">' + getTranslatedCategory(note.cat) + '</td>' +
                        '<td class="text-start"><a href="' + base_url + 'index.php/notes/view/' + (note.id ? note.id : '') + '" title="' + preview.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" data-bs-toggle="tooltip">' + (note.title ? note.title : '') + '</a></td>' +
                        '<td class="text-center" data-utc="' + (note.creation_date ? note.creation_date : '') + '"></td>' +
                        '<td class="text-center" data-utc="' + (note.last_modified ? note.last_modified : '') + '"></td>' +
                        '<td class="text-center">' +
                            '<div class="btn-group btn-group-sm" role="group">' +
                                '<a href="' + base_url + 'index.php/notes/view/' + (note.id ? note.id : '') + '" class="btn btn-info" title="View"><i class="fa fa-eye"></i></a>' +
                                '<a href="' + base_url + 'index.php/notes/edit/' + (note.id ? note.id : '') + '" class="btn btn-primary" title="Edit"><i class="fa fa-pencil"></i></a>' +
                                (note.cat === 'Contacts'
                                    ? '<button type="button" class="btn btn-secondary" title="' + lang_notes_duplication_disabled + '" disabled data-bs-toggle="tooltip"><i class="fa fa-copy"></i></button>'
                                    : '<button type="button" class="btn btn-secondary" title="' + lang_notes_duplicate + '" onclick="confirmDuplicateNote(\'' + (note.id ? note.id : '') + '\')"><i class="fa fa-copy"></i></button>'
                                ) +
                                '<button type="button" class="btn btn-danger" title="' + lang_general_word_delete + '" onclick="confirmDeleteNote(\'' + (note.id ? note.id : '') + '\')"><i class="fa fa-trash"></i></button>' +
                            '</div>' +
                        '</td>' +
                    '</tr>';
            });
        }
        if (domCache.notesTableBody) {
            domCache.notesTableBody.innerHTML = tbody;
            // After rendering, initialize table elements
            initializeTableElements();
        }
        updateSortIndicators();
    }

    // Modal confirmation for delete and duplicate
    window.confirmDeleteNote = function(noteId) {
        showBootstrapModal(lang_notes_delete, lang_notes_delete_confirmation, function() {
            deleteNote(noteId);
        });
    };
    window.confirmDuplicateNote = function(noteId) {
        var note = (window.lastNotesData || []).find(function(n) { return n.id == noteId; });
        if (note && note.cat === 'Contacts') {
            showBootstrapModal(lang_notes_duplication_disabled_short, lang_notes_duplication_disabled, function(){});
            return;
        }
        showBootstrapModal(lang_notes_duplicate, lang_notes_duplicate_confirmation, function() {
            duplicateNote(noteId);
        });
    };

    // Actions for delete and duplicate
    function deleteNote(noteId) {
        fetch(base_url + 'index.php/notes/delete/' + noteId, { method: 'POST' })
            .then(() => {
                // Check if we need to go to previous page after deletion
                // If we're on the last page and it only has 1 item, go back one page
                var currentPageItemCount = window.lastNotesData ? window.lastNotesData.length : 0;
                if (currentPage > 1 && currentPageItemCount === 1) {
                    currentPage = currentPage - 1;
                }
                performNotesSearch();
                reloadCategoryCounters();
            });
    }

    // Duplicate note via POST with timestamp
    function duplicateNote(noteId) {
        // Get local timestamp
        var now = new Date();
        var timestamp = now.toLocaleString();
        var formData = new FormData();
        formData.append('timestamp', timestamp);
        fetch(base_url + 'index.php/notes/duplicate/' + noteId, {
            method: 'POST',
            body: formData
        })
        .then(() => {
            performNotesSearch();
            reloadCategoryCounters();
        });
    }

    // Bootstrap modal helper
    function showBootstrapModal(title, message, onConfirm) {
        var modalId = 'confirmModal_' + Math.random().toString(36).substr(2, 9);
        var modalHtml = '<div class="modal fade" id="' + modalId + '" tabindex="-1" role="dialog" data-bs-backdrop="static">' +
            '<div class="modal-dialog" role="document">' +
            '<div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title">' + title + '</h5></div>' +
            '<div class="modal-body"><p>' + message + '</p></div>' +
            '<div class="modal-footer justify-content-end">' +
            '<button type="button" class="btn btn-primary me-2" id="confirmModalBtn_' + modalId + '">' + lang_general_word_ok + '</button>' +
            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + lang_general_word_cancel + '</button>' +
            '</div></div></div></div>';
        var modalDiv = document.createElement('div');
        modalDiv.innerHTML = modalHtml;
        document.body.appendChild(modalDiv);
        var modalEl = modalDiv.querySelector('.modal');
        var modal;
        try {
            modal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
            modal.show();
        } catch (e) {
            document.body.removeChild(modalDiv);
            if (confirm(message)) {
                onConfirm();
            }
            return;
        }
        modalDiv.querySelector('#confirmModalBtn_' + modalId).onclick = function() {
            modal.hide();
            setTimeout(function() { document.body.removeChild(modalDiv); }, 300);
            onConfirm();
        };
        modalDiv.querySelector('[data-bs-dismiss="modal"]').onclick = function() {
            modal.hide();
            setTimeout(function() { document.body.removeChild(modalDiv); }, 300);
        };
    }

    // Patch performNotesSearch to use server-side pagination and sorting
    performNotesSearch = function() {
        var searchTerm = domCache.searchBox ? domCache.searchBox.value.trim() : '';
        var selectedCat = getActiveCategory();
        var sortColIdx = sortState.column;
        var sortDir = sortState.direction;
    	var sortCol = (sortColIdx !== null && SORT_COLUMN_MAP[sortColIdx]) ? SORT_COLUMN_MAP[sortColIdx] : null;
        var formData = new FormData();
        formData.append('cat', selectedCat);
        formData.append('search', searchTerm.length >= SEARCH_MIN_LENGTH ? searchTerm : '');
        formData.append('page', currentPage);
        formData.append('per_page', NOTES_PER_PAGE);
        formData.append('sort_col', sortCol || '');
        formData.append('sort_dir', sortDir || '');
        fetch(base_url + 'index.php/notes/search', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(resp => {
            var data = (resp && Array.isArray(resp.notes)) ? resp.notes : [];
            window.lastNotesData = data;
            lastResponseTotal = resp.total || 0;
            totalPages = Math.max(1, Math.ceil(lastResponseTotal / NOTES_PER_PAGE));
            if (currentPage > totalPages) currentPage = totalPages;
            renderNotesTable(data);
            renderPagination();
            reloadCategoryCounters();
        })
        .catch(error => {
            if (domCache.notesTableBody) {
                domCache.notesTableBody.innerHTML = '<tr><td colspan="5">' + lang_notes_error_loading + ':' + error.message + '</td></tr>';
            }
            if (domCache.paginationContainer) {
                domCache.paginationContainer.innerHTML = '';
            }
        });
    };

    // Reset to first page on search, sort, or category change
    if (domCache.categoryButtons && domCache.notesTableBody) {
        domCache.categoryButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                domCache.categoryButtons.forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                currentPage = 1;
                performNotesSearch();
            });
        });
    }
    if (domCache.searchBox) {
        domCache.searchBox.addEventListener('input', function() {
            currentPage = 1;
            performNotesSearch();
        });
    }
    if (domCache.resetBtn) {
        domCache.resetBtn.addEventListener('click', function() {
            if (domCache.searchBox) domCache.searchBox.value = '';
            currentPage = 1;
            performNotesSearch();
        });
    }

    // Initial render - only if we have the necessary elements
    if (domCache.notesTableBody) {
        performNotesSearch();
    }
});
