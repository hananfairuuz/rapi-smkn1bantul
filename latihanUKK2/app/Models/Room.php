<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Model Master Ruangan (Tabel 'rooms')
 *
 * Kolom:
 * - name       : Nama fasilitas ruangan (misal: Lab RPL, Aula, Ruang Sidang).
 * - location   : Lokasi fisik gedung & lantai.
 * - capacity   : Daya tampung ruangan (orang).
 * - description: Fasilitas pendukung di dalam ruangan.
 * - pic_id     : ID pengguna ber-role 'pic' penanggung jawab ruangan.
 */
class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'capacity', 'description', 'pic_id'];

    protected $casts = [
        'capacity' => 'integer',
    ];

    /**
     * Relasi: Setiap ruangan dipimpin/diawasi oleh satu PIC Ruang (User).
     */
    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    /**
     * Relasi: Satu ruangan memiliki banyak jadwal permohonan agenda (One-to-Many).
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Relasi: Satu ruangan memiliki banyak riwayat pengaduan kerusakan (One-to-Many).
     */
    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * Filter koleksi event untuk menentukan mana yang masih aktif / dalam pembokingan
     * sehingga mencegah penghapusan ruangan sebelum ada pergantian ruangan.
     */
    public static function filterActiveBookings(Collection $events, ?CarbonImmutable $now = null): Collection
    {
        $now ??= CarbonImmutable::now('Asia/Jakarta');

        return $events->filter(function (Event $event) use ($now) {
            if ($event->deleted_by_creator_at !== null || $event->returned_at !== null) {
                return false;
            }
            if (! in_array($event->status, ['pending', 'approved'], true)) {
                return false;
            }

            return $event->endAt()->gt($now);
        })->values();
    }

    /**
     * Mengambil daftar agenda yang masih dalam pembokingan (pending/approved)
     * atau sedang dipakai pada ruangan ini dan belum selesai/dikembalikan.
     */
    public function activeBookings(): Collection
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $events = $this->events()
            ->with('user:id,name')
            ->whereIn('status', ['pending', 'approved'])
            ->whereNull('deleted_by_creator_at')
            ->whereNull('returned_at')
            ->where(function ($q) use ($now) {
                $q->whereDate('date', '>=', $now->subDay()->toDateString())
                    ->orWhereDate('end_date', '>=', $now->toDateString());
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return self::filterActiveBookings($events, $now);
    }

    /**
     * Memindahkan seluruh agenda aktif/pembokingan dari ruangan ini ke ruangan pengganti.
     * Memeriksa tabrakan jadwal terlebih dahulu sebelum melakukan transaksi database,
     * dan mengirimkan notifikasi pergantian ruangan kepada masing-masing peminjam.
     *
     * @throws \RuntimeException jika terjadi bentrok jadwal dengan agenda lain di ruangan pengganti.
     */
    public function moveActiveBookingsTo(Room $replacementRoom, string $moveReason): int
    {
        $activeBookings = $this->activeBookings();
        if ($activeBookings->isEmpty()) {
            return 0;
        }

        $targetEvents = self::filterActiveBookings(
            $replacementRoom->events()
                ->whereIn('status', ['pending', 'approved'])
                ->whereNull('deleted_by_creator_at')
                ->whereNull('returned_at')
                ->get()
        );

        foreach ($activeBookings as $booking) {
            $bStart = $booking->startAt()->getTimestamp();
            $bEnd = $booking->endAt()->getTimestamp();

            $conflict = $targetEvents->first(function (Event $target) use ($bStart, $bEnd) {
                return $target->startAt()->getTimestamp() < $bEnd
                    && $target->endAt()->getTimestamp() > $bStart;
            });

            if ($conflict) {
                $tgl = $booking->date->format('d/m/Y');
                $jam = substr($booking->start_time, 0, 5) . '–' . substr($booking->end_time, 0, 5);
                throw new \RuntimeException(
                    "Ruangan pengganti '{$replacementRoom->name}' bentrok jadwal dengan agenda '{$conflict->title}' untuk peminjaman '{$booking->title}' ({$tgl} {$jam}). Silakan pilih ruangan pengganti lain."
                );
            }
        }

        return DB::transaction(function () use ($activeBookings, $replacementRoom, $moveReason) {
            $oldRoomName = $this->name;
            $movedBorrowerIds = [];

            foreach ($activeBookings as $booking) {
                $booking->update([
                    'room_id' => $replacementRoom->id,
                    'move_reason' => $moveReason,
                ]);

                if ($booking->user_id) {
                    $movedBorrowerIds[] = (int) $booking->user_id;
                    SystemNotification::create([
                        'user_id' => $booking->user_id,
                        'event_id' => $booking->id,
                        'title' => 'Pergantian Ruangan Peminjaman',
                        'message' => "Ruangan untuk agenda '{$booking->title}' dipindahkan dari {$oldRoomName} ke {$replacementRoom->name}. Alasan: {$moveReason}",
                    ]);
                }
            }

            if ($replacementRoom->pic_id) {
                $firstBooking = $activeBookings->first();
                $count = $activeBookings->count();
                SystemNotification::notifyUsers(
                    [$replacementRoom->pic_id],
                    'Agenda Dipindahkan ke Ruangan Anda',
                    "{$count} agenda dari {$oldRoomName} dipindahkan ke {$replacementRoom->name}. Alasan: {$moveReason}",
                    $firstBooking?->id,
                    array_merge([auth()->id()], $movedBorrowerIds)
                );
            }

            return $activeBookings->count();
        });
    }
}
