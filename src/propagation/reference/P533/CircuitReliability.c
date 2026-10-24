#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End Local Includes

// Local prototypes
void ModeSort(struct Mode *M[MAXMDS], int order[MAXMDS], int criteria);
int NumberofModes(struct PathData path);
double DigitalModulationSignalandInterferers(struct PathData *path, int iS[MAXMDS], int iI[MAXMDS]);
void EquatorialScattering(struct PathData *path, int iS[MAXMDS]);
double FindFlambdad(struct ControlPt CP);
double FindFTl(struct ControlPt CP);
// End local prototypes

// Local defines
// Flags for digital reliability calculation
#define DOMINANT		0
#define SOONEST			1
#define NOTINDEX		99
// End local defines

void CircuitReliability(struct PathData *path) {

	/*

	  CircuitReliability() finds the basic circuit reliability, BCR, and the overall circuit reliability, OCR,
	 		for analog and digital systems.
	 		
	 		The BCR is calculated as described in Table 1 ITU-R P.842-4. 
	 		The calculation is broken down into 11 steps. Many of the parameters necessary for the computation have already been 
	 		calculated elsewhere in this project. The median available receiver power of the wanted signal, which is 
	 		Step 1 in Table 1 P.842-4, is calculated in the subroutine MedianAvailableReceiverPower(). The subroutine 
	 		MedianAvailableReceiverPower() implements the calculation in P.533-12 Section 6 Median available receiver.
	 		Table 1 P.842-4 Steps 2, 5 and 8 are calculated in subroutine Noise() and summarized below.
	 
	 				 P.842-4 Table 1 Step
	 							2			Median noise factors for atmospheric, galactic and man-made noise
	 							5			Lower decile deviation of atmospheric, galactic and Man-made noise
	 							8			Upper decile deviation of atmospheric, galactic and Man-made noise
	 
	 		This subroutine, CircuitReliability(), completes the remaining steps in P.842-4 Table 1.
	 
	 		The simplified approximate BCR method from section 9 "BCR for digital modulation systems" P.842-4 is 
	 		determined from the three probabilities:
	 				i) Probability that the required signal-to-noise ratio, SN0, is achieved
	 				ii) Probability that the required time spread, T0, at a level of -10 dB relative to the 
	 					peak signal amplitude is not exceeded
	 				iii) Probability that the required frequency dispersion f0 at a level of -10 dB relative to the 
	 					peak signal amplitude is not exceeded
	 
	 		This subroutine also determines the overall circuit reliability, OCR, via the method given in ITU-R P.P.842-4 Table 3
	 		for digital systems. 
	  
	 		This subroutine also determines the equatorial scattering occurrence probability and then calculates the OCR in the 
	 		presence of scattering
	 
	 			INPUT
	 				struct PathData *path
	 
	 			OUTPUT
	 				path->SNR - Signal-to-noise ratio
	 				path->DuSN - Upper decile deviation signal-to-noise ratio
	 				path->DlSN - Lower decile deviation signal-to-noise ratio
					path->SNRXX - Signal-to-noise ratio at the desired reliability 
	 				path->SIR - Signal-to-interference ratio
	 				path->DuSI - Upper decile deviation signal-to-interference ratio
	 				path->DlSI - Lower decile deviation signal-to-interference ratio
	 				path->BCR - Basic circuit reliability
	 				path->OCR - Overall circuit reliability without scattering
	 				path->OCRs - Overall circuit reliability with scattering
	 				path->MIR - Multimode interference ratio
	 				path->RSN - Probability that the required signal-to-noise ratio, SN0, is achieved 
	 				path->RT - Probability that the required time spread, T0, is not exceeded
	 				path->RF - Probability that the required frequency dispersion is not exceeded.
	 
	 				via DigitalModulationSignalandInterferers()
	 				path->Md_F2[].tau - F2 layer mode delay
	 				path->Md_E[].tau - E layer mode delay
	 
	 			SUBROUTINES
					DigitalModulationSignalandInterferers()
					GeomagneticCoords()
					EquatorialScattering()

	 */

	double x, y;	// Temps
	double S;
	double fBMUFR;	// Frequency to basic MUF ratio
	double DuSd;	// Signal upper decile deviation (day-to-day) (dB)
	double DlSd;	// Signal lower decile deviation (day-to-day) (dB)
	double DuSh;	// Signal upper decile deviation (hour-to-hour) (dB)
	double DlSh;	// Signal lower decile deviation (hour-to-hour) (dB)

	double DTu;		// Upper decile deviation of time spread (dB)
	double DTl;		// Lower decile deviation of time spread (dB)
	double DFu;		// Upper decile deviation of frequency spread (dB)
	double DFl;		// Lower decile deviation of frequency spread (dB)
	double DlIh;	// Lower decile deviation of interference (dB)
	double DuIh;	// Upper decile deviation of interference (dB)
	double Tm;		// Time spread
	double Fm;		// Frequency spread
	double D;		// path distance
	double Isum;	// Interference sum
	double Isuml;	// Interference sum lower decile
	double Isumu;	// Interference sum upper decile

	// NORM is an array of independent variables that give 
	// the normal cdf from 0.5 to 0.99 in 0.01 increments
	// with a standard deviataion of one and mean zero
	double NORM[50] = { 0.0000000000, 0.0250689082, 0.0501535835, 0.075269862, 0.1004337206,
						0.1256613469, 0.1509692155, 0.1763741647, 0.201893479, 0.2275449764,
						0.2533471029, 0.2793190341, 0.3054807878, 0.331853346, 0.3584587930,
						0.3853204663, 0.4124631294, 0.4399131658, 0.467698799, 0.4958503478,
						0.5244005133, 0.5533847202, 0.5828415079, 0.612812991, 0.6433454057,
						0.6744897502, 0.7063025626, 0.7388468486, 0.772193213, 0.8064212461,
						0.8416212327, 0.8778962945, 0.9153650877, 0.954165253, 0.9944578841,
						1.036433391,  1.080319342,  1.12639113,   1.174986792, 1.226528119,
						1.281551564,  1.340755033,  1.405071561,  1.47579103,  1.554773595,
						1.644853625,  1.750686073,  1.880793606,  2.053748909, 2.326347874 };

	int n;
	int iI[MAXMDS];		// Interfence mode indicies
	int iS[MAXMDS];		// Signal mode indicies

	// Table 2 P.842-4
	double Table2LD[2][10] = {{  8.0, 12.0, 13.0, 10.0,  8.0,  8.0,  8.0, 7.0, 6.0, 5.0},
				              { 11.0, 16.0, 17.0, 13.0, 11.0, 11.0, 11.0, 9.0, 8.0, 7.0}};
	double Table2UD[2][10] = {{ 6.0,  8.0, 12.0, 13.0, 12.0, 9.0, 9.0, 8.0, 7.0, 7.0},
							  { 9.0, 11.0, 12.0, 13.0, 12.0, 9.0, 9.0, 8.0, 7.0, 7.0}};

	int gt60deg; // Index for greater than 60 degrees geomagnetic latitude
	int BMUF; // Basic MUF index for P.842-4 Table 2

	struct Location Geomag; // 

	// For readability
	struct NoiseParams noiseP = path->noiseP;

	// Begin BCR Calculation *********************************************************************

	// Step 1: "Median available receiver power of wanted signal (dBW)"
	//		See MedianAvailableReceiverPower()

	// Step 2: Median noise factor for atmospheric noise, galactic and man-made noise
	//		See Noise() [P372.dll]

	// Step 3: "Median resultant signal-to-noise ratio (dB) for bandwidth b (Hz)"
	// There is a difference between digial and analog modulation in how the signal is found
	// Once the signal is found for each modulation the BCR calculation is identical
	if(path->Modulation == ANALOG) {
		S = path->Pr; 
	}
	else { // path->Modulation == DIGITAL
		// The signal for digital modulation requires the calculation given in 
		// ITU-R P.533-12 Section 10.2.3 Reliability prediction procedure.
		S = DigitalModulationSignalandInterferers(path, iS, iI);
	}

    // Calculate the SNR
	path->SNR = S - 10.0*log10(pow(10.0, (noiseP.FaA/10.0)) + pow(10.0, (noiseP.FaM/10.0)) + pow(10.0, (noiseP.FaG/10.0)))
		          - 10.0*log10(path->BW) + 204;

	// Step 4 & 7: "Signal upper decile deviation (day-to-day) (dB)" & "Signal lower decile deviation (day-to-day) (dB)"		

	// Determine if the path crosses 60 degrees 
	// From note 1 Table 2 P.842.4
	// If any point which "lies between control points located 1000 km from each end of the path" crosses 
	// geomagnetic latitude 60 degrees then identify it. 
	gt60deg = 0; // 0 means less than 60 degrees 
	if(path->distance > 2000.0) {
		// Note: There may be a degenerate case that looking at only three locations may not determine correctly
		// Check the mid-path 
		GeomagneticCoords(path->CP[MP].L, &Geomag);
		if(Geomag.lat >= 60.0*D2R) gt60deg = 1; // 1 means a location is greater than 60 degrees 
		// Check the control point 1000 km from the transmitter
		GeomagneticCoords(path->CP[T1k].L, &Geomag);
		if(Geomag.lat >= 60.0*D2R) gt60deg = 1; // 1 means a location is greater than 60 degrees
		// Check the control point 1000 km from the receiver
		GeomagneticCoords(path->CP[R1k].L, &Geomag);
		if(Geomag.lat >= 60.0*D2R) gt60deg = 1; // 1 means a location is greater than 60 degrees 
	}

    // Find the basic MUF index to retrieve the values from P.842-4 Table 2
	fBMUFR = path->frequency/path->BMUF;

	BMUF = 9; // Initialize just in case

	if(0.8 >= fBMUFR) {
		BMUF = 0;
	}
	else if((0.8 < fBMUFR) && (fBMUFR <= 1.0)) {
		BMUF = 1;
	}
	else if((1.0 < fBMUFR) && (fBMUFR <= 1.2)) {
		BMUF = 2;
	}
	else if((1.2 < fBMUFR) && (fBMUFR <= 1.4)) {
		BMUF = 3;
	}
	else if((1.4 < fBMUFR) && (fBMUFR <= 1.6)) {
		BMUF = 4;
	}
	else if((1.6 < fBMUFR) && (fBMUFR <= 1.8)) {
		BMUF = 5;
	}
	else if((1.8 < fBMUFR) && (fBMUFR <= 2.0)) {
		BMUF = 6;
	}
	else if((2.0 < fBMUFR) && (fBMUFR <= 3.0)) {
		BMUF = 7;
	}
	else if((3.0 < fBMUFR) && (fBMUFR <= 4.0)) {
		BMUF = 8;
	}
	else if(4.0 < fBMUFR) {
		BMUF = 9;
	}

    // Deciles day-to-day
	DlSd = Table2LD[gt60deg][BMUF];
	DuSd = Table2UD[gt60deg][BMUF];

	// Deciles hour-to-hour
	DuSh = 5.0;
	DlSh = 8.0;


	// Step 6: "Upper decile deviation of resultant signal-to-noise ratio (dB)"
	x = pow(10.0, (noiseP.FaA/10.0)) + pow(10.0, (noiseP.FaM/10.0)) + pow(10.0, (noiseP.FaG/10.0));
	y = pow(10.0, ((noiseP.FaA-noiseP.DlA)/10.0)) + pow(10.0, ((noiseP.FaM- noiseP.DlM)/10.0)) + pow(10.0, ((noiseP.FaG- noiseP.DlG)/10.0));

	path->DuSN = sqrt(pow(10.0*log10(x/y),2) + pow(DuSd,2) + pow(DuSh,2));

	// Step 9: "Upper decile deviation of resultant signal-to-noise ratio (dB)"
	// The value in variable x can be reused from Step 6 above.
	y = pow(10.0, ((noiseP.FaA+ noiseP.DuA)/10.0)) + pow(10.0, ((noiseP.FaM+ noiseP.DuM)/10.0)) + pow(10.0, ((noiseP.FaG+ noiseP.DuG)/10.0));

	path->DlSN = sqrt(pow(10.0*log10(y/x),2) + pow(DlSd,2) + pow(DlSh,2));

	// Step 11: "Basic circuit reliability for S/N >= or < S/Nr (%)"
	if(path->SNR >= path->SNRr) {
		path->BCR = min(130.0 - 80.0/(1.0 + ((path->SNR-path->SNRr)/path->DlSN)), 100.0);
	}
	else { // (SNR < path->SNRr)
		path->BCR = max(80.0/(1.0 + ((path->SNRr-path->SNR)/path->DuSN)) - 30.0, 0.0);
	}

    // End of BCR Calculation *********************************************************************

	// Begin simplified approximate BCR calculation for digital modulation systems ****************

	// The following calculation is based on a method found in ITU-R P.842-4 
	// Section 9: "BCR for digital modulation systems". 

	if((path->Modulation == DIGITAL) && ((path->T0 != 0.0) && (path->F0 != 0.0))) {
		// Note: The following equivalences
		//		SNR0 = path->SNRr
		//		SNRm = path->SNR
		//		Dl = path->DlSN
		//		Du = path->DuSN

		// Time spread
		D = path->distance; // for readability
		if(D <= 2000.0) {
			Tm = min((2.5e7*(1.0 - pow((path->frequency/path->BMUF),2))*pow(D,-2)), (7.0 - 0.00175*D));	
		}
		else { // path->distance <= 2000.0
			Tm = min((4.27e-2*(1.0 - pow((path->frequency/path->BMUF),2))*pow(D,0.65)), 3.5);	
		}

        // Frequency spread
		Fm = 0.02*path->frequency*Tm;

		// Time deciles
		DTu = 0.15*Tm;
		DTl = 0.15*Tm;

		// Frequency deciles
		DFu = 0.1*Fm;
		DFl = 0.1*Fm;

		// Probability that the required signal-to-noise ratio is achieved
		if(path->SNR >= path->SNRr) {
			path->RSN = min(130.0 - 80.0/(1.0 + ((path->SNR - path->SNRr)/path->DlSN)), 100.0);
		}
		else { // (path->SNR < path->SNRr)
			path->RSN = max((80.0/(1.0 + ((path->SNRr - path->SNR)/path->DuSN)) - 30.0), 0.0);
		}

        // Probability that the required time spread, T0, at a level of -10 dB relative to the peak 
		// signal amplitude 
		if(Tm >= path->T0) {
			path->RT = min((130.0 - 80.0/(1.0 + (path->T0 - Tm)/DTl)), 100.0);
		}
		else { // (Tm < path->T0)
			path->RT = max((80.0/(1.0 + (Tm - path->T0)/DTu) - 30.0), 0.0);
		}

        // Probability that the required frequency spread f0 at a level of -10 dB relative to the peak 
		// signal amplitude
		if(Fm >= path->F0) {
			path->RF = min((130.0 - 80.0/(1.0 + (path->F0 - Fm)/DFl)), 100.0);
		}
		else { // (Fm < path->F0)
			path->RF = max((80.0/(1.0 + (Fm - path->F0)/DFu) - 30.0), 0.0);
		}
    } // path->Modulation == DIGITAL

	// End simplified approximate BCR calculation for digital modulation systems ******************

	// Begin OCR Calculation **********************************************************************

	if ((path->Modulation == DIGITAL) && (path->distance <= 9000.0)) {

		// Table 3 P.842-4

		// There are three sums for this calculation that can be accomplished in one loop:
		//		i) The interference sum with the protection ratio
		//		ii) The interference sum with the protection ratio and upper decile deviation
		//		iii) The interference sum with the protection ratio and lower decile deviation

		// First set all the deviations
		// Step 5: Set all the day-to-day deciles to 0 dB
		// Step 6: Upper decile deviation of the wanted signal, DuSh, was set to 5 dB above
		// the lower decile deviations of the interfering signal is set to 8 dB
		DlIh = 8.0;

		// Step 9: Lower decile deviation of the wanted signal, DlSh, was set to 8 dB above
		// the lower decile deviations of the interfering signal is set to 8 dB
		DuIh = 5.0;

		// Prepare the sums that will be used in the following steps:
		//		Step 4: Median resultant signal-to-interference signal (dB)
		//		Step 7: Upper decile deviation of resultant signal-to-interference ratio (dB)
		//		Step 10: Lower decile deviation of resultant signal-to-interference ratio (dB)
		// Initialize the sums
		Isum = 0.0;  // The interference sum with the protection ratio
		Isumu = 0.0; // The interference sum with the protection ratio and upper decile deviation
		Isuml = 0.0; // The interference sum with the protection ratio and lower decile deviation
		// iI[] came from the routine DigitalModulationSignalandInterferers() which grouped 
		// all E and F2 layers together. Consequently the E and F2 layers will have to be determined
		// separately here. Note: In the F2 layer loop the index is offset by 3 for the 3 E layer modes.
		// E layer loop
		for(n=0; n<MAXMDS; n++) {
			if(iI[n] != NOTINDEX) {  //
				if(iI[n] < MAXEMDS) { // E mode interference
					Isum += pow(10.0, ((path->Md_E[iI[n]].Prw - path->A)/10.0));
					Isumu += pow(10.0, ((path->Md_E[iI[n]].Prw - path->A + DuIh)/10.0));
					Isuml += pow(10.0, ((path->Md_E[iI[n]].Prw - path->A - DlIh)/10.0));
				}
				else { // F2 mode interference 
					Isum += pow(10.0, ((path->Md_F2[iI[n]-3].Prw - path->A)/10.0));
					Isumu += pow(10.0, ((path->Md_F2[iI[n]-3].Prw - path->A + DuIh)/10.0));
					Isuml += pow(10.0, ((path->Md_F2[iI[n]-3].Prw - path->A - DlIh)/10.0));
				}
            }
        }

        // In the situation where all the modes are signal and there is no interference
		// there is nothing more to do
		// Return the values set by this program with the interference set to 
		if(Isum == 0.0) {
			path->DuSI = DuSh;
			path->DlSI = DlSh;
			path->MIR = 0.0;
			path->OCR = path->BCR*path->MIR/100.0;
			return;
		}

        // Step 4: Determine the signal-to-interference ratio
		path->SIR = S - 10.0*log10(Isum);

		// Step 7: Determine the upper decile deviation of the signal-to-interference ratio
		path->DuSI = sqrt(pow(DuSh,2) + pow(10.0*log10(Isum/Isuml),2));

		// Step 10: Determine the lower decile deviation of the signal-to-interference ratio
		path->DlSI = sqrt(pow(DlSh,2) + pow(10.0*log10(Isumu/Isum), 2));

		// Step 12: Circuit reliability in the presence of interference only for S/I >= or < S/Ir
		if(path->SIR >= path->SIRr) {
			path->MIR = min(130.0 - 80.0/(1.0 + ((path->SIR-path->SIRr)/path->DlSI)), 100.0);
		}
		else { // (SIR < path->SIRr)
			path->MIR = max((80.0/(1.0 + ((path->SIRr-path->SIR)/path->DuSI))) - 30.0, 0.0);
		}

        // Overall Circuit reliability in the absence of scattering
		path->OCR = path->BCR*path->MIR/100.0;

		// Find the Overall Circuit reliability with scattering
		EquatorialScattering(path, iS); 
	}

    // End OCR Calculation ************************************************************************

	// SNR for the required reliability
	//
	// For details please see 
	// "CCIR Report 322 Noise Variation Parameters"
	// Technical Document 2813, June 1995
	// D. C. Lawrence
	// Naval Command, Control and Ocean Surveillance Center
	// RDT&E Division
	// http://www.dtic.mil/dtic/tr/fulltext/u2/a298722.pdf
	//
	// Note: The NORM[40] = 1.28 
	//		 SNRXX = SNR50 +- t(XX%)*(D_u,l/1.28)

	if(path->SNRXXp < 50) {
		path->SNRXX = path->SNR + path->DuSN*NORM[50-path->SNRXXp]/NORM[40]; 
	}
	else { // path->Relr >= 50 
		path->SNRXX = path->SNR - path->DlSN*NORM[path->SNRXXp - 50]/NORM[40];
	}

    return;

} // End CircuitReliability()

