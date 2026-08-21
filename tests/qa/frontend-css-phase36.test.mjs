import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const css = fs.readFileSync(new URL('../../assets/css/frontend.css', import.meta.url), 'utf8');

const blocks = [...css.matchAll(/([^{}]+)\{([^{}]*)\}/g)].map((m) => ({
  selector: m[1].trim().replace(/\s+/g, ' '),
  body: m[2].trim().replace(/\s+/g, ' '),
}));
const bodiesFor = (selector) => blocks.filter((b) => b.selector === selector).map((b) => b.body);

test('phase36 removes superseded package-card CTA and spacing fallbacks', () => {
  assert.ok(!bodiesFor('.sltr-package-card h3').some((b) => b === 'margin-top: 0;'));
  assert.ok(!bodiesFor('.sltr-package-card .sltr-package-price').some((b) => b === 'margin-bottom: 18px;'));
  assert.ok(!bodiesFor('.sltr-package .sltr-package-price').some((b) => b === 'margin-bottom: 18px;'));
  assert.ok(!bodiesFor('.sltr-package-card .sltr-select-button').some((b) => b.includes('max-width: 260px')));
  assert.ok(!bodiesFor('.sltr-package .sltr-select-button').some((b) => b.includes('max-width: 260px')));
  assert.match(css, /\.sltr-package \.sltr-select-button,\s*\.sltr-package-card \.sltr-select-button\s*\{[\s\S]*?position:\s*absolute;[\s\S]*?bottom:\s*10px;[\s\S]*?pointer-events:\s*none;[\s\S]*?\}/);
  assert.match(css, /\.sltr-package strong,\s*\.sltr-package-card h3\s*\{[\s\S]*?margin-top:\s*-6px\s*!important;[\s\S]*?margin-bottom:\s*0\s*!important;[\s\S]*?\}/);
  assert.match(css, /\.sltr-package \.sltr-package-price,\s*\.sltr-package-card \.sltr-package-price\s*\{[\s\S]*?margin-bottom:\s*8px\s*!important;[\s\S]*?\}/);
});
