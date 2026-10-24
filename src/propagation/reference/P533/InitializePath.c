#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End local includes

// Local prototypes
int WhatSeason(struct Location L, int month);
void InitializeCPs(struct PathData *path);
void InitializeModes(struct Mode *M, int n);
// End local prototypes

/*
 * This set of routines initializes the PathData structure
 */
void InitializePath(struct PathData *path) {

	/*

		InitializePath() - Sets the path structure output values to default values

			INPUT
				struct PathData *path

			OUTPUT
				struct PathData *path

			SUBROUTINES
				IntializeModes()
				GreatCircleDistance()
				InitializCPs()
				WhatSeason()


	*/
	
	// It is assumed that ValidatePath() has run by this point to assure that the
	// input data is correct.

	/********************************************************************************************/
	/* First, zero out all variables that can potentially have values in any part of this program. */
	/********************************************************************************************/

	// Initialize the path to be set elsewhere.
	
	// Clear the variables that will be determined elsewhere.	
	path->B			= 99.9;
	path->BCR		= 0.0;
	path->BMUF		= 99.9;
	path->DMidx		= NODOMINANTMODE;
	path->DMptr		= NULL;
	path->DlSI		= TINYDB;
	path->DlSN		= TINYDB;
	path->DuSI		= TINYDB;
	path->DuSN		= TINYDB;
	path->EIRP		= TINYDB;
	path->Ei		= TINYDB;
	path->El		= TINYDB;
	path->E0		= TINYDB;
	path->Ep		= TINYDB;
	path->Es		= TINYDB;
	path->F			= TINYDB;
	path->fH		= 0.0;
	path->Gap		= TINYDB;
	path->Grw		= TINYDB;
	path->Gtl		= TINYDB;
	path->K[0]		= 0.0;
	path->K[1]		= 0.0;
	path->Ly		= 0.0;
	path->MIR		= TINYDB;
	path->MUF10		= 99.9;
	path->MUF50		= 99.9;
	path->MUF90		= 99.9;
	path->OCR		= 0.0;
	path->OCRs		= 0.0;
	path->OPMUF		= 99.9;
	path->OPMUF10	= 99.9;
	path->OPMUF90	= 99.9;
	path->Pr		= TINYDB;
	path->RF		= 0.0;
	path->RSN		= 0.0;
	path->RT		= 0.0;
	path->SIR		= TINYDB;
	path->SNR		= TINYDB;
	path->SNRXX		= TINYDB;
	path->distance	= 999999.9;
	path->dmax		= 999999.9;
	path->ele		= 2.0*PI;
	path->fL		= 0.0;
	path->fM		= 0.0;
	path->n0_E		= NOLOWESTMODE;
	path->n0_F2		= NOLOWESTMODE;
	path->probocc	= 0.0;
	path->ptick		= 0.0;
	path->season	= 99;

	// Initialize all the data in the structures. 
	
	// Initializing modes

	InitializeModes(&path->Md_F2[0], MAXF2MDS);
	InitializeModes(&path->Md_E[0], MAXEMDS);

	// End initializing modes

	// To initialize the control point, the distance between the tx and rx needs to be determined.
	// Find the great circle distance between the tx and rx.
	path->distance = GreatCircleDistance(path->L_tx, path->L_rx);

	// There is a degenerate case where path distance is zero. 
	// If the distance is zero set it to epsilon as an approximation
	if (path->distance == 0.0) {
		path->distance = DBL_EPSILON;
	}

    // Determine if this is a long path. If so, adjust the distance.
	if(path->SorL == LONGPATH) {
		path->distance = R0*PI*2 - path->distance;
	}

    // Initialize the control points
	InitializeCPs(path);
	// End initializing control points

	// Initialize Noise from the P372.dll
	dllInitializeNoise(&path->noiseP);
	// End Initialize Noise

	// Initialize the path variables.
	// For several calculations you need to know the season.
	path->season = WhatSeason(path->CP[MP].L, path->month);
	
	return;
}

