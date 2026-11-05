@extends('layouts.app')

@section('content')
<div class="row users-heading">
    <div>
        <h1>Pengguna &amp; Peran</h1>
        <p class="muted">Kelola pengguna berdasarkan kelompok perannya.</p>
    </div>
    <a class="btn" href="{{ route('users.create') }}">+ Tambah Pengguna</a>
</div>

<form method="get" action="{{ route('users.index') }}" class="card user-search">
    <label for="user-search">Cari pengguna</label>
    <div class="user-search-controls">
        <input id="user-search" type="search" name="search" value="{{ $search }}" placeholder="Nama, email, WhatsApp, atau peran">
        <button type="submit">Cari</button>
        @if($search !== '')
            <a class="btn secondary" href="{{ route('users.index') }}">Hapus pencarian</a>
        @endif
    </div>
</form>

@forelse($roleGroups as $group)
    <section class="card user-group">
        <header class="user-group-header">
            <div>
                <h2>{{ $group['label'] }}</h2>
                <p class="muted">Daftar akun dengan peran {{ strtolower($group['label']) }}.</p>
            </div>
            <span class="badge approved">{{ $group['users']->count() }} pengguna</span>
        </header>
        <div class="table-card">
            <table>
                <thead>
                    <tr><th>Nama</th><th>Email</th><th>WhatsApp</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach($group['users'] as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->whatsapp ?: '—' }}</td>
                            <td>
                                <div class="user-actions">
                                    <a class="btn warn" href="{{ route('users.edit', $user) }}">Ubah</a>
                                    <form method="post" action="{{ route('users.destroy', $user) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="danger" onclick="return confirm('Hapus akun ini? Akun yang masih memiliki data terkait tidak dapat dihapus.')">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@empty
    <div class="card empty-state">{{ $search !== '' ? 'Tidak ada pengguna yang cocok dengan pencarian.' : 'Belum ada pengguna.' }}</div>
@endforelse
@endsection
