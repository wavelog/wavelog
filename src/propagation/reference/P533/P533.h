#ifndef P533_H
#define P533_H

// Operating system preprocessor directives *********************************************************
#ifdef _WIN32
	#define DLLEXPORT __declspec(dllexport)
#endif
#ifdef __linux__
	#define DLLEXPORT
#endif
#ifdef __APPLE__
	#define DLLEXPORT
#endif

// External Preprocessors Dependancies
// The path structure constains the P372 noise structure
#include "Noise.h"
// End External Preprocessor Dependancies

// P533 *******************************************************************************************

// Version number
#define P533VER		"14.2"

// Have the preprocessor time stamp the compile time
#define P533CT		__TIMESTAMP__

/*
 * Conventions
 *	Latitude and Longitude used by P533 are to be in radians under the following convention:
 *			latitude (-90.0 - +90.0)
 *				        S=-     N=+
 *			longitude (-180.0 - +180.0)
 *				         W=-      E=+
 */

// Indicies for magfit() The gyrofrequency and magnetic dip are calculated at a height
//		of either 100 or 300 km. For absorption calculation 100 km is typically used
//		while 300 km is used for other calculations.
#define HR100km		0		// height = 100 (km)
#define HR300km		1		// height = 300 (km)

// Returns ************************************************************************

// Note: All error numbers in the calculation engine P533() are < 1000

// Return ERROR >= 100 and < 150

// Returns from ValidData()
#define RTN_ERRYEAR						100	// ERROR: Invalid Input Year
#define RTN_ERRMONTH					101 // ERROR: Invalid Input Month
#define RTN_ERRHOUR						102 // ERROR: Invalid Input Hour
#define RTN_ERRMANMADENOISE				103 // ERROR: Invalid Input Man-Made Noise
#define	RTN_ERRNOFOF2DATA				104 // ERROR: Invalid Input Missing foF2 array data
#define RTN_ERRNOM3KF2DATA				105 // ERROR: Invalid Input Missing M(3000)F2 array data
#define RTN_ERRNODUDDATA				106 // ERROR: Invalid Input Missing DuD array data
#define	RTN_ERRNOFAMDATA				107 // ERROR: Invalid Input Missing Fam array data
#define RTN_ERRNOFOF2VARDATA			108 // ERROR: Invalid Input Missing foF2 Variability array data
#define RTN_ERRSSN						109 // ERROR: Invalid Input Sun Spot Number
#define RTN_ERRMODULATION				110 // ERROR: Invalid Input Modulation
#define RTN_ERRFREQUENCY				111 // ERROR: Invalid Input Frequency
#define RTN_ERRBW						112 // ERROR: Invalid Input Bandwidth
#define RTN_ERRTXPOWER					113 // ERROR: Invalid Input Transmit Power
#define RTN_ERRSNRR						114 // ERROR: Invalid Input Required Signal-to-Noise ratio
#define RTN_ERRSIRR						115 // ERROR: Invalid Input Required Signal-to-Interference ratio
#define RTN_ERRF0						116 // ERROR: Invalid Input F0
#define RTN_ERRT0						117 // ERROR: Invalid Input T0
#define RTN_ERRA						118 // ERROR: Invalid Input Digital Modulation Amplitude ratio
#define RTN_ERRTW						119 // ERROR: Invalid Input Digital Modulation Time Window
#define RTN_ERRFW						120 // ERROR: Invalid Input Digital Modulation Frequency Window
#define RTN_ERRLTX						121 // ERROR: Invalid Input Transmit Location
#define RTN_ERRLRX						122 // ERROR: Invalid Input Receive Location
#define RTN_ERRRXANTENNAPATTERN			123 // ERROR: Invalid Input Receive Antenna Pattern
#define RTN_ERRTXANTENNAPATTERN			124 // ERROR: Invalid Input Transmit Antenna Pattern
#define	RTN_ERRSNRXXP					125 // ERROR: Invalid Input SNRXX Percentage
// END returns from ValidData()

