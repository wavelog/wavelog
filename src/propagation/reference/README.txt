***

# International Telecommunications Union

# Radiocommunications Sector (ITU-R)

# Working Party 3L - Ionospheric Propagation and Radio Noise

## ITURHFProp(), P533(), P372(), and ITURNoise()

Software methods for the prediction of the performance of HF circuits based on 
Recommendations ITU-R P.533-14 and P.372-15

***

## P533()

This program, presented as a dynamic linked library, provides methods for the prediction of available frequencies, signal levels, and the predicted reliability for analogue and digital-modulated HF systems, taking into account not only the signal-to-noise ratio but also the expected time and frequency spreads of the channel. This program calculates the HF path parameters that appear in Recommendation ITU-R P.533-14. 

Great care has been taken in this implementation to follow the ITU published standard P.533-14. The flow of this implementation was designed primarily for readability and clarity, with performance being the secondary goal. It is hoped that this code will illuminate the standard and be easily maintainable.  For the definition of the interface to this library, please see
below.

## P372()

This program, presented as a dynamic linked library, provides methods for the prediction of background levels of radio-frequency noise in the frequency range from 0.1 Hz to 100 GHz in accordance with Recommendation ITU-R P.372-14. The program takes into account noise from the atmosphere, galaxy, and man-made sources. The program is used by the program P533() as part of the required calculations.

## ITURHFProp()

This program's sole purpose is to act as an input and output data wrapper for P533(). This routine is as an example of how the P533() model can be used in other applications. Please refer to ITUHFProp() to determine how data is loaded and unloaded from P533() to conduct successfully HF circuit analysis.

### USAGE

```
ITURHFProp [Options] [Input File] [Output File]
    Options
        -s Silent Mode: Suppresses display output except for error messages
        -h Help: Displays help
        -v Version: Displays the version of ITURHFProp() and P533()

    Input File
        Full path name of the input file. 
        If no path is given the current directory is assumed.

    Output File
        Full path name of the output file. 
        Note: Existing files will be overwritten.
        If no output file is indicated a default output file, either a
        report or a path dump file will be created in
        the .\\Report file directory
```

### ITURHFProp input file parameters (meaning, units and limits):

The following input parameter fields are required to run ITURHFProp. 

* There is no required order that the input parameters must appear in the input file. The input file can have any name. 

* There are two output option a report that generated a comma separated values (CSV) text file and a Path dump text file.

* Comment lines in an ITURHFProp input file begin with two forward slashes "//"

* Blank lines are ignored.

* Certain string fields, such as the transmitter name, must appear between quotes.

* All paths to the data files, report files and antenna pattern files are included in quotes and must end in a forward or back slash depending of the OS.  The `""` below indicates that the input variable
is enclosed in double quotes.

| Input File Parameter Name | Data Type | Notes |
| --- | --- | --- |
| PathName |  "" | Text name of path |
| PathTXName | "" | Text name of transmitting site |
| Path.L_tx.lat | float | Transmit antenna latitude in decimal degrees -90.0 to 90.0 |
| Path.L_tx.lng | float | Transmit antenna longitude in decimal degrees -180.0 to 180.0 |
| TXAntFilePath | "" | Path and filename to transmitter antenna pattern or     "ISOTROPIC". If an antenna pattern file is desired it is in the format: "c:\provide_full_path_to_antenna_file\AntennaPatternName.xxx" |
| TXGOS | float | Transmit antenna gain offset (in dB), typically this is 0.0 |
| PathRXName | "" |Text name of receiving site |
| RXAntFilePath | "" | Path and filename to transmitter antenna pattern or "ISOTROPIC". If an antenna pattern file is desired it is in the format: "c:\provide_full_path_to_antenna_file\AntennaPatternName.xxx" |
| RXGOS | float | Receive antenna gain offset (in dB), typically this is 0.0  |
| AntennaOrientation | "" |Orientation of antenna, "TX2RX" when transmit antenna points to receive antenna or "ARBITRARY" when user defined. |
| Path.year | int | Path year, between 1900 and 2100 |
| Path.month | int | Path month, from 1 to 12 |
| Path.hour | int or list | Path hour in UTC, from 1 to 24. This can be a comma separated list of integers i.e, 1,4,7,18 |
| Path.SSN | int | Sunspot number R12, between 1 and 311 |
| Path.frequency | float or list | Frequency (MHz), between 1.6 and 30.0. This can be a comma separated list of decimal values i.e, 3.45, 11.553, 17.7756, 23.0008 |
| Path.txpower | float | Transmit power (dB(kW)), between -30.0 and 60.0 |
| Path.BW | float | Bandwidth (Hz), between 0.005 to 3000000.0 |
| Path.SNRr | float | Required Signal-to-noise ratio (dB), between -30.0 and 200.0 |
| Path.SNRXXp | int | Percent of month in which signal-to-noise exceed, between 1 and 99 | 
| Path.ManMadeNoise | "" | Path noise environment, "CITY", "RESIDENTIAL", "RURAL", "QUIETRURAL", "QUIET", "NOISY" or in dB, between 100.0 AND 200.0 |
| Path.SorL | "" | Path between the Transmitter and Receiver either "SHORTPATH" or "LONGPATH" |
| Path.Modulation | "" | Modulation type, "ANALOG" or "DIGITAL" |

The following six options (`Path.SIRr`, `Path.A`, `Path.TW`, `Path.FW`, `Path.T0` and `Path.F0`) are
applicable only when the `Path.Modulation` is "DIGITAL"

| Input File Parameter Name | Data Type | Note |
| --- | --- | --- |
| Path.SIRr | float | Required Signal-to-interference ratio (dB), between -30.0 and 200.0 |
| Path.A | float | Required Amplitude Ratio (dB), between 0.0 and 50.0 |
| Path.TW | float | Time window (ms), between 0.0 and 50.0 |
| Path.FW | float | Frequency window (Hz), between 0.0 and 1000.0 |
| Path.T0 | float | Time spread for simple BCR (ms), between 0.0 and 1000.0 | 
| Path.F0 | float | Frequency dispersion for simple BCR (Hz), between 0.0 and 1000.0 | 

Specification of the output report in the input file has a great many options.

| Input File Parameter Name | Data Type | Note |
| --- | --- | --- |
| RptFilePath | "" | "c:\provide_full_path_to\Reports\"<br/>The program outputs CSV text files starting with the prefix RPT for a report file and PDD for a path dump text file. <br/>A time stamped txt file is only created in the directory indicated by `RptFilePath` if no filename is given on the command line.<br/> The RPT or PDD filename format described below for `DUMPPATH`. <br/>The output OPTIONS defined by the input variable `RptFileFormat` are given below. |
| RptFileFormat | "OPTION1 [ \| OPTION2 \| OPTION3 \| OPTION4 \| OPTION5 ... ]" | |

