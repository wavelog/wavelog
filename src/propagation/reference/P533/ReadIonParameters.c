#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <math.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End local includes

/*
 * These routines use the ionospheric data from the monthly median parameter maps that are generated internally to the REC533() 
 * program. It is hoped that the routines in this program which use ionospheric maps can then take advantage of more contemporary 
 * data sometime in the future. Be aware that the ionospheric maps used here are 1.5-degree resolution maps and it may take some
 * effort to make these routines take advantage of arbitrary resolution maps if and when they become available.
 */

int ReadIonParametersTxt(struct PathData *path, char DataFilePath[256], int silent) {
	/*
	 * ReadIonParametersTxt() is a routine to read ionospheric parameters from a file into arrays necessary for the ITU-R P.533 
	 *		calculation engine. All of the input data here that is "hard coded" will be passed presumably to the final version
	 *		of thewith relative accuracy  P.533 engine.
	 *
	 *	This routine reads Peter Suessman's ionospheric parameters from the program iongrid.
	 *	The file that is read is a text file. Once the file is read, the result is stored in two arrays. foF2 and M3kF2
	 *	that will be passed to the ITU HFProp engine to the propagation prediction
	 *	The arrays are of the format
	 *
	 *			foF2[hour][longitude][latitude][SSN] & M3kF2[hour][longitude][latitude][SSN]
	 *
	 *				where
	 *
	 *					hour =		0 to 23
	 *					longitude = 0 to 240 in 1.5-degree increments from 180 degrees West
	 *					latitude =	0 to 120 in 1.5-degree increments from 90 degrees South
	 *					SSN =		0 to 1 - 0 and 100 12-month smoothed sun spot number
	 *
	 *	The eccentricities of how the input file was created are based on P.1239 and Suessman's code, which is based
	 *	on work of several administrations. These ionospheric parameter files are based on CCIR spherical harmonic coefficients from 
	 *	the 1958 Geophysical year. Please refer to P.1239 for details on how to convert between the coefficients and foF2 and M(3000)F2
	 *
	 *		INPUT
	 *			struct PathData *path
	 *	
	 *		OUTPUT
	 *			Data is read into the arrays foF2 and M3kF2
	 *
	 */

	#ifdef __GNUC__
	#pragma GCC diagnostic push
	#pragma GCC diagnostic ignored "-Wunused-result"
	#endif

	int		j, k, m;
	int		/*hrs,*/ lng, lat, ssn; // Temp gridmap maxima

	int linelen = 300;
	char line[300];

	char InFilePath[256];
	char MapFile[32];
	
	FILE *fp;
	
	// At present the path structure is not used but is passed in so that it can select the correct map file based on month

	// The dimensions of the array are fixed by Suessman's file generating program "iongrid"
	// Eventually it would be nice if these were not fixed values so that other resolutions could be used. 
	//hrs = 24;	// 24 hours
	lng = 241;	// 241 longitudes at 1.5-degree increments
	lat = 121;	// 121 latitudes at 1.5-degree increments
	ssn = 2;	// 2 SSN (12-month smoothed sun spot numbers) high and low
	
	// This may require error handling at some point.
	// Eventually you want the file that is indicated by GUIConfig to be opened.
	strcpy(InFilePath, DataFilePath);

	//strcat(InFilePath, "ionmap/TXT/");
	sprintf(MapFile, "ionos%02d.txt", path->month+1);
	strcat(InFilePath, MapFile);
	fp = fopen(InFilePath, "r"); 
	//fp = fopen("..\\..\\ionmap\\ionos04.txt", "r"); 
	if(fp == NULL) {
		printf("ReadIonParameters: ERROR Can't find input file %s\n", InFilePath);
		return RTN_ERRREADIONPARAMETERS;
	}

    if(silent != TRUE) {
		printf("ReadIonParameters: Reading file ionos%02d.txt for ionospheric parameters\n", path->month+1);
		printf("ReadIonParameters: Reading foF2 into array\n");
	}

    // Read in foF2
	for(m = 0; m < ssn; m++) { // SSN
		for(j = 0; j < lng; j++) { // Longitude
			for(k = 0; k < lat; k++) { // Latitude
				// Read 24 hours of data from the Dambolt/Seussman ionospheric atlas file.
				// There are six lines for 24 hours.
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f  %f  %f", &path->foF2[0][j][k][m], &path->foF2[1][j][k][m], &path->foF2[2][j][k][m],                 
					                                 &path->foF2[3][j][k][m], &path->foF2[4][j][k][m]);
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f", &path->foF2[5][j][k][m], &path->foF2[6][j][k][m],&path->foF2[7][j][k][m]);
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f  %f  %f", &path->foF2[8][j][k][m], &path->foF2[9][j][k][m], &path->foF2[10][j][k][m],
					                                 &path->foF2[11][j][k][m], &path->foF2[12][j][k][m]);
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f", &path->foF2[13][j][k][m], &path->foF2[14][j][k][m], &path->foF2[15][j][k][m]);
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f  %f  %f", &path->foF2[16][j][k][m], &path->foF2[17][j][k][m], &path->foF2[18][j][k][m],
					                                 &path->foF2[19][j][k][m], &path->foF2[20][j][k][m]);
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f", &path->foF2[21][j][k][m],&path->foF2[22][j][k][m],&path->foF2[23][j][k][m]);
			}
        }
    }

    if(silent != TRUE) {
		printf("ReadIonParameters: Reading M3kF2 into array\n");
	}

    // Read in M3kF2
	for(m = 0; m < ssn; m++) { // SSN
		for(j = 0; j < lng; j++) { // Longitude
			for(k = 0; k < lat; k++) { // Latitude
				// Read 24 hours of data from the Dambolt/Seussman ionospheric atlas file.
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f  %f  %f", &path->M3kF2[0][j][k][m], &path->M3kF2[1][j][k][m], &path->M3kF2[2][j][k][m],
					                                 &path->M3kF2[3][j][k][m], &path->M3kF2[4][j][k][m]);
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f", &path->M3kF2[5][j][k][m], &path->M3kF2[6][j][k][m], &path->M3kF2[7][j][k][m]);
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f  %f  %f", &path->M3kF2[8][j][k][m],  &path->M3kF2[9][j][k][m], &path->M3kF2[10][j][k][m],
					                                 &path->M3kF2[11][j][k][m], &path->M3kF2[12][j][k][m]);
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f", &path->M3kF2[13][j][k][m], &path->M3kF2[14][j][k][m], &path->M3kF2[15][j][k][m]);
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f  %f  %f", &path->M3kF2[16][j][k][m], &path->M3kF2[17][j][k][m], &path->M3kF2[18][j][k][m], 
					                                 &path->M3kF2[19][j][k][m], &path->M3kF2[20][j][k][m]);
				fgets(line, linelen, fp);
				sscanf(line, "  %f  %f  %f", &path->M3kF2[21][j][k][m], &path->M3kF2[22][j][k][m], &path->M3kF2[23][j][k][m]);
			}
        }
    }

    // Close the file and return.
	fclose(fp);

	return RTN_READIONPARAOK;

	#ifdef __GNUC__
	#pragma GCC diagnostic pop
	#endif
}

