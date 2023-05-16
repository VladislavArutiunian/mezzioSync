<?php

namespace Sync\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'account_id',
        'kommo_contact_id',
        'emails'
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
