#include <stdio.h>
#include <stdlib.h>
#include <math.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End local includes

void CalculateCPParameters( struct PathData *path, struct ControlPt *here) {

	/*
	 
	  CalculateCPParameters() finds the ionosheric parameters foF2 and M3kF2 for the high (SSN = 100) and low (SSN = 0), the gyrofrequency and magnetic dip at 100 and 300 km 
	 		and the solar parameters for the control point. The ionospheric parameters are determined by the bi-linear interpolation method in P.1144-3 (2001) 
	 		and linear interpolation by the desired SSN (Found in path->SSN - the user selected SSN) the routine determines the foF2 and M3F2 at the point of interest. 
	 		This routine further determines foE at the point of interest. 
	 
	 		INPUT
	 			struct PathData *path
	 			struct ControlPt *here - This is a pointer to the control point of interest.
	 
	 		OUTPUT
	 			There are four subroutines in this program that calculate the parameters for 
	 			the control point. Which subroutine calulates which parameter is summarized below
	 
	 			via IonosphericParameters()
	 				here->foF2 - Critical frequency of the F2 layer
	 				here->M3kF2 - Critical frequency of the F2 layer for 3000 km
	 			via FindFoE()
	 				here->foE - Critical frequency fo the E layer
	 				here->ltime - Local time
	 			via magfit()
	 				here->dip[2] - Magnetic dip calculated at 100 and 300 km
	 				here->fH[2] - Gyrofreqency calculated at 100 and 300 km
	 			via SolarParameters() 
	 				here->Sun.ha - Hour angle (radians)
	 				here->Sun.sha -  Sunrise/Sunset hour angle (radians)
	 				here->Sun.sza - Solar zenith angle (radians)
	  				here->Sun.decl - Solar declination (radians)
	  				here->Sun.eot- Equation of time (minutes)
	 				here->Sun.lsr - local sunrise (hours)
	 				here->Sun.lsn - local solar noon (hours)
	 				here->Sun.lss - local sunset (hours)
	 
	 		SUBROUTINES
				IonosphericParameters()
				SolarParameters()
				FindfoE()
				magfit()

	 */

	/*
	 * Find the ionospheric parameters foF2 and M3kF2 at the control point here.
	 * If here is not on a grid point then use bilinear interpolation.
	 */
	IonosphericParameters(here, path->foF2, path->M3kF2, path->hour, path->SSN);

	/*
	 * Calculate the solar parameters. 
	 * These parametres are used in the MedianSkywaveFieldStrengthLong() calculation. Because this 
	 * routine determines the control point parameters for all methods, find the solar parameters now
	 * before entering the conditional loop for the foE calculation.
	 */
	// Find the solar parameters for the control point.
	SolarParameters(here, path->month, (double)path->hour);
		
	/*
	 * Calculate foE by the method outlined in P.1239-2. 
	 */
	FindfoE(here, path->month, path->hour, path->SSN);

	/* 
	 * At each control point the gyrofrequency and magnetic dip must also be calculated.
	 * The calculation is done at two heights: 
	 *		height = 300 km is for the determination of MUF and the long path (> 9000 km).
     *      height = 100 km is used in the determination of absorption on the int paths (< 9000 km).
	 */

	magfit(here, 100.00);
	magfit(here, 300.00);

	return;
}

