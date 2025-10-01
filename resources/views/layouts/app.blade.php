<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
  <meta http-equiv="Pragma" content="no-cache">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Petstore CRUD (demo)</title>
  <style>
    body{font-family: system-ui, sans-serif; padding:20px; max-width:900px; margin:0 auto}
    nav a{margin-right:8px}
    table{width:100%; border-collapse: collapse}
    th,td{border:1px solid #ddd; padding:6px}
    label{display:block; margin-top:10px}
    input,select{width:100%; padding:8px; margin-top:4px}
    .error{color:#b00020}
    .success{color:#007a1c}
    table{width:100%; border-collapse:collapse; table-layout:fixed}
    thead th:nth-child(1){width:140px}
    thead th:nth-child(4){width:220px}
    th,td{border:1px solid #ddd; padding:6px; vertical-align:top; word-break:break-word; overflow-wrap:anywhere}
  </style>
</head>
<body>
  <nav>
    <a href="{{ route('pets.index') }}">Lista</a>
    <a href="{{ route('pets.create') }}">Dodaj</a>
  </nav>
  @if(session('success')) <p class="success">{{ session('success') }}</p> @endif
  @if($errors->any()) <p class="error">{{ $errors->first() }}</p> @endif
  @yield('content')
</body>
</html>
