#include <math.h>
#include <stdio.h>
#include <string.h>
#include "Common.h"
#include "P533.h"

#ifndef WL_DATA_PATH
#define WL_DATA_PATH "/data/"
#endif

static struct PathData path;
static struct PathData defaults;
static int allocated;
static int loaded_month;
static double result[5];

static void clear_result(void) {
    int i;
    for (i = 0; i < 5; ++i) result[i] = NAN;
}

static int check_file(const char *name, long expected_size, int error) {
    FILE *file = fopen(name, "rb");
    long size;
    if (!file) return error;
    if (fseek(file, 0, SEEK_END) != 0) {
        fclose(file);
        return error;
    }
    size = ftell(file);
    fclose(file);
    return size == expected_size ? 0 : error;
}

int wl_init(int month) {
    char filename[256];
    int code;
    clear_result();
    if (month < 1 || month > 12) return RTN_ERRMONTH;
    if (loaded_month == month) return 0;
    loaded_month = 0;
    snprintf(filename, sizeof(filename), WL_DATA_PATH "ionos%02d.bin", month);
    code = check_file(filename, 11197844L, RTN_ERRREADIONPARAMETERS);
    if (code) return code;
    snprintf(filename, sizeof(filename), WL_DATA_PATH "COEFF%02dW.txt", month);
    code = check_file(filename, month == 1 ? 235276L : 235275L, RTN_ERROPENCOEFFFILE);
    if (code) return code;
    code = check_file(WL_DATA_PATH "P1239-3 Decile Factors.txt", 73427L, RTN_ERRCANTOPENP1239FILE);
    if (code) return code;
    if (!allocated) {
        code = AllocatePathMemory(&path);
        if (code != RTN_ALLOCATEP533OK) return code;
        IsotropicPattern(&path.A_tx, 0.0, 1);
        IsotropicPattern(&path.A_rx, 0.0, 1);
        path.Modulation = ANALOG;
        path.BW = 2500.0;
        path.txpower = -10.0; /* 100 W expressed as dB relative to 1 kW. */
        path.SNRr = 0.0;
        path.SNRXXp = 50;
        code = ReadP1239(&path, WL_DATA_PATH);
        if (code != RTN_READP1239OK) return code;
        defaults = path;
        allocated = 1;
    }
    path = defaults;
    path.month = month - 1;
    code = ReadIonParametersBin(path.month, path.foF2, path.M3kF2, WL_DATA_PATH, 1);
    if (code != RTN_READIONPARAOK) return code;
    code = ReadFamDud(&path.noiseP, WL_DATA_PATH, path.month);
    if (code != RTN_READFAMDUDOK) return code;
    loaded_month = month;
    return 0;
}

int wl_predict(double tx_lat, double tx_lng, double rx_lat, double rx_lng,
               int year, int month, int utc_hour, double ssn, double frequency,
               int long_path, int noise_category) {
    int code;
    clear_result();
    if (!isfinite(tx_lat) || !isfinite(tx_lng) ||
        fabs(tx_lat) > 90.0 || fabs(tx_lng) > 180.0) return RTN_ERRLTX;
    if (!isfinite(rx_lat) || !isfinite(rx_lng) ||
        fabs(rx_lat) > 90.0 || fabs(rx_lng) > 180.0) return RTN_ERRLRX;
    if (!isfinite(ssn) || ssn < 1.0 || ssn > 311.0 || ssn != floor(ssn)) return RTN_ERRSSN;
    if (!isfinite(frequency) || frequency < 1.0 || frequency > 30.0) return RTN_ERRFREQUENCY;
    if (noise_category < 0 || noise_category > 3) return RTN_ERRMANMADENOISE;
    if (long_path != 0 && long_path != 1) return 3001;
    if (month < 1 || month > 12) return RTN_ERRMONTH;
    if (!allocated || loaded_month != month) return 3002;
    path = defaults;
    path.year = year;
    path.month = month - 1;
    path.hour = utc_hour;
    path.SSN = (int)ssn;
    path.frequency = frequency;
    path.SorL = long_path;
    path.L_tx.lat = tx_lat * 0.017453292519943295769;
    path.L_tx.lng = tx_lng * 0.017453292519943295769;
    path.L_rx.lat = rx_lat * 0.017453292519943295769;
    path.L_rx.lng = rx_lng * 0.017453292519943295769;
    path.noiseP.ManMadeNoise = noise_category;
    code = P533(&path);
    if (code != RTN_P533OK) return code;
    result[3] = path.BMUF;
    result[4] = path.distance;
    if (path.Pr == TINYDB) return 3004; /* Official no-mode sentinel, not a power floor. */
    result[0] = path.Pr + 30.0; /* Reference Pr is dBW, exposed power is dBm. */
    result[1] = result[0] - path.SNR;
    result[2] = path.SNR;
    if (!isfinite(result[0]) || !isfinite(result[1]) ||
        !isfinite(result[2]) || !isfinite(result[3]) || !isfinite(result[4])) {
        clear_result();
        return 3003;
    }
    return 0;
}

double *wl_result(void) {
    return result;
}
