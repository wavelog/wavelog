var maxPerc = 0;

document.addEventListener("DOMContentLoaded", function() {
  document.querySelectorAll('.dropdown').forEach(dd => {
		dd.addEventListener('hide.bs.dropdown', function (e) {
			if (e.clickEvent && e.clickEvent.target.closest('.dropdown-menu')) {
				e.preventDefault(); // stop Bootstrap from closing
			}
		});
	});
});

var map;
var maidenhead;
var percFilterMin = 0;
var percFilterMax = 100;

function gridPlot(form) {
    // If map is already initialized
    var container = L.DomUtil.get('gridsquare_map');

    if(container != null){
        container._leaflet_id = null;
        container.remove();
        $("#gridmapcontainer").append('<div id="gridsquare_map" class="map-leaflet" style="width: 100%;"></div>');
        set_map_height(50);
    }
    $.ajax({
       url: site_url+'/mostwantedgrids/getGridsjs',
       type: 'get',
       success: function(data) {
          mwgrids = data.grid_4char;
          plot();
       },
       error: function () {
           showToast('Most Wanted Grids', 'Failed to load grid data', 'bg-danger text-white', 3000);
       },
    });
}

function plot() {
            let layer = L.tileLayer(jslayer, {
                maxZoom: 12,
                attribution: jsattribution,
                id: 'mapbox.streets'
            });

            let mapCenter = [19, 0];
            let mapZoom = 3;
            for (key in mwgrids) {
                if (mwgrids[key] > maxPerc) {
                    maxPerc = mwgrids[key];
                }
            }
            var slider = document.getElementById("perc_max");
            slider.value = maxPerc;
            var fill = document.getElementById('perc_fill');
            fill.style.right = (100 - maxPerc) + '%';

            map = L.map('gridsquare_map', {
            layers: [layer],
            center: mapCenter,
            zoom: mapZoom,
            minZoom: 2,
            maxZoom: 9,
            fullscreenControl: true,
                fullscreenControlOptions: {
                    position: 'topleft'
                },
            });

            maidenhead = L.maidenhead().addTo(map);
            map.on('mousemove', onMapMove);
            map.on('click', onMapClick);
}

// Query params for the shared gridsquare QSO modal (gridmodal.js)
gridModalQuery = function (loc_4char) {
	return {
		'Searchphrase': loc_4char,
		'Band': 'SAT',
		'Mode': 'All',
		'Sat': 'All',
		'Orbit': 'All',
		'Propagation': 'SAT',
		'Type': 'VUCC'
	};
};

function colorGradient(color1, color2, color3, percentage) {
	const scaleMax = Math.min(percFilterMax, maxPerc);
	// Scale to current max value
	percentage = scaleMax > 0 ? percentage * 100 / scaleMax : 0;

	// Parse RGBA color string
	function parseRGBA(color) {
		const values = color.match(/[\d.]+/g).map(Number);
		return {
			r: values[0],
			g: values[1],
			b: values[2],
			a: values[3] ?? 1
		};
	}

	const c1 = parseRGBA(color1);
	const c2 = parseRGBA(color2);
	const c3 = parseRGBA(color3);

	// Interpolate between two colors
	function interpolate(start, end, factor) {
		const r = Math.round(start.r + (end.r - start.r) * factor);
		const g = Math.round(start.g + (end.g - start.g) * factor);
		const b = Math.round(start.b + (end.b - start.b) * factor);
		const a = start.a + (end.a - start.a) * factor;

		return `rgba(${r}, ${g}, ${b}, ${a.toFixed(3)})`;
	}

	// First half: color1 -> color2
	if (percentage <= 50) {
		return interpolate(c1, c2, percentage / 50);
	}
	// Second half: color2 -> color3
	return interpolate(c2, c3, (percentage - 50) / 50);

}

$(document).ready(function(){
	gridPlot(this.form);
	$(window).resize(function () {
		set_map_height();
	});
	['perc_min', 'perc_max'].forEach(function(id) {
		document.getElementById(id).addEventListener('input', function() {
			var min = parseInt(document.getElementById('perc_min').value, 10);
			var max = parseInt(document.getElementById('perc_max').value, 10);
			if (min > max) {
				if (this.id === 'perc_min') {
					max = min;
					document.getElementById('perc_max').value = max;
				} else {
					min = max;
					document.getElementById('perc_min').value = min;
				}
			}
			percFilterMin = min;
			percFilterMax = max;
			document.getElementById('perc_min_val').textContent = min + '%';
			document.getElementById('perc_max_val').textContent = max + '%';
			var fill = document.getElementById('perc_fill');
			fill.style.left = min + '%';
			fill.style.right = (100 - max) + '%';
			if (maidenhead) {
				maidenhead.redraw();
			}
		});
	});
});
