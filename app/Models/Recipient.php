<?php

namespace App\Models;

use Database\Factories\RecipientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recipient extends Model
{
    /** @use HasFactory<RecipientFactory> */
    use HasFactory;

    protected $fillable = ['name', 'email', 'status'];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(RecipientGroup::class, 'group_members', 'recipient_id', 'group_id');
    }
}
