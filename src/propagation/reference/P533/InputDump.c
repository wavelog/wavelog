#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// Local includes
#include "Common.h"
#include "P533.h"
// End local include


DLLEXPORT int InputDump(struct PathData *path) {

	/* 
	 
	  InputDump() - Dumps the path passed in so that it can be examined for proper passing.
	  
	 		INPUT 
	 			struct PathData *path
	 
	 		OUTPUT
	 			prints the path data for trouble shooting the use of the DLL
	 
			SUBROUTINES
				None
	 
	 */

	printf("Dumping input to P533 DLL\n");
	printf("Path structure address (path) 0x%llx\n", (unsigned long long)path);
	printf("\n");
	printf("path->name addr          0x%llx contents %s\n", (unsigned long long)&path->name, path->name);
	printf("path->txname addr        0x%llx contents %s\n", (unsigned long long)&path->txname, path->txname);
	printf("path->rxname addr        0x%llx contents %s\n", (unsigned long long)&path->rxname, path->rxname);
	printf("path->year addr          0x%llx contents %d\n", (unsigned long long)&path->year, path->year);
	printf("path->month addr         0x%llx contents %d\n", (unsigned long long)&path->month, path->month);
	printf("path->hour addr          0x%llx contents %d\n", (unsigned long long)&path->hour, path->hour);
	printf("path->SSN addr           0x%llx contents %d\n", (unsigned long long)&path->SSN, path->SSN);
	printf("path->Modulation addr    0x%llx contents %d\n", (unsigned long long)&path->Modulation, path->Modulation);
	printf("path->SorL addr          0x%llx contents %d\n", (unsigned long long)&path->SorL, path->SorL);
	printf("\n");
	printf("path->frequency addr     0x%llx contents %lf\n", (unsigned long long)&path->frequency, path->frequency);
	printf("path->BW addr            0x%llx contents %lf\n", (unsigned long long)&path->BW, path->BW);
	printf("path->txpower addr       0x%llx contents %lf\n", (unsigned long long)&path->txpower, path->txpower);
	printf("\n");
	printf("path->SNRXXp addr        0x%llx contents %d\n",  (unsigned long long)&path->SNRXXp, path->SNRXXp);
	printf("path->SNRr addr          0x%llx contents %lf\n", (unsigned long long)&path->SNRr, path->SNRr);
	printf("path->SIRr addr          0x%llx contents %lf\n", (unsigned long long)&path->SIRr, path->SIRr);
	printf("\n");
	printf("path->F0 addr            0x%llx contents %lf\n", (unsigned long long)&path->F0, path->F0);
	printf("path->T0 addr            0x%llx contents %lf\n", (unsigned long long)&path->T0, path->T0);
	printf("path->A addr             0x%llx contents %lf\n", (unsigned long long)&path->A, path->A);
	printf("path->TW addr            0x%llx contents %lf\n", (unsigned long long)&path->TW, path->TW);
	printf("path->FW addr            0x%llx contents %lf\n", (unsigned long long)&path->FW, path->FW);
	printf("\n");
	printf("path->L_tx addr          0x%llx\n",              (unsigned long long)&path->L_tx);
	printf("path->L_tx.lat addr      0x%llx contents %lf\n", (unsigned long long)&path->L_tx.lat, path->L_tx.lat);
	printf("path->L_tx.lng addr      0x%llx contents %lf\n", (unsigned long long)&path->L_tx.lng, path->L_tx.lng);
	printf("\n");
	printf("path->A_tx addr          0x%llx\n",             (unsigned long long)&path->A_tx);
	printf("path->A_tx.name addr     0x%llx contents %s\n", (unsigned long long)&path->A_tx.Name, path->A_tx.Name);
	printf("path->A_tx.pattern addr  0x%llx\n",             (unsigned long long)&path->A_tx.pattern);
	printf("\tpath->A_tx.pattern[0][0][0] addr     0x%llx contents %lf\n", (unsigned long long)&(path->A_tx.pattern[0][0][0]), path->A_tx.pattern[0][0][0]);
	printf("\tpath->A_tx.pattern[0][0][90] addr    0x%llx contents %lf\n", (unsigned long long)&(path->A_tx.pattern[0][0][90]), path->A_tx.pattern[0][0][90]);
	printf("\tpath->A_tx.pattern[0][359][0] addr   0x%llx contents %lf\n", (unsigned long long)&(path->A_tx.pattern[0][359][0]), path->A_tx.pattern[0][359][0]);
	printf("\tpath->A_tx.pattern[0][359][90] addr  0x%llx contents %lf\n", (unsigned long long)&(path->A_tx.pattern[0][359][90]), path->A_tx.pattern[0][359][90]);
	printf("\n");
	printf("path->L_rx addr          0x%llx\n",              (unsigned long long)&path->L_rx);
	printf("path->L_rx.lat addr      0x%llx contents %lf\n", (unsigned long long)&path->L_rx.lat, path->L_rx.lat);
	printf("path->L_rx.lng addr      0x%llx contents %lf\n", (unsigned long long)&path->L_rx.lng, path->L_rx.lng);
	printf("\n");
	printf("path->A_rx addr          0x%llx\n",             (unsigned long long)&path->A_rx);
	printf("path->A_rx.name addr     0x%llx contents %s\n", (unsigned long long)&path->A_rx.Name, path->A_rx.Name);
	printf("path->A_rx.pattern addr  0x%llx\n",             (unsigned long long)&path->A_rx.pattern);
	printf("\tpath->A_rx.pattern[0][0][0] addr     0x%llx contents %lf\n", (unsigned long long)&path->A_rx.pattern[0][0][0], path->A_rx.pattern[0][0][0]);
	printf("\tpath->A_rx.pattern[0][0][90] addr    0x%llx contents %lf\n", (unsigned long long)&path->A_rx.pattern[0][0][90], path->A_rx.pattern[0][0][90]);
	printf("\tpath->A_rx.pattern[0][359][0] addr   0x%llx contents %lf\n", (unsigned long long)&path->A_rx.pattern[0][359][0], path->A_rx.pattern[0][359][0]);
	printf("\tpath->A_rx.pattern[0][359][90] addr  0x%llx contents %lf\n", (unsigned long long)&path->A_rx.pattern[0][359][90], path->A_rx.pattern[0][359][90]);
	printf("\n");
	printf("path->foF2 addr          0x%llx\n",                         (unsigned long long)&path->foF2);
	printf("\tpath->foF2[0][0][0][0] addr       0x%llx contents %lf\n", (unsigned long long)&path->foF2[0][0][0][0], path->foF2[0][0][0][0]);
	printf("\tpath->foF2[23][0][0][0] addr      0x%llx contents %lf\n", (unsigned long long)&path->foF2[23][0][0][0], path->foF2[23][0][0][0]);
	printf("\tpath->foF2[0][240][0][0] addr     0x%llx contents %lf\n", (unsigned long long)&path->foF2[0][240][0][0], path->foF2[0][240][0][0]);
	printf("\tpath->foF2[0][0][120][0] addr     0x%llx contents %lf\n", (unsigned long long)&path->foF2[0][0][120][0], path->foF2[0][0][120][0]);
	printf("\tpath->foF2[0][0][0][1] addr       0x%llx contents %lf\n", (unsigned long long)&path->foF2[0][0][0][1], path->foF2[0][0][0][1]);
	printf("path->M3kF2 addr         0x%llx\n",                         (unsigned long long)&path->M3kF2);
	printf("\tpath->M3kF2[0][0][0][0] addr       0x%llx contents %lf\n", (unsigned long long)&path->M3kF2[0][0][0][0], path->M3kF2[0][0][0][0]);
	printf("\tpath->M3kF2[23][0][0][0] addr      0x%llx contents %lf\n", (unsigned long long)&path->M3kF2[23][0][0][0], path->M3kF2[23][0][0][0]);
	printf("\tpath->M3kF2[0][240][0][0] addr     0x%llx contents %lf\n", (unsigned long long)&path->M3kF2[0][240][0][0], path->M3kF2[0][240][0][0]);
	printf("\tpath->M3kF2[0][0][120][0] addr     0x%llx contents %lf\n", (unsigned long long)&path->M3kF2[0][0][120][0], path->M3kF2[0][0][120][0]);
	printf("\tpath->M3kF2[0][0][0][1] addr       0x%llx contents %lf\n", (unsigned long long)&path->M3kF2[0][0][0][1], path->M3kF2[0][0][0][1]);
	printf("path->foF2var addr       0x%llx\n",                          (unsigned long long)&path->foF2var);
	printf("\tpath->foF2var[0][0][0][0][0] addr       0x%llx contents %lf\n", (unsigned long long)&path->foF2var[0][0][0][0][0], path->foF2var[0][0][0][0][0]);
	printf("\tpath->foF2var[2][0][0][0][0] addr       0x%llx contents %lf\n", (unsigned long long)&path->foF2var[2][0][0][0][0], path->foF2var[2][0][0][0][0]);
	printf("\tpath->foF2var[0][23][0][0][0] addr      0x%llx contents %lf\n", (unsigned long long)&path->foF2var[0][23][0][0][0], path->foF2var[0][23][0][0][0]);
	printf("\tpath->foF2var[0][0][18][0][0] addr      0x%llx contents %lf\n", (unsigned long long)&path->foF2var[0][0][18][0][0], path->foF2var[0][0][18][0][0]);
	printf("\tpath->foF2var[0][0][0][2][0] addr       0x%llx contents %lf\n", (unsigned long long)&path->foF2var[0][0][0][2][0], path->foF2var[0][0][0][2][0]);
	printf("\tpath->foF2var[0][0][0][0][1] addr       0x%llx contents %lf\n", (unsigned long long)&path->foF2var[0][0][0][0][1], path->foF2var[0][0][0][0][1]);
	printf("path->noiseP.dud addr			 0x%llx\n",                          (unsigned long long)&path->noiseP.dud);
	printf("\tpath->noiseP.dud[0][0][0] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.dud[0][0][0], path->noiseP.dud[0][0][0]);
	printf("\tpath->noiseP.dud[4][0][0] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.dud[4][0][0], path->noiseP.dud[4][0][0]);
	printf("\tpath->noiseP.dud[0][11][0] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.dud[0][11][0], path->noiseP.dud[0][11][0]);
	printf("\tpath->noiseP.dud[0][0][4] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.dud[0][0][4], path->noiseP.dud[0][0][4]);
	printf("path->noiseP.fam addr           0x%llx\n",                      (unsigned long long)&path->noiseP.fam);
	printf("\tpath->noiseP.fam[0][0] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.fam[0][0], path->noiseP.fam[0][0]);
	printf("\tpath->noiseP.fam[11][0] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.fam[11][0], path->noiseP.fam[11][0]);
	printf("\tpath->noiseP.fam[0][13] addr			 0x%llx contents %lf\n",     (unsigned long long)&path->noiseP.fam[0][13], path->noiseP.fam[0][13]);
	printf("path->noiseP.fakp addr           0x%llx\n",                          (unsigned long long)&path->noiseP.fakp);
	printf("\tpath->noiseP.fakp[0][0][0] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.fakp[0][0][0],  path->noiseP.fakp[0][0][0]);
	printf("\tpath->noiseP.fakp[5][0][0] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.fakp[5][0][0],  path->noiseP.fakp[5][0][0]);
	printf("\tpath->noiseP.fakp[0][15][0] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.fakp[0][15][0], path->noiseP.fakp[0][15][0]);
	printf("\tpath->noiseP.fakp[0][0][28] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.fakp[0][0][28], path->noiseP.fakp[0][0][28]);
	printf("path->noiseP.fakabp addr           0x%llx\n",                        (unsigned long long)&path->noiseP.fakabp);
	printf("\tpath->noiseP.fakabp[0][0] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.fakabp[0][0], path->noiseP.fakabp[0][0]);
	printf("\tpath->noiseP.fakabp[5][0] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.fakabp[5][0], path->noiseP.fakabp[5][0]);
	printf("\tpath->noiseP.fakabp[0][1] addr			 0x%llx contents %lf\n", (unsigned long long)&path->noiseP.fakabp[0][1], path->noiseP.fakabp[0][1]);
	printf("\n");

	printf("\n\n");
	printf("**************** Atmospheric Coefficients ****************\n");
	printf("\tFor coefficient map see: NTIA Report 85-173 \n");
	printf("\tAtmospheric Radio Noise: Worldwide Levels and Other Characteristics\n");
	printf("\tSpaulding and Washburn]\n");
	printf("\tTable 6 page 56\n");
	printf("**********************************************************\n");
	for (int k = 0; k < 6; k++) {
		printf("******************* TIME BLOCK %02d **********************\n", k);
		printf("\t\tAlpha(%02d,%02d)\t\t\tBeta(%02d,%02d)\n", 0, k, 1, k);
		printf("\t\t%f\t\t\t\t%f\n", path->noiseP.fakabp[0][k], path->noiseP.fakabp[1][k]);
		printf("\tCHI\t\tMIXED LATITUDE AND LONGITUDE COEFFICIENTS\n");
		for (int i = 0; i < 29; i++) {
			printf("%f\t", path->noiseP.fakp[k][15][i]);
			for (int j = 0; j <= 4; j++) {
				printf("%f\t", path->noiseP.fakp[k][j][i]);
			}
            printf("\n\t\t\t");
			for (int j = 5; j <= 9; j++) {
				printf("%f\t", path->noiseP.fakp[k][j][i]);
			}
            printf("\n\t\t\t");
			for (int j = 10; j <= 14; j++) {
				printf("%f\t", path->noiseP.fakp[k][j][i]);
			}
            printf("\n");
		}
    }

    return RTN_INPUTDUMPOK;

}