void ModeSort(struct Mode *M[MAXMDS], int order[MAXMDS], int criteria) {

	/*
	 
	  ModeSort() is used to return the index to the mode of interest.
	  
	 		INPUT
	 			struct Mode *M[MAXMDS] - 3 E modes and 6 F2 modes in one array
	 			int criteria - Flag that is either DOMINANT or SOONEST
	 
	 		OUTPUT
	 			int order[MAXMDS] - Index array of the modes in the order desired

			SUBROUTINES
				None
	  
	 */

	int n, m;
	int j; 

	// Initialize the order array
	m = 0; // This is so the array gets loaded from 0
	for(n=0; n<MAXMDS; n++) {
		// Find the modes that exist
		if (M[n]->BMUF != 0.0) {
			order[m++] = n; 
		}
    }

    if(criteria == DOMINANT) {
		for(n=0; n<MAXMDS; n++) {
			if(order[n] != NOTINDEX) { // The mode exists
				for(m=0; m<MAXMDS; m++) {
					if(order[m] != NOTINDEX) { // The mode exists
						if(M[order[n]]->Ew > M[order[m]]->Ew) {
							j = order[n];
							order[n] = order[m];
							order[m] = j;
						}
                    }
                }
            }
        }
    }
	else if(criteria == SOONEST) {
		for(n=0; n<MAXMDS; n++) {
			if(order[n] != NOTINDEX) { // The mode exists
				for(m=0; m<MAXMDS; m++) {
					if(order[m] != NOTINDEX) { // The mode exists
						if((M[order[n]]->BMUF != 0.0) && (M[order[n]]->tau < M[order[m]]->tau)) {
							j = order[n];
							order[n] = order[m];
							order[m] = j;
						}
                    }
                }
            }
        }
    }

    return;

} // End ModeSort()

int NumberofModes(struct PathData path) {

	/*
	 
	  NumberofModes() - Counts the number of modes in the path structure
	 
	 		INPUT
	 			struct PathData path
	 
	 		OUTPUT
	 			returns the number of modes that exist
	 
	 		SUBROUTINES
				None

	 */

	int n, count;

	count = 0;
	for(n=0; n<MAXEMDS; n++) {
		// Only count the modes that exist
		if(path.Md_E[n].BMUF != 0.0) {
			count += 1;
		}
    }
    for(n=0; n<MAXF2MDS; n++) {
		// Only cound the modes that exist
		if(path.Md_F2[n].BMUF != 0.0) {
			count += 1;
		}
    }

    return count;

} // End NumberofModes()

double DigitalModulationSignalandInterferers(struct PathData *path, int iS[MAXMDS], int iI[MAXMDS]) {

	/*
	 
	 	DigitalModulationSignalandInterferers() Returns the signal that satisfies the amplitude ratio, A, 
	 		and the time window, Tw. It also returns the indicies of the interfering modes in the array, iI[9]
	 		The latter is used in the calculation of overall circuit reliability, OCR
	 
	 		INPUT
	 			struct PathData *path
	 			
	 		OUTPUT
	 			int iS[MAXMDS] - Index array of the modes that meet the signal criteria
	 			int iI[MAXMDS] - Index array of the modes that meet the interference criteria
	 
	 		SUBROUTINES
				ElevationAngle()
				NumberofModes()
				ModeSort()

	 */

	int n,m,j;	// Temp

	double Etw;		// Sum of the field strengths (dB) within path->A of the dominant mode and Tw of the earlest arriving mode
	double Ssum;	// Sum of the field strengths 
	double S;		// Signal used in Step 1 Table 1 and Table 3 P.842-4
	double dh;		// Hop distance
	double hr;		// Reflection height
	double delta;	// Elevation angle
	double psi;		// Half hop angle 
	double ptick;	// Slant range
	double deltat;	// Time window criteria
	double deltaA;  // A ratio criteria 

	struct Mode *M[MAXMDS];	// This array is so that all modes can be examined together independant of E or F2 layer
	// The following 2 arrays, iPrw and itau, are modes indicies arrays for the digital BCR, SIR and OCR calculation
	int iEw[9]; 
	int itau[9];

	// Initalize the order array
	for(n=0; n<MAXMDS; n++) { 
		iEw[n]	= NOTINDEX;
		itau[n] = NOTINDEX;
		iI[n]	= NOTINDEX;
		iS[n]	= NOTINDEX;
	}

    //*****************************************************************************************
	// Although the slant range. ptick, was calculated in MedianSkywaveFieldStrengthShort() is was
	// calculated under the E layer screening condition which is not relevant here so ptick must be
	// calculated here. 
	if(path->distance <= 9000.0) {
		// Determine the time delay for all modes that exist
		// E layer modes
		// E layer reflection height
		hr = 110.0;
		for(n=path->n0_E; n<MAXEMDS; n++) {
			if(path->Md_E[n].BMUF != 0.0) { // Mode exists
				dh = path->distance/(n+1); // Hop distance
				delta = ElevationAngle(dh, hr);
				psi = dh/(2.0*R0);
				ptick = 2.0*R0*(sin(psi)/cos(delta - psi));
				path->Md_E[n].tau = (n+1)*(ptick/VofL)*1000.0;
			}
        }

        // F2 layer modes
		// The reflection height for each F2 mode was calculated in ELayerScreeningFrequency()
		for(n=path->n0_F2; n<MAXF2MDS; n++) {
			if(path->Md_F2[n].BMUF != 0.0) { // Mode exists
				hr = path->Md_F2[n].hr;
				dh = path->distance/(n+1); // Hop distance
				delta = ElevationAngle(dh, hr);
				psi = dh/(2.0*R0);
				ptick = 2.0*R0*(sin(psi)/cos(delta - psi));
				path->Md_F2[n].tau = (n+1)*(ptick/VofL)*1000.0;
			}
        }

        // Do the following if there are 2 or more modes 
		if(NumberofModes(*path) >= 2) {
			// For this calculation the layers don't matter so set up an array of all the modes
			// so that a single loop can be used
			// Point the M[] array at all of the modes in path
			for(n=0; n<MAXEMDS; n++) {
				M[n] = &path->Md_E[n];
			}
            for(n=0; n<MAXF2MDS; n++) {
				M[n+3] = &path->Md_F2[n];
			}

            // P.533-12 Section 10.2.3 Reliability prediction procedure
			// Step 1: Determination of the dominant mode, Ew
			ModeSort(M, iEw, DOMINANT); // iEw[0] is the dominant mode

			// Order the modes by time also			
			ModeSort(M, itau, SOONEST); // itau[0] is the earlest mode

			// Step 2: All other active modes with strengths exceeding (Ew - A (dB)) are identified.
			// Step 3: The first arriving mode is identified, and all modes within the time window, Tw, 
			// measured from the first arriving mode, are identified.
			// Step 4: For path lengths up to 7000 km, a power summation of the modes arriving within the
			// window is made, or for path lengths between 7000 and 9000 km the interpolation procedure is used.
			// the basic circuit reliability, BCR, is determined using the same method as in the analog modulation.
			// Step 2 gives an amplitude criteria and Step 3 gives a delay criteria and determine the modes which fulfill 
			// both of these criteria. Step 4 details what to do with the modes that meet the criteria.

			// The zeroth element in the itau[] array is the soonest arriving mode.
			deltat = (M[itau[0]]->tau + path->TW/1000.0); // The tau for each mode is in seconds where the time window, TW, is in mS
			// The zeroth element in the iEw[] array is the dominant mode.
			deltaA = (M[iEw[0]]->Prw - path->A);

			// Initialize signal sum
			Ssum = 0.0;
			for(n=0; n<MAXMDS; n++) {
				// Sum the mode as signals that satisfy the following criteria:
				//		i) The mode exists
				//		ii) The mode within the A ratio of the dominant mode median received power
				//		iii) The mode arrives within TW of the earlest arriving mode
				if(M[n]->BMUF != 0.0) { 
					if((M[n]->Prw >= deltaA) && (M[n]->tau <= deltat)) {

						Ssum += pow(pow(10.0, M[n]->Ew/10.0), 2);
						iS[n] = n;
					}
					else { // If the mode is not determined to be a signal then it is interference.
						// Store the index of the interfering modes for later use in the calculation of 
						// the signal-to-interference ratio (See Table 3 P.842-4).
						iI[n] = n;
					}
                }
            }

            // Only determine Etw if there is a mode that satisfies the criteria above 
			// otherwise Etw is set to the something small
			if(Ssum > 0) {
				Etw = 10.0*log10(sqrt(Ssum));
			}
			else {
				Etw = TINYDB;
			}

            // Use the combined mode power, Ssum, and the antenna gain, Grw
			S = Etw + path->Grw - 20.0*log10(path->frequency) - 107.2;

		}
		else { // (NumberofModes(*path) < 2) 
			// There is only one mode. 
			S = path->Pr;
		}
    } // if(path->distance <= 9000.0) 
	else { // (path->distance > 9000.0) 
		S = path->Pr;
	}

    // The iI[] and iS[] arrays need to be ordered.
	for(n=0; n<MAXMDS; n++) {
		for(m=0; m<MAXMDS; m++) {
			if(iI[n] < iI[m]) {
				j = iI[n];
				iI[n] = iI[m];
				iI[m] = j;
			}
            if(iS[n] < iS[m]) {
				j = iS[n];
				iS[n] = iS[m];
				iS[m] = j;
			}
        }
    }

    return S;

}

