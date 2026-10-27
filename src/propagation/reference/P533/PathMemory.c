#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End local includes

/*
 * Allocates the Antenna structure (Part of the PathData struct).  This 
 * function is called when the antenna types have been defined which in
 * turn define the dimensions of the required data structure.
 */
DLLEXPORT int AllocateAntennaMemory(struct Antenna *ant, int freqn, int azin, int elen) {
	double *freqList;   // List of frequencies for which we have pattern data
	double ***antpat;
	int m, n;
	
	ant->freqn = freqn;
	freqList = (double *) malloc(ant->freqn * sizeof(double));
	if(freqList != NULL) {
		ant->freqs = freqList;
	} else {
		return RTN_ERRALLOCATEANT;
	}

	antpat = (double ***) malloc(ant->freqn * sizeof(double *));
 	for (m=0; m < ant->freqn; m++) {
 		antpat[m] = (double **) malloc(azin * sizeof(double *));
 		for (n=0; n<azin; n++) {
 			antpat[m][n] = (double*) malloc(elen * sizeof(double));
 		}
 	}
	
	if(antpat != NULL) {
		ant->pattern = antpat;
	} else {
		return RTN_ERRALLOCATEANT;
	}
	
	return RTN_ALLOCATEP533OK;

}


DLLEXPORT int AllocatePathMemory(struct PathData *path) {
	
	/*

	  AllocatePathMemory() - Allocates the memory necessary for the path structure. The data must be read into these structures elsewhere.
	 
	 		INPUT
	 			struct PathData *path	
	 		
	 		OUTPUT
	 			path->foF2
	 			path->M3kF2
	 			path->foF2var
	 			path->dud
	 			path->fam 
	 
	 		SUBROUTINES
	 			None
	 
	 */

	float ****foF2;			// foF2 ionospheric map
	float ****M3kF2;		// M(3000)F2 ionospheric map
	double *****foF2var;	// foF2 statistics

	int retval;
	int hrs, lng, lat, ssn;
	int i, j, k, m;
	int season;
	int decile;

	/*
	 * Allocate the ionospheric parameter arrays that will be used by the P533 engine.
	 */
	hrs = 24;	// 24 hours
	lng = 241;	// 241 longitudes at 1.5 degree increments
	lat = 121;	// 121 latitudes at 1.5 degree increments
	ssn = 2;	// 2 SSN (12-month smoothed sun spot numbers) high and low

	/* 
	 * Create the foF2 array so you can pass it into the core P.533 process.
	 */
	foF2 = (float****) malloc(hrs * sizeof(float***));
	for (i=0; i<hrs; i++) {
		foF2[i] = (float***) malloc(lng * sizeof(float**));
		for (j=0; j<lng; j++) {
			foF2[i][j] = (float**) malloc(lat * sizeof(float*));
			for (k=0; k<lat; k++) {
				foF2[i][j][k] = (float*) malloc(ssn * sizeof(float));
			}
        }
    }

    /* 
     * Create the M(3000)F2 array so you can pass it into the core P.533 process.
     */
	M3kF2 = (float****) malloc(hrs * sizeof(float***));
	for (i=0; i<hrs; i++) {
		M3kF2[i] = (float***) malloc(lng * sizeof(float**));
		for (j=0; j<lng; j++) {
			M3kF2[i][j] = (float**) malloc(lat * sizeof(float*));
			for (k=0; k<lat; k++) {
				M3kF2[i][j][k] = (float*) malloc(ssn * sizeof(float));
			}
        }
    }

    /*
     * Allocate the foF2 variablity arrays that will be used by the P533 engine.
     */
	season = 3;	// 3 seasons
				//		1) WINTER 2) EQUINOX 3) SUMMER
	hrs = 24;	// 24 hours  
	lat = 19;	// 19 latitude by 5
				//      0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90
	ssn = 3;	// 3 SSN ranges
				//		1) R12 < 50 2) 50 <= R12 <= 100 3) R12 > 100
	decile = 2;	// 2 deciles 
				//	1) lower 2) upper

	/* 
	 * Create the foF2 array so you can pass it into the core P.533 process.
	 */
	foF2var = (double*****) malloc(season * sizeof(double****));
	for (i=0; i<season; i++) {
		foF2var[i] = (double****) malloc(hrs * sizeof(double***));
		for (j=0; j<hrs; j++) {
			foF2var[i][j] = (double***) malloc(lat * sizeof(double**));
			for (k=0; k<lat; k++) {
				foF2var[i][j][k] = (double**) malloc(ssn * sizeof(double*));
				for (m=0; m<ssn; m++) {
					foF2var[i][j][k][m] = (double*) malloc(decile * sizeof(double));
				}
			}
		}
	}

	/*
	 * The TX and RX antenna arrays are allocated when parsing the
	 * input files (e.g. ReadType13) as the array size varies with the antenna
	 * type and the number of frequencies for which pattern data is available.
	 *
	 * The arrays are free'd in FreePathMemory.
	 */
 	path->A_tx.pattern = NULL;
	path->A_rx.pattern = NULL;

	// Check for NULLs and save the pointers to the path structure.
	if(foF2 != NULL) path->foF2 = foF2;
	else return RTN_ERRALLOCATEFOF2;

	if(M3kF2 != NULL) path->M3kF2 = M3kF2;
	else return RTN_ERRALLOCATEM3KF2;

	if(foF2var != NULL) path->foF2var = foF2var;
	else return RTN_ERRALLOCATEFOF2VAR;

	// P372.dll **********************************************************
    
	// Load the Noise routines in P372.dll ******************************
#ifdef _WIN32
	
	int mod[512];

	// Get the handle to the P372 DLL.
	hLib = LoadLibrary("P372.dll");
	if (hLib == NULL) {
		printf("P533: AllocatePathMemory: Error %d P372.DLL Not Found\n", RTN_ERRP372DLL);
		return RTN_ERRP372DLL;
	}

    // Get the handle to the DLL library, hLib.
	GetModuleFileName((HMODULE)hLib, (LPTSTR)mod, 512);
	//
	dllAllocateNoiseMemory = (iNoiseMemory)GetProcAddress((HMODULE)hLib, "AllocateNoiseMemory");
#elif __linux__ || __APPLE__
	void * hLib;
	hLib = dlopen("libp372.so", RTLD_NOW);
	if (!hLib) {
		printf("Couldn't load libp372.so, exiting.\n");
		exit(1);
	};
	dllAllocateNoiseMemory = dlsym(hLib, "AllocateNoiseMemory");
#endif	

	// End P372.DLL Load ************************************************
	
	// Allocate the memory in the noise structure
	retval = dllAllocateNoiseMemory(&path->noiseP);
	if (retval != RTN_ALLOCATEP372OK) {
		return RTN_ERRALLOCATENOISE;
	}
	// P372.dll **********************************************************

	return RTN_ALLOCATEP533OK;

}


