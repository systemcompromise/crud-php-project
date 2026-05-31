<?php
require_once __DIR__ . '/Database.php';

class Content {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll(string $status = '', string $search = ''): array {
        $sql    = "SELECT * FROM contents WHERE 1=1";
        $params = [];

        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        if ($search) {
            $sql .= " AND (title ILIKE :search OR body ILIKE :search OR author ILIKE :search)";
            $params['search'] = "%$search%";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM contents WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): array {
        $slug = $this->generateSlug($data['title']);

        $stmt = $this->db->prepare(
            "INSERT INTO contents (title, slug, body, category, status, author)
             VALUES (:title, :slug, :body, :category, :status, :author)
             RETURNING *"
        );
        $stmt->execute([
            'title'    => trim($data['title']),
            'slug'     => $slug,
            'body'     => trim($data['body']),
            'category' => trim($data['category'] ?? 'Umum'),
            'status'   => $data['status'] ?? 'draft',
            'author'   => trim($data['author']),
        ]);
        return $stmt->fetch();
    }

    public function update(int $id, array $data): ?array {
        $stmt = $this->db->prepare(
            "UPDATE contents
             SET title    = :title,
                 slug     = :slug,
                 body     = :body,
                 category = :category,
                 status   = :status,
                 author   = :author
             WHERE id = :id
             RETURNING *"
        );
        $stmt->execute([
            'id'       => $id,
            'title'    => trim($data['title']),
            'slug'     => $this->generateSlug($data['title'], $id),
            'body'     => trim($data['body']),
            'category' => trim($data['category'] ?? 'Umum'),
            'status'   => $data['status'] ?? 'draft',
            'author'   => trim($data['author']),
        ]);
        return $stmt->fetch() ?: null;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM contents WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function incrementViews(int $id): void {
        $this->db->prepare("UPDATE contents SET views = views + 1 WHERE id = :id")
                 ->execute(['id' => $id]);
    }

    public function getStats(): array {
        $stmt = $this->db->query(
            "SELECT
                COUNT(*)                                         AS total,
                COUNT(*) FILTER (WHERE status = 'published')    AS published,
                COUNT(*) FILTER (WHERE status = 'draft')        AS draft,
                COUNT(*) FILTER (WHERE status = 'archived')     AS archived,
                COALESCE(SUM(views), 0)                         AS total_views
             FROM contents"
        );
        return $stmt->fetch();
    }

    // ----------------------------------------
    // Private Helpers
    // ----------------------------------------

    private function generateSlug(string $title, int $excludeId = 0): string {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Fallback jika slug kosong setelah sanitasi
        if (empty($slug)) {
            $slug = 'konten-' . time();
        }

        $base  = $slug;
        $i     = 1;
        $limit = 100; // Cegah infinite loop

        while ($i <= $limit) {
            $check = $this->db->prepare(
                "SELECT id FROM contents WHERE slug = :slug AND id != :id"
            );
            $check->execute(['slug' => $slug, 'id' => $excludeId]);

            if (!$check->fetch()) {
                break; // Slug unik, keluar dari loop
            }

            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}