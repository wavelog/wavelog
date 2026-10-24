#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// Local includes
#include "Common.h"
#include "Noise.h"
// End Local includes 

void InitializeNoise(
    struct NoiseParams* noiseP
) {
    noiseP->FaA = TINYDB;
    noiseP->FaG = TINYDB;
    noiseP->FaM = TINYDB;
    noiseP->FamT = TINYDB;

    noiseP->DlA = TINYDB;
    noiseP->DlG = TINYDB;
    noiseP->DlM = TINYDB;
    noiseP->DuG = TINYDB;
    noiseP->DuM = TINYDB;
    noiseP->DlT = TINYDB;
    noiseP->DuA = TINYDB;
    noiseP->DuT = TINYDB;
}
