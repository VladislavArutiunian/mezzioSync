<?php

namespace Sync\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function contact(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function access(): HasOne
    {
        return $this->hasOne(Access::class);
    }

    public function integration(): HasOne
    {
        return $this->hasOne(Integration::class);
    }
}