int ReadIonParametersBin(int month, float ****foF2, float ****M3kF2, char DataFilePath[256], int silent) {
	/*
	 * ReadIonParametersBin() is a routine to read ionospheric parameters from a file into arrays necessary for the ITU-R P.533 
	 *		calculation engine. All of the input data here that is "hard coded" will be passed presumably to the final version
	 *		of the P.533 engine.
	 *
	 *	This routine reads Peter Suessman's ionospheric parameters from the program iongrid.
	 *	The file that is read is a text file. Once the file is read, the result is stored in two arrays, foF2 and M3kF2, 
	 *	that will be passed to the ITU HFProp engine to the propagation prediction
	 *	The arrays are of the format
	 *
	 *			foF2[hour][longitude][latitude][SSN] & M3kF2[hour][longitude][latitude][SSN]
	 *
	 *				where
	 *
	 *					hour =		0 to 23
	 *					longitude = 0 to 240 in 1.5-degree increments from 180 degrees West
	 *					latitude =	0 to 120 in 1.5-degree increments from 90 degrees South
	 *					SSN =		0 to 1 - 0 and 100 12-month smoothed sun spot number
	 *
	 *	The eccentricities of how the input file was created are based on P.1239 and Suessman's code, which is based
	 *	on work of several administrations. These ionospheric parameter files are based on CCIR spherical harmonic coefficients from 
	 *	the 1958 Geophysical year. Please refer to P.1239 for details on how to convert between the coefficients and foF2 and M(3000)F2
	 *
	 *		INPUT
	 *			struct PathData *path
	 *	
	 *		OUTPUT
	 *			data is read into the arrays foF2 and M3kF2
	 *
	 */
	#ifdef __GNUC__
	#pragma GCC diagnostic push
	#pragma GCC diagnostic ignored "-Wunused-result"
	#endif

	char buffer[256];

	int	i, j, k, m;
	int	hrs, lng, lat, ssn; // Temp gridmap maxima
	int numfoF2;

	//warning C4189: 'linelen': local variable is initialized but not referenced
	//int linelen = 300;

	float * readBuffer;

	char InFilePath[256];
	char MapFile[32];
	
	FILE *fp;
	
	// At present the path structure is not used but is passed in so that it can select the correct map file based on month.

	// The dimensions of the array are fixed by Suessman's file generating program "iongrid".
	// Eventually it would be nice if these were not fixed values so that other resolutions could be used. 
	hrs = 24;	// 24 hours
	lng = 241;	// 241 longitudes at 1.5-degree increments
	lat = 121;	// 121 latitudes at 1.5-degree increments
	ssn = 2;	// 2 SSN (12-month smoothed sun spot numbers) high and low
	numfoF2 = hrs * lng * lat * ssn;
	// This may require error handling at some point.
	// Eventually you want the file that is indicated by GUIConfig to be opened.
	strcpy(InFilePath, DataFilePath);
	// Glue on the ionmap/BIN directory to the InFilePath
	//strcat(InFilePath, "ionmap/BIN/");
	sprintf(MapFile, "ionos%02d.bin", month+1);
	// Glue on the filenake of the coefficient file
	strcat(InFilePath, MapFile);
	fp = fopen(InFilePath, "rb"); 
	if(fp == NULL) {
		printf("ReadIonParameters: ERROR Can't find input file %s\n", InFilePath);
		return RTN_ERRREADIONPARAMETERS;
	}

    if(silent != TRUE) {
		printf("ReadIonParameters: Reading file ionos%02d.txt for ionospheric parameters\n", month+1);
	}

    //The first 5 bytes of the file are overhead that FORTRAN puts in 
	fread(&buffer, sizeof(char), 5, fp);

	readBuffer = (float *) malloc(sizeof(float) * numfoF2);
	fread(readBuffer,sizeof(float),numfoF2,fp);

	if(silent != TRUE) {
		printf("ReadIonParameters: Reading foF2 (binary) into array\n");
	}

    // Read in foF2
	for(m = 0; m < ssn; m++) { // SSN
		for(j = 0; j < lng; j++) { // Longitude
			for(k = 0; k < lat; k++) { // Latitude
				// Read 24 hours of data from the Dambolt/Seussman ionospheric atlas file
				for(i = 0; i < hrs; i++) { // Latitude
					foF2[i][j][k][m] = readBuffer[ (m * (lng * lat * hrs)) + 
													(j * (lat * hrs)) +
													(k * (hrs)) +
													 i];
					// fread(&foF2[i][j][k][m], sizeof(float), 1, fp);
				}
            }
        }
    }

    free(readBuffer);

	// The next 5 bytes are the tail of the foF2 record followed by 5 bytes of header for the M(3000)F2 record.
	fread(&buffer, sizeof(char), 10, fp);
	
	if(silent != TRUE) {
		printf("ReadIonParameters: Reading M3kF2 (binary) into array\n");
	}

    readBuffer = (float *) malloc(sizeof(float) * numfoF2);
	fread(readBuffer,sizeof(float),numfoF2,fp);

	// Read in M3kF2
	for(m = 0; m < ssn; m++) { // SSN
		for(j = 0; j < lng; j++) { // Longitude
			for(k = 0; k < lat; k++) { // Latitude
				// Read 24 hours of data from the Dambolt/Seussman ionospheric atlas file.
				for(i = 0; i < hrs; i++) { // Latitude
					M3kF2[i][j][k][m] = readBuffer[ (m * (lng * lat * hrs)) + 
													(j * (lat * hrs)) +
													(k * (hrs)) +
													 i];
					// fread(&M3kF2[i][j][k][m], sizeof(float), 1, fp);
				}
            }
        }
    }

    free(readBuffer);

	// Close the file and return.
	fclose(fp);

	return RTN_READIONPARAOK;

	#ifdef __GNUC__
	#pragma GCC diagnostic pop
	#endif
}
