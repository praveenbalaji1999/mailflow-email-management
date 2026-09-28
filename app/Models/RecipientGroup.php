<?php

namespace App\Models;

use Database\Factories\RecipientGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RecipientGroup extends Model
{
    /** @use HasFactory<RecipientGroupFactory> */
    use HasFactory;

    protected $table = 'recipient_groups';

    protected $fillable = ['name', 'description'];

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Recipient::class, 'group_members', 'group_id', 'recipient_id');
    }
}
