#include <stdio.h>
#include <stdlib.h>
#include <math.h>

// Local Includes
#include "Common.h"
#include "P533.h"

DLLEXPORT double GreatCircleDistance(struct Location here, struct Location there) {

/* 

  GreatCircleDistance() determines the distance in km between here and there on the great circle.
 
 		INPUT
 			here - Control point origin of calculation
 			there - Control point terminus of calculation
 
 		OUTPUT 
 			returns the distance (km) between here and there

		SUBROUTINES
			None

 */
	return 2.0*R0*asin(sqrt(pow((sin((here.lat-there.lat)/2.0)),2.0) + cos(here.lat)*cos(there.lat)*pow((sin((here.lng - there.lng)/2.0)),2.0))); 

} //  GreatCircleDistance()

DLLEXPORT void GreatCirclePoint(struct Location here, struct Location there, struct ControlPt *midpnt, double distance, double fraction) {

/*

 	GreatCirclePoint() determines the lat and long of a point on a great circle path of length distance from here to there.
 		The point is determined at fraction*distance from here. 
 
 		INPUT
 			here - Location origin of calculation
 			there - Location point terminus of calculation
 			distance - Distance from here to there 
 			fraction - Fraction of the distance from here to there to determine the mid-path
 
 		OUTPUT
 			midpnt->L.lat - Mid-point latitude
 			midpnt->L.lng - Mid-point longitude
 			midpnt->distance - Mid-point distance from here

		SUBROUTINES
			None
 			
*/ 
	
	double A, B, d, x, y, z; // temp variables
	
	if (distance != 0.0) {
		midpnt->distance = distance*fraction;
		d = distance / R0;
		A = sin((1 - fraction)*d) / sin(d);
		B = sin(fraction*d) / sin(d);
		x = A*cos(here.lat)*cos(here.lng) + B*cos(there.lat)*cos(there.lng);
		y = A*cos(here.lat)*sin(here.lng) + B*cos(there.lat)*sin(there.lng);
		z = A*sin(here.lat) + B*sin(there.lat);
		midpnt->L.lat = atan2(z, sqrt(pow(x, 2) + pow(y, 2)));
		midpnt->L.lng = atan2(y, x);
	}
	else if (distance == 0) {
		midpnt->distance = 0.0;
		midpnt->L.lat = here.lat;
		midpnt->L.lng = here.lng;
	}

return;

} // GreatCirclePoint()

DLLEXPORT void GeomagneticCoords(struct Location here, struct Location *there) {
	
/* 

	GeomagneticCoords() - Conversion from geographic coordinates to geomagnetic coordinates (lat, lng)
	 
		INPUT
	 		here - Location origin of calculation
	 
	 	OUTPUT 
	 		there - Location with geomagnetic location of here 

		SUBROUTINES
			None

*/ 

	struct Location GeoMagNPole;

	// This was the pole location when the coefficients Lh were determined.
	// In 1955 the Geomagnetic North Pole was at 78.5N	69.2W,  
	//				while the South Pole was at  78.5S  110.8E
	GeoMagNPole.lat = 78.5*D2R; 
	GeoMagNPole.lng = -68.2*D2R;

	there->lat = asin(sin(here.lat)*sin(GeoMagNPole.lat) + cos(here.lat)*cos(GeoMagNPole.lat)*cos(here.lng - GeoMagNPole.lng));
	there->lng = asin(cos(here.lat)*sin(here.lng - GeoMagNPole.lng)/cos(there->lat));

	return;

}

DLLEXPORT double Bearing(struct Location here, struct Location there, int direction) {

/*

  Bearing() - Determines the bearing of a Location
 
 		INPUT
 			here - Location of the origin of calculation
 			there - Location of the terminus of the calculation
			direction - Either the short or long way round
 
 		OUTPUT
 			returns the bearing from here to there

		SUBROUTINES
			None
 
 */

	// N and E are positive
	// S and W are negative

	double numerator, denominator;
	double bearing;

	numerator = sin(there.lng - here.lng)*cos(there.lat);
	denominator = cos(here.lat)*sin(there.lat) - sin(here.lat)*cos(there.lat)*cos(there.lng - here.lng);

	bearing = atan2(numerator, denominator);

	bearing = fmod((2.0 * PI + bearing), 2.0 * PI);

	if (direction == LONGPATH) {
        // Flip the bearing around since you are looking at the long way round
		// Then modulo 2*PI
		bearing = fmod((2.0 * PI + (bearing + PI)), 2.0 * PI);
	}

return bearing;
}
