// Visits every demo page on desktop and mobile, records HTTP status + console errors,
// and saves full-page screenshots. Usage: node scripts/smoke-screenshots.mjs <baseUrl> <urls.json> <outDir>
import { chromium } from 'playwright';
import fs from 'node:fs';

const [base, urlsFile, out] = process.argv.slice(2);
const urls = JSON.parse(fs.readFileSync(urlsFile, 'utf8'));
fs.mkdirSync(out, { recursive: true });

const browser = await chromium.launch();
const report = [];
let failures = 0;

for (const [device, viewport] of [['desktop', { width: 1440, height: 900 }], ['mobile', { width: 390, height: 844 }]]) {
    const context = await browser.newContext({ viewport, deviceScaleFactor: device === 'mobile' ? 2 : 1, isMobile: device === 'mobile', hasTouch: device === 'mobile' });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(String(e)));
    page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });

    // Sign in (the demo login form is pre-filled).
    await page.goto(base + '/admin/login', { waitUntil: 'networkidle' });
    await page.locator('button[type=submit]').first().click();
    await page.waitForURL(/\/admin(\/)?$/, { timeout: 20000 }).catch(() => {});

    for (const [name, path] of Object.entries(urls)) {
        errors.length = 0;
        let status = 0;
        try {
            const res = await page.goto(base + path, { waitUntil: 'networkidle', timeout: 30000 });
            status = res ? res.status() : 0;
            await page.waitForTimeout(300);
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
            await page.screenshot({ path: `${out}/${device}-${name}.png`, fullPage: device === 'desktop' ? false : true });
            const bad = status >= 400 || status === 0;
            if (bad) failures++;
            report.push({ device, name, path, status, overflow, errors: [...errors].slice(0, 5) });
            console.log(`${bad ? 'FAIL' : 'ok  '} ${device} ${status} ${name}${overflow > 2 ? ` (horizontal overflow ${overflow}px)` : ''}${errors.length ? ' console: ' + errors[0].slice(0, 160) : ''}`);
        } catch (e) {
            failures++;
            report.push({ device, name, path, status, error: String(e) });
            console.log(`FAIL ${device} ${name}: ${e}`);
        }
    }
    await context.close();
}

await browser.close();
fs.writeFileSync(`${out}/report.json`, JSON.stringify(report, null, 2));
console.log(`\n${failures} failing page(s)`);
process.exit(failures ? 1 : 0);
