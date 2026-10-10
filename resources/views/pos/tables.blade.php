@extends('pos.layout')

@section('content')
@php $role = session('pos_employee')->Role; @endphp
<div class="screen">

    <div class="page-header">
        <div class="header-left">
            <a href="{{ route('dashboard') }}" class="btn-back">‹</a>
            <div>
                <div class="header-name">Table Status</div>
                <div class="header-greeting">{{ $activeTables->count() }} occupied · {{ 20 - $activeTables->count() }} free</div>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div style="display:flex;gap:16px;padding:10px 16px;font-size:.8rem;font-weight:700">
        <span style="display:flex;align-items:center;gap:6px">
            <span style="width:14px;height:14px;border-radius:3px;background:var(--card);border:2px solid var(--border);display:inline-block"></span>
            Free
        </span>
        <span style="display:flex;align-items:center;gap:6px">
            <span style="width:14px;height:14px;border-radius:3px;background:rgba(231,76,60,.15);border:2px solid rgba(231,76,60,.4);display:inline-block"></span>
            Occupied
        </span>
    </div>

    {{-- Table grid — view only for cashier --}}
    <div class="table-select-row">
        <div class="table-grid">
            @foreach(range(1, 50) as $t)
            @php
                $key    = 'Table ' . $t;
                $active = $activeTables->get($key);
            @endphp
            <div class="table-tile {{ $active ? 'table-tile--occupied' : 'table-tile--free' }}">
                <span class="table-tile-num">{{ $t }}</span>
                <span class="table-tile-status">{{ $active ? 'Occupied' : 'Free' }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
