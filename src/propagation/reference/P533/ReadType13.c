#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End local includes


DLLEXPORT void SetAntennaPatternVal(struct PathData * path, int TXorRX, int azimuth, int elevation, double value) {
	/*
	SetAntennaPatternVal() - Set a value in an antenna pattern. This is especially useful for
		when you're calling this from managed code.
		
	If the pattern datastructure has not been initialied before calling this 
	function, a single frequency data structure will be allocated.

	INPUT
		struct PathData
		int TXorRX
		int azimuth
		int elevation
		double value

	*/
	int frequencyIndex = 0;
	
	//If TXorRX == 0 set the transmitter's antenna pattern value.
	if (TXorRX == 0){
		if (path->A_tx.pattern == NULL) {
			AllocateAntennaMemory(&path->A_tx, 1, 360, 91);
			path->A_tx.freqs[0] = 0.0;
		}
		path->A_tx.pattern[frequencyIndex][azimuth][elevation] = value;
	}
	//At the moment anything but 0 is the RX.
	else {
		if (path->A_rx.pattern == NULL) {
			AllocateAntennaMemory(&path->A_rx, 1, 360, 91);
			path->A_rx.freqs[0] = 0.0;
		}
		path->A_rx.pattern[frequencyIndex][azimuth][elevation] = value;
	}
}

int ReadType11(struct Antenna *Ant, FILE *fp, int silent) {

	#ifdef __GNUC__
	#pragma GCC diagnostic push
	#pragma GCC diagnostic ignored "-Wunused-result"
	#endif

	char line[256];			// Read input line
	char instr[256];		// String temp

	const int freqn = 1;		// 1-30Mhz in 1MHz intervals, as per standard voacap files.
	const int azin = 360;		// Fixed number of azimuths at 1-degree intervals
	const int elen = 91;		// Fixed number of elevations at 1-degree intervals

	double MaxG = 0.0;			// Maximum gain

	int j;									// Loop counter
	
	AllocateAntennaMemory(Ant, freqn, azin, elen);

	/*
   * Read a VOACAP antenna pattern Type 14 file
	 * Typically, the whole file will look like the following (The file contains a 
   * single gain block).
	 *
	 * SWWhip for REC533  :Sample type 11  Gain Table versus Elevation Angle
   *  3     3 parameters
   *   0.00  [ 1] Max Gain dBi..:
   *   11    [ 2] Antenna Type..: 91 values gain in elevation angle follows
   *  -4.8   [ 3] Efficiency (for IONCAP)
   *   -20.0   -14.0   -11.0    -7.6    -5.4    -4.0    -3.2    -2.5    -1.8    -1.6
   *    -1.3    -1.1     -.9     -.6     -.5     -.4     -.2     -.1      .0      .0
   *      .0      .0      .0      .0      .0      .0     -.1     -.2     -.2     -.2
   *     -.3     -.3     -.4     -.5     -.5     -.6     -.7     -.8     -.8     -.9
   *    -1.0    -1.1    -1.2    -1.4    -1.5    -1.6    -1.8    -1.9    -2.0    -2.1
   *    -2.3    -2.4    -2.6    -2.7    -2.9    -3.1    -3.2    -3.4    -3.6    -3.7
   *    -3.9    -4.2    -4.4    -4.7    -5.0    -5.4    -5.7    -6.0    -6.4    -6.7
   *    -7.1    -7.5    -7.9    -8.4    -8.8    -9.3    -9.8   -10.4   -10.9   -11.4
   *   -12.0   -12.6   -13.2   -13.9   -14.6   -15.4   -16.2   -17.2   -18.2   -19.6
   *   -21.9
	 */

	if (fp == NULL) {
		return RTN_ERRCANTOPENANTFILE;
	}

    // The first line is the name of the antenna.
	// fgets will return a string that has a trailing "\n" which needs to be stripped off
	/*
	if (fgets(line, sizeof(line), fp) != NULL) {
		size_t len = strlen(line);
		if (len > 0 && line[len - 1] == '\n') {
			line[--len] = '\0';
		};
	};*/
	if (fgets(line, sizeof(line), fp) != NULL) {
		line[strcspn(line, "\n")] = '\0';
	}

    strcpy(Ant->Name, line);	// Store it to the path structure.

	// User feedback
	if(silent != TRUE) {
		printf("ReadType11: Reading antenna %.35s\n", Ant->Name);
	}

    fgets(line, sizeof(line), fp);		// Number of parameters

	// The next lines are parameters
	fgets(line, sizeof(line), fp);		// Max Gain
	sscanf(line, " %lf %s\n", &MaxG, instr);
	fgets(line, sizeof(line), fp);		// Antenna type (ignored)
	fgets(line, sizeof(line), fp); 		// Efficiency (ignored)

	Ant->freqs[0] = 0;
  
  for(j=0; j<90; j += 10) {
		fgets(line, sizeof(line), fp);
		sscanf(line, " %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf",
			&Ant->pattern[0][0][j],   &Ant->pattern[0][0][j+1], &Ant->pattern[0][0][j+2], &Ant->pattern[0][0][j+3], &Ant->pattern[0][0][j+4],
			&Ant->pattern[0][0][j+5], &Ant->pattern[0][0][j+6], &Ant->pattern[0][0][j+7], &Ant->pattern[0][0][j+8], &Ant->pattern[0][0][j+9]);
	}
    fgets(line, sizeof(line), fp);
	sscanf(line, " %lf\n", &Ant->pattern[0][0][90]);

	// If max gain != 0.0 add it to the values read in from the table.
	if (MaxG != 0.0) {
		for(j=0; j<elen; j += 1) {
			Ant->pattern[0][0][j] += MaxG;
		}
    }

    // Copy the array of elevation data to the rest of the data structure.
	for (j=1; j<azin; j++) {
		memcpy(Ant->pattern[0][j], Ant->pattern[0][0], elen * sizeof(double));
	}
	
	return RTN_READANTENNAPATTERNSOK;

	#ifdef __GNUC__
	#pragma GCC diagnostic pop
	#endif
}


