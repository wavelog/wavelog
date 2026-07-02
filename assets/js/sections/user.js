//
// Javascript for User Section
//

function clearRefSwitches() {
    var iotaSwitch = document.getElementById("iotaToQsoTab");
    iotaSwitch.checked = false;
    var sotaSwitch = document.getElementById("sotaToQsoTab");
    sotaSwitch.checked = false;
    var wwffSwitch = document.getElementById("wwffToQsoTab");
    wwffSwitch.checked = false;
    var potaSwitch = document.getElementById("potaToQsoTab");
    potaSwitch.checked = false;
    var sigSwitch = document.getElementById("sigToQsoTab");
    sigSwitch.checked = false;
    var dokSwitch = document.getElementById("dokToQsoTab");
    dokSwitch.checked = false;
}

function actions_modal(user_id, modal) {
    $.ajax({
        url: base_url + 'index.php/user/actions_modal',
        type: 'POST',
        data: {
            modal: modal,
            user_id: user_id
        },
        success: function(response) {
            $('#actionsModal-container').html(response);
            $('#actionsModal').modal('show');
        },
        error: function() {
            alert(lang_general_word_error);
        }
    });
    $(window).on('blur', function() {
        $('#actionsModal').modal('hide');
    });
}

function send_passwort_reset(user_id) {
    $('#pwd_reset_message').hide().removeClass('alert-success alert-danger');
    $('#send_resetlink_btn').prop('disabled', true).addClass('running');
    $('#passwordreset_sent').hide().removeClass('fa-check fa-times text-success text-danger');

    $.ajax({
        url: base_url + 'index.php/user/admin_send_password_reset',
        type: 'POST',
        data: {
            user_id: user_id,
            submit_allowed: true
        },
        success: function(result) {
            if (result) {
                $('#pwd_reset_message').show().text(lang_admin_password_reset_processed).addClass('alert-success');
                $('#send_resetlink_btn').prop('disabled', false).removeClass('running');
                $('#passwordreset_sent').show().addClass('fa-check text-success');
            } else {
                $('#pwd_reset_message').show().text(lang_admin_email_settings_incorrect).addClass('alert-danger');
                $('#send_resetlink_btn').prop('disabled', false).removeClass('running');
                $('#passwordreset_sent').show().addClass('fa-times text-danger');
            }
        },
        error: function() {
            $('#pwd_reset_message').show().text(lang_admin_password_reset_failed).addClass('alert-danger');
            $('#send_resetlink_btn').prop('disabled', false).removeClass('running');
            $('#passwordreset_sent').show().addClass('fa-times text-danger');
        }
    });
}

function convert_user(user_id, convert_to) {
    $('#user_converted_message').hide().removeClass('alert-success alert-danger');
    $('#convert_user_btn').prop('disabled', true).removeClass('btn-secondary').addClass('btn-danger running');
    $('#user_converted').hide().removeClass('fa-check fa-times text-success text-danger');

    $.ajax({
        url: base_url + 'index.php/user/convert',
        type: 'POST',
        data: {
            user_id: user_id,
            convert_to: convert_to,
        },
        success: function(result) {
            if (result) {
                $('#user_converted_message').show().html(lang_account_conversion_processed).addClass('alert-success');
                $('#convert_user_btn').removeClass('running btn-danger').addClass('btn-secondary');
                $('#user_converted').show().addClass('fa-check text-success');
            } else {
                $('#user_converted_message').show().html(lang_account_conversion_failed).addClass('alert-danger');
                $('#convert_user_btn').prop('disabled', false).removeClass('running');
                $('#user_converted').show().addClass('fa-times text-danger');
            }
        },
        error: function() {
            $('#user_converted_message').show().html(lang_account_conversion_failed).addClass('alert-danger');
            $('#convert_user_btn').prop('disabled', false).removeClass('running');
            $('#user_converted').show().addClass('fa-times text-danger');
        }
    });
}


