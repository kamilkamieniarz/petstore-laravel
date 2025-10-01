@php($pet = $pet ?? [])
<label>Nazwa
  <input type="text" name="name" value="{{ old('name', $pet['name'] ?? '') }}" required>
</label>
<label>Status
  <select name="status">
    @foreach(['available','pending','sold'] as $s)
      <option value="{{ $s }}" @selected(old('status', $pet['status'] ?? 'available')===$s)>{{ ucfirst($s) }}</option>
    @endforeach
  </select>
</label>
<label>Photo URLs (CSV)
  <input type="text" name="photo_urls"
         value="{{ old('photo_urls', isset($pet['photoUrls']) ? implode(',', $pet['photoUrls']) : '') }}">
</label>

<label>Tagi (CSV)
  <input type="text" name="tags"
         value="{{ old('tags', isset($pet['tags']) ? implode(',', array_map(fn($t)=>$t['name'] ?? '', $pet['tags'])) : '') }}">
</label>

<label>Kategoria
  <input type="text" name="category"
         value="{{ old('category', $pet['category']['name'] ?? '') }}">
</label>