void EquatorialScattering(struct PathData *path, int iS[MAXMDS]) {

	/* 
	  EquatorialScattering() - Determines the equatorial scattering by the method described 
	 		in P.533-12 Section 10.3 "Equatorial Scattering"
	 
	 		INPUT
	 			struct PathData *path
	 			int iS[MAXMDS] - Index array of the modes identified as interference
	 
	 		OUTPUT
	 			path->OCRs - Overall circuit reliability with scattering
	 
			SUBROUTINES
				FindFlambdad()
				FindFTl()

	 */

	double pm;				// Mode within the amplitude ratio and time window
	double PTspread[MAXMDS];		// Time spread of the mode which satisfies the amplitude and time criteria
	double Tspread = 1.0;	// Standard deviation of the time spread, taken as 1 mS
	double tau;				// Time delay being considered
	double taum;			// Time delay of the mode
	double PFspread[2];		// Frequency spreading of the dominant mode
	double Fspread = 3.0;	// Standard deviation of the frequency spread, taken as 3 Hz
	double f;				// Frequency being considered
	double fm;				// Transmitted center frequency
	double probocc[MAXMDS];		// Probability of the occurrence of scattering 
	double biggest;			// Temp
	double FR;				// Temp			 
	double FS;				// Temp
	double Flambdad;		// Temp
	double FTl;				// Temp

	int n;					// Temp
	int useCP;				// Flag

	// P.533-12 Section 10.3 "Equatorial Scattering"
	// Step 7: Find the time spread for the modes that are within the amplitude ratio, A, and time 
	// window, Tw. The index for these modes can be found in the array iS[9] which was determined 
	// by the routine DigitalModulationSignalandInterferers().

	if((path->distance <= 9000.0) && (iS[0] != NOTINDEX)) {

		tau = path->TW; 

		for(n=0; n<MAXMDS; n++) {
			// Initialize PTspread to something tiny, DBL_MIN 
			PTspread[n] = DBL_MIN;
			if(iS[n] != NOTINDEX) {
				if(iS[n] < MAXEMDS) { 
					// The mode is an E mode
					pm = path->Md_E[iS[n]].Ew;
					taum = path->Md_E[iS[n]].tau;
				}
				else { 
					// The mode is a F2 mode
					pm = path->Md_F2[iS[n]].Ew;
					taum = path->Md_F2[iS[n]].tau;
				}

                PTspread[n] = 0.056*pm*exp(-(pow((tau - taum), 2))/(2.0*pow(Tspread, 2))); 

			}
        }

        // Step 8: Determine the frequency spreading of the dominant mode
		// Find the dominant mode
		if(iS[0] < MAXEMDS) { 
			// The mode is an E mode
			pm = path->Md_E[iS[0]].Ew;
		}
		else { 
			// The mode is a F2 mode
			pm = path->Md_F2[iS[0]].Ew;
		}

        // Center frequency
		fm = path->frequency*1e6; // (Hz)

		// Frequency window
		f = path->FW; // (Hz)

		PFspread[0] = 0.056*pm*exp(-(pow((+f - fm), 2))/(2.0*pow(Fspread, 2))); 
		PFspread[1] = 0.056*pm*exp(-(pow((-f - fm), 2))/(2.0*pow(Fspread, 2))); 

		// Step 9: Determine the probability of scattering occuring
		useCP = FALSE;
		// Determine if the time spread component is within the amplitude ratio, A, of the 
		// dominant mode power level 
		for(n=0; n<MAXMDS; n++) {
			if(PTspread[n] != DBL_MIN) {
				// Note: At this point pm is the dominant mode 
				if((pm - PTspread[n]) >= path->A) {
					useCP = TRUE;
				}
            }
        }
        // Determine is the frequency spread components are within the amplitude ratio, A, of the 
		// dominant mode power level 
		for(n=0; n<2; n++) {
			if((pm - PFspread[n]) >= path->A) {
					useCP = TRUE;
				}
        }

        // Determine the coefficients for the probocc calculation that are independant of the control point
		FR = (0.1 + 0.008*max(path->SSN, 160));
		FS = 0.55 + 0.45*sin(60.0*D2R*((path->month+1.0) - 1.5));

		if(useCP == TRUE) { // Use the control points
			for(n=0; n<MAXMDS; n++) { // Examine all modes

				// Initialize variables for each mode
				probocc[n] = 0.0;
				Flambdad = 0.0;
				FTl = 0.0;

				if((iS[n] != NOTINDEX) && (iS[n] >= MAXEMDS)) { // Does the mode exist and is it an F2 layer mode?
					if(iS[n] == path->n0_F2) { // Lowest order F2 mode
						if(path->distance <= path->dmax) {
							Flambdad = FindFlambdad(path->CP[MP]);
							FTl = FindFTl(path->CP[MP]);							
						}
						else {
							if(PTspread[Td02] >= PTspread[Rd02]) {
								Flambdad = FindFlambdad(path->CP[Td02]);
								FTl = FindFTl(path->CP[Td02]);
							}
							else {
								Flambdad = FindFlambdad(path->CP[Rd02]);
								FTl = FindFTl(path->CP[Rd02]);
							}
                        }
                    }
					else { // Higher order F2 modes
						if(path->distance <= path->dmax) { 
							// Find the largest time scattering by brute force
							if((PTspread[T1k] >= PTspread[R1k]) && (PTspread[T1k] >= PTspread[MP])) {
								Flambdad = FindFlambdad(path->CP[T1k]);
								FTl = FindFTl(path->CP[T1k]);
							} else if ((PTspread[R1k] >= PTspread[T1k]) && (PTspread[R1k] >= PTspread[MP])) {
								Flambdad = FindFlambdad(path->CP[R1k]);
								FTl = FindFTl(path->CP[R1k]);
							} else if ((PTspread[MP] >= PTspread[R1k]) && (PTspread[MP] >= PTspread[T1k])) {
								Flambdad = FindFlambdad(path->CP[MP]);
								FTl = FindFTl(path->CP[MP]);
							}
                        }
						else {
							if((PTspread[T1k] >= PTspread[R1k]) &&
							   (PTspread[T1k] >= PTspread[Td02]) &&
							   (PTspread[T1k] >= PTspread[MP]) &&
							   (PTspread[T1k] >= PTspread[Rd02])) {
								Flambdad = FindFlambdad(path->CP[T1k]);
								FTl = FindFTl(path->CP[T1k]);
							} 
							else if ((PTspread[R1k] >= PTspread[T1k]) && 
								     (PTspread[R1k] >= PTspread[MP]) &&
									 (PTspread[R1k] >= PTspread[Td02]) &&
									 (PTspread[R1k] >= PTspread[Rd02])) {
								Flambdad = FindFlambdad(path->CP[R1k]);
								FTl = FindFTl(path->CP[R1k]);
							} 
							else if ((PTspread[MP] >= PTspread[R1k]) && 
								     (PTspread[MP] >= PTspread[T1k]) &&
									 (PTspread[MP] >= PTspread[Td02]) &&
									 (PTspread[MP] >= PTspread[Rd02]))	{
								Flambdad = FindFlambdad(path->CP[MP]);
								FTl = FindFTl(path->CP[MP]);
							} 
							else if((PTspread[Td02] >= PTspread[Rd02]) && 
								    (PTspread[Td02] >= PTspread[MP]) &&
									(PTspread[Td02] >= PTspread[T1k]) &&
									(PTspread[Td02] >= PTspread[R1k]))	{
								Flambdad = FindFlambdad(path->CP[Td02]);
								FTl = FindFTl(path->CP[Td02]);
							} 
							else if ((PTspread[Rd02] >= PTspread[T1k]) && 
								     (PTspread[Rd02] >= PTspread[MP]) &&
									 (PTspread[Rd02] >= PTspread[R1k]) &&
									 (PTspread[Rd02] >= PTspread[Td02])) {
								Flambdad = FindFlambdad(path->CP[Rd02]);
								FTl = FindFTl(path->CP[Rd02]);							
							}
                        }
                    } // Higher order F2 modes
				} // Does mode exist

				probocc[n] = Flambdad*FTl*FR*FS;

			} // for(n=0; n<9; n++)

			// Find the biggest probocc
			biggest = 0.0;
			for(n=0; n<MAXMDS; n++) {
				if(probocc[n] > biggest) {
					biggest =  probocc[n];
				}
            }

            path->probocc = biggest; 

			// Find the overall circuit reliabilty with scattering
			path->OCRs = path->BCR*path->MIR*(100.0 - path->probocc)/10000.0;

		} // useCP == TRUE
		else { // useCP == FALSE

			path->OCRs = path->BCR*path->MIR/100.0;
		}
    }

    return;

}

