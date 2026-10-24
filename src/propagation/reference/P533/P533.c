#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// Local Include ******************************************************
#include "Common.h"
#include "P533.h"
// End Local Include **************************************************

	int P533(struct PathData *path) {

		/*

		P533()
			This program provides methods for the prediction of available frequencies, signal levels, and the predicted reliability
			for analogue and digital-modulated HF systems, taking into account not only the signal-to-noise ratio but also the expected
			time and frequency spreads of the channel. This program calculates the HF path parameters that appear in ITU-R P.533-12.
			This program is loosely based on the program REC533. This model uses control points to determine the modes of propagation of
			HF signals in the ionosphere.

			Great care has been taken in this implementation to follow the ITU published standard P.533-12. The flow of this implementation
			was designed primarily for readability and clarity, with performance being the secondary goal. Unfortunately, clever performance-enhancing
			algorithms tend to obscure many calculations. It is hoped that this code will illuminate the standard and be easily maintainable.

			Where possible, this code has been verified with the original REC533() code. There are inevitable variations between REC533 and this
			implementation, not only because of improvements to computer resources in the five decades since REC533's inception but also because of the
			differences between FORTRAN and C. It has become apparent over the course of this project that there were unfortunate omissions and additions
			to the REC533() code that were at odds with the standard. These variations and algorithmic eccentricities have been discussed at length with
			ITU Study Group 3, in particular Working Party 3L. Many individuals who worked on the creation of the original REC533 implementation are no
			longer accessible, which is another reason to document this effort as thoroughly as possible. It would be a shame if decades of hard work were
			lost to esoteric coding techniques that obfuscate the model's inner workings. The original REC533() code used exceptionally clever methods;
			however, those methods often clouded the possible understanding of the actual calculation.

			Use of variable names in the standard should make the algorithms more readable, albeit at the potential expense of having multiply defined
			identical variables in subroutines. There may be occasional redundant calculations again for clarity. In addition, for clarity,some of the calculations
			may use pedantic methods that may be obvious inefficient. This is in an attempt to make the code more accessible to those who are unfamiliar with
			it. Communication is the first priority of this effort; efficiency is secondary.

			The P533() routine has been designed to operate on the data structure path whose prototype can be found in P533.h as the data structure PathData.
			Please start with this header file when exploring this implementation. The structure PathData was determined as the most efficient way to pass data
			between the subroutines without losing clarity. Take note that the #defines in this header file are primarily there to enhance readability. That is
			why there are several #defines that resolve to identical values.

			The P533() routine is designed to do no I/O. It simply takes the structure PathData path and calculates. This was done to enhance usability and
			portability. It has become clear over years of using legacy propagation models that this is the most rational and expedient architecture. The data
			must be loaded into the structure PathData elsewhere before P533() can run. A rudimentary implementation/example of how to use this propagation model
			will accompany P533(). The application ITURHFProp() sets up the data necessary to operate the core P533(). It is hoped that the program ITURHFProp() will
			be used in many situations where rudimentary analyses are satisfactory.

			It is the intent of this project that users will be able to freely use the model with public domain compilers and tools. Please refer to ITURHFProp()
			for details on how to populate the structure PathData. The following outlines the main data sets that must be loaded for the proper operation of P533().
			There are four sources of external data that are necessary to set up the structure PathData and run p533(), not including user input.

				i) Ionospheric data or maps. This data is in the ~/IonMap directory which contains monthly median foF2and M(3000)F2 data at 1.5-degree latitude and
				longitude increments for low and high sunspot numbers. The subroutine ReadIonParameters() reads these map files. The data in these files was generated
				by the methods in REC533(). Prior to this implementation, the data in these files only existed internally to REC533(). The method to derive these maps
				is derived from arrays of spherical harmonic coefficients, see Equation (3) P.1239-2. The coefficients in this form do not allow for contemporary data
				to be incorporated into the maps easily. For this reason the ITU has freed this data in the form of a map that can be updated.

				ii) Atmospheric data. Mixed coefficient files that were compiled by Dambolt and Suessman. These files contain the original spherical harmonic coefficients
				for foF2 and M(3000)F2 in addition to other data related to atmospheric noise, layer thickness, MUF statistics, etc. for a particular month. Some  of this
				data appears to be deprecated. For instance, much of the foE data has been replaced by the method found in Section 4 of ITU-R P.1239-2. In this
				implementation only the atmospheric data will be used. The suplemental subroutine ReadCoeff() can be used to examine these coefficients in more detail.
				Much of the detail about the arrays found in these coefficient files will be available in REC533() code. Refer to the file ReadCoeffExample() for an example
				how to use these coefficient files in an external application.

				iii) MUF variability. The file "P1239-2 Decile Factors.txt" is read in to get the decile factors related to MUF variability. This file is read in by using
				the subroutine ReadP1239(path);

				iv) Antenna data. The PathData structure requires the antenna pattern for the transmitter and receiver. At preset this pattern is 360 degrees azimuth
				and 91 degrees elevation. This data typically can be found from NEC or Type 13 antenna data from the VOACAP suite.

				An external program is necessary to run the p533() engine. The external program must populate the path structure with the correct data. The program ValidatePath()
				initially does input parameter validity checking. The p533() does no I/O. All input data to p533() is handled by higher level programs. By design, the path
				structure that p533() uses for calculation can be externally altered for whichever study the user desires. As long as the month does not change, the data arrays
				from the three sources above need not be changed. Only when the month changes during an analysis does any external data need to be read in again. For instance,
				in the event that an analysis is run which straddles two or more months, i) the ionoshpheric maps, and ii) the atmospheric data, will need to be reloaded.
				The MUF variability data, iii), are for an entire year.

			INPUT
				struct PathData *path

			OUTPUT
				struct PathData *path

			SUBROUTINES
				ValidatePath()
				InitializePath()
				MUFBasic()
				MUFVariability()
				MUFOperational()
				ELayerScreeningFrequency()
				MedianSkywaveFieldStrengthShort()
				MedianSkywaveFieldStrengthLong()
				Between7000kmand9000km()
				MedianAvaiableReceiverPower()
				CircuitReliability()

			EXTERNAL DLL
				The following subroutines are from the P372.dll
				Any subroutine in the P533 projeect that starts with dll is refering to P372.dll

				dllP372Version()
				dllP372CompileTime()
				dllNoise()
				dllAllocateNoiseMemory()
				dllFreeNoiseMemory()
				dllReadFamDud()
				dllInitializeNoise()

			STANDARDS
				ITU-R P.533-14 (08/19)
				ITU-R P.1239-3 (02/12)
				ITU-R P.1240-2 (07/15)
				ITU-R P.371-8 (07/99)
				ITU-R P.372-14 (08/19)
				ITU-R P.842-5 (09/13)
				ITU-R P.1057-6 (08/19)
				ITU-R P.1144-10 (08/19)
				ITU-R F.339-8 (02/13)

			COMPILER
				Microsoft Visual Studio 2019

			OS
				Windows 10 Professional

			WETWARE
				Behm
				Boulder, Colorado
				(2013-21)

			This project would not have been possible without the expertise and wisdom of Professor Les Barclay,
			Dr. Thomas Damboldt, Peter Suessmann and George Engelbrecht. I am indebted to them all.

			Special thanks to James Watson for his continued efforts to make this project more accurate, flexable,
			and accessable. 

			*********************************************************************************************
			These software methods for the prediction of the performance of HF circuits based on
			Recommendations ITU-R P.533-14 and P.372-13
			The ITURHFProp, P533 and P372 software has been developed collaboratively by participants in ITU-R
			Study Group 3. It may be used by implementers in their implementation of the Recommendation as well
			as in revisions of the specific original Recommendation and in other ITU Recommendations, free from
			any copyright assertions.
			
			This software is provided “as is” WITH NO WARRANTIES, EXPRESS OR IMPLIED,
			INCLUDING BUT NOT LIMITED TO, THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
			AND NON-INFRINGEMENT OF INTELLECTUAL PROPERTY RIGHTS.
			
			The ITU shall not be held liable in any event for any damages whatsoever (including, without
			limitation, damages for loss of profits, business interruption, loss of information, or any other
			pecuniary loss) arising out of or related to use of the software.
	        ***************************************************************************************************


		*/

	int retval; // return value

	// Load the Noise routines in P372.dll ******************************
	#ifdef _WIN32
		// Get the handle to the P372 DLL.
		hLib = LoadLibrary("P372.dll");
		if (hLib == NULL) {
			printf("P533: Error %d P372.DLL Not Found\n", RTN_ERRP372DLL);
			return RTN_ERRP372DLL;
		}
		int mod[512];
		// Get the handle to the DLL library, hLib.
		GetModuleFileName((HMODULE)hLib, (LPTSTR)mod, 512);
		// Get the P372Version() process from the DLL.
		dllP372Version = (cP372Info)GetProcAddress((HMODULE)hLib, "P372Version");
		// Get the P372CompileTime() process from the DLL.
		dllP372CompileTime = (cP372Info)GetProcAddress((HMODULE)hLib, "P372CompileTime");

		dllNoise = (iNoise)GetProcAddress((HMODULE)hLib, "Noise");
		dllAllocateNoiseMemory = (iNoiseMemory)GetProcAddress((HMODULE)hLib, "AllocateNoiseMemory");
		dllFreeNoiseMemory = (iNoiseMemory)GetProcAddress((HMODULE)hLib, "FreeNoiseMemory");
		dllInitializeNoise = (vInitializeNoise)GetProcAddress((HMODULE)hLib, "InitializeNoise");
	#elif __linux__ || __APPLE__
		void * hLib;
		hLib = dlopen("libp372.so", RTLD_NOW);
		if (!hLib) {
			printf("Couldn't load libp372.so, exiting.\n");
			exit(1);
		}
		dllP372Version = dlsym(hLib, "P372Version");
		dllP372CompileTime = dlsym(hLib, "P372CompileTime");
		dllNoise = dlsym(hLib, "Noise");
		dllAllocateNoiseMemory = dlsym(hLib, "AllocateNoiseMemory");
		dllFreeNoiseMemory = dlsym(hLib, "FreeNoiseMemory");
		dllInitializeNoise = dlsym(hLib, "InitializeNoise");
	#endif	
		
	// End P372.DLL Load ************************************************
	
	// Before moving on load the version and compile time of the P372.DLL
	path->P372ver = dllP372Version();
	path->P372compt = dllP372CompileTime();
	
	// Validate the input data
	retval = ValidatePath(path);
	if(retval != RTN_VALIDDATAOK) return retval; // check that the input parameters are correct

	// Calculate the distances between rx and tx, find the midpoint of the path, find the midpoint distance and initialize the path 
	// This will aso determine the ionospheric parameters for 3 of the potential 5 control points.
	InitializePath(path);

	/************************************************************/
	/* Part 1 - Frequency availability                          */
	/************************************************************/
	/*
	   The folowing routines in Part 1:
	 		i) MUFBasic()
	 		ii) MUFVariability()
	  		iii) MUFOperational()
	   Have to be executed in the order above because they slowly populate the path structure
	   as the calculation progresses. Typically they follow the flow of ITU-R P533-11
	   since the standard is not necessarily a discription of a computer algorythm some of the 
	   calculation is out of sequence with ITU-R P.533-12.

	   Note: The following 4 subroutines are only applicable to paths less than or equal to 9000 km
	 */

	// Determine the basic MUF (BMUF) This will also determine R - d0/2 and T - d0/2
	// Control points if necessary. 
	MUFBasic(path);

	// Determine Fprob for each mode and the path the 50% MUF (MUF50), 90% MUF (MUF90) and the 10% MUF (MUF10)
	MUFVariability(path);

	// Determine the for each mode and the path the operational MUF (OPMUF), 90% OPMUF (OPMUF90) and the 10% OPOMUF (OPMUF10)
	MUFOperational(path);

	// E Layer Screening Frequency is determine contingent on the path length
	ELayerScreeningFrequency(path);

	/************************************************************/
	/* Part 2 - Median sky-wave field strength                  */
	/************************************************************/
	/*
	 * Each of the routines below will initially check the path->distance to determine if the calculation should proceed.
	 * As in Part 1 above these routines are designed to be executed in the following order 
	 * 		i)		MedianSkywaveFieldStrengthShort()	Calculation for path->distance < 9000 km
	 *		ii)		MedianSkywaveFieldStrengthLong()	Calculation for path->distance > 9000 km
	 *		iii)	Between7000kmand9000km()			Interpolation for path->distance between 7000 and 9000 km
	 */

	 MedianSkywaveFieldStrengthShort(path);

	 MedianSkywaveFieldStrengthLong(path);

	 Between7000kmand9000km(path);

	 MedianAvailableReceiverPower(path);

	/************************************************************/
	/* Part 3 - The prediction of system performance            */
	/************************************************************/

	// Call noise from the P372.dll
	retval = dllNoise(&path->noiseP, path->hour, path->L_rx.lng, path->L_rx.lat, path->frequency);
	if (retval != RTN_NOISEOK) return retval; // check that the input parameters are correct

	CircuitReliability(path);

	return RTN_P533OK;  // Return no errors
}

DLLEXPORT char const * P533Version(void) {

	/*

	  P533Version() - Returns the version of the P533 DLL
	 
	 		INPUT
	 			None
	 
	 		OUTPUT
	 			returns a pointer to the version character string

			SUBROUTINES
				None
	 
	 */

	return P533VER;

}

DLLEXPORT char const * P533CompileTime(void) {

	/*

	  P533CompileTime() - Returns the compile time of the P533 DLL
	 
	 		INPUT
	 			None
	 
	 		OUTPUT
	 			returns a pointer to the version character string

			SUBROUTINES
				None
	 
	 */

	return P533CT;

}

DLLEXPORT int sizeofPathDataStruct(void) {
	/*
		sizeofPathStruct() - Returns the sizeof(pathdata) for testing.
			INPUT	
				None
			OUTPUT
				returns an int with the sizeof(pathdata)

	*/
	return sizeof(struct PathData);

}