// Return ERROR from AllocatePathMemory(), FreePathMemory() and InputDump()
#define RTN_ERRALLOCATEFOF2				131 // ERROR: Allocating Memory for foF2
#define RTN_ERRALLOCATEM3KF2			132 // ERROR: Allocating Memory for M(3000)F2
#define RTN_ERRALLOCATEFOF2VAR			133 // ERROR: Allocating Memory for foF2 Variability
#define RTN_ERRALLOCATETX				134 // ERROR: Allocating Memory for Tx Antenna Pattern
#define RTN_ERRALLOCATERX				135 // ERROR: Allocating Memory for Rx Antenna Pattern
#define RTN_ERRALLOCATEANT			    137 // ERROR: Allocating Memory for Antenna Pattern

// Return ERROR from ReadAntennaPatterns() ReadType13()
#define	RTN_ERRCANTOPENANTFILE	        138 // ERROR: Can Not Open Recieve Antenna File

// Return ERROR from ReadP1239()
#define RTN_ERRCANTOPENP1239FILE		139 // ERROR: Can Not Open foF2 Variability file "P1239-2 Decile Factors.txt"
#define RTN_ERRNOTP12393				140 // ERROR: Invalid P.1239-3 File

// Return ERROR from ReadIonParametersTxt()
#define RTN_ERRREADIONPARAMETERS		141 // ERROR: Can Not Open Ionospheric Parameters File


// Return OKAY > 10 and <= 20
#define RTN_ALLOCATEP533OK				11 // AllocatePathMemory()
#define RTN_PATHFREED					12 // PathMemory.c FreePathMemory(()
#define RTN_INPUTDUMPOK					13 // InputDump()
#define RTN_READIONPARAOK			    14 // ReadIonParameters()
#define RTN_READP1239OK					15 // ReadP1239()
#define RTN_READANTENNAPATTERNSOK		16 // ReadAntennaPatterns()
#define	RTN_VALIDDATAOK					17 // ValidPath()

#define	RTN_P533OK						10 // P533()

// End Returns ********************************************************************
// End Returns ********************************************************************

// Control point index names for readability
// These are defined from the sense of the short model
// Please note these change meaning when the long model is exclusively used
// i.e when the path->distance is > 9000 km. This is done for diagnostic purposes.
#define T1k		0	// T + 1000 (km)
// Note: Alternative use in long model penetration point closest to the trasmitter at the current hour
#define Td02	1	// T + d0/2 (km)
// Note: Alternative use in long model as T + dM/2
#define MP		2	// path mid-path (km)
#define	Rd02	3	// R - d0/2 (km)
// Note: Alternative use in lone model as R - dM/2
#define	R1k		4	// R - 1000 (km)
// Note: Alternative use in long model at last penetration point, 2*nL, at current hour

// foF2 variability index names for readability
#define WINTER	0
#define EQUINOX 1
#define SUMMER  2

// Decile flags
#define DL		0 // Lower decile
#define DU		1 // Upper decile

#define DAY		0 // DAY index for the rop array in MUFOperational()
#define NIGHT	1 // NIGHT index for the rop array in MUFOperational()

#define JAN		0
#define FEB		1
#define MAR		2
#define APR		3
#define MAY		4
#define JUN		5
#define JUL		6
#define AUG		7
#define SEP		8
#define OCT		9
#define NOV		10
#define DEC		11

// For the determination of the lowest order E and F2 mode
#define NOLOWESTMODE	99

// For the determination of the dominant mode
#define NODOMINANTMODE	99

// Noise calculation (See ITU-R P.372)
#define CITY		0.0
#define RESIDENTIAL 1.0
#define RURAL		2.0
#define QUIETRURAL	3.0
#define NOISY		4.0
#define	QUIET		5.0

// Modulation flags
#define ANALOG		0
#define DIGITAL		1

// Short or long path flags
#define SHORTPATH	0
#define LONGPATH	1

// Minimum Elevation Angle (degrees) for the Short model
#define MINELEANGLES 3.0
// Minimum Elevation Angle (degree) for the Long model
#define MINELEANGLEL 3.0

// Maximum Sun Spot Number
#define MAXSSN		160

// Maximum number of F2 modes
#define MAXF2MDS	6

// Maximum number of E modes
#define MAXEMDS		3