void IonosphericParameters(struct ControlPt *here, float ****foF2, float ****M3kF2, int hour, int SSN){

	/*
	 
	  IonosphericParameters() - Finds foF2 and M(3000)F2 by Bilinear Interpolation.
	 
	  	INPUTS
	 		struct ControlPt *here,
	  		double ****foF2
	 		double ****M3kF2
	 		int hour
	 		int SSN
	 
	 	OUTPUT
	 		here->foF2
	 		here->M3KF2

		SUBROUTINES
			BilinearInterpolation()

 		I am indebted to Peter Suessman for the use of his program, iongrid ver 1.80, which was used extensively
 		to verify the method in this routine. 

	*/
	 	
	struct Neighbor {
		struct Location L; // the physical location of the point
		double foF2[2], M3kF2[2]; // the values for the ionospheric parameters at the point
		int j, k; // the j (lng) and k (lat) indexes into the gridpoint map. 
	} UL, UR, LR, LL; // UL = upper left, UR = upper right, LR = lower right and LL = lower left
	
	struct Neighbor ned; //  
    
	// Temporary Varibles
	int m; // Temp the SSN index
	int n; // Temp hours index

	
	
	int		lng, lat;	// gridmap maxima

	// For the gridpoint maps at an increment of 1.5 degrees in lat and long
	int zerolat = 60;	// The max latitude is 121 
	int zerolng = 120;	// The max longitude is 241
	double inc;			// Increment for the gridpoint maps (1.5 * pi) / 180 = 0.0261799388
	double fracj, frack;// Fractional "column" j and fractional "row" k

	// This routine is dependent on the 1.5 degree increment
	inc = 1.5*D2R;

	lng = 241;	// 241 Longitudes at 1.5 degree increments
	lat = 121;	// 121 lngitudes at 1.5 degree increments
	 
	/*
	 * Find the neighborhood around the point of interest
	 * The neighbors are determined differently for each quadrant so that the neighbors are in the correct order relative to the 
	 * array indices. This is done to simplify the interpolation code. There are no quadrants in the foF2 and M3kF2 data array. In the 
	 * foF2 and M3kF2 array the 0,0 point is the southwest corner and the indicies increase north and east. 
	 * First determine the quadrant
	 */
	if( here->L.lat >= 0.0 ) {
		if (here->L.lng >= 0.0) { // NE quad
			LL.k = zerolat+(int)(here->L.lat / inc); // North is positive
			LL.j = zerolng+(int)(here->L.lng / inc); // East is positive
			LR.k = LL.k;
			LR.j = LL.j+1;
			UR.k = LL.k+1;
			UR.j = LL.j+1;
			UL.k = LL.k+1;
			UL.j = LL.j;
			// Check the rollover conditions - corners and edges
			if ( LL.j != lng-1 ) { // not on E edge
				if ( LL.k != lat-1 ) { // not on N edge
					// don't do anything
				}
				else { // N edge
					// fix the N rollover - no rollover N to S
					UR.k = LL.k;
					UL.k = LL.k;
				}
            }
			else { // E edge
				if ( LL.k != lat-1 ) { // not on N edge
					// fix the E rollover - rollover E to W
					LR.j = 0;
					UR.j = 0;
				}
				else { // NE corner
					// Collapse to 1D
					LR.k = LL.k;
					LR.j = 0;
					UR.k = LL.k;
					UR.j = 0;
					UL.k = LL.k;
					UL.j = LL.j;
				}
            }
        }
		else {	// NW quad
			LR.k = zerolat+(int)(here->L.lat / inc); // North is positive
			LR.j = zerolng+(int)(here->L.lng / inc); // West is negative	
			LL.k = LR.k;
			LL.j = LR.j-1;
			UL.k = LR.k+1;
			UL.j = LR.j-1;
			UR.k = LR.k+1;
			UR.j = LR.j;
			// Check the rollover conditions - corners and edges
			if ( LR.j != 0.0 ) { // not on W edge
				if ( LR.k != lat-1 ) { // not on N edge
					// don't do anything
				}
				else { // N edge
					// fix the N rollover - no rollover N to S
					UR.k = LR.k;
					UL.k = LR.k;
				}
            }
			else { // W edge
				if ( LR.k != lat-1 ) { // not on N edge
					// fix the W rollover - rollover W to E
					LL.j = lng-1;
					UL.j = lng-1;
				}
				else { // NW corner
					// Collapse to 1D
					LL.k = LR.k;
					LL.j = lng-1;
					UR.k = LR.k;
					UR.j = LR.j;
					UL.k = LR.k;
					UL.j = lng-1;
				}
            }
        }
    }
	else { 
		if (here->L.lng >= 0.0) { // SE quad
			UL.k = zerolat+(int)(here->L.lat / inc); // South is negative
			UL.j = zerolng+(int)(here->L.lng / inc); // East is positive
			UR.k = UL.k;
			UR.j = UL.j+1;
			LL.k = UL.k-1;
			LL.j = UL.j;
			LR.k = UL.k-1;
			LR.j = UL.j+1;
			// Check the rollover conditions - corners and edges
			if ( UL.j != lng-1 ) { // not on E edge
				if ( UL.k != 0 ) { // not on SE corner
					// don't do anything
				}
				else { // S edge
					// fix the S rollover - no rollover S to N
					LL.k = UL.k;
					LR.k = UL.k;
				}
            }
			else { // E edge
				if ( UL.k != 0 ) { // not on SE corner
					// fix the E rollover - rollover E to W
					LR.j = 0;
					UR.j = 0;
				}
				else { // SE corner
					// Collapse to 1D
					LR.k = UL.k;
					LR.j = 0;
					UR.k = UL.k;
					UR.j = 0;
					LL.k = UL.k;
					LL.j = UL.j;
				}
            }
        }
		else { // SW quad
			UR.k = zerolat+(int)(here->L.lat / inc); // South is negative
			UR.j = zerolng+(int)(here->L.lng / inc); // West is negative
			UL.k = UR.k;
			UL.j = UR.j-1;
			LL.k = UR.k-1;
			LL.j = UR.j-1;
			LR.k = UR.k-1;
			LR.j = UR.j;
			// Check the rollover conditions - corners and edges
			if ( UR.j != 0.0 ) { // not on W edge
				if ( UR.k != 0 ) { // not on SW corner
					// don't do anything
				}
				else { // S edge
					// fix the N rollover - no rollover N to S
					LR.k = UR.k;
					LL.k = UR.k;
				}
            }
			else { // W edge
				if ( UR.k != 0 ) { // not on SW corner
					// fix the W rollover - rollover W to E
					LL.j = lng-1;
					UL.j = lng-1;
				}
				else { // SW corner
					// Collapse to 1D
					LR.k = UR.k;
					LR.j = UR.j;
					LL.k = UR.k;
					LL.j = lng-1;
					UL.k = UR.k;
					UL.j = lng-1;
				}
            }
        }
    }

    // Determine the lat and lng in degrees
	UL.L.lat = UL.k*inc - PI/2.0;
	UL.L.lng = UL.j*inc - PI;
	LL.L.lat = LL.k*inc - PI/2.0;
	LL.L.lng = LL.j*inc - PI;
	UR.L.lat = UR.k*inc - PI/2.0;
	UR.L.lng = UR.j*inc - PI;
	LR.L.lat = LR.k*inc - PI/2.0;
	LR.L.lng = LR.j*inc - PI;

	/*
	 * At this point you have the neighborhood around the point now you can populate the foF2 and
	 * M3kF2 for each of the adjacent points
	 */
	n = hour;

	for(m=0; m<2; m++) { // SSN
		// Upper Left
		UL.foF2[m] = foF2[n][UL.j][UL.k][m];
		UL.M3kF2[m] = M3kF2[n][UL.j][UL.k][m];
		// Upper Right
		UR.foF2[m] = foF2[n][UR.j][UR.k][m];
		UR.M3kF2[m] = M3kF2[n][UR.j][UR.k][m];
		// Lower Left
		LL.foF2[m] = foF2[n][LL.j][LL.k][m];
		LL.M3kF2[m] = M3kF2[n][LL.j][LL.k][m];
		// Lower Right
		LR.foF2[m] = foF2[n][LR.j][LR.k][m];
		LR.M3kF2[m] = M3kF2[n][LR.j][LR.k][m];
	}

    // Now you are ready to interpolate the value at the point of interest
	// determine the fractional "column" j and fractional "row" k for the bilinear interpolation calculation
	frack =  fabs(here->L.lat/inc) - (int)fabs(here->L.lat/inc); // Fractional row distance
	fracj =  fabs(here->L.lng/inc) - (int)fabs(here->L.lng/inc); // Fractional column distance
	for(m=0; m<2; m++) {
		ned.foF2[m] = BilinearInterpolation(LL.foF2[m], LR.foF2[m], UL.foF2[m], UR.foF2[m], frack, fracj);
		ned.M3kF2[m] = BilinearInterpolation(LL.M3kF2[m], LR.M3kF2[m], UL.M3kF2[m], UR.M3kF2[m], frack, fracj);
	}

    // End of calculation for foF2 and M3kF2

	/*
	 * Now interpolate by the SSN. Note the SSN maximum has been restricted to a maximm of 160 ITU-R P.533-12.
	 * "For most purposes it is adequate to assume a linear relationship with R12 for both foF2 and M(3000)F2." 
	 * ITU-R P.1239-2 (10-2009)
	 * Note the index on foF2 and M3kF2 in the neighbor structure is for the SSN = 0 (index = 0) and SSN = 100 (index = 1)
	 */
	SSN = min(SSN, MAXSSN);
	here->foF2 = (ned.foF2[1]*SSN + ned.foF2[0]*(100.0 - SSN))/100.0;
	here->M3kF2 = (ned.M3kF2[1]*SSN + ned.M3kF2[0]*(100.0 - SSN))/100.0;

	// End of calculation for foF2 and M3kF2

	return;

}


