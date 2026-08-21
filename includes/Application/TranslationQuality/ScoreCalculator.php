<?php

declare(strict_types=1);

namespace Slotera\Application\TranslationQuality;

if (!defined('ABSPATH')) { exit; }

final class ScoreCalculator
{
    public function calculate(int $source, int $translated, int $valid, int $critical): array
    {
        $completion = $source > 0 ? round(($translated / $source) * 100, 1) : 100.0;
        $quality = $translated > 0 ? round(($valid / $translated) * 100, 1) : 100.0;
        $safety = $critical > 0 ? 0.0 : 100.0;
        $readiness = round(($completion * 0.40) + ($quality * 0.45) + ($safety * 0.15), 1);

        return [
            'completion' => $completion,
            'quality' => $quality,
            'readiness' => $readiness,
        ];
    }

    public function status(array $scores, int $critical, int $warnings): string
    {
        if ($critical > 0) { return 'blocked'; }
        if ((float)($scores['completion'] ?? 0) >= 95.0 && (float)($scores['quality'] ?? 0) >= 98.0 && $warnings <= 10) {
            return 'ready';
        }
        if ((float)($scores['quality'] ?? 0) < 90.0 || $warnings > 50) { return 'needs_attention'; }
        return 'in_progress';
    }
}
