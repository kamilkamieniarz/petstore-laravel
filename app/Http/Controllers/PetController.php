<?php

namespace App\Http\Controllers;

use Illuminate\Validation\ValidationException;
use App\Http\Requests\StorePetRequest;
use App\Http\Requests\UpdatePetRequest;
use App\Services\ApiException;
use App\Services\PetstoreClient;

class PetController extends Controller
{
    public function __construct(private PetstoreClient $api) {}

    // LISTA  /pet/findByStatus
    public function index()
    {
        $status = request()->string('status', 'available')->toString();

        if ($status === 'all') {
            try {
                $pets = array_merge(
                    $this->api->findPetsByStatus(['available']),
                    $this->api->findPetsByStatus(['pending']),
                    $this->api->findPetsByStatus(['sold'])
                );
            } catch (\Throwable $e) {
                $pets = [];
            }
        } else {
            try {
                $pets = $this->api->findPetsByStatus([$status]);
            } catch (\Throwable $e) {
                $pets = [];
            }
        }


        $recent = collect(session('recent_pets', []))
            ->filter(fn($p) => ($p['status'] ?? null) === $status)
            ->values()
            ->all();

        $byId = [];
        foreach (array_merge($pets, $recent) as $p) {
            if (!isset($p['id'])) continue;
            $byId[$p['id']] = $p; // sesja nadpisze API
        }
        $merged = array_values($byId);

        return response()
            ->view('pets.index', ['pets' => $merged, 'status' => $status])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }


    public function create()
    {
        return view('pets.create');
    }

    // DODAWANIE /pet
    public function store(StorePetRequest $request)
    {
        $data = $request->validated();
        if (!array_key_exists('name', $data) || trim((string)$data['name']) === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Name is required']);
        }

        $payload = $this->formToPetPayload($data);
        $payload['id'] = $this->generateId();

        try {
            $this->api->addPet($payload);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['api' => 'Create failed. Try again.']);
        }

        $pet = $this->fetchPetEventually($payload['id']);
        if (empty($pet)) {
            $pet = $payload;
        }

        $recent = collect(session('recent_pets', []))
            ->reject(fn($p) => ($p['id'] ?? null) === ($pet['id'] ?? null))
            ->prepend($pet)
            ->take(50)
            ->values()
            ->all();
        session(['recent_pets' => $recent]);

