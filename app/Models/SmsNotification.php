<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsNotification extends Model
{
    protected $fillable = [
        'sender_id', 'recipient_count', 'message', 'filters',
        'status', 'sent_count', 'failed_count', 'organization_id',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
