@extends('layouts.app')
@section('content')
<h1>Dodaj Pet</h1>
<form method="post" action="{{ route('pets.store') }}">
  @csrf
  @include('pets._form')
  <button type="submit">Zapisz</button>
</form>
@endsection
