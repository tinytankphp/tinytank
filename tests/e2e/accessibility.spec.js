import { expect, test } from './fixtures.js';

test('the skip link comes first and moves the focus to the content', async ({ page }) => {
    await page.goto('/blog');
    const skip = page.getByRole('link', { name: 'Skip to content' });
    await expect(skip).not.toBeInViewport(); // visually hidden until focused

    await page.keyboard.press('Tab');
    await expect(skip).toBeFocused();
    await expect(skip).toBeInViewport();

    await page.keyboard.press('Enter');
    await expect(page.locator('main')).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.locator('main a, main input, main button').first()).toBeFocused(); // past the navigation
});

test('keyboard focus is always visible', async ({ page }) => {
    await page.goto('/');
    await page.keyboard.press('Tab'); // skip link
    await page.keyboard.press('Tab'); // the site name

    const outline = await page.evaluate(() => getComputedStyle(document.activeElement).outlineStyle);
    expect(outline).toBe('solid');
});

test('reduced motion stops movement, keeps colour fades, and lets an allowed effect move', async ({ page }) => {
    // The video's play button: the element with a transition (whether or not the poster was downloaded,
    // which CI never does: without it, a plain placeholder comes first in the link).
    const transition = async () => page.evaluate(() => getComputedStyle(document.querySelector('figure .transition')).transitionProperty);
    // Two spinning elements, one of them marked as an effect the visitor asked for.
    const animations = async () => page.evaluate(() => ['', 'motion-allowed'].map((name) => {
        const element = document.createElement('div');
        element.className = name;
        element.style.animation = 'spin 2s linear infinite';
        document.body.append(element);
        return getComputedStyle(element).animationDuration;
    }));

    await page.goto('/blog/markdown-cheatsheet');
    expect(await transition()).toContain('transform');
    expect(await animations()).toEqual(['2s', '2s']);

    await page.emulateMedia({ reducedMotion: 'reduce' });
    const reduced = await transition();
    expect(reduced).not.toContain('transform');
    expect(reduced).not.toBe('all');
    expect(reduced).toContain('color');
    expect(await animations()).toEqual(['1e-05s', '2s']);
});
