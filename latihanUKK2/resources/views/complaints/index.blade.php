@extends('layouts.app')

@section('content')
<div class="row">
    <div>
        <h1>Pengaduan Fasilitas</h1>
        <p class="muted">PIC hanya dapat menangani pengaduan ruangan yang ditugaskan kepadanya. Setelah 24 jam belum diperbaiki, admin dapat membuka pesan WhatsApp siap kirim untuk PIC.</p>
    </div>
    @if(in_array(auth()->user()->role?->name, ['admin', 'guru', 'organisasi']))
        <a class="btn" href="{{ route('complaints.create') }}">+ Buat Pengaduan</a>
    @endif
</div>

@forelse($complaints as $complaint)
    <article class="card">
        <div class="row">
            <div>
                <h3>{{ $complaint->facility_name }}</h3>
                <p><strong>{{ $complaint->room->name }}</strong> · Pelapor: {{ $complaint->reporter->name }}</p>
                @if($complaint->event)
                    <p class="muted">Terkait agenda: {{ $complaint->event->title }} · {{ $complaint->event->date->format('d M Y') }}</p>
                @endif
                <p><strong>PIC ruangan:</strong> {{ $complaint->room->pic?->name ?? 'Belum ditetapkan' }}</p>
                <p class="muted">{{ $complaint->description }}</p>
                @if($complaint->approval_status === 'pending')
                    <p>Status persetujuan: <span class="badge pending">MENUNGGU PERSETUJUAN PIC</span></p>
                @elseif($complaint->approval_status === 'rejected')
                    <p>Status persetujuan: <span class="badge rejected">DITOLAK PIC</span></p>
                @else
                    <p>Status persetujuan: <span class="badge approved">DISETUJUI PIC</span></p>
                @endif
                <p>Status: <span class="badge {{ $complaint->status_repair === 'Sudah Diperbaiki' ? 'approved' : ($complaint->status_repair === 'Menunggu Spare Part' ? 'pending' : 'rejected') }}">{{ $complaint->status_repair }}</span></p>
                @if($complaint->status_repair === 'Sudah Diperbaiki')
                    <p><strong>Keterangan perbaikan:</strong> {{ $complaint->repair_notes ?: 'Barang sudah diperbaiki.' }}</p>
                @elseif($complaint->repair_notes)
                    <p>Catatan PIC: {{ $complaint->repair_notes }}</p>
                @endif
            </div>
            @if($complaint->photo_path)
                <a href="{{ route('complaints.photo', $complaint) }}" target="_blank"><img src="{{ route('complaints.photo', $complaint) }}" alt="Foto bukti" style="max-width:120px;max-height:100px"></a>
            @endif
        </div>
        <div class="actions">
            @if(auth()->user()->role?->name === 'pic' && $complaint->approval_status === 'pending')
                <form method="post" class="inline" action="{{ route('complaints.review', $complaint) }}">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="approval_status" value="approved">
                    <button type="submit">Setujui Pengaduan</button>
                </form>
                <form method="post" class="inline" action="{{ route('complaints.review', $complaint) }}">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="approval_status" value="rejected">
                    <button class="danger" type="submit">Tolak Pengaduan</button>
                </form>
            @elseif(auth()->user()->role?->name === 'pic' && $complaint->approval_status === 'approved')
                <a class="btn warn" href="{{ route('complaints.edit', $complaint) }}">Perbarui</a>
            @endif
            @if(auth()->user()->role?->name === 'admin' && $complaint->status_repair === 'Belum Diperbaiki' && $complaint->created_at->lte($whatsappDueAt) && $complaint->room->pic?->whatsapp)
                <a class="btn" target="_blank" href="https://wa.me/{{ preg_replace('/\D/', '', $complaint->room->pic->whatsapp) }}?text={{ urlencode('Peringatan RAPI: pengaduan '.$complaint->facility_name.' di '.$complaint->room->name.' belum diperbaiki lebih dari 24 jam.') }}">WhatsApp PIC</a>
            @elseif(auth()->user()->role?->name === 'admin' && $complaint->status_repair === 'Belum Diperbaiki')
                <span class="muted">Eskalasi WhatsApp tersedia setelah 24 jam.</span>
            @endif
            @if(auth()->user()->role?->name === 'admin')
                <form method="post" class="inline" action="{{ route('complaints.destroy', $complaint) }}">@csrf @method('delete')<button class="danger" onclick="return confirm('Hapus pengaduan ini?')">Hapus</button></form>
            @endif
        </div>
    </article>
@empty
    <div class="card">Belum ada pengaduan.</div>
@endforelse
@endsection
