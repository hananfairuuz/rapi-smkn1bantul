<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Notifikasi Sistem (Tabel 'system_notifications')
 *
 * Menampilkan pesan peringatan/lonceng untuk user:
 * - title   : Judul notifikasi.
 * - message : Isi pesan.
 * - read_at : Tanggal/jam notifikasi dibaca (null jika belum dibaca).
 */
class SystemNotification extends Model
{
    protected $fillable = ['user_id', 'event_id', 'title', 'message', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    /**
     * Relasi ke User penerima notifikasi.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi opsional ke agenda yang terkait dengan notifikasi.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Mengirim notifikasi yang sama ke sekumpulan ID user secara unik (tanpa duplikasi).
     */
    public static function notifyUsers(
        iterable $userIds,
        string $title,
        string $message,
        ?int $eventId = null,
        array|int|null $excludeUserIds = null
    ): void {
        $excluded = collect(is_array($excludeUserIds) ? $excludeUserIds : [$excludeUserIds])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $targets = collect($userIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => in_array($id, $excluded, true));

        foreach ($targets as $userId) {
            self::create([
                'user_id' => $userId,
                'event_id' => $eventId,
                'title' => $title,
                'message' => $message,
            ]);
        }
    }

    /**
     * Mengambil seluruh ID pengguna ber-peran Admin.
     */
    public static function adminIds(): array
    {
        return User::whereHas('role', fn ($q) => $q->where('name', 'admin'))
            ->pluck('id')
            ->all();
    }

    /**
     * Mengirim notifikasi ke seluruh pengguna ber-peran Admin.
     */
    public static function notifyAdmins(
        string $title,
        string $message,
        ?int $eventId = null,
        array|int|null $excludeUserIds = null
    ): void {
        self::notifyUsers(self::adminIds(), $title, $message, $eventId, $excludeUserIds);
    }

    /**
     * Notifikasi pengajuan peminjaman ruangan baru ke Admin & PIC ruangan terkait.
     */
    public static function notifyBookingSubmitted(array $events, User $actor, bool $autoApproved): void
    {
        if (empty($events)) {
            return;
        }

        $actor->loadMissing('role');
        $collection = collect($events);
        $roomsById = Room::whereIn('id', $collection->pluck('room_id')->filter()->unique())
            ->get()
            ->keyBy('id');
        /** @var Event $first */
        $first = $collection->first();
        $dateLabel = self::formatEventDate($first);
        $timeLabel = self::formatEventTime($first);
        $allRoomNames = $collection->map(fn (Event $ev) => $roomsById->get($ev->room_id)?->name)->filter()->unique()->implode(', ');
        $roleLabel = self::roleDisplayName($actor->role?->name);

        if ($autoApproved) {
            self::notifyAdmins(
                'Agenda Peminjaman Baru (Disetujui)',
                "{$actor->name} ({$roleLabel}) membuat jadwal peminjaman {$allRoomNames} untuk agenda '{$first->title}' pada {$dateLabel} ({$timeLabel}).",
                $first->id,
                $actor->id
            );
        } else {
            self::notifyAdmins(
                'Pengajuan Peminjaman Ruang Baru',
                "{$actor->name} ({$roleLabel}) mengajukan peminjaman {$allRoomNames} untuk agenda '{$first->title}' pada {$dateLabel} ({$timeLabel}). Menunggu persetujuan.",
                $first->id,
                $actor->id
            );
        }

        $groupedByPic = $collection
            ->filter(fn (Event $ev) => $roomsById->get($ev->room_id)?->pic_id !== null)
            ->groupBy(fn (Event $ev) => (int) $roomsById->get($ev->room_id)->pic_id);

        foreach ($groupedByPic as $picId => $picEvents) {
            /** @var Event $picFirst */
            $picFirst = $picEvents->first();
            $picRooms = $picEvents->map(fn (Event $ev) => $roomsById->get($ev->room_id)?->name)->filter()->unique()->implode(', ');
            $title = $autoApproved ? 'Ruangan Anda Dijadwalkan' : 'Ruangan Diajukan untuk Peminjaman';
            $message = $autoApproved
                ? "Agenda '{$picFirst->title}' (peminjam: {$picFirst->borrower_name}) akan memakai {$picRooms} pada {$dateLabel} ({$timeLabel})."
                : "{$actor->name} mengajukan peminjaman {$picRooms} untuk agenda '{$picFirst->title}' pada {$dateLabel} ({$timeLabel}) — menunggu persetujuan admin.";

            self::notifyUsers([$picId], $title, $message, $picFirst->id, $actor->id);
        }
    }

    /**
     * Notifikasi perubahan status persetujuan agenda ke Peminjam (Guru/Organisasi) & PIC ruangan.
     */
    public static function notifyEventStatusUpdated(Event $event, User $actor): void
    {
        $room = Room::find($event->room_id);
        $roomName = $room?->name ?? 'Ruangan';
        $dateLabel = self::formatEventDate($event);
        $timeLabel = self::formatEventTime($event);
        $isApproved = $event->status === 'approved';

        self::notifyUsers(
            [$event->user_id],
            $isApproved ? 'Agenda Disetujui' : 'Agenda Ditolak',
            $isApproved
                ? "Pengajuan peminjaman ruangan '{$roomName}' untuk agenda '{$event->title}' telah disetujui."
                : "Pengajuan peminjaman ruangan '{$roomName}' ditolak. Alasan: {$event->rejection_reason}",
            $event->id,
            $actor->id
        );

        if ($room?->pic_id) {
            self::notifyUsers(
                [$room->pic_id],
                $isApproved ? 'Jadwal Ruangan Disetujui' : 'Pengajuan Ruangan Ditolak',
                $isApproved
                    ? "Peminjaman {$roomName} untuk agenda '{$event->title}' pada {$dateLabel} ({$timeLabel}) telah disetujui admin."
                    : "Pengajuan peminjaman {$roomName} untuk agenda '{$event->title}' pada {$dateLabel} ditolak oleh admin.",
                $event->id,
                [$actor->id, $event->user_id]
            );
        }
    }

    /**
     * Notifikasi perubahan jadwal atau pemindahan ruangan agenda oleh Admin.
     */
    public static function notifyEventUpdated(Event $event, Room $oldRoom, User $actor, ?string $moveReason = null): void
    {
        $newRoom = Room::find($event->room_id);
        $newRoomName = $newRoom?->name ?? 'Ruangan';
        $dateLabel = self::formatEventDate($event);
        $timeLabel = self::formatEventTime($event);
        $roomChanged = (int) $event->room_id !== (int) $oldRoom->id;

        if ($roomChanged) {
            self::notifyUsers(
                [$event->user_id],
                'Ruangan agenda dipindahkan',
                "Agenda {$event->title} dipindahkan ke {$newRoomName}. Alasan: {$moveReason}",
                $event->id,
                $actor->id
            );

            if ($newRoom?->pic_id) {
                self::notifyUsers(
                    [$newRoom->pic_id],
                    'Agenda Dipindahkan ke Ruangan Anda',
                    "Agenda '{$event->title}' pada {$dateLabel} ({$timeLabel}) dipindahkan dari {$oldRoom->name} ke {$newRoomName}. Alasan: {$moveReason}",
                    $event->id,
                    [$actor->id, $event->user_id]
                );
            }

            if ($oldRoom->pic_id) {
                self::notifyUsers(
                    [$oldRoom->pic_id],
                    'Agenda Dipindahkan dari Ruangan Anda',
                    "Agenda '{$event->title}' di {$oldRoom->name} pada {$dateLabel} telah dipindahkan ke {$newRoomName}.",
                    $event->id,
                    [$actor->id, $event->user_id, $newRoom?->pic_id]
                );
            }

            return;
        }

        self::notifyUsers(
            [$event->user_id],
            'Jadwal Agenda Diperbarui',
            "Jadwal agenda '{$event->title}' di {$newRoomName} diperbarui oleh {$actor->name} menjadi {$dateLabel} ({$timeLabel}).",
            $event->id,
            $actor->id
        );

        if ($newRoom?->pic_id) {
            self::notifyUsers(
                [$newRoom->pic_id],
                'Perubahan Jadwal Ruangan',
                "Jadwal agenda '{$event->title}' di {$newRoomName} diperbarui menjadi {$dateLabel} ({$timeLabel}).",
                $event->id,
                [$actor->id, $event->user_id]
            );
        }
    }

    /**
     * Notifikasi pengembalian/check-out ruangan ke PIC, Admin, dan Peminjam terkait.
     */
    public static function notifyEventCheckedOut(Event $event, User $actor): void
    {
        $room = Room::find($event->room_id);
        $roomName = $room?->name ?? 'Ruangan';
        $isDamaged = $event->checkout_condition === 'rusak';
        $condLabel = $isDamaged ? 'Ada Kerusakan Dilaporkan' : 'Aman & Bersih';
        $title = $isDamaged ? 'Pengembalian Ruang (Ada Kerusakan)' : 'Ruangan Telah Dikembalikan';
        $msgForManagers = "{$actor->name} mengonfirmasi pengembalian {$roomName} untuk agenda '{$event->title}' (Kondisi: {$condLabel}).";

        $picId = $room?->pic_id;
        if ($picId) {
            self::notifyUsers([$picId], $title, $msgForManagers, $event->id, $actor->id);
        }

        self::notifyAdmins($title, $msgForManagers, $event->id, [$actor->id, $picId]);

        self::notifyUsers(
            [$event->user_id],
            'Konfirmasi Pengembalian Ruangan',
            "Penggunaan {$roomName} untuk agenda '{$event->title}' telah dikonfirmasi selesai & dikembalikan oleh {$actor->name} (Kondisi: {$condLabel}).",
            $event->id,
            $actor->id
        );
    }

    /**
     * Notifikasi pembatalan/penghapusan agenda aktif oleh Admin ke Peminjam & PIC.
     */
    public static function notifyEventDeleted(
        string $title,
        ?string $roomName,
        int $ownerId,
        ?int $picId,
        string $dateLabel,
        User $actor,
        bool $wasRejected
    ): void {
        if ($wasRejected) {
            return;
        }

        $safeRoom = $roomName ?? 'Ruangan';
        self::notifyUsers(
            [$ownerId],
            'Agenda Dihapus',
            "Agenda '{$title}' di {$safeRoom} ({$dateLabel}) telah dihapus oleh {$actor->name}.",
            null,
            $actor->id
        );

        if ($picId) {
            self::notifyUsers(
                [$picId],
                'Agenda Ruangan Dibatalkan',
                "Agenda '{$title}' di {$safeRoom} ({$dateLabel}) telah dibatalkan/dihapus oleh {$actor->name}.",
                null,
                [$actor->id, $ownerId]
            );
        }
    }

    /**
     * Notifikasi pengaduan fasilitas rusak baru ke PIC ruangan & Admin.
     */
    public static function notifyComplaintSubmitted(Complaint $complaint, User $reporter): void
    {
        $room = Room::find($complaint->room_id);
        $roomName = $room?->name ?? 'Ruangan';
        $picId = $room?->pic_id;

        if ($picId) {
            self::notifyUsers(
                [$picId],
                'Pengaduan Fasilitas Baru',
                "{$reporter->name} melaporkan kerusakan '{$complaint->facility_name}' di {$roomName}. Mohon verifikasi dan tindak lanjut.",
                null,
                $reporter->id
            );
        }

        self::notifyAdmins(
            'Laporan Kerusakan Fasilitas',
            "{$reporter->name} melaporkan kerusakan '{$complaint->facility_name}' di {$roomName} (menunggu persetujuan PIC).",
            null,
            [$reporter->id, $picId]
        );
    }

    /**
     * Notifikasi hasil verifikasi pengaduan oleh PIC ke Pelapor & Admin.
     */
    public static function notifyComplaintReviewed(Complaint $complaint, User $actor, string $approvalStatus): void
    {
        $roomName = Room::whereKey($complaint->room_id)->value('name') ?? 'Ruangan';
        $isApproved = $approvalStatus === 'approved';

        self::notifyUsers(
            [$complaint->reporter_id],
            'Status Verifikasi Pengaduan',
            "Laporan kerusakan '{$complaint->facility_name}' di {$roomName} telah " . ($isApproved ? 'disetujui oleh PIC.' : 'ditolak oleh PIC.'),
            null,
            $actor->id
        );

        self::notifyAdmins(
            'Verifikasi Pengaduan oleh PIC',
            "{$actor->name} " . ($isApproved ? 'menyetujui' : 'menolak') . " laporan kerusakan '{$complaint->facility_name}' di {$roomName}.",
            null,
            [$actor->id, $complaint->reporter_id]
        );
    }

    /**
     * Notifikasi pembaruan status perbaikan oleh PIC ke Pelapor & Admin.
     */
    public static function notifyComplaintRepairUpdated(Complaint $complaint, User $actor): void
    {
        $roomName = Room::whereKey($complaint->room_id)->value('name') ?? 'Ruangan';

        self::notifyUsers(
            [$complaint->reporter_id],
            'Update Perbaikan Fasilitas',
            "Status perbaikan fasilitas '{$complaint->facility_name}' di {$roomName} diperbarui menjadi: {$complaint->status_repair}.",
            null,
            $actor->id
        );

        self::notifyAdmins(
            'Update Status Perbaikan PIC',
            "{$actor->name} memperbarui status perbaikan '{$complaint->facility_name}' di {$roomName} menjadi: {$complaint->status_repair}.",
            null,
            [$actor->id, $complaint->reporter_id]
        );
    }

    /**
     * Notifikasi penugasan atau pengalihan PIC ruangan.
     */
    public static function notifyRoomPicAssigned(Room $room, User $actor, ?int $previousPicId = null): void
    {
        if ($room->pic_id && (int) $room->pic_id !== (int) $previousPicId) {
            $loc = $room->location ? " ({$room->location})" : '';
            self::notifyUsers(
                [$room->pic_id],
                'Penugasan PIC Ruangan',
                "Anda ditugaskan oleh {$actor->name} sebagai PIC ruangan '{$room->name}'{$loc}.",
                null,
                $actor->id
            );
        }

        if ($previousPicId && (int) $previousPicId !== (int) $room->pic_id) {
            self::notifyUsers(
                [$previousPicId],
                'Perubahan Penugasan PIC Ruangan',
                "Tugas Anda sebagai PIC untuk ruangan '{$room->name}' telah dialihkan oleh {$actor->name}.",
                null,
                $actor->id
            );
        }
    }

    private static function formatEventDate(Event $event): string
    {
        $start = $event->date->format('d/m/Y');
        if ($event->isMultiDay()) {
            return $start . ' – ' . $event->end_date->format('d/m/Y');
        }

        return $start;
    }

    private static function formatEventTime(Event $event): string
    {
        return substr($event->start_time, 0, 5) . '–' . substr($event->end_time, 0, 5);
    }

    private static function roleDisplayName(?string $role): string
    {
        return match ($role) {
            'admin' => 'Admin',
            'pic' => 'PIC Ruang',
            'guru' => 'Guru',
            'organisasi' => 'Organisasi',
            default => 'Pengguna',
        };
    }
}
