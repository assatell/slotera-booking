<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

/**
 * Conservative heuristic lint for translations that are technically present
 * but still look like literal machine output or an unfinished mixed-language draft.
 *
 * Rules intentionally favour precision over recall: a warning is only emitted
 * when a known high-confidence construction or a strong script anomaly is found.
 */
final class TranslationQualityLintService
{
    /**
     * @return array{valid:bool,message:string,rule:string,confidence:string,suggestion:string,evidence:array<int,string>}
     */
    public function analyze(string $source, string $translation, string $locale): array
    {
        $source = trim($source);
        $translation = trim($translation);
        $locale = str_replace('-', '_', trim($locale));

        if ($source === '' || $translation === '') {
            return $this->clean();
        }

        if ($this->isRussian($locale)) {
            $result = $this->analyzeRussian($translation);
            if (!$result['valid']) {
                return $result;
            }
        }

        $mixed = $this->detectMixedScriptArtifact($translation, $locale);
        if (!$mixed['valid']) {
            return $mixed;
        }

        return $this->clean();
    }

    private function analyzeRussian(string $translation): array
    {
        $normalized = $this->lower(preg_replace('/\s+/u', ' ', $translation) ?? $translation);

        $rules = [
            ['/(^|[.!?]\s+)недействительно\s+(запрос|пакет|ссылка|токен|дата)([.!?]|$)/u', 'literal-invalid-noun', 'Недействительный запрос', 'Неестественное согласование в буквальном переводе “Invalid …”.'],
            ['/(^|[.!?]\s+)(новый|новое)\s+дата([.!?]|$)/u', 'adjective-gender-date', 'Новая дата', 'Прилагательное не согласовано со словом «дата».'],
            ['/(^|[.!?]\s+)фиксированный\s+цена([.!?]|$)/u', 'adjective-gender-price', 'Фиксированная цена', 'Прилагательное не согласовано со словом «цена».'],
            ['/(^|[.!?]\s+)пользовательский\s+оплата([.!?]|$)/u', 'adjective-gender-payment', 'Пользовательская оплата', 'Прилагательное не согласовано со словом «оплата».'],
            ['/(^|[.!?]\s+)выбрать\s+(ваш\s+)?дата([.!?]|$)/u', 'infinitive-ui-command-date', 'Выберите дату', 'Для интерфейсной команды используется инфинитив и нарушено согласование.'],
            ['/(^|[.!?]\s+)выбрать\s+время([.!?]|$)/u', 'infinitive-ui-command-time', 'Выберите время', 'Для интерфейсной команды лучше использовать повелительную форму.'],
            ['/(^|[.!?]\s+)пожалуйста\s+выбрать\b/u', 'literal-please-command', 'Пожалуйста, выберите…', 'Буквальная конструкция “Please choose” выглядит как машинный перевод.'],
            ['/(^|[.!?]\s+)отсутствует\s+перенести\s+данные([.!?]|$)/u', 'literal-reschedule-data', 'Отсутствуют данные для переноса', 'Нарушены порядок слов и форма глагола.'],
            ['/(^|[.!?]\s+)пакет\s+детали([.!?]|$)/u', 'noun-stack-package-details', 'Детали пакета', 'Порядок существительных скопирован с английского.'],
            ['/(^|[.!?]\s+)запланировано\s+(даты|событие)([.!?]|$)/u', 'participle-agreement-scheduled', 'Запланированные даты', 'Форма слова не согласована с существительным.'],
            ['/(^|[.!?]\s+)показать\s+пакет\s+описание([.!?]|$)/u', 'noun-stack-package-description', 'Показать описание пакета', 'Порядок существительных скопирован с английского.'],
            ['/(^|[.!?]\s+)купон\s+скидка([.!?]|$)/u', 'noun-stack-coupon-discount', 'Скидка по купону', 'Порядок существительных скопирован с английского.'],
            ['/(^|[.!?]\s+)открыть\s+оплата\s+ссылка([.!?]|$)/u', 'noun-stack-payment-link', 'Открыть ссылку для оплаты', 'Порядок слов похож на буквальную подстановку.'],
            ['/(^|[.!?]\s+)бронирование\s+ошибка([.!?]|$)/u', 'noun-stack-booking-failed', 'Ошибка бронирования', 'Порядок существительных скопирован с английского.'],
            ['/(^|[.!?]\s+)да,?\s+отменить\s+бронирование([.!?]|$)/u', 'ui-command-capitalization', 'Да, отменить бронирование', 'Команда выглядит как необработанная машинная строка.'],
        ];

        foreach ($rules as [$pattern, $rule, $suggestion, $message]) {
            if (preg_match($pattern, $normalized) !== 1) {
                continue;
            }

            // A lint rule must not report an issue when its own normalized
            // suggestion is identical to the current translation. This keeps
            // conservative heuristics from flagging already-correct UI copy.
            if ($suggestion !== '' && $this->normalizeComparable($translation) === $this->normalizeComparable($suggestion)) {
                continue;
            }

            return $this->issue($message, $rule, $suggestion, 'high', [trim($translation)]);
        }

        // English article/preposition surrounded by Cyrillic words is a strong
        // unfinished-machine-output signal, while technical tokens remain allowed.
        if (preg_match('/\p{Cyrillic}+[\p{Cyrillic}\p{M}]*\s+(a|an|the|and|or|to|with|for|from|in|on)\s+\p{Cyrillic}+/iu', $translation, $match) === 1) {
            return $this->issue(
                'В переводе осталась английская служебная часть речи между русскими словами.',
                'embedded-english-function-word',
                '',
                'high',
                [(string) ($match[0] ?? '')]
            );
        }

        return $this->clean();
    }

