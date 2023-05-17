<?php

namespace Sync\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Integration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'account_id',
        'client_id',
        'secret_key',
        'url'
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

    /**
     * @return string
     */
    public function getReturnUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getIntegrationId(): string
    {
        return $this->client_id;
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->secret_key;
    }

    /**
     * @return string
     */
    public function getAccountId(): string
    {
        return $this->account_id;
    }
}
