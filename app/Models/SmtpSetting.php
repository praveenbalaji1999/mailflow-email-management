<?php

namespace App\Models;

use Database\Factories\SmtpSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmtpSetting extends Model
{
    /** @use HasFactory<SmtpSettingFactory> */
    use HasFactory;

    protected $fillable = ['host', 'port', 'encryption', 'username', 'password', 'from_name', 'from_email'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'encrypted'];
    }
}
