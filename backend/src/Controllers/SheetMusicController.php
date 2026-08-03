<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\SheetMusic;

/**
 * HTTP handlers for the sheet music REST endpoints.
 */
final class SheetMusicController
{
    private SheetMusic $model;

    public function __construct(?SheetMusic $model = null)
    {
        $this->model = $model ?? new SheetMusic();
    }

    public function index(Request $request): Response
    {
        return Response::json($this->model->findAll());
    }

    public function show(Request $request, int $id): Response
    {
        $item = $this->model->findById($id);

        if ($item === null) {
            return Response::json(['error' => 'Sheet music not found'], 404);
        }

        return Response::json($item);
    }

    public function store(Request $request): Response
    {
        $data = $request->jsonBody();
        $errors = $this->validate($data, requireRequired: true);

        if ($errors !== []) {
            return Response::json(['error' => 'Validation failed', 'fields' => $errors], 422);
        }

        return Response::json($this->model->create($this->clean($data)), 201);
    }

    public function update(Request $request, int $id): Response
    {
        if (!$this->model->exists($id)) {
            return Response::json(['error' => 'Sheet music not found'], 404);
        }

        $data = $request->jsonBody();
        $errors = $this->validate($data, requireRequired: true);

        if ($errors !== []) {
            return Response::json(['error' => 'Validation failed', 'fields' => $errors], 422);
        }

        return Response::json($this->model->update($id, $this->clean($data)));
    }

    public function destroy(Request $request, int $id): Response
    {
        if (!$this->model->delete($id)) {
            return Response::json(['error' => 'Sheet music not found'], 404);
        }

        return Response::json(['message' => 'Deleted successfully']);
    }

    private const REQUIRED = ['title', 'composer', 'year', 'genre'];
    private const ALL = ['title', 'subtitle', 'composer', 'arranger', 'year', 'genre', 'score_img'];

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function validate(array $data, bool $requireRequired): array
    {
        $errors = [];

        foreach (self::REQUIRED as $field) {
            $present = isset($data[$field]) && trim((string) $data[$field]) !== '';
            if (!$present && $requireRequired) {
                $errors[$field] = ucfirst($field) . ' is required.';
            }
        }

        if ($requireRequired) {
            $this->validateYear($data, $errors);
        } elseif (isset($data['year']) && $data['year'] !== '') {
            $this->validateYear($data, $errors);
        }

        return $errors;
    }

    /** @param array<string, mixed> $data @param array<string, string> $errors */
    private function validateYear(array $data, array &$errors): void
    {
        $year = $data['year'] ?? '';
        if (!is_numeric($year) || (int) $year < 500 || (int) $year > (int) date('Y')) {
            $errors['year'] = 'Please enter a valid year.';
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function clean(array $data): array
    {
        $trim = static fn ($value) => is_string($value) ? trim($value) : $value;

        $result = [];
        foreach (self::ALL as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            if ($field === 'subtitle' || $field === 'arranger' || $field === 'score_img') {
                $result[$field] = $trim($data[$field]) !== '' ? $trim($data[$field]) : null;
            } else {
                $result[$field] = $trim($data[$field]);
            }
        }

        return $result;
    }
}