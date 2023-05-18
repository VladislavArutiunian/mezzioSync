<?php

namespace Sync\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property false|mixed|string $kommo_access_token
 * @property int|mixed|null $account_id
 */
class Access extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'account_id',
        'kommo_access_token',
        'unisender_api_key'
    ];

    /**
     * Relation to accounts table
     *
     * @return BelongsTo
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
