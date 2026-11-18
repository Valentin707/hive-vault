<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaultAuditLog extends Model
{
    use HasUuids;

    public const VAULT_INITIALIZED = 'vault_initialized';

    public const VAULT_UNLOCKED = 'vault_unlocked';

    public const VAULT_LOCKED = 'vault_locked';

    public const ITEM_CREATED = 'item_created';

    public const ITEM_VIEWED = 'item_viewed';

    public const ITEM_COPIED = 'item_copied';

    public const ITEM_UPDATED = 'item_updated';

    public const ITEM_DELETED = 'item_deleted';

    protected $fillable = [
        'user_id',
        'vault_item_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vaultItem(): BelongsTo
    {
        return $this->belongsTo(VaultItem::class);
    }
}
