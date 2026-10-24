#include <stdio.h>
#include <stdlib.h>
#include <math.h>

// Local includes
#include "Common.h"
#include "P533.h"

// Testing 
// This turns diagnostic prints off (FALSE) and on (TRUE)
#define BARF FALSE
//#define BARF TRUE
#define BARFCP FALSE
// Testing

// Local Define
#define NOIL -0.17
// End Local Define

// Control point array, CP, defines to enhance readability.
#define MAXCP	28 // There are a potential 26 90 km penetration points and 2 control points from Table 1a.
#define TdM2	26 // For this routine this will be the index to the Control point at T + d0/2.
#define RdM2	27 // For this routine this will be the index to the Control point at R - d0/2.

// Define for FindfL()
#define NOTIME	99

// Define for WinterAnomaly()
#define SOUTH	1
#define NORTH	0

// Local prototypes
void FindMUFsandfM(struct PathData *path, struct ControlPt CP[MAXCP][24], int hops, double dh);
void FindfL(struct PathData *path, struct ControlPt CP[MAXCP][24], int hops, double dh, double ptick, double fH, double i90);
double WinterAnomaly(double lat, int month);
void CopyCP(struct ControlPt thisCP, struct ControlPt *thatCP);
void iRollOverTime(int *time);
// Testing 
int hrs(double time);
int mns(double time);
// Testing
// End local prototypes

// Testing
void PrintControlPointData(struct ControlPt CP, int i, int j);
int degrees(double coord);
int minutes(double coord);
int seconds(double coord);
// Testing

// End local prototypes

