<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'capacity', 'description', 'pic_id'];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }
}
