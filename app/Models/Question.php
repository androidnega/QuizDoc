<?php

namespace App\Models;

use App\Casts\EncryptedNullable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Question extends Model
{
    protected $fillable = [
        'quiz_id', 'text', 'type', 'options', 'correct_answer',
        'topic', 'source', 'points', 'explanation_wrong', 'explanation_correct',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'text' => EncryptedNullable::class,
        ];
    }

    /**
     * Backward-compatibility for legacy MySQL imports where the column is
     * accidentally named uppercase `TEXT` instead of `text`.
     */
    public function getTextAttribute($value): ?string
    {
        $raw = $value;
        if (($raw === null || $raw === '') && array_key_exists('TEXT', $this->attributes)) {
            $raw = $this->attributes['TEXT'];
        }

        if ($raw === null || $raw === '') {
            return $raw;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (DecryptException $e) {
            return $raw;
        }
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
