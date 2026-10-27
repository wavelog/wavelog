/* Numerical comparison against independently compiled native reference fixtures. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..', '..');
const fixtures = require('./native-fixtures.json');
global.WebAssembly = undefined;
const create = require(path.join(root, 'assets', 'js', 'propagation', 'p533-engine.js'));

async function main() {
    const started = performance.now();
    const engine = await create();
    const initialHeap = engine.HEAPF64;
    engine.FS.mkdir('/data');
    const data = process.env.WAVELOG_PROPAGATION_DATA || path.join(root, 'updates', 'propagation', fixtures.revision);
    let month;
    let largest = [0, 0, 0, 0, 0];
    for (const fixture of fixtures.cases) {
        const selected = fixture.arguments[5];
        if (selected !== month) {
            if (month) {
                const old = String(month).padStart(2, '0');
                engine.FS.unlink('/data/ionos' + old + '.bin');
                engine.FS.unlink('/data/COEFF' + old + 'W.txt');
            }
            const suffix = String(selected).padStart(2, '0');
            for (const name of ['ionos' + suffix + '.bin', 'COEFF' + suffix + 'W.txt',
                'P1239-3 Decile Factors.txt']) {
                engine.FS.writeFile('/data/' + name, fs.readFileSync(path.join(data, name)));
            }
            assert.equal(engine._wl_init(selected), 0);
            month = selected;
        }
        assert.equal(engine._wl_predict(...fixture.arguments), fixture.code, fixture.name);
        const index = engine._wl_result() / 8;
        const actual = Array.from(engine.HEAPF64.subarray(index, index + 5));
        fixture.result.forEach((expected, i) => {
            if (expected === null) {
                assert.ok(Number.isNaN(actual[i]), fixture.name + ' no-mode field ' + i);
            } else {
                const delta = Math.abs(actual[i] - expected);
                largest[i] = Math.max(largest[i], delta);
                assert.ok(delta <= fixtures.absoluteTolerance[i],
                    `${fixture.name} field ${i}: ${actual[i]} versus native ${expected}`);
            }
        });
        console.log('PASS ' + fixture.name);
    }
    const args = fixtures.cases[0].arguments;
    assert.equal(engine._wl_predict(...args.slice(0, 7), 0, ...args.slice(8)), 109);
    assert.equal(engine._wl_predict(...args.slice(0, 8), 31, ...args.slice(9)), 111);
    assert.equal(engine._wl_init(0), 101);
    assert.equal(engine._wl_init(2), 141);
    assert.equal(engine._wl_init(10), 0);
    assert.equal(engine._wl_predict(...args), 0);
    assert.notEqual(engine.HEAPF64, initialHeap, 'HEAPF64 updates after memory growth');
    console.log('Max absolute errors: ' + largest.join(', '));
    console.log(`PASS invalid-input, missing-data, and memory-growth checks; ${performance.now() - started} ms`);
}

main().catch(error => { console.error(error); process.exitCode = 1; });
