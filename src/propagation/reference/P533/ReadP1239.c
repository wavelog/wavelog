#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End local includes

int ReadP1239(struct PathData *path, const char * DataFilePath) {

	/*
	 * ReadP1239() - Read the file "P1239-2 Decile Factors.txt", which is Table 2 and 3 in ITU-R P1239-2 (10/09).
	 *		The data in this file are the decile factors for within-the-month variations of foF2.
	 *
	 *			INPUT
	 *				struct PathData *path
	 *
	 *			OUTPUT
	 *				data is written into the array path.foF2var
	 *
	 */
	#ifdef __GNUC__
	#pragma GCC diagnostic push
	#pragma GCC diagnostic ignored "-Wunused-result"
	#endif

	int i, k, m, n;
	int /*hrs,*/ season, lat, ssn, decile;
	char line[256]; // There are 80 characters in the line.
	char substr[45]; 
	char InFilePath[256];

	FILE *fp;
	
	strcpy(InFilePath, DataFilePath);
	strcat(InFilePath, "P1239-3 Decile Factors.txt");   

	fp = fopen (InFilePath, "r");  // Open the file. Home
	if(fp == NULL) {
		printf("ReadP1239: ERROR Can't find input file 'P1239-3 Decile Factors.txt'\n");
		printf("\t\t<%s>\n", InFilePath);
		return RTN_ERRCANTOPENP1239FILE;
	}
	
	season = 3;	// 3 seasons
				//		1) WINTER 2) EQUINOX 3) SUMMER
	//hrs = 24;	// 24 hours  
	lat = 19;	// 19 latitude by 5
				//      0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90
	ssn = 3;	// 3 SSN ranges
				//		1) R12 < 50 2) 50 <= R12 <= 100 3) R12 > 100
	decile = 2;	// 2 deciles 
				//	1) lower 2) upper

	fgets(line, 256, fp);
	fgets(line, 256, fp);

	// Now read numbers for the lower decile.
	for(n=0;n<decile;n++) { // 2 deciles lower and upper
		for(i=0;i<season;i++) { // Three seasons
			for(m=0; m<ssn; m++) { // Three sunspot ranges
				// Read the next four lines of text.
				fgets(line, 256, fp);
				fgets(line, 256, fp);
				fgets(line, 256, fp);
				fgets(line, 256, fp);
				for(k=lat-1;k>=0;k--) {  // 19 latitudes counting backward to make the indices correspond to increasing latitude
					// Read the next latitude line of text
					fgets(line, 256, fp);
					// Scan 1 string latitude and 24 numbers corresponding to hours
					sscanf(line, "%s %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf %lf/n", substr,
									&path->foF2var[i][0][k][m][n],  &path->foF2var[i][1][k][m][n],  &path->foF2var[i][2][k][m][n],  &path->foF2var[i][3][k][m][n], 
									&path->foF2var[i][4][k][m][n],  &path->foF2var[i][5][k][m][n],  &path->foF2var[i][6][k][m][n],  &path->foF2var[i][7][k][m][n],
									&path->foF2var[i][8][k][m][n],  &path->foF2var[i][9][k][m][n],  &path->foF2var[i][10][k][m][n], &path->foF2var[i][11][k][m][n], 
									&path->foF2var[i][12][k][m][n], &path->foF2var[i][13][k][m][n], &path->foF2var[i][14][k][m][n], &path->foF2var[i][15][k][m][n], 
									&path->foF2var[i][16][k][m][n], &path->foF2var[i][17][k][m][n], &path->foF2var[i][18][k][m][n], &path->foF2var[i][19][k][m][n], 
									&path->foF2var[i][20][k][m][n], &path->foF2var[i][21][k][m][n], &path->foF2var[i][22][k][m][n], &path->foF2var[i][23][k][m][n]);
				}
			}
		}
	}

	
	fclose(fp);  // Close the file.

	return RTN_READP1239OK;

	#ifdef __GNUC__
	#pragma GCC diagnostic pop
	#endif
}