void MedianSkywaveFieldStrengthLong(struct PathData *path) {

	/*
	 
	  MedianSkywaveFieldStrengthLong() - Determines the signal strength for paths greater than 9000 km in accordance with 
	 		P.533-12 Section 5.3 "Paths longer than 7000 km". This routine is patterned after the FTZ() method found in REC533(). 
	 		The basis of this routine here and in the algorithm in P.533-12 is from work by	Thomas Dambolt and Peter Suessman. 
	 
	 		INPUT
	 			struct PathData *path
	 
	 		OUTPUT
	 			path->El - Median field strength (dB(1uV/m))
	 
	 		SUBROUTINES
				ElevationAngle()
				ZeroCP()
				GreatCirclePoint()
				CalculateCPParameters()
				AntennaGain08()
				findfM()
				findfL()

	 */

	int n, i, j;	// Temp
	int hour;		// Temp

	// fL Calculation
	int nL;				// Number of hops 
	double deltaL;		// Elevation angle
	double dL;			// Hop distance

	// fM Calculation
	int nM;				// Number of hops
	double deltaM;		// Elevation angle
	double dM;			// Hop distance

	double hr;			// Mirror reflection height
	double Pt;			// Transmitter power
	double i90;			// Angle of incidence at a height of 90 km

	double psi;
	double phi;			// 90 km penetration angle
	double dh90;		// 90 km penetration distance

	double fracd;		// fractional distance
	double f;			// path->frequency
	double Etl;			// Resultant median field strength
	double D;			// Path distance for focus gain term

	double elevation;	// Antenna elevation

	struct ControlPt CP[MAXCP][24]; // Temp

	// Initialize variables
	elevation = 2.0*PI;

	// This procedure applies only to paths greater than 7000 km. 
	//   i)  For paths greater than 7000 km the field strength is interpolated 
	//	     with the short (< 7000 km) path method.
	//   ii) For paths greater than 9000 km this is the only method used
	if(path->distance >= 7000.0) {

		// For both the fM and fL calculation the reflection height is 300 km
		hr = 300.0;

		// fL Calculation Geometry
		// The following determines the hops for the penetration points
		// The number of hops is determined by finding equal hops
		// that are 3000 km or less
		for(n = 0; path->distance/(n+1.0) > 3000.0; n++);
		
		// Number of hops
		nL = n;

		// Hop distance
		dL = path->distance/(nL+1.0);  

		// Determine the elevation angle for fL hops
		deltaL = ElevationAngle(dL, hr);

		// fM Calculation Geometry
		// The following determines the hops for the control points
		// The number of hops is determined by finding equal hops
		// that are 4000 km or less. For this calculation the minimum
		// elevation angle is taken into account. If the the hops are 
		// determineded to be 4000 km or less and the elevation angle is 
		// found to be below the minimum another hop is added
		for(n = 0; path->distance/(n+1.0) > 4000.0; n++);

		// Number of hops
		nM = n;

		// Hop distance
		dM = path->distance/(nM+1.0);  

		// Determine the elevation angle
		deltaM = ElevationAngle(dM, hr);

		// Is the calculated elevation angle more than the minimum
		if(deltaM < MINELEANGLEL*D2R) {
			nM += 1; // Add a hop

			// Hop distance
			dM = path->distance/(nM+1.0);  
			// Elevation angle
			deltaM = ElevationAngle(dM, hr);

			}

        /**********************************************************************************************************
           Control and Penetration point initialization for the reference frequencies
          
           For the fL calculation, there will be required 24 hours of data at the locations of interest.
           Both the calculation of the upper and lower reference frequencies, fM and fL respectively,
           require 24 hours of data. 
           For the calculation of fM, the 24 hours of data are required at the control points described in 
           Table 1a) P.533-12. 
           For the calculation of fL, the 24 hours of data are required at the 90 km penetration points. There are 
           potentially 13 hops tx to rx since the circumference of the earth is 40,075.16 and the hop length 
           for this routine is 3000. For every hop there are two penetrations of the 90 km D layer.
           Consequently, 24 x 2 (fM) and 24 x 26 (fL) control points are needed. 
           Therefore, for this calculation 24 x 15 control points are required. 
         *********************************************************************************************************/
			
		// Determine the 90 km penetration points 
		i90 = IncidenceAngle(deltaL, 90.0); 

		// Determine where the rays penetrate the 90 km height to calculate the dips.
		// Find the 90-km height half-hop distance.
		phi = (PI/2.0 - deltaL - i90);
	
		dh90 = R0*phi;

		// The path structure is used to determine the data at the control points 
		// Store the path->hour
		hour = path->hour;

		for(j=0; j<24; j++) { // hours		

			path->hour = j;

			for(i=0; i <= nL; i++) { // 90-km penetration points 

				// Zero the elements of the two control points
				ZeroCP(&CP[2*i][j]);
				ZeroCP(&CP[(2*i)+1][j]);

				// There are two control points per hop.
				// First the end nearest the tx for this hop.
				fracd = (i*dL + dh90)/path->distance;
				GreatCirclePoint(path->L_tx, path->L_rx, &CP[2*i][j], path->distance, fracd);
				CalculateCPParameters(path, &CP[2*i][j]);

				CP[2*i][j].hr = 90.0;
			
				// Next the end nearest to the receiver for this hop
				fracd = ((i+1)*dL  - dh90)/path->distance;
				GreatCirclePoint(path->L_tx, path->L_rx, &CP[(2*i)+1][j], path->distance, fracd);
				CalculateCPParameters(path, &CP[(2*i)+1][j]);

				CP[(2*i)+1][j].hr = 90.0;

				} // (i=0; i < n; i++)

			// Initialize control points (T + d0/2 & R - d0/2) from Table 1a) as the last two control points in the array.
			// First determine the fractional distances and then find the point on the great circle between tx and rx.
			fracd = (1.0/(2.0*(nM+1))); // T + d0/2 as a fraction of the total path length
			GreatCirclePoint(path->L_tx, path->L_rx, &(CP[TdM2][j]), path->distance, fracd);
			fracd = (1.0 - (1.0/(2.0*(nM+1)))); // R - d0/2 as a fraction of the total path length
			GreatCirclePoint(path->L_tx, path->L_rx, &(CP[RdM2][j]), path->distance, fracd);
			// All distances for the control points are relative to the tx.
	
			// Find foF2, M(3000)F2 and foE these control points.
			CalculateCPParameters(path, &CP[TdM2][j]);
			CalculateCPParameters(path, &CP[RdM2][j]);
			
			CP[TdM2][j].x = 0.0;
			CP[TdM2][j].foE = 0.0;
			CP[TdM2][j].hr = 300.0; // For this calculation the reflection height is fixed at 300 km.

			CP[RdM2][j].x = 0.0;
			CP[RdM2][j].foE = 0.0;
			CP[RdM2][j].hr = 300.0; // For this calculation the reflection height is fixed at 300 km.
		
		} // (j=0; j<24; j++)

		// Restore the path->hour
		path->hour = hour;
	
	    /**********************************************************************
		   End control point initialization for the reference frequencies.
		***********************************************************************/
		// The data is now available in the control point array, CP, for the calculation of the reference frequencies.
		
		// Find the virtual slant range (19)
		psi = dM/(2.0*R0);
		path->ptick = fabs(2.0*R0*(sin(psi)/cos(deltaM + psi)))*(nM+1.0);
		
		// Free space field strength
		path->E0 = 139.6 - 20.0*log10(path->ptick);

		path->Gtl = AntennaGain08(*path, path->A_tx, TXTORX, &elevation);

		// Focusing on long distance gain limited to 15 dB
		D = path->distance;

		path->Gap = min(10.0*log10(D/(R0*fabs(sin(D/R0)))), 15.0);

		path->Ly = NOIL;

		// Transmitter power dB (1kW)
		Pt = path->txpower;

		// Mean gyrofrequency
		path->fH = (CP[TdM2][path->hour].fH[HR300km] + CP[RdM2][path->hour].fH[HR300km])/2.0;
		
		// Find the MUF fM
		FindMUFsandfM(path, CP, nM, dM);

		// Lower frequency
		FindfL(path, CP, nL, dL, path->ptick, path->fH, i90);

		f = path->frequency;
		
		// Calculate Etl in sections
		Etl = (pow((path->fL+path->fH),2)/pow((f+path->fH), 2)) + (pow((f+path->fH),2)/pow((path->fM+path->fH),2)); 
		Etl = Etl*(pow((path->fM+path->fH),2)/((pow((path->fM+path->fH),2)) + pow((path->fL+path->fH),2)));

		path->F = 1.0 - Etl;
		
		Etl = path->E0*(1.0 - Etl);
		Etl = Etl - 30.0 + Pt + path->Gtl + path->Gap - path->Ly;
		path->El = Etl;

		/**************************************************************
		   End of the calculation for paths greater than 7000 km 
	    ***************************************************************/

		// In the case that the path is greater than 9000 km, this is the only method that is used so set the path parameters appropriately
		if(path->distance > 9000.0) {

			// The elevation angle for the fM calculation is what reperesents the long path
			path->ele = deltaM;

			// Copy the control points to the path structure
			CopyCP(CP[RdM2][path->hour], &path->CP[Rd02]);
			CopyCP(CP[TdM2][path->hour], &path->CP[Td02]);

			// Copy the two extreme penetration points to control points in the path structure
			CopyCP(CP[0][path->hour], &path->CP[T1k]);
			CopyCP(CP[2*nL][path->hour], &path->CP[R1k]);
				
			// Path dmax
			path->dmax = 4000.0;

		}

        // Testing
		if(BARF) {
			printf("\nP533 MSFSL: Mean gyro frequency (fH) %f\n", path->fH);
			printf(  "P533 MSFSL: Virtual slant range angle using fM parameters (psi) %f\n", psi);
			printf(  "P533 MSFSL: Virtual slant range using fM parameters (ptick) %f\n", path->ptick);
			printf("\nP533 MSFSL: reflection height (hr) %f for both fL and fM calculations\n", hr);
			printf("\nP533 MSFSL: number of penetration points (2*(nL+1)) %d\n",2*(nL+1));
			printf(  "P533 MSFSL: hop distance for penetration points (dhL) %f\n", dL);
			printf(  "P533 MSFSL: elevation angle for dh and hr above (deltaL) %f\n", deltaL);
			printf("\nP533 MSFSL: number of hops for fM calculation (nM+1) %d\n",(nM+1));
			printf(  "P533 MSFSL: hop distance (dM) for control points T+dM/2 and R-dM/2 (dM) %f\n", dM);
			printf(  "P533 MSFSL: elevation angle for dh and hr above (deltaM) %f\n", deltaM);
	
			for(i=0; i <= nL; i++) { // 90-km penetration points 
				printf("\nP533 MSFSL: Hop number (i) %d\n", i);
				PrintControlPointData(CP[(2*i)][hour], 2*i, hour);
				PrintControlPointData(CP[(2*i)+1][hour], (2*i)+1, hour);
			}

            printf("\nP533 MSFSL: Control Points (T + dM/2 & R - dM/2)\n");
			PrintControlPointData(CP[TdM2][hour], TdM2, hour);
			PrintControlPointData(CP[RdM2][hour], RdM2, hour);
		
			printf("\nP533 MSFSL: Incident angle (i90) %f\n", i90);
			printf(  "P533 MSFSL: 90-km height half-hop angle (phi) %f\n", phi); 
			printf(  "P533 MSFSL: 90-km height half-hop distance (dh90) %f\n", dh90); 
			printf(  "P533 MSFSL: Hop distance (dM) %f\n", dM);
		
			printf("\n");
			}
        // Testing

		return;
						
	} // (path->distance >= 7000.0)

}