DLLEXPORT int FreePathMemory(struct PathData *path) {	
	/*

	 	FreePath() - Frees the memory that was dynamically (m) allocated for the structure PathData path
	 
	 		INPUT
	 			struct PathData *path
	 
	 		OUTPUT
	 			void
	 
	 		SUBROUTINES
	 			None
	 
	 */

	int retval;
	int hrs, lng, lat, ssn;
	int i, j, k, m, n;
	int season;
	int azimuth;
	
	/*
	 * Free the ionospheric parameter arrays.
	 */
	hrs = 24;	// 24 hours
	lng = 241;	// 241 longitudes at 1.5 degree increments
	lat = 121;	// 121 latitudes at 1.5 degree increments
	ssn = 2;	// 2 SSN (12-month smoothed sun spot numbers) high and low

	for (i=0; i<hrs; i++) {
		for (j=0; j<lng; j++) {
			for (k=0; k<lat; k++) {
				free(path->foF2[i][j][k]);
				}
            free(path->foF2[i][j]);
			}
        free(path->foF2[i]);
	}
    free(path->foF2);

	for (i=0; i<hrs; i++) {
		for (j=0; j<lng; j++) {
			for (k=0; k<lat; k++) {
				free(path->M3kF2[i][j][k]);
			}
            free(path->M3kF2[i][j]);
		}
        free(path->M3kF2[i]);
	}
    free(path->M3kF2);

	// Free the foF2 variability memory
	season = 3;	 
	lat = 19;	
	ssn = 3;	

	for (i=0; i<season; i++) {
		for (j=0; j<hrs; j++) {
			for (k=0; k<lat; k++) {
				for (m=0; m<ssn; m++) {
					free(path->foF2var[i][j][k][m]);
				}
				free(path->foF2var[i][j][k]);
			}
			free(path->foF2var[i][j]);
		}
		free(path->foF2var[i]);
	}
	free(path->foF2var);
	
	// Free antenna array
	azimuth = 360;
	free(path->A_tx.freqs);
	for (m=0; m < path->A_tx.freqn; m++) {
		for (n=0; n<azimuth; n++) {
			free(path->A_tx.pattern[m][n]);
		}
		free(path->A_tx.pattern[m]);
    }
	free(path->A_tx.pattern);

  free(path->A_rx.freqs);
	for (m=0; m < path->A_rx.freqn; m++) {
		for (n=0; n<azimuth; n++) {
			free(path->A_rx.pattern[m][n]);
		}
		free(path->A_rx.pattern[m]);
	}
	free(path->A_rx.pattern);

	// Free the noise memory
	retval = dllFreeNoiseMemory(&path->noiseP);
	if (retval != RTN_NOISEFREED) return retval; // check that the input parameters are correct
	
	return RTN_PATHFREED;

}

