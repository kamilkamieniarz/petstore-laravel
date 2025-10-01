@extends('layouts.app')

@section('content')
<h1>Pets</h1>

<form id="filterForm" method="get" action="{{ route('pets.index') }}" style="margin:12px 0; display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
  <label style="display:inline-block">
    Status<br>
    <select name="status" id="statusSelect">
      <option value="all" @selected(($status ?? 'all')==='all')>Wszystkie</option>
      @foreach(['available','pending','sold'] as $s)
        <option value="{{ $s }}" @selected(($status ?? 'all')===$s)>{{ ucfirst($s) }}</option>
      @endforeach
    </select>
  </label>

  <label style="display:inline-block">
    Szukaj (nazwa lub ID)<br>
    <input type="text" id="searchInput" placeholder="np. rex lub 12345" autocomplete="off">
  </label>

  <noscript>
    <button type="submit">Filtruj (bez JS)</button>
  </noscript>
</form>


<div id="searchInfo" style="margin:6px 0;"></div>

<table>
  <colgroup>
    <col style="width:140px">
    <col>
    <col>
    <col style="width:220px">
  </colgroup>
  <thead>
    <tr><th style="width:120px">ID</th><th>Nazwa</th><th>Typ/Status</th><th style="width:200px">Akcje</th></tr>
  </thead>
  <tbody id="petsTableBody">
  @forelse($pets as $p)
    <tr>
      <td>{{ $p['id'] ?? '—' }}</td>
      <td>{{ $p['name'] ?? '—' }}</td>
      <td>{{ $p['type'] ?? $p['status'] ?? '—' }}</td>
      <td>
        @if(isset($p['id']))
          <a href="{{ route('pets.show', $p['id']) }}">Pokaż</a>
          <a href="{{ route('pets.edit', $p['id']) }}">Edytuj</a>
          <form action="{{ route('pets.destroy', $p['id']) }}" method="post" style="display:inline">
            @csrf @method('DELETE')
            <button onclick="return confirm('Usunąć?')">Usuń</button>
          </form>
        @endif
      </td>
    </tr>
  @empty
    <tr><td colspan="4">Brak danych</td></tr>
  @endforelse
  </tbody>
</table>

<script>
(function() {
  const input  = document.getElementById('searchInput');
  const status = document.getElementById('statusSelect');
  const tbody  = document.getElementById('petsTableBody');
  const info   = document.getElementById('searchInfo');

  let timer = null;

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }

  function render(items) {
    if (!Array.isArray(items) || items.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4">Brak danych</td></tr>';
      return;
    }

    const rows = [];
    for (const raw of items) {
      const p = raw || {};
      const id   = (p.id ?? '');
      const name = (p.name ?? '—');
      const typ  = (p.type ?? p.status ?? '—');

      const safeId   = esc(String(id));
      const safeName = esc(String(name));
      const safeTyp  = esc(String(typ));

      const showUrl = id ? `/pets/${encodeURIComponent(id)}` : '#';
      const editUrl = id ? `/pets/${encodeURIComponent(id)}/edit` : '#';

      rows.push(`
        <tr>
          <td>${safeId || '—'}</td>
          <td>${safeName}</td>
          <td>${safeTyp}</td>
          <td>
            ${id ? `<a href="${showUrl}">Pokaż</a> <a href="${editUrl}">Edytuj</a>` : ''}
          </td>
        </tr>
      `);
    }
    tbody.innerHTML = rows.join('');
  }

  async function searchNow() {
    const term = input.value.trim();
    const st   = status.value || 'all';
    const url  = new URL(`{{ route('pets.search') }}`, window.location.origin);
    url.searchParams.set('status', st);
    if (term) url.searchParams.set('term', term);

    info.textContent = 'Szukam…';
    try {
      const resp = await fetch(url.toString(), { headers: { 'Accept': 'application/json', 'Cache-Control': 'no-store' } });
      if (!resp.ok) throw new Error('HTTP ' + resp.status);
      const data = await resp.json();
      render(data.items || []);
      info.textContent = term ? `Wyników: ${data.count || 0}` : '';
    } catch (e) {
      info.textContent = 'Błąd wyszukiwania';
    }
  }

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      searchNow();
    }
  });

  input.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(searchNow, 250);
  });

  status.addEventListener('change', () => {
    clearTimeout(timer);
    input.value = '';
    searchNow();
  });
})();
</script>

@endsection