void FindMUFsandfM(struct PathData *path, struct ControlPt CP[MAXCP][24], int hops, double dM) {

	(void)hops; // To avoid unused parameter warning

	/*
	 
	  FindfM() Finds the upper reference frequency, fM, from 24 hours of calculated MUFs.
		Determines the the basic and operation MUFs for the long path. This routine also
		finds the 10% and 90% decile values for the MUF
	 
	 		INPUT
	 			struct PathData *path
	 			struct ControlPt CP[MAXCP][24] - 24 hours of control point data
	 			int hops - Number of hops
				double dh - hop length
	 
	 		OUTPUT
	 			path->BMUF
				path->MUF50
				path->MUF10
				path->MUF90
				path->OPMUF
				path->OPMUF10 
				path->OPMUF90
				path->fM

			SUBROUTINES
				Bearing()
				FindfoF2var()
	 */

	int t;					// Local time counter
	int n;					// Local counters
	
	double f4[2];			// F2(4000)MUF at the control points  
	double fz[2];			// F2(Zero)MUF at the control points
	double fD;				// Distance reduction factor
	double deltal;			// lower decile of MUF
	double deltau;			// Upper decile of MUF

	int decile;				// decile flag
	int smallerCP;			// Index that indicates the control point where the smaller basic MUF 

	double fBMmin[2];		// The lowest value of f4 in 24 hours at control points
	
	// Values used in the determination of K
	double W[2] = {0.1, 0.2}; 
	double X[2] = {1.2, 0.2}; 
	double Y[2] = {0.6, 0.4}; 
	double A;				// Azimuth at the midpoint
	double EW;				// Interpolation value to fine W, X and Y
	double IW, IX, IY;		// Interpolated values of W, X and Y respectively
	double fBM[2][24];		// F2(D)MUF at the control points for 24 hours 

	int noon[2];			 // temp local noon index 
	
	// The folowing equation for fD comes from 
	// "Predicting the Performance of BAND 7 Communications Systems Using Electronic Computers"
	// NBS Report 7619, D. Lucas, 1963. The coefficients have been changed for  
	// hop distances in km. See page 92 step 12 in section "IX Mathmatical Expressions"

	// Calculate fD "Distance reduction factor"
	fD = (((((( -2.40074637494790e-24 *dM + 
		         25.8520201885984e-21)*dM + 
				-92.4986988833091e-18)*dM + 
		         102.342990689362e-15)*dM +
			     22.0776941764705e-12)*dM +
				 87.4376851991085e-9)*dM +
			     29.1996868566837e-6)*dM;

	// What is the index that will be local noon (UTC) at the T + d0/2 and R - d0/2?
	// The lat and lng of the control points are all the same so the hour is arbitrarily so use 1
	// Subtract one from the value noon[*] since it will be used as an index and not a countable hour 
	noon[0] =  (int)(12.0 - CP[TdM2][1].L.lng/(15.0*D2R)) - 1; // The longitude is subtracted from 12 because W is negative
	noon[1] =  (int)(12.0 - CP[RdM2][1].L.lng/(15.0*D2R)) - 1; //

	// Roll over the time
	noon[0] = (noon[0]+24) % 24;
	noon[1] = (noon[1]+24) % 24;

	// Initialize the variables to find the minimum MUF in 24 hours.
	fBMmin[0] = 100.0;
	fBMmin[1] = 100.0;
	for(t=0; t<24; t++) {
		// Calculate F2(4000)MUF
		f4[0] = 1.1*CP[TdM2][t].foF2*CP[TdM2][t].M3kF2;
		// Calculate F2(ZERO)MUF
		fz[0] = CP[TdM2][t].foF2 + 0.5*CP[TdM2][t].fH[HR300km];	
		
		// Calculate the Basic MUF
		fBM[0][t] = fz[0] + (f4[0] - fz[0])*fD; 
		fBMmin[0] = min(fBM[0][t], fBMmin[0]);
		
		// Calculate F2(4000)MUF	
		f4[1] = 1.1*CP[RdM2][t].foF2*CP[RdM2][t].M3kF2;
		// Calculate F2(ZERO)MUF
		fz[1] = CP[RdM2][t].foF2 + 0.5*CP[RdM2][t].fH[HR300km];
		
		fBM[1][t] = fz[1] + (f4[1] - fz[1])*fD; 
		fBMmin[1] = min(fBM[1][t], fBMmin[1]);
			
	}

    // Before proceeding, finding the forward azimuth at the midpoint is required.
	// The azimuth is used to interpolate the W, X and Y values to calculate K.
	A = Bearing(path->CP[MP].L, path->L_rx, SHORTPATH);

	// Now use A to interpolate the W, X and Y values.
	if(A > PI) {
		A -= PI;
	}
    if(A >= PI/2.0) {
		A -= PI/2.0;
	}
	else { 
		A = PI/2.0 - A;
	}

    EW = A/(PI/2.0);
	IW = W[0]*(1.0-EW) + W[1]*EW; // Interpolated W
	IY = Y[0]*(1.0-EW) + Y[1]*EW; // Interpolated Y
	IX = X[0]*(1.0-EW) + X[1]*EW; // Interpolated X
		
	// fBM,min has been determined for both control points now K  
	for(n=0; n<2; n++) {
		// determine K
		path->K[n] = 1.2 + IW*(fBM[n][path->hour]/fBM[n][noon[n]]) + IX*(pow((fBM[n][noon[n]]/fBM[n][path->hour]), 1.0/3.0) - 1.0) + IY*pow((fBMmin[n]/fBM[n][noon[n]]) ,2);
	}

    // Testing
	if(BARF) {
		printf("\nMUF calculation parameters\n");
		printf(  "P533 MSFSL: F2(D)MUF @ T+dM/n (fBM) %f\n", fBM[0][path->hour]);
		printf(  "P533 MSFSL: F2(D)MUF @ R-dM/n (fBM) %f\n", fBM[1][path->hour]);
		printf(  "P533 MSFSL: F2(ZERO)MUF @ T+dM/n (fz) %f\n", fz[0]);
		printf(  "P533 MSFSL: F2(ZERO)MUF @ R-dM/n (fz) %f\n", fz[1]);
		printf(  "P533 MSFSL: Distance reduction factor (fD) %f\n", fD);
		//printf(  "P533 MSFSL: hop length factor (z) %f\n", z);
		
		printf("\nP533 MSFSL FindfM: (EW) %f\n", EW);
		printf(  "P533 MSFSL FindfM: (A) %f\n", A);
		printf(  "P533 MSFSL FindfM: Interpolated W (IW) %f\n", IW);
		printf(  "P533 MSFSL FindfM: Interpolated Y (IY) %f\n", IY);
		printf(  "P533 MSFSL FindfM: Interpolated X (IX) %f\n", IX);
		for(n=0; n<24; n++) printf(  "P533 MSFSL FindFM: fBM[T+dM/2][%d] %f\n", n, fBM[0][n]);
		for(n=0; n<24; n++)	printf(  "P533 MSFSL FindFM: fBM[R-dM/2][%d] %f\n", n, fBM[1][n]);
		printf(	 "P533 MSFSL FindFM: noon @ T + dM/2 %d\n", noon[0]);
		printf(	 "P533 MSFSL FindFM: noon @ R - dM/2 %d\n", noon[1]);
		printf(  "P533 MSFSL FindFM: fBM,noon[T+dM/2] %f\n", fBM[0][noon[0]]);
		printf(  "P533 MSFSL FindFM: fBM,noon[R-dM/2] %f\n", fBM[1][noon[1]]);
		printf(  "P533 MSFSL FindFM: fBM,min[T+dM/2] %f\n", fBMmin[0]);
		printf(  "P533 MSFSL FindFM: fBM,min[R-dM/2] %f\n", fBMmin[1]);
		printf(  "P533 MSFSL FindfM: path->K[T+dM/2] %f\n", path->K[0]);
		printf(  "P533 MSFSL FindfM: path->K[R-dM/2] %f\n", path->K[1]);
		printf(  "P533 MSFSL FindfM: path->K[T+dM/2]*fBM[T + dM/2] %f\n", path->K[0]*fBM[0][path->hour]);
		printf(  "P533 MSFSL FindfM: path->K[R-dM/2]*fBM[R - dM/2] %f\n", path->K[1]*fBM[1][path->hour]);
	}
    // Testing

	path->fM = min(path->K[0]*fBM[0][path->hour], path->K[1]*fBM[1][path->hour]);

	// Before leaving this routine determine if any of the other MUF parameters should be set
	if(path->distance > 9000) {

		smallerCP = 99; // Initialize smallerCP
		if(fBM[0][path->hour] < fBM[1][path->hour]) {
			smallerCP = TdM2;
			smallerCP = 26;
			// This is the Basic MUF for the path if the path is greater than 9000 km, otherwise it is calculated in MUFBasic()
			if(path->distance > 9000) path->BMUF = fBM[0][path->hour];
		}
		else if(fBM[0][path->hour] >= fBM[1][path->hour]) {
			smallerCP = RdM2;
			smallerCP = 27;
			// This is the Basic MUF for the path if the path is greater than 9000 km, otherwise it is calculated in MUFBasic()
			if(path->distance > 9000) path->BMUF = fBM[1][path->hour];
		}

        // Determine the MUF deciles
		decile = DL; // Lower MUF decile
		// Find the deltal in the foF2var array
		deltal = FindfoF2var(*path, CP[smallerCP][path->hour].ltime, CP[smallerCP][path->hour].L.lat, decile);
				
		decile = DU; // Upper MUF decile
		// Find the deltau in the foF2var array
		deltau = FindfoF2var(*path, CP[smallerCP][path->hour].ltime, CP[smallerCP][path->hour].L.lat, decile);

		// Determine the decile MUFs
		path->MUF50 = path->BMUF;
		path->MUF10 = path->MUF50*deltau;
		path->MUF90 = path->MUF50*deltal;

		// Operation MUF and decile Operation MUF
		path->OPMUF = path->fM;
		path->OPMUF10 = path->OPMUF*deltau; // largest 10% OPMUF
		path->OPMUF90 = path->OPMUF*deltal; // largest 90% OPMUF
	}

    return;
}