$(document).ready(function(){

	$('#adminusertable').DataTable({
		"pageLength": 25,
		responsive: true,
		ordering: true,
		"scrollY": "100%",
		"scrollCollapse": true,
		"paging": true,
		"language": {
			url: getDataTablesLanguageUrl(),
		},
		dom: 'Bfrtip',
		buttons: [
			{
				extend: 'csv',
				className: 'mb-1 btn btn-primary', // Bootstrap classes
				init: function(api, node, config) {
					$(node).removeClass('dt-button').addClass('btn btn-primary'); // Ensure Bootstrap class applies
				},
				exportOptions: {
					columns: [ 0, 1, 2, 3, 4, 5 ]
				}
			}
		]
	});

	$('#adminclubusertable').DataTable({
		"pageLength": 25,
		responsive: true,
		ordering: true,
		"scrollY": "100%",
		"scrollCollapse": true,
		"paging": true,
		"language": {
			url: getDataTablesLanguageUrl(),
		},
		dom: 'Bfrtip',
		buttons: [
			{
				extend: 'csv',
				className: 'mb-1 btn btn-primary', // Bootstrap classes
				init: function(api, node, config) {
					$(node).removeClass('dt-button').addClass('btn btn-primary'); // Ensure Bootstrap class applies
				},
				exportOptions: {
					columns: [ 0, 1, 2, 3, 4, 5 ]
				}
			}
		]
	});

	$(function () {
		$('.btn-tooltip').tooltip();
	});

	$('.icon_selectBox').off('click').on('click', function(){
		var boxcontent = $(this).attr('data-boxcontent');
		if ($('.icon_selectBox_data[data-boxcontent="'+boxcontent+'"]').is(":hidden")) { $('.icon_selectBox_data[data-boxcontent="'+boxcontent+'"]').show(); } else { $('.icon_selectBox_data[data-boxcontent="'+boxcontent+'"]').hide(); }
	});
	$('.icon_selectBox_data').off('mouseleave').on('mouseleave', function(){ if ($(this).is(":visible")) { $(this).hide(); } });
	$('.icon_selectBox_data label').off('click').on('click', function(){
		var boxcontent = $(this).closest('.icon_selectBox_data').attr('data-boxcontent');
		$('input[name="user_map_'+boxcontent+'_icon"]').attr('value',$(this).attr('data-value'));
		if ($(this).attr('data-value') != "0") {
			$('.user_icon_color[data-icon="'+boxcontent+'"]').show();
			$('.icon_selectBox[data-boxcontent="'+boxcontent+'"] .icon_overSelect').html($(this).html());
		} else {
			$('.user_icon_color[data-icon="'+boxcontent+'"]').hide();
			$('.icon_selectBox[data-boxcontent="'+boxcontent+'"] .icon_overSelect').html($(this).html().substring(0,10)+'.');
		}
		$('.icon_selectBox_data[data-boxcontent="'+boxcontent+'"]').hide();
	});

	$('.collapse').on('shown.bs.collapse', function(e) {
		var $card = $(this).closest('.accordion-item');
		var $open = $($(this).data('parent')).find('.collapse.show');

		var additionalOffset = 0;
		if($card.prevAll().filter($open.closest('.accordion-item')).length !== 0)
		{
			additionalOffset =  $open.height();
		}
		$('html,body').animate({
			scrollTop: $card.offset().top - additionalOffset
		}, 300);
	});

	$('#lotw_test_btn').click(function() {
		var btn_div = $('#lotw_test_btn');
		var msg_div = $('#lotw_test_txt');

		msg_div.removeClass('alert-success alert-danger').text('').hide();
		btn_div.removeClass('alert-success alert-danger').addClass('running').prop('disabled', true);

		$.ajax({
			url: base_url + 'index.php/lotw/check_lotw_credentials',
			type: 'POST',
			contentType: "application/json",
			data: JSON.stringify({lotw_user: $("#user_lotw_name").val(), lotw_pass: $("#user_lotw_password").val()}),
			success: function(res) {
				if(res.status == 'OK') {
					btn_div.addClass('alert-success').removeClass('running').prop('disabled', false);
					msg_div.addClass('alert-success').text(decodeHtml(res.details)).show();
				} else {
					btn_div.addClass('alert-danger').removeClass('running').prop('disabled', false);
					msg_div.addClass('alert-danger').text('Error: '+decodeHtml(res.details)).show();
				}
			},
			error: function(res) {
				btn_div.addClass('alert-danger').removeClass('running').prop('disabled', false);;
				msg_div.addClass('alert-danger').text('ERROR').show();
			},
		})
	});

	$('.admin_pwd_reset').click(function() {
		var pwd_reset_user_name = $(this).data('username');
		var pwd_reset_user_callsign = $(this).data('callsign');
		var pwd_reset_user_id = $(this).data('userid');
		var pwd_reset_user_email = $(this).data('usermail');

		BootstrapDialog.confirm({
			title: lang_general_word_warning,
			message:
			lang_admin_confirm_pwd_reset + "\n\n" +
			lang_admin_user + ": " + pwd_reset_user_name + "\n" +
			lang_gen_hamradio_callsign + ": " + pwd_reset_user_callsign,
			type: BootstrapDialog.TYPE_DANGER,
			btnCancelLabel: lang_general_word_cancel,
			btnOKLabel: lang_general_word_ok,
			btnOKClass: "btn-warning",
			closable: false,  // set closable: false, to prevent closing during ajax call
			callback: function (result) {
				if (result) {
					var wait_dialog = BootstrapDialog.show({
						title: lang_general_word_please_wait,
						message: '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i></div>',
						closable: false,
						buttons: []
					});
					$.ajax({
						url: base_url + 'index.php/user/admin_send_password_reset',
						type: 'POST',
						data: {
							user_id: pwd_reset_user_id,
							submit_allowed: true
						},
						success: function(result) {
							wait_dialog.close();

							if (result) {
								$('#pwd_reset_message').addClass('alert-success');
								$('#pwd_reset_message').text(lang_admin_password_reset_processed + " " + pwd_reset_user_name + " (" + pwd_reset_user_email + ")");
								$('#pwd_reset_message').show();
							} else {
								$('#pwd_reset_message').addClass('alert-danger');
								$('#pwd_reset_message').text(lang_admin_email_settings_incorrect);
								$('#pwd_reset_message').show();
							}
						},
						error: function() {
							wait_dialog.close();

							$('#pwd_reset_message').addClass('alert-danger');
							$('#pwd_reset_message').text('Error! Description: admin_send_password_reset failed');
							$('#pwd_reset_message').show();
						}
					});
				}
			},
		}).getModalHeader().find('.modal-title').after('<i class="fas fa-spinner fa-spin fa-2x"></i>');

	});

	var target = document.body;
	var observer = new MutationObserver(function() {
		$('input[type="search"]').on('keyup', function (e) {
			tocrappyzero=$(this).val().toUpperCase().replaceAll(/0/g, 'Ø');
			$(this).val(tocrappyzero);
			$(this).trigger("input");
		});
	});
	var config = { childList: true, subtree: true};

	// pass in the target node, as well as the observer options
	observer.observe(target, config);
});

