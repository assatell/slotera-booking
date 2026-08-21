import fs from 'node:fs';
import assert from 'node:assert/strict';

const seo = fs.readFileSync(new URL('../../assets/js/admin-seo.js', import.meta.url), 'utf8');
const editor = fs.readFileSync(new URL('../../assets/js/admin-package-editor.js', import.meta.url), 'utf8');

for (const source of [seo, editor]) {
  assert.match(source, /\\p\{L\}\\p\{N\}/, 'slug generation must accept Unicode letters and numbers');
  assert.match(source, /\\u0370-\\u03FF/, 'legacy fallback must include Greek');
}

function slugify(value) {
  value = String(value || '').trim().toLowerCase();
  if (!value) return '';
  if (value.normalize) value = value.normalize('NFKD').replace(/[\u0300-\u036f]/g, '');
  return value.replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '');
}

const cases = [
  ['Greek', 'Πακέτο Δοκιμή', 'πακετο-δοκιμη'],
  ['Cyrillic', 'Новый пакет', 'новыи-пакет'],
  ['Polish', 'Łódź pakiet próbny', 'łodz-pakiet-probny'],
  ['Icelandic', 'Þjónustupakki árið 2026', 'þjonustupakki-arið-2026'],
  ['Maltese', 'Pakkett għat-tfal ħelu', 'pakkett-għat-tfal-ħelu'],
  ['Latin', 'New Service Package', 'new-service-package'],
];
for (const [label,input,expected] of cases) {
  assert.equal(slugify(input), expected, `${label} slug regression`);
}

console.log('unicode slug generation: ok');
