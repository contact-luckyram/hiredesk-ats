<?php

namespace App\Support;

/** Tiny server-side SVG chart helpers (no JavaScript, so charts render instantly and print well). */
class Svg
{
    /**
     * Polyline points for a sparkline / area chart scaled into a width x height box.
     *
     * @param  array<int, float|int>  $values
     * @return array{line: string, area: string, points: array<int, array{0: float, 1: float}>}
     */
    public static function series(array $values, float $width, float $height, float $pad = 4, ?float $max = null): array
    {
        $values = array_values($values) ?: [0];
        $max = max($max ?? max($values), 1);
        $n = count($values);
        $step = $n > 1 ? ($width - 2 * $pad) / ($n - 1) : 0;

        $points = [];
        foreach ($values as $i => $v) {
            $points[] = [round($pad + $i * $step, 2), round($height - $pad - ($v / $max) * ($height - 2 * $pad), 2)];
        }

        $line = 'M'.implode(' L', array_map(fn ($p) => $p[0].' '.$p[1], $points));
        $last = end($points);
        $area = $line.' L'.$last[0].' '.($height - $pad).' L'.$points[0][0].' '.($height - $pad).' Z';

        return ['line' => $line, 'area' => $area, 'points' => $points];
    }

    /**
     * Donut segments as stroke-dasharray values for circles of the given radius.
     *
     * @param  array<string, int>  $values
     * @return array<int, array{label: string, value: int, dash: string, offset: float, percent: int}>
     */
    public static function donut(array $values, float $radius): array
    {
        $total = array_sum($values) ?: 1;
        $circumference = 2 * M_PI * $radius;
        $offset = 0;
        $segments = [];

        foreach ($values as $label => $value) {
            $length = $value / $total * $circumference;
            $segments[] = [
                'label' => (string) $label,
                'value' => $value,
                'dash' => round($length, 2).' '.round($circumference - $length, 2),
                'offset' => round(-$offset, 2),
                'percent' => (int) round($value / $total * 100),
            ];
            $offset += $length;
        }

        return $segments;
    }
}
