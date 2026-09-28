<?php

namespace App\Models;

use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    protected $fillable = ['campaign_name', 'subject', 'message', 'attachment_path', 'status', 'created_by'];

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}