int ReadType13(struct Antenna *Ant, FILE * fp, double bearing, int silent) {

	#ifdef __GNUC__
	#pragma GCC diagnostic push
	#pragma GCC diagnostic ignored "-Wunused-result"
	#endif

	char line[256];		// Read input line
	char instr[256];	// String temp

	int i, j;					// Loop counters
	int freqn, azin, elen;		// Number of freqs, elevations and azimuths
	int iazi;					// Offset azimuth counter
	int iMBOS;				// Integer offset of the main beam azimuth
	int iI = 0;				// Temp

	double MaxG = 0.0;	// Maximum gain

	azin = 360;			// Fixed number of azimuths at 1-degree intervals
	elen = 91;			// Fixed number of elevations at 1-degree intervals
	freqn = 1;      // Assume data for a single frequency block

	AllocateAntennaMemory(Ant, freqn, azin, elen);

	// Determine the azimuth direction that the antenna is pointing to find the index offset.
	// Ideally the antenna pattern could be rotated to any position and then every gain value in the
	// pattern would be interpolated. In this implementation the pattern will be rotated to the nearest
	// integer azimuth degree. This approximate method was chosen because of the error of having the
	// pattern off by maximally +- 1/2 degree is considered to be minimal.
	iMBOS = (int)(bearing*R2D);

	// Read a VOACAP antenna pattern Type 13 file
	/*
	 * Typically, the header will look like the following:
	 *
	 * DeMinco Antenna ITS 2010 (ASCUH15)       :Sample type 13  360-degree gain table
	 * 4     4 parameters
	 * 9.450  [ 1] Max Gain dBi..:
	 *   13    [ 2] Antenna Type..: 360 x 91 gain values follow
	 *   0.0   [ 3] Efficiency (for IONCAP)
	 * 15.000  [ 4] Frequency
	 *
	 */

	// The first line is the name of the antenna.
	// fgets will return a string that has a trailing "\n" which needs to be stripped off
	if (fgets(line, sizeof(line), fp) != NULL) {
		size_t len = strlen(line);
		if (len > 0 && line[len - 1] == '\n') {
			line[--len] = '\0';
		}
    }

    strcpy(Ant->Name, line);	// Store it to the path structure.

	// User feedback
	if(silent != TRUE) {
		printf("ReadType13: Reading antenna %.35s\n", Ant->Name);
	}

    fgets(line, sizeof(line), fp);		// Number of parameters

	// The next lines are parameters
	fgets(line, sizeof(line), fp);		// Max Gain
	sscanf(line, " %lf %s\n", &MaxG, instr);
	fgets(line, sizeof(line), fp);		// Antenna type
	sscanf(line, " %d %s\n", &iI, instr);
	fgets(line, sizeof(line), fp); // Efficiency
	fgets(line, sizeof(line), fp); // Frequency
	Ant->freqs[0] = atof(line);

	/*
	 * There are 360 azimuth blocks that look like the following. The leading zero is the azimuth.
	 *
	 *	0    -99.999-15.670 -9.730 -6.320 -3.960 -2.180 -0.790  0.320  1.240  1.980
	 *		   2.590  3.080  3.470  3.760  3.970  4.100  4.160  4.150  4.060  3.910
	 *         3.680  3.390  3.030  2.590  2.070  1.460  0.770 -0.030 -0.950 -1.990
	 *        -3.200 -4.590 -6.200 -8.070-10.220-12.470-14.120-14.080-12.590-10.760
	 *        -9.130 -7.760 -6.650 -5.750 -5.030 -4.450 -4.000 -3.660 -3.420 -3.260
	 *        -3.190 -3.190 -3.260 -3.400 -3.610 -3.880 -4.230 -4.640 -5.120 -5.680
	 *        -6.330 -7.060 -7.890 -8.830 -9.900-11.120-12.510-14.080-15.810-17.570
	 *       -18.950-19.300-18.460-17.020-15.490-14.090-12.880-11.830-10.930-10.160
	 *        -9.500 -8.930 -8.450 -8.050 -7.710 -7.430 -7.200 -7.030 -6.910 -6.840
	 *        -6.820
	 */

	for(i=0; i<azin; i++) {
		// Advance to the next azimuth and roll it over if necessary.
		iazi = (iMBOS+i)%360;

		fgets(line, sizeof(line), fp);
		sscanf(line, " %d %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf",
			&iI, &Ant->pattern[0][iazi][0], &Ant->pattern[0][iazi][1], &Ant->pattern[0][iazi][2], &Ant->pattern[0][iazi][3], &Ant->pattern[0][iazi][4],
			     &Ant->pattern[0][iazi][5], &Ant->pattern[0][iazi][6], &Ant->pattern[0][iazi][7], &Ant->pattern[0][iazi][8], &Ant->pattern[0][iazi][9]);
		for(j=10; j<90; j += 10) {
			fgets(line, sizeof(line), fp);
			sscanf(line, " %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf",
				&Ant->pattern[0][iazi][j],   &Ant->pattern[0][iazi][j+1], &Ant->pattern[0][iazi][j+2], &Ant->pattern[0][iazi][j+3], &Ant->pattern[0][iazi][j+4],
				&Ant->pattern[0][iazi][j+5], &Ant->pattern[0][iazi][j+6], &Ant->pattern[0][iazi][j+7], &Ant->pattern[0][iazi][j+8], &Ant->pattern[0][iazi][j+9]);
		}
        fgets(line, sizeof(line), fp);
		sscanf(line, " %lf\n", &Ant->pattern[0][iazi][90]);
	}

    return RTN_READANTENNAPATTERNSOK;

	#ifdef __GNUC__
	#pragma GCC diagnostic pop
	#endif
}


