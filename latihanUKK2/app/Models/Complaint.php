<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = ['room_id', 'event_id', 'reporter_id', 'facility_name', 'description', 'photo_path', 'approval_status', 'status_repair', 'repair_notes', 'repair_photo_path'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
