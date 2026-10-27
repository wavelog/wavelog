#ifndef NOISE_H
#define NOISE_H

/* Operating system preprocessor directives */
#ifdef _WIN32
    #define DLLEXPORT __declspec(dllexport)
#endif
#ifdef __linux__
    #define DLLEXPORT
#endif
#ifdef __APPLE__
    #define DLLEXPORT
#endif
/* End operating system preprocessor directives */

/* Defines */
// Version number.
#define P372VER "14.3"

// Have the preprocessor time stamp the compile time.
#define P372CT __TIMESTAMP__

// Noise calculation (See ITU-R P.372).
#define CITY 0.0
#define RESIDENTIAL 1.0
#define RURAL 2.0
#define QUIETRURAL 3.0
#define NOISY 4.0
#define QUIET 5.0

// MakeNoise().
#define MNNOPRINT 0
#define MNPRINTTOSTDOUT 1
#define MNPRINTTOFILE 2

// Return ERROR >= 200 and < 220.
// Return ERROR from ReadFamDud().
// ERROR: Can Not Open Coefficient File.
#define RTN_ERROPENCOEFFFILE 201

// Return ERROR from AllocatePathMemory(), FreePathMemory() and InputDump().
// ERROR: Allocating Memory for DuD.
#define RTN_ERRALLOCATEDUD 202
// ERROR: Allocating Memory for Fam.
#define RTN_ERRALLOCATEFAM 203
// ERROR: Allocating Memory for FakP.
#define RTN_ERRALLOCATEFAKP 204
// ERROR: Allocating Memory for FakABP// Return ERROR from P533().
#define RTN_ERRALLOCATEFAKABP 205
// ERROR: Can Not Open P372.DLL.
#define RTN_ERRP372DLL 206
// ERROR: Allocating Memory for Noise Structure.
#define RTN_ERRALLOCATENOISE 207
// ERROR: Can't open output file in MakeNoise().
#define RTN_ERRMNCANTOPENFILE 208

// Return OKAY > 20 and <= 30.
// AllocatePathMemory().
#define RTN_ALLOCATEP372OK 21
// ReadFamDud(),
#define RTN_READFAMDUDOK 22
// NoiseMemory.c FreeNoiseMemory().
#define RTN_NOISEFREED 23
// Noise().
#define RTN_NOISEOK 24
// Noise() Man-made noise override.
#define RTN_NOISEMANMADEOK 25
// MakeNoise() Stand alone P372 caller.
#define RTN_MAKENOISEOK 26
/* End Defines */

/* Struct Definitions */
struct FamStats {
  int tmblk;       // Timeblock
  double FA;       // Atmospheric noise in dB above kT0b at 1 MHz
  double SigmaFam; // Standard deviation of values, Fam
  double Du;       // Ratio of upper decile to median value, Fam
  double SigmaDu;  // Standard deviations of values of Du
  double Dl;       // Ratio of median value, Fam, to lower decile
  double SigmaDl;  // Standard deviation of values of Dl
};

struct NoiseParams {
  // Output Parameters
  double FaA;  // Atmospheric noise
  double DuA;  // Atmospheric noise upper decile
  double DlA;  // Atmospheric noise lower decile
  double FaM;  // Man-made noise
  double DuM;  // Man-made noise upper decile
  double DlM;  // Man-made noise lower decile
  double FaG;  // Galactic noise
  double DuG;  // Galactic noise upper decile
  double DlG;  // Galactic noise lower decile
  double DuT;  // Total noise upper decile
  double DlT;  // Total noise lower decile
  double FamT; // Total noise

  // Non-Output Parameters
  double ManMadeNoise;
  double ***fakp;
  double **fakabp;
  double **fam;
  double ***dud;
};
/* End Struct Definitions */

/* Start P372.DLL typedef */
#ifdef _WIN32
    #include <Windows.h>
    // P372Version() & P372CompileTime()
    typedef const char *(__cdecl *cP372Info)(void);
    // AllocateNoiseMemory() & FreeNoiseMemory()
    typedef int(__cdecl *iNoiseMemory)(
        struct NoiseParams *noiseP
    );
    // Noise()
    typedef int(__cdecl *iNoise)(
        struct NoiseParams *noiseP,
        int hour,
        double lng,
        double lat,
        double frequency
    );
    // ReadFamDud()
    typedef int(__cdecl *iReadFamDud)(
        struct NoiseParams *noiseP,
        const char *DataFilePath,
        int month
    );
    // InitializeNoise()
    typedef void(__cdecl *vInitializeNoise)(
        struct NoiseParams *noiseP
    );
    // AtmosphericNoise()
    typedef void(__cdecl *vAtmosphericNoise)(
        struct NoiseParams *noiseP,
        int iutc,
        double lng,
        double lat,
        double frequency
    );
    // AtmosphericNoise_LT()
    typedef void(__cdecl *vAtmosphericNoise_LT)(
        struct NoiseParams *noiseP,
        struct FamStats *FamS,
        int lrxmt,
        double lng,
        double lat,
        double frequency
    );
    // MakeNoise().
    typedef int(__stdcall *iMakeNoise)(
        int month,
        int hour,
        double lat,
        double lng,
        double freq,
        double mmnoise,
        char *datafilepath,
        double *out,
        int pntflag
    );