/*
 * Account-page Settings Search
 * -------------------------------------------------------------------
 * On the user account edit page (application/views/user/edit.php) injects a
 * sticky search box above the form. It matches the text already rendered on
 * the page — which is in the user's active language — so searching works in
 * whatever locale the user has chosen, with no language-specific matching.
 * Hides non-matching cards, auto-expands any section that still has a hit,
 * shows a result count, and restores the exact section open/close state when
 * cleared. Runs only when `.accordion.user_edit` is present.
 * The search-box UI strings (placeholder, clear, jump, no-results) are the
 * lang_account_search_* globals defined in edit.php via __()/gettext.
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    // Case- and accent-insensitive comparison base.
    function norm(s) {
        return String(s == null ? '' : s)
            .normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    }

    function init() {
        var accordion = document.querySelector('.accordion.user_edit');
        if (!accordion) return;            // only the account edit page
        var form = document.querySelector('form[name="users"]');
        if (!form) return;
        if (document.getElementById('wl-settings-search')) return; // idempotent

        // Search-box UI strings, provided as lang_account_search_* globals by
        // application/views/user/edit.php via __()/gettext.
        var T = {
            ph:    lang_account_search_placeholder,
            clear: lang_account_search_clear,
            jump:  lang_account_search_jump,
            none:  lang_account_search_none
        };

        // --- Gather sections + cards, snapshot each section's open state ---
        var sections = [];
        for (var i = 0; i < accordion.children.length; i++) {
            var item = accordion.children[i];
            if (!item.classList || !item.classList.contains('accordion-item')) continue;
            var buttonEl = item.querySelector('.accordion-button');
            var collapseEl = item.querySelector('.accordion-collapse');
            var cardEls = item.querySelectorAll('.card');
            var cards = [];
            for (var c = 0; c < cardEls.length; c++) {
                cards.push({ el: cardEls[c], text: norm(cardEls[c].textContent) });
            }
            sections.push({
                item: item,
                button: buttonEl,
                collapse: collapseEl,
                header: buttonEl ? norm(buttonEl.textContent) : '',
                initiallyOpen: collapseEl ? collapseEl.classList.contains('show') : true,
                cards: cards
            });
        }

        // --- Inject minimal styling once ---
        if (!document.getElementById('wl-settings-search-style')) {
            var style = document.createElement('style');
            style.id = 'wl-settings-search-style';
            style.textContent = [
                '.wl-settings-search{position:sticky;top:0;z-index:1030;background-color:var(--bs-body-bg,#fff);',
                'padding:.5rem .25rem .45rem;margin:0 0 .75rem;border-bottom:1px solid var(--bs-border-color,rgba(0,0,0,.12));}',
                '.wl-search-row{display:flex;align-items:center;gap:.5rem;}',
                '.wl-search-field{position:relative;flex:1 1 auto;}',
                '.wl-search-field>i{position:absolute;left:.7rem;top:50%;transform:translateY(-50%);opacity:.5;pointer-events:none;}',
                '.wl-settings-search input[type=text]{padding-left:2.1rem;padding-right:2rem;border-radius:.375rem;}',
                '.wl-search-clear{position:absolute;right:.2rem;top:50%;transform:translateY(-50%);border:0;background:transparent;',
                'color:inherit;opacity:.55;cursor:pointer;font-size:1.15rem;line-height:1;padding:.25rem .45rem;}',
                '.wl-search-clear:hover{opacity:1;}',
                '.wl-search-count{min-width:1.7em;}',
                '.wl-search-jump{display:flex;flex-wrap:wrap;align-items:center;gap:.3rem;margin-top:.45rem;}',
                '.wl-search-jump .wl-jump-label{color:var(--bs-body-color,inherit);font-weight:500;}',
                '.wl-search-none{margin-top:.4rem;color:var(--bs-body-color,inherit);}',
                '.accordion.user_edit .accordion-item{scroll-margin-top:7rem;}',
                '.accordion.user_edit .accordion-item.wl-hit>.accordion-header .accordion-button{box-shadow:inset 3px 0 0 var(--bs-primary,#0d6efd);}',
                '.wl-settings-search [hidden]{display:none!important;}'
            ].join('');
            document.head.appendChild(style);
        }

        // --- Build the search bar (outside the form so it never submits) ---
        var bar = document.createElement('div');
        bar.id = 'wl-settings-search';
        bar.className = 'wl-settings-search';
        bar.innerHTML =
            '<div class="wl-search-row">' +
                '<span class="wl-search-field">' +
                    '<i class="fas fa-search"></i>' +
                    '<input type="text" class="form-control" autocomplete="off" placeholder="' + T.ph + '" aria-label="' + T.ph + '">' +
                    '<button type="button" class="wl-search-clear" aria-label="' + T.clear + '" title="' + T.clear + '" hidden>&times;</button>' +
                '</span>' +
                '<span class="badge bg-secondary wl-search-count" hidden></span>' +
            '</div>' +
            '<div class="wl-search-jump">' +
                '<span class="small wl-jump-label me-1">' + T.jump + '</span>' +
            '</div>' +
            '<div class="wl-search-none small" hidden>' + T.none + '</div>';

        var input = bar.querySelector('input[type=text]');
        var clearBtn = bar.querySelector('.wl-search-clear');
        var countEl = bar.querySelector('.wl-search-count');
        var jumpRow = bar.querySelector('.wl-search-jump');
        var noneEl = bar.querySelector('.wl-search-none');

        // Jump-to chips (one per section, labelled in the user's language)
        sections.forEach(function (s) {
            var label = s.button ? s.button.textContent.trim() : '';
            if (!label) return;
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'btn btn-sm btn-outline-primary py-0';
            chip.textContent = label;
            chip.addEventListener('click', function () {
                input.value = '';
                restore();
                expand(s);
                s.item.scrollIntoView({ behavior: 'smooth', block: 'start' });
                input.focus();
            });
            jumpRow.appendChild(chip);
        });

        form.parentNode.insertBefore(bar, form);

        var lastFirst = null;

        function filter() {
            var q = norm(input.value).trim().replace(/\s+/g, ' ');
            clearBtn.hidden = !input.value;

            if (!q) { restore(); return; }

            var firstMatch = null;
            var visible = 0;

            sections.forEach(function (s) {
                var headerHit = s.header.indexOf(q) !== -1;
                var sectionHit = false;
                s.cards.forEach(function (card) {
                    var hit = headerHit || card.text.indexOf(q) !== -1;
                    card.el.style.display = hit ? '' : 'none';
                    if (hit) { sectionHit = true; visible++; }
                });
                if (sectionHit) {
                    s.item.style.display = '';
                    s.item.classList.add('wl-hit');
                    expand(s);
                    if (!firstMatch) firstMatch = s.item;
                } else {
                    s.item.style.display = 'none';
                    s.item.classList.remove('wl-hit');
                }
            });

            countEl.textContent = String(visible);
            countEl.hidden = false;
            noneEl.hidden = visible !== 0;
            jumpRow.hidden = true;

            if (firstMatch && firstMatch !== lastFirst) {
                lastFirst = firstMatch;
                firstMatch.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function restore() {
            lastFirst = null;
            sections.forEach(function (s) {
                s.item.style.display = '';
                s.item.classList.remove('wl-hit');
                s.cards.forEach(function (card) { card.el.style.display = ''; });
                if (s.initiallyOpen) { expand(s); } else { collapse(s); }
            });
            clearBtn.hidden = true;
            countEl.hidden = true;
            noneEl.hidden = true;
            jumpRow.hidden = false;
        }

        function expand(s) {
            if (!s.collapse) return;
            s.collapse.classList.add('show');
            if (s.button) { s.button.classList.remove('collapsed'); s.button.setAttribute('aria-expanded', 'true'); }
        }
        function collapse(s) {
            if (!s.collapse) return;
            s.collapse.classList.remove('show');
            if (s.button) { s.button.classList.add('collapsed'); s.button.setAttribute('aria-expanded', 'false'); }
        }

        input.addEventListener('input', filter);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { input.value = ''; restore(); }
        });
        clearBtn.addEventListener('click', function () { input.value = ''; restore(); input.focus(); });
    }

    ready(init);
})();