void FindfL(struct PathData *path, struct ControlPt CP[MAXCP][24], int hops, double dh, double ptick, double fH, double i90) {

	(void)dh; // To avoid unused parameter warning

	/*
	 
	 	FindfL() - Determines the lower reference frequency from 24 hours of solar zenith angles.
	 
	 		INPUT
	 			struct PathData *path
	 			struct ControlPt CP[MAXCP][24] - 24 hours of control point data including solar zenith angles
	 			int hops - Number of hops
	 			double dh - Hop distance
	 			double ptick - Slant range
	 			double fH - Mean gyrofrequency
	 			double i90 - Incident angle at 90 km
	 
	 		OUTPUT
	 			return fL the upper reference frequency
	 
			SUBROUTINE
				WinterAnomaly()

	 */

	// Winter-anomaly factor
	double Aw; 

	double SumCosChi[24];
	double fLN;
	double fL[24];
	double chi;		// solar zenith angle
	double dt;		// Temp

	int month;	// month index for readability
	int i;
	int t;		// Time index
	int tr;		// Time that requires special treatment
	int prev, now;

	// For readability
	month = path->month;

	for(t=0;t<24;t++) {
		SumCosChi[t] = 0;
		for(i=0; i < 2*(hops+1); i++) { 
			chi = CP[i][t].Sun.sza;
			dt=cos(chi);
			if((chi > 0.0) && (chi < PI/2.0)) {
				// Sum the control points
				SumCosChi[t] += sqrt(cos(chi));
			}
        }
    }

    // Determine winter-anomaly factor, Aw at the path mid-point
	Aw = WinterAnomaly(path->CP[MP].L.lat, month);

	fLN = sqrt(path->distance/3000.0);
	
	// Calculate all 24 values of the LUF first
	// This is necessary first so that we can find the transition of the LUF from
	// day-to-night and not the night-to-day transition.
	// Note: For this calculation the SSN can be greater than MAXSSN
	for(i=0; i<24; i++) { // Calculate fL for 24 hours
		fL[i] = max((5.3*sqrt(((1.0 + 0.009*path->SSN)*SumCosChi[i])/(cos(i90)*log(9.5e6/ptick))) - fH)*(Aw + 1.0), fLN);
	}

    tr = NOTIME; // Initialize the reference time indicator. 
	
	// Testing
	if(BARF) {
		for(i=0;i<24;i++) printf("P533 MSFSL Before tr FindfL: (fL[%d]) %f\n", i, fL[i]);
	}
    // Testing

	// Find the first local time that fL[i] <= 2.0*fLN decay from day-LUF to night-LUF
	for(now=0; now<24; now++) { // Calculate fL for 24 hours
		prev = ((now - 1)+24) % 24; // Find the previous time and roll over

		if(tr == NOTIME) { // only find one reference time in 24 hours
			if((fL[prev] >= 2.0*fLN) && (fL[now] <= 2.0*fLN)) { // Find a time where the LUF is decreasing into night
				tr = now;
				dt = (2.0*fLN - fL[tr])/(fL[prev] - fL[tr]);
				fL[tr] = 0.7945*fL[prev]*(dt*(1.0 - 0.7945) + 0.7945);
				if(fL[now] < fL[tr]) { 
					fL[now] = fL[tr];
				}
            }
        }
    }

    if(tr != NOTIME) {
		for(i=1; i<4; i++) {
			now = ((tr + i)+24) % 24; // Find now and roll over
 
			prev = ((now - 1)+24) % 24; // Find previous time and roll over

			fL[now] = max(fL[prev] * 0.7945, fL[now]);
		}
    }
	else {
		// If tr can't be found
		// Nothing else to do here 
	}

    // Testing
	if(BARF) {
		for(i=0;i<24;i++) printf("P533 MSFSL After tr FindfL: (fL[%d]) %f\n", i, fL[i]);
		printf("\nP533 MSFSL FindfL: winter-anomaly factor (Aw) %f\n", Aw);
		printf(  "P533 MSFSL FindfL: (fLN) %f\n", fLN);	
		for(i=0;i<24;i++) printf("P533 MSFSL FindfL: (SumCosChi[%d]) %f\n", i, SumCosChi[i]);
		printf("\n");
	}
    // Testing

	// Find fL[] for the "present hour" for further calculations 
	// The "present hour" is calculated at path->hour + 1. 
	// Elsewhere in the code it is assumed that UTC 1 uses the index path->hour = 0
	// to represent time from 0:00 to 0:59 
	// In this calculation UTC 1 implies 1:00 to 1:59 UTC so the "present hour" 
	// is path->hour + 1. 
	// This is a consequence of the routine being based on the Fortran program FTZ() 
	// which of course uses indexing beginning at 1
	// Find the "present hour" and roll it over if necessary
	now = (path->hour + 1 + 24) % 24;

	// Set the value of fL[]
	path->fL = fL[now]; 

	// Testing
	if(BARFCP) {
		for(t=0;t<24;t++) {
			for(i=0; i <= hops; i++) { // 90-km penetration points 
				printf("\nP533 MSFSL FindfL: Hop number (i) %d\n", i);
				PrintControlPointData(CP[(2*i)][t], 2*i, t);
				PrintControlPointData(CP[(2*i)+1][t], (2*i)+1, t);
			}
        }
    }
    // Testing


	return; 

}


double WinterAnomaly(double lat, int month) {

	/*

	  WinterAnomaly() calculates the winter anomaly factor for long paths (> 9000 km).
	 		This function uses the Table 5 P.533-12 to find Aw for any latitude.
	 		Values other than 60 degrees latitude are determined by interpolation.
	 
	 		INPUT
	 			double lat - Latitude of the point of interest
	 			double month - The month of interest
	 
	 		OUTPUT
	 			return Aw the interpolated winter anomaly factor 
	 
			SUBROUTINES
				None

	 */

	int iNS;	// North-South index

	// Winter-anomaly factor
	double Aw[12][2] = {{0.30, 0.00},  // January
						{0.15, 0.00},  // February
						{0.03, 0.00},  // March
						{0.00, 0.03},  // April
						{0.00, 0.15},  // May
						{0.00, 0.30},  // June
						{0.00, 0.30},  // July
						{0.00, 0.15},  // August
						{0.00, 0.03},  // September
						{0.03, 0.00},  // October
						{0.15, 0.00},  // November
						{0.30, 0.00}}; // December

	if(lat < 0.0) {
		iNS = SOUTH;
	}
	else {
		iNS = NORTH;
	}

    lat = fabs(lat); // All latitudes must be positive
	
	if(((lat >= 0.0) && (lat <= 30.0*D2R)) || (lat >= 90.0*D2R)) {
		return(0.0);
	}
	// Interpolate with the peak winter anomaly factor at 60 degrees. 
	else if(lat < 60.0*D2R) {
		return(Aw[month][iNS]*(lat*R2D - 30.0)/30.0); 
	}
	else {
		return(Aw[month][iNS]*(90.0 - lat*R2D)/30.0);
	}
}

