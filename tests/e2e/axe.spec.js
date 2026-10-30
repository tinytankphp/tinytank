import AxeBuilder from '@axe-core/playwright';
import { expect, test } from './fixtures.js';

// Every page in the sitemap, in the light and the dark theme, checked with axe-core
// (https://github.com/dequelabs/axe-core): WCAG 2.2 AA and axe's best practices. Any problem fails
// the test, so CI stops before it's published. `npm run test:a11y` runs this file alone (after
// `npm run build`): see the docs, Accessibility → Checking.
//
// A problem that can't be fixed right away can be accepted here, visibly, with its reason. Keep this
// list empty: [rule, part of the element's selector, why], e.g.
//   ['color-contrast', '.legacy-banner', 'replaced in the next release (issue #12)'],
const EXCEPTIONS = [];

const STANDARDS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'];

for (const colorScheme of ['light', 'dark']) {
    test.describe(`the ${colorScheme} theme`, () => {
        test.use({ colorScheme });

        test('every page passes the accessibility checks', async ({ page }) => {
            test.setTimeout(180_000);
            const sitemap = await (await page.request.get('/sitemap.xml')).text();
            const paths = [...sitemap.matchAll(/<loc>([^<]+)<\/loc>/g)].map(([, url]) => new URL(url).pathname);
            expect(paths.length).toBeGreaterThan(0);

            const problems = [];
            for (const path of paths) {
                await page.goto(path);
                const { violations } = await new AxeBuilder({ page }).withTags(STANDARDS).analyze();
                for (const violation of violations) {
                    for (const node of violation.nodes) {
                        const element = node.target.join(' ');
                        if (EXCEPTIONS.some(([rule, selector]) => rule === violation.id && element.includes(selector))) {
                            continue;
                        }
                        problems.push(`${path}: ${violation.help} (${violation.id}, ${violation.impact})\n    ${element}\n    ${node.failureSummary.replace(/\n\s*/g, ' ')}\n    ${violation.helpUrl}`);
                    }
                }
            }
            expect(problems, `${problems.length} accessibility problem(s):\n\n${problems.join('\n\n')}`).toEqual([]);
        });
    });
}
