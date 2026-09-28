<?php

namespace App\Models;

use Database\Factories\CampaignRecipientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    /** @use HasFactory<CampaignRecipientFactory> */
    use HasFactory;

    protected $fillable = ['campaign_id', 'recipient_id', 'email', 'status'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