The `RptFileFormat` indicates to ITURHFProp what data outputs are desired. The OPTIONs can be entered in any order, except OPTION `RPT_DUMPPATH` which must appear alone. The output file will indicate how the desired output parameters are ordered. The order that the desired output parameters appear in the output file is fixed.

The following report output data OPTIONs that can be configured in the input file are:

| OPTION | Note | 
| --- | --- |
| RPT_D             | Path distance (km) 
| RPT_DMAX          | Path dmax (km) and Slant Range (km)
| RPT_ELE           | Path elevation (rad)
| RPT_BMUF          | Path basic MUF (MHz)
| RPT_BMUFD         | BMUF deciles MUF50, MUF90 & MUF10
| RPT_OPMUF         | Operational MUF (MHz)
| RPT_OPMUFD        | OPMUF deciles OPMUF90, OPMUF10
| RPT_N0_F2         | Lowest order F2 layer mode
| RPT_N0_E          | Lowest order E layer mode
| RPT_E             | Field Strength (dB(1uV/m)) Es, El or Ei depending on distance
| RPT_PR            | Median received power, Pr, and the receive antenna gain, Grw
| RPT_NOISESOURCES  | Noise (Atmospheric) FaA, (Man-made) FaM & (Galactic) FaG (dB)
| RPT_NOISESOURCESD | DuA, DlA, DuM, DlM, DuG, & DlG Noise component deciles (dB)
| RPT_NOISETOTALD   | DuT & DlT Total noise deciles (dB)
| RPT_NOISETOTAL    | Total Noise, FamT (dB)
| RPT_SNR           | Signal-to-noise ratio (dB)
| RPT_SNRD          | SNR Deciles DuSN & DlSN
| RPT_SNRXX         | Signal-to-noise ratio (dB) at the desired reliability XX %
| RPT_SIR           | Signal-to-interference ratio (dB)
| RPT_SIRD          | SIR decile variations DuSI & DlSI
| RPT_RSN           | Digital modulation performance parameters RSN, RT & RF
| RPT_BCR           | Basic Circuit Reliability (%)
| RPT_OCR           | Overall Circuit Reliability (%) without the consideration of scattering
| RPT_OCRS          | Overall Circuit Reliability (%) considering of scattering and Probocc (%)
| RPT_MIR           | Digital Modulation Multimode interference ratio (%)
| RPT_RXLOCATION    | Receiver location
| RPT_DOMMODE       | Dominant Mode only relevant to short paths < 7000 km
| RPT_GRW           |For path distances <= 7000 km, Grw is the "lossless receiving antenna of gain (dB relative to an isotropic radiator) in the direction of signal incidence". Grw will be the dominant mode gain. <br/>For path distances >= 9000 km, Grw is the "largest value of receiving antenna gain at the required azimuth in the elevation range 0 to 8 degrees."
RPT_ESL             | Field Strength (dB(1uV/m)) Es and El
RPT_LONG            | Long path parameters Gap, E0, fM, fL, Ly and K[2]
RPT_ALL             | All of the above

### RPT_DUMPPATH

A Special report type called `RPT_DUMPPATH` can be used as OPTION1 with no
other OPTIONS selected. The OPTION, `RPT_DUMPPATH`, typically is used for
trouble shooting and for detailed analysis that may be required for point-to-
point links. This option generates a large amount of data.

`RPT_DUMPPATH` outputs most of the calculated values. Note the output from
this option is path length dependent. Note for links that are > 9000 km the
output printed by the OPTION, `RPT_DUMPPATH`, only applies to a small number
of the control and penetration points necessary to do the prediction.

The file created by the option is named **PDDddmmyy-hhnnss.txt** where the path data dump file is time stamped:

```
dd = day
mm = month
yy = year
hh = hour
nn = minute
ss = seconds
```

Examples of RptFileFormat settings.

```
RptFileFormat "RPT_E | RPT_PR | RPT_RXLOCATION | RPT_D | RPT_GRW"
RptFileFormat "RPT_DUMPPATH"
```

There are two ways to indicate the analysis area of the calculation

**Method One:** For the output geographical area to be examined all four corners of the analysis are must be specified. For point-to-point links all four corner coordinates are identical. The convention
for these coordinates is up is north and left is west.

```
LL.lat    Lower left latitude, decimal degrees, -90.0 to 90.0
LL.lng    Lower left longitude, decimal degrees, -180.0 to 180.0
LR.lat    Lower right latitude, decimal degrees, -90.0 to 90.0
LR.lng    Lower right longitude, decimal degrees, -180.0 to 180.0
UL.lat    Upper left latitude, decimal degrees, -90.0 to 90.0
UL.lng    Upper left longitude, decimal degrees, -180.0 to 180.0
UR.lat    Upper right latitude, decimal degrees, -90.0 to 90.0
UR.lng    Upper right longitude, decimal degrees, -180.0 to 180.0
```

**Method Two:** The analysis area is entered as two points of a rectangle by entering latitude and longitude of the South East and North West corners of the analysis rectangle

```
SE.lat    South East latitude, decimal degrees, -90.0 to 90.0
SE.lng    South East longitude, decimal degrees, -180.0 to 180.0
NW.lat    North West latitude, decimal degrees, -90.0 to 90.0
NW.lng    North West longitude, decimal degrees, -180.0 to 180.0
```

Latitude and Longitude increment step. Note: There are no lower increment limits. These input are only
relevant to areas and will be ignored for point-to-point calculations as defined by the bounding
area above.

```
latinc    Latitude increment step in decimal degrees, > 0.0
lnginc    Longitude increment step in decimal degrees, > 0.0
```

| Input File Parameter Name | Data Type | Note |
| --- | --- | --- |
|DataFilePath |" "| DataFilePath is a text string in the format: "c:\provide_full_path_to_data_directory\"<br/>This directory contains the base location of the required Coeff, P.1239 and IonMap files.|

### Examples and more information:

See "itu.in" for an example input file and "itu.out" for the corresponding output file in the
..\bin directory.

#### Minimum requirements:

Microsoft Windows 10 (Home Premium, 32 bit) or higher is recommended.
Your system may require the Microsoft Visual Studio Runtime.  The MSVC library can be found here (May 2024) 

https://support.microsoft.com/en-us/help/2977003/the-latest-supported-visual-c-downloads

Or please search the web for “Microsoft Visual Studio C++ Runtime Library”


