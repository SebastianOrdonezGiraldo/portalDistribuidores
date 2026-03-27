import fs from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';

const args = process.argv.slice(2);

const readOption = (flag) => {
    const index = args.indexOf(flag);
    if (index === -1 || index === args.length - 1) {
        return '';
    }

    return args[index + 1];
};

const input = readOption('--input');
const outputDir = readOption('--output-dir');
const basename = readOption('--basename') || 'photo';

if (!input || !outputDir) {
    console.error('Missing --input or --output-dir.');
    process.exit(1);
}

const image = sharp(input, { failOn: 'none' });
const metadata = await image.metadata();
const width = Number(metadata.width || 0);
const height = Number(metadata.height || 0);

if (!width || !height) {
    console.error('Unable to read image dimensions.');
    process.exit(1);
}

const candidateWidths = [320, 640, 1080].filter((candidate) => candidate < width);
if (width <= 1080) {
    candidateWidths.push(width);
}

const variantWidths = [...new Set(candidateWidths)].sort((left, right) => left - right);

await fs.mkdir(outputDir, { recursive: true });

const variants = [];

for (const targetWidth of variantWidths) {
    const targetHeight = Math.max(1, Math.round((height * targetWidth) / width));
    const fileName = `${basename}-${targetWidth}.webp`;
    const outputPath = path.join(outputDir, fileName);

    await sharp(input, { failOn: 'none' })
        .resize({ width: targetWidth, fit: 'inside', withoutEnlargement: true })
        .webp({ quality: 78 })
        .toFile(outputPath);

    variants.push({
        file: fileName,
        width: targetWidth,
        height: targetHeight,
    });
}

process.stdout.write(JSON.stringify({
    width,
    height,
    variants,
}));
