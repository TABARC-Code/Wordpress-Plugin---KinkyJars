/* End-to-end: a real WordPress (WordPress Playground) in a real browser, doing exactly what
   INSTALL.md tells a newbie to do: upload the zip, activate, set up, publish, visit.
   It also uploads a zip shaped like GitHub's "Source code" download, to prove the guide's
   warning about it is true.
   Needs: npx (for @wp-playground/cli), zip, and playwright. CHROMIUM_PATH reuses a Chromium.
   Usage: node tests/e2e.cjs [path to kink-jars.zip, default dist/kink-jars.zip] */
const { spawn, execFileSync } = require('node:child_process');
const assert = require('node:assert/strict');
const fs = require('node:fs'), os = require('node:os'), path = require('node:path');
const { chromium } = require('playwright');

const zipPath = path.resolve(process.argv[2] || path.join(__dirname, '../dist/kink-jars.zip'));
// A stand-in for GitHub's green Code > Download ZIP: the whole repository inside one extra folder.
const work = fs.mkdtempSync(path.join(os.tmpdir(), 'kj-'));
const sourceZip = path.join(work, 'Wordpress-Plugin---KinkyJars-main.zip');
fs.mkdirSync(path.join(work, 'Wordpress-Plugin---KinkyJars-main'));
execFileSync('unzip', ['-q', zipPath, '-d', path.join(work, 'Wordpress-Plugin---KinkyJars-main')]);
execFileSync('zip', ['-qr', sourceZip, 'Wordpress-Plugin---KinkyJars-main'], { cwd: work });
const PORT = 9411, BASE = `http://127.0.0.1:${PORT}`;
const blueprint = path.join(os.tmpdir(), 'kink-jars-blueprint.json');
fs.writeFileSync(blueprint, JSON.stringify({ steps: [{ step: 'login' }] }));

function step(name) { console.log('ok   ' + name); }
// SCREENSHOTS=docs/images saves the pictures used in the guide, from this very run.
const shots = process.env.SCREENSHOTS;
async function shot(page, name, el) {
  if (!shots) return;
  fs.mkdirSync(shots, { recursive: true });
  // WordPress 7 fades between admin screens; wait it out so pictures don't show two pages at once.
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(1200);
  await (el || page).screenshot({ path: path.join(shots, name + '.jpg'), type: 'jpeg', quality: 72 });
}

(async () => {
  const server = spawn('npx', ['-y', '@wp-playground/cli@latest', 'server', `--port=${PORT}`,
    `--blueprint=${blueprint}`], { stdio: ['ignore', 'pipe', 'pipe'], detached: true });
  let log = ''; server.stdout.on('data', d => log += d); server.stderr.on('data', d => log += d);
  const stop = () => { try { process.kill(-server.pid); } catch (e) {} };
  try {
    for (let i = 0; !/Ready!/.test(log); i++) {
      if (i > 200) throw new Error('WordPress did not start:\n' + log);
      await new Promise(r => setTimeout(r, 1500));
    }
    const browser = await chromium.launch({ executablePath: process.env.CHROMIUM_PATH || undefined, args: ['--no-sandbox'] });
    const page = await browser.newPage({ viewport: { width: 1280, height: 860 } });
    const errors = [];
    page.on('pageerror', e => errors.push(e.message));
    page.on('dialog', d => d.accept());

    // The wrong download first: WordPress should refuse it
    await page.goto(BASE + '/wp-admin/plugin-install.php?tab=upload');
    await page.setInputFiles('#pluginzip', sourceZip);
    await page.click('#install-plugin-submit');
    await page.waitForSelector('text=No valid plugins were found.', { timeout: 60000 });
    await shot(page, '0-wrong-zip');
    step('GitHub "Source code" style zip is refused: "No valid plugins were found."');

    // Guide steps: Plugins > Add Plugin > Upload Plugin > Install Now > Activate Plugin
    await page.goto(BASE + '/wp-admin/plugin-install.php?tab=upload');
    await page.setInputFiles('#pluginzip', zipPath);
    await shot(page, '1-upload-plugin');
    await page.click('#install-plugin-submit');
    await page.waitForSelector('text=Plugin installed successfully.', { timeout: 60000 });
    await shot(page, '2-installed');
    await page.click('a:has-text("Activate Plugin")');
    await page.waitForSelector('tr[data-slug="kink-jars"].active');
    const wpVersion = await page.evaluate(() => (document.querySelector('#footer-upgrade') || {}).textContent || '');
    step('kink-jars.zip uploads, installs and activates (' + wpVersion.trim() + ')');

    // The welcome notice and the "Set up" link both lead to the settings page
    assert.ok(await page.isVisible('text=Kink Jars is installed.'), 'welcome notice missing');
    assert.ok(await page.isVisible('tr[data-slug="kink-jars"] a:has-text("Set up")'), 'Set up link missing');
    await shot(page, '3-welcome-notice');
    await page.click('a.button-primary:has-text("Set up Kink Jars")');
    await page.waitForSelector('h1:has-text("Kink Jars")');
    await shot(page, '4-settings-page');
    step('welcome notice and Set up link lead to Settings > Kink Jars');

    // Guide step: Create my Kink Jars page (wide is the default)
    await page.click('input[type=submit][value="Create my Kink Jars page"]');
    await page.waitForSelector('.notice-success:has-text("saved as a draft")');
    assert.ok(await page.isVisible('text=a draft, so only you can see it'));
    await shot(page, '5-draft-created');
    step('one click makes a draft page');

    // Guide step: Publish it now
    await page.click('button:has-text("Publish it now")');
    await page.waitForSelector('.notice-success:has-text("Published")');
    const href = await page.getAttribute('a:has-text("View page")', 'href');
    step('publish button makes it live');

    // The page a visitor sees
    await page.goto(href);
    const embed = await page.waitForSelector('.kink-jars-embed.alignwide iframe');
    const width = await embed.evaluate(el => el.getBoundingClientRect().width);
    assert.ok(width > 900, `iframe only ${width}px wide`);
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth), 1280);
    const frame = await embed.contentFrame();
    await frame.waitForSelector('#ageGate[open]');
    await shot(page, '6-visitor-age-gate');
    await frame.click('#ageYes');
    await frame.click('.flag[data-id="trans"]');
    await frame.click('.q >> nth=0 >> .scale button:nth-child(5)');
    await embed.scrollIntoViewIfNeeded();
    await frame.click('#save');
    await frame.waitForSelector('#modal[open]');
    assert.ok(await frame.evaluate(() => document.getElementById('modalImg').naturalWidth) > 1000);
    await frame.click('#closeModal');
    await frame.evaluate(() => scrollTo(0, 0));
    await page.evaluate(() => scrollTo(0, 0));
    await page.waitForTimeout(300);
    await shot(page, '7-visitor-page');
    const fontsOk = await frame.evaluate(async () => (await document.fonts.load('700 20px Oswald')).length > 0);
    assert.ok(fontsOk, 'bundled fonts did not load');
    step(`visitor page: wide (${Math.round(width)}px), age gate, palette, rating, PNG save, fonts`);

    // Guide: updating. Upload the same zip again; WordPress should offer to replace it.
    await page.goto(BASE + '/wp-admin/plugin-install.php?tab=upload');
    await page.setInputFiles('#pluginzip', zipPath);
    await page.click('#install-plugin-submit');
    await page.waitForSelector('text=Replace current with uploaded', { timeout: 60000 });
    await page.click('text=Replace current with uploaded');
    await page.waitForSelector('text=/Plugin (updated|installed) successfully/', { timeout: 60000 });
    const updatedText = await page.textContent('.wrap');
    await page.goto(href);
    await page.waitForSelector('.kink-jars-embed.alignwide iframe');
    step('re-uploading offers "Replace current with uploaded" and the page survives (' + (updatedText.match(/Plugin \w+ successfully/) || ['?'])[0] + ')');

    assert.deepEqual(errors, []);
    step('no JavaScript errors');
    await browser.close();
  } finally { stop(); fs.rmSync(work, { recursive: true, force: true }); }
})().catch(e => { console.error('FAIL ' + e.message); process.exit(1); });