double AntennaGain08(struct PathData path, struct Antenna Ant, int direction, double * elevation) {


	/*

		AntennaGain08() - Determines the largest antenna gain in the range 0 to 8 degrees elevation. 
			At present June 2013. There is a minimum elevation angle in the long model that is fixed 
			at 3 degrees. Per Dambolt and Suessmann this routine which finds the maximum gain between
			0 and 8 degrees should not be altered under the assumption that it is improbable that the 
			antenna gain determined by the proceedure would be less than 3 degrees. 

			INPUT
				struct PathData path
				struct Antenna Ant
				int direction

			OUTPUT
				largest antenna gain in the range 0 to 8 degrees elevation

			SUBROUTINES
				AntennaGain()

	*/


	// The structure Antenna Ant is used to tell the subroutine which antenna to calculate.

	double G, Gmax;
	double delta; // Elevation angle

	int i;

	// Find the largest value of transmitting gain at the required azimuth in the elevation 
	// range 0 to 8 degrees
	Gmax = TINYDB;
	for(i=0; i<9; i++) {
		delta = ((double)i)*D2R;
		G = AntennaGain(path, Ant, delta, direction);
		if( G > Gmax) {
			Gmax = G;
			*elevation = i*D2R;
		}
    }

    return Gmax;
}