// Maximum number of modes
#define MAXMDS	MAXEMDS+MAXF2MDS

// Direction of the AntennaGain()
#define TXTORX	1
#define RXTOTX  2

// Testing
// E Layer Screening switch
#define NOELAYERSCREENING TRUE
// Testing

// End #define ************************************************************************************


// Structures *************************************************************************************

/*
 *	The naming convention of structures is that the first letter is capitalized and instances of that structure in other
 *	structures use all-cap abbreviations. Thus, a ControlPt structure in a mode structure is a CP_ and a location structure
 *	is a L or L_. This note may help as one delves into the code.
 *
 *  The CP[MP] structure in the path is a special midpoint control point.
 *
 *  The lowest index of the Md_F2 and Md_E structure arrays are to be the lowest order mode where the lowest order mode is
 *  the lowest index + 1
 *
 */

struct Location {
	double lat, lng;
};

struct SolarParameters {
	double ha;		// hour angle (radians)
	double sha;		// Sunrise/sunset hour angle (radians)
	double sza;		// Solar zenith angle (radians)
	double decl;	// Solar declination (radians)
	double eot;		// Equation of time (minutes)
	double lsr;		// local sunrise (hours)
	double lsn;		// local solar noon (hours)
	double lss;		// local sunset (hours)
};

struct ControlPt {
	struct Location L;
	double distance;// This is the distance (km) from the transmitter to the CP and not the hop range
	double foE;		// E layer critical frequency (MHz)
	double foF2;	// F2 layer critical frequency (MHz)
	double M3kF2;	// F2 layer critical frequency @ 3000 km (MHz)
	double dip[2];	// Magnetic dip (radians)
	double fH[2];	// Gyrofrequency (MHz)
	double ltime;	// Local time (hours)
	double hr;		// Mirror reflection point (km)
	double x;		// foE/foF2 ratio used in the calculation of the F2MUF
	// Solar parameters
	struct SolarParameters Sun;
};

struct Mode {
	// Define the myriad of MUFs
	double BMUF;	// Basic MUF (MHz). Typically there is no difference between the basic and the 50% MUF
					// The BMUF is checked to see if it is != 0.0 to determine if the mode exists
	double MUF90;	// MUF exceeded for 90% of the days of the month (MHz)
	double MUF50;	// MUF exceeded for 50% of the days of the month(MHz)
	double MUF10;	// MUF exceeded for 10% of the days of the month(MHz)
	double OPMUF;	// Operation MUF(MHz)
	double OPMUF10; // Operation MUF exceeded 10% of the days of the month(MHz)
	double OPMUF90; // Operation MUF exceeded 90% of the days of the month(MHz)
	double Fprob;	// Probability that the mode is supported at the frequency of interest
	double deltal;	// Lower decile for the MUF calculations
	double deltau;	// Upper decile for the MUF calculations
	// Other parameters associated with the mode
	double hr;		// Reflection height for the mode
	double fs;		// E-Layer screening frequency for F2 modes only(MHz)
	double Lb;		// < 9000 km path basic loss
	double Ew;		// < 9000 km field strength(dB(1 uV/m))
	double ele;		// Elevation angle
	double Prw;		// Receiver power (dBW)
	double Grw;		// Receive antenna gain (dBi)
	double tau;		// Time delay
	int MC;
};

struct Beam {
	double azm;		// Azimuth
	double ele;		// Elevation angle
	double G;		// Gain for the azimuth and elevation
};

struct Antenna {
	char Name[256];

	/*
	 * Int used to track the number of frequencies for which we have pattern data
	 * (e.g. the size of the freqs array).
	 */
	int freqn;

	/*
	 * An array to store the frequencies we have pattern data for.
	 */
	double *freqs;

	// 3D double pointer to the antenna pattern data
	// [freq_index][azimuth][elevation]
	// The following is assumed about the antenna pattern when the program is run:
	//		i) The orientation is correct. The antenna pattern is in the orientation as it would be on the Earth.
	//		ii) The data is valid. It is the responsibility of the calling program to ensure this.
	double ***pattern;
};

// Any "adjustment" to the contents of the structure PathData to make indices out of some of the variables, such as month and hour
// are done in InitializePath()

