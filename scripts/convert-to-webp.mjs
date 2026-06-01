#!/usr/bin/env node
/**
 * Batch-convert every PNG/JPG/JPEG in public/assets to high-quality WebP.
 *
 * Strategy:
 *   - PNGs with alpha: lossless WebP (preserves transparency without a halo)
 *   - PNGs without alpha + JPGs: lossy q=85 with effort=6 (visually identical,
 *     huge size win)
 *   - Skip any file we can't decode (favicon.ico, SVGs, anything already .webp)
 *
 * Originals are deleted after a successful conversion. The blade/js references
 * are swapped to .webp by a separate codemod pass.
 */
import { promises as fs } from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';

const ASSETS_DIR = path.resolve('public/assets');
const QUALITY = 85;
const EFFORT = 6; // 0 (fast) .. 6 (best compression)

const stats = {
    converted: 0,
    skipped: 0,
    failed: 0,
    bytesIn: 0,
    bytesOut: 0,
};

async function convert(file) {
    const ext = path.extname(file).toLowerCase();
    if (! ['.png', '.jpg', '.jpeg'].includes(ext)) {
        return;
    }

    const inPath = path.join(ASSETS_DIR, file);
    const outPath = path.join(ASSETS_DIR, file.replace(/\.(png|jpg|jpeg)$/i, '.webp'));

    try {
        const input = sharp(inPath);
        const meta = await input.metadata();
        const hasAlpha = meta.hasAlpha && ext === '.png';

        const options = hasAlpha
            ? { lossless: true, effort: EFFORT }
            : { quality: QUALITY, effort: EFFORT };

        await input.webp(options).toFile(outPath);

        const inSize = (await fs.stat(inPath)).size;
        const outSize = (await fs.stat(outPath)).size;
        stats.bytesIn += inSize;
        stats.bytesOut += outSize;

        await fs.unlink(inPath);

        stats.converted++;
        const saved = (((inSize - outSize) / inSize) * 100).toFixed(0);
        console.log(`  ${file} -> .webp  (${(inSize / 1024).toFixed(0)}KB -> ${(outSize / 1024).toFixed(0)}KB, ${saved}% smaller)`);
    } catch (e) {
        stats.failed++;
        console.error(`  FAIL ${file}: ${e.message}`);
    }
}

const entries = await fs.readdir(ASSETS_DIR);
for (const entry of entries) {
    if ((await fs.stat(path.join(ASSETS_DIR, entry))).isFile()) {
        await convert(entry);
    }
}

const savedTotal = (((stats.bytesIn - stats.bytesOut) / stats.bytesIn) * 100).toFixed(0);
console.log('');
console.log(`Converted: ${stats.converted}   Failed: ${stats.failed}`);
console.log(`Total: ${(stats.bytesIn / 1024 / 1024).toFixed(2)}MB -> ${(stats.bytesOut / 1024 / 1024).toFixed(2)}MB  (${savedTotal}% smaller)`);