double FindFlambdad(struct ControlPt CP) {

	/*
	 
	  FindFlambdad() - Determines F sub lambda sub d in P.533-12 Appendix 1
	 		to Annex 1 "A model for scattering of HF signals"
	 
	 	INPUT 
	 		Control point to determine F sub lambda sub d
	 
	 	OUTPUT
	 		returns F sub lambda sub d
	 
	 	SUBROUTINES
			None

	 */

	double lambdad;

	// Magnetic dip parameter 
	lambdad = fabs(CP.dip[HR100km]);
	if((0.0 <= lambdad) && (lambdad < 15.0*D2R)) {
		return 1.0;
	}
	else if((15.0*D2R <= lambdad) && (lambdad < 25.0*D2R)) {
		return pow(((25.0 - lambdad)/10.0), 2) * ((lambdad - 10.0)/5.0);
	}
	else if((25.0*D2R <= lambdad) && (lambdad <= 90.0*D2R)) {
		return 0.0;
	}

    return 0.0;

}

double FindFTl(struct ControlPt CP) {

	/*
	 
	 
	  FindTl() - Determines the F sub T sub l parameter in in P.533-12 Appendix 1
	 		to Annex 1 "A model for scattering of HF signals"
	 
	 	INPUT 
	 		Control point of interest
	 
	 	OUTPUT 
	 		returns time parameter F sub T sub l for the calculation of Prob sub occ
	 
	 	SUBROUTINES
			None

	 */

	double Tl;

	// Time parameter
	Tl = CP.ltime;
	if((0.0 < Tl) && (Tl <= 3.0)) {
		return 1.0;
	}
	else if((3.0 < Tl) && (Tl <= 7.0)) {
		return pow(((7.0 - Tl)/4.0), 2) * ((Tl - 1.0)/2.0);
	}
	else if((7.0 < Tl) && (Tl <= 19.0)) {
		return 0.0;
	}
	else if((19.0 < Tl) && (Tl <= 20.0)) {
		return pow((Tl - 19.0), 2)*(41.0 - 2.0*Tl);
	}
	else if((20.0 < Tl) && (Tl <= 24.0)) {
		return 1.0;
	}

    return 0.0;
}
