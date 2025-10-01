@extends('layouts.app')
@section('content')
@if(isset($error))<p class="error">{{ $error }}</p>@endif
@if(empty($pet))
  <p>Brak danych</p>
@else
  <h1>Pet #{{ $pet['id'] ?? '—' }}</h1>
  <p><strong>Nazwa:</strong> {{ $pet['name'] ?? '—' }}</p>
  <p><strong>Status/Typ:</strong> {{ $pet['status'] ?? $pet['type'] ?? '—' }}</p>

  <p><strong>Tagi:</strong>
    @if(!empty($pet['tags']))
      {{ implode(', ', array_map(fn($t)=>$t['name'] ?? '', $pet['tags'])) }}
    @else — @endif
  </p>

  <p><strong>Kategoria:</strong> {{ $pet['category']['name'] ?? '—' }}</p>

  <p><strong>Zdjęcia:</strong>
    @if(!empty($pet['photoUrls']))
      <ul>
        @foreach($pet['photoUrls'] as $u)
          <li><a href="{{ $u }}" target="_blank" rel="noopener">{{ $u }}</a></li>
        @endforeach
      </ul>
    @else — @endif
  </p>

  @if(isset($pet['id']))
    <p><a href="{{ route('pets.edit', $pet['id']) }}">Edytuj</a></p>
  @endif
@endif
@endsection