### A brief description of the interface to P533.dll:

The program P533.dll has the following entry points:

```c
DLLEXPORT int P533(struct PathData *path)
DLLEXPORT const char * P533Version();
DLLEXPORT void GreatCirclePoint(struct Location here, struct Location there, struct ControlPt *midpnt, double distance, double fraction);
DLLEXPORT double GreatCircleDistance(struct Location here, struct Location there);
DLLEXPORT void GeomagneticCoords(struct Location here, struct Location *there);
DLLEXPORT double Bearing(struct Location here, struct Location there);
DLLEXPORT int AllocatePathMemory(struct PathData *path);
DLLEXPORT int FreePathMemory(struct PathData *path);
DLLEXPORT int InputDump(struct PathData *path);
```

An external program is necessary to run the P533.dll engine. An external program must create, manage
and then free the data memory interface to P533.dll. An external program is used to populate the
structure PathData, which is defined in p533.h. It is the responsibility of an external program to
insure that the user input data and static data arrays are loaded correctly. The P533.dll does limited I/O. P533.dll contains “helper” subroutines to read the F2 layer variability file “P1239-3 Decile Factors.txt” via ReadP1239() and user selected antenna files via ReadType13().
The P533.dll does validate the input data. The data validation in P533.dll insures that the user is
requesting calculations within the designed range of input parameters to the P.533-14 method.

The program ITURHFProp is an example of how an external program can set up and run the data necessary
for P533.dll calculations. It is suggest that those who have an interest in creating a management
program for custom P533.dll applications obtain a copy of the source for ITURHFProp from the ITU.

In addition to the user desired input parameters an external program must manage the following data
sets. The four sources of external data that are necessary to set up the structure PathData and
run P533()are:

1. **Ionospheric data or maps**.
This data is in the ~/IonMap directory which contains monthly median foF2 and M(3000)F2 data
at 1.5-degree latitude and longitude increments for low and high sunspot numbers. The data in
these files was generated by the methods in REC533(). The method to derive these maps is
derived from arrays of spherical harmonic coefficients, see Equation (3) of Recommendation
ITU-R P.1239-3. The coefficients in this form do not allow for contemporary data to be
incorporated into the maps easily. For this reason the ITU has freed this data in the form of
a map that can be updated.

2. **MUF variability**.
The file "P1239-2 Decile Factors.txt" is read in to get the decile factors related to MUF
variability. This data can been examined in Recommendation ITU-R P.1239-3

3. **Antenna data**.
The PathData structure requires the antenna pattern for the transmitter and receiver, in all
cases except when isotropic is chosen. At present this pattern is 360 degrees azimuth and 91
degrees elevation. This data typically can be found from NEC or Type 13 antenna data from the
VOACAP suite.

Typically as long as the month does not change, the input data arrays from the three sources need not
be changed. Only when the month changes during an analysis does any external data need to be read in
again by the external program. For instance, in the event that an analysis is run which straddles two
or more months, i) the ionospheric maps, and ii) the noise data, will need to be reloaded. 
In addition to main entry point into the P533.dll, P533(), the following programs can be used to
interface and troubleshoot the operations of an external management program. Please note the structure
definitions that appear in the following utility functions are defined in P533.h.

```c
DLLEXPORT const char * P533Version();
```

The program P533Version() takes no arguments and returns the version of P533.dll as string variable.

```c
DLLEXPORT void GreatCirclePoint(struct Location here, struct Location there, struct ControlPt *midpnt,
                                double distance, double fraction);
```

The program GreatCirclePoint() is a utility program that returns a point along the great circle path
between the Locations, here and there, at a fraction of the distance from here. Note the midpoint of
the path is included for circumstances where the path takes the long way round the Earth.

```c
DLLEXPORT double GreatCircleDistance(struct Location here, struct Location there);
```

The program GreatCircleDistance() determines the great circle distance from the Location here to the
Location there.

```c
DLLEXPORT void GeomagneticCoords(struct Location here, struct Location *there);
```

The Location here is converted to geomagnetic coordinates and returned as Location pointed to by
there.

```c
DLLEXPORT double Bearing(struct Location here, struct Location there);
```

The program Bearing() returns the radian bearing from the Location here to Location there.

```c
DLLEXPORT int AllocatePathMemory(struct PathData *path);
```

The program AllocatePathMemory() is designed to be used in external programs to allocate the structure
PathData. This program is used to create the data interface to P533.dll and returns a pointer to the
data structure. While this routine creates the data interface the program FreePathMemory() releases
the memory.

```c
DLLEXPORT int FreePathMemory(struct PathData *path);
```

The program FreePathMemory() is designed to release the memory that was created by
AllocatePathMemory().

```c
DLLEXPORT int InputDump(struct PathData *path);
```

The program InputDump() is a utility to print the contents of the path structure. This utility is
useful to determine that the path structure has been passed correctly to the P533.dll. All of the user
input data is printed as are the extremes of the data arrays.

The data that is required to be loaded into the structure PathData is in the sections “User-provided
Input” and “Array pointers” below. The section “Calculated Parameters” contains the parameters that
are returned by the calculations in subroutine P533(). A management program that runs P533.dll will
have access to all of these parameters. Please refer to ITURHFProp to see an example of how the
PathData structure can be interrogated to remove calculation results.