// Testing
void PrintControlPointData(struct ControlPt CP, int i, int j) {

	double tz, ltime;

	printf("**********************************************************\n");
	if(i == RdM2) {
		printf("*            Control Point @ R - dM/2 (Hour %d)                    *\n", j);
	}
	else if(i == TdM2) {
		printf("*            Control Point @ T + dM/2 (Hour %d)                    *\n", j);
	}
	else {
		printf("*            Penetration Point - %d (Hour %d)                    *\n", i, j);
	}
    printf("**********************************************************\n");
	printf("\tLatitude\t=\t% 5.3lf\t(% 5.3lf)\t[%d %d %d]\n", CP.L.lat, CP.L.lat*R2D, degrees(CP.L.lat*R2D), minutes(CP.L.lat*R2D), seconds(CP.L.lat*R2D));
	printf("\tLongitude\t=\t% 5.3lf\t(% 5.3lf)\t[%d %d %d]\n", CP.L.lng, CP.L.lng*R2D, degrees(CP.L.lng*R2D), minutes(CP.L.lng*R2D), seconds(CP.L.lng*R2D));
	printf("\tdistance = % 5.3lf\n", CP.distance);
	printf("\tMagnetic dip (100 km)  = % 5.3lf (deg)\n", CP.dip[HR100km]*R2D); 
	printf("\tGyrofrequency (100 km) = % 5.3lf (MHz)\n", CP.fH[HR100km]);
	printf("\tMagnetic dip (300 km)  = % 5.3lf (deg)\n", CP.dip[HR300km]*R2D); 
	printf("\tGyrofrequency (300 km) = % 5.3lf (MHz)\n", CP.fH[HR300km]); 
	printf("\tM(3000)F2 = % 5.3lf\n", CP.M3kF2);
	printf("\tfoE   = % 5.3lf (MHz)\n", CP.foE);
	printf("\tfoF2  = % 5.3lf (MHz)\n", CP.foF2);
	printf("\treflection height  = % 5.3lf (km)\n", CP.hr);
	printf("\tsolar zenith angle = % 5.3lf (deg)\n", CP.Sun.sza*R2D); 
	printf("\tsolar declination  = % 5.3lf (deg)\n", CP.Sun.decl*R2D); 
	printf("\tsolar hour angle   = % 5.3lf (deg)\n", CP.Sun.ha*R2D); 
	printf("\tequation of time   = % 5.3lf (minutes)\n", CP.Sun.eot); 
	// Determine the tz of the control point
	tz = (int)(CP.L.lng/(15.0*D2R));
	ltime = fmod(CP.Sun.lsr+tz+24, 24);
	printf("\tlocal sunrise      = % 02d:%02d (UTC) % 02d:%02d (Local)\n", hrs(CP.Sun.lsr), mns(CP.Sun.lsr), hrs(ltime), mns(ltime)); 
	ltime = fmod(CP.Sun.lsn+tz+24, 24);
	printf("\tlocal solar noon   = % 02d:%02d (UTC) % 02d:%02d (Local)\n", hrs(CP.Sun.lsn), mns(CP.Sun.lsn), hrs(ltime), mns(ltime)); 
	ltime = fmod(CP.Sun.lss+tz+24, 24);
	printf("\tlocal sunset       = % 02d:%02d (UTC) % 02d:%02d (Local)\n", hrs(CP.Sun.lss), mns(CP.Sun.lss), hrs(ltime), mns(ltime)); 
	ltime = fmod(CP.ltime+tz+24, 24);
	printf("\tlocal time         = % 02d:%02d (UTC) % 02d:%02d (Local)\n", hrs(CP.ltime), mns(CP.ltime), hrs(ltime), mns(ltime));
}

