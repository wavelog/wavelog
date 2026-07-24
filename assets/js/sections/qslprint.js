let qsoDialogInstance = null;

function removeQslRow(id) {
	var $row = $("#qslprint_" + id);
	if ($.fn.dataTable.isDataTable('#qslprint_table')) {
		$('#qslprint_table').DataTable().row($row).remove().draw(false);
	} else {
		$row.remove();
	}
}

function deleteFromQslQueue(id) {
	BootstrapDialog.confirm({
		title: 'DANGER',
		message: 'Warning! Are you sure you want to removes this QSL from the queue?',
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		callback: function(result) {
			if(result) {
				$.ajax({
					url: base_url + 'index.php/qslprint/delete_from_qsl_queue',
					type: 'post',
					data: {'id': id	},
					success: function(html) {
						removeQslRow(id);
					}
				});
			}
		}
	});
}

function openQsoList(callsign) {
	$.ajax({
		url: base_url + 'index.php/qslprint/open_qso_list',
		type: 'post',
		data: {'callsign': callsign},
		success: function(html) {
			qsoDialogInstance = BootstrapDialog.show({
				title: 'QSO List',
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'qso-dialog',
				nl2br: false,
				message: html,
				onshown: function(dialog) {
					$('[data-bs-toggle="tooltip"]').tooltip();
				},
				buttons: [{
					label: lang_admin_close,
					action: function (dialogItself) {
						dialogItself.close();
					}
				}]
			});
		}
	});
}

function addQsoToPrintQueue(id) {
	$.ajax({
        url: base_url + 'index.php/qslprint/add_qso_to_print_queue',
        type: 'post',
		data: {'id': id},
        success: function(html) {
					if (qsoDialogInstance) {
                        qsoDialogInstance.close();
                    }
                    let callSign = $("#qsolist_"+id).find("td:eq(0)").text();
                    let formattedCallSign = callSign.replace(/0/g, "Ø").toUpperCase();
                    // Plain callsign (with 0, not Ø) for the DataTables search source
                    let searchCallSign = formattedCallSign.replace(/Ø/g, "0");
                    let line = '<tr id="qslprint_'+id+'">';
					let freq_or_band = $('#frequency_or_band').val();

					line += '<td style=\'text-align: center\'><div class="form-check"><input class="form-check-input" type="checkbox" /></div></td>';
                    line += '<td style="text-align: center" data-search="' + searchCallSign + '">';
                    line += '<span class="qso_call">';
                    line += '<a id="edit_qso" href="javascript:displayQso(' + id + ');">';
                    line += formattedCallSign;
                    line += '</a>';
                    line += '<a target="_blank" href="https://www.qrz.com/db/' + formattedCallSign + '">';
                    line += '<img width="16" height="16" src="' + base_url + 'images/icons/qrz.png" alt="Lookup ' + formattedCallSign + ' on QRZ.com">';
                    line += '</a> ';
                    line += '<a target="_blank" href="https://www.hamqth.com/' + formattedCallSign + '">';
                    line += '<img width="16" height="16" src="' + base_url + 'images/icons/hamqth.png" alt="Lookup ' + formattedCallSign + ' on HamQTH">';
                    line += '</a> ';
                    line += '<a target="_blank" href="https://www.eqsl.cc/Member.cfm?' + formattedCallSign + '">';
                    line += '<img width="16" height="16" src="' + base_url + 'images/icons/eqsl.png" alt="Lookup ' + formattedCallSign + ' on eQSL.cc">';
                    line += '</a>';
                    line += '</span>';
                    line += '</td>';
					line += '<td style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(1)").text()+'</td>';
					line += '<td style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(2)").text()+'</td>';
					line += '<td style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(3)").text()+'</td>';
					if (freq_or_band === 'band') {
						line += '<td class=\'col-band\' style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(4)").text()+'</td>';
						line += '<td class=\'col-freq\' style=\'text-align: center; display:none;\'>'+$("#qsolist_"+id).find("td:eq(5)").text()+'</td>';
					} else if (freq_or_band === 'frequency') {
						line += '<td class=\'col-band\' style=\'text-align: center; display:none;\'>'+$("#qsolist_"+id).find("td:eq(4)").text()+'</td>';
						line += '<td class=\'col-freq\' style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(5)").text()+'</td>';
					} else {
						line += '<td class=\'col-band\' style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(4)").text()+'</td>';
						line += '<td class=\'col-freq\' style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(5)").text()+'</td>';
					}
					line += '<td style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(6)").text()+'</td>';
					line += '<td style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(7)").text()+'</td>';
					line += '<td style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(10)").text()+'</td>';
					line += '<td style=\'text-align: center\'><span class="badge text-bg-light">'+$("#qsolist_"+id).find("td:eq(8)").text()+'</span></td>';
					line += '<td style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(9)").text()+'</td>';
					line += '<td style=\'text-align: center\'>'+$("#qsolist_"+id).find("td:eq(11)").text()+'</td>';
					let prev_qsl_html = $("#qsolist_"+id).find("td:eq(12)").html();
					line += '<td style=\'text-align: center; white-space: nowrap;\'>'+prev_qsl_html+'</td>';
					line += '<td style=\'text-align: center; white-space: nowrap;\'><div class="d-inline-flex align-items-center gap-1"><button onclick="mark_qsl_sent('+id+', \'B\')" class="btn btn-sm btn-success" data-bs-toggle="tooltip" data-bs-title="'+lang_qslprint_action_mark+'"><i class="fa fa-check"></i></button><button onclick="deleteFromQslQueue('+id+')" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" data-bs-title="'+lang_qslprint_action_remove+'"><i class="fas fa-trash-alt"></i></button><button onclick="openQsoList(\''+$("#qsolist_"+id).find("td:eq(0)").text()+'\')" class="btn btn-sm btn-success" data-bs-toggle="tooltip" data-bs-title="'+lang_qslprint_action_qsolist+'"><i class="fas fa-search"></i></button></div></td>';
                    line += '</tr>';
                    if ($.fn.dataTable.isDataTable('#qslprint_table')) {
                        $('#qslprint_table').DataTable().row.add($(line)).draw(false);
                    } else {
                        $('#qslprint_table tr:last').after(line);
                    }
                    $('#qslprint_'+id+' [data-bs-toggle="tooltip"]').tooltip();
                    $("#qsolist_"+id).remove();
                },
                error: function() {
					console.error('Error adding QSO to print queue.');
				}
            });
}

