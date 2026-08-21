import fs from 'node:fs';
import assert from 'node:assert/strict';
const read = rel => fs.readFileSync(new URL('../../' + rel, import.meta.url), 'utf8');

const availability = read('includes/Domain/Availability/AvailabilityService.php');
const editor = read('includes/Admin/Views/package-form/sections/solo-content.php');
const js = read('assets/js/admin-package-editor.js');

assert.match(availability, /\$working_windows = !empty\(\$package\['open_247'\]\)/);
assert.doesNotMatch(availability, /!empty\(\$package\['open_247'\]\) && \$hours_mode === 'custom'/);

assert.match(editor, /sltr-package-media-validation/);
assert.match(js, /allowedImageMimes = \['image\/jpeg', 'image\/png', 'image\/gif', 'image\/webp'\]/);
assert.match(js, /width < 300 \|\| height < 300/);
assert.match(js, /Minimum size: 300 × 300 px/);
assert.match(js, /Unsupported image type\. Use JPG, PNG, GIF or WebP\./);
assert.match(js, /if \(!selected\.length && selection\.length\) return;/);

console.log('Open 24/7 overrides working-hours mode and slider image rejection is explained.');
