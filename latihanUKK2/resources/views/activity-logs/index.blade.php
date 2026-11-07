@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem - RAPI-Sekolah')

@section('content')
    <div class="row" style="margin-bottom: 20px;">
        <div>
            <h1>Log Aktivitas Sistem</h1>
            <p class="muted">Rekam jejak audit trail seluruh tindakan dan aktivitas penting dalam sistem.</p>
        </div>
    </div>

    <form method="get" action="{{ route('activity-logs.index') }}" class="card actions" style="margin-bottom: 20px;">
        <label for="log-search">Cari aktivitas, aksi, atau pengguna</label>
        <input id="log-search" type="search" name="search" value="{{ $search }}" placeholder="Contoh: Persetujuan, Ruangan, Nama Admin...">
        <button type="submit">Cari Log</button>
        @if($search !== '')
            <a class="btn secondary" href="{{ route('activity-logs.index') }}">Reset</a>
        @endif
    </form>

    <div class="card table-card" style="padding: 0; overflow: hidden;">
        <table>
            <thead>
                <tr>
                    <th style="width: 170px;">Waktu</th>
                    <th style="width: 200px;">Pengguna</th>
                    <th style="width: 180px;">Aksi</th>
                    <th>Keterangan</th>
                    <th style="width: 130px;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="font-size: 12.5px; color: var(--muted); white-space: nowrap;">
                            <strong>{{ $log->created_at->format('d/m/Y') }}</strong><br>
                            {{ $log->created_at->format('H:i:s') }} WIB
                        </td>
                        <td>
                            @if($log->user)
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="avatar" style="width: 28px; height: 28px; font-size: 12px;">{{ strtoupper(substr($log->user->name, 0, 1)) }}</span>
                                    <div>
                                        <strong style="font-size: 13px;">{{ $log->user->name }}</strong>
                                        <small class="muted" style="display: block; font-size: 11px;">{{ match($log->user->role?->name) {'admin' => 'Admin', 'pic' => 'PIC', 'guru' => 'Guru', 'organisasi' => 'Organisasi', default => 'User'} }}</small>
                                    </div>
                                </div>
                            @else
                                <span class="muted" style="font-size: 12px;">Sistem / Anonim</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge" style="background: #e2e8f0; color: #334155; font-size: 11.5px; padding: 4px 8px;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td style="font-size: 13px; line-height: 1.45;">
                            {{ $log->description }}
                        </td>
                        <td style="font-size: 12px; color: var(--muted); font-family: monospace;">
                            {{ $log->ip_address ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 32px;" class="muted">
                            Belum ada catatan aktivitas yang tercatat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div style="margin-top: 18px;">
            {{ $logs->links() }}
        </div>
    @endif
@endsection

