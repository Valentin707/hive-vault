<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VaultFolder extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'encrypted_name',
        'iv',
        'sort_order',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VaultItem::class, 'folder_id')
            ->where('user_id', $this->user_id);
    }
}
