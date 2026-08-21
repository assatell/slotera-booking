<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Infrastructure\Repositories\CategoryRepository;
use Slotera\Infrastructure\Repositories\LocationRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class SitemapService
{
    public function register_hooks(): void
    {
        add_action('init', [$this, 'register_rewrite']);
        add_filter('query_vars', [$this, 'query_vars']);
        add_action('template_redirect', [$this, 'maybe_render']);
    }

    public function register_rewrite(): void
    {
        add_rewrite_rule('^slotera-sitemap\\.xml$', 'index.php?sltr_sitemap=1', 'top');
        add_rewrite_rule('^slotera-sitemap\\.xsl$', 'index.php?sltr_sitemap_xsl=1', 'top');
    }

    public function query_vars(array $vars): array
    {
        $vars[] = 'sltr_sitemap';
        $vars[] = 'sltr_sitemap_xsl';
        return $vars;
    }

    public function maybe_render(): void
    {
        $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        $basename = basename($path);
        $is_pretty_url = $basename === 'slotera-sitemap.xml';
        $is_stylesheet_url = $basename === 'slotera-sitemap.xsl';

        if ((string) get_query_var('sltr_sitemap_xsl') === '1' || isset($_GET['sltr_sitemap_xsl']) || $is_stylesheet_url) {
            $this->render_stylesheet();
        }

        if ((string) get_query_var('sltr_sitemap') !== '1' && !isset($_GET['sltr_sitemap']) && !$is_pretty_url) {
            return;
        }

        $settings = (new SettingsRepository())->all();
        if (empty($settings['seo_sitemap_enabled']) || $this->external_seo_plugin_active()) {
            status_header(404);
            exit;
        }

        status_header(200);
        nocache_headers();
        header('Content-Type: application/xml; charset=UTF-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?xml-stylesheet type="text/xsl" href="' . esc_url(home_url('/slotera-sitemap.xsl')) . '"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($this->entries() as $entry) {
            echo "  <url>\n";
            echo '    <loc>' . esc_url($entry['loc']) . "</loc>\n";
            if (!empty($entry['lastmod'])) {
                echo '    <lastmod>' . esc_html($entry['lastmod']) . "</lastmod>\n";
            }
            echo '    <changefreq>' . esc_html($entry['changefreq'] ?? 'weekly') . "</changefreq>\n";
            echo '    <priority>' . esc_html($entry['priority'] ?? '0.8') . "</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    private function render_stylesheet(): void
    {
        status_header(200);
        nocache_headers();
        header('Content-Type: application/xml; charset=UTF-8');

        echo <<<'XSL'
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">
    <xsl:output method="html" encoding="UTF-8" indent="yes"/>
    <xsl:template match="/">
        <html>
            <head>
                <title>Slotera XML Sitemap</title>
                <meta name="viewport" content="width=device-width, initial-scale=1"/>
                <style>
                    body { margin: 0; padding: 32px; background: #f6f7fb; color: #1f2937; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
                    .wrap { max-width: 1100px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08); overflow: hidden; }
                    header { padding: 26px 30px; border-bottom: 1px solid #e5e7eb; }
                    h1 { margin: 0 0 8px; font-size: 24px; line-height: 1.25; }
                    p { margin: 0; color: #6b7280; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 14px 18px; border-bottom: 1px solid #eef2f7; text-align: left; vertical-align: top; }
                    th { background: #f9fafb; color: #374151; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; }
                    tr:last-child td { border-bottom: none; }
                    a { color: #2563eb; text-decoration: none; word-break: break-word; }
                    a:hover { text-decoration: underline; }
                    .muted { color: #6b7280; }
                    .count { display: inline-block; margin-top: 12px; padding: 6px 10px; border-radius: 999px; background: #eef2ff; color: #3730a3; font-size: 13px; }
                    @media (max-width: 700px) { body { padding: 16px; } th:nth-child(2), td:nth-child(2), th:nth-child(3), td:nth-child(3) { display: none; } }
                </style>
            </head>
            <body>
                <div class="wrap">
                    <header>
                        <h1>Slotera XML Sitemap</h1>
                        <p>This sitemap is generated automatically for indexable Slotera packages, categories, local pages, ordinary WordPress pages and posts selected in SEO Settings.</p>
                        <span class="count"><xsl:value-of select="count(sitemap:urlset/sitemap:url)"/> URLs</span>
                    </header>
                    <table>
                        <thead>
                            <tr>
                                <th>URL</th>
                                <th>Last modified</th>
                                <th>Change frequency</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            <xsl:for-each select="sitemap:urlset/sitemap:url">
                                <tr>
                                    <td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td>
                                    <td class="muted"><xsl:value-of select="sitemap:lastmod"/></td>
                                    <td class="muted"><xsl:value-of select="sitemap:changefreq"/></td>
                                    <td class="muted"><xsl:value-of select="sitemap:priority"/></td>
                                </tr>
                            </xsl:for-each>
                        </tbody>
                    </table>
                </div>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
XSL;
        exit;
    }

    /**
     * @return array<int, array{loc:string,lastmod:string,changefreq?:string,priority?:string}>
     */
    private function entries(): array
    {
        $entries = [];
        $settings = (new SettingsRepository())->all();
        $location_repo = new LocationRepository();

        if (!empty($settings['seo_sitemap_include_packages'])) {
            foreach ((new PackageRepository())->get_active(500, 0) as $package) {
                if (empty($package['solo_page_enabled'])) {
                    continue;
                }
                $url = $this->permalink((int) ($package['page_id'] ?? 0));
                if ($url !== '' && $this->is_indexable_entity($url, $package)) {
                    $entries[] = $this->entry($url, (string) ($package['updated_at'] ?? ''), 'weekly', '0.8');
                }
            }
        }

        if (!empty($settings['seo_sitemap_include_categories'])) {
            foreach ((new CategoryRepository())->get_active() as $category) {
                $url = $this->permalink((int) ($category['page_id'] ?? 0));
                if ($url !== '' && $this->is_indexable_entity($url, $category)) {
                    $entries[] = $this->entry($url, (string) ($category['updated_at'] ?? ''), 'weekly', '0.8');
                }
            }
        }

        if (!empty($settings['seo_sitemap_include_locations'])) {
            foreach ($location_repo->get_active() as $location) {
                $city_url = LocalRouteService::city_url($location);
                if ($city_url !== '') {
                    $entries[] = $this->entry($city_url, (string) ($location['updated_at'] ?? ''), 'weekly', '0.7');
                }

                foreach ($location_repo->get_packages_for_location((int) ($location['id'] ?? 0), 500) as $package) {
                    $local_url = LocalRouteService::url($location, $package);
                    if ($local_url === '' || !$this->is_indexable_entity($local_url, $package)) {
                        continue;
                    }
                    if ($local_url !== '') {
                        $entries[] = $this->entry($local_url, (string) ($package['updated_at'] ?? ''), 'weekly', '0.7');
                    }
                }
            }
        }

        if (!empty($settings['seo_sitemap_include_other_pages']) && !empty($settings['seo_wp_pages_enabled'])) {
            foreach ($this->other_pages() as $page) {
                $url = get_permalink($page);
                if (!is_string($url) || $url === '') {
                    continue;
                }
                $entries[] = $this->entry($url, (string) ($page->post_modified_gmt ?: $page->post_modified), 'monthly', '0.6');
            }
        }

        if (!empty($settings['seo_sitemap_include_posts'])) {
            foreach ($this->posts() as $post) {
                $url = get_permalink($post);
                if (!is_string($url) || $url === '') {
                    continue;
                }
                $entries[] = $this->entry($url, (string) ($post->post_modified_gmt ?: $post->post_modified), 'monthly', '0.6');
            }
        }

        return $this->unique_entries($entries);
    }

    /** @return array<int, \WP_Post> */
    private function other_pages(): array
    {
        $excluded = $this->slotera_page_ids();
        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 1000,
            'orderby' => 'modified',
            'order' => 'DESC',
            'post__not_in' => $excluded,
            'meta_query' => [
                [
                    'key' => '_sltr_wp_page_seo_enabled',
                    'value' => '1',
                    'compare' => '=',
                ],
            ],
        ]);

        return is_array($pages) ? array_values(array_filter($pages, [$this, 'is_indexable_wp_page'])) : [];
    }

    /** @return array<int, \WP_Post> */
    private function posts(): array
    {
        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 1000,
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);
        return is_array($posts) ? array_values(array_filter($posts, [$this, 'is_indexable_post'])) : [];
    }

    private function is_indexable_wp_page(\WP_Post $page): bool
    {
        if (!$this->is_public_post($page)) {
            return false;
        }
        if ((int) get_post_meta($page->ID, '_sltr_wp_page_seo_noindex', true) === 1) {
            return false;
        }
        if (trim((string) get_post_meta($page->ID, '_sltr_wp_page_seo_redirect_301', true)) !== '') {
            return false;
        }
        if (!$this->canonical_points_to_self((string) get_post_meta($page->ID, '_sltr_wp_page_seo_canonical', true), (string) get_permalink($page))) {
            return false;
        }
        if ($this->contains_slotera_shortcode((string) $page->post_content)) {
            return false;
        }
        if ($this->is_system_page($page)) {
            return false;
        }
        return true;
    }

    private function is_indexable_post(\WP_Post $post): bool
    {
        return $this->is_public_post($post) && !$this->is_attachment_page($post);
    }

    private function is_public_post(\WP_Post $post): bool
    {
        if ($post->post_status !== 'publish') {
            return false;
        }
        if ($post->post_type === 'attachment') {
            return false;
        }
        if ((string) $post->post_password !== '') {
            return false;
        }
        return true;
    }

    private function is_indexable_entity(string $url, array $entity): bool
    {
        if (!$this->is_indexable((string) ($entity['seo_robots'] ?? 'index,follow'))) {
            return false;
        }
        if (trim((string) ($entity['seo_redirect_301'] ?? '')) !== '') {
            return false;
        }
        if (!$this->canonical_points_to_self((string) ($entity['seo_canonical'] ?? ''), $url)) {
            return false;
        }
        return true;
    }

    private function canonical_points_to_self(string $canonical, string $url): bool
    {
        $canonical = trim($canonical);
        if ($canonical === '') {
            return true;
        }
        $canonical_url = wp_validate_redirect(esc_url_raw($canonical), '');
        if ($canonical_url === '') {
            return false;
        }
        return untrailingslashit($canonical_url) === untrailingslashit($url);
    }

    private function is_attachment_page(\WP_Post $post): bool
    {
        return $post->post_type === 'attachment';
    }

    private function is_system_page(\WP_Post $page): bool
    {
        $slug = sanitize_title((string) $page->post_name);
        $blocked_slugs = [
            'account',
            'my-account',
            'login',
            'register',
            'forgot-password',
            'password-reset',
            'booking-success',
            'booking-cancel',
            'booking-cancelled',
            'thank-you',
            'checkout',
            'cart',
            'reschedule',
            'cancel-booking',
            'privacy-policy',
        ];
        if (in_array($slug, $blocked_slugs, true)) {
            return true;
        }

        $content = strtolower((string) $page->post_content);
        $system_markers = [
            '[slotera_account',
            '[slotera_login',
            '[slotera_thank_you',
            '[slotera_checkout',
            '[slotera_cancel',
            '[slotera_reschedule',
        ];
        foreach ($system_markers as $marker) {
            if (strpos($content, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, int> */
    private function slotera_page_ids(): array
    {
        $ids = [];
        foreach ((new PackageRepository())->get_active(500, 0) as $package) {
            $ids[] = (int) ($package['page_id'] ?? 0);
        }
        foreach ((new CategoryRepository())->get_active() as $category) {
            $ids[] = (int) ($category['page_id'] ?? 0);
        }
        return array_values(array_unique(array_filter($ids)));
    }

    private function contains_slotera_shortcode(string $content): bool
    {
        return preg_match('/\[(slotera_booking|slotera_category|slotera_packages|slotera_categories|slotera_thank_you|slotera_checkout|slotera_contact|slotera_login|slotera_account)\b/i', $content) === 1;
    }

    private function permalink(int $page_id): string
    {
        if ($page_id <= 0) {
            return '';
        }
        $url = get_permalink($page_id);
        return is_string($url) ? $url : '';
    }

    private function is_indexable(string $robots): bool
    {
        return stripos($robots, 'noindex') === false;
    }

    private function entry(string $url, string $mysql, string $changefreq, string $priority): array
    {
        return ['loc' => $url, 'lastmod' => $this->lastmod($mysql), 'changefreq' => $changefreq, 'priority' => $priority];
    }

    /** @param array<int, array{loc:string,lastmod:string,changefreq?:string,priority?:string}> $entries */
    private function unique_entries(array $entries): array
    {
        $seen = [];
        $unique = [];
        foreach ($entries as $entry) {
            $key = untrailingslashit((string) ($entry['loc'] ?? ''));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $entry;
        }
        return $unique;
    }

    private function external_seo_plugin_active(): bool
    {
        return (bool) apply_filters('sltr_external_seo_plugin_active', count((new SEOService())->detected_external_plugins()) > 0);
    }

    private function lastmod(string $mysql): string
    {
        $ts = $mysql !== '' ? strtotime($mysql) : false;
        return $ts ? gmdate('Y-m-d', $ts) : gmdate('Y-m-d');
    }
}