```c

struct PathData {

    // User-provided Input ************************************************************************

    char name[256];       // The path name
    char txname[256];     // The transmitter name
    char rxname[256];     // The receiver name

    int year;
    int month;            // Note: This is 0 - 11
    int hour;             // Note: This is an hour index 0 - 23
                          // Where 1 - 24 UTC is required add one and rollover
    int SSN;              // 12-month smoothed sun sport number a.k.a. R12
     
    int Modulation;       // Modulation flag

    int SorL;             // Short or long path switch

    double frequency;     // Frequency (MHz)
    double BW;            // Bandwidth (Hz)

    double txpower;       // Transmitter power (dB(1 kW))

    int SNRXXp;           // Required reliability    (%) (1 to 99)
    double SNRr;          // Required signal-to-noise ratio (dB)
    double SIRr;          // Required signal-to-interference ratio (dB)

    double ManMadeNoise;  // Man-made noise flag or set by the user


    // Parameters for approximate basic circuit reliability for digital modulation
    double F0;            // Frequency dispersion at a level -10 dB relative to the peak signal amplitude
    double T0;            // Time spread at a level -10 dB relative to the peak signal amplitude


    // Parameters for digital modulation performance
    double A;             // Required A ratio (dB)
    double TW;            // Time window (msec)
    double FW;            // Frequency window (Hz)

    struct Location L_tx, L_rx;
    struct Antenna A_tx, A_rx;


    // End User Provided Input *********************************************************************

    // Array pointers ******************************************************************************

    /*
    The advantage of having these pointers in the PathData structure is that p533() can be
    re-entered with the data allocations intact since they are determined and loaded externally
    to p533(). This is done to make area coverage calculations, multiple hours and/or
    any calculations that require the path be examined for another location or time within the
    current month. If the month changes foF2 and M3kF2 will have to be reloaded, while the pointer
    foF2var does not since it is for the entire year
    Pointers to array extracted from the coefficients in ~/IonMap directory
    */

    float ****foF2;         // foF2
    float ****M3kF2;        // M(3000)F2


    // Pointer to array extracted from the file "P1239-2 Decile Factors.txt"
    double *****foF2var;    // foF2 Variablity from ITU-R P.1239-2 TABLE 2 and TABLE 3

    // End Array Pointers *************************************************************************


    // Calculated Parameters **********************************************************************
    int season;            // This is used for MUF calculations
    double distance;       // This is the great circle distance (km) between the rx and tx
    double ptick;          // Slant range
    double dmax;           // d sub max (km) determined as a function of the midpoint of the path and other parameter

    double B;              // Intermediate value when calculating dmax also determined at midpoint of the path

    double ele;            // For paths that are longer than 9000 km this is the composite elevation angle MUFs

    double BMUF;           // Basic MUF (MHz)
    double MUF50;          // MUF exceeded for 50% of the days of the month (MHz)
    double MUF90;          // MUF exceeded for 90% of the days of the month (MHz)
    double MUF10;          // MUF exceeded for 10% of the days of the month (MHz)
    double OPMUF;          // Operation MUF (MHz)
    double OPMUF90;        // OPMUF exceeded for 90% of the days of the month (MHz)
    double OPMUF10;        // OPMUF exceeded for 10% of the days of the month (MHz)

    // Note that the Highest probable frequency, HPF, is 10% MUF (MHz) and the Optimum working
    // frequency, FOT, is 90% MUF (MHz)

    int n0_F2;             // Lowest order F2 mode ( 0 to MAXF2MODES )
    int n0_E;              // Lowest order E mode ( 0 to 2 )

    // Signal powers
    double Es;             // The overall resultant equivalent median sky-wave field strength for path->distance < 7000 km
    double El;             // The overall resultant median field strength for paths->distance > 9000 km
    double Ei;             // For paths->distance between 7000 and 9000 km the interpolated resultant median field strength
    double Ep;             // The Path Field Strength (dBµ) Depending on the path distance this is either Es, El or Ei.
    double Pr;             // Median available receiver power

      // Long path (> 9000 km) parameters
    double E0;             // The free-space field strength for 3 MW EIRP
    double Gap;            // Focusing on long distance gain (dB)
    double Ly;             // "Not otherwise included" loss
    double fM;             // Upper reference frequency
    double fL;             // Lower reference frequency
    double F;              // f(f, fH, fL, fM) in eqn 28 P.533-14
    double fH;             // Mean gyrofrequency
    double Gtl;            // Largest antenna gain in the range 0 to 8 degrees
    double K[2];           // Correction factor

      // Signal-to-noise ratio
    double SNR;            // Median resultant signal-to-noise ratio (dB) for bandwidth b (Hz)
    double DuSN;           // Upper decile deviation of the signal-to-noise ratio (dB)
    double DlSN;           // Upper decile deviation of the signal-to-noise ratio (dB)

    // Signal-to-noise at the required reliability
    double SNRXX;          // Signal-to-noise at the required reliability

      // Digitially modulated system stats
    double SIR;            // Signal-to-interference ratio (dB)
    double DuSI;           // Upper decile deviation of the signal-to-interference ratio (dB)
    double DlSI;           // Lower decile deviation of the signal-to-interference ratio (dB)
    double RSN;            // Probability that the required SNR is achieved
    double RT;             // Probability that the required time spread T0 is not exceeded
    double RF;             // Probability that the required frequency spread f0 is not exceeded

      // Reliability
    double BCR;            // Basic circuit reliability
    double OCR;            // Overall circuit reliability without scattering
    double OCRs;           // Overall circuit reliability with scattering
    double MIR;            // Multimode interference
    double probocc;        // Probability of scattering occurring

    // Antenna related parameters:

    double Grw;            // path->distance <= 7000 km
                           // Grw is the "lossless receiving antenna of gain Grw
                           // (dB relative to an isotropic radiator) in the direction of signal
                           // incidence"
                           // Grw will be the dominant mode gain
                           // path->distance >= 9000 km
                           // Grw is the "largest value of receiving antenna gain at the required
                           // azimuth
                           // in the elevation range 0 to 8 degrees."

    double EIRP;           // Transmitter EIRP


    struct ControlPt CP[5]; // There are a maximum of 5 CP from P.533-14 Table (1d)

    // ITU-R P.533-14 5.2.1 modes considered "Up to three E modes (for paths up to 4000 km) and
    // up to six F2 modes are selected"
    // In part three of P.533-14 it would have been easier to make all nine modes in one array for
    // digitally modulated systems. To increase the readability and because the method often treats
    // layers differently, the modes are separated by layer. The lowest index of the Md_F2 and
    // Md_E structure arrays are to be the lowest order mode where the lowest order mode is the lowest
    // index + 1

    struct Mode Md_F2[MAXF2MDS];
    struct Mode Md_E[MAXEMDS];

    // The following are conveniences for examining data
    // The variables *DMptr and DMidx are set in MedianAvailableReceiverPower()
    struct Mode *DMptr;       // Pointer to the dominant mode
    int DMidx;                // Index to the dominant mode (0-2) E layer (3-8) F2 layer

    // End Calculated Parameter **************************************************************************
};
```

The structure PathData above also contains other structures which are defined as:

```c
struct Location {
    double lat, lng;
};

struct SolarParameters {
    double ha;         // hour angle (radians)
    double sha;        // Sunrise/sunset hour angle (radians)
    double sza;        // Solar zenith angle (radians)
    double decl;       // Solar declination (radians)
    double eot;        // Equation of time (minutes)
    double lsr;        // local sunrise (hours)
    double lsn;        // local solar noon (hours)
    double lss;        // local sunset (hours)
};

struct ControlPt {
    struct Location L;
    double distance;  // This is the distance (km) from the transmitter to the CP and not the hop range
    double foE;       // E layer critical frequency (MHz)
    double foF2;      // F2 layer critical frequency (MHz)
    double M3kF2;     // F2 layer critical frequency @ 3000 km (MHz)
    double dip[2];    // Magnetic dip (radians)
    double fH[2];     // Gyrofrequency (MHz)
    double ltime;     // Local time (hours)
    double hr;        // Mirror reflection point (km)
    double x;         // foE/foF2 ratio used in the calculation of the F2MUF

    // Solar parameters
    struct SolarParameters Sun;
};

struct Mode {
    // Define the myriad of MUFs
    double BMUF;        // Basic MUF (MHz). Typically there is no difference between the basic and
                        // the 50% MUF
                        // The BMUF is checked to see if it is != 0.0 to determine if the mode exists
    double MUF90;       // MUF exceeded for 90% of the days of the month (MHz)
    double MUF50;       // MUF exceeded for 50% of the days of the month(MHz)
    double MUF10;       // MUF exceeded for 10% of the days of the month(MHz)
    double OPMUF;       // Operation MUF(MHz)
    double OPMUF10;     // Operation MUF exceeded 10% of the days of the month(MHz)
    double OPMUF90;     // Operation MUF exceeded 90% of the days of the month(MHz)
    double Fprob;       // Probability that the mode is supported at the frequency of interest
    double deltal;      // Lower decile for the MUF calculations
    double deltau;      // Upper decile for the MUF calculations

    // Other parameters associated with the mode
    double hr;          // Reflection height for the mode
    double fs;          // E-Layer screening frequency for F2 modes only(MHz)
    double Lb;          // < 9000 km path basic loss
    double Ew;          // < 9000 km field strength(dB(1 µV/m))
    double ele;         // Elevation angle
    double Prw;         // Receiver power (dBW)
    double Grw;         // Receive antenna gain (dBi)
    double tau;         // Time delay
    in MC;              // Mode Considered Flag (TRUE/FALSE)
};

struct Beam {
    double azm;          // Azimuth
    double ele;          // Elevation angle
    double G;            // Gain for the azimuth and elevation
};

struct Antenna {
    char Name[256];
    // 2D double pointer to the antenna pattern data
    // The following is assumed about the antenna pattern when the program is run:
    //    i) The orientation is correct. The antenna pattern is in the orientation as it would
    //     be on the Earth.
    //    ii) The data is valid. It is the responsibility of the calling program to ensure
    //      this.
    double **pattern;
};
```

In the above some of the uppercase words are C defines that represent numbers and are used in the code
as an alias for readability.

### A brief description of the interface to P372.dll:

The program P372.dll has the following entry points:

```c
// Prototypes
DLLEXPORT int AllocateNoiseMemory(struct NoiseParams *noiseP);
DLLEXPORT int FreeNoiseMemory(struct NoiseParams *noiseP);
DLLEXPORT int Noise(struct NoiseParams *noiseP, int hour, double lng, double lat, double frequency);
DLLEXPORT int ReadFamDud(struct NoiseParams *noiseP, const char *DataFilePath, int month);
DLLEXPORT void InitializeNoise(struct NoiseParams *noiseP);
DLLEXPORT char const * P372CompileTime();
DLLEXPORT char const * P372Version();
```

An external program is necessary to run the P372.dll engine. An external program must create, manage
and then free the data memory interface to P372.dll. An external program is used to populate the
structure NoiseParams, which is defined in noise.h. It is the responsibility of an external program to
insure that the user input data and static data arrays are loaded correctly. The P372.dll does limited I/O. P372.dll contains subroutines to read the ITU harmonized coefficient files. The files required are part of this package and are named COEFFXX.TXT, where XX is a two-digit number for the month. P372.dll uses the month dependent noise coefficient values for the fakp[][], fakabp[][], dud[][][] and fam[][] used in the calculation of atmospheric noise. 

The atmospheric noise data arises from the mixed coefficient files, COEFFXX.TXT, that were compiled by Dambolt and Suessman. These files contain the original spherical harmonic coefficients for foF2 and M(3000)F2 in addition to data related to atmospheric noise, layer thickness, MUF statistics, etc. for a particular month. Some of this data appears to be deprecated. 

Although P372.dll is called from P533.dll it can be executed independently if its data structures are loaded correctly for the calculation. 

```c
// Struct Definitions

struct FamStats {
	int tmblk;          // Timeblock
	double FA;          // Atmospheric noise in dB above kT0b at 1 MHz
	double SigmaFam;    // Standard deviation of values, Fam
	double Du;          // Ratio of upper decile to median value, Fam
	double SigmaDu;     // Standard deviations of values of Du
	double Dl;          // Ratio of median value, Fam, to lower decile
	double SigmaDl;     // Standard deviation of values of Dl
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
```

### Return Codes ITURHFProp,P533 and P372

When running ITURHFProp the typical return code is 1000. This indicates that the program has executed
correctly. In the event that an error occurs the program will exit with one of the following error
numbers. The following list may be useful to determine the type of error. Please note that in the event
that the calculation by P533() fails ITURHFProp() will exit with the appropriate P533() error.


There are two ranges of return codes from P533() and ITURHFProp()

Returns from P533() are greater than or equal to 0 and less than 200
    Return numbers less than 100 are normal and indicate no error in processing
    Return numbers greater than 100 and less than 200 are errors

Returns from P533() are greater than or equal to 0 and less than 210
    Return numbers less than 10 are normal and indicate no error in processing
    Return numbers greater than 200 and less than 210 are errors    Returns from ITURHFProp() are greater than or equal to 1000 and less than 1200
    Return numbers greater than or less than 1000 and less than 1100 are normal and indicate
    no error in processing
    Return numbers greater than 1000 and less than 1200 are errors

