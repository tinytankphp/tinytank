import { expect, test } from './fixtures.js';

test('the browser downloads a small modern version of an image, not the original', async ({ page }) => {
    const images = [];
    page.on('response', (response) => {
        if (response.request().resourceType() === 'image' && response.url().includes('/media/pages/')) {
            images.push([new URL(response.url()).pathname, response.headers()['content-type'], response.status()]);
        }
    });

    await page.goto('/style-guide');
    const photo = page.locator('picture img').first();
    await photo.scrollIntoViewIfNeeded(); // lazy-loaded
    await expect(photo).toBeVisible();
    await expect.poll(() => photo.evaluate((img) => img.complete && img.naturalWidth > 0)).toBe(true);

    expect(images.length).toBeGreaterThan(0);
    for (const [path, type, status] of images) {
        expect(status, path).toBe(200);
        expect(path, 'a responsive version').toMatch(/\.\d+w\.(avif|webp)$/);
        expect(type).toMatch(/^image\/(avif|webp)$/);
    }
});