int ReadType14(struct Antenna *Ant, FILE *fp, int silent) {

	#ifdef __GNUC__
	#pragma GCC diagnostic push
	#pragma GCC diagnostic ignored "-Wunused-result"
	#endif

	char line[256];			// Read input line
	char instr[256];		// String temp

	int i, j;	// Loop counters
	int freqn, azin, elen;			// Number of elevations and azimuths

	/*
	 * The efficiency value in type 13/14 antenna files is used by VOACAP when
	 * determining receive noise values.  It is not used in P533/P372 and the 
	 * values are discarded.  The variable is only used to make the content of 
	 * the scanf() statements a little clearer.
	 */

	double efficiency;	// Antenna efficiency
	int iI = 0;					// Temp

	double MaxG = 0.0;	// Maximum gain

	freqn = 30;					// 1-30Mhz in 1MHz intervals, as per standard voacap files.
	azin = 360;					// Fixed number of azimuths at 1-degree intervals
	elen = 91;					// Fixed number of elevations at 1-degree intervals
	
	AllocateAntennaMemory(Ant, freqn, azin, elen);

	/*
     * Read a VOACAP antenna pattern Type 14 file
	 * Typically, the header will look like the following:
	 *
	 * 3EL Yagi @10M
 	 *  3     3 parameters
   *  0.00  [ 1] Max Gain dBi..:
   *  14    [ 2] Antenna Type..: 30 x (efficiency + 91 gain values) follow
   *  14.0  [ 3] Frequency
	 *
	 */

	if (fp == NULL) {
		return RTN_ERRCANTOPENANTFILE;
	}

    // The first line is the name of the antenna.
	// fgets will return a string that has a trailing "\n" which needs to be stripped off
	if (fgets(line, sizeof(line), fp) != NULL) {
		size_t len = strlen(line);
		if (len > 0 && line[len - 1] == '\n') {
			line[--len] = '\0';
		}
    }

    strcpy(Ant->Name, line);	// Store it to the path structure.

	// User feedback
	if(silent != TRUE) {
		printf("ReadType14: Reading antenna %.35s\n", Ant->Name);
	}

    fgets(line, sizeof(line), fp);		// Number of parameters

	// The next lines are parameters
	fgets(line, sizeof(line), fp);		// Max Gain
	sscanf(line, " %lf %s\n", &MaxG, instr);
	fgets(line, sizeof(line), fp);		// Antenna type
	sscanf(line, " %d %s\n", &iI, instr);
	fgets(line, sizeof(line), fp); // Frequency

	/*
	 * There are 30 elevation blocks (1-30) that look like the following. The first line
	 * of each block starts with the frequency and is followed by the efficiancy and the
	 * gain values for 0-9 degrees.
	 *
	 *  2 -0.57 -55.646 -4.202 -0.683  0.814  1.634  2.143  2.483  2.721  2.892  3.015
   *            3.104  3.167  3.209  3.234  3.244  3.242  3.230  3.207  3.176  3.138
	 *            3.091  3.038  2.979  2.913  2.842  2.764  2.681  2.593  2.499  2.400
   *            2.296  2.187  2.073  1.954  1.830  1.701  1.567  1.429  1.285  1.136
   *            0.982  0.822  0.658  0.488  0.313  0.133 -0.054 -0.245 -0.443 -0.647
   *           -0.857 -1.074 -1.297 -1.527 -1.764 -2.009 -2.261 -2.521 -2.791 -3.069
   *           -3.356 -3.654 -3.963 -4.283 -4.615 -4.960 -5.319 -5.694 -6.085 -6.494
   *           -6.923 -7.374 -7.849 -8.350 -8.882 -9.447-10.051-10.699-11.398-12.158
   *          -12.990-13.908-14.930-16.094-17.436-19.022-20.963-23.463-26.987-33.009
   *          -50.573
	 */
  for (i = 0; i<30; i++) {
		fgets(line, sizeof(line), fp);
		sscanf(line, " %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf",
				&Ant->freqs[i], &efficiency, &Ant->pattern[i][0][0], &Ant->pattern[i][0][1], &Ant->pattern[i][0][2], &Ant->pattern[i][0][3], &Ant->pattern[i][0][4],
				&Ant->pattern[i][0][5], &Ant->pattern[i][0][6], &Ant->pattern[i][0][7], &Ant->pattern[i][0][8], &Ant->pattern[i][0][9]);
		for(j=10; j<90; j += 10) {
			fgets(line, sizeof(line), fp);
			sscanf(line, " %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf",
				&Ant->pattern[i][0][j],   &Ant->pattern[i][0][j+1], &Ant->pattern[i][0][j+2], &Ant->pattern[i][0][j+3], &Ant->pattern[i][0][j+4],
				&Ant->pattern[i][0][j+5], &Ant->pattern[i][0][j+6], &Ant->pattern[i][0][j+7], &Ant->pattern[i][0][j+8], &Ant->pattern[i][0][j+9]);
		}
        fgets(line, sizeof(line), fp);
		sscanf(line, " %lf\n", &Ant->pattern[i][0][90]);
		
		// Add max gain value where required.
		if (MaxG != 0.0) {
			for(j=0; j<=90; j += 1) {
				Ant->pattern[i][0][j] += MaxG;
			}
        }

        // Copy the array of elevation data to the rest of the data structure.
		for (j=1; j<azin; j++) {
			memcpy(Ant->pattern[i][j], Ant->pattern[i][0], elen * sizeof(double));
		}
	}
	return RTN_READANTENNAPATTERNSOK;

	#ifdef __GNUC__
	#pragma GCC diagnostic pop
	#endif
}


void IsotropicPattern(struct Antenna *Ant, double G, int silent) {

	int azin, elen, freqn;			// Number of frequencies, elevations and azimuths
	int i, j;						// Loop counters

	azin = 360;					// Fixed number of azimuths at 1-degree intervals
	elen = 91;					// Fixed number of elevations at 1-degree intervals
	freqn = 1;					// Number of frequencies to be read


	AllocateAntennaMemory(Ant, freqn, azin, elen);

    // User feedback
	if(silent != TRUE) {
		printf("IsotropicPattern: Reading Isotropic antenna\n");
	}
    Ant->freqs[0] = 0;

	for(i=0; i<azin; i++) {
		for(j=0; j<elen; j++) {
			Ant->pattern[0][i][j] = G;
		}
    }

    return;
}