// Testing

void CopyCP(struct ControlPt thisCP, struct ControlPt *thatCP) {
	/*

	  CopyCp() Copies one control point to another for the situation where they can not point
	        to the same memeory location.
	 
	 		INPUT
	 			struct ControlPt thisCP - The source control point that contains the information to copy 
	 			struct ControlPt thatCP - The target conntrol point that will be coppied to. 
	 
	 		OUTPUT
	 			None 
	 
			SUBROUTINES
				None

	 */

	thatCP->dip[0] = thisCP.dip[0];
	thatCP->dip[1] = thisCP.dip[1];
	
	thatCP->distance = thisCP.distance;
	
	thatCP->fH[0] = thisCP.fH[0];
	thatCP->fH[1] = thisCP.fH[1];
	
	thatCP->foE = thisCP.foE;
	thatCP->foF2 = thisCP.foF2;
	thatCP->hr = thisCP.hr;
	thatCP->L.lat = thisCP.L.lat;
	thatCP->L.lng = thisCP.L.lng;
	thatCP->ltime = thisCP.ltime;
	thatCP->M3kF2 = thisCP.M3kF2;
	
	thatCP->Sun.sza = thisCP.Sun.sza;
	thatCP->Sun.sha = thisCP.Sun.sha;
	thatCP->Sun.decl = thisCP.Sun.decl;
	thatCP->Sun.eot = thisCP.Sun.eot;
	thatCP->Sun.ha = thisCP.Sun.ha;
	thatCP->Sun.lsn = thisCP.Sun.lsn;
	thatCP->Sun.lsr = thisCP.Sun.lsr;
	thatCP->Sun.lss = thisCP.Sun.lss;

	thatCP->x = thisCP.x;

	return;

}


// Testing
int degrees(double coord) { // Returns the degrees of coordinates
	return (int)coord;
}

int minutes(double coord) { // Returns the minutes of coordinates
	return abs((int)((coord - (int)coord)*60.0));
}

int seconds(double coord) { // Returns the seconds of coordinates
	return abs((int)((((coord - (int)coord)*60.0) - (int)((coord - (int)coord)*60.0))*60.0));
}

int hrs(double time) {
	return (int)time % 24;
}

int mns(double time) {
	return abs((int)((time - (int)time)*60.0));
}

