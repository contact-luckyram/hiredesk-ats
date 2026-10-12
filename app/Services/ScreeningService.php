<?php

namespace App\Services;

use App\Models\Questionnaire;
use Illuminate\Support\Str;

/**
 * Scores questionnaire answers: weighted points per question, knock-out
 * questions, plus points for resume keywords. Returns a 0-100 score.
 */
class ScreeningService
{
    /**
     * @return array{score: int, knocked_out: bool, breakdown: array<int, array{label: string, points: int, max: int, knockout: bool, passed: bool}>}
     */
    public function score(?Questionnaire $questionnaire, array $answers, ?string $resumeText = null): array
    {
        if (! $questionnaire) {
            return ['score' => null, 'knocked_out' => false, 'breakdown' => []];
        }

        $earned = 0;
        $max = 0;
        $knockedOut = false;
        $breakdown = [];

        foreach ($questionnaire->questions ?? [] as $q) {
            $key = $q['key'] ?? Str::slug($q['label'] ?? '', '_');
            $weight = (int) ($q['weight'] ?? 0);
            $answer = $answers[$key] ?? null;
            $passed = $this->matches($q, $answer);

            if (! empty($q['knockout']) && ! $passed) {
                $knockedOut = true;
            }

            $points = $passed ? $weight : 0;
            $earned += $points;
            $max += $weight;

            $breakdown[] = [
                'label' => $q['label'] ?? $key,
                'answer' => is_array($answer) ? implode(', ', $answer) : (string) $answer,
                'points' => $points,
                'max' => $weight,
                'knockout' => ! empty($q['knockout']),
                'passed' => $passed,
            ];
        }

        $keywords = $questionnaire->keywords ?? [];
        if ($keywords && $questionnaire->keyword_points) {
            $found = collect($keywords)->filter(fn ($k) => $resumeText && Str::contains($resumeText, $k, ignoreCase: true));
            $points = (int) round($questionnaire->keyword_points * $found->count() / count($keywords));
            $earned += $points;
            $max += $questionnaire->keyword_points;
            $breakdown[] = [
                'label' => 'Resume keywords',
                'answer' => $found->implode(', ') ?: 'none found',
                'points' => $points,
                'max' => (int) $questionnaire->keyword_points,
                'knockout' => false,
                'passed' => $points > 0,
            ];
        }

        return [
            'score' => $max ? (int) round($earned / $max * 100) : null,
            'knocked_out' => $knockedOut,
            'breakdown' => $breakdown,
        ];
    }

    private function matches(array $q, mixed $answer): bool
    {
        $ideal = $q['ideal'] ?? null;
        if ($answer === null || $answer === '') {
            return false;
        }

        return match ($q['type'] ?? 'yes_no') {
            'number' => is_numeric($answer) && (float) $answer >= (float) ($ideal ?? 0),
            'text' => true,
            'choice' => $ideal === null || $ideal === '' || in_array($answer, (array) $ideal, true) || (string) $answer === (string) $ideal,
            default => strtolower((string) $answer) === strtolower((string) ($ideal ?? 'yes')),
        };
    }
}