#endif
/* End P372.DLL typedef */

/* Prototypes */
// _cdecl exports for all environments __linux__ && __APPLE__ && _WIN32.
DLLEXPORT int AllocateNoiseMemory(
    struct NoiseParams *noiseP
);
DLLEXPORT int FreeNoiseMemory(
    struct NoiseParams *noiseP
);
DLLEXPORT int Noise(
    struct NoiseParams *noiseP,
    int hour,
    double rlng,
    double rlat,
    double frequency
);
DLLEXPORT int ReadFamDud(
    struct NoiseParams *noiseP,
    const char *DataFilePath,
    int month
);
DLLEXPORT void InitializeNoise(
    struct NoiseParams *noiseP
);
DLLEXPORT char const *P372CompileTime(void);
DLLEXPORT char const *P372Version(void);
DLLEXPORT void AtmosphericNoise(
    struct NoiseParams *noiseP,
    int iutc,
    double rlng,
    double rlat,
    double frequency
);
DLLEXPORT void AtmosphericNoise_LT(
    struct NoiseParams *noiseP,
    struct FamStats *FamS,
    int lrxmt,
    double rlng,
    double rlat,
    double frequency
);
// Note: MakeNoise() requires decimal degrees lat and lng.
DLLEXPORT int MakeNoise(
    int month,
    int hour,
    double lat,
    double lng,
    double freq,
    double mmnoise,
    char *datafilepath,
    double *out,
    int pntflag
);

#if _WIN32
    // _stdcall exports dummies used to provide entry points in the DLL for 
    // MS Excel.
    DLLEXPORT int __stdcall _AllocateNoiseMemory(
        struct NoiseParams *noiseP
    );
    DLLEXPORT int __stdcall _FreeNoiseMemory(
        struct NoiseParams *noiseP
    );
    DLLEXPORT int __stdcall _Noise(
        struct NoiseParams *noiseP,
        int hour,
        double rlng,
        double rlat,
        double frequency
    );
    DLLEXPORT int __stdcall _ReadFamDud(
        struct NoiseParams *noiseP,
        const char *DataFilePath,
        int month
    );
    DLLEXPORT void __stdcall _InitializeNoise(
        struct NoiseParams *noiseP
    );
    DLLEXPORT char const *__stdcall _P372CompileTime(void);
    DLLEXPORT char const *__stdcall _P372Version(void);
    DLLEXPORT void __stdcall _AtmosphericNoise(
        struct NoiseParams *noiseP,
        int iutc,
        double rlng,
        double rlat,
        double frequency
    );
    DLLEXPORT void __stdcall _AtmosphericNoise_LT(
        struct NoiseParams *noiseP,
        struct FamStats *FamS,
        int lrxmt,
        double rlng,
        double rlat,
        double frequency
    );
    // Note: MakeNoise() requires decimal degrees lat and lng as input.
    DLLEXPORT int __stdcall _MakeNoise(
        int month,
        int hour,
        double lat,
        double lng,
        double freq,
        double mmnoise,
        char *datafilepath,
        double *out,
        int pntflag
    );
#endif
/* End Prototypes */

/* Operating system preprocessor */
#ifdef _WIN32
    HINSTANCE hLib;
    cP372Info dllP372Version;
    cP372Info dllP372CompileTime;
    iNoise dllNoise;
    iNoiseMemory dllAllocateNoiseMemory;
    iNoiseMemory dllFreeNoiseMemory;
    iReadFamDud dllReadFamDud;
    vInitializeNoise dllInitializeNoise;
    vAtmosphericNoise dllAtmosphericNoise;
    vAtmosphericNoise_LT dllAtmosphericNoise_LT;
    iMakeNoise dllMakeNoise;
#elif defined(__linux__) || defined(__APPLE__)
    #include <dlfcn.h>
    void *hLib;
    char *(*dllP372Version)();
    char *(*dllP372CompileTime)();
    int (*dllNoise)(struct NoiseParams *, int, double, double, double);
    int (*dllAllocateNoiseMemory)(struct NoiseParams *);
    int (*dllFreeNoiseMemory)(struct NoiseParams *);
    int (*dllReadFamDud)(struct NoiseParams *, const char *, int);
    void (*dllInitializeNoise)(struct NoiseParams *);
#endif
/* End operating system preprocessor */
#endif // NOISE_H
