<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\SheetMusic;
use App\Utils\XlsxReader;

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
        $search = trim($request->query('search'));
        $genre = $request->query('genre');
        $sort = $request->query('sort', 'newest');
        $page = max(1, (int) $request->query('page', '1'));
        $pageSize = max(1, min(100, (int) $request->query('page_size', '8')));

        $result = $this->model->findPaginated($search, $genre, $sort, $page, $pageSize);
        $totalPages = max(1, (int) ceil($result['total'] / $pageSize));

        return Response::json([
            'data' => $result['rows'],
            'meta' => [
                'total' => $result['total'],
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => $totalPages,
            ],
            'counts' => ['genres' => $this->model->genreCounts()],
            'stats' => $this->model->aggregate(),
        ]);
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

    /**
     * Import sheet music records from an uploaded `.xlsx` workbook.
     *
     * Expects a multipart field named `file`. The first row must be a header
     * row. `id` is always auto-incremented by the database.
     */
    public function importExcel(Request $request): Response
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
            return Response::json(['error' => 'No file provided.'], 422);
        }

        $file = $_FILES['file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return Response::json(['error' => 'File upload failed.'], 422);
        }

        if ($file['size'] > self::IMPORT_MAX_BYTES) {
            return Response::json(['error' => 'File must be 10 MB or smaller.'], 422);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'xlsx') {
            return Response::json(['error' => 'Only .xlsx files are supported.'], 422);
        }

        try {
            $matrix = XlsxReader::parse($file['tmp_name']);
        } catch (\Throwable) {
            return Response::json(
                ['error' => 'Could not read the Excel file. Make sure it is a valid .xlsx document.'],
                422
            );
        }

        if ($matrix === []) {
            return Response::json(['error' => 'The uploaded file is empty.'], 422);
        }

        $mapping = $this->buildHeaderMapping($matrix[0]);
        if ($mapping === null) {
            return Response::json(
                ['error' => 'The first row must contain column headers (location, shelf_id, title, composer, category, ...).'],
                422
            );
        }

        $errors = [];
        foreach ($mapping['missing'] as $field) {
            $errors[] = [
                'row' => 1,
                'field' => $field,
                'message' => ucfirst(str_replace('_', ' ', $field)) . ' column is missing from the header row.',
            ];
        }

        $records = [];
        for ($i = 1, $count = count($matrix); $i < $count; $i++) {
            $rowNum = $i + 1; // 1-based, header row included
            $rowErrors = $this->mapImportRow($matrix[$i], $mapping['columns'], $rowNum, $record);

            if ($rowErrors !== []) {
                foreach ($rowErrors as $error) {
                    $errors[] = $error;
                }
                continue;
            }

            if ($record !== null) {
                $records[] = $record;
            }
        }

        if ($errors !== []) {
            return Response::json([
                'error' => 'Import failed. Fix the highlighted rows and try again.',
                'imported' => 0,
                'errors' => $errors,
            ], 422);
        }

        if ($records === []) {
            return Response::json(['error' => 'The file contains no data rows to import.'], 422);
        }

        try {
            $imported = $this->model->createMany($records);
        } catch (\Throwable) {
            return Response::json(['error' => 'Could not save the records to the database.'], 500);
        }

        return Response::json([
            'message' => "Imported {$imported} sheet music records.",
            'imported' => $imported,
        ], 201);
    }

    private const IMPORT_MAX_BYTES = 10 * 1024 * 1024; // 10 MB

    private const IMPORT_REQUIRED = ['title'];

    private const IMPORT_OPTIONAL = ['location', 'shelf_id', 'subtitle', 'composer', 'arranger', 'genre', 'category', 'publisher'];

    private const HEADER_ALIASES = [
        'location' => 'location',
        'shelf' => 'shelf_id',
        'shelfid' => 'shelf_id',
        'shelfno' => 'shelf_id',
        'shelfnumber' => 'shelf_id',
        'title' => 'title',
        'subtitle' => 'subtitle',
        'composer' => 'composer',
        'arranger' => 'arranger',
        'genre' => 'genre',
        'category' => 'category',
        'publisher' => 'publisher',
        'year' => 'year',
        'scoreimg' => 'score_img',
        'scoreimage' => 'score_img',
    ];

    private const MAX_LENGTH = [
        'location' => 255,
        'shelf_id' => 80,
        'title' => 255,
        'subtitle' => 255,
        'composer' => 255,
        'arranger' => 255,
        'genre' => 80,
        'category' => 80,
        'publisher' => 255,
    ];

    /**
     * Map header cells to database fields. Returns null when the header row is
     * unusable, otherwise the field => column mapping plus any required fields
     * with no matching column.
     *
     * @param list<string|null> $headerRow
     * @return array{columns: array<string, int>, missing: list<string>}|null
     */
    private function buildHeaderMapping(array $headerRow): ?array
    {
        $columns = [];

        foreach ($headerRow as $index => $cell) {
            if ($cell === null || trim($cell) === '') {
                continue;
            }

            $normalized = self::normalizeHeader($cell);
            $field = self::HEADER_ALIASES[$normalized] ?? null;

            if ($field !== null && !isset($columns[$field])) {
                $columns[$field] = $index;
            }
        }

        if ($columns === []) {
            return null;
        }

        $missing = [];
        foreach (self::IMPORT_REQUIRED as $field) {
            if (!isset($columns[$field])) {
                $missing[] = $field;
            }
        }

        return ['columns' => $columns, 'missing' => $missing];
    }

    /**
     * Turn one spreadsheet row into a clean insert record.
     *
     * @param list<string|null>       $row
     * @param array<string, int>      $columns
     * @param list<array<string, mixed>> $errors collects validation problems
     * @return array<string, mixed>|null clean record, or null for fully empty rows
     */
    private function mapImportRow(array $row, array $columns, int $rowNum, ?array &$record): array
    {
        $errors = [];

        $hasData = false;
        foreach ($columns as $index) {
            if (isset($row[$index]) && trim((string) $row[$index]) !== '') {
                $hasData = true;
                break;
            }
        }

        if (!$hasData) {
            $record = null;
            return $errors;
        }

        $cellAt = static function (string $field) use ($row, $columns): ?string {
            $index = $columns[$field] ?? null;
            if ($index === null || !isset($row[$index]) || trim($row[$index]) === '') {
                return null;
            }
            return trim((string) $row[$index]);
        };

        $clean = [];
        foreach (self::IMPORT_REQUIRED as $field) {
            $value = $cellAt($field);
            if ($value === null) {
                $errors[] = [
                    'row' => $rowNum,
                    'field' => $field,
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required.',
                ];
                $clean[$field] = null;
            } else {
                $clean[$field] = $value;
                $lengthError = $this->lengthError($field, $value);
                if ($lengthError !== null) {
                    $errors[] = ['row' => $rowNum, 'field' => $field, 'message' => $lengthError];
                }
            }
        }

        foreach (self::IMPORT_OPTIONAL as $field) {
            $value = $cellAt($field);
            $clean[$field] = $value;
            if ($value !== null) {
                $lengthError = $this->lengthError($field, $value);
                if ($lengthError !== null) {
                    $errors[] = ['row' => $rowNum, 'field' => $field, 'message' => $lengthError];
                }
            }
        }

        $year = $cellAt('year');
        if ($year === null) {
            $clean['year'] = (int) date('Y');
        } elseif (!is_numeric($year) || (int) $year < 500 || (int) $year > (int) date('Y')) {
            $errors[] = ['row' => $rowNum, 'field' => 'year', 'message' => 'Please enter a valid year.'];
            $clean['year'] = (int) date('Y');
        } else {
            $clean['year'] = (int) $year;
        }

        $record = $clean;
        return $errors;
    }

    private function lengthError(string $field, string $value): ?string
    {
        $max = self::MAX_LENGTH[$field] ?? null;
        if ($max !== null && mb_strlen($value) > $max) {
            return ucfirst(str_replace('_', ' ', $field)) . ' must be ' . $max . ' characters or fewer.';
        }
        return null;
    }

    private static function normalizeHeader(string $header): string
    {
        return strtolower((string) preg_replace('/[\s_\-]+/', '', $header));
    }

    private const REQUIRED = ['title', 'year', 'genre'];
    private const ALL = ['title', 'subtitle', 'composer', 'arranger', 'year', 'genre', 'score_img', 'location', 'shelf_id', 'category', 'publisher'];

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
            if ($field === 'composer' || $field === 'subtitle' || $field === 'arranger' || $field === 'score_img'
                || $field === 'location' || $field === 'shelf_id'
                || $field === 'category' || $field === 'publisher') {
                $result[$field] = $trim($data[$field]) !== '' ? $trim($data[$field]) : null;
            } else {
                $result[$field] = $trim($data[$field]);
            }
        }

        return $result;
    }
}