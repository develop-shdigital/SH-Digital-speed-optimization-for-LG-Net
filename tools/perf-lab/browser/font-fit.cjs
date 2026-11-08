// Find the size-adjust of a font fallback at which the fewest texts break into a different
// number of lines than in the web font. For each page (against the local replay, phone and
// desktop) it measures every text block in the first LIMIT px in the web font, then draws the
// same blocks in a local Arial face at each candidate size-adjust and counts the blocks whose
// height changes. Sum over the pages, then pick a value in the middle of the lowest range.
//
//   SITE=https://www.example.ch node browser/font-fit.cjs work/font-fit.json /path/=/abs/page.html ...
//
// MODE=weights (default): one face per family and weight, Arial Bold from weight 600.
// MODE=synthetic: one regular Arial face per family for all weights (the browser makes it bold).
// METRICS: JSON {"Family": [ascent, descent]} in em from the font files (default: LGnet's fonts);
// the overrides are converted for every size so the line height stays the same.
// RANGE=from,to,step (default 88,116,0.5); LIMIT (default 1400 px). Writes
// work/replay/current.json like lighthouse/run.mjs; PORT selects the replay (default 8443).
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const SITE = (process.env.SITE || '').replace(/\/$/, '');
const port = +(process.env.PORT || 8443);
const replayDir = process.env.REPLAY_DIR || path.join(__dirname, '..', 'work', 'replay');
const MODE = process.env.MODE || 'weights';
const LIMIT = +(process.env.LIMIT || 1400);
const RANGE = (process.env.RANGE || '88,116,0.5').split(',').map(Number);
const METRICS = JSON.parse(process.env.METRICS || '{"Archivo":[0.87806,0.21004],"Public Sans":[0.950072,0.225017],"Work Sans":[0.930069,0.243017]}');
const [outFile, ...pairs] = process.argv.slice(2);
if (!SITE || !outFile || !pairs.length) {
  console.error('usage: SITE=https://www.example.ch node browser/font-fit.cjs <out.json> /path/=/abs/page.html ...');
  process.exit(1);
}
const host = new URL(SITE).host;
const BOLD = 'local("Arial Bold"),local("Arial-BoldMT"),local("Liberation Sans Bold"),local("LiberationSans-Bold")';
const REG = 'local("Arial"),local("ArialMT"),local("Liberation Sans")';

(async () => {
  const browser = await chromium.launch({ args: [`--host-resolver-rules=MAP ${host} 127.0.0.1:${port}`, '--ignore-certificate-errors', '--proxy-server=http://127.0.0.1:9', `--proxy-bypass-list=${host}`] });
  const totals = {};
  for (const pair of pairs) {
    const [p, file] = pair.split('=');
    fs.writeFileSync(path.join(replayDir, 'current.json'), JSON.stringify({ [p]: path.resolve(file) }));
    for (const device of ['mobile', 'desktop']) {
      const ctx = await browser.newContext(device === 'mobile' ? { viewport: { width: 412, height: 823 }, deviceScaleFactor: 1.75, isMobile: true, hasTouch: true } : { viewport: { width: 1350, height: 940 } });
      const page = await ctx.newPage();
      await page.goto(SITE + p, { waitUntil: 'load', timeout: 60000 });
      await page.evaluate(() => document.fonts.ready);
      await page.waitForTimeout(300);
      const result = await page.evaluate(async ({ METRICS, BOLD, REG, LIMIT, MODE, RANGE, label }) => {
        const blocks = [];
        for (const el of document.querySelectorAll('body *')) {
          if (!Array.from(el.childNodes).some((n) => n.nodeType === 3 && n.textContent.trim())) continue;
          const r = el.getBoundingClientRect();
          const cs = getComputedStyle(el);
          if (!r.height || r.top + scrollY > LIMIT || cs.display === 'inline') continue;
          const fam = cs.fontFamily.split(',')[0].replace(/"/g, '').trim();
          if (!METRICS[fam]) continue;
          blocks.push({ el, fam, weight: cs.fontWeight, h: r.height, text: el.textContent.trim().slice(0, 30) });
        }
        const groups = {};
        for (const b of blocks) {
          const key = MODE === 'synthetic' ? `${b.fam}|all` : `${b.fam}|${b.weight}`;
          (groups[key] = groups[key] || []).push(b);
        }
        const style = document.createElement('style');
        document.head.appendChild(style);
        const out = {};
        for (const [key, list] of Object.entries(groups)) {
          const [fam, weight] = key.split('|');
          out[key] = { n: list.length, miss: {}, texts: {} };
          for (let s = RANGE[0]; s <= RANGE[1] + 1e-6; s += RANGE[2]) {
            const S = s.toFixed(2);
            const name = `Fit ${S}`;
            const face = MODE === 'synthetic' ? `src:${REG};` : `src:${+weight >= 600 ? BOLD : REG};font-weight:${weight};`;
            style.textContent = `@font-face{font-family:"${name}";${face}size-adjust:${S}%;ascent-override:${(METRICS[fam][0] / (s / 100) * 100).toFixed(2)}%;descent-override:${(METRICS[fam][1] / (s / 100) * 100).toFixed(2)}%;line-gap-override:0%}`;
            await document.fonts.load(`${MODE === 'synthetic' ? 400 : weight} 16px "${name}"`);
            for (const b of list) b.el.style.setProperty('font-family', `"${name}"`, 'important');
            const bad = list.filter((b) => Math.abs(b.el.getBoundingClientRect().height - b.h) > 1);
            out[key].miss[S] = bad.length;
            out[key].texts[S] = bad.slice(0, 3).map((b) => `${label}: ${b.weight} ${b.text}`);
            for (const b of list) b.el.style.removeProperty('font-family');
          }
        }
        style.remove();
        return out;
      }, { METRICS, BOLD, REG, LIMIT, MODE, RANGE, label: `${p} ${device}` });
      for (const [key, g] of Object.entries(result)) {
        const t = (totals[key] = totals[key] || { n: 0, miss: {}, texts: {} });
        t.n += g.n;
        for (const [S, m] of Object.entries(g.miss)) t.miss[S] = (t.miss[S] || 0) + m;
        for (const [S, texts] of Object.entries(g.texts)) t.texts[S] = (t.texts[S] || []).concat(texts);
      }
      await ctx.close();
    }
  }
  fs.writeFileSync(outFile, JSON.stringify(totals, null, 1));
  for (const [key, t] of Object.entries(totals).sort()) {
    const entries = Object.entries(t.miss).map(([S, m]) => [parseFloat(S), m]);
    const best = Math.min(...entries.map((e) => e[1]));
    const at = entries.filter((e) => e[1] === best).map((e) => e[0].toFixed(2));
    console.log(`${key.padEnd(18)} texts ${String(t.n).padStart(4)}  fewest changed ${best} at size-adjust ${at.join(', ')}`);
  }
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
