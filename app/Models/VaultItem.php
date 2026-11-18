<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VaultItem extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'folder_id',
        'type',
        'encrypted_title',
        'title_iv',
        'encrypted_payload',
        'payload_iv',
        'is_favorite',
        'last_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(VaultFolder::class, 'folder_id')
            ->where('user_id', $this->user_id);
    }
}