P533(): Return numbers less than 100 are normal and indicate no error in processing

    0    NO ERROR:    P533() Normal Exit
    1    NO ERROR:    AllocatePathMemory()
    2    NO ERROR:    PathMemory.c FreePathMemory(()
    3    NO ERROR:    InputDump()
    4    NO ERROR:    ReadIonParameters()
    5    NO ERROR:    ReadP1239()
    6    NO ERROR:    ReadAntennaPatterns()
    7    NO ERROR:    ReadAntennaPatterns()
    8    NO ERROR:    ValidPath()

P533(): Return numbers greater than 100 and less than 200 are errors

    100    ERROR:    Invalid Input Year
    101    ERROR:    Invalid Input Month
    102    ERROR:    Invalid Input Hour
    103    ERROR:    Invalid Input Man-Made Noise
    104    ERROR:    Invalid Input Missing foF2 array data
    105    ERROR:    Invalid Input Missing M(3000)F2 array data
    106    ERROR:    Invalid Input Missing DuD array data
    107    ERROR:    Invalid Input Missing Fam array data
    108    ERROR:    Invalid Input Missing foF2 Variability array data
    109    ERROR:    Invalid Input Sun Spot Number
    110    ERROR:    Invalid Input Modulation
    111    ERROR:    Invalid Input Frequency
    112    ERROR:    Invalid Input Bandwidth
    113    ERROR:    Invalid Input Transmit Power
    114    ERROR:    Invalid Input Required Signal-to-Noise ratio
    115    ERROR:    Invalid Input Required Signal-to-Interference ratio
    116    ERROR:    Invalid Input F0
    117    ERROR:    Invalid Input T0
    118    ERROR:    Invalid Input Digital Modulation Amplitude ratio
    119    ERROR:    Invalid Input Digital Modulation Time Window
    120    ERROR:    Invalid Input Digital Modulation Frequency Window
    121    ERROR:    Invalid Input Transmit Location
    122    ERROR:    Invalid Input Receive Location
    123    ERROR:    Invalid Input Receive Antenna Pattern
    124    ERROR:    Invalid Input Transmit Antenna Pattern
    125    ERROR:    Invalid Input Require Reliability
    130    ERROR:    Allocating Memory for foF2 Array
    131    ERROR:    Allocating Memory for M(3000)F2 Array
    132    ERROR:    Allocating Memory for foF2 Variability
    133    ERROR:    Allocating Memory for Tx Antenna Pattern
    134    ERROR:    Allocating Memory for Rx Antenna Pattern
    135    ERROR:    Allocating Memory for Noise
    140    ERROR:    Antenna File Format Error (Type 13)
    141    ERROR:    Can Not Open Receive Antenna Array
    160    ERROR:    Can Not Open foF2 Variability file "P1239-2 Decile Factors.txt"
    161    ERROR:    Invalid P.1239-3 File
    170    ERROR:    Can Not Open Ionospheric Parameters File
    180    ERROR:    Can Not Open P372.DLL

P372(): Return numbers less than 10 are normal and indicate no error in processing

    1    NO ERROR:    AllocatePathMemory()
    2    NO ERROR:    ReadFamDud()
    3    NO ERROR:    FreeNoiseMemory()
    4    NO ERROR:    Noise()
    5    NO ERROR:    Noise() Man-made noise override

P372(): Return numbers greater than 200 and less than 210 are errors

    201    ERROR:    Can Not Open Coefficient File
    202    ERROR:    Allocating Memory for DuD
    203    ERROR:    Allocating Memory for Fam
    204    ERROR:    Allocating Memory for FakP
    205    ERROR:    Allocating Memory for FakABP

ITURHFProp(): Return numbers greater than or less than 1000 and less than 1100 are normal and indicate no error in processing

    1000    NO ERROR:    Okay Calculation Completed 
    1001    NO ERROR:    ITURHFProp()
    1002    NO ERROR:    ValidateITURHFP()
    1003    NO ERROR:    ReadInputConfiguration()

ITURHFProp(): Return numbers greater than or less than 1000 and less than 1100 are errors

    1100    ERROR:    Can Not Open Output File
    1101    ERROR:    Can Not Find P533.DLL
    1102    ERROR:    Can Not Open Receive Antenna File
    1103    ERROR:    Can Not Open Transmit Antenna File
    1104    ERROR:    Antenna Orientation    
    1105    ERROR:    Transmit Bearing 
    1106    ERROR:    Receive Bearing
    1107    ERROR:    Receive Gain Offset
    1108    ERROR:    Transmit Gain Offset
    1109    ERROR:    Invalid Lower Left Latitude
    1110    ERROR:    Invalid Lower Right Latitude
    1111    ERROR:    Invalid Upper Left Latitude
    1112    ERROR:    Invalid Upper Right Latitude
    1113    ERROR:    Invalid Lower Left Longitude
    1114    ERROR:    Invalid Lower Right Longitude
    1115    ERROR:    Invalid Upper Left Longitude
    1116    ERROR:    Invalid Upper Right Longitude
    1117    ERROR:    Invalid Area Left Latitude
    1118    ERROR:    Invalid Area Right Latitude
    1119    ERROR:    Invalid Area Left Longitude
    1120    ERROR:    Invalid Area Right Longitude
    1121    ERROR:    Invalid Area Lower Latitude
    1122    ERROR:    Invalid Area Upper Latitude
    1123    ERROR:    Invalid Area Left Longitude
    1124    ERROR:    Invalid Area Right Longitude
    1200    ERROR:    Invalid Command Line 
    1201    ERROR:    Missing Input File

### ITURNoise Description - P372.dll driver program and associated tools for generation P.372-14 Section 5 style figures

At present the ITURNoise can be found here:

```
ITU-R-HF\P372\Src\ITURNoise
```
The tools associated with generating the plots from P.372-14 are found here:

```
ITU-R-HF\P372\Src\AtmosPlots
```

### Recommendation P.372 Calculation Engine and Associated Utility Programs
This document is a brief description of P372.DLL, ITURNoise.exe, and MakeP732figs.py programs. Please consult the source of ITURNoise() 
to see more details of the interfacing to the P372.DLL. (See https://github.com/ITU-R-Study-Group-3/ITU-R-HF). 
This document is provided as an overview of these programs and an initial descripting of their use. 

P372.DLL
Functions within P372.DLL can be accessed from either the _cdecl or __stdcall calling conventions. 

```c
__cdecl Calling convention
    AllocateNoiseMemory
    AtmosphericNoise_LT
    FreeNoiseMemory
    InitializeNoise
    MakeNoise
    Noise
    P372CompileTime
    P372Version
    ReadFamDud

__stdcall Calling convention
    __AllocateNoiseMemory@4
    __AtmosphericNoise_LT@36
    __FreeNoiseMemory@4
    __InitializeNoise@4
    __MakeNoise@52
    __Noise@32
    __P372CompileTime@0
    __P372Version@0
    __ReadFamDud@12
```

The input parameters to these subroutines are in the following forms.

```c
int AllocateNoiseMemory(struct NoiseParams *noiseP);
int FreeNoiseMemory(struct NoiseParams *noiseP);
int Noise(struct NoiseParams *noiseP, int hour, double lng, double lat, double frequency);
int ReadFamDud(struct NoiseParams *noiseP, const char *DataFilePath, int month);
void InitializeNoise(struct NoiseParams *noiseP);
char const * P372CompileTime();
char const * P372Version();
void AtmosphericNoise_LT(struct NoiseParams* noiseP, struct FamStats* FamS, int lrxmt, double lng, double lat, double frequency);
int MakeNoise(int month, int hour, double lat, double lng, double freq, double mmnoise, char* datafilepath, double* out, int pntflag);
```

For additional details please see noise.h in the ITU-R-HF repo. 


### ITURNoise.exe
The program ITURNoise.exe is a utility program that has two modes: 

- Mode 1 which returns the combined total noise, total noise upper decile, 
    and total noise lower decile., and the constituent parts of the total noise: 
    galactic, atmospheric, and man-made noise calculation or

- Mode 2 generates the data necessary to create Recommendation P.372-14 Figures 13 through 36: a), b), and c)

