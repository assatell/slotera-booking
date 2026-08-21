<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

/**
 * Conservative UX/linguistic heuristics for every non-English locale.
 *
 * Rules intentionally prefer precision over recall:
 *  - detect English residue only when the same high-signal token occurs in the
 *    English source and in the localized value;
 *  - ignore product names, protocols, file formats and accepted UI loanwords;
 *  - never infer truncation from prefixes of other localized word forms.
 */
final class TranslationUxLinguisticService
{
    /** Language-neutral technical vocabulary that may remain unchanged. */
    private const TECHNICAL_ALLOWLIST = [
        'admin','api','booking','calendar','captcha','client','code','csv','date','email','facebook','form','google','html','http','https','id','instagram','json','login','magic','marketing','menu','name','oauth','online','payment','paypal','pdf','phone','plugin','popup','price','recaptcha','rest','slotera','smtp','ssl','status','stripe','system','test','tls','url','user','webhook','woocommerce','wordpress','xml','zoom',
    ];

    /** Accepted locale-specific loanwords which otherwise resemble English. */
    private const LOCALE_ALLOWLIST = [
        'et' => ['link'],
        'et_EE' => ['link'],
        'de' => ['fallback','link','links','popup'],
        'de_DE' => ['fallback','link','links','popup'],
        'fr' => ['email','marketing','popup','site','webhook'],
        'fr_FR' => ['email','marketing','popup','site','webhook'],
        'da' => ['link','login','online'],
        'da_DK' => ['link','login','online'],
        'nl' => ['later','open','site'],
        'nl_NL' => ['later','open','site'],
    ];

    /** Very strong English residue markers. One marker is sufficient. */
    private const SINGLE_MARKERS = [
        'and','back','could','currently','does','enter','every','fallback','later','only','opens','redirecting','successful','triggered','used','when','with','without',
    ];

    /** General English markers. Two distinct markers are required. */
    private const MULTI_MARKERS = [
        'access','another','applied','available','choose','confirmation','current','disabled','either','empty','expired','found','has','have','image','invalid','invoice','many','needs','open','please','preferred','preview','provide','received','requests','rescheduled','safety','selected','send','signed','site','summary','support','this','too','use','verify','were','will','your',
    ];

    /** @return array{valid:bool,message:string,rule:string,confidence:string,suggestion:string,evidence:array<int,string>} */
    public function analyze(string $source, string $translation, string $locale, array $localeVocabulary = []): array
    {
        $source = trim($source);
        $translation = trim($translation);
        $locale = str_replace('-', '_', trim($locale));

        if ($source === '' || $translation === '' || $locale === 'en' || $locale === 'en_US') {
            return $this->clean();
        }

        return $this->detectEnglishResidue($source, $translation, $locale);
    }

    /** @return array{valid:bool,message:string,rule:string,confidence:string,suggestion:string,evidence:array<int,string>} */
    private function detectEnglishResidue(string $source, string $translation, string $locale): array
    {
        $sourceWords = array_values(array_unique($this->words($source)));
        $translationWords = array_values(array_unique($this->words($translation)));
        if ($sourceWords === [] || $translationWords === []) {
            return $this->clean();
        }

        $shared = array_values(array_intersect($sourceWords, $translationWords));
        $shared = array_values(array_filter($shared, fn(string $word): bool => !$this->allowed($word, $locale)));
        if ($shared === []) {
            return $this->clean();
        }

        $single = array_values(array_intersect($shared, self::SINGLE_MARKERS));
        if ($single !== []) {
            return $this->issue(
                'Mixed-language UX string: a high-confidence English fragment remains inside the translation.',
                'english-residue-high-confidence',
                '',
                'high',
                array_slice($single, 0, 5)
            );
        }

        $multi = array_values(array_intersect($shared, self::MULTI_MARKERS));
        if (count($multi) >= 2) {
            return $this->issue(
                'Mixed-language UX string: multiple English source words remain inside the translation.',
                'english-residue-multiple',
                '',
                'high',
                array_slice($multi, 0, 8)
            );
        }

        return $this->clean();
    }

    /** @return array<int,string> */
    private function words(string $value): array
    {
        $value = preg_replace([
            '~https?://\S+|\b\S+@\S+\b~u',
            '/(?<!%)%(?:\d+\$)?[-+0 #\']*(?:\d+|\*)?(?:\.(?:\d+|\*))?[bcdeEfFgGosuxX]/',
            '/\{\{\s*[a-zA-Z0-9_.-]+\s*\}\}/',
            '/(?<!\{)\{\s*[a-zA-Z0-9_.-]+\s*\}(?!\})/',
        ], ' ', strip_tags($value)) ?? $value;
        preg_match_all('/\p{L}[\p{L}\p{M}\'-]*/u', $value, $matches);
        return array_map(fn($word): string => $this->lower((string)$word), $matches[0] ?? []);
    }

    private function allowed(string $word, string $locale): bool
    {
        if ($this->length($word) <= 2 || in_array($word, self::TECHNICAL_ALLOWLIST, true)) {
            return true;
        }
        $language = substr($locale, 0, 2);
        return in_array($word, self::LOCALE_ALLOWLIST[$locale] ?? [], true)
            || in_array($word, self::LOCALE_ALLOWLIST[$language] ?? [], true);
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function clean(): array
    {
        return ['valid'=>true,'message'=>'','rule'=>'','confidence'=>'','suggestion'=>'','evidence'=>[]];
    }

    private function issue(string $message, string $rule, string $suggestion, string $confidence, array $evidence): array
    {
        return compact('message','rule','suggestion','confidence','evidence') + ['valid'=>false];
    }
}