    private function detectMixedScriptArtifact(string $translation, string $locale): array
    {
        if (preg_match('/\p{Cyrillic}/u', $translation) !== 1) {
            return $this->clean();
        }

        // Words that splice Latin and Cyrillic letters are almost always copy or
        // transliteration artifacts (for example “купонs”). URLs, emails and runtime
        // placeholders are excluded because placeholder identifiers are intentionally
        // Latin even inside Cyrillic email templates.
        // Email template bodies store line breaks as literal escape sequences
        // (for example "\n\n"). Remove those control tokens before the
        // word-level script check so the Latin "n" is not joined to the first
        // Cyrillic word on the next line (for example "nБлагодарим").
        $plain = str_replace(['\\n', '\\r', '\\t'], ' ', $translation);
        $plain = preg_replace('~\{[A-Za-z0-9_.:-]+\}|https?://\S+|\b\S+@\S+\b~u', ' ', $plain) ?? $plain;
        if (preg_match('/\b(?=[\p{L}\p{M}]*\p{Latin})(?=[\p{L}\p{M}]*\p{Cyrillic})[\p{L}\p{M}]+\b/u', $plain, $match) === 1) {
            return $this->issue(
                'Одно слово содержит смесь латинских и кириллических букв.',
                'mixed-script-word',
                '',
                'high',
                [(string) ($match[0] ?? '')]
            );
        }

        return $this->clean();
    }

    private function isRussian(string $locale): bool
    {
        return $locale === 'ru' || strpos($locale, 'ru_') === 0;
    }


    private function normalizeComparable(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        return $this->lower($value);
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function clean(): array
    {
        return ['valid' => true, 'message' => '', 'rule' => '', 'confidence' => '', 'suggestion' => '', 'evidence' => []];
    }

    private function issue(string $message, string $rule, string $suggestion, string $confidence, array $evidence): array
    {
        return [
            'valid' => false,
            'message' => $message,
            'rule' => $rule,
            'confidence' => $confidence,
            'suggestion' => $suggestion,
            'evidence' => $evidence,
        ];
    }
}
