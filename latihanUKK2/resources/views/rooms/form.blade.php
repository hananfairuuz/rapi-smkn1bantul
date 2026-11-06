@extends('layouts.app')

@section('content')
    <div class="page-heading">
        <div>
            <h1>{{ $room->exists ? 'Edit' : 'Tambah' }} Ruangan</h1>
            <p class="muted">Kelola data fasilitas ruangan dan kapasitas tampung sekolah.</p>
        </div>
    </div>

    <form class="card form-card" method="post" action="{{ $room->exists ? route('rooms.update', $room) : route('rooms.store') }}">
        @csrf
        @if($room->exists)
            @method('put')
        @endif

        <div class="form-grid">
            <div class="form-field form-wide">
                <label for="name">Nama Ruangan <span style="color: #ef4444;">*</span></label>
                <input id="name" name="name" value="{{ old('name', $room->name) }}" placeholder="Contoh: Laboratorium Komputer 1, Aula Utama" required>
                @error('name')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label for="location">Lokasi</label>
                <input id="location" name="location" value="{{ old('location', $room->location) }}" placeholder="Contoh: Gedung A Lantai 2">
                @error('location')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field">
                <label for="capacity">Kapasitas Maksimal (Orang)</label>
                <input id="capacity" type="number" min="1" max="10000" name="capacity" value="{{ old('capacity', $room->capacity) }}" placeholder="Contoh: 40">
                <p class="muted">Jumlah kapasitas maksimal orang untuk ruangan ini.</p>
                @error('capacity')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field form-wide">
                <label for="description">Deskripsi / Fasilitas Ruangan</label>
                <textarea id="description" name="description" rows="3" placeholder="Fasilitas AC, Proyektor, 36 Unit PC, Audio Sound System...">{{ old('description', $room->description) }}</textarea>
                @error('description')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="form-field form-wide">
                <label for="pic_id">PIC Ruangan</label>
                <select id="pic_id" name="pic_id">
                    <option value="">-- Belum ditetapkan --</option>
                    @foreach($pics as $pic)
                        <option value="{{ $pic->id }}" @selected(old('pic_id', $room->pic_id) == $pic->id)>{{ $pic->name }} ({{ $pic->email }})</option>
                    @endforeach
                </select>
                @error('pic_id')<p class="error">{{ $message }}</p>@enderror
            </div>

            <div class="actions form-wide">
                <button type="submit">Simpan</button>
                <a class="btn" href="{{ route('rooms.index') }}">Batal</a>
            </div>
        </div>
    </form>
@endsection
