#!/usr/bin/env node
/**
 * Walk every file under public/assets and print the ones that are not
 * referenced anywhere in resources/, app/, config/, database/, routes/.
 *
 * Matches both raw filenames ("foo bar.png") and their URL-encoded form
 * ("foo%20bar.png") since blade frequently goes through `rawurlencode`.
 *
 * Does NOT delete anything - it just prints a candidate list.
 */
import { promises as fs } from 'node:fs';
import path from 'node:path';

const ASSETS_DIR = path.resolve('public/assets');
const SCAN_DIRS = ['resources', 'app', 'config', 'database', 'routes'];
const SCAN_EXTS = ['.blade.php', '.php', '.js', '.css', '.mjs', '.ts', '.vue', '.json'];

// Build a single in-memory haystack from every scannable source file,
// then look up each asset name in it. Faster than greping per-asset.
let haystack = '';
async function* walk(dir) {
    for (const entry of await fs.readdir(dir, { withFileTypes: true })) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            if (['node_modules', 'vendor', '.git'].includes(entry.name)) continue;
            yield* walk(full);
        } else {
            yield full;
        }
    }
}

for (const root of SCAN_DIRS) {
    const dir = path.resolve(root);
    try { await fs.access(dir); } catch { continue; }
    for await (const f of walk(dir)) {
        if (! SCAN_EXTS.some((e) => f.toLowerCase().endsWith(e))) continue;
        try { haystack += '\n' + await fs.readFile(f, 'utf8'); } catch {}
    }
}

const assets = await fs.readdir(ASSETS_DIR);
const unused = [];
for (const asset of assets) {
    // Skip favicon and apple-touch-icon — referenced from <head>.
    if (asset === 'favicon.ico') continue;

    const encoded = encodeURIComponent(asset);
    if (haystack.includes(asset) || haystack.includes(encoded)) continue;
    unused.push(asset);
}

let bytes = 0;
for (const f of unused) {
    const s = (await fs.stat(path.join(ASSETS_DIR, f))).size;
    bytes += s;
    console.log(`  ${(s / 1024).toFixed(0).padStart(5)} KB  ${f}`);
}
console.log('');
console.log(`Unused: ${unused.length} files, ${(bytes / 1024).toFixed(0)} KB total`);