void FindfoE(struct ControlPt *here, int month, int hour, int SSN) {

	/* 
	 
	  FindFoE() - Determines the critical frequency of the E-layer, foE, by the method in 
	  		ITU-R P.1239. This routine assumes that the solar parameters have been calculated 
	 		for the control point before execution.
	  		
	 		INPUT
	 			struct ControlPt *here
	 			int month
	 			int hour
	 			int SSN
	 
	 		OUTPUT
	 			here->foE critical frequency of the E-layer determined for the control point here
	 
	 		SUBROUTINES
				None

	 */
	 
	// Temps for the calculation of foE
	double A;			// solar activity factor
	double B;			// seasonal factor
	double phi;			// monthly mean 10.7 cm solar radio flux
	double M, N;

	//warning C4189: 'day15th': local variable is initialized but not referenced
	//int day15th[12] = {15,46,74,105,135,166,196,227,258,288,319,349}; // 15th day of each month;
	
	double C;			// main latitude factor
	double X, Y;		// temps
	double D;			// time-of-day factor
	double dsza;		// delta solar zenith angle
	double p, h;		// coefficients
	// End of Temporary Variables

	// Restrict the ssn to 160
	SSN = min(SSN, MAXSSN);
	 
	/*
	 * Now find the foE for the control point "here"
	 * There are four parts to the calculation 
	 *		A = solar activity factor
	 *		B = seasonal factor
	 *		C = main latitude factor
	 *		D = time-of-day factor
	 */
	/* 
	 * Calculation for A : solar activity factor
	 * First find phi sub 12 (phi) by eqn (2) in P.1239-2 (2009)
	 */
	phi = 63.7 + 0.728*SSN + 0.00089*pow(SSN, 2);
	A = 1.0 + 0.0094*(phi - 66.0); 

	/*
	 * Calculation for B : seasonal factor
	 */

	if(fabs(here->L.lat) < 32.0*D2R) {
		M = - 1.93 + 1.92*cos(here->L.lat);
	}
	else { // fabs(here->L.lat) >= 32*D2R
		M = 0.11 - 0.49*cos(here->L.lat);
	}

    if(fabs(here->L.lat - here->Sun.decl) < 80.0*D2R) {
		N = here->L.lat - here->Sun.decl;
	}
	else {
		N = 80.0*D2R;
	}

    B = pow(cos(N), M);

	/* 
	 * Calculation for C : main latitude factor
	 */

	if(fabs(here->L.lat) < 32.0*D2R) { 
		X = 23.0;
		Y = 116.0;
	}
	else { // fabs(here->L.lat) >= 32*D2R
		X = 92.0;
		Y = 35.0;
	}
    C = X + Y*cos(here->L.lat);

	/*
	 * Calculation of D : time-of-day factor
	 */

	// In each case in determining D from the solar zentih angle (here->sza) the p exponent is determined the same
	if(fabs(here->L.lat) <= 12.0*D2R) {		
		p = 1.31;
	}
	else { // fabs(here->L.lat) > 12*D2R
		p = 1.2;
	}

    // Now calculate D conditional on the solar zenith angle (here->sza)
	if(here->Sun.sza <= 73.0*D2R) {
		D = pow(cos(here->Sun.sza),p);
	}
	else if ((here->Sun.sza > 73.0*D2R) && (here->Sun.sza  < PI/2.0)) { // Twilight is 90 degrees
		dsza = 6.27e-13*pow((here->Sun.sza*R2D - 50.0),8.0)*D2R;
		D = pow((cos(here->Sun.sza - dsza)),p);
	}
	else { // (here->sza >= 90.0*D2R )
		// In this case local sunset and sunrise must be known.
		// Find h the number of hours after sunset
		hour = (hour + 1) % 24; // Adjust time and roll over
		if((here->Sun.lss >= here->Sun.lsr) && (hour >= here->Sun.lss) && (hour >= here->Sun.lsr)) {
			h = hour - here->Sun.lss;
		}
		else if((here->Sun.lss < here->Sun.lsr) && (hour >= here->Sun.lss) && (hour < here->Sun.lsr)) {
			h = hour - here->Sun.lss;
		}
		else if((here->Sun.lss >= here->Sun.lsr) && (hour < here->Sun.lss) && (hour < here->Sun.lsr)) {
			h = 24.0 - here->Sun.lss + hour;
		}
		else {
			h = 0.0;
		}

        // If it is night determine if here is in a polar region and during a period of polar winter
		// The Norwegian territory of Svalbad is known to experiences a civil polar night lasting 
		// from about 11 November until 30 January. 
		// Civil Polar night is when the sun is 6 degrees below the horizon. 
		// Because the arctic circle is at 66.5622 degrees, civil twilight would be 72.5622 degrees. Although civil twilight is chosen here,
		// the precise angle of ionospheric twilight is open to debate. Half the month of November to the 1st of February experiences polar 
		// night. This program works on median months, so polar night will be defined as ...
		if(((here->L.lat > 72.5622*D2R) && ((month == NOV) || (month == DEC) || (month == JAN))) 
										||
		   ((here->L.lat < 72.5622*D2R) && ((month == MAY) || (month == JUN) || (month == JUL)))) {
			// Northern hemisphere polar winter || Southern hemisphere polar winter
			D = pow(0.072,p)*exp(25.2 - 0.28*here->Sun.sza*R2D);
		}
		else {
			// Choose the larger of the two calculations
			D = max((pow(0.072,p)*exp(-1.4*h)), (pow(0.072,p)*exp(25.2 - 0.28*here->Sun.sza*R2D)));
		}
    }

    // Choose the larger of the foE calculations
	here->foE = max(pow(A*B*C*D, 0.25), pow(0.004*pow((1.0 + 0.021*phi), 2) , 0.25));
	
	return;
}

