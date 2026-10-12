<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Applies a Boolean keyword search to a query, e.g.
 *   ICU AND (ACLS OR BLS) NOT travel
 *   "wound care" -agency
 * Terms are ANDed by default; OR joins groups; NOT / a leading "-" excludes.
 */
class BooleanSearch
{
    /** @param  array<int, string>  $columns */
    public static function apply(Builder $query, string $expression, array $columns): Builder
    {
        $expression = trim(str_replace(['(', ')'], ' ', $expression));
        if ($expression === '') {
            return $query;
        }

        $groups = preg_split('/\s+OR\s+/i', $expression);

        return $query->where(function (Builder $outer) use ($groups, $columns) {
            foreach ($groups as $group) {
                preg_match_all('/(-|NOT\s+)?"([^"]+)"|(-|NOT\s+)?(\S+)/i', $group, $matches, PREG_SET_ORDER);

                $outer->orWhere(function (Builder $q) use ($matches, $columns) {
                    foreach ($matches as $m) {
                        $negate = ! empty($m[1]) || ! empty($m[3] ?? '');
                        $term = $m[2] !== '' ? $m[2] : ($m[4] ?? '');
                        if ($term === '' || strtoupper($term) === 'AND') {
                            continue;
                        }

                        $q->where(function (Builder $w) use ($term, $columns, $negate) {
                            foreach ($columns as $column) {
                                $negate
                                    ? $w->where(fn ($x) => $x->whereNull($column)->orWhere($column, 'not like', "%{$term}%"))
                                    : $w->orWhere($column, 'like', "%{$term}%");
                            }
                        });
                    }
                });
            }
        });
    }
}