struct PathData {

	// User-provided Input ************************************************************************

	char name[256];		// The path name
	char txname[256];	// The transmitter name
	char rxname[256];	// The receiver name

	int year;
	int month;			// Note: This is 0 - 11
	int hour;			// Note: This is an hour index 0 - 23
						//       Where 1 - 24 UTC is required add one and rollover
	int SSN;			// 12-month smoothed sun sport number a.k.a. R12

	int Modulation;		// Modulation flag

	int SorL;			//  Short or long path switch

	double frequency;	// Frequency (MHz)
	double BW;			// Bandwidth (Hz)

	double txpower;		// Transmitter power (dB(1 kW))

	int SNRXXp;			// Required signal-to-noise ration (%) of the time (1 to 99)
	double SNRr;		// Required signal-to-noise ratio (dB)
	double SIRr;		// Required signal-to-interference ratio (dB)

	// Parameters for approximate basic circuit reliability for digital modulation
	double F0;			// Frequency dispersion at a level -10 dB relative to the peak signal amplitude
	double T0;			// Time spread at a level -10 dB relative to the peak signal amplitude

	// Parameters for digitial modulation performance
	double A;			// Required A ratio (dB)
	double TW;			// Time window (msec)
	double FW;			// Frequency window (Hz)

	struct Location L_tx, L_rx;
	struct Antenna A_tx, A_rx;

	// End User Provided Input *********************************************************************

	// Array pointers ******************************************************************************
	// The advantage of having these pointers in the PathData structure is that p533() can be
	// re-entered with the data allocations intact since they are determined and loaded externally
	// to p533(). This is done to make area coverage calculations, multiple hours and/or
	// any calculations that require the path be examined for another location or time within the
	// current month. If the month changes foF2 and M3kF2 will have to be reloaded, while the pointer
	// foF2var does not since it is for the entire year
	// Pointers to array extracted from the coefficients in ~/IonMap directory
	float ****foF2;			// foF2
	float ****M3kF2;		// M(3000)F2
	// Pointer to array extracted from the file "P1239-2 Decile Factors.txt"
	double *****foF2var;	// foF2 Variablity from ITU-R P.1239-2 TABLE 2 and TABLE 3

 	// End Array Pointers *************************************************************************

	// Calculated Parameters **********************************************************************
	int season;			// This is used for MUF calculations
	double distance;	// This is the great circle distance (km) between the rx and tx
	double ptick;		// Slant range
	double dmax;		// d sub max (km) determined as a function of the midpoint of the path and other parameter
	double B;			// Intermediate value when calculating dmax also determined at midpoint of the path
	double ele;			// For paths that are longer than 9000 km this is the composite elevation angle

	// MUFs
	double BMUF;	// Basic MUF (MHz)
	double MUF50;	// MUF exceeded for 50% of the days of the month (MHz)
	double MUF90;	// MUF exceeded for 90% of the days of the month (MHz)
	double MUF10;	// MUF exceeded for 10% of the days of the month (MHz)
	double OPMUF;	// Operation MUF (MHz)
	double OPMUF90; // OPMUF exceeded for 90% of the days of the month (MHz)
	double OPMUF10; // OPMUF exceeded for 10% of the days of the month (MHz)
	// Highest probable frequency, HPF, is 10% MUF (MHz)
	// Optimum working frequency, FOT, is 90% MUF (MHz)

	int n0_F2;		// Lowest order F2 mode ( 0 to MAXF2MODES )
	int n0_E;		// Lowest order E mode ( 0 to 2 )

	// Signal powers
	double Es;	// The overall resultant equivalent median sky-wave field strength for path->distance < 7000 km
	double El;	// The overall resultant median field strength for paths->distance > 9000 km
	double Ei;	// For paths->distance between 7000 and 9000 km the interpolated resultant median field strength
	double Ep;	// The Path Field Strength (dBu) Depending on the path distance this is either Es, El or Ei.
	double Pr;	// Median available receiver power

	// Short path (< 7000 km) parameters
	double Lz;		// 	"Not otherwise included" loss

