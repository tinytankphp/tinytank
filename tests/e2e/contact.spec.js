import { expect, test } from './fixtures.js';

// The timing spam check refuses forms sent within 3 seconds of being shown: wait like a person would.
const humanPause = 3200;

async function fillIn(page, message = 'Hello, a question about TinyTank.') {
    await page.getByLabel('Name').fill('Ada Lovelace');
    await page.getByLabel('Email').fill('ada@example.test');
    await page.getByLabel('Message').fill(message);
    await page.waitForTimeout(humanPause);
}

test('the contact form sends without a reload', async ({ page }) => {
    await page.goto('/contact');
    await fillIn(page);
    await page.getByRole('button', { name: 'Send' }).click();

    await expect(page.getByRole('status')).toHaveText('Thanks! Your message is on its way.');
    expect(new URL(page.url()).search).toBe(''); // updated in place by Datastar
});

test('while it\'s sent, the button waits, with a spinner, and can\'t be pressed again', async ({ page }) => {
    // The server's answer held back a moment, to see the button in between.
    let release;
    const held = new Promise((resolve) => (release = resolve));
    let posts = 0;
    await page.route('**/contact', async (route) => {
        if (route.request().method() !== 'POST') {
            return route.continue();
        }
        posts++;
        await held;
        await route.continue();
    });
    await page.goto('/contact');
    await fillIn(page);
    const button = page.locator('#contact-form button[type="submit"]');
    await button.click();

    await expect(button).toBeDisabled();
    await expect(button).toHaveAttribute('aria-busy', 'true');
    await expect(button).toContainText('Sending…');
    await expect(button.locator('[data-spinner]')).toBeVisible();
    await button.click({ force: true }); // a second press does nothing
    await page.getByLabel('Name').press('Enter'); // nor does Enter in a field
    expect(posts).toBe(1);

    release();
    await expect(page.getByRole('status')).toContainText('Thanks');
    expect(posts).toBe(1, 'one message');
});

test('a problem found on the server is shown in the form', async ({ page }) => {
    await page.goto('/contact');
    await fillIn(page, 'See https://a.test, https://b.test and https://c.test');
    await page.getByRole('button', { name: 'Send' }).click();

    await expect(page.getByRole('alert')).toHaveText('Please include at most 2 links.');
    await expect(page.getByLabel('Message')).toHaveValue('See https://a.test, https://b.test and https://c.test');
    const button = page.getByRole('button', { name: 'Send' });
    await expect(button).toBeEnabled(); // ready to send again, once the answer is in
    await expect(button).toHaveAttribute('aria-busy', 'false');
});

test.describe('without JavaScript', () => {
    test.use({ javaScriptEnabled: false });

    test('the form is a normal post, and a refresh cannot send it twice', async ({ page }) => {
        await page.goto('/contact');
        await page.getByLabel('Name').fill('Ada Lovelace');
        await page.getByLabel('Email').fill('ada@example.test');
        await page.getByLabel('Message').fill('Hello, a question about TinyTank.');
        await page.waitForTimeout(humanPause);
        await page.getByRole('button', { name: 'Send' }).click();

        await expect(page).toHaveURL(/\/contact\?sent=1$/);
        await expect(page.getByRole('status')).toHaveText('Thanks! Your message is on its way.');
    });
});
