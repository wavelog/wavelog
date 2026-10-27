"""Build independent native reference DLLs and regenerate comparison fixtures.

Requires the MSVC x64 C compiler. Native binaries remain in ignored .build/native;
they are validation tools, never deployed. Scientific sources are compiled
unchanged, including their original dynamic P372.dll loading.
"""
import argparse
import ctypes
import hashlib
import json
import os
from pathlib import Path
import subprocess

from build import ROOT, REPO, REVISION, DATA, prepare


CASES = [
    ("short-day", [45.4642, 9.19, 51.5074, -0.1278, 2026, 10, 12, 100, 14.2, 0, 2]),
    ("short-night", [45.4642, 9.19, 51.5074, -0.1278, 2026, 10, 0, 100, 7.1, 0, 2]),
    ("long-path", [45.4642, 9.19, 40.7128, -74.006, 2026, 10, 12, 100, 14.2, 1, 2]),
    ("intercontinental", [45.4642, 9.19, -33.8688, 151.2093, 2026, 6, 6, 80, 14.2, 0, 3]),
    ("equatorial", [-0.1807, -78.4678, -6.2088, 106.8456, 2026, 3, 18, 140, 21.2, 0, 1]),
    ("polar", [69.6492, 18.9553, 61.2181, -149.9003, 2026, 12, 0, 30, 7.1, 0, 0]),
    ("transition-7000-9000", [40.7128, -74.006, 55.7558, 37.6173, 2026, 10, 12, 100, 14.2, 0, 2]),
    ("high-band-night", [45.4642, 9.19, 46.2044, 6.1432, 2026, 10, 0, 10, 28.5, 0, 3]),
    ("no-supported-mode", [45.4642, 9.19, 0, -30, 2026, 10, 12, 100, 1, 0, 2]),
]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--vcvars", type=Path, default=Path(
        r"C:\Program Files\Microsoft Visual Studio\2022\Community\VC\Auxiliary\Build\vcvars64.bat"))
    args = parser.parse_args()
    prepare()
    directory = ROOT / ".build" / "native"
    directory.mkdir(exist_ok=True)
    wrapper = (ROOT / "wrapper.c").read_text(encoding="utf-8")
    data = DATA.resolve().as_posix() + "/"
    wrapper = wrapper.replace('#define WL_DATA_PATH "/data/"',
                              f'#define WL_DATA_PATH "{data}"')
    (directory / "wrapper.c").write_text(wrapper, encoding="utf-8")
    flags = "/nologo /LD /O2 /fp:strict /wd4996 /wd4005"
    p372 = ROOT / "reference" / "P372"
    p533 = ROOT / "reference" / "P533"
    noise_sources = " ".join(f'"{p372 / name}"' for name in
                            ("Noise.c", "NoiseMemory.c", "InitializeNoise.c", "MakeNoise.c"))
    commands = [
        f'call "{args.vcvars}" >nul',
        f'cl {flags} {noise_sources} /Fe:P372.dll',
        f'cl {flags} "{p533}\\*.c" /Fe:P533.dll',
        f'cl {flags} /I"{p533}" wrapper.c P533.lib P372.lib /Fe:wrapper.dll '
        '/link /EXPORT:wl_init /EXPORT:wl_predict /EXPORT:wl_result',
    ]
    subprocess.run(" && ".join(commands), executable=os.environ["COMSPEC"],
                   shell=True, check=True, cwd=directory)
    with os.add_dll_directory(str(directory)):
        noise = ctypes.CDLL(str(directory / "P372.dll"))
        native = ctypes.CDLL(str(directory / "P533.dll"))
        library = ctypes.CDLL(str(directory / "wrapper.dll"))
        native.P533Version.restype = noise.P372Version.restype = ctypes.c_char_p
        library.wl_init.argtypes = [ctypes.c_int]
        library.wl_predict.argtypes = [
            ctypes.c_double, ctypes.c_double, ctypes.c_double, ctypes.c_double,
            ctypes.c_int, ctypes.c_int, ctypes.c_int, ctypes.c_double,
            ctypes.c_double, ctypes.c_int, ctypes.c_int]
        library.wl_result.restype = ctypes.POINTER(ctypes.c_double)
        cases = []
        for name, arguments in CASES:
            code = library.wl_init(arguments[5])
            if code:
                raise RuntimeError(f"{name}: init returned {code}")
            code = library.wl_predict(*arguments)
            if code not in (0, 3004):
                raise RuntimeError(f"{name}: native prediction returned {code}")
            result = [library.wl_result()[i] for i in range(5)]
            if code == 3004:
                result[:3] = [None] * 3
            cases.append(dict(name=name, arguments=arguments, code=code, result=result))
        metadata = dict(
            revision=REVISION, p533Version=native.P533Version().decode(),
            p372Version=noise.P372Version().decode(),
            reference="Unmodified pinned C sources compiled with MSVC x64 /O2 /fp:strict; "
                      "original P533.dll dynamically loads original P372.dll.",
            binarySha256={name: hashlib.sha256((directory / name).read_bytes()).hexdigest()
                          for name in ("P533.dll", "P372.dll")},
            absoluteTolerance=[1e-7, 1e-7, 1e-7, 1e-7, 1e-6],
            resultFields=["receivedPowerDbm", "noiseDbm", "snrDb", "basicMufMHz", "distanceKm"],
            cases=cases)
        output = ROOT / "native-fixtures.json"
        output.write_text(json.dumps(metadata, indent=2, allow_nan=False) + "\n", encoding="utf-8")
        print(f"Wrote {len(cases)} independent native cases to {output.relative_to(REPO)}")


if __name__ == "__main__":
    main()