$(".station_id").change(function(){
	var station_id = $(".station_id").val();
	$.ajax({
		url: base_url + 'index.php/qslprint/get_qsos_for_print_ajax',
		type: 'post',
		data: {'station_id': station_id},
		success: function(html) {
			$('.resulttable').empty();
			$('.resulttable').append(html);
			initQslprintTable();
		}
	});
});

function initQslprintTable() {
	if (!$.fn.dataTable.isDataTable('#qslprint_table')) {
		$('#qslprint_table').DataTable({
			stateSave: true,
			autoWidth: false,
			orderCellsTop: true,
			ordering: true,
			order: [],
			columnDefs: [
				{ orderable: false, targets: 0 }
			],
			pageLength: 25,
			lengthMenu: [
				[10, 25, 50, 100, -1],
				[10, 25, 50, 100, lang_export_qslprint_pagination_all]
			],
			paging: 'pagination',
			language: {
				url: getDataTablesLanguageUrl(),
			},
			initComplete: function () {
				var api = this.api();
				// Filter dropdowns live in their own (second) header row
				var $filterCells = $('#qslprint_table thead tr').last().find('th');
				api.columns('.select-filter').every(function () {
					var column = this;
					var $cell = $filterCells.eq(column.index());
					if (!$cell.length) { return; }
					var select = $('<select class="form-select form-select-sm" style="width:100%;"><option value=""></option></select>')
						.appendTo($cell.empty())
						.on('click', function (e) { e.stopPropagation(); })	// keep dropdown clicks from re-sorting the column
						.on('change', function () {
							var val = $.fn.dataTable.util.escapeRegex($(this).val());
							column.search(val ? '^' + val + '$' : '', true, false).draw();
						});
					if ($cell.hasClass('select-filter-html')) {
						// Body cell holds HTML (e.g. Callsign links); build options from the
						// plain text stored in data-search so values/labels stay clean and safe.
						var seen = {}, labels = [];
						column.nodes().to$().each(function () {
							var label = $(this).attr('data-search') || $(this).text().trim();
							if (label && !seen.hasOwnProperty(label)) {
								seen[label] = true;
								labels.push(label);
							}
						});
						labels.sort();
						$.each(labels, function (i, label) {
							$('<option>').val(label).text(label).appendTo(select);
						});
					} else {
						column.data().unique().sort().each(function (d) {
							select.append('<option value="' + d + '">' + d + '</option>');
						});
					}
					// Reflect any stateSave-restored column search back into the dropdown
					var saved = column.search();
					if (saved) {
						select.val(saved.replace(/^\^|\$$/g, ''));
					}
				});
			}
		});
	}
}
initQslprintTable();

