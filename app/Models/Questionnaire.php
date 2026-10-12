<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pre-screening questions. Each question has a weight (points) and may be a
 * knock-out: a wrong answer screens the applicant out automatically.
 *
 * questions: [{key, label, type: yes_no|choice|number|text, options[], ideal, knockout, weight}]
 */
class Questionnaire extends Model
{
    protected $fillable = ['name', 'description', 'questions', 'keywords', 'keyword_points'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'keywords' => 'array'];
    }

    public function jobOpenings(): HasMany
    {
        return $this->hasMany(JobOpening::class);
    }

    public function maxPoints(): int
    {
        return (int) collect($this->questions ?? [])->sum(fn ($q) => (int) ($q['weight'] ?? 0)) + (int) $this->keyword_points;
    }
}
