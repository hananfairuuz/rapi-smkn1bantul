@extends('layouts.app')
@section('content')
<div class="page-heading"><div><h1>{{ $creating ? 'Tambah Pengguna' : 'Ubah Pengguna' }}</h1><p>{{ $creating ? 'Buat akun email baru untuk pengguna sistem.' : 'Peran PIC dapat ditetapkan lalu dihubungkan ke ruangan pada menu Kelola Ruangan.' }}</p></div></div>
@if($creating)
    <div style="margin-bottom: 1.25rem; padding: 0.85rem 1.15rem; background: #f0f7ff; border: 1px solid #bae0ff; border-radius: 8px; color: #0050b3; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
        <div>
            <strong>Ingin menambah banyak pengguna sekaligus?</strong>
            <span style="font-size: 0.9em; opacity: 0.9;"> Anda dapat mengunggah file Excel (.xlsx), Word (.docx), atau CSV (.csv).</span>
        </div>
        <a href="{{ route('users.import') }}" class="btn secondary" style="padding: 6px 14px; font-size: 0.85rem; border-color: #91caff;">📥 Import dari Dokumen</a>
    </div>
@endif
<form class="card form-card" method="post" action="{{ $creating ? route('users.store') : route('users.update', $user) }}">@csrf @unless($creating) @method('put') @endunless
    <label for="name">Nama lengkap</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required>
    <label for="email">Email akun (untuk masuk)</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
    @if($creating)
        <label for="password">Kata sandi untuk login email</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
        <label for="password_confirmation">Konfirmasi kata sandi</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
    @endif
    <label for="whatsapp">Nomor telepon / WhatsApp</label><input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}" inputmode="tel" autocomplete="tel" pattern="\+?[0-9]{8,20}" placeholder="62812..." required>
    <label for="role_id">Peran</label><select id="role_id" name="role_id" required><option value="">Pilih peran</option>@foreach($roles as $role)<option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ match($role->name) {'admin'=>'Waka / Admin','pic'=>'PIC Ruang','guru'=>'Guru','organisasi'=>'Organisasi',default=>$role->name} }}</option>@endforeach</select>
    <p class="muted">Kata sandi ini dibuat khusus untuk login menggunakan email dan kata sandi, bukan kata sandi akun Google. Login Google tetap menggunakan akun Google masing-masing.</p>
    <div class="actions"><button>{{ $creating ? 'Tambah Pengguna' : 'Simpan Perubahan' }}</button><a class="btn secondary" href="{{ route('users.index') }}">Batal</a></div>
</form>
@endsection