void SolarParameters(struct ControlPt *here, int month, double hour) {

	/*
	 
	 	SolarParameters() - Calculate the solar parmeters at the control point for the given 
	 		time and month.
	 
	 		INPUT
	 			struct ControlPt *here - The control point of interest
	 			int month - Month index
	 			double hour - Decimal hours
	  
	 		OUTPUT
	 			here->Sun.ha - Hour angle (radians)
	 			here->Sun.sha -  Sunrise/Sunset hour angle (radians)
	 			here->Sun.sza - Solar zenith angle (radians)
	  			here->Sun.decl - Solar declination (radians)
	  			here->Sun.eot- Equation of time (minutes)
	 			here->Sun.lsr - local sunrise (hours)
	 			here->Sun.lsn - local solar noon (hours)
	 			here->Sun.lss - local sunset (hours)

			SUBROUTINES
				None

		Thanks to the following references
		See www.analemma.com/Pages/framesPage.html
		See holbert.faculty.asu.edu/eee463/SolarCalcs.pdf
		Although W is + and E is - and the time zones are also reversed 
		See www.esrl.noaa.gov/gmd/grad/solcalc/solareqns.PDF
	 
	 */

	double cosphi;	// cosine of the solar zenith angle
	double tst;		// True solar time
	double toffset;
	double ltime;	// Local time 
	double tzone;	// Time zone
	double lambda;
	double epsilon;	
	double nu;
	double beta;
	double A = 0.98565327;		// Average angle per day
	double B = 3.98891967;		// Minutes pre degree of Earth's rotation
	double S = sin(23.45*D2R);	// Earth's tile sine
	double C = cos(23.45*D2R);	// Earth's tile cosine
	double V = 78.746118*D2R;		// Value of nu on March 21st

	int day;
	double D;

	// The day of the year (doty) array allows us to determine the day count of the day of interest
	int doty[12] = { 0, 31, 59, 90, 120, 152, 181, 212, 243, 273, 304, 334 }; 

	// Determine the local time, hours, minutes, seconds and time zone
	ltime = hour + (int)(here->L.lng/(15.0*D2R)); // Local time 
	tzone = (int)(here->L.lng/(15.0*D2R)); // hours
	
	// At present this code only works for the 15th day of the month
	// If this changes a day field should be added to the path structure
	// and passed into this routine
	day = 15;

	D = doty[month] + day + hour/24.0;

	// Calculate the Equation-of-Time
	// First find the time due to the elliptic orbit of the Earth
	// The average day is 360 degrees / 365.25 days a year assuming a circular orbit equals 0.985653
	// Assume that the perihelion ( The Earth is closest to the sun ) is on January 2nd.
	// So in D days of the year the earth moves through lambda degrees
	lambda = A*D2R*(D - 2);

	// Determine the arc length due to an elliptical orbit
	// ( 360 degrees / PI ) * 0.016713 the shape factor of the elliptic equals 1.915169
	nu = lambda + 1.915169*D2R*sin(lambda);

	// Find the angles associated with the tile of the Earth
	// epsilon is the mean sun angle of the Earth after N - 80 days
	epsilon = A*D2R*(D - 80);

	// epsilon is +- PI/2
	if(epsilon >= 270*D2R) {
		epsilon -= 2.0*PI;
	}
	else if(epsilon >= 90*D2R) {
		epsilon -= PI;
	}

    // The angle of the true sun is beta
	beta = atan(C*tan(epsilon));

	// Equation of Time = tilt effect + eclliptic effect
	// Where 0.398892 is the minutes per degree of Earth's rotation 
	// 1440 minutes per day /361 degrees per day 
	here->Sun.eot = B*((epsilon - beta) + (lambda - nu))*R2D;

	// Solar declination in radians
	here->Sun.decl =  asin(S*sin((sin(A*(D-2)*D2R)*0.016713 + A*(D-2)*D2R) - V));
	
	// Find the hour angle which can be found from the solar time corrected for the local longitude and the eot
	toffset = (((here->L.lng/(15.0*D2R)) - tzone)*60.0 + here->Sun.eot); // minutes

	tst = ltime*60 + toffset; // Apparent/Local/True solar time in minutes

	here->Sun.ha = ((tst/4.0) - 180)*D2R; // radians
	
	// Hour angle at sunrise and sunset in radians
	here->Sun.sha = acos((cos(90.833*(D2R))/(cos(here->L.lat)*cos(here->Sun.decl))) - (tan(here->L.lat)*tan(here->Sun.decl)));

	// The cosine of the solar zenith angle can be found
	cosphi = (sin(here->L.lat)*sin(here->Sun.decl)) + (cos(here->L.lat)*cos(here->Sun.decl)*cos(here->Sun.ha));

	 /* (watch out for the roundoff errors) */
    if ( fabs (cosphi) > 1.0 ) {
        if ( cosphi >= 0.0 )
            cosphi =  1.0;
        else
            cosphi = -1.0;
    }

	here->Sun.sza = acos(cosphi); // Solar zenith angle which will be positive even in the for southern latitudes

	// Switch the sign of the longitude for the time calculation
	// Local Sunrise relative to UTC in fractional hours
	here->Sun.lsr = (720.0 + (-here->L.lng - here->Sun.sha)*R2D*4.0 - here->Sun.eot)/60.0;
	
	// Local Sunset relative to UTC in fractional hours
	here->Sun.lss = (720.0 + (-here->L.lng + here->Sun.sha)*R2D*4.0 - here->Sun.eot)/60.0;

	// Local Solar noon relative to UTC in fractional hours
	here->Sun.lsn = (720.0 + (-here->L.lng)*R2D*4.0 - here->Sun.eot)/60.0;

	// Roll over the times. Note: add 24 because for the fmod(x, 24) x might be negative 
	here->Sun.lsr = fmod(here->Sun.lsr + 24.0, 24.0); 
	here->Sun.lss = fmod(here->Sun.lss + 24.0, 24.0);
	here->Sun.lsn = fmod(here->Sun.lsn + 24.0, 24.0);

	// Store the UTC time to here structure
	here->ltime = hour;

	return;

}

double BilinearInterpolation(double LL, double LR, double UL, double UR, double r, double c) {

	/*

	 BilinearInterpolation() - Interpolates a value given the Neighbors by 
			the method in ITU-R P.1144-5 (10/09)
	 
	 		INPUT	
	 			double LL - Lower left neighbor
	 			double LR - Lower right neighbor
	 			double UL - Upper left neighbor
	 			double UR - Upper right neighbor
	 			double r - Fraction row
	 			double c - Fractional column
	 
	 		OUTPUT
	 			returns the interpolated value

			SUBROUTINES
				None
	 
	 */
	
	return	LL*((1.0 - r)*(1.0 - c)) +
			UL*((r)*(1.0 - c)) +
			LR*((1.0 - r)*(c)) +
			UR*((r)*(c));

}