	// Long path (> 9000 km) parameters
	double E0;		// The free-space field strength for 3 MW EIRP
	double Gap;		// Focusing on long distance gain (dB)
	double Ly;		// "Not otherwise included" loss
	double fM;		// Upper reference frequency
	double fL;		// Lower reference frequency
	double F;		// f(f, fH, fL, fM) in eqn 28 P.533-12
	double fH;		// Mean gyrofrequency
	double Gtl;		// Largest antenna gain in the range 0 to 8 degrees
	double K[2];	// Correction factor

	// Signal-to-noise ratio
	double SNR;	 // Median resultant signal-to-noise ratio (dB) for bandwidth b (Hz)
	double DuSN; // Upper decile deviation of the signal-to-noise ratio (dB)
	double DlSN; // Lower decile deviation of the signal-to-noise ratio (dB)

	// Signal-to-noise at the required reliability
	double SNRXX; //

	// Digitially modulated system stats
	double SIR;  // Signal-to-interference ratio (db)
	double DuSI; // Upper decile deviation of the signal-to-interference ratio (db)
	double DlSI; // Lower decile deviation of the signal-to-interference ratio (db)
	double RSN;  // Probability that the required SNR is achieved
	double RT;	 // Probability that the required time spread T0 is not exceeded
	double RF;	 // Probability that the required frequency spread f0 is not exceeded

	// Reliability
	double BCR;		// Basic circuit reliability
	double OCR;		// Overall circuit reliability without scattering
	double OCRs;	// Overall circuit reliability with scattering
	double MIR;		// Multimode interference
	double probocc; // Probability of scattering occuring

	// Antenna related parameters

	// Grw
	//	path->distance <= 7000 km
	//		Grw is the "lossless receiving antenna of gain Grw
	//		(dB relative to an isotropic radiator) in the direction of signal incidence"
	//		Grw will be the dominant mode gain
	//	path->distance >= 9000 km
	//		Grw is the "largest value of receiving antenna gain at the required azimuth in the
	//		elevation range 0 to 8 degrees."
	double Grw;

	// Transmitter EIRP
	double EIRP;

	// There are a maximum of 5 CP from P.533-12 Table 1d)
	// See #define above for "Control point index names for readability"
	struct ControlPt CP[5];

	// ITU-R P.533-12 5.2.1 modes considered "Up to three E modes (for paths up to 4000 km) and
	// up to six F2 modes are selected"
	// In part three of P.533-12 it would have been easier to make all nine modes in one array for digitally
	// modulated systems. To increase the readability and because the method often treats layers differently
	// the modes are separated by layer.
	struct Mode Md_F2[MAXF2MDS];
	struct Mode Md_E[MAXEMDS];

	// The following are conveniences for examining data
	// The variables *DMptr and DMidx are set in MedianAvailableReceiverPower()
	struct Mode *DMptr; // Pointer to the dominant mode
	int DMidx;			// Index to the dominant mode (0-2) E layer (3-8) F2 layer

	// Noise Structure
	struct NoiseParams noiseP;

	// P372.DLL Information
	char const *P372ver;		// P372() Version number
	char const *P372compt;		// P372() Compile time

	// End Calculated Parameters *****************************************************************************
};

// End Structures *********************************************************************************

// Prototypes *************************************************************************************

// Note: The arguments passed are by reference (pointer) if the subroutine changes the argument within it.
//       Otherwise arguments are passed by value. There are a few cases that the only reason that the arguments
//		 are passed by reference was because the next level program required it.
// Any subroutines prototyped here are used external to the file that contains them. There may be local subroutines in
// each program file, consult them for more details. These subroutines were developed as the code was being written
// in the order necessary. If the order is maintained then the correspondence will be be preserved between the code and
// the recommendation ITU-R P.533-12. In that regard the order of execution of the subroutines is important since
// calculations in P.533-12 build on one another.

// CalculateCPParameters.c Prototype
void CalculateCPParameters(struct PathData *path, struct ControlPt *here);
void SolarParameters(struct ControlPt *here, int month, double hour);
double BilinearInterpolation(double LL, double LR, double UL, double UR, double r, double c);
void IonosphericParameters(struct ControlPt *here, float ****foF2, float ****M3kF2, int hour, int SSN);
void FindfoE(struct ControlPt *here, int month, int hour, int SSN);

