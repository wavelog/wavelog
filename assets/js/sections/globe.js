$(document).ready(function() {
	main();

	async function main() {
		let x=await getQsos();
		globePayArc=[];
		globePayLab=[];
		x.forEach((element) => {
			let OneQsoArc={};
			OneQsoArc.startLat=element.latlng1[0];
			OneQsoArc.startLng=element.latlng1[1];
			OneQsoArc.endLat=element.latlng2[0];
			OneQsoArc.endLng=element.latlng2[1];
			OneQsoArc.name=element.callsign;
			if (element.confirmed) {
				OneQsoArc.color = 'green';
			} else {
				OneQsoArc.color = 'red';
			}
			// OneQsoArc.color = [['red', 'white', 'blue', 'green'][Math.round(Math.random() * 3)], ['red', 'white', 'blue', 'green'][Math.round(Math.random() * 3)]]
			OneQsoArc.altitude=0.15;
			globePayArc.push(OneQsoArc);
			let OneQsoLab={};
			OneQsoLab.lat=element.latlng2[0];
			OneQsoLab.lng=element.latlng2[1];
			OneQsoLab.text=element.callsign;
			globePayLab.push(OneQsoLab);
		});
		renderGlobe(globePayArc,globePayLab);
	}

	async function getQsos() {
		let fdata=new FormData();
		fdata.append('de','1');
		fdata.append('qsoresults','100');

		const response = await fetch(base_url + 'logbookadvanced/mapQsos', {
			method: "POST",
			mode: "cors",
			cache: "no-cache",
			credentials: "same-origin",
			redirect: "follow",
			referrerPolicy: "no-referrer",
			body: fdata,
		});
		return response.json();
	}

	function renderGlobe(arcsData,labelData) {
		Globe()
			.globeImageUrl(base_url + '/assets/images/earth-blue-marble.jpg')
			.labelsData(labelData)
			.arcsData(arcsData)
			.arcColor('color')
			//.arcAltitude('altitude')
			.arcAltitudeAutoScale(.3)
			.arcStroke(.2)
			.arcDashLength(() => 1)
			.arcDashGap(() => 0)
			.arcDashAnimateTime(() => 4000 + 500)
		(document.getElementById('globeViz'))
	}
});
