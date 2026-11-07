<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'booking_group_id',
        'title',
        'borrower_name',
        'borrower_whatsapp',
        'description',
        'date',
        'end_date',
        'start_time',
        'end_time',
        'status',
        'move_reason',
        'rejection_reason',
        'returned_at',
        'checkout_condition',
        'checkout_notes',
        'deleted_by_creator_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'end_date' => 'date',
            'returned_at' => 'datetime',
            'deleted_by_creator_at' => 'datetime',
        ];
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }

    public function isDeletedByCreator(): bool
    {
        return $this->deleted_by_creator_at !== null;
    }

    public function endsNextDay(): bool
    {
        return $this->endAt()->toDateString() === $this->date->copy()->addDay()->toDateString();
    }

    public function isMultiDay(): bool
    {
        return $this->end_date !== null && ! $this->end_date->isSameDay($this->date);
    }

    public function startAt(): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $this->date->toDateString().' '.substr($this->start_time, 0, 8),
            'Asia/Jakarta'
        );
    }

    public function endAt(): CarbonImmutable
    {
        $endDate = CarbonImmutable::parse(
            ($this->end_date ?? $this->date)->toDateString(),
            'Asia/Jakarta'
        );
        if ($endDate->isSameDay($this->date)
            && substr($this->end_time, 0, 5) < substr($this->start_time, 0, 5)) {
            $endDate = $endDate->addDay();
        }

        return CarbonImmutable::parse(
            $endDate->toDateString().' '.substr($this->end_time, 0, 8),
            'Asia/Jakarta'
        );
    }

    public function executionStatus(): string
    {
        return $this->endAt()->lte(now('Asia/Jakarta'))
            ? 'Terlaksana'
            : 'Belum terlaksana';
    }

    public function hasStarted(): bool
    {
        return now('Asia/Jakarta')->gte($this->startAt());
    }

    public function canBeCheckedOut(): bool
    {
        return $this->status === 'approved' && $this->hasStarted() && ! $this->isReturned();
    }

    public function canBeDeletedBy(User $user): bool
    {
        $role = $user->role?->name;
        if ($role === 'admin') {
            return true;
        }

        if ($this->user_id !== $user->id || ! in_array($role, ['guru', 'organisasi'], true)) {
            return false;
        }

        if ($this->status === 'rejected') {
            return true;
        }

        return $this->endAt()->startOfDay()->addDays(3)->lte(now('Asia/Jakarta')->startOfDay());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }
}
