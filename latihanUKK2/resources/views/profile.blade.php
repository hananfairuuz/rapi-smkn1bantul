@extends('layouts.app')
@section('content')
<div class="page-heading"><div><h1>Profil Saya</h1><p>Perbarui informasi akun Anda.</p></div></div>
<form class="card form-card" method="post" action="{{ route('profile.update') }}">@csrf @method('put')
    <label for="name">Nama lengkap</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required>
    <label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
    <label for="whatsapp">Nomor telepon / WhatsApp</label><input id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}" inputmode="tel" autocomplete="tel" pattern="\+?[0-9]{8,20}" placeholder="62812..." required>
    <p class="muted">Nomor telepon dapat diperbarui oleh semua peran melalui halaman Profil Saya.</p>
    <div class="actions"><button>Simpan Profil</button></div>
</form>
@endsection
