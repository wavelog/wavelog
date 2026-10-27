"""Build the pinned ITU reference engine as JavaScript, not WebAssembly."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess

ROOT = Path(__file__).resolve().parent
REPO = ROOT.parent.parent
REVISION = "82017594a1c6cacfaa7e86954c4ae7b3a5825a3d"
EMSCRIPTEN = "4.0.23"
DATA = Path(os.environ.get("WAVELOG_PROPAGATION_DATA",
                         REPO / "updates" / "propagation" / REVISION))


def adapt(source, name):
    # Only linking/platform glue changes. All scientific routines remain untouched.
    if name == "P533.c":
        start = source.index("\t// Load the Noise routines in P372.dll")
        end = source.index("\t// End P372.DLL Load", start)
        source = source[:start] + source[end:]
    elif name == "PathMemory.c":
        start = source.index("\t// Load the Noise routines in P372.dll")
        end = source.index("\t// End P372.DLL Load", start)
        source = source[:start] + source[end:]
    elif name == "Noise.h":
        start = source.index("/* Operating system preprocessor */")
        end = source.index("/* End operating system preprocessor */", start)
        source = source[:start] + source[end:]
    for symbol in ("P372Version", "P372CompileTime", "Noise", "AllocateNoiseMemory",
                   "FreeNoiseMemory", "InitializeNoise", "ReadFamDud"):
        source = re.sub(r"\bdll" + symbol + r"\b", symbol, source)
    return source


def prepare():
    manifest = json.loads((ROOT / "reference-manifest.json").read_text(encoding="utf-8"))
    for relative, expected in manifest["sha256"].items():
        source = (DATA / Path(relative).name if relative.startswith("assets/resources/propagation/")
                  else REPO / relative)
        actual = hashlib.sha256(source.read_bytes()).hexdigest()
        if actual != expected:
            raise RuntimeError(f"Pinned reference hash mismatch: {relative}")
    directory = ROOT / ".build"
    directory.mkdir(exist_ok=True)
    for component in ("P533", "P372"):
        target = directory / component
        target.mkdir(exist_ok=True)
        for source in (ROOT / "reference" / component).iterdir():
            if source.suffix not in (".c", ".h"):
                continue
            try:
                text = source.read_text(encoding="utf-8-sig")
            except UnicodeDecodeError:
                text = source.read_text(encoding="cp1252")
            text = adapt(text, source.name)
            (target / source.name).write_text(text, encoding="utf-8", newline="\n")
    shutil.copyfile(ROOT / "wrapper.c", directory / "wrapper.c")
    return directory


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--emsdk", type=Path, default=os.environ.get("EMSDK"))
    parser.add_argument("--prepare-only", action="store_true")
    args = parser.parse_args()
    directory = prepare()
    if args.prepare_only:
        return
    if args.emsdk is None:
        parser.error("--emsdk or EMSDK must identify Emscripten 4.0.23")
    sdk = Path(args.emsdk).resolve()
    emscripten = sdk / "upstream" / "emscripten"
    version = (emscripten / "emscripten-version.txt").read_text().strip().strip('"')
    if version != EMSCRIPTEN:
        raise RuntimeError(f"Expected Emscripten {EMSCRIPTEN}, got {version}")
    env = dict(os.environ)
    env["EM_CONFIG"] = str(directory / ".emscripten")
    node = next((sdk / "node").glob("*/bin/node*"), None)
    if node is None:
        node = next((sdk / "node").glob("*/node.exe"))
    config = [
        f"LLVM_ROOT = {str(sdk / 'upstream' / 'bin')!r}",
        f"BINARYEN_ROOT = {str(sdk / 'upstream')!r}",
        f"NODE_JS = [{str(node)!r}]",
        f"CACHE = {str(directory / 'cache')!r}",
    ]
    (directory / ".emscripten").write_text("\n".join(config), encoding="utf-8")
    sources = sorted((directory / "P533").glob("*.c"))
    sources += [directory / "P372" / name for name in
                ("Noise.c", "NoiseMemory.c", "InitializeNoise.c")]
    sources += [directory / "wrapper.c"]
    output = REPO / "assets" / "js" / "propagation" / "p533-engine.js"
    command = [
        os.sys.executable, str(emscripten / "emcc.py"),
        *map(str, sources), "-I" + str(directory / "P533"),
        "-D__linux__", "-O2", "-fno-fast-math", "-ffp-contract=off",
        "-Wno-macro-redefined", "-Wno-format", "-Wno-incompatible-pointer-types",
        "-sWASM=0", "-sMODULARIZE=1", "-sEXPORT_NAME=createP533Engine",
        "-sENVIRONMENT=web,worker,node", "-sFORCE_FILESYSTEM=1",
        "-sALLOW_MEMORY_GROWTH=1", "-sINITIAL_MEMORY=33554432",
        "-sMAXIMUM_MEMORY=536870912", "-sSTACK_SIZE=1048576",
        '-sEXPORTED_FUNCTIONS=["_wl_init","_wl_predict","_wl_result"]',
        '-sEXPORTED_RUNTIME_METHODS=["FS","HEAPF64"]',
        "-o", str(output),
    ]
    env["SOURCE_DATE_EPOCH"] = "1747296000"
    subprocess.run(command, env=env, check=True, cwd=REPO)
    if output.with_suffix(".wasm").exists():
        raise RuntimeError("Unexpected deployed WebAssembly")
    print(f"Built {output.relative_to(REPO)} ({output.stat().st_size:,} bytes); "
          f"ITU {REVISION}; Emscripten {EMSCRIPTEN}; WASM=0")


if __name__ == "__main__":
    main()
