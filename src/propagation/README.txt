ITU HF reference engine for the browser
=======================================

Upstream: https://github.com/ITU-R-Study-Group-3/ITU-R-HF
Revision: 82017594a1c6cacfaa7e86954c4ae7b3a5825a3d
Reference versions: P533 14.2, P372 14.3.
Compiler: Emscripten 4.0.23, WASM=0 (wasm2js), strict floating point.

This is the complete pinned reference calculation, not a fitted propagation
approximation. The upstream README and C sources are preserved byte-for-byte.
See NOTICE.txt for ITU permission and disclaimer. The upstream README describes
P.533-14 and P.372; its revision-specific version descriptions and limitations
are authoritative. This bundle does not claim implementation of later revisions.

Build
-----
Install/activate Emscripten 4.0.23 separately. From the repository root:

  python src\propagation\build.py --emsdk C:\path\to\emsdk

Alternatively set EMSDK to that directory. Python 3.10+ is required.
The build verifies SHA256 of every preserved reference source and official data
file against reference-manifest.json before compiling. The compiler version is
checked. SOURCE_DATE_EPOCH is fixed, fast-math is disabled, and floating-point
contraction is disabled. Repeat builds were byte-identical.

Reference data installation
---------------------------
The large reference maps and coefficients are NOT shipped in Git or releases.
In Wavelog's Cron Manager, run "Install and verify ITU-R propagation reference
data" for first-time setup. The hourly job subsequently checks and repairs
missing/corrupt files, skipping valid ones without downloading them again.
Downloads use the pinned official ITURHFProp/Data directory on
raw.githubusercontent.com over verified HTTPS, not a moving branch.
Every file is size/SHA256 checked before an atomic rename. Concurrent
installations are locked; failed downloads do not replace existing data.
The initial install downloads about 137 MB and requires writable update storage.
Interrupted installs resume on the next run, keeping previously verified files.

Data is stored in updates/propagation/<revision>/, respecting Wavelog's datadir
setting through Paths::make_update_path(). It is served by the authenticated
propagation/data endpoint, including when datadir is outside the web root.
Browsers fetch only their required month. Missing/damaged data is reported
explicitly; there is no approximate prediction fallback.

Builds and Node/native tests default to updates/propagation/<revision>/.
Set WAVELOG_PROPAGATION_DATA to that directory's absolute path if using a custom
datadir. Install the data before running builds or numerical tests.
reference-manifest.json preserves the original logical asset names for source
provenance; build.py resolves those data entries against the installed directory.

Only ignored .build copies are adapted: the P533 and PathMemory dynamic P372
loading blocks are removed, the Noise.h loader globals are removed, and
dll-prefixed routine calls are changed to the same directly linked routines.
No scientific expressions, coefficients, loops, or algorithms are changed.
P372 Noise.c, NoiseMemory.c, and InitializeNoise.c are linked statically.

The deployed output is assets\js\propagation\p533-engine.js. It creates a
createP533Engine factory (also CommonJS-exported for Node). There is no .wasm,
native executable, separate memory initializer, or server runtime dependency.
The memory starts at 32 MiB, grows as required up to 512 MiB, and exports current
HEAPF64 after growth. FS and all three wrapper entry points are exported.

API
---
Factory:
  const engine = await createP533Engine();

Before wl_init, write the three verified files to the in-memory FS:
  /data/ionosMM.bin
  /data/COEFFMMW.txt
  /data/P1239-3 Decile Factors.txt

MM is the two-digit public month 01..12. Official binary maps are little-endian
IEEE float records, preserved unchanged. Each map is 11,197,844 bytes.
COEFF01W.txt is 235,276 bytes; other coefficient files are 235,275 bytes.
P1239-3 Decile Factors.txt is 73,427 bytes.
application\config\propagation-data.json lists every data SHA256 and byte
count for installer/server-side integrity verification. The wrapper checks exact file
lengths before calling the original parsers, which otherwise do not reject all
truncated files. Verify SHA256 before writing FS; length checks alone cannot
detect same-length corruption.

  int wl_init(int month);
  int wl_predict(double txLatDegrees, double txLngDegrees,
                 double rxLatDegrees, double rxLngDegrees,
                 int year, int month, int hour, double smoothedSSN,
                 double frequencyMHz, int longPath, int noiseCategory);
  double *wl_result(void);

Use engine._wl_init, engine._wl_predict, engine._wl_result.
Both operations return 0 on success, otherwise a nonzero error/status code.
Read five doubles from engine.HEAPF64 at engine._wl_result()/8.
Read the current HEAPF64 property, not a previously captured view.

