@extends('layouts.app')
@section('content')
@if(isset($error))<p class="error">{{ $error }}</p>@endif
@if(empty($pet))
  <p>Brak danych</p>
@else
  <h1>Edytuj Pet #{{ $pet['id'] ?? '—' }}</h1>
  <form method="post" action="{{ route('pets.update', $pet['id'] ?? 0) }}">
    @csrf @method('PUT')
    @include('pets._form', ['pet' => $pet])
    <button type="submit">Zapisz</button>
  </form>
@endif
@endsection