// Initialize.c Prototypes
//	Only three of the five control points are determined in InitializePath() T + 1000, M and R - 1000.
//	The control points T + d0/2 and R - d0/2  are determined in MUFBasic()
void InitializePath(struct PathData *path);

// P533.c Prototype for the P533 propagation model engine
DLLEXPORT int P533(struct PathData *path);
DLLEXPORT char const * P533Version(void);

// Geometry.c Prototypes
DLLEXPORT void GreatCirclePoint(struct Location here, struct Location there, struct ControlPt *midpnt, double distance, double fraction);
DLLEXPORT double GreatCircleDistance(struct Location here, struct Location there);
DLLEXPORT void GeomagneticCoords(struct Location here, struct Location *there);
DLLEXPORT double Bearing(struct Location here, struct Location there, int direction);

// ValidataPath.c Prototypes
int ValidatePath(struct PathData *path);

// magfit.c Prototype
void magfit(struct ControlPt *here, double height);

// MUFBasic Prototype
//	Note MUFBasic() determines the control points T + d0/2 and R - d0/2
void MUFBasic(struct PathData *path);
double CalcCd(double d, double dmax);
double CalcF2DMUF(struct ControlPt *CP, double distance, double dmax, double B);
double Calcdmax(struct ControlPt *CP);
double CalcB(struct ControlPt *CP);

// MUFVariability.c Prototype
void MUFVariability(struct PathData *path);
double FindfoF2var(struct PathData path, double hour, double lat, int decile);

// MUFOperational.c Prototype
void MUFOperational(struct PathData *path);

// ELayerScreeningFrequency.c Prototype
void ELayerScreeningFrequency(struct PathData *path);
double ElevationAngle(double dh, double hr);
double IncidenceAngle(double deltaf, double hr);

// MedianSkywaveFieldStrengthShort.c Prototype
void MedianSkywaveFieldStrengthShort(struct PathData *path);
double AntennaGain(struct PathData path, struct Antenna Ant, double delta, int direction);
void ZeroCP(struct ControlPt *CP);

// MedianSkywaveFieldStrengthLong.c Prototype
void MedianSkywaveFieldStrengthLong(struct PathData *path);
double AntennaGain08(struct PathData path, struct Antenna Ant, int direction, double * elevation);

// Between7000kmand9000km.c Prototypes
void Between7000kmand9000km(struct PathData *path);

// MedianAvailableReceiverPower.c Prototypes
void MedianAvailableReceiverPower(struct PathData *path);

// CircuitReliability.c Prototype
void CircuitReliability(struct PathData *path);

// PathMemory.c prototype
DLLEXPORT int AllocatePathMemory(struct PathData *path);
DLLEXPORT int FreePathMemory(struct PathData *path);
DLLEXPORT int AllocateAntennaMemory(struct Antenna *ant, int freqn, int azin, int elen);

// InputDump. c Prototype
DLLEXPORT int InputDump(struct PathData *path);

//Antenna file AND COEFFICIENT routines
DLLEXPORT int ReadType11(struct Antenna *Ant, FILE *fp, int silent);
DLLEXPORT int ReadType13(struct Antenna *Ant, FILE *fp, double bearing, int silent);
DLLEXPORT int ReadType14(struct Antenna *Ant, FILE *fp, int silent);
DLLEXPORT void IsotropicPattern(struct Antenna *Ant, double G, int silent);
DLLEXPORT int ReadIonParametersBin(int month, float ****foF2, float ****M3kF2, char DataFilePath[256], int silent);
DLLEXPORT int ReadIonParametersTxt(struct PathData *path, char DataFilePath[256], int silent) ;
DLLEXPORT int ReadP1239(struct PathData *path, const char * DataFilePath);
DLLEXPORT void SetAntennaPatternVal(struct PathData * path, int TXorRX, int azimuth, int elevation, double value);

//Testing Routines
DLLEXPORT int sizeofPathDataStruct(void);


// End Prototypes *********************************************************************************

// End P533 ***************************************************************************************
#endif // P533_H
