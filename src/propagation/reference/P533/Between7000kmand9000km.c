#include <stdio.h>
#include <stdlib.h>
#include <math.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End Local includes

void Between7000kmand9000km(struct PathData *path) {

	/*
	 
	  Between7000kmand9000km() Interpolates between the path-distances of 7000 and 9000 km. 
	 		The interpolation method is given in ITU-R P.533-12 Section 5.4 "Paths between 7000 and 9000 km"
	 
	 	INPUT 
	 		struct PathData *path
	 
	 	OUTPUT
	 		path-Ei - The interpolated path field strength (dB(1uV/m))
	 		path->BMUF - The path basic MUF (MHz)

		SUBROUTINES
			CalcB()
			Calcdmax()
			CalcF2DMUF()
	 		
	 */

	double Xs;		// Linear field strength of path->Es
	double Xl;		// Linear field strength of path->El
	double Xi;		// Linear interpolated field strength
	double B;		// Intermediate value fo the MUF calculation
	double dmax;	// Maximum hop distance as calculated by Eqn (5) P.533-12
	double BMUF[2]; // Array of basic MUFs at two control points

	if((7000.0 < path->distance) && (path->distance < 9000.0)) {

		Xl = pow(10.0, path->El/100.0);
		Xs = pow(10.0, path->Es/100.0);

		Xi = Xs + ((path->distance - 7000.0)/2000.0)*(Xl - Xs);

		path->Ei = 100.0*log10(Xi); // Eqn (42) P.533-12

		// Calculate the basic MUF according to P.533-12 Section 5.4 "Paths between 7000 and 9000 km"
		B = CalcB(&path->CP[Td02]);
		dmax = min(Calcdmax(&path->CP[Td02]), 4000.0);
		BMUF[0] = CalcF2DMUF(&path->CP[Td02], path->distance/(path->n0_F2+1), dmax, B);
		
		B = CalcB(&path->CP[Rd02]);
		dmax = min(Calcdmax(&path->CP[Rd02]), 4000.0);
		BMUF[1] = CalcF2DMUF(&path->CP[Rd02], path->distance/(path->n0_F2+1), dmax, B);

		path->BMUF = min(BMUF[0], BMUF[1]);

	} // ((7000.0 < path->distance) && (path->distance < 9000.0))

	return;

}
