<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$mode = $argv[1] ?? 'qa';
function fail(string $m): never { fwrite(STDERR, "FAIL: $m\n"); exit(1); }
function ok(string $m): void { fwrite(STDOUT, "OK: $m\n"); }
function phpFiles(string $root): array {
    $out=[]; $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach($it as $f){ if($f->isFile() && $f->getExtension()==='php') $out[]=$f->getPathname(); }
    sort($out); return $out;
}
function lint(string $root): void {
    foreach (phpFiles($root) as $file) {
        $source = file_get_contents($file);
        if ($source === false) fail('Unable to read PHP file for lint: '.$file);
        try {
            // TOKEN_PARSE invokes PHP's parser in-process without spawning a child executable.
            // This keeps lint independent of Windows shell/codepage handling even when
            // php.exe itself is installed in a path containing spaces or Unicode.
            token_get_all($source, TOKEN_PARSE);
        } catch (ParseError $error) {
            fail('PHP syntax error in '.$file.': '.$error->getMessage());
        }
    }
    ok('PHP syntax lint passed');
}
function tests(string $root): void {
    $runtime=$root.'/tests/runtime/php-behavior.php';
    require $runtime;
    ok('runtime behavior tests passed');
    // Validate the real production freeze implementation, not just manifest text.
    require_once $root.'/includes/Application/Services/Translations/FrontendTranslationStrings.php';
    require_once $root.'/includes/Application/Services/Translations/EmailTranslationStrings.php';
    require_once $root.'/includes/Application/Services/EmailTemplateTranslationData.php';
    require_once $root.'/includes/Application/Services/EmailTemplateRegistry.php';
    require_once $root.'/includes/Application/Services/TranslationFreezeService.php';
    $freezeService = new \Slotera\Application\Services\TranslationFreezeService();
    foreach ($freezeService->frozenLocales() as $freezeLocale) {
        foreach (['frontend', 'emails', 'email_templates'] as $freezeSection) {
            $freezeResult = $freezeService->verify($freezeSection, (string) $freezeLocale);
            if (($freezeResult['valid'] ?? false) !== true) {
                $expectedFreeze = json_encode($freezeResult['expected'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $actualFreeze = json_encode($freezeResult['actual'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                fail('translation freeze drift: '.$freezeLocale.'/'.$freezeSection.' expected='.$expectedFreeze.' actual='.$actualFreeze);
            }
        }
    }
    ok('translation freeze baselines verified against production registries');
    $rl=file_get_contents($root.'/includes/Infrastructure/Http/RateLimiter.php');
    if(!str_contains($rl,"fail_closed('increment'") || !str_contains($rl,"fail_closed('read'") || !str_contains($rl,'return PHP_INT_MAX;')) fail('rate limiter fail-closed regression');
    $ip=file_get_contents($root.'/includes/Infrastructure/Http/ClientIpResolver.php');
    if(!str_contains($ip,'for ($index = count($chain) - 1; $index >= 0; --$index)')) fail('proxy chain regression');
    $obs=file_get_contents($root.'/includes/Application/Services/ObservabilityLogger.php');
    if(str_contains($obs,'HTTP_X_REQUEST_ID') || !str_contains($obs,'self::generate_request_id()')) fail('request id regression');
    $visitorAnalyticsController=file_get_contents($root.'/includes/Frontend/Controllers/VisitorAnalyticsController.php');
    foreach(["RateLimiter::increment('visitor_analytics_ip'", "RateLimiter::increment('visitor_analytics_session'", 'RATE_LIMIT_PER_IP', 'RATE_LIMIT_PER_SESSION', 'is_same_site_url', ', 429)'] as $analyticsGuard) {
        if(!str_contains($visitorAnalyticsController, $analyticsGuard)) fail('public visitor analytics protection regression: '.$analyticsGuard);
    }
    $settingsRepository=file_get_contents($root.'/includes/Infrastructure/Repositories/SettingsRepository.php');
    $secretMigrationGuard=strpos($settingsRepository, 'SecretStore::encryption_available()');
    $secretMigrationEncrypt=strpos($settingsRepository, '$encrypted = SecretStore::encrypt_settings($settings)', $secretMigrationGuard === false ? 0 : $secretMigrationGuard);
    $secretMigrationWrite=strpos($settingsRepository, 'update_option(self::OPTION_NAME, $encrypted, false)', $secretMigrationEncrypt === false ? 0 : $secretMigrationEncrypt);
    if($secretMigrationGuard === false || $secretMigrationEncrypt === false || $secretMigrationWrite === false || !($secretMigrationGuard < $secretMigrationEncrypt && $secretMigrationEncrypt < $secretMigrationWrite)) fail('plaintext secret migration encryption-availability regression');
    if(!str_contains($settingsRepository, '!SecretStore::is_encrypted($encrypted[$key])')) fail('plaintext secret migration completeness regression');
    $visitorAnalyticsClient=file_get_contents($root.'/assets/js/frontend-analytics.js');
    $visitorAnalyticsService=file_get_contents($root.'/includes/Application/Services/VisitorAnalyticsService.php');
    $privacyService=file_get_contents($root.'/includes/Application/Services/PrivacyService.php');
    $pluginSource=file_get_contents($root.'/includes/Core/Plugin.php');
    foreach(['if (sent) { return; }', 'final_event: 1'] as $singleEventGuard) if(!str_contains($visitorAnalyticsClient, $singleEventGuard)) fail('single visitor analytics event regression: '.$singleEventGuard);
    if(str_contains($visitorAnalyticsClient, 'send(false)') || str_contains($visitorAnalyticsClient, 'setTimeout(function () { send')) fail('intermediate visitor analytics event regression');
    foreach(['privacy_visitor_analytics_enabled', 'privacy_visitor_session_analytics_enabled', 'slotera_visitor_analytics_consent_granted', "'ip_prefix_hash' => ''", "'exited' => \$final_event"] as $privacyGuard) if(!str_contains($visitorAnalyticsService, $privacyGuard)) fail('visitor analytics policy regression: '.$privacyGuard);
    if(!str_contains($pluginSource, '$visitor_analytics->is_collection_allowed()') || !str_contains($pluginSource, 'visitor_analytics_session_enabled')) fail('visitor analytics enqueue policy regression');
    foreach(['Database::visitor_events_table()', 'privacy_visitor_analytics_retention_days', 'RETENTION_MAX_BATCHES'] as $retentionGuard) if(!str_contains($privacyService, $retentionGuard)) fail('visitor analytics retention regression: '.$retentionGuard);
    $socialLoginService=file_get_contents($root.'/includes/Application/Services/SocialLoginService.php');
    $accountController=file_get_contents($root.'/includes/Frontend/Controllers/AccountController.php');
    foreach(['is_verified_email_claim', "['email_verified'] ?? null", 'sltr_social_login_email_unverified'] as $emailVerificationGuard) if(!str_contains($socialLoginService, $emailVerificationGuard)) fail('social login verified email regression: '.$emailVerificationGuard);
    if(!str_contains($accountController, "(\$profile['email_verified'] ?? false) !== true")) fail('social login controller verified email regression');
    $bookingModeBase=file_get_contents($root.'/includes/Application/Services/BookingModes/AbstractBookingModeHandler.php');
    if(!str_contains($bookingModeBase, 'notify_after_successful_creation') || !str_contains($bookingModeBase, 'notifications->booking_created')) fail('booking post-create notification flow regression');
    foreach(['FixedBookingModeHandler.php','SimpleBookingModeHandler.php','DateRangeInventoryBookingModeHandler.php'] as $handlerFile) {
        $handler=file_get_contents($root.'/includes/Application/Services/BookingModes/'.$handlerFile);
        $paymentPosition=strrpos($handler, 'payment_service->create_for_booking');
        $notificationPosition=strrpos($handler, '$this->notify_after_successful_creation');
        if($paymentPosition === false || $notificationPosition === false || $notificationPosition <= $paymentPosition || str_contains($handler, 'notifications->booking_created') || !str_contains($handler, "'defer_notifications' => true")) fail('payment-first booking notification regression: '.$handlerFile);
    }
    $paymentService=file_get_contents($root.'/includes/Application/Services/PaymentService.php');
    if(!str_contains($paymentService, "empty(\$context['defer_notifications'])")) fail('deferred payment notification regression');
    $urlGuard=file_get_contents($root.'/includes/Application/Security/UrlGuard.php');
    foreach(['curl_pinning_available', "function_exists('curl_init')", "defined('CURLOPT_RESOLVE')", 'sltr_secure_http_transport_unavailable', 'curl_setopt($handle, CURLOPT_RESOLVE', 'throw new \\RuntimeException'] as $transportGuard) if(!str_contains($urlGuard, $transportGuard)) fail('secure outbound transport regression: '.$transportGuard);
    if(str_contains($urlGuard, '@curl_setopt')) fail('DNS pinning failure suppression regression');
    $marketingEmailService=file_get_contents($root.'/includes/Application/Services/MarketingEmailService.php');
    $marketingAutomationService=file_get_contents($root.'/includes/Application/Services/MarketingAutomationService.php');
    foreach([$marketingEmailService,$marketingAutomationService] as $source) {
        if(str_contains($source, 'marketing_privacy_compliance_confirmed') || str_contains($source, 'privacy_compliance_required') || str_contains($source, 'Privacy / compliance gate')) fail('obsolete marketing compliance gate regression');
    }
    if(!str_contains($marketingEmailService, 'email_delivery_readiness') || !str_contains($marketingEmailService, 'email_notifications_enabled') || !str_contains($marketingEmailService, 'smtp_host')) fail('marketing Email Settings readiness regression');
    foreach(['queue-settings.php','preview-test.php','queue-controls-log.php'] as $deadMarketingView) if(is_file($root.'/includes/Admin/Views/marketing-form/'.$deadMarketingView)) fail('dead coupon campaign view returned: '.$deadMarketingView);
    $externalMailDetector=file_get_contents($root.'/includes/Application/Services/ExternalMailPluginDetector.php');
    $emailController=file_get_contents($root.'/includes/Admin/Controllers/EmailController.php');
    $smtpMailer=file_get_contents($root.'/includes/Application/Services/SmtpMailerService.php');
    $emailSettingsView=file_get_contents($root.'/includes/Admin/Views/settings/email.php');
    foreach(['wp-mail-smtp','fluent-smtp','post-smtp','easy-wp-smtp','mailgun','brevo','wp-offload-ses'] as $mailPluginSlug) if(!str_contains($externalMailDetector, "'".$mailPluginSlug."'")) fail('external mail plugin recognition regression: '.$mailPluginSlug);
    if(!str_contains($emailController, 'smtp_blocked_by_external_plugin') || !str_contains($emailController, "'smtp_enabled' => \$smtp_requested")) fail('external SMTP save blocking regression');
    if(!str_contains($smtpMailer, 'has_external_delivery_plugin()')) fail('runtime external SMTP conflict protection regression');
    if(!str_contains($emailSettingsView, 'External email delivery plugin detected. Slotera SMTP was not enabled')) fail('SMTP conflict UX regression');
    $marketingView=file_get_contents($root.'/includes/Admin/Views/marketing-list.php');
    if(!str_contains($marketingView, 'Marketing Emails remain available') || !str_contains($marketingView, 'wp_mail()')) fail('external mail Marketing Emails notice regression');
    foreach(['come-back','after-booking','campaigns'] as $tab) if(!str_contains($marketingView, "sltr_marketing_tab === '$tab'")) fail('marketing tabs regression: '.$tab);
    if(str_contains($marketingView, "page=slotera-marketing&action=new")) fail('manual campaign create action returned to marketing overview');
    $couponView=file_get_contents($root.'/includes/Admin/Views/coupons-list.php');
    $couponCampaignView=file_get_contents($root.'/includes/Admin/Views/coupon-campaigns-list.php');
    if(!str_contains($couponView, 'sltr_coupon_tab=campaigns') || str_contains($couponCampaignView, 'Create Coupon Campaign')) fail('coupon campaigns navigation regression');
    $couponController = file_get_contents(__DIR__ . '/../includes/Admin/Controllers/CouponController.php');
    $marketingController = file_get_contents(__DIR__ . '/../includes/Admin/Controllers/MarketingController.php');
    $couponsPage = file_get_contents(__DIR__ . '/../includes/Admin/Pages/CouponsPage.php');
    if(!str_contains($couponController, "action=new&coupon_id=' . \$id")) fail('coupon save redirect does not open prefilled campaign');
    if(!str_contains($marketingController, 'page=slotera-marketing&campaign_saved=1')) fail('campaign save redirect does not return to coupons');
    if(!str_contains($couponsPage, '$sltr_bound_coupon = $prefill_coupon_id > 0 ? $this->repo->get_by_id($prefill_coupon_id) : null') || !str_contains($couponsPage, "'source' => 'coupon_bound'") || !str_contains($couponsPage, 'campaign_requires_coupon=1') || !str_contains($couponsPage, '$sltr_bound_coupon_package_ids')) fail('strict coupon-bound campaign flow regression');
    $historyView=file_get_contents($root.'/includes/Admin/Views/campaign-history-table.php');
    foreach(['sltr_process_marketing_queue_now','sltr_pause_marketing_campaign','sltr_resume_marketing_campaign','sltr_stop_marketing_campaign','sltr_retry_failed_marketing_campaign'] as $action) if(!str_contains($historyView, $action)) fail('unified campaign history action missing: '.$action);
    $marketingController=file_get_contents($root.'/includes/Admin/Controllers/MarketingController.php');
    if(!str_contains($marketingController, 'process_campaign_queue($id)')) fail('campaign-scoped immediate queue processing regression');
    if(!str_contains($marketingController, 'campaign_return_url') || !str_contains($marketingController, 'campaign_history_url')) fail('campaign source-aware redirect regression');
    $migrator=file_get_contents($root.'/includes/Core/Migrator.php');
    $schemaInstaller=file_get_contents($root.'/includes/Core/DatabaseSchemaInstaller.php');
    $migrationRegistry=file_get_contents($root.'/includes/Core/Migrations/MigrationRegistry.php');
    if(!str_contains($migrator, 'is_fresh_install') || !str_contains($migrator, 'FreshInstallSetup::run()')) fail('fast fresh-install migration path regression');
    $pdfService=file_get_contents($root.'/includes/Application/Services/PdfInvoiceService.php');
    if(str_contains($pdfService, 'NotoSansSC-VF.ttf') || str_contains($pdfService, 'uses_cjk_font')) fail('CJK PDF fallback returned to production');
    if(!str_contains($pdfService, 'NotoSans-Slotera.ttf') || !str_contains($pdfService, '/FontFile2 ') || !str_contains($pdfService, '/ToUnicode ')) fail('self-contained Unicode PDF regression');
    if(is_file($root.'/assets/fonts/NotoSansSC-VF.ttf')) fail('Noto Sans SC must not ship in production');
    if(is_file($root.'/includes/Core/ActivationProfiler.php')) fail('diagnostic profiler shipped in production');
    $translationRegistry=file_get_contents($root.'/includes/Application/Services/TranslationRegistry.php');
    $translationService=file_get_contents($root.'/includes/Application/Services/TranslationService.php');
    $frontendStrings=file_get_contents($root.'/includes/Application/Services/Translations/FrontendTranslationStrings.php');
    $adminStrings=file_get_contents($root.'/includes/Application/Services/Translations/AdminTranslationStrings.php');
    $bookingJs=file_get_contents($root.'/assets/js/frontend-booking-form.js');
    $bookingShortcode=file_get_contents($root.'/includes/Frontend/Shortcodes/BookingShortcode.php');
    foreach(['hi_IN','ko_KR','ja_JP','zh_CN','zh_Hans'] as $asianLocale) {
        foreach([$translationRegistry,$translationService,$frontendStrings,$adminStrings,$bookingJs,$bookingShortcode] as $source) {
            if(str_contains($source, $asianLocale)) fail('Asian locale regression: '.$asianLocale);
        }
    }
    if(!str_contains($translationRegistry, "'pt_BR' => 'Português (Brasil)'") || !str_contains($frontendStrings, "'pt_BR' =>")) fail('pt_BR must remain supported');
    $packageRepo=file_get_contents($root.'/includes/Infrastructure/Repositories/PackageRepository.php');
    $packageController=file_get_contents($root.'/includes/Admin/Controllers/PackageController.php');
    $categoryController=file_get_contents($root.'/includes/Admin/Controllers/CategoryController.php');
    $locationController=file_get_contents($root.'/includes/Admin/Controllers/LocationController.php');
    $packageMigration=file_get_contents($root.'/includes/Core/Migrations/Version_1_0_964.php');
    foreach(['title_font_family VARCHAR(160)', 'title_font_size INT UNSIGNED'] as $packageColumn) if(!str_contains($schemaInstaller, $packageColumn)) fail('package canonical schema regression: '.$packageColumn);
    foreach(["'title_font_family' =>", "'title_font_size' =>"] as $packageField) if(!str_contains($packageRepo, $packageField)) fail('package repository field regression: '.$packageField);
    if(!str_contains($packageMigration, "maybe_add_column(\$packages, 'title_font_family'") || !str_contains($packageMigration, "maybe_add_column(\$packages, 'title_font_size'")) fail('package schema repair migration regression');
    foreach([$packageController,$categoryController,$locationController] as $saveController) if(!str_contains($saveController, 'sltr_error=save_failed')) fail('admin save failure handling regression');
    $freshInstallSetup=file_get_contents($root.'/includes/Core/Migrations/FreshInstallSetup.php');
    if(!str_contains($freshInstallSetup, 'migrate_to_10141')) fail('fresh install canonical pages setup regression');
    $campaignRepo=file_get_contents($root.'/includes/Infrastructure/Repositories/MarketingCampaignRepository.php');
    $campaignSchema=file_get_contents($root.'/includes/Core/DatabaseSchemaInstaller.php');
    $campaignMigration=file_get_contents($root.'/includes/Core/Migrations/Version_1_0_963.php');
    if(!str_contains($campaignSchema, 'automation_type VARCHAR(30)') || !str_contains($campaignSchema, "source VARCHAR(20) NOT NULL DEFAULT 'coupon'")) fail('explicit campaign source schema regression');
    if(!str_contains($campaignRepo, "'source' =>") || !str_contains($campaignRepo, "'automation_type' =>")) fail('campaign source repository regression');
    if(!str_contains($marketingAutomationService, "'source' => 'automation'") || !str_contains($marketingAutomationService, "'automation_type' => self::COME_BACK_KEY")) fail('automation source persistence regression');
    if(!str_contains($campaignMigration, "source='automation'")) fail('campaign source migration regression');
    $marketingLogs=file_get_contents($root.'/includes/Infrastructure/Repositories/MarketingLogRepository.php');
    if(!str_contains($marketingLogs, 'delete_for_campaign') || !str_contains($marketingLogs, 'campaign_source')) fail('unified campaign history repository regression');
    $frontendCss=file_get_contents($root.'/assets/css/frontend.css');
    if(str_contains($frontendCss, 'var(--sltr-muted-text')) fail('legacy muted Appearance token regression');
    foreach([
        'Booking details: canonical form, validation, payment picker, and layout.',
        '.sltr-step[data-step="4"] .sltr-fields textarea',
        'background: var(--sltr-card-bg, var(--sltr-form-bg, #ffffff));',
        '.sltr-field-invalid {'
    ] as $detailsGuard) if(!str_contains($frontendCss, $detailsGuard)) fail('booking details canonical CSS regression: '.$detailsGuard);
    if(str_contains($frontendCss, 'v1.0.596 booking details layout polish')) fail('booking details historical patch regression');
    $publicTokenStyles=file_get_contents($root.'/includes/Frontend/Views/public-token/_styles.php');
    foreach(['appearance_theme', '--sltr-form-bg', '--sltr-card-bg', '--sltr-card-border', '--sltr-primary', '--sltr-muted'] as $appearanceGuard) if(!str_contains($publicTokenStyles, $appearanceGuard)) fail('public token Appearance regression: '.$appearanceGuard);
    if(str_contains($publicTokenStyles, 'background:#f6f7f7') || str_contains($publicTokenStyles, 'background:#fff')) fail('public token hardcoded light background regression');
    ok('marketing tabs regression checks passed');
    ok('security regression checks passed');
}
function verifyRelease(string $root): void {
    foreach(['.phpunit.result.cache','.DS_Store'] as $bad){
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach($it as $f) if($f->getFilename()===$bad) fail("forbidden release artifact: $bad");
    }
    $main=file_get_contents($root.'/slotera-booking.php');
    $releaseManifest=json_decode((string) file_get_contents($root.'/release-manifest.json'), true);
    $releaseVersion=is_array($releaseManifest) ? (string) ($releaseManifest['version'] ?? '') : '';
    if($releaseVersion === '' || !preg_match('/Version:\s*'.preg_quote($releaseVersion, '/').'\b/',$main)) fail('plugin version does not match release manifest');
    foreach(['composer.json','package.json','pnpm-lock.yaml','QA.md','release-manifest.json','build-provenance.json','checksums.sha256','tools/release-metadata.mjs','tools/release-attestation.mjs','tools/build-release.ps1'] as $required) if(!is_file($root.'/'.$required)) fail("missing $required");
    $packageMetadata=json_decode((string) file_get_contents($root.'/package.json'), true);
    $provenanceMetadata=json_decode((string) file_get_contents($root.'/build-provenance.json'), true);
    if((string) ($packageMetadata['version'] ?? '') !== $releaseVersion || (string) ($provenanceMetadata['version'] ?? '') !== $releaseVersion) fail('release metadata version mismatch');
    foreach(['builder','vcs','source','build','hashes','transformation_chain','release_manifest_sha256'] as $field) if(!array_key_exists($field, $provenanceMetadata)) fail('provenance field missing: '.$field);
    if(!preg_match('/^[a-f0-9]{64}  build-provenance\.json$/m', (string) file_get_contents($root.'/checksums.sha256'))) fail('provenance is not included in checksum manifest');
    if((string) ($releaseManifest['schema'] ?? '') !== 'slotera-release-manifest/v2') fail('release manifest schema mismatch');
    if((string) ($provenanceMetadata['schema'] ?? '') !== 'slotera-build-provenance/v3') fail('provenance schema mismatch');
    if((string) ($releaseManifest['signing']['algorithm'] ?? '') !== 'RSA-PSS-SHA256' || !preg_match('/^sha256:[a-f0-9]{64}$/', (string) ($releaseManifest['signing']['key_id'] ?? ''))) fail('release signing policy missing');
    ok('release contents verified');
}
if($mode==='lint') lint($root);
elseif($mode==='test') tests($root);
elseif($mode==='verify-release') verifyRelease($root);
elseif($mode==='qa'){ lint($root); tests($root); verifyRelease($root); }
else fail('unknown mode');
