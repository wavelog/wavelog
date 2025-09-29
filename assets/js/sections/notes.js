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
}

// Category filter for notes list
document.addEventListener('DOMContentLoaded', function() {
    // Duplicate Contacts note check for add/edit pages
    var titleInput = document.getElementById('inputTitle');
    var catSelect = document.getElementById('catSelect');
    var saveBtn = document.querySelector('button[type="submit"]');
    var form = document.getElementById('notes_add');
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
        fetch(base_url + 'index.php/notes/get_category_counts', {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data && typeof data === 'object') {
                document.querySelectorAll('.category-btn').forEach(function(btn) {
                    var cat = btn.getAttribute('data-category');
                    var badge = btn.querySelector('.badge');
                    if (cat === '__all__') {
                        if (badge) badge.textContent = data.all_notes_count ?? 0;
                    } else {
                        if (badge) badge.textContent = data.category_counts && data.category_counts[cat] ? data.category_counts[cat] : 0;
                    }
                });
            }
        });
    }
    // Helper: Convert UTC string to browser local time
    function utcToLocal(utcString) {
        if (!utcString) return '';
        // Parse as UTC
        var utcDate = new Date(utcString + ' UTC');
        if (isNaN(utcDate.getTime())) return utcString;
        return utcDate.toLocaleString();
    }
    var base_url = window.base_url || document.body.getAttribute('data-baseurl') || '/';
    var notesTableBody = document.querySelector('#notesTable tbody');
    var categoryButtons = document.querySelectorAll('.category-btn');
    var searchBox = document.getElementById('notesSearchBox');
    var resetBtn = document.getElementById('notesSearchReset');

    function getActiveCategory() {
        var activeBtn = document.querySelector('.category-btn.active');
        if (activeBtn) {
            var cat = activeBtn.getAttribute('data-category');
            return cat === '__all__' ? '' : cat;
        }
        return '';
    }

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
					tbody = '<tr><td colspan="3" class="text-center text-muted">No notes were found.</td></tr>';
				} else {
					data.forEach(function(note) {
						tbody += '<tr>' +
							'<td>' + (note.cat ? note.cat : '') + '</td>' +
							'<td><a href="' + base_url + 'index.php/notes/view/' + (note.id ? note.id : '') + '">' + (note.title ? note.title : '') + '</a></td>' +
							'<td>' + (note.last_modified ? note.last_modified : '') + '</td>' +
						'</tr>';
					});
				}
				notesTableBody.innerHTML = tbody;
			})
			.catch(error => {
				notesTableBody.innerHTML = '<tr><td colspan="3">Error loading notes: ' + error.message + '</td></tr>';
			});
    }

    // Sorting logic for notes table
    var notesTable = document.getElementById('notesTable');
    var sortState = {
        column: 3, // Default to 'Last Modification' column
        direction: 'desc' // Show latest modified at top
    };
    var columnHeaders = notesTable ? notesTable.querySelectorAll('thead th') : [];

    // Add sorting indicators and click handlers
    columnHeaders.forEach(function(th, idx) {
        var span = document.createElement('span');
        span.className = 'dt-column-order';
        span.setAttribute('role', 'button');
        span.setAttribute('aria-label', th.textContent + ': Activate to sort');
        span.setAttribute('tabindex', '0');
        th.appendChild(span);
        th.style.cursor = 'pointer';
        th.addEventListener('click', function() {
            // Cycle sort direction: null -> asc -> desc -> null
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
    });

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
    var notesPerPage = 15;
    var currentPage = 1;
    var totalPages = 1;
    var lastResponseTotal = 0;
    var lastNotesData = [];
    var paginationContainer = document.getElementById('notesPagination');
    if (!paginationContainer) {
        paginationContainer = document.createElement('div');
        paginationContainer.id = 'notesPagination';
        paginationContainer.className = 'd-flex justify-content-center my-3';
        var notesTableContainer = document.getElementById('notesTableContainer');
        if (notesTableContainer) {
            notesTableContainer.appendChild(paginationContainer);
        }
    }

    function renderPagination() {
        paginationContainer.innerHTML = '';
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
        paginationContainer.appendChild(ul);
    }

    function renderNotesTable(data) {
        var tbody = '';
        if (data.length === 0) {
            tbody = '<tr><td colspan="5" class="text-center text-muted">No notes were found.</td></tr>';
        } else {
            data.forEach(function(note) {
                    // Strip HTML/Markdown and truncate to 100 chars for tooltip
                    var rawContent = note.note ? note.note : '';
                    // Remove Markdown (basic: *, _, #, >, `, ![...], [...](...), etc.)
                    var plainContent = rawContent.replace(/(!?\[.*?\]\(.*?\))|[#>*_`]/g, '');
                    // Remove HTML tags
                    plainContent = plainContent.replace(/<[^>]+>/g, '');
                    // Collapse whitespace
                    plainContent = plainContent.replace(/\s+/g, ' ').trim();
                    // Truncate to 100 chars
                    var preview = plainContent.length > 100 ? plainContent.substring(0, 100) + '…' : plainContent;
                    tbody += '<tr>' +
                        '<td class="text-center">' + (note.cat ? note.cat : '') + '</td>' +
                        '<td class="text-start"><a href="' + base_url + 'index.php/notes/view/' + (note.id ? note.id : '') + '" title="' + preview.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" data-bs-toggle="tooltip">' + (note.title ? note.title : '') + '</a></td>' +
                        '<td class="text-center" data-utc="' + (note.creation_date ? note.creation_date : '') + '"></td>' +
                        '<td class="text-center" data-utc="' + (note.last_modified ? note.last_modified : '') + '"></td>' +
                        '<td class="text-center">' +
                            '<div class="btn-group btn-group-sm" role="group">' +
                                '<a href="' + base_url + 'index.php/notes/view/' + (note.id ? note.id : '') + '" class="btn btn-info" title="View"><i class="fa fa-eye"></i></a>' +
                                '<a href="' + base_url + 'index.php/notes/edit/' + (note.id ? note.id : '') + '" class="btn btn-primary" title="Edit"><i class="fa fa-pencil"></i></a>' +
                                (note.cat === 'Contacts'
                                    ? '<button type="button" class="btn btn-secondary" title="Duplication is disabled for Contacts notes" disabled data-bs-toggle="tooltip"><i class="fa fa-copy"></i></button>'
                                    : '<button type="button" class="btn btn-secondary" title="Duplicate" onclick="confirmDuplicateNote(\'' + (note.id ? note.id : '') + '\')"><i class="fa fa-copy"></i></button>'
                                ) +
                                '<button type="button" class="btn btn-danger" title="Delete" onclick="confirmDeleteNote(\'' + (note.id ? note.id : '') + '\')"><i class="fa fa-trash"></i></button>' +
                            '</div>' +
                        '</td>' +
                    '</tr>';
            });
        }
        notesTableBody.innerHTML = tbody;
        // After rendering, convert all UTC times to local
        document.querySelectorAll('#notesTable td[data-utc]').forEach(function(td) {
            var utc = td.getAttribute('data-utc');
            td.textContent = utcToLocal(utc);
        });
        // Initialize Bootstrap tooltips for note titles
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('#notesTable a[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(el) {
            new bootstrap.Tooltip(el);
        });
        updateSortIndicators();
    }

    // Modal confirmation for delete and duplicate
    window.confirmDeleteNote = function(noteId) {
        showBootstrapModal('Delete Note', 'Are you sure you want to delete this note?', function() {
            deleteNote(noteId);
        });
    };
    window.confirmDuplicateNote = function(noteId) {
        var note = (window.lastNotesData || []).find(function(n) { return n.id == noteId; });
        if (note && note.cat === 'Contacts') {
            showBootstrapModal('Duplication Disabled', 'Duplication is disabled for Contacts notes. Only one note per callsign is allowed.', function(){});
            return;
        }
        showBootstrapModal('Duplicate Note', 'Do you want to duplicate this note?', function() {
            duplicateNote(noteId);
        });
    };
    function deleteNote(noteId) {
        fetch(base_url + 'index.php/notes/delete/' + noteId, { method: 'POST' })
            .then(() => {
                performNotesSearch();
                reloadCategoryCounters();
            });
    }
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
    function showBootstrapModal(title, message, onConfirm) {
        var modalId = 'confirmModal_' + Math.random().toString(36).substr(2, 9);
        var modalHtml = '<div class="modal fade" id="' + modalId + '" tabindex="-1" role="dialog" data-bs-backdrop="static">' +
            '<div class="modal-dialog" role="document">' +
            '<div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title">' + title + '</h5></div>' +
            '<div class="modal-body"><p>' + message + '</p></div>' +
            '<div class="modal-footer justify-content-end">' +
            '<button type="button" class="btn btn-primary me-2" id="confirmModalBtn_' + modalId + '">Confirm</button>' +
            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>' +
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
        var searchTerm = searchBox ? searchBox.value.trim() : '';
        var selectedCat = getActiveCategory();
        var sortColIdx = sortState.column;
        var sortDir = sortState.direction;
        var sortColMap = ['cat', 'title', 'last_modified'];
        var sortCol = sortColIdx !== null ? sortColMap[sortColIdx] : null;
        var formData = new FormData();
        formData.append('cat', selectedCat);
        formData.append('search', searchTerm.length >= 3 ? searchTerm : '');
        formData.append('page', currentPage);
        formData.append('per_page', notesPerPage);
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
            var data = resp.notes || [];
            lastNotesData = data;
            lastResponseTotal = resp.total || 0;
            totalPages = Math.max(1, Math.ceil(lastResponseTotal / notesPerPage));
            if (currentPage > totalPages) currentPage = totalPages;
            renderNotesTable(data);
            renderPagination();
            reloadCategoryCounters();
        })
        .catch(error => {
            notesTableBody.innerHTML = '<tr><td colspan="3">Error loading notes: ' + error.message + '</td></tr>';
            paginationContainer.innerHTML = '';
        });
    };

    // Reset to first page on search, sort, or category change
    if (categoryButtons && notesTableBody) {
        categoryButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                categoryButtons.forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                currentPage = 1;
                performNotesSearch();
            });
        });
    }
    if (searchBox) {
        searchBox.addEventListener('input', function() {
            currentPage = 1;
            performNotesSearch();
        });
    }
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (searchBox) searchBox.value = '';
            currentPage = 1;
            performNotesSearch();
        });
    }

    // Initial render
    performNotesSearch();
});
