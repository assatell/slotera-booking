import fs from 'node:fs';
import assert from 'node:assert/strict';

const sanitizer = fs.readFileSync(new URL('../../includes/Core/HtmlSanitizer.php', import.meta.url), 'utf8');
const shortcode = fs.readFileSync(new URL('../../includes/Frontend/Shortcodes/BookingShortcode.php', import.meta.url), 'utf8');

assert.match(sanitizer, /'video'\s*=>\s*\[/);
assert.match(sanitizer, /'source'\s*=>\s*\[/);
for (const attr of ['controls','playsinline','preload','autoplay','muted']) {
  assert.ok(sanitizer.includes(`'${attr}' => true`), `video allowlist missing ${attr}`);
}
assert.ok(sanitizer.includes("'src' => true"), 'source allowlist missing src');
assert.ok(sanitizer.includes("'type' => true"), 'source allowlist missing type');

assert.match(shortcode, /<video controls playsinline preload="metadata"/);
assert.match(shortcode, /<source src="<\?php echo esc_url\(\$video_url\); \?>" type="<\?php echo esc_attr\(\$video_mime\); \?>">/);

console.log('package video sanitizer allowlist: ok');
