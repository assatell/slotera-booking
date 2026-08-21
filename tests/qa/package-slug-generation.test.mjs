import fs from 'node:fs';
import assert from 'node:assert/strict';

const editor = fs.readFileSync(new URL('../../assets/js/admin-package-editor.js', import.meta.url), 'utf8');
const serviceProvider = fs.readFileSync(new URL('../../includes/Admin/AdminServiceProvider.php', import.meta.url), 'utf8');
const identity = fs.readFileSync(new URL('../../includes/Admin/Views/package-form/sections/identity.php', import.meta.url), 'utf8');

assert.match(identity, /class="button sltr-generate-slug"/, 'package form must expose the generate slug control');
assert.match(serviceProvider, /sltr-admin-package-editor/, 'package editor bundle must load on package pages');
assert.match(editor, /closest\('\.sltr-generate-slug'\)/, 'package editor must provide a fallback slug handler');
assert.match(editor, /target\.dispatchEvent\(new Event\('input'/, 'slug generation must notify dependent UI');
console.log('package slug generation fallback: ok');