Stable result layout:
  0: median available receiver power, dBm (official Pr in dBW plus 30)
  1: noise power, dBm (received power minus official SNR, same bandwidth)
  2: official median SNR, dB
  3: official basic MUF, MHz (not operational MUF)
  4: official great-circle path distance, km

The link budget uses 100 W (-10 dB relative to 1 kW), isotropic 0 dBi antennas
at both ends, 2,500 Hz bandwidth, analog modulation, required SNR 0 dB, and
SNR percentage 50. The official full noise and circuit-reliability calculations
still run. The noise is external atmospheric/man-made/galactic reference noise,
not a measured receiver floor. No receiver-specific noise figure is added.

Input constraints:
  Latitude -90..90 degrees, longitude -180..180 degrees.
  Year 1900..2100, month 1..12, hour 0..23.
  The public hour is passed unchanged as the official PathData hour index.
  Preserve this convention for reference comparisons: the upstream CLI labels
  input hours 1..24 and subtracts one; some internal comments also use this
  convention. No timing equations or reference index conventions are corrected.
  Monthly smoothed SSN is integer 1..311, not instantaneous SFI.
  The reference itself caps SSN to MAXSSN=160 in several (not all) equations.
  Frequency 1..30 MHz inclusive. The 160m band is supported by the engine
  and included in the application UI.
  longPath 0 (short great circle) or 1 (complementary great circle).
  noiseCategory 0 city, 1 residential, 2 rural, 3 quiet rural.
  The official required-SNR validation range is -30..200 dB; the wrapper fixes
  SNRr at 0 dB. SNRr affects reliability, not the reported median SNR.

The model predicts monthly median conditions, using the reference's
representative day/time/ionospheric climatology. It does not model daily
geomagnetic K/A indices, instantaneous space weather, terrain, antenna height,
real station antennas, takeoff obstructions, or individual QSOs.

Errors and no-mode status:
  Native reference errors are returned unchanged (100.. ranges in P533.h and
  Noise.h). Common ones: 100 year, 101 month, 102 hour, 103 noise category,
  109 SSN, 111 frequency, 121 TX location, 122 RX location, 139 P1239 file,
  141 ionospheric file, 201 noise coefficient file.
  3001 invalid short/long-path switch.
  3002 wl_init has not successfully loaded the requested month.
  3003 non-finite reference numerical output.
  3004 reference returned its TINYDB no-supported-mode sentinel for Pr.
       This is not a valid received-power floor. The first three results stay
       NaN; the reference MUF and distance are available in results 3 and 4.
       Applications should render this as no supported mode, not failure of
       every other cell or a made-up dB value.
  All result fields are reset to NaN before an operation, so failed calls do
  not expose stale successful link budgets.

Maps and coefficients are allocated once and reloaded on month changes; P1239
and isotropic antennas are allocated once. The caller may unlink the previous
month's FS map/coefficient after loading the next. One module instance is
stateful and must not execute overlapping calls; use a dedicated worker.

Independent native validation
-----------------------------
On Windows with MSVC x64 installed:

  python src\propagation\build_native.py

An alternate vcvars64.bat path can be supplied with --vcvars.
This compiles the UNMODIFIED preserved P533/P372 C files with /O2 /fp:strict
into native DLLs, preserving their ORIGINAL dynamic P372 loading. It compiles
the same unit-conversion/API wrapper separately against those libraries.
Native DLLs remain in ignored .build\native and are never deployed.
native-fixtures.json records independent native results, exact source revision,
library versions, native DLL SHA256 hashes, field names, and tolerances.

  node src\propagation\verify.cjs

Nine independent cases cover short day, short night, long complementary path,
intercontinental short path, equatorial, polar, 7,000..9,000 km interpolation,
high-band night, and the official no-supported-mode sentinel. Per-field
absolute tolerances are 1e-7 dB/MHz and 1e-6 km.
The verification disables global WebAssembly and also checks invalid inputs,
missing monthly data, memory growth, and the exported updated HEAPF64 view.

Results from Windows x64, Node 24.19.0, Emscripten 4.0.23:
  All nine native comparison cases: PASS.
  Largest absolute dB/MHz difference: 4.263256414560601e-14.
  Largest absolute distance difference: 3.637978807091713e-12 km.
  No-WebAssembly, invalid-input, missing-data, memory-growth checks: PASS.
  Initial factory/map setup was approximately 573 ms in a cold smoke run.
  1,000 repeated 6,475 km short-path calls: approximately 721 ms.
  46,656 worldwide calls (2,592 cells x 9 bands x short/long): 200.1 seconds,
  all success, heap 40,304,640 bytes. Long paths are substantially more costly.
These are observations, not portable speed guarantees. Worker execution,
progress/cancellation between calls, and coarse/progressive grids are important
for interactive use. Do not replace the reference calculation with a faster
approximation and label it the ITU engine.
