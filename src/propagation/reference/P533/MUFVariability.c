#include <stdio.h>
#include <stdlib.h>
#include <math.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End local includes

void MUFVariability(struct PathData *path) {
	/*

	 	MUFVariability() - Calculates the F2 and E Fprob, 50% MUF (MUF50), 90% MUF (MUF90) and 10% MUF (MUF10) 
	 		in accordance with P.533-12
	 		Section 3.7 "The path operational MUF" and
	 		Section 3.6 "Within the month probability of ionospheric propagation support" 
	 
	 		INPUT
	 			struct PathData *path
	 
	 		OUTPUT
	 			path->MUF50 = Path basic MUF 50% of a month 
	 			path->MUF90 = Path basic MUF 90% of a month 
	 			path->Md_E[].MUF50 = E layer 50% of the month MUF
	 			path->Md_F2[].MUF50 = F2 Layer 50% of the month MUF
	 			path->Md_E[].MUF90 = E layer 90% of the month MUF
	 			path->Md_F2[].MUF90 = F2 Layer 90% of the month MUF
	 			path->Md_F2[].deltal = F2 layer lower decile deviation of the MUF
	 			path->Md_F2[].deltau = F2 layer upper decile deviation of the MUF
	 			path->Md_E[].deltal = E layer lower decile deviation of the MUF
	 			path->Md_E[].deltau = E layer lower decile deviation of the MUF
	 			path->Md_F2[].Fprob = F2 layer within the month probability of ionospheric propagation support 
	 			path->Md_E[].Fprob = E layer within the month probability of ionospheric propagation support
	 	

			SUBROUTINES
				FindfoF2var()

	 */

	int i;		// Index
	int decile; // Decile flag

	double EMUF10, EMUF90;		// Temp 10% and 90% E layer MUF
	double F2MUF10, F2MUF90;	// Temp 10% and 90% F2 layer MUF

	
	// Only do this subroutine if the path is less than or equal to 9000 km if not exit
	if(path->distance > 9000) return; 
	
	// Section 3.65 seems to imply that the basic MUF for the path and the 50% MUF are the same so for this calculation set them equal
	path->MUF50 = path->BMUF;

	// There potentially are 3 E layer modes and 6 F2 layer modes

	// The F2 layer modes
	for(i=0; i<MAXF2MDS; i++) { // There are 6 F2 layer modes 
		if(path->Md_F2[i].BMUF != 0.0) { // If the Basic MUF is set, non-zero, the layer exists

			// Section 3.6 P.533-12 indicates that the basic MUF and MUF(50) are the same.
			path->Md_F2[i].MUF50 = path->Md_F2[i].BMUF;

			// Determine the decile factors
			decile = DL; // Lower decile
			// Find the deltal in the foF2var array
			path->Md_F2[i].deltal = FindfoF2var(*path, path->CP[MP].ltime, path->CP[MP].L.lat, decile);
				
			decile = DU; // Upper decile
			// Find the deltau in the foF2var array
			path->Md_F2[i].deltau = FindfoF2var(*path, path->CP[MP].ltime, path->CP[MP].L.lat, decile);

			// Find the other MUFs
			path->Md_F2[i].MUF10 = path->Md_F2[i].deltau*path->Md_F2[i].MUF50;
			path->Md_F2[i].MUF90 = path->Md_F2[i].deltal*path->Md_F2[i].MUF50;

			// Now determine the probability that the mode can be supported
			if(path->frequency < path->Md_F2[i].MUF50) {
				path->Md_F2[i].Fprob = min(1.3 - (0.8/(1.0+((1.0 - (path->frequency/path->Md_F2[i].MUF50))/(1.0 - path->Md_F2[i].deltal)))), 1.0);
				//printf("Eq.9  Freq=%.2f MUF50=%.2f deltal=%.2f Fprob=%.2f\n", path->frequency, path->Md_F2[i].MUF50, path->Md_F2[i].deltal, path->Md_F2[i].Fprob);
			}
			else { // (path->frequency >= path->Md_F2[i].MUF50)
				path->Md_F2[i].Fprob = max((0.8/(1.0 + (((path->frequency/path->Md_F2[i].MUF50) - 1.0)/(path->Md_F2[i].deltau - 1.0)))) - 0.3, 0.0);
				//printf("Eq.10 Freq=%.2f MUF50=%.2f deltau=%.2f Fprob=%.2f\n", path->frequency, path->Md_F2[i].MUF50, path->Md_F2[i].deltau, path->Md_F2[i].Fprob);
			}
		}
	}

	// The following loop is the same as that for the F2 layer but the decile values are fixed according to Section 3.6
	// The lower decile delta is 0.95 and the upper is 1.05/
	// Find the E layer MUFs
	for(i=0; i<MAXEMDS; i++) { // There are 6 F2 layer modes 
		if(path->Md_E[i].BMUF != 0.0) { // If the Basic MUF is set, non-zero, the layer exists

			// Section 3.6 P.533-12 indicates that the basic MUF and MUF(50) are the same.
			path->Md_E[i].MUF50 = path->Md_E[i].BMUF;
			
			// deltal for E layer
			path->Md_E[i].deltal = 0.95;

			// deltau for the E layer 
			path->Md_E[i].deltau = 1.05;

			// Find the other MUFs
			path->Md_E[i].MUF10 = path->Md_E[i].deltau*path->Md_E[i].MUF50;
			path->Md_E[i].MUF90 = path->Md_E[i].deltal*path->Md_E[i].MUF50;

			// Now determine the probability that the mode can be supported
			if(path->frequency < path->Md_E[i].MUF50) {
				path->Md_E[i].Fprob = min(1.3 - (0.8/(1.0 + ((1.0 - (path->frequency/path->Md_E[i].MUF50))/(1.0 - path->Md_E[i].deltal)))), 1.0);
			}
			else { // (path->frequency >= path->Md_F2[i].MUF50)
				path->Md_E[i].Fprob = max((0.8/(1.0 + (((path->frequency/path->Md_E[i].MUF50) - 1.0)/(path->Md_E[i].deltau - 1.0)))) - 0.3, 0.0);
			}
		}
	}

	// Now assume that the same procedure that applies to the basic MUF does for the variability MUFs
	// As was done in the MUFBasic() determine the largest of the MUF90 and MUF10
	// Pick the MUF extrema for the path for the E and F2 layer
	EMUF10 = 0.0;
	EMUF90 = 0.0;
	for(i=0; i<MAXEMDS; i++) {
		if(path->Md_E[i].BMUF != 0.0) { // If the Basic MUF is set the layer exists
			if(path->Md_E[i].MUF90 > EMUF90) EMUF90 = path->Md_E[i].MUF90;
			if(path->Md_E[i].MUF10 > EMUF10) EMUF10 = path->Md_E[i].MUF10;
		}
    }
    F2MUF10 = 0.0;
	F2MUF90 = 0.0;
	for(i=0; i<MAXF2MDS; i++) {
		if(path->Md_F2[i].BMUF != 0.0) { // If the Basic MUF is set the layer exists
			if(path->Md_F2[i].MUF90 > F2MUF90) F2MUF90 = path->Md_F2[i].MUF90;
			if(path->Md_F2[i].MUF10 > F2MUF10) F2MUF10 = path->Md_F2[i].MUF10;
		}
    }

    // Determine the 90% and 10% MUF amongst all existant modes
	path->MUF90 = max(EMUF90, F2MUF90); // largest 90% MUF
	path->MUF10 = max(EMUF10, F2MUF10); // largest 10% MUF

	return;

}

