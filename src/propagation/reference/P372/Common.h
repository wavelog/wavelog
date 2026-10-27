#ifndef COMMON_H
#define COMMON_H

// #defines

// Operating system preprocessor directives
#define MAX(x, y) (((x) > (y)) ? (x) : (y))
#define MIN(x, y) (((x) < (y)) ? (x) : (y))
#define max(x, y) (((x) > (y)) ? (x) : (y))
#define min(x, y) (((x) < (y)) ? (x) : (y))

// Define some constants to enhance readability
#define TRUE 1
#define FALSE 0
#define PI 3.14159265358979323846
// International Union of Geodesy and Geophysics mean Earth radius.
#define R0 6371.009 // km
#define D2R 0.0174532925 // PI/180
#define R2D 57.2957795 // 180/PI
#define VofL 299792458.0 // Velocity of light (m/s)

// Numbers used as indicators
#define TINYDB DBL_MIN_10_EXP // Smallest number in dB
#define TOOBIG DBL_MAX // Large number typically an error 

// Double extreme
#define DBL_MANT_DIG 53
#define DBL_DIG 15
#define DBL_MIN_EXP -1021
#define DBL_MIN_10_EXP -307
#define DBL_MAX_EXP 1024
#define DBL_MAX_10_EXP 308
#define DBL_MAX 1.7976931348623157E+308
#define DBL_MIN 2.2250738585072014E-308
#define DBL_EPSILON 2.2204460492503131E-016

//////////////////////////////////////////////////////////////////////////////
//      Copyright  International Telecommunication Union (ITU) 2018         //
//                     All rights reserved.                                 //
// No part of this publication may be reproduced, by any means whatsoever,  //
//              without written permission of ITU                           //
//////////////////////////////////////////////////////////////////////////////
#endif // COMMON_H
