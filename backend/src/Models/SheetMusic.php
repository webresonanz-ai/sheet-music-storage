<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Data access layer for the sheet_music table.
 */
final class SheetMusic
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    /** @return list<array<string, mixed>> */
    public function findAll(): array
    {
        $rows = $this->db
            ->query('SELECT * FROM sheet_music ORDER BY created_at DESC')
            ->fetchAll();

        return array_map(fn (array $row) => $this->mapRow($row), $rows);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sheet_music WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sheet_music (title, subtitle, composer, arranger, year, genre, score_img)
             VALUES (:title, :subtitle, :composer, :arranger, :year, :genre, :score_img)'
        );

        $stmt->execute([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'composer' => $data['composer'],
            'arranger' => $data['arranger'] ?? null,
            'year' => $data['year'],
            'genre' => $data['genre'],
            'score_img' => $data['score_img'] ?? null,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(int $id, array $data): ?array
    {
        if (!$this->exists($id)) {
            return null;
        }

        $stmt = $this->db->prepare(
            'UPDATE sheet_music
                SET title = :title,
                    subtitle = :subtitle,
                    composer = :composer,
                    arranger = :arranger,
                    year = :year,
                    genre = :genre,
                    score_img = :score_img
              WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'composer' => $data['composer'],
            'arranger' => $data['arranger'] ?? null,
            'year' => $data['year'],
            'genre' => $data['genre'],
            'score_img' => $data['score_img'] ?? null,
        ]);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM sheet_music WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM sheet_music WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() !== false;
    }

    /**
     * Convert a database row to the JSON shape the frontend expects
     * (camelCase timestamps).
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'subtitle' => $row['subtitle'],
            'composer' => $row['composer'],
            'arranger' => $row['arranger'],
            'year' => (int) $row['year'],
            'genre' => $row['genre'],
            'scoreImg' => $row['score_img'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }
}