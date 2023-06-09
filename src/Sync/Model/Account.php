<?php

namespace Sync\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property mixed $integration
 * @property mixed $kommo_id
 */
class Account extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'kommo_id',
    ];

    /**
     * Relation to contacts table
     *
     * @return HasMany
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Relation to accesses table
     *
     * @return HasOne
     */
    public function access(): HasOne
    {
        return $this->hasOne(Access::class);
    }

    /**
     * Relation to integrations table
     *
     * @return HasOne
     */
    public function integration(): HasOne
    {
        return $this->hasOne(Integration::class);
    }
}