void InitializeCPs(struct PathData *path) {
	/*

	  InitializeCPs(). This routine zeros the five potential control points. The locations of the T - d0/2 and R - d0/2 
	 	control points are determined elsewhere because they are dependent on n0 ( the lowest-order F2 mode) The locations, 
		distances of and ionoshpheric properties for the midpoint (MP) and the 1000 km away control points (T + 1000 and 
		R - 1000) are initialized here. The other control points at T + d0/2 and R - d0/2 require that the lowest-order propagating 
		mode be determined. This calculation for the lowest-order propagating mode is performed in MUFBasic().
		Although all of these control points may not be used for any given calculation, all the control points are initialized here. 
		The indices for each are shown below. The name is to aid readability. The index names are given as #defines and can be found
		in the header file P533.h.
	 
	 		Control Point
	 		Location		Index		Name
	 		T + 1000		  0			 T1k
	 		T + d0/2		  1			 Td02
	 		M				  2			 M
	 		R - d0/2		  3			 Rd02
	 		R - 1000		  4			 R1k
	 		where T is the location (lat and long) of the transmitter
	 		      R is the location (lat and long) of the receiver
	 			  M is the location (lat and long) of the midpoint
	 			  d0 is the hope distance (km) of the lowest-order mode

		INPUT
			struct PathData *path

		OTUPUT
			initialized control points path->CP[n]

		SUBROUTINES
			GreatCirclePoint()
			CalculateCPParameters()
	 	
	 */

	int i;

	double fracd; // fractional distance

	// Initialize five control points
	for(i = 0; i < 5; i++) {
		path->CP[i].L.lat = 0.0;
		path->CP[i].L.lng = 0.0;
		path->CP[i].distance = 0.0;
		path->CP[i].foE = 0.0;
		path->CP[i].foF2 = 0.0;
		path->CP[i].M3kF2 = 0.0;
		path->CP[i].fH[HR300km] = 0.0;
		path->CP[i].dip[HR300km] = 0.0;
		path->CP[i].fH[HR100km] = 0.0;
		path->CP[i].dip[HR100km] = 0.0;
		path->CP[i].Sun.lsn = 0.0;
		path->CP[i].Sun.lsr = 0.0;
		path->CP[i].Sun.lss = 0.0;
		path->CP[i].Sun.sza = 0.0;
		path->CP[i].Sun.decl = 0.0;
		path->CP[i].Sun.eot = 0.0;
		path->CP[i].Sun.sha = 0.0;
		path->CP[i].Sun.ha = 0.0;
		path->CP[i].ltime = 0.0;
		path->CP[i].hr = 0.0;
		path->CP[i].x = 0.0;
	}
	
	/**************************************************************/
	/* Now calculate everything that can be before p533() begins. */
	/**************************************************************/
		
	// Now find MP, T1k and R1k control points.
	// First, determine the fractional distances then find the point on the great circle between tx and rx.
	// All distances for the control points are relative to the tx.

	// Mid-point control point M
	// MP control point which is always used in the calculation of p533()
	fracd = 0.5; 
	GreatCirclePoint(path->L_tx, path->L_rx, &path->CP[MP], path->distance, fracd);	

	// Find foF2, M(3000)F2 and foE the MP control point
	CalculateCPParameters(path, &path->CP[MP]);

	// The next two control points depend on the total path length. If the path is not at least 2000 km then there is no
	// point in determining control points 1000 km from each end. 
	if(path->distance >= 2000.0) { 
		// R1k Control point - Fractional distance R - 1000
		fracd = (path->distance - 1000)/path->distance; 
		GreatCirclePoint(path->L_tx, path->L_rx, &path->CP[R1k], path->distance, fracd);

		// T1k Control point - Fractional distance T + 1000
		fracd = 1000.0/path->distance; 
		GreatCirclePoint(path->L_tx, path->L_rx, &path->CP[T1k], path->distance, fracd);
	
		// Find foF2, M(3000)F2 and foE these control points
		CalculateCPParameters(path, &path->CP[T1k]);
		CalculateCPParameters(path, &path->CP[R1k]);

	}

    // Note the local sunrise, sunset and noon at the CPs are found when IonParameters() is run.

	// The other control points at T + d0/2 and R - d0/2 require that the lowest-order propagating mode
	// be determined. This calculation is performed in MUFBasic().

	return;
}


void InitializeModes(struct Mode *M, int n) {

	/*

		InitializeModes() - Initialized the n number of modes: MAXF2MDS for F2 and MAXEMDS for E

		INPUT
			struct Mode *M
			int n

		OUTPUT
			n initialized modes pointed to by the base pointer *M 

		SUBROUTINES
			None
			
	*/

	int i;

	for(i = 0; i < n; i++) {

			(M+i)->BMUF = 0.0; 
			(M+i)->MUF90 = 0.0;  
			(M+i)->MUF50 = 0.0; 
			(M+i)->MUF10 = 0.0; 
			(M+i)->OPMUF = 0.0;
			(M+i)->OPMUF10 = 0.0;
			(M+i)->OPMUF90 = 0.0;
			(M+i)->Fprob = 0.0;
			(M+i)->deltal = 0.0;
			(M+i)->deltau = 0.0;
			(M+i)->fs = 0.0;
			(M+i)->Lb = -TINYDB;
			(M+i)->Ew = TINYDB;
			(M+i)->Prw = TINYDB;
			(M+i)->Grw = TINYDB;
			(M+i)->hr = 0.0;
			(M+i)->tau = 0.0;  
			(M+i)->ele = 0.0;
			(M+i)->MC = FALSE;

	}
}

int WhatSeason(struct Location L, int month) {

	/*
	
		WhatSeason() determines the month and latitude dependent index which 
		    represents the season. The index is used to locate appropriate variables in the foF2var array.

		INPUT
			struct Location L
			int month

		OUTPUT
			int season

		SUBROUTINES
			None

	*/

	//Initialise to prevent warning C4701: potentially uninitialized local variable 'season' used
	int season = WINTER;
	
	if(L.lat >= 0) { // Northern hemisphere and the equator
		switch (month) {
			case NOV: case DEC: case JAN: case FEB: 
				season = WINTER;
				break;
			case MAR: case APR: case SEP: case OCT: 
				season = EQUINOX;
				break;
			case MAY: case JUN: case JUL: case AUG: 
				season = SUMMER;
				break;
		}
    }
	else { // Southern Hemisphere 
		switch (month) {
			case MAY: case JUN: case JUL: case AUG: 
				season = WINTER;
				break;
			case MAR:case APR: case SEP: case OCT: 
				season = EQUINOX;
				break;
			case NOV: case DEC: case JAN: case FEB: 
				season = SUMMER;
				break;
		}
    }
	
	return season;
	
}