function showOqrs(id) {
	$.ajax({
		url: base_url + 'index.php/qslprint/show_oqrs',
		type: 'post',
		data: {'id': id},
		success: function(html) {
			BootstrapDialog.show({
				title: 'OQRS',
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'qso-dialog',
				nl2br: false,
				message: html,
				onshown: function(dialog) {
					$('[data-bs-toggle="tooltip"]').tooltip();
				},
				buttons: [{
					label: lang_admin_close,
					action: function (dialogItself) {
						dialogItself.close();
					}
				}]
			});
		}
	});
}

function mark_qsl_sent(id, method) {
    $.ajax({
        url: base_url + 'index.php/qso/qsl_sent_ajax',
        type: 'post',
        data: {'id': id,
            'method': method
        },
        success: function(data) {
            if (data.message == 'OK') {
                removeQslRow(id); // removes choice from menu
            }
            else {
                $(".container").append('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>You are not allowed to update QSL status!</div>');
            }
        }
    });
}

var target = document.body;
var box_observer = new MutationObserver(function() {
	$('#checkBoxAll').change(function (event) {
		if (this.checked) {
			$('.qslprint tbody tr').each(function (i) {
				$(this).closest('tr').addClass('activeRow');
				$(this).closest('tr').find("input[type=checkbox]").prop("checked", true);
			});
		} else {
			$('.qslprint tbody tr').each(function (i) {
				$(this).closest('tr').removeClass('activeRow');
				$(this).closest('tr').find("input[type=checkbox]").prop("checked", false);
			});
		}
	});
	$('.qslprint').on('click', 'input[type="checkbox"]', function() {
		if ($(this).is(":checked")) {
			$(this).closest('tr').addClass('activeRow');
		} else {
			$(this).closest('tr').removeClass('activeRow');
		}
	});


});
var config = { childList: true, subtree: true};
box_observer.observe(target, config);