### Mode 1 – Noise Calculation for a Single Location
To return the noise parameters for a single location, Mode 1 above, 7 command line arguments are required. Below is an example of running ITURNoise.exe to return noise for a single location

```
C:\>ITURNoise 1 14 1.0 40.0 165.0 0 "G:\User\Data\" 0
```

- Argument 1 is an integer that indicates the month (1 to 12) of interest. In the example above the desired month is January. 
- Argument 2 is an integer that indicates the hour (1 to 24) UTC at the receive point. In most cases this will require the calculation of the time UTC from the longitude at that receive point. In the example above the receive point is 11 hours (165? E/(15?/hr) ahead of UTC so to determine the noise for the 12th local time hour the 1st hour UTC is chosen. 
- Argument 3 is a float that indicates the latitude (degrees). In the example above the latitude desired is 40? N. 
- Argument 4 is a float that indicates the longitude (degrees). In the example above the longitude desired is 165? E
- Argument 5 is a float that indicates the man-made noise which can either be categorical (0-5) or value of man-made noise in dB (represented by a negative number). In the example above the value 1.0 indicates that the noise category residential. The category codes appear below. 

    |code|Noise Category|
    |----|--------------|
    | 0.0    | City|
    | 1.0    | Residential|
    | 2.0    | Rural|
    | 3.0    | Quiet Rural|
    | 4.0    | Noisy|
    | 5.0    | Quiet|

    If argument 5 is give as a negative number, the man-made noise will be set to that value where the galactic and atmospheric noise calculations are not performed. This second mode is of little value for this stand-alone program, ITURNoise.exe, running, P533.dll. Overriding the P372 calculation is included here and is consistent with some analyses desired in ITURHFProp which also uses the P372.dll.

- Argument 6 indicates the location of the required CCIR data files. The data file path must be given as a string enclosed in double quotes without trailing back slash. In the example above the location of the required CCIR data files are on drive C: in the directory Data, “C:\Data”

- Argument 7 allows the program to provide return data from the P.372 calculation is several formats. The table below gibes the integer print flag codes. 

    |code|        Print Mode|
    |----|----------------|
    ||No argument    Prints detailed output to stdout
    |0        |No output|
    |1        |Prints detailed outpuit to stderr|
    |2        |Prints detailed output to file “MakeNoiseOut.txt”|
    |3        | Prints detailed header, column description, and CSV output to stdout|
    |4        |Prints CSV output to stdout|

Example ITURNoise() Output

```
**********************************************************
ITU-R Study Group 3: Radiowave Propagation
**********************************************************
Analysis: 1/7/20 - 12:47:04
P372 Version:      14.2
P372 Compile Time: Wed Jul  1 11:00:38 2020
**********************************************************

JANUARY  : 14 (UTC)
40.0000 (deg lat) 165.0000 (deg long)
1.000 (MHz)

[FaA]  Noise Component (Atmospheric): 60.733
[DuA]  Upper Decile    (Atmospheric): 10.601
[DlA]  Upper Decile    (Atmospheric): 8.278
[FaM]  Noise Component    (Man-Made): 76.800
[DuM]  Upper Decile       (Man-Made): 11.000
[DlM]  Lower Decile       (Man-Made): 6.700
[FaG]  Noise Component    (Galactic): 52.000
[DuG]  Upper Decile       (Galactic): 2.000
[DlG]  Lower Decile       (Galactic): 2.000
[FamT] Noise                 (Total): 76.987
[DuT]  Upper Decile          (Total): 10.940
[DlT]  Lower Decile          (Total): 6.574

**********************************************************
```

### Mode 2 – Generate Atmospheric Noise Figure Data Output
There is one command line argument required for Mode 2, the data file path in quotes without the trailing back slash (Please see the description of Mode1 Argument 6 above). 

The following example would generate the figure data in the root directory.

```
C:\>ITURNoise "G:\User\Data"
```

Mode 2 creates the following directory structure in the current directory or directory where it is run.

```
.\ ----- P372_figures --|-- a ----- csv
                        |
                        |-- b ----- csv
                        | 
                        |-- c ----- csv
```

There are 72 figure data files that are created, 24 per category a), b), and c). The naming convention for the output files follow x_ymzh.csv, where x is the figure data type (a, b, or c), y is the month (1, 4, 7, or 10), and h is the local time hour (0, 4, 8, 12, 16, or 20).


### MakeP372figs.py

MakeP372figs.py was developed for Python 3.7.7 and is dependent on the Python libraries os, glob, numpy, cartopy, matplotlib, math, and pandas. At present MakeP372figs.py only runs on Windows. 

#### Generation of the Recommendation P.372-14 Figure files

The data files, and P372_figures directory structure, are required as input for the Python program MakeP372figs.py. When the program MakeP372figs.py is run from a directory, that immediately contains the directory structure P372_figures above, the following output directories will be created 

```
.\ ----- P372_figures --|-- a --|-- svg
                        |       |-- png
                        |       |-- pdf
                        |
                        |-- b --|-- svg
                        |       |-- png
                        |       |-- pdf
                        |
                        |-- c --|-- svg
                                |-- png
                                |-- pdf
```

MakeP372figs.py writes each figure in three common formats: scalable vector graphics (SVG), portable networks graphics (PNG), and portable document format (PDF). The later two are produced at 300 dpi.

Note that the P.372-14 13-36 a) Figures are labeled relative season in the northern hemisphere. The figures generated from MakeP372figs.py are now labeled with the central month of the relative northern hemisphere season. 

|N. Hemisphere Season (P.372-14 Figures)|Months|
|---------------------------------------|------|
| Winter |DEC-JAN-FEB|
| Spring |MAR-APR-MAY|
| Summer |JUN-JUL-AUG|
| Autumn |SEP-OCT-NOV|

Specifically, the a) figure data generated by ITUNoise() in Mode 2 are January, April, July, and October. 

Also note the a) Figures created by MakeP372figs.py have longitude range from 180 degrees west longitude to 180 degrees east longitude instead of the current a) Figure range from 60 degrees west longitude to 60 degrees east longitude. 

