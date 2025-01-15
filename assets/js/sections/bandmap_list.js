$(function() {

	function SortByQrg(a, b){
		var a = a.frequency;
		var b = b.frequency;
		return ((a< b) ? -1 : ((a> b) ? 1 : 0));
	}

	function get_dtable () {
		var table = $('.spottable').DataTable({
			"paging": false,
			"retrieve": true,
			"language": {
				url: getDataTablesLanguageUrl(),
			},
			'columnDefs': [
				{
					'targets': 1, "type":"num",
					'createdCell':  function (td, cellData, rowData, row, col) {
						$(td).addClass("kHz"); 
					}
				},
				{
					'targets': 2,
					'createdCell':  function (td, cellData, rowData, row, col) {
						$(td).addClass("spotted_call"); 
						$(td).attr( "title", lang_click_to_prepare_logging);
					}
				}
			],
			"language": {
				url: getDataTablesLanguageUrl(),
			}
		});
		return table;
	}

	function fill_list(band,de,maxAgeMinutes,cwn) {
		// var table = $('.spottable').DataTable();
		var table = get_dtable();
		if ((band != '') && (band !== undefined)) {
			let dxurl = dxcluster_provider + "/spots/" + band + "/" +maxAgeMinutes + "/" + de;
			$.ajax({
				url: dxurl,
				cache: false,
				dataType: "json"
			}).done(function(dxspots) {
				table.page.len(50);
				let oldtable=table.data();
				table.clear();
				let spots2render=0;
				if (dxspots.length>0) {
					dxspots.sort(SortByQrg);
					dxspots.forEach((single) => {
						if ((cwn == 'wkd') && (!(single.worked_dxcc))) { return; }
						if ((cwn == 'cnf') && (!(single.cnfmd_dxcc))) { return; }
						if ((cwn == 'ucnf') && ((single.cnfmd_dxcc))) { return; }
						spots2render++;
						var data=[];
						if (single.cnfmd_dxcc) {
							dxcc_wked_info="text-success";
						} else if (single.worked_dxcc) {
							dxcc_wked_info="text-warning";
						} else {
							dxcc_wked_info="text-danger";
						}
						if (single.cnfmd_call) {
							wked_info="text-success";
						} else if (single.worked_call) {
							wked_info="text-warning";
						} else {
							wked_info="";
						}
						lotw_badge='';
						lclass='';
						if (single.dxcc_spotted.lotw_user) {
							$('#lotw_info').text("LoTW");
							if (single.dxcc_spotted.lotw_user > 365) {
								lclass='lotw_info_red';
							} else if (single.dxcc_spotted.lotw_user > 30) {
								lclass='lotw_info_orange';
							} else if (single.dxcc_spotted.lotw_user > 7) {
								lclass='lotw_info_yellow';
							}
							lotw_badge='<a id="lotw_badge" style="float: right;" href="https://lotw.arrl.org/lotwuser/act?act='+single.spotted+'" target="_blank"><small id="lotw_infox" class="badge text-bg-success '+lclass+'" data-bs-toggle="tooltip" title="LoTW User. Last upload was '+single.dxcc_spotted.lotw_user+' days ago">L</small></a>';
						}
							
						data[0]=[];
						data[0].push(single.when_pretty);
						data[0].push(single.frequency*1);
						wked_info=((wked_info != '' ?'<span class="'+wked_info+'">' : '')+'<span id="prepcall">'+single.spotted+'</span>'+(wked_info != '' ? '</span>' : ''));
						spotted=wked_info+lotw_badge;
						data[0].push(spotted);
						if (single.dxcc_spotted.flag) {
							dxcc_wked_info=((dxcc_wked_info != '' ?'<span class="'+dxcc_wked_info+'">' : '')+single.dxcc_spotted.flag+' '+single.dxcc_spotted.entity+(dxcc_wked_info != '' ? '</span>' : ''));
						} else {
							dxcc_wked_info=((dxcc_wked_info != '' ?'<span class="'+dxcc_wked_info+'">' : '')+single.dxcc_spotted.entity+(dxcc_wked_info != '' ? '</span>' : ''));
						}
						data[0].push('<a href="javascript:spawnLookupModal(\''+single.dxcc_spotted.dxcc_id+'\',\'dxcc\')";>'+dxcc_wked_info+'</a>');
						data[0].push(single.spotter);
						data[0].push(single.message || '');
						if (single.worked_call) {
							data[0].push(single.last_wked.LAST_QSO+' in '+single.last_wked.LAST_MODE);
						} else {
							data[0].push('');
						}
						if (oldtable.length > 0) {
							let update=false;
							oldtable.each( function (srow) {
								if (JSON.stringify(srow) === JSON.stringify(data[0])) {
									update=true;
								} 
							});
							if (!update) { 	// Sth. Fresh? So highlight
								table.rows.add(data).draw().nodes().to$().addClass("fresh");
							} else { 
								table.rows.add(data).draw(); 
							}
						} else {
							table.rows.add(data).draw();
						}
					});
					setTimeout(function(){	// Remove Highlights
						$(".fresh").removeClass("fresh");
					},10000);
				} else {
					table.clear();
					table.draw();
				}
				if (spots2render == 0) {
					table.clear();
					table.draw();
				}
			});
		} else {
			table.clear();
			table.draw();
		}
	}

	function highlight_current_qrg(qrg) {
		var table=get_dtable();
		// var table=$('.spottable').DataTable();
		table.rows().every(function() {
			var d=this.data();
			var distance=Math.abs(parseInt(d[1])-qrg);
			if (distance<=20) {
				distance++;
				alpha=(.5/distance);
				this.nodes().to$().css('--bs-table-bg', 'rgba(0,0,255,' + alpha + ')');
				this.nodes().to$().css('--bs-table-accent-bg', 'rgba(0,0,255,' + alpha + ')');
			} else {
				this.nodes().to$().css('--bs-table-bg', '');
				this.nodes().to$().css('--bs-table-accent-bg', '');
			}
		});
	}

	var table=get_dtable();
	table.order([1, 'asc']);
	table.clear();
	fill_list($('#band option:selected').val(), $('#decontSelect option:selected').val(),dxcluster_maxage,$('#cwnSelect option:selected').val());
	setInterval(function () { fill_list($('#band option:selected').val(), $('#decontSelect option:selected').val(),dxcluster_maxage,$('#cwnSelect option:selected').val()); },60000);

	$("#cwnSelect").on("change",function() {
		table.clear();
		fill_list($('#band option:selected').val(), $('#decontSelect option:selected').val(),dxcluster_maxage,$('#cwnSelect option:selected').val());
	});

	$("#decontSelect").on("change",function() {
		table.clear();
		fill_list($('#band option:selected').val(), $('#decontSelect option:selected').val(),dxcluster_maxage,$('#cwnSelect option:selected').val());
	});

	$("#band").on("change",function() {
		table.clear();
		fill_list($('#band option:selected').val(), $('#decontSelect option:selected').val(),dxcluster_maxage,$('#cwnSelect option:selected').val());
	});

	$("#spottertoggle").on("click", function() {
		if (table.column(4).visible()) {
			table.column(4).visible(false);
		} else {
			table.column(4).visible(true);
		}
	});

	let qso_window_last_seen=Date.now()-3600;
	let bc_qsowin = new BroadcastChannel('qso_window');
	let pong_rcvd = false;

	bc_qsowin.onmessage = function (ev) {
		if (ev.data == 'pong') {
			qso_window_last_seen=Date.now();
			pong_rcvd = true;
		}
	};

	setInterval(function () {
		// reset the pong flag if the last seen time is older than 1 second in case the qso window was closed
		if (qso_window_last_seen < (Date.now()-1000)) {
			pong_rcvd = false;
		}
		bc_qsowin.postMessage('ping');
	},500);
	
	let bc2qso = new BroadcastChannel('qso_wish');

	// set some times
	let wait4pong = 2000; // we wait in max 2 seconds for the pong
	let check_intv = 100; // check every 100 ms

	$(document).on('click','#prepcall', function() {
		let ready_listener = true;
		let call=this.innerText;
		let qrg=''
		if (this.parentNode.parentNode.className.indexOf('spotted_call')>=0) {
			qrg=this.parentNode.parentNode.parentNode.cells[1].textContent*1000;
		} else {
			qrg=this.parentNode.parentNode.cells[1].textContent*1000;
		}

		try {
			irrelevant=fetch(CatCallbackURL + '/'+qrg).catch(() => {
				openedWindow = window.open(CatCallbackURL + '/' + qrg);
				openedWindow.close();
			});
		} finally {}

		let check_pong = setInterval(function() {
			if (pong_rcvd || ((Date.now() - qso_window_last_seen) < wait4pong)) {
                clearInterval(check_pong); // max time reached or pong received
				bc2qso.postMessage({ frequency: qrg, call: call });
			} else {
				clearInterval(check_pong);
				let cl={};
				cl.call=call;
				cl.qrg=qrg;

				let newWindow = window.open(base_url + 'index.php/qso?manual=0', '_blank');

                if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                    $('#errormessage').html(popup_warning).addClass('alert alert-danger').show();
					setTimeout(function() {
						$('#errormessage').fadeOut();
					}, 3000);
                } else {
                    newWindow.focus();
                }

				// wait for the ready message
                bc2qso.onmessage = function(ev) {
					if (ready_listener == true) {
						if (ev.data === 'ready') {
							bc2qso.postMessage({ frequency: cl.qrg, call: cl.call })
							ready_listener = false;
						}
					}
				};
			}
		}, check_intv);
	});

	$("#menutoggle").on("click", function() {
		if ($('.navbar').is(":hidden")) {
			$('.navbar').show();
			$('#dxtabs').show();
			$('#dxtitle').show();
			$('#menutoggle_i').removeClass('fa-arrow-down');
			$('#menutoggle_i').addClass('fa-arrow-up');
		} else {
			$('.navbar').hide();
			$('#dxtabs').hide();
			$('#dxtitle').hide();
			$('#menutoggle_i').removeClass('fa-arrow-up');
			$('#menutoggle_i').addClass('fa-arrow-down');
		}
	});
	
	var CatCallbackURL = "http://127.0.0.1:54321";
	var updateFromCAT = function() {

	if($('select.radios option:selected').val() != '0') {
		radioID = $('select.radios option:selected').val();
		$.getJSON( base_url+"index.php/radio/json/" + radioID, function( data ) {

			if (data.error) {
				if (data.error == 'not_logged_in') {
					$(".radio_cat_state" ).remove();
					if($('.radio_login_error').length == 0) {
						$('.messages').prepend('<div class="alert alert-danger radio_login_error" role="alert"><i class="fas fa-broadcast-tower"></i> You\'re not logged it. Please <a href="'+base_url+'">login</a></div>');
					}
				}
				// Put future Errorhandling here
			} else {
				if($('.radio_login_error').length != 0) {
					$(".radio_login_error" ).remove();
				}
				var band = frequencyToBand(data.frequency);
				CatCallbackURL=data.cat_url;
				if (band !== $("#band").val()) {
					$("#band").val(band);
					$("#band").trigger("change");
				}

				var minutes = Math.floor(cat_timeout_interval / 60);

				if(data.updated_minutes_ago > minutes) {
					$(".radio_cat_state" ).remove();
					if($('.radio_timeout_error').length == 0) {
						$('.messages').prepend('<div class="alert alert-danger radio_timeout_error" role="alert"><i class="fas fa-broadcast-tower"></i> Radio connection timed-out: ' + $('select.radios option:selected').text() + ' data is ' + data.updated_minutes_ago + ' minutes old.</div>');
					} else {
						$('.radio_timeout_error').html('Radio connection timed-out: ' + $('select.radios option:selected').text() + ' data is ' + data.updated_minutes_ago + ' minutes old.');
					}
				} else {
					$(".radio_timeout_error" ).remove();
					text = '<i class="fas fa-broadcast-tower"></i><span style="margin-left:10px;"></span><b>TX:</b> '+(Math.round(parseInt(data.frequency)/100)/10000).toFixed(4)+' MHz';
					highlight_current_qrg((parseInt(data.frequency))/1000);
					if(data.mode != null) {
						text = text+'<span style="margin-left:10px"></span>'+data.mode;
					}
					if(data.power != null && data.power != 0) {
						text = text+'<span style="margin-left:10px"></span>'+data.power+' W';
					}
					if (! $('#radio_cat_state').length) {
						$('.messages').prepend('<div aria-hidden="true"><div id="radio_cat_state" class="alert alert-success radio_cat_state" role="alert">'+text+'</div></div>');
					} else {
						$('#radio_cat_state').html(text);
					}
				}
			}
		});
	}
};

$.fn.dataTable.moment(custom_date_format + ' HH:mm');
// Update frequency every three second
setInterval(updateFromCAT, 3000);

// If a radios selected from drop down select radio update.
$('.radios').change(updateFromCAT);

});
