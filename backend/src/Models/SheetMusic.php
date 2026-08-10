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

    private const ORDER_BY = [
        'newest' => 'created_at DESC',
        'oldest' => 'created_at ASC',
        'year-asc' => 'year ASC, created_at ASC',
        'year-desc' => 'year DESC, created_at ASC',
        'title' => 'title ASC',
        'composer' => 'composer ASC',
    ];

    /**
     * Return one page of results matching search/filter/sort, plus the total
     * number of matching rows (for pagination meta).
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function findPaginated(string $search, string $genre, string $sort, int $page, int $pageSize): array
    {
        $where = [];
        $params = [];

        if ($genre !== '') {
            $where[] = 'genre = :genre';
            $params['genre'] = $genre;
        }

        if ($search !== '') {
            $where[] = "CONCAT(COALESCE(title, ''), ' ', COALESCE(subtitle, ''), ' ', COALESCE(composer, ''), ' ', COALESCE(arranger, '')) LIKE :q";
            $params['q'] = '%' . $search . '%';
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM sheet_music' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $orderBy = self::ORDER_BY[$sort] ?? self::ORDER_BY['newest'];
        $offset = max(0, ($page - 1) * $pageSize);
        $limit = max(1, min(100, $pageSize));

        $stmt = $this->db->prepare(
            'SELECT * FROM sheet_music' . $whereSql . " ORDER BY {$orderBy} LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();

        return [
            'rows' => array_map(fn (array $row) => $this->mapRow($row), $rows),
            'total' => $total,
        ];
    }

    /** @return array<string, int> genre => number of pieces */
    public function genreCounts(): array
    {
        $rows = $this->db
            ->query('SELECT genre, COUNT(*) AS cnt FROM sheet_music GROUP BY genre')
            ->fetchAll();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['genre']] = (int) $row['cnt'];
        }

        return $counts;
    }

    /** @return array<string, mixed> whole-collection aggregate stats */
    public function aggregate(): array
    {
        $row = $this->db
            ->query('SELECT COUNT(*) AS total, COUNT(DISTINCT composer) AS composers, COUNT(DISTINCT genre) AS eras, MIN(year) AS min_year, MAX(year) AS max_year FROM sheet_music')
            ->fetch();

        return [
            'total' => (int) $row['total'],
            'uniqueComposers' => (int) $row['composers'],
            'erasCovered' => (int) $row['eras'],
            'minYear' => $row['min_year'] !== null ? (int) $row['min_year'] : null,
            'maxYear' => $row['max_year'] !== null ? (int) $row['max_year'] : null,
        ];
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
            'INSERT INTO sheet_music (title, subtitle, composer, arranger, year, genre, score_img, location, shelf_id, category, publisher)
             VALUES (:title, :subtitle, :composer, :arranger, :year, :genre, :score_img, :location, :shelf_id, :category, :publisher)'
        );

        $stmt->execute([
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'composer' => $data['composer'] ?? null,
            'arranger' => $data['arranger'] ?? null,
            'year' => $data['year'],
            'genre' => $data['genre'],
            'score_img' => $data['score_img'] ?? null,
            'location' => $data['location'] ?? null,
            'shelf_id' => $data['shelf_id'] ?? null,
            'category' => $data['category'] ?? null,
            'publisher' => $data['publisher'] ?? null,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    /**
     * Insert many records inside a single transaction.
     *
     * @param list<array<string, mixed>> $rows each row must already be validated/cleaned
     * @return int number of inserted rows
     */
    public function createMany(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO sheet_music (title, subtitle, composer, arranger, year, genre, score_img, location, shelf_id, category, publisher)
             VALUES (:title, :subtitle, :composer, :arranger, :year, :genre, :score_img, :location, :shelf_id, :category, :publisher)'
        );

        $this->db->beginTransaction();
        try {
            foreach ($rows as $data) {
                $stmt->execute([
                    'title' => $data['title'],
                    'subtitle' => $data['subtitle'] ?? null,
                    'composer' => $data['composer'] ?? null,
                    'arranger' => $data['arranger'] ?? null,
                    'year' => $data['year'],
                    'genre' => $data['genre'],
                    'score_img' => $data['score_img'] ?? null,
                    'location' => $data['location'] ?? null,
                    'shelf_id' => $data['shelf_id'] ?? null,
                    'category' => $data['category'] ?? null,
                    'publisher' => $data['publisher'] ?? null,
                ]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return count($rows);
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
                    score_img = :score_img,
                    location = :location,
                    shelf_id = :shelf_id,
                    category = :category,
                    publisher = :publisher
              WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'composer' => $data['composer'] ?? null,
            'arranger' => $data['arranger'] ?? null,
            'year' => $data['year'],
            'genre' => $data['genre'],
            'score_img' => $data['score_img'] ?? null,
            'location' => $data['location'] ?? null,
            'shelf_id' => $data['shelf_id'] ?? null,
            'category' => $data['category'] ?? null,
            'publisher' => $data['publisher'] ?? null,
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
            'location' => $row['location'],
            'shelfId' => $row['shelf_id'],
            'title' => $row['title'],
            'subtitle' => $row['subtitle'],
            'composer' => $row['composer'],
            'arranger' => $row['arranger'],
            'year' => (int) $row['year'],
            'genre' => $row['genre'],
            'category' => $row['category'],
            'publisher' => $row['publisher'],
            'scoreImg' => $row['score_img'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }
}