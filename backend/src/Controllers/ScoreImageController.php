<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Handles score-sheet image uploads and serving.
 *
 * Files are stored under `upload/score-img/` relative to the backend root.
 */
final class ScoreImageController
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    private const MAX_BYTES = 5 * 1024 * 1024; // 5 MB

    private function uploadDir(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'score-img';
    }

    /**
     * Store an uploaded image. Expects a multipart field named `scoreImage`.
     */
    public function upload(Request $request): Response
    {
        if (!isset($_FILES['scoreImage']) || $_FILES['scoreImage']['error'] === UPLOAD_ERR_NO_FILE) {
            return Response::json(['error' => 'No image provided.'], 422);
        }

        $file = $_FILES['scoreImage'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return Response::json(['error' => 'Image upload failed.'], 422);
        }

        if ($file['size'] > self::MAX_BYTES) {
            return Response::json(['error' => 'Image must be 5 MB or smaller.'], 422);
        }

        $mime = $this->detectMime($file['tmp_name']);
        if ($mime === null || !isset(self::ALLOWED[$mime])) {
            return Response::json(['error' => 'Only JPG, PNG, WebP or GIF images are allowed.'], 422);
        }

        $extension = self::ALLOWED[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        $targetDir = $this->uploadDir();
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $target = $targetDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return Response::json(['error' => 'Could not store the image.'], 500);
        }

        return Response::json([
            'filename' => $filename,
            'url' => '/api/uploads/score-img/' . $filename,
        ], 201);
    }

    /**
     * Serve a previously uploaded image so the frontend can display it.
     */
    public function serve(Request $request, string $filename): Response
    {
        $filename = basename($filename); // strip any path separators

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ];

        if (!isset($mimeTypes[$extension])) {
            return Response::json(['error' => 'Not found'], 404);
        }

        $path = $this->uploadDir() . DIRECTORY_SEPARATOR . $filename;
        if (!is_file($path)) {
            return Response::json(['error' => 'Not found'], 404);
        }

        if (!is_readable($path)) {
            return Response::json(['error' => 'Permission denied'], 403);
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            return Response::json(['error' => 'Could not read file'], 500);
        }

        return new Response(
            $content,
            200,
            [
                'Content-Type' => $mimeTypes[$extension],
                'Content-Length' => (string) filesize($path),
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]
        );
    }

    private function detectMime(string $path): ?string
    {
        if (function_exists('finfo_open')) {
            $info = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($info, $path);
            finfo_close($info);
            return is_string($mime) ? $mime : null;
        }

        return null;
    }
}