<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    protected $fillable = [
    'user_id', 'client_name', 'industry', 'pain_points', 'deal_size', 'generated_content', 'status'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