function markSelectedQsos() {
	var elements = $('.qslprint tbody input:checked');
	var nElements = elements.length;
	if (nElements == 0) {
		BootstrapDialog.alert({
			title: lang_qslprint_info,
			message: lang_qslprint_select_at_least_one_row,
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return;
	}
	$('.markallprinted').prop("disabled", true);
	var id_list=[];
	elements.each(function() {
		let id = $(this).first().closest('tr').attr('id');
		id = id.match(/\d/g);
		id = id.join("");
		id_list.push(id);
	});
	$.ajax({
		url: base_url + 'index.php/logbookadvanced/update_qsl',
		type: 'post',
		data: {'id': JSON.stringify(id_list, null, 2),
			'sent' : 'Y',
			'method' : ''
		},
		success: function(data) {
			if (data !== []) {
				$.each(data, function(k, v) {
					removeQslRow(this.qsoID);
				});
			}
			$('.markallprinted').prop("disabled", false);
		}
	});
}

function removeSelectedQsos() {
	var elements = $('.qslprint tbody input:checked');
	var nElements = elements.length;
	if (nElements == 0) {
		BootstrapDialog.alert({
			title: lang_qslprint_info,
			message: lang_qslprint_select_at_least_one_row,
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return;
	}
	$('.removeall').prop("disabled", true);

	var id_list=[];
	elements.each(function() {
		let id = $(this).first().closest('tr').attr('id');
		id = id.match(/\d/g);
		id = id.join("");
		id_list.push(id);
	});

	$.ajax({
		url: base_url + 'index.php/logbookadvanced/update_qsl',
		type: 'post',
		data: {'id': JSON.stringify(id_list, null, 2),
			'sent' : 'N',
			'method' : ''
		},
		success: function(data) {
			if (data !== []) {
				$.each(data, function(k, v) {
					removeQslRow(this.qsoID);
				});
			}
			$('.removeall').prop("disabled", false);
		}
	});
}

function exportSelectedQsos() {
	var elements = $('.qslprint tbody input:checked');
	var nElements = elements.length;
	if (nElements == 0) {
		BootstrapDialog.alert({
			title: lang_qslprint_info,
			message: lang_qslprint_select_at_least_one_row,
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return;
	}
	$('.exportselected').prop("disabled", true);

	var id_list=[];
	elements.each(function() {
		let id = $(this).first().closest('tr').attr('id');
		id = id.match(/\d/g);
		id = id.join("");
		id_list.push(id);
	});

	xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function() {
		var a;
		if (xhttp.readyState === 4 && xhttp.status === 200) {
			// Trick for making downloadable link
			a = document.createElement('a');
			a.href = window.URL.createObjectURL(xhttp.response);
			// Give filename you wish to download
			// Get the current date and time
			const now = new Date();

			// Format the date and time as UTC Ymd-Hi
			const year = now.getUTCFullYear();
			const month = String(now.getUTCMonth() + 1).padStart(2, '0'); // Months are zero-based
			const day = String(now.getUTCDate()).padStart(2, '0');
			const hours = String(now.getUTCHours()).padStart(2, '0');
			const minutes = String(now.getUTCMinutes()).padStart(2, '0');

			// Create the formatted filename
			const filename = `${my_call}-${year}${month}${day}-${hours}${minutes}.adi`;
			a.download = filename;
			a.style.display = 'none';
			document.body.appendChild(a);
			a.click();
		}
	};

	// Post data to URL which handles post request
	xhttp.open("POST", site_url+'/logbookadvanced/export_to_adif', true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	// You should set responseType as blob for binary responses
	xhttp.responseType = 'blob';
	xhttp.send("id=" + JSON.stringify(id_list, null, 2));

	$('.exportselected').prop("disabled", false);
}

function markMethod(){

	//grab the dropdown
	const select = document.getElementById('markqslmethod');

	//grab the selected method
	const methodkey = select.value;
	const method = select.options[select.selectedIndex].text;

	//perform function
	markMethodQSOs(methodkey === "ALL" ? '' : method);
}

function markMethodQSOs(method) {

	//unmark any QSO that is already marked for cleanup purposes
	unmarkallQSOs();

	//grab the table
	const table = document.getElementById('qslprint_table');

    //loop through each row except the header
    Array.from(table.tBodies[0].rows).forEach(row => {

		//get the send-method column
        const sendMethodCell = row.querySelector('td.send-method');

		//check if it contains the right method (or skip check if method is empty)
        if (sendMethodCell && (method === "" || sendMethodCell.textContent.trim() === method)) {

			//find the checkbox in the first cell
            const checkbox = row.querySelector('td:first-child input[type="checkbox"]');

			//check that box
			if (checkbox) {
                checkbox.checked = true;
            }
        }
    });
}

function unmarkallQSOs(){

	//grab the table
	const table = document.getElementById('qslprint_table');

	//loop through each row except the header
    Array.from(table.tBodies[0].rows).forEach(row => {

		//find the checkbox in the first cell
		const checkbox = row.querySelector('td:first-child input[type="checkbox"]');

		//check that box
		if (checkbox) {
			checkbox.checked = false;
		}
    });
}

function switchbandandfrequencydisplay(mode){

	//switch state according to selected value. Default case = band
	switch(mode) {
	case 'band':
		bandcols = document.querySelectorAll('.col-band');
		bandcols.forEach(cell => { cell.style.display = '';});
		freqcols = document.querySelectorAll('.col-freq');
		freqcols.forEach(cell => { cell.style.display = 'none';});
		break;
	case 'frequency':
		bandcols = document.querySelectorAll('.col-band');
		bandcols.forEach(cell => { cell.style.display = 'none';});
		freqcols = document.querySelectorAll('.col-freq');
		freqcols.forEach(cell => { cell.style.display = '';});
		break;
	case 'both':
		bandcols = document.querySelectorAll('.col-band');
		bandcols.forEach(cell => { cell.style.display = '';});
		freqcols = document.querySelectorAll('.col-freq');
		freqcols.forEach(cell => { cell.style.display = '';});
		break;
	default:
		bandcols = document.querySelectorAll('.col-band');
		bandcols.forEach(cell => { cell.style.display = '';});
		freqcols = document.querySelectorAll('.col-freq');
		freqcols.forEach(cell => { cell.style.display = 'none';});
		break;
	}
}

document.getElementById('frequency_or_band').addEventListener('change', function (event) {
	//switch display options
	switchbandandfrequencydisplay(event.target.value);
});

function printDialog(printType, printAll = false) {
	const id_list = getSelectedIds();
	if (id_list.length === 0 && !printAll) {
		BootstrapDialog.alert({
			title: lang_qslprint_info,
			message: lang_qslprint_select_at_least_one_row,
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return;
	}
	let title = '';
	if (printType === 'label') {
		title = lang_print_label;
	} else if (printType === 'qslcard') {
		title = lang_print_qslcard;
	}

	$.ajax({
		url: base_url + 'index.php/qslprint/printdialog',
		type: 'post',
		data: {'printType': printType, 'id_list': id_list, 'printAll': printAll},
		success: function (html) {
			BootstrapDialog.show({
				title: '<i class="fas fa-print me-2"></i>' + title,
				size: BootstrapDialog.SIZE_NORMAL,
				cssClass: 'qso-dialog',
				nl2br: false,
				message: html,
				onshown: function(dialog) {
				},
				buttons: [
					{
						label: lang_admin_close,
						cssClass: 'btn btn-secondary btn-sm',
						action: function (dialogItself) {
							dialogItself.close();
						}
					}
				],
			});
		}
	});
}

function requestedQslAction(action) {
	var url = base_url + 'index.php/qslprint/' + action + '/' + $('.station_id').val();
	// if someone wants to export we don't need to confirm, just do it
	if (action !== 'qsl_printed') {
		window.location.href = url;
		return;
	}
	BootstrapDialog.confirm({
			title: lang_qslprint_warning,
			message: lang_qslprint_are_you_sure,
			type: BootstrapDialog.TYPE_DANGER,
			closable: true,
			draggable: true,
			btnOKClass: 'btn-danger',
			callback: function(result) {
				if(result) {
					window.location.href = url;
				}
			},
		});
}

function getSelectedIds() {
	let id_list = [];
	$('#qslprint_table tbody input[name="selected_qsos[]"]:checked').each(function () {
		id_list.push($(this).val());
	});
	return id_list;
}

function printSelectedQsos(printAll) {
	if (printAll == true) {
		const tpl = document.getElementById('qslcard_template_id').value;
        // Print options come from the template's layout.options, not this form.
        window.open(base_url + 'index.php/qslpostcard/pdfqueue/' + tpl, '_blank');
	} else {
		let id_list = getSelectedIds();
		let $container = $('#qslcard_selected_ids');
		if (id_list.length) {
			$container.empty();
			$.each(id_list, function (i, id) {
				$('<input>').attr({ type: 'hidden', name: 'selected_ids[]' }).val(id).appendTo($container);
			});
		}
		let tplId = $('#qslcard_template_id').val();
		if (!tplId) {
			return;
		}
		let $form = $('#printQslCardForm');
		$form.attr('action', base_url + 'index.php/qslpostcard/pdfselected/' + tplId);
		$form.attr('target', '_blank');
		$form[0].submit();
	}
}

function saveAndPrintSelectedQsos(printAll) {
	if (printAll == true) {
		const tpl = document.getElementById('qslcard_template_id').value;
        window.location.href  = base_url + 'index.php/qslpostcard/pdfqueue/' + tpl + '?download=1', '_blank';
	} else {
		let id_list = getSelectedIds();
		let $container = $('#qslcard_selected_ids');
		if (id_list.length) {
			$container.empty();
			$.each(id_list, function (i, id) {
				$('<input>').attr({ type: 'hidden', name: 'selected_ids[]' }).val(id).appendTo($container);
			});
		}
		var tplId = $('#qslcard_template_id').val();
		if (!tplId) {
			return;
		}
		var $form = $('#printQslCardForm');
		$form.attr('action', base_url + 'index.php/qslpostcard/pdfselected/' + tplId + '?download=1');
		$form.attr('target', '_blank');
		$form[0].submit();
		dialog.close();
	}
}

function printLabel(printAll) {
	const id_list = getSelectedIds();
	const options = {
			'startat': $('#startat').val(),
			'grid': $('#gridlabel')[0].checked,
			'via': $('#via')[0].checked,
			'tnxmsg': $('#tnxmsg')[0].checked,
			'qslmsg': $('#qslmsg')[0].checked,
			'reference': $('#reference')[0].checked,
			'mycall': $('#mycall')[0].checked,
			'opcall': $('#opcall')[0].checked
		};
	let url, postData;
	if (printAll == true) {
		url = base_url + 'index.php/labels/print/All';
		postData = options;
	} else {
		url = base_url + 'index.php/labels/printids';
		postData = $.extend({'id': JSON.stringify(id_list, null, 2)}, options);
	}
	$.ajax({
		url: url,
		type: 'post',
		data: postData,
		xhr:function(){
			var xhr = new XMLHttpRequest();
			xhr.responseType= 'blob'
			return xhr;
		},
		success: function(data) {
			if(data){
				var file = new Blob([data], {type: 'application/pdf'});
				var fileURL = URL.createObjectURL(file);
				window.open(fileURL);
			}
			$('#printLabel').prop("disabled", false);
		},
		error: function (data) {
			BootstrapDialog.alert({
				title: lang_gen_advanced_logbook_error,
				message: lang_gen_advanced_logbook_label_print_error,
				type: BootstrapDialog.TYPE_DANGER,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
			$.each(id_list, function(k, v) {
				unselectQsoID(this);
			});
			$('#printLabel').prop("disabled", false);
		},
	});
}

function markQslPrinted(printAll) {
	$('#button_markprint').attr("disabled", true).addClass("running");
	if (printAll == true) {
		$.ajax({
			url: base_url + 'index.php/qslprint/qsl_printed/all',
			type: 'get',
			success: function () {
				if ($.fn.dataTable.isDataTable('#qslprint_table')) {
					$('#qslprint_table').DataTable().clear().draw();
				}
				$('#button_markprint').removeClass("running");
			},
			error: function () {
				$('#button_markprint').prop("disabled", false).removeClass("running");
			}
		});
	} else {
		let id_list = getSelectedIds();
		if (!id_list.length) {
			$('#button_markprint').prop("disabled", false).removeClass("running");
			return;
		}
		$.ajax({
			url: base_url + 'index.php/logbookadvanced/update_qsl',
			type: 'post',
			data: {
				'id': JSON.stringify(id_list, null, 2),
				'sent': 'Y',
				'method': ''
			},
			success: function (data) {
				if (data !== []) {
					$.each(data, function (k, v) {
						removeQslRow(this.qsoID);
					});
				}
				$('#button_markprint').prop("disabled", false).removeClass("running");
			},
			error: function () {
				$('#button_markprint').prop("disabled", false).removeClass("running");
			}
		});
	}
}