double FindfoF2var(struct PathData path, double hour, double lat, int decile) { 

	/*

	  FindfoF2var() - Determines the variation in foF2 given the season, hour, latitude ss and decile
	 		This routine uses the bilinear interpolation method in ITU-R P.1144-5
	 
	 		INPUT
	 			struct PathData path 
	 			double hour - Hour of interest
	 			double lat - Latitude of interest
	 			int decile - Upper or lower decile index
	 
	 		OUTPUT
	 			return interpolated value

			SUBROUTINES
				BilinearInterpolation()
	 
	 */

	double UR, UL, LL, LR; // Neighboring values

	int latU, latL; // lower and upper lat
	int hourU, hourL; // lower and upper hour
	int ssn;		// sunspot number index

	double c; // Fractional column
	double r; // Fractional row

	double Irc; // Interpolated value

	lat = fabs(lat/(5.0*D2R)); // 5 degree increments

	// Determine the fractional column and row
	// The distance between indices is 1.0
	r = lat - (int)lat; // The fractional part of the row
	c = hour - (int)hour; // The fractional part of the column

	latL = (int)floor(lat);
	latU = (int)ceil(lat);
	
	if(latL < 0) {
		latL = 18; // rollunder
		// The sense of the fractional row is reversed when negative so fix it
		r = 1.0 - r;
	}
    if(latU > 18) {
		latU = 0; // rollover 
	}

    hourL = (int)floor(hour);
	hourU = (int)ceil(hour);

	if(hourL < 0) {
		hourL = 23; // rollunder
		// The sense of the fractional column is reversed when negative so fix it
		c = 1.0 - c;
	}

    if(hourU > 23) {
		hourU = 0; // rollover hour
	}

    // Determine the sunspot number index ssn. 
	if(path.SSN < 50) {
		ssn = 0;
	}
	else if((50 <= path.SSN) && (path.SSN <=100)) { 
		ssn = 1;	
	}
	else { // path-SSN > 100
		ssn = 2;
	}

    // Find the neighbors
	LL = path.foF2var[path.season][hourL][latL][ssn][decile];
	LR = path.foF2var[path.season][hourU][latL][ssn][decile];
	UL = path.foF2var[path.season][hourL][latU][ssn][decile];
	UR = path.foF2var[path.season][hourU][latU][ssn][decile];

	Irc = BilinearInterpolation(LL, LR, UL, UR, r, c);

	return Irc;

}

