import { expect, test } from './fixtures.js';

// The style guide (content/pages/style-guide/, unlisted) holds every content element at once, so a
// broken element shows up here: no console errors or CSP violations (fixtures.js), and the lazy
// photo really arrives in a modern format. It isn't in the sitemap, hence its own test.
test('the style guide renders every element cleanly', async ({ page }) => {
    const response = await page.goto('/style-guide');

    expect(response?.status()).toBe(200);
    await expect(page.getByRole('heading', { level: 1, name: 'Style guide' })).toBeVisible();
    await expect(page.locator('pre[data-lang] .hl-keyword').first()).toBeVisible();
    await expect(page.locator('table')).toBeVisible();

    const photo = page.locator('picture img');
    await photo.scrollIntoViewIfNeeded();
    await expect.poll(() => photo.evaluate((img) => img.complete && img.naturalWidth)).toBeGreaterThan(0);
    expect(await photo.evaluate((img) => img.currentSrc)).toMatch(/\.(avif|webp)$/);
});
