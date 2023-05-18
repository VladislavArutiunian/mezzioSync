<?php

namespace Sync\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
<<<<<<< HEAD
 * @method static where(string $string, string $string1, string $clientId)
 * @property mixed $url
 * @property mixed $secret_key
 * @property mixed $client_id
=======
 * @property mixed $url
 * @property mixed $client_id
 * @property mixed $secret_key
 * @method static where(string $string, string $string1, string $clientId)
>>>>>>> master
 */
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
}
