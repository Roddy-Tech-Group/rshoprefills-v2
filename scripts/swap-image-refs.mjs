#!/usr/bin/env node
/**
 * Find every .png / .jpg / .jpeg filename that now exists as .webp in
 * public/assets and rewrite references to use the .webp version.
 *
 * Scans resources/, app/, public/ (excluding /assets), config/, database/.
 * Handles both raw and URL-encoded filenames (e.g. "foo bar.png" + "foo%20bar.png").
 *
 * Idempotent: re-running after a partial swap is safe.
 */
import { promises as fs } from 'node:fs';
import path from 'node:path';

const ASSETS_DIR = path.resolve('public/assets');
const SCAN_DIRS = ['resources', 'app', 'config', 'database', 'routes'];
const SCAN_EXTS = ['.blade.php', '.php', '.js', '.css', '.mjs', '.ts', '.vue', '.json'];

const webpFiles = (await fs.readdir(ASSETS_DIR))
    .filter((f) => f.toLowerCase().endsWith('.webp'));

// Each converted file maps to one or more legacy reference patterns we need
// to swap. Build the candidate name list once.
const swaps = [];
for (const webp of webpFiles) {
    const base = webp.replace(/\.webp$/i, '');
    for (const ext of ['png', 'jpg', 'jpeg', 'PNG', 'JPG', 'JPEG']) {
        const raw = `${base}.${ext}`;
        const encoded = encodeURIComponent(raw);
        swaps.push({ from: raw, to: `${base}.webp` });
        if (encoded !== raw) {
            swaps.push({ from: encoded, to: encodeURIComponent(`${base}.webp`) });
        }
    }
}

async function* walk(dir) {
    for (const entry of await fs.readdir(dir, { withFileTypes: true })) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            if (entry.name === 'node_modules' || entry.name === 'vendor' || entry.name === '.git') {
                continue;
            }
            yield* walk(full);
        } else {
            yield full;
        }
    }
}

let filesChanged = 0;
let totalSwaps = 0;

for (const root of SCAN_DIRS) {
    const dir = path.resolve(root);
    try { await fs.access(dir); } catch { continue; }

    for await (const file of walk(dir)) {
        if (! SCAN_EXTS.some((e) => file.toLowerCase().endsWith(e))) continue;

        let contents;
        try { contents = await fs.readFile(file, 'utf8'); } catch { continue; }
        if (! contents) continue;

        let changed = false;
        let swapsInFile = 0;

        for (const { from, to } of swaps) {
            if (! contents.includes(from)) continue;
            const re = new RegExp(from.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');
            const before = contents;
            contents = contents.replace(re, to);
            if (contents !== before) {
                swapsInFile += (before.match(re) || []).length;
                changed = true;
            }
        }

        if (changed) {
            await fs.writeFile(file, contents);
            filesChanged++;
            totalSwaps += swapsInFile;
            console.log(`  ${path.relative(process.cwd(), file)}  (${swapsInFile} swap${swapsInFile === 1 ? '' : 's'})`);
        }
    }
}

console.log('');
console.log(`Files changed: ${filesChanged}   Total swaps: ${totalSwaps}`);
