#include <stdio.h>
#include <stdlib.h>
#include <math.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End local includes

int ValidatePath(struct PathData *path) {

	/*

		ValidatePath() - Checks the data in the path for correct input.

		INPUT
		struct PathData *path

		OUTPUT
		returns an integer representing the validity of the data

		SUBROUTINES
		None

		*/

	if ((path->year < 1900) || (path->year > 2100))						return RTN_ERRYEAR;
	if ((path->month < 0) || (path->month > 11))						return RTN_ERRMONTH;
	if ((path->hour < 0) || (path->hour > 23))							return RTN_ERRHOUR;

	// There are two conditions on the path->ManMadeNoise
	//	1) if path->ManMadeNoise is positive 
	//			Then the value is either a flag or a single value > 100 and < 200
	//  2) if path->ManMadeNoise is negative 
	//			Then the user desires to cancel the noise calculation and and value is valid
	if (path->noiseP.ManMadeNoise > 0.0) {
		if (((path->noiseP.ManMadeNoise != CITY) && (path->noiseP.ManMadeNoise != RESIDENTIAL) && (path->noiseP.ManMadeNoise != RURAL)
			&& (path->noiseP.ManMadeNoise != QUIETRURAL) && (path->noiseP.ManMadeNoise != QUIET) && (path->noiseP.ManMadeNoise != NOISY))
			&& ((path->noiseP.ManMadeNoise > 6.0) && (path->noiseP.ManMadeNoise < 100.0))
			&& (path->noiseP.ManMadeNoise > 200.0))							return RTN_ERRMANMADENOISE;
	}

	if (path->foF2 == NULL)												return RTN_ERRNOFOF2DATA;
	if (path->M3kF2 == NULL)											return RTN_ERRNOM3KF2DATA;
	if (path->noiseP.dud == NULL)										return RTN_ERRNODUDDATA;
	if (path->noiseP.fam == NULL)										return RTN_ERRNOFAMDATA;
	if (path->foF2var == NULL)											return RTN_ERRNOFOF2VARDATA;
	if ((1 > path->SSN) || (path->SSN > 311))							return RTN_ERRSSN;
	if ((path->Modulation != DIGITAL) && (path->Modulation != ANALOG))	return RTN_ERRMODULATION;
	if ((1.0 > path->frequency) || path->frequency > 30.0)				return RTN_ERRFREQUENCY;
	if ((0.005 > path->BW) || (path->BW > 3e6))							return RTN_ERRBW;
	if ((-30.0 > path->txpower) || (path->txpower > 60))				return RTN_ERRTXPOWER;
	if ((-30.0 > path->SNRr) || (path->SNRr > 200))						return RTN_ERRSNRR;
	if ((-30.0 > path->SIRr) || (path->SIRr > 200))						return RTN_ERRSIRR;
	if ((0.0 > path->F0) || (path->F0 > 1000))							return RTN_ERRF0;
	if ((0.0 > path->T0) || (path->T0 > 1000))							return RTN_ERRT0;
	if ((0.0 > path->A) || (path->A > 1000))							return RTN_ERRA;
	if ((0.0 > path->TW) || (path->TW > 50.0))							return RTN_ERRTW;
	if ((0.0 > path->FW) || (path->FW > 1000))							return RTN_ERRFW;
	if ((fabs(path->L_tx.lat) > PI / 2.0) || (fabs(path->L_tx.lng) > PI))	return RTN_ERRLTX;
	if ((fabs(path->L_rx.lat) > PI / 2.0) || (fabs(path->L_rx.lng) > PI))	return RTN_ERRLRX;
	if (path->A_rx.pattern == NULL)										return RTN_ERRRXANTENNAPATTERN;
	if (path->A_tx.pattern == NULL)										return RTN_ERRTXANTENNAPATTERN;
	if ((1 > path->SNRXXp) || (path->SNRXXp > 99))					 	return RTN_ERRSNRXXP;

	// path data valid
	return RTN_VALIDDATAOK;

}
