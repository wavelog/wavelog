#include <stdio.h>
#include <stdlib.h>
#include <math.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End local includes

// Local prototypes
void DominantMode(struct PathData *path);
// End local prototypes

void MedianAvailableReceiverPower(struct PathData *path) {

	/*	
	  MedianAvailableReceiverPower() - Calculates the median available receiver power by the method in 
	 		ITU-R P.533-12 Section 6 "Median available receiver power".
	 
	 		INPUT
	 			struct PathData *path
	 
	 		OUTPUT
	 			path->Md_F2[].Prw - F2 mode received power 
	 			path->Md_E[].Prw - E mode received power
	 			path->Pr - Median available received power
	 			path->Ep - The path field strength at the given path->distance.
	 
			SUBROUTINE
				AntennaGain()
				DominantMode()
				AntennaGain08()
	 
	 */
	
	double SumPr;	// Summation of individual mode powers
	double Grw;		// Mode gain paths < 9000 km and the overall receiver gain paths > 9000 km
	double Prw;		// Greatest receive mode power

	int i;			// Temp
	
	double elevation;	// Antenna elevation

	// Intialize 
	Prw = TINYDB;
	elevation = 2.0*PI;

	// In each case the receiver gain, Grw, must be determined. Grw is calculated at the receiver elevation angles for 
	// paths less than 9000 km. While for paths >= 9000 km the largest gain between 0 and 8 degrees is used.

	if(path->distance <= 7000.0) {
		
		// For each mode power to the total received power sum as given in Eqn (38) P.533-12
		// This can be done as each mode power is calculated
		SumPr = 0.0;

		// Calculate the available signal power Prw (dBW) for each mode from 
		// sky-wave field strength Ew (dB(1 µV/m)), frequency f (MHz) and Grw
		// lossless receiving antenna of gain.

		// Do any E-layer modes exist if so proceed
		// See "Modes considered" Section 5.2.1 P.533-12

		if(path->n0_E != NOLOWESTMODE) {
			for(i=path->n0_E; i<MAXEMDS; i++) {
				if(((i == path->n0_E) && (path->distance/(path->n0_E+1) <= 2000.0))
					                             ||
				   ((i != path->n0_E) && (path->Md_E[i].BMUF != 0.0))) {

					// Find the receiver gain for this mode.
					path->Md_E[i].Grw = AntennaGain(*path, path->A_rx, path->Md_E[i].ele, RXTOTX);

					path->Md_E[i].Prw = path->Md_E[i].Ew + path->Md_E[i].Grw 
										- 20.0*log10(path->frequency) - 107.2;

					// Determine if this is the greatest received power
					// If this is first time in the loop i == path->n0_E then initialize Prw
					if(Prw < path->Md_E[i].Prw){
						// Prw is the greatest power
						Prw = path->Md_E[i].Prw;

						// Point to the dominant mode and set the dominant mode index.
						path->DMptr = &path->Md_E[i];
						path->DMidx = i;

					}

                    // Add this mode to the sum.
					SumPr += pow(10.0, path->Md_E[i].Prw/10.0); 
				}
            }
        }
        // F2 modes
		// Do any F2-layer modes exist if so proceed
		if(path->n0_F2 != NOLOWESTMODE) {
			for(i=path->n0_F2; i<MAXF2MDS; i++) {
				if(((i == path->n0_F2) && (path->distance/(path->n0_F2+1) <= path->dmax) && (path->Md_F2[i].fs < path->frequency)) 
													   ||
				   ((i != path->n0_F2) && (path->Md_F2[i].BMUF != 0.0) && (path->Md_F2[i].fs < path->frequency))) {
					// Find the receiver gain for this mode.
					path->Md_F2[i].Grw = AntennaGain(*path, path->A_rx, path->Md_F2[i].ele, RXTOTX);

					path->Md_F2[i].Prw = path->Md_F2[i].Ew + path->Md_F2[i].Grw 
										- 20.0*log10(path->frequency) - 107.2;

					// Determine if this is the greatest received power.
					// If there was an E mode then Prw is already set to that power
					if(Prw < path->Md_F2[i].Prw) {
						// Prw is the greatest power.
						Prw = path->Md_F2[i].Prw;
					
						// Point to the dominant mode and set the dominant mode index.
						path->DMptr = &path->Md_F2[i];
						path->DMidx = i + 3;

					}

                    // Add this mode to the sum.
					SumPr += pow(10.0, path->Md_F2[i].Prw/10.0); 
				}
            }
        }

        // Now that the modes are calculated, set the path parameters. 
		// Find the total received power. 
		// If the SumPr is 0 then set the path->Pr to something small
		if((SumPr != 0.0) && (path->DMptr != NULL)) {
			path->Pr = 10.0*log10(SumPr);
			// The dominant mode is known so set any values in the path structure that are relevant.
			DominantMode(path);
		}
		else {
			// There are no E modes and all the F2 modes are screened
			path->Pr = TINYDB;
		}

        // Save the path field strength. For this distance select Es, which includes E layer screening.
		path->Ep = path->Es;

	}
	else if((7000.0 < path->distance) && (path->distance < 9000.0)) {
		// Determine the receiver gain.
		Grw = AntennaGain08(*path, path->A_rx, RXTOTX, &elevation);

		// Use the interpolated power, Ei.
		path->Pr = path->Ei + Grw - 20.0*log10(path->frequency) - 107.2;

		// The path receiver gain Grw.
		path->Grw = Grw;

		// Save the path field strength, which at this distance is Ei.
		path->Ep = path->Ei;

		// Set the rx antenna elevation to the  long path rx elevation
		path->ele = elevation;

	}
	else { // path->distance >= 9000.0)
		// Determine the receiver gain.
		Grw = AntennaGain08(*path, path->A_rx, RXTOTX, &elevation);

		// Use the combined mode power, El, and the antenna gain between 0 and 8 degrees, Grw.
		path->Pr = path->El + Grw - 20.0*log10(path->frequency) - 107.2;

		// The path receiver gain Grw.
		path->Grw = Grw;

		// Save the path field strength, which at this distance is El.
		path->Ep = path->El;

		// Set the rx antenna elevation to the  long path rx elevation
		path->ele = elevation;
	}

    return;
}

void DominantMode(struct PathData *path) {

	/* 

	  DominantMode() - Stores values that are associated with the dominant 
	 		to the path structure. These are strictly not part of the standard 
	 		P.533-12 but are provided for continuity in the analysis. 
	 
	 		INPUT
	 			struct PathData *path
	 
	 		OUTPUT
	 			path->Grw
	 			path->ele
	 
			SUBROUTINES
				None

	 */

		// Select the dominant mode to represent the median path behavior.
		// The path receiver gain is the dominant mode receiver gain.
		path->Grw = path->DMptr->Grw;

		// The path elevation angle is the dominant mode elevation angle.
		path->ele = path->DMptr->ele;

}
