<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Core\Database;
use Slotera\Infrastructure\Repositories\LocationRepository;
use Slotera\Infrastructure\Repositories\PackageRepository;
use Slotera\Infrastructure\Repositories\SettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class SEOService
{
    public function register_hooks(): void
    {
        add_filter('pre_get_document_title', [$this, 'filter_document_title'], 20);
        add_action('wp_head', [$this, 'render_head'], 2);
        add_action('template_redirect', [$this, 'maybe_redirect_301'], 1);
    }


    public function maybe_redirect_301(): void
    {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        if (!$this->slotera_redirects_enabled()) {
            return;
        }

        $url = $this->current_redirect_url();
        if ($url === '') {
            return;
        }

        $request_path = (string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        if ($request_path === '') {
            $request_path = '/';
        }
        $current = home_url($request_path);
        if (untrailingslashit($url) === untrailingslashit($current)) {
            return;
        }

        $trace = isset($_COOKIE['sltr_redirect_trace']) ? json_decode(stripslashes((string) $_COOKIE['sltr_redirect_trace']), true) : [];
        if (!is_array($trace)) {
            $trace = [];
        }

        $current_norm = untrailingslashit($current);
        $target_norm = untrailingslashit($url);

        if (in_array($target_norm, $trace, true)) {
            return;
        }

        $trace[] = $current_norm;
        $trace = array_slice(array_values(array_unique($trace)), -5);

        if (!headers_sent()) {
            setcookie('sltr_redirect_trace', wp_json_encode($trace), [
                'expires' => time() + 300,
                'path' => '/',
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        wp_safe_redirect($url, 301);
        exit;
    }

    private function current_redirect_url(): string
    {
        if (is_admin() || !is_singular()) {
            return '';
        }

        $post = get_post();
        if (!$post) {
            return '';
        }

        $package = $this->package_for_post((int) $post->ID, (string) $post->post_content);
        if ($package) {
            return $this->safe_redirect_url((string) ($package['seo_redirect_301'] ?? ''));
        }

        $category = $this->category_for_post((int) $post->ID, (string) $post->post_content);
        if ($category) {
            return $this->safe_redirect_url((string) ($category['seo_redirect_301'] ?? ''));
        }

        if ($post->post_type === 'page' && (int) get_post_meta((int) $post->ID, '_sltr_wp_page_seo_enabled', true) === 1) {
            $settings = new SettingsRepository();
            if (empty($settings->get('seo_wp_pages_enabled', 0)) || $this->external_seo_plugin_active()) {
                return '';
            }
            return $this->safe_redirect_url((string) get_post_meta((int) $post->ID, '_sltr_wp_page_seo_redirect_301', true));
        }

        return '';
    }

    private function safe_redirect_url(string $url): string
    {
        $url = esc_url_raw(trim($url));
        if ($url === '') {
            return '';
        }
        $validated = wp_validate_redirect($url, '');
        return is_string($validated) ? $validated : '';
    }

    public function filter_document_title(string $title): string
    {
        $mode = $this->seo_output_mode();
        if ($mode === 'disabled' || ($mode === 'auto' && $this->external_seo_plugin_active())) {
            return $title;
        }

        $meta = $this->current_meta();
        return $meta ? $meta['title'] : $title;
    }

    public function render_head(): void
    {
        $mode = $this->seo_output_mode();
        if ($mode === 'disabled' || ($mode === 'auto' && $this->external_seo_plugin_active())) {
            return;
        }

        $meta = $this->current_meta();
        if (!$meta) {
            return;
        }

        echo "\n<!-- Slotera SEO -->\n";
        echo '<meta name="description" content="' . esc_attr($meta['description']) . '">' . "\n";
        echo '<link rel="canonical" href="' . esc_url($meta['canonical']) . '">' . "\n";
        echo '<meta name="robots" content="' . esc_attr($meta['robots']) . '">' . "\n";
        echo '<meta property="og:type" content="' . esc_attr($meta['og_type']) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($meta['og_title']) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($meta['og_description']) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($meta['canonical']) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($this->site_name()) . '">' . "\n";
        if ($meta['image'] !== '') {
            echo '<meta property="og:image" content="' . esc_url($meta['image']) . '">' . "\n";
        }
        if (!empty($meta['schema'])) {
            echo '<script type="application/ld+json">' . wp_json_encode($meta['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
        echo "<!-- /Slotera SEO -->\n";
    }

    private function current_meta(): ?array
    {
        $local_context = LocalRouteService::current_context();
        if ($local_context) {
            if ((string) ($local_context['type'] ?? 'package') === 'city') {
                return $this->city_landing_meta((array) ($local_context['location'] ?? []), (string) ($local_context['url'] ?? ''));
            }
            return $this->local_package_meta((array) ($local_context['package'] ?? []), (array) ($local_context['location'] ?? []), (string) ($local_context['url'] ?? ''), (array) ($local_context['relation'] ?? []));
        }

        if (is_admin() || !is_singular()) {
            return null;
        }

        $post = get_post();
        if (!$post) {
            return null;
        }

        $package = $this->package_for_post((int) $post->ID, (string) $post->post_content);
        if ($package) {
            return $this->package_meta($package, (int) $post->ID);
        }

        $category = $this->category_for_post((int) $post->ID, (string) $post->post_content);
        if ($category) {
            return $this->category_meta($category, (int) $post->ID);
        }

        if ($post->post_type === 'page') {
            return $this->wp_page_meta((int) $post->ID, $post);
        }

        return null;
    }

    private function package_for_post(int $post_id, string $content): ?array
    {
        global $wpdb;
        $table = Database::packages_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE page_id = %d AND is_active = 1 LIMIT 1", $post_id), ARRAY_A);
        if ($row) {
            return $row;
        }

        if (preg_match('/\[slotera_booking[^\]]*package_id=["\']?(\d+)/i', $content, $m)) {
            $package = (new PackageRepository())->get_by_id((int) $m[1]);
            return ($package && !empty($package['is_active'])) ? $package : null;
        }

        return null;
    }

    private function category_for_post(int $post_id, string $content): ?array
    {
        global $wpdb;
        $table = Database::categories_table();
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE page_id = %d AND is_active = 1 LIMIT 1", $post_id), ARRAY_A);
        if ($row) {
            return $row;
        }

        if (preg_match('/\[slotera_category[^\]]*category_id=["\']?(\d+)/i', $content, $m)) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND is_active = 1 LIMIT 1", (int) $m[1]), ARRAY_A);
            return $row ?: null;
        }

        return null;
    }

    private function package_meta(array $package, int $post_id): array
    {
        $site = $this->site_name();
        $title = $this->clean((string) ($package['title'] ?? ''));
        $title_position = $this->title_position((string) ($package['seo_site_title_position'] ?? 'right'));
        $fallback_title = $this->format_title($title, $site, $title_position);
        $description = $this->excerpt((string) ($package['description'] ?? ''), 155);
        if ($description === '') {
            $description = $this->excerpt((string) ($package['solo_content'] ?? ''), 155);
        }
        if ($description === '') {
            $description = $title !== '' ? sprintf(__('Book %s online.', 'slotera-booking'), $title) : $site;
        }

        $i18n = $this->localized_seo($package);
        $canonical = $this->canonical((string) ($i18n['seo_canonical'] ?? ($package['seo_canonical'] ?? '')), $post_id);
        $image = $this->image((string) ($i18n['seo_og_image'] ?? ($package['seo_og_image'] ?? '')), (string) ($package['slider_image_ids'] ?? ''), (string) ($package['gallery_image_ids'] ?? ''));
        $seo_title = $this->clean((string) ($i18n['seo_title'] ?? ($package['seo_title'] ?? '')));
        $seo_description = $this->excerpt((string) ($i18n['seo_description'] ?? ($package['seo_description'] ?? '')), 180);
        $og_title = $this->clean((string) ($i18n['seo_og_title'] ?? ($package['seo_og_title'] ?? '')));
        $og_description = $this->excerpt((string) ($i18n['seo_og_description'] ?? ($package['seo_og_description'] ?? '')), 220);
        $template_context = $this->template_context(['package_name' => $title]);
        $template_title = $this->template_value('seo_template_package_title', $template_context, 255);
        $template_description = $this->template_value('seo_template_package_description', $template_context, 180);

        $price = max(0, (float) ($package['price'] ?? 0));
        $currency = CurrencyService::normalize((string) ((new \Slotera\Infrastructure\Repositories\SettingsRepository())->all()['payment_currency'] ?? 'EUR'));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $title,
            'description' => $description,
            'url' => $canonical,
            'provider' => [
                '@type' => 'Organization',
                'name' => $site,
                'url' => home_url('/'),
            ],
        ];
        if ($image !== '') {
            $schema['image'] = $image;
        }
        if ($price > 0) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => number_format($price, 2, '.', ''),
                'priceCurrency' => $currency,
                'url' => $canonical,
                'availability' => 'https://schema.org/InStock',
            ];
        }

        if ($post_id > 0) {
            $breadcrumb_schema = $this->simple_breadcrumb_schema($title !== '' ? $title : __('Package', 'slotera-booking'), $canonical);
            if (!empty($breadcrumb_schema)) {
                $schema = [
                    '@context' => 'https://schema.org',
                    '@graph' => [
                        $schema,
                        $breadcrumb_schema,
                    ],
                ];
            }
        }


        return [
            'title' => $seo_title !== '' ? $this->format_title($seo_title, $site, $title_position) : ($template_title !== '' ? $this->format_title($template_title, $site, $title_position) : $fallback_title),
            'description' => $seo_description !== '' ? $seo_description : ($template_description !== '' ? $template_description : $description),
            'canonical' => $canonical,
            'robots' => $this->robots((string) ($package['seo_robots'] ?? 'index,follow')),
            'og_type' => 'website',
            'og_title' => $og_title !== '' ? $og_title : ($seo_title !== '' ? $this->format_title($seo_title, $site, $title_position) : ($template_title !== '' ? $this->format_title($template_title, $site, $title_position) : $fallback_title)),
            'og_description' => $og_description !== '' ? $og_description : ($seo_description !== '' ? $seo_description : ($template_description !== '' ? $template_description : $description)),
            'image' => $image,
            'schema' => $schema,
        ];
    }




    private function city_landing_meta(array $location, string $url): array
    {
        $location_repo = new LocationRepository();
        $location_name = $this->clean((string) ($location['name'] ?? ''));
        if ($location_name === '') {
            $location_name = __('Location', 'slotera-booking');
        }
        $site = $this->site_name();
        $template_context = $this->template_context(['location_name' => $location_name]);
        $template_title = $this->template_value('seo_template_location_title', $template_context, 255);
        $title = $this->format_title($template_title !== '' ? $template_title : sprintf(__('Services in %s', 'slotera-booking'), $location_name), $site, 'right');
        $intro = $location_repo->resolve_local_intro($location, null, []);
        $template_description = $this->template_value('seo_template_location_description', $template_context, 180);
        $description = $template_description !== '' ? $template_description : $this->excerpt($intro, 170);
        if ($description === '') {
            $description = sprintf(__('Browse available packages and book services in %s online.', 'slotera-booking'), $location_name);
        }
        if ($url === '') {
            $url = LocalRouteService::city_url($location);
        }

        $packages = $location_repo->get_packages_for_location((int) ($location['id'] ?? 0));
        $items = [];
        foreach ($packages as $package) {
            $package_title = $this->clean((string) ($package['title'] ?? ''));
            $package_url = LocalRouteService::url($location, $package);
            if ($package_title === '' || $package_url === '') {
                continue;
            }
            $items[] = [
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'name' => $package_title,
                'url' => $package_url,
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'CollectionPage',
                    'name' => sprintf(__('Services in %s', 'slotera-booking'), $location_name),
                    'description' => $description,
                    'url' => $url,
                    'about' => [
                        '@type' => 'City',
                        'name' => $location_name,
                    ],
                ],
                $this->city_breadcrumb_schema($location, $url),
            ],
        ];

        if (!empty($items)) {
            $schema['@graph'][] = [
                '@type' => 'ItemList',
                'name' => sprintf(__('Available packages in %s', 'slotera-booking'), $location_name),
                'itemListElement' => $items,
            ];
        }

        $faq = $location_repo->resolve_local_faq($location, null);
        $faq_entities = [];
        foreach ($faq as $item) {
            $question = $this->clean((string) ($item['question'] ?? ''));
            $answer = $this->clean((string) ($item['answer'] ?? ''));
            if ($question === '' || $answer === '') { continue; }
            $faq_entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }
        if (!empty($faq_entities)) {
            $schema['@graph'][] = [
                '@type' => 'FAQPage',
                'mainEntity' => $faq_entities,
            ];
        }

        $business = $this->local_business_schema($location, $url);
        if (!empty($business)) {
            $schema['@graph'][] = $business;
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $url,
            'robots' => 'index,follow',
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $description,
            'image' => '',
            'schema' => $schema,
        ];
    }

    private function city_breadcrumb_schema(array $location, string $url): array
    {
        $location_name = $this->clean((string) ($location['name'] ?? ''));
        if ($location_name === '') {
            $location_name = __('Location', 'slotera-booking');
        }
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => $this->site_name(),
                    'item' => home_url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $location_name,
                    'item' => $url,
                ],
            ],
        ];
    }

    private function local_package_meta(array $package, array $location, string $url, array $relation = []): array
    {
        $meta = $this->package_meta($package, 0);
        $location_repo = new LocationRepository();
        $location_name = $this->clean((string) ($location['name'] ?? ''));
        $package_title = $this->clean((string) ($package['title'] ?? ''));
        $local_intro = $location_repo->resolve_local_intro($location, $relation, $package);
        $local_faq = $location_repo->resolve_local_faq($location, $relation);

        if ($location_name !== '' && $package_title !== '') {
            $site = $this->site_name();
            $title_position = $this->title_position((string) ($package['seo_site_title_position'] ?? 'right'));
            $local_context = $this->template_context(['package_name' => $package_title, 'location_name' => $location_name]);
            $local_title = $this->template_value('seo_template_local_package_title', $local_context, 255);
            if ($local_title === '') {
                $local_title = sprintf(__('%1$s in %2$s', 'slotera-booking'), $package_title, $location_name);
            }
            $meta['title'] = $this->format_title($local_title, $site, $title_position);
            $meta['og_title'] = $meta['title'];
        }

        $i18n = $this->localized_seo($package);
        $description = $this->excerpt((string) ($i18n['seo_description'] ?? ($package['seo_description'] ?? '')), 180);
        if ($description === '') {
            $description = $this->excerpt($local_intro, 170);
        }
        if ($description === '') {
            $description = $this->excerpt((string) ($package['description'] ?? ''), 155);
        }
        if ($location_name !== '' && $package_title !== '') {
            $local_context = $this->template_context(['package_name' => $package_title, 'location_name' => $location_name]);
            $template_description = $this->template_value('seo_template_local_package_description', $local_context, 180);
            $fallback = sprintf(__('Book %1$s in %2$s online.', 'slotera-booking'), $package_title, $location_name);
            $meta['description'] = $description !== '' ? $description : ($template_description !== '' ? $template_description : $fallback);
            $meta['og_description'] = $meta['description'];
        }

        $meta['canonical'] = $url;
        if (isset($meta['schema']) && is_array($meta['schema'])) {
            $meta['schema']['url'] = $url;
            $meta['schema']['areaServed'] = [
                '@type' => 'City',
                'name' => $location_name,
            ];
            if (isset($meta['schema']['offers']) && is_array($meta['schema']['offers'])) {
                $meta['schema']['offers']['url'] = $url;
            }
            if (!empty($local_faq)) {
                $faq_entities = [];
                foreach ($local_faq as $item) {
                    $question = $this->clean((string) ($item['question'] ?? ''));
                    $answer = $this->clean((string) ($item['answer'] ?? ''));
                    if ($question === '' || $answer === '') { continue; }
                    $faq_entities[] = [
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $answer,
                        ],
                    ];
                }
                if (!empty($faq_entities)) {
                    $meta['schema'] = [
                        '@context' => 'https://schema.org',
                        '@graph' => [
                            $meta['schema'],
                            [
                                '@type' => 'FAQPage',
                                'mainEntity' => $faq_entities,
                            ],
                        ],
                    ];
                }
            }

            $breadcrumb_schema = $this->local_breadcrumb_schema($package, $location, $url);
            if (!empty($breadcrumb_schema)) {
                $meta['schema'] = $this->append_schema_node($meta['schema'], $breadcrumb_schema);
            }

            $local_business_schema = $this->local_business_schema($location, $url);
            if (!empty($local_business_schema)) {
                $meta['schema'] = $this->append_schema_node($meta['schema'], $local_business_schema);
            }
        }

        return $meta;
    }


    private function simple_breadcrumb_schema(string $label, string $url): array
    {
        $label = $this->clean($label);
        if ($label === '') {
            return [];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => $this->site_name(),
                    'item' => home_url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $label,
                    'item' => $url,
                ],
            ],
        ];
    }

    private function local_breadcrumb_schema(array $package, array $location, string $url): array
    {
        $location_name = $this->clean((string) ($location['name'] ?? ''));
        $package_title = $this->clean((string) ($package['title'] ?? ''));
        $location_slug = sanitize_title((string) ($location['slug'] ?? ''));

        if ($location_name === '') {
            $location_name = __('Location', 'slotera-booking');
        }
        if ($package_title === '') {
            $package_title = __('Package', 'slotera-booking');
        }

        $items = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => $this->site_name(),
                'item' => home_url('/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $location_name,
                'item' => $location_slug !== '' ? home_url(user_trailingslashit($location_slug)) : home_url('/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $package_title,
                'item' => $url,
            ],
        ];

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private function append_schema_node(array $schema, array $node): array
    {
        if (empty($node)) {
            return $schema;
        }

        if (isset($schema['@graph']) && is_array($schema['@graph'])) {
            $schema['@graph'][] = $node;
            return $schema;
        }

        $base = $schema;
        if (isset($base['@context'])) {
            unset($base['@context']);
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                $base,
                $node,
            ],
        ];
    }

    private function local_business_schema(array $location, string $url): array
    {
        $settings = (new SettingsRepository())->all();
        if (empty($settings['seo_local_business_schema_enabled'])) {
            return [];
        }

        $name = $this->clean((string) ($settings['seo_local_business_name'] ?? ''));
        if ($name === '') {
            $name = $this->site_name();
        }
        if ($name === '') {
            return [];
        }

        $type = $this->clean((string) ($settings['seo_local_business_type'] ?? 'LocalBusiness'));
        $allowed_types = ['LocalBusiness', 'EntertainmentBusiness', 'ProfessionalService'];
        if (!in_array($type, $allowed_types, true)) {
            $type = 'LocalBusiness';
        }

        $location_name = $this->clean((string) ($location['name'] ?? ''));
        $schema = [
            '@type' => $type,
            '@id' => trailingslashit(home_url('/')) . '#slotera-local-business',
            'name' => $name,
            'url' => home_url('/'),
        ];

        $phone = $this->clean((string) ($settings['seo_local_business_phone'] ?? ''));
        if ($phone !== '') {
            $schema['telephone'] = $phone;
        }

        $email = sanitize_email((string) ($settings['seo_local_business_email'] ?? ''));
        if ($email !== '') {
            $schema['email'] = $email;
        }

        $logo = esc_url_raw(trim((string) ($settings['seo_local_business_logo'] ?? '')));
        if ($logo !== '') {
            $schema['image'] = $logo;
            $schema['logo'] = $logo;
        }

        $street = $this->clean((string) ($settings['seo_local_business_street'] ?? ''));
        $city = $this->clean((string) ($settings['seo_local_business_city'] ?? ''));
        $region = $this->clean((string) ($settings['seo_local_business_region'] ?? ''));
        $postal = $this->clean((string) ($settings['seo_local_business_postal_code'] ?? ''));
        $country = $this->clean((string) ($settings['seo_local_business_country'] ?? ''));
        if ($street !== '' || $city !== '' || $region !== '' || $postal !== '' || $country !== '') {
            $address = ['@type' => 'PostalAddress'];
            if ($street !== '') { $address['streetAddress'] = $street; }
            if ($city !== '') { $address['addressLocality'] = $city; }
            if ($region !== '') { $address['addressRegion'] = $region; }
            if ($postal !== '') { $address['postalCode'] = $postal; }
            if ($country !== '') { $address['addressCountry'] = $country; }
            $schema['address'] = $address;
        }

        if ($location_name !== '') {
            $schema['areaServed'] = [
                '@type' => 'City',
                'name' => $location_name,
            ];
        }

        $price_range = $this->clean((string) ($settings['seo_local_business_price_range'] ?? ''));
        if ($price_range !== '') {
            $schema['priceRange'] = $price_range;
        }

        $schema['sameAs'] = array_values(array_filter(array_map('esc_url_raw', preg_split('/\R+/', (string) ($settings['seo_local_business_same_as'] ?? '')) ?: [])));
        if (empty($schema['sameAs'])) {
            unset($schema['sameAs']);
        }

        return $schema;
    }

    private function category_meta(array $category, int $post_id): array
    {
        $site = $this->site_name();
        $name = $this->clean((string) ($category['name'] ?? ''));
        $title_position = $this->title_position((string) ($category['seo_site_title_position'] ?? 'right'));
        $fallback_title = $this->format_title($name, $site, $title_position);
        $description = $this->excerpt((string) ($category['description'] ?? ''), 155);
        if ($description === '') {
            $description = $name !== '' ? sprintf(__('Browse %s packages and book online.', 'slotera-booking'), $name) : $site;
        }
        $i18n = $this->localized_seo($category);
        $canonical = $this->canonical((string) ($i18n['seo_canonical'] ?? ($category['seo_canonical'] ?? '')), $post_id);
        $seo_title = $this->clean((string) ($i18n['seo_title'] ?? ($category['seo_title'] ?? '')));
        $seo_description = $this->excerpt((string) ($i18n['seo_description'] ?? ($category['seo_description'] ?? '')), 180);
        $og_title = $this->clean((string) ($i18n['seo_og_title'] ?? ($category['seo_og_title'] ?? '')));
        $og_description = $this->excerpt((string) ($i18n['seo_og_description'] ?? ($category['seo_og_description'] ?? '')), 220);
        $template_context = $this->template_context(['category_name' => $name]);
        $template_title = $this->template_value('seo_template_category_title', $template_context, 255);
        $template_description = $this->template_value('seo_template_category_description', $template_context, 180);
        $image = esc_url_raw((string) ($i18n['seo_og_image'] ?? ($category['seo_og_image'] ?? '')));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $name,
            'description' => $description,
            'url' => $canonical,
        ];

        return [
            'title' => $seo_title !== '' ? $this->format_title($seo_title, $site, $title_position) : ($template_title !== '' ? $this->format_title($template_title, $site, $title_position) : $fallback_title),
            'description' => $seo_description !== '' ? $seo_description : ($template_description !== '' ? $template_description : $description),
            'canonical' => $canonical,
            'robots' => $this->robots((string) ($category['seo_robots'] ?? 'index,follow')),
            'og_type' => 'website',
            'og_title' => $og_title !== '' ? $og_title : ($seo_title !== '' ? $this->format_title($seo_title, $site, $title_position) : ($template_title !== '' ? $this->format_title($template_title, $site, $title_position) : $fallback_title)),
            'og_description' => $og_description !== '' ? $og_description : ($seo_description !== '' ? $seo_description : ($template_description !== '' ? $template_description : $description)),
            'image' => $image,
            'schema' => $schema,
        ];
    }



    private function wp_page_meta(int $post_id, \WP_Post $post): ?array
    {
        $settings = new SettingsRepository();
        if (empty($settings->get('seo_wp_pages_enabled', 0))) {
            return null;
        }
        if ($this->external_seo_plugin_active()) {
            return null;
        }
        if ((int) get_post_meta($post_id, '_sltr_wp_page_seo_enabled', true) !== 1) {
            return null;
        }

        $site = $this->site_name();
        $title = $this->clean((string) get_post_meta($post_id, '_sltr_wp_page_seo_title', true));
        if ($title === '') {
            $title = $this->clean((string) get_the_title($post_id));
        }
        $description = $this->excerpt((string) get_post_meta($post_id, '_sltr_wp_page_seo_description', true), 180);
        if ($description === '') {
            $description = $this->excerpt(wp_strip_all_tags((string) $post->post_content), 180);
        }
        $canonical = $this->canonical((string) get_post_meta($post_id, '_sltr_wp_page_seo_canonical', true), $post_id);
        $og_title = $this->clean((string) get_post_meta($post_id, '_sltr_wp_page_seo_og_title', true));
        $og_description = $this->excerpt((string) get_post_meta($post_id, '_sltr_wp_page_seo_og_description', true), 220);
        $image = esc_url_raw((string) get_post_meta($post_id, '_sltr_wp_page_seo_og_image', true));
        $robots = (int) get_post_meta($post_id, '_sltr_wp_page_seo_noindex', true) === 1 ? 'noindex,follow' : $this->robots((string) $settings->get('seo_default_robots', 'index,follow'));

        return [
            'title' => $this->format_title($title, $site, 'right'),
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og_type' => 'website',
            'og_title' => $og_title !== '' ? $og_title : $this->format_title($title, $site, 'right'),
            'og_description' => $og_description !== '' ? $og_description : $description,
            'image' => $image,
            'schema' => [],
        ];
    }

    private function localized_seo(array $entity): array
    {
        $json = (string) ($entity['seo_i18n_json'] ?? '');
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        $locale = $this->current_seo_locale();
        $candidates = [$locale];
        $short = strtolower(substr($locale, 0, 2));
        if ($short !== '') {
            $candidates[] = $short;
        }

        foreach ($decoded as $stored_locale => $fields) {
            if (!is_array($fields)) {
                continue;
            }
            $normalized = str_replace('-', '_', (string) $stored_locale);
            $stored_short = strtolower(substr($normalized, 0, 2));
            if (in_array($normalized, $candidates, true) || ($stored_short !== '' && $stored_short === $short)) {
                return array_filter($fields, static fn($value) => is_string($value) && trim($value) !== '');
            }
        }

        return [];
    }

    private function current_seo_locale(): string
    {
        $locale = (new TranslationService())->locale_for_group('frontend');
        if ($locale === '' && function_exists('determine_locale')) {
            $locale = (string) determine_locale();
        }
        if ($locale === '') {
            $locale = get_locale();
        }
        return str_replace('-', '_', $locale !== '' ? $locale : 'en_US');
    }


    private function template_value(string $setting_key, array $context, int $max_length): string
    {
        $value = (new SEOTemplateService())->render((string) (new SettingsRepository())->get($setting_key, ''), $context);
        if ($value === '') {
            return '';
        }
        return $max_length > 0 ? $this->excerpt($value, $max_length) : $this->clean($value);
    }

    /** @param array<string, scalar|null> $context */
    private function template_context(array $context = []): array
    {
        return array_merge([
            'package_name' => '',
            'category_name' => '',
            'location_name' => '',
            'site_name' => $this->site_name(),
        ], $context);
    }

    private function title_position(string $position): string
    {
        return in_array($position, ['left', 'right'], true) ? $position : 'right';
    }

    private function format_title(string $title, string $site, string $position): string
    {
        $title = $this->clean($title);
        $site = $this->clean($site);
        if ($title === '') {
            return $site;
        }
        if ($site === '') {
            return $title;
        }
        if ($this->title_contains_site($title, $site)) {
            return $title;
        }
        $format = (string) (new SettingsRepository())->get('seo_title_format', '');
        if (strpos($format, '{title}') !== false || strpos($format, '{site}') !== false) {
            return str_replace(['{title}', '{site}'], [$title, $site], $format);
        }
        return $position === 'left' ? $site . ' | ' . $title : $title . ' | ' . $site;
    }

    private function title_contains_site(string $title, string $site): bool
    {
        if ($site === '') {
            return false;
        }
        return function_exists('mb_stripos') ? mb_stripos($title, $site) !== false : stripos($title, $site) !== false;
    }

    private function seo_output_mode(): string
    {
        $mode = (string) (new SettingsRepository())->get('seo_meta_output_mode', 'auto');
        return in_array($mode, ['auto', 'force', 'disabled'], true) ? $mode : 'auto';
    }

    private function slotera_redirects_enabled(): bool
    {
        $mode = $this->seo_output_mode();
        if ($mode === 'disabled') {
            return false;
        }
        if ($mode === 'auto' && $this->external_seo_plugin_active()) {
            return false;
        }
        return true;
    }

    public function detected_external_plugins(): array
    {
        $plugins = [];
        if (defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend')) {
            $plugins[] = 'Yoast SEO';
        }
        if (defined('RANK_MATH_VERSION') || class_exists('RankMath')) {
            $plugins[] = 'Rank Math';
        }
        if (defined('SEOPRESS_VERSION') || class_exists('SEOPress')) {
            $plugins[] = 'SEOPress';
        }
        if (defined('AIOSEO_VERSION') || class_exists('AIOSEO\\Plugin\\AIOSEO')) {
            $plugins[] = 'All in One SEO';
        }
        return $plugins;
    }

    private function external_seo_plugin_active(): bool
    {
        $active = count($this->detected_external_plugins()) > 0;

        /**
         * Allow site owners/developers to force Slotera SEO meta output even when a dedicated SEO plugin is active.
         * Returning false disables the compatibility guard.
         */
        return (bool) apply_filters('sltr_external_seo_plugin_active', $active);
    }

    private function site_name(): string
    {
        return wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
    }

    private function canonical(string $custom, int $post_id): string
    {
        $custom = esc_url_raw(trim($custom));
        if ($custom !== '') {
            return $custom;
        }
        if ($post_id > 0) {
            $permalink = get_permalink($post_id);
            return is_string($permalink) ? $permalink : home_url('/');
        }
        $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash((string) $_SERVER['REQUEST_URI'])) : '/';
        return home_url($request_uri);
    }

    private function robots(string $robots): string
    {
        return in_array($robots, ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'], true) ? $robots : (string) (new SettingsRepository())->get('seo_default_robots', 'index,follow');
    }

    private function clean(string $value): string
    {
        return trim(wp_strip_all_tags(strip_shortcodes($value)));
    }

    private function excerpt(string $value, int $length): string
    {
        $value = preg_replace('/\s+/', ' ', $this->clean($value));
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return '';
        }
        return function_exists('mb_substr') && function_exists('mb_strlen') && mb_strlen($value) > $length
            ? rtrim(mb_substr($value, 0, $length - 1)) . '…'
            : (strlen($value) > $length ? rtrim(substr($value, 0, $length - 1)) . '…' : $value);
    }

    private function image(string $custom, string $slider_ids, string $gallery_ids): string
    {
        $custom = esc_url_raw(trim($custom));
        if ($custom !== '') {
            return $custom;
        }

        foreach ([$slider_ids, $gallery_ids] as $ids) {
            foreach (array_filter(array_map('absint', preg_split('/[\s,]+/', $ids) ?: [])) as $id) {
                $url = wp_get_attachment_image_url($id, 'full');
                if ($url) {
                    return esc_url_raw($url);
                }
            }
        }

        return '';
    }
}
