// Which texts change size when the web fonts arrive? Loads each page twice against the local
// replay, once with every .woff2 blocked (so the fallback fonts stay) and once normally, and
// compares the boxes of headings, paragraphs, list items and buttons in the first 1400 px.
// A text that breaks into a different number of lines moves everything below it.
//
//   SITE=https://www.example.ch node browser/font-swap.cjs /path/=/abs/page.html [/other/=/abs/other.html ...]
//
// Writes work/replay/current.json (the page under test) like lighthouse/run.mjs; PORT selects
// the replay (default 8443).
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const SITE = (process.env.SITE || '').replace(/\/$/, '');
const port = +(process.env.PORT || 8443);
const replayDir = process.env.REPLAY_DIR || path.join(__dirname, '..', 'work', 'replay');
const LIMIT = +(process.env.LIMIT || 1400);
const pairs = process.argv.slice(2);
if (!SITE || !pairs.length) {
  console.error('usage: SITE=https://www.example.ch node browser/font-swap.cjs /path/=/abs/page.html ...');
  process.exit(1);
}
const host = new URL(SITE).host;

async function boxes(browser, p, device, blockFonts) {
  const ctx = await browser.newContext(device === 'mobile' ? { viewport: { width: 412, height: 823 }, deviceScaleFactor: 1.75, isMobile: true, hasTouch: true } : { viewport: { width: 1350, height: 940 } });
  const page = await ctx.newPage();
  if (blockFonts) await page.route(/\.woff2(\?|$)/, (r) => r.abort());
  await page.goto(SITE + p, { waitUntil: 'load', timeout: 60000 });
  await page.evaluate(() => document.fonts.ready);
  await page.waitForTimeout(1000);
  const rows = await page.evaluate((limit) => Array.from(document.querySelectorAll('h1, h2, h3, h4, p, li, .elementor-button, .elementor-widget')).map((el) => {
    const r = el.getBoundingClientRect();
    const cs = getComputedStyle(el);
    return { tag: el.tagName, text: (el.innerText || '').trim().replace(/\s+/g, ' ').slice(0, 40), y: Math.round(r.top + scrollY), h: Math.round(r.height), font: cs.fontFamily.split(',')[0].replace(/"/g, ''), weight: cs.fontWeight, size: cs.fontSize };
  }).filter((r) => r.h > 0 && r.y <= limit), LIMIT);
  await ctx.close();
  return rows;
}

(async () => {
  const browser = await chromium.launch({ args: [`--host-resolver-rules=MAP ${host} 127.0.0.1:${port}`, '--ignore-certificate-errors', '--proxy-server=http://127.0.0.1:9', `--proxy-bypass-list=${host}`] });
  for (const pair of pairs) {
    const [p, file] = pair.split('=');
    fs.writeFileSync(path.join(replayDir, 'current.json'), JSON.stringify({ [p]: path.resolve(file) }));
    for (const device of ['mobile', 'desktop']) {
      const fallback = await boxes(browser, p, device, true);
      const web = await boxes(browser, p, device, false);
      const changed = [];
      for (let i = 0; i < Math.min(fallback.length, web.length); i++) {
        if (fallback[i].y !== web[i].y || fallback[i].h !== web[i].h) changed.push([fallback[i], web[i]]);
      }
      const texts = changed.filter(([a, b]) => a.h !== b.h && /^(H\d|P|LI)$/.test(a.tag));
      const move = Math.max(0, ...changed.map(([a, b]) => Math.abs(a.y - b.y)));
      console.log(JSON.stringify({ path: p, device, changed: changed.length, maxMove: move, texts: texts.slice(0, 5).map(([a, b]) => `${a.tag} "${a.text}" ${a.font} ${a.weight} ${a.size}: ${a.h} -> ${b.h} px`) }));
    }
  }
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
