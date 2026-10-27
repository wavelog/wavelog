#include <stdio.h>
#include <stdlib.h>
#include <math.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End local includes

// Local prototypes
double MirrorReflectionHeight(struct PathData path, struct ControlPt CP, double dh);
// End local prototypes

void ELayerScreeningFrequency(struct PathData *path) {

	/*

	 	ELayerScreeningFrequency() Calculates the E-Layer screening frequency for the F2 modes 
	 		from Section 4 E-layer maximum screening frequency (fs) ITU-R P533-11.
	 		
	 		INPUT
	 			struct PathData *path
	 	
	 		OUTPUT
	 			path->Md_F2[k].hr - Reflection height
	 			path->Md_F2[k].fs - E layer screening frequency

			SUBROUTINES
				MirrorReflectionHeight()
				ElevationAngle()
				IncidentAngle()
	 
	 */

	double dh;		// Hop distance
	double deltaf;	// Elevation angle calculated for F2 layer
	double foE;		// Critical frequency E layer
	double i;		// Angle of incidence

	int k;			// Temp
	
	// E layer screening is restricted to paths no longer than 4000 km.
	if(path->distance > 4000) return; 

	// Determine the foE for this calculation.
	if(path->distance <= 2000.0) {
		foE = path->CP[MP].foE;
	}
	else {
		foE = max(path->CP[T1k].foE, path->CP[R1k].foE);
	}

    // Now find the E-Layer screening for the F2 modes that exist.
	// The hop of the F2 mode is related to the index i. Since i is an C index use the hop number n = k + 1; 
	for(k=path->n0_F2; k<MAXF2MDS; k++) {
		// Determine the hop distance
		dh = path->distance/(k+1);

		if(path->distance <= path->dmax) {
			path->Md_F2[k].hr = MirrorReflectionHeight(*path, path->CP[MP], dh);

		}
		else if(path->distance > path->dmax){
			// In this case you have to find the mirror reflection height at all the control points and take the mean.
			// Assume that the hop distance is path->dmax.
			path->Md_F2[k].hr = (MirrorReflectionHeight(*path, path->CP[Td02], dh) +
					            MirrorReflectionHeight(*path, path->CP[MP], dh) +
					            MirrorReflectionHeight(*path, path->CP[Rd02], dh))/3.0;
		}

        // Find the elevation angle from equation 13 Section 5.1 Elevation angle.
		// ITU-R P.533-12
		deltaf = ElevationAngle(dh, path->Md_F2[k].hr);

		// angle of incidence at height hr = 110 km
		i = IncidenceAngle(deltaf, 110.0);
		
		// Now calculate the E layer screening frequency.
		if(path->distance <= 2000) {
			path->Md_F2[k].fs = (1.05 * foE)/cos(i);
		}
		else { // (path->distance > 2000)
			// Use the larger of the foE at the control points 1000 km from either end.
			path->Md_F2[k].fs = (1.05 * foE)/cos(i);
		}
    } // End for(k=path->n0_F2; k<MAXF2MODES; k++)

	return;

}

