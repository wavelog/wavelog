#include <stdlib.h>

// Local includes
#include "Common.h"
#include "Noise.h"

#include <stdlib.h>
#include <stddef.h>

int AllocateNoiseMemory(
    struct NoiseParams* noiseP
) {
    /*
    Allocate the memory necessary for the noiseP structure.
    The data must be read into these structures elsewhere.

        INPUT
            struct NoiseParams *noiseP

        OUTPUT
            noiseP->fakp
            noiseP->fakabp
            noiseP->fam
            noiseP->dud

        SUBROUTINES
            None
     */

    double ***fakp;
    double **fakabp;
    double **fam;
    double ***dud;

    int m, n;

    // Create the fakp array.
    fakp = (double***)malloc(6 * sizeof(double**));
    for (n = 0; n < 6; n++) {
        fakp[n] = (double**)malloc(16 * sizeof(double*));
        for (m = 0; m < 16; m++) {
            fakp[n][m] = (double*)malloc(29 * sizeof(double));
        }
    }

    // Create the fakabp array.
    fakabp = (double**)malloc(6 * sizeof(double*));
    for (m = 0; m < 6; m++) {
        fakabp[m] = (double*)malloc(2 * sizeof(double));
    }

    // Create the dud array.
    dud = (double***)malloc(5 * sizeof(double**));
    for (n = 0; n < 5; n++) {
        dud[n] = (double**)malloc(12 * sizeof(double*));
        for (m = 0; m < 12; m++) {
            dud[n][m] = (double*)malloc(5 * sizeof(double));
        }
    }

    // Create the fam array.
    fam = (double**)malloc(12 * sizeof(double*));
    for (m = 0; m < 12; m++) {
        fam[m] = (double*)malloc(14 * sizeof(double));
    }

    // Check for NULLs and save the pointers to the path structure.
    if (dud != NULL)
        noiseP->dud = dud;
    else
        return RTN_ERRALLOCATEDUD;

    if (fam != NULL)
        noiseP->fam = fam;
    else
        return RTN_ERRALLOCATEFAM;

    if (fakp != NULL)
        noiseP->fakp = fakp;
    else
        return RTN_ERRALLOCATEFAKP;

    if (fakabp != NULL)
        noiseP->fakabp = fakabp;
    else
        return RTN_ERRALLOCATEFAKABP;

    return RTN_ALLOCATEP372OK;
}

int FreeNoiseMemory(
    struct NoiseParams *noiseP
) {
    /*
    Free the memory that was dynamically (m) allocated for the structure 
    NoiseParams noiseP.

        INPUT
            struct NoiseParams *noiseP

        OUTPUT
            void

         SUBROUTINES
             None
       */

    int m, n;

    // Free DUD
    for (n = 0; n < 5; n++) {
        for (m = 0; m < 12; m++) {
            free(noiseP->dud[n][m]);
        }
        free(noiseP->dud[n]);
    }
    free(noiseP->dud);

    // Free FAM
    for (m = 0; m < 12; m++) {
        free(noiseP->fam[m]);
    }
    free(noiseP->fam);

    // Free FAKP
    for (n = 0; n < 6; n++) {
        for (m = 0; m < 16; m++) {
            free(noiseP->fakp[n][m]);
        }
        free(noiseP->fakp[n]);
    }
    free(noiseP->fakp);

    // Free fakabp
    for (m = 0; m < 6; m++) {
        free(noiseP->fakabp[m]);
    }
    free(noiseP->fakabp);

    return RTN_NOISEFREED;
}