        return redirect()->route('pets.show', $pet['id'])->with('success', 'Pet created');
    }


    // POKAZ  /pet/{id}
    public function show($id)
    {
        $id = (int) $id;

        $fromSession = collect(session('recent_pets', []))->firstWhere('id', $id);
        if ($fromSession) {
            return view('pets.show', ['pet' => $fromSession]);
        }

        try {
            $pet = $this->api->getPet($id);
            return view('pets.show', compact('pet'));
        } catch (\Throwable $e) {
            return view('pets.show', ['pet' => null, 'error' => 'Pet not found or API unavailable (id: '.$id.').']);
        }
    }


    // EDYCJA – formularz
    public function edit($id)
    {
        $id = (int) $id;

        $fromSession = collect(session('recent_pets', []))->firstWhere('id', $id);
        if ($fromSession) {
            return view('pets.edit', ['pet' => $fromSession]);
        }

        try {
            $pet = $this->api->getPet($id);
            return view('pets.edit', compact('pet'));
        } catch (\Throwable $e) {
            return redirect()
                ->route('pets.index')
                ->withErrors(['api' => 'Pet not found or API unavailable (id: '.$id.').']);
        }
    }


    // ZAPIS EDYCJI /pet
    public function update(UpdatePetRequest $request, $id)
    {
        $data = $request->validated();
        if (!array_key_exists('name', $data) || trim((string)$data['name']) === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Name is required']);
        }

        $payload = $this->formToPetPayload($data);
        $payload['id'] = (int) $id;

        try {
            $this->api->updatePet($payload);
            try {
                $pet = $this->api->getPet($id);
            } catch (\Throwable $e) {
                $pet = $payload;
            }
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'api' => 'Update failed: Pet not found or API unavailable (id: '.$id.').',
            ]);
        }

        $recent = collect(session('recent_pets', []))
            ->reject(fn($p) => ($p['id'] ?? null) === (int) $id)
            ->prepend($pet)
            ->take(50)
            ->values()
            ->all();
        session(['recent_pets' => $recent]);

        return redirect()->route('pets.show', $pet['id'] ?? $id)->with('success', 'Pet updated');
    }



    // USUWANIE /pet/{id}
    public function destroy($id)
    {
        try {
            $this->api->deletePet((int) $id);
        } catch (ApiException $e) {
            return back()->withErrors(['api' => $this->friendlyError($e)]);
        }

        return redirect()->route('pets.index')->with('success', 'Pet deleted');
    }

    private function fetchPetEventually(int $id, int $attempts = 4, int $delayMs = 250): array
    {
        for ($i = 0; $i < $attempts; $i++) {
            try {
                $pet = $this->api->getPet($id);
                if (!empty($pet)) {
                    return $pet;
                }
            } catch (\Throwable $e) {
                // czekamy i próbujemy ponownie
            }
            usleep($delayMs * 1000);
        }
        return [];
    }

    public function search()
    {
        $status = request()->string('status', 'available')->toString();
        if (!in_array($status, ['available','pending','sold'], true)) {
            $status = 'available';
        }

        $term = trim((string) request('term', ''));
        if (mb_strlen($term) > 80) {
            $term = mb_substr($term, 0, 80);
        }

        if ($status === 'all') {
            try {
                $pets = array_merge(
                    $this->api->findPetsByStatus(['available']),
                    $this->api->findPetsByStatus(['pending']),
                    $this->api->findPetsByStatus(['sold'])
                );
            } catch (\Throwable $e) {
                $pets = [];
            }
        } else {
            try {
                $pets = $this->api->findPetsByStatus([$status]);
            } catch (\Throwable $e) {
                $pets = [];
            }
        }

        $recent = collect(session('recent_pets', []))
            ->filter(fn($p) => ($p['status'] ?? null) === $status)
            ->values()
            ->all();

        $byId = [];
        foreach (array_merge($pets, $recent) as $p) {
            if (!isset($p['id'])) continue;
            $byId[$p['id']] = $p; // sesja nadpisze API
        }
        $merged = array_values($byId);

        if ($term !== '') {
            if (ctype_digit($term)) {
                $id = (int) $term;
                $merged = array_values(array_filter($merged, fn($p) => ($p['id'] ?? null) === $id));
            } else {
                $t = mb_strtolower($term);
                $merged = array_values(array_filter($merged, function ($p) use ($t) {
                    $name = mb_strtolower((string) ($p['name'] ?? ''));
                    // Jeżeli brak name, spróbujmy też po kategorii
                    $cat  = mb_strtolower((string) ($p['category']['name'] ?? ''));
                    return ( $name !== '' && mb_strpos($name, $t) !== false )
                        || ( $cat  !== '' && mb_strpos($cat,  $t) !== false );
                }));
            }
        }

        return response()->json([
            'items'  => $merged,
            'status' => $status,
            'count'  => count($merged),
        ]);
    }


    private function generateId(): int
    {
        return random_int(1_000_000, 2_000_000_000);
    }

    private function formToPetPayload(array $data): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $status = $data['status'] ?? 'available';

        $photoUrls = [];
        if (!empty($data['photo_urls'])) {
            $photoUrls = array_values(array_filter(array_map('trim', explode(',', (string) $data['photo_urls']))));
        }

        $tags = [];
        if (!empty($data['tags'])) {
            $tagsCsv = array_values(array_filter(array_map('trim', explode(',', (string) $data['tags']))));
            $tags = array_map(fn($t, $i) => ['id' => $i + 1, 'name' => $t], $tagsCsv, array_keys($tagsCsv));
        }

        $payload = [
            'name'      => $name,
            'status'    => $status,
            'photoUrls' => $photoUrls ?: ['https://example.com/placeholder.jpg'],
        ];

        if ($tags) {
            $payload['tags'] = $tags;
        }

        if (!empty($data['category'])) {
            $payload['category'] = ['id' => 1, 'name' => $data['category']];
        }

        return $payload;
    }


    private function friendlyError(ApiException $e): string
    {
        return match ($e->status) {
            400 => 'Invalid input. Please check your data.',
            404 => 'Pet not found.',
            405 => 'Method not allowed or malformed request.',
            415 => 'Unsupported media type.',
            429 => 'Too many requests. Try again later.',
            default => 'External API error ('.$e->status.').',
        };
    }
}