double MirrorReflectionHeight(struct PathData path, struct ControlPt CP, double d) {

	/*

	 	MirrorReflectionHeight() - Calculates the mirror reflection height by the method
	 		in ITU-R P.533-12 Section 5.1 "Elevation angle".
	 
	 		INPUT
	 			struct PathData path
	 			struct ControlPt CP - The control point of interest
	 			double d - The hop length
	 
	 		OUTPUT
	 			returns the mirror reflection height
	 
			SUBROUTINES
				None

	 */

	//Initialise to prevent - warning C4701: potentially uninitialized local variable 'hr' used
	double hr = 0; // Mirror reflection height

	// Temps
	double a, b, h, x, xr, y;
	double H, Z, J, U, G;
	double A1, A2, B1, B2, E1, E2, F1, F2;
	double deltaM;
	double ds, df;

	// Determine the critical frequency ratio
	x = CP.foF2/CP.foE;

	y = max(x, 1.8);

	deltaM = (0.18/(y - 1.4))+(0.096*(min(path.SSN,160) - 25.0)/(150.0));

	xr = path.frequency/CP.foF2;

	H = (1490.0/(CP.M3kF2 + deltaM)) - 316.0;

	if((x > 3.33) && (xr >= 1.0)) { // a)
		E1 = -0.09707*pow(xr, 3) + 0.6870*xr*xr - 0.7506*xr + 0.6;

		if(xr <= 1.71) {
			F1 = -1.862*pow(xr, 4) + 12.95*pow(xr, 3) - 32.03*xr*xr + 33.50*xr - 10.91;
		}
		else { // (xr > 1.71)
			F1 = 1.21 + 0.2*xr;
		}

        if(xr <= 3.7) {
			G = -2.102*pow(xr, 4) + 19.50*pow(xr, 3) - 63.15*xr*xr - 44.73;
		}
		else {
			G = 19.25;
		}

        ds = 160.0 + (H + 43.0)*G;
		a = (d - ds)/(H + 140.0);
		A1 = 140.0 + (H - 47.0)*E1;
		B1 = 150.0 + (H - 17.0)*F1 - A1;

		if((B1 >= 0.0) && (a >= 0.0)) {
			h = A1 + B1*pow(2.4, -a);
		}
		else {
			h = A1 + B1;
		}

        hr = min(h, 800.0);
	}
	else if ((x > 3.33) && (xr < 1.0)) { // b)
		Z = max(xr, 0.1);
		E2 = 0.1906*Z*Z + 0.00583*Z + 0.1936;
		A2 = 151.0 + (H - 47.0)*E2;
		F2 = 0.645*Z*Z + 0.883*Z + 0.162;
		B2 = 141.0 + (H - 24.0)*F2 - A2;
		df = min(0.115*d/(Z*(H + 140.0)), 0.65);
		b = -7.535*pow(df, 4) + 15.75*pow(df, 3) - 8.834*df*df - 0.378*df +1.0;

		if(B2 >= 0.0) {
			h = A2 + B2*b;
		}
		else {
			h = A2 + B2;
		}

        hr = min(h, 800.0);

	}
	else if(x <= 3.33) { // c

		J = -0.7126*pow(y, 3) + 5.863*y*y - 16.13*y + 16.07;
		U = 8.0E-5*(H - 80.0)*(1.0 + 11.0*pow(y, -2.2)) + 1.2E-3*H*pow(y, -3.6);
		hr = min((115.0 + H*J + U*d),800.0);

	}

    return hr;
}

double ElevationAngle(double dh, double hr) {

	/*

	 	ElevationAngle() - Determines the elevation angle from P.533-12 equation (13) Section 5.1 Elevation angle
	 		given the hop distance (dh) and the mirror reflection height (hr)
	  
	 		INPUT
	 			double dh - Hop distance
	 			double hr - Reflection height
	 
	 		OUTPUT
	 			returns the elevation angle
	 
	 		SUBROUTINES
				None

	 */

	double ele;

	ele = ((1.0/tan(dh/(2.0*R0))) - ((R0/(R0+hr))/sin((dh/(2.0*R0)))));
	ele = atan(ele);
	
	return ele;

}

double IncidenceAngle(double deltaf, double hr) {

	/*

	 	IncidenceAngle() Determine the angle of incidence from  P.533-12 equation (12).
	 		Section 4 E-layer maximum screening frequency (fs) given the 
	 		mirror reflection height (hr) and the elevation angle (deltaf)
	 
	 		INPUT
	 			double deltaf - Elevation angle
	 			double hr - Reflection height
	 
	 		OUTPUT
	 			returns the incidence angle
	 
			SUBROUTINES
				None

	 */

	return asin(R0*cos(deltaf)/(R0 + hr));
}