The b) and c) Figures are very similar to the published figures in P.372-14 with the exception that the new c) Figures no longer include V_dm, “Expected value of median deviation of average voltage.” 


### Version 14 Notes:

#### Version 14.3

Most of the corrections in this release concern ITURNoise, P372.dll and the python file MakeP372Figs. 

- Compiled on Visual Studio 2022
- ITURNoise project was moved to the Combined.sln file
- Corrections to ITURNoise driver and MakeP372Figs.py
    - The figures that are created by MakeP372Figs are in section 5 of P.372-14. The main reoutine to generate the atmospheric noise from the CCIR coefficent file had an error that was pointed out by Harvey Berger. Thanks Harvey!
    - The time blocks were not used correctly in AtmosphericNoises.c
- Corrected ITUNoise Driver outputting SigmaDU twice to csv for the figures.
- Added V_d and sigma_V_d in Atmospheric noise routine
- Now included x64 output DLL and EXE files.
- Corrections to Linux build
- Fixed Issue #17 reported by lzhengp1986

#### Version 14.1 and 14.2

Efforts since the last release include numerous editorial changes, simplifications, reorganizations, modifications and corrections to memory management, and general bug fixes which are important but do not affect the operation of the calculations. The release notes below contain a summary of those changes which may effect the usefulness of the program and/or the operational work flow of analyses that use ITURHFProp (and its constituent subroutines). 

Special WP 3L thanks to Mr. James Watson for the extraordinary work to make ITURHFProp more useful in general, work to make sure that Linux users of ITURHFProp maintain access, and continued efforts to assure that the program astutely and accurately manages HF antenna patterns. In that regard significant changes were made to streamline the program for the Linux environment while maintaining consistency between Windows and Linux. Much work has been included to make ITURHFProp more easily adapted to both legacy and NEC based antenna patterns. The input of the analysis area has been simplified to be defined by just the South East and North West corners. Equivalence of ARBITRARY and MANUAL for user antenna bearing selection. And most significantly the rearchitecture of ITURHFProp so that the P372 noise calculation can stand alone. Thus noise method going forward after this release can be an independent calculation. 

An abbreviated summary of Changes since the last revision

- Edited Rec P.533-14 Eqn (9) and (10) now F_prob range 0 < F_prob <= 1
- Changed default SNRXX to 99
- Fixed a typo in the P1239 file. '132.' should be '1.32'.
- Added the -c and -t options to the help message.
- Fixed bug with a misplaced return statement that was causing failures when using isotropic antennas.
- Applied the max gain value figure to the Type 14 antennas as well. In most cases this won't be required as it tends to be 0.0.
- Added the Max gain value to the relative gains to make the const17 type of antenna work properly.
- Added a note about the csv option to the man page.
- Added link to RFC4180.
- Added supports for type 11 antennas and checks the antenna type by opening the file and passing a file pointer before calling the appropriate file parser.
- Added Antenna Type 14 Support
- Modified the transmit antenna to accept the n14/N14 names.
- Updated to reflect the use of N14/n14 to denote type 14 antennas.
- Changed Type 14 Antenna File Extension to .n14/.N14
NEC2 uses the file extension 'N14' to denote type 14 antennas. It seems best to use this existing (albeit informal) standard than to try and impose a new standard on users.
- Read the frequency from the file instead of the loop counter as this may be better if we start to handle files that don't comprise 30 ordered frequency blocks.
- Used the variable freqn when calling AllocateAntennaMemory() as this may be clearer.
- Now accepts Upper and Lower Case Suffixes
- Type 14 antennas may be called up with either t14 or T14 file suffixes.
- Changed Antenna.numFreqs to Antenna.freqn to align better with existing variable names.
- Removed the redundant 'bearing' arg from the ReadType14() call.
- Removed a number of debug print statements that we added to assist debugging Type 14 antennas.
- Added support for multiple frequency patterns and type 14 antennas.
- Removed the relative filepath spec from the libp372 so that we load it from the lib dir.
- Added install-data target to copy the data files to /usr/local/share/p533/data.
- Rebase the new architecture for ITURHFProp.
- The noise method P.372 is now independently callable from ITURHFProp.
- AntennaGain() and AntennaGain08() require a direction to determine the bearing. Although for an isotropic rx/tx antenna this is irrelevant when using a real antenna pattern the direction must be used to determine the gain. For both programs the direction has now been included as an input
- Corrected wrap around error of the bearing in AntennaGain()
- When a T13 antenna file is read there is an \n at the end of the line that adds a line feed to the output. Removed
- Added UNDOCUMENTED option "-t" to the command line to suppress all output save the CSV table
- Corrected missing trailer from one line analysis output.
- Fixed Antenna file input problem

#### Version 13.6 Notes:

This release covers all changes identified as of Aug 2016. In addition to architecture changes and 
bug fixes other significant changes in this release are:

1. new error numbering scheme
2. Relr input parameter is no longer used or recognized
3. new input parameter SNRXXp is a input number between 1 and 99 representing the desired SNR percentage to be calculated.
4. renamed file "P1239-2 Decile Factors.txt" to "P1239-3 Decile Factors.txt"
5. input data files:
COEFF01W.txt
COEFF02W.txt
COEFF03W.txt
COEFF04W.txt
COEFF05W.txt
COEFF06W.txt
COEFF07W.txt
COEFF08W.txt
COEFF09W.txt
COEFF10W.txt
COEFF11W.txt
COEFF12W.txt
ionos01.txt
ionos02.txt
ionos03.txt
ionos04.txt
ionos05.txt
ionos06.txt
ionos07.txt
ionos08.txt
ionos09.txt
ionos10.txt
ionos11.txt
ionos12.txt
P1239-3 Decile Factors.txt
must be located in the directory pointed to by DataFilePath in the input file. 

---

The ITURHFProp, P533 and P372 software has been developed collaboratively by participants in ITU-R 
Study Group 3. It may be used by implementers in their implementation of the Recommendation as well
as in revisions of the specific original Recommendation and in other ITU Recommendations, free from
any copyright assertions. 

The software is provided “as is” WITH NO WARRANTIES, EXPRESS OR IMPLIED,
INCLUDING BUT NOT LIMITED TO, THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE 
AND NON-INFRINGEMENT OF INTELLECTUAL PROPERTY RIGHTS. 

The ITU shall not be held liable in any event for any damages whatsoever (including, without 
limitation, damages for loss of profits, business interruption, loss of information, or any other
pecuniary loss) arising out of or related to use of the Software.

---

#2022 ITU-R Study Group 3


