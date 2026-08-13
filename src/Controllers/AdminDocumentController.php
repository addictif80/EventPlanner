<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AdminActivityLog;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;

class AdminDocumentController
{
    private const UPLOAD_DIR = __DIR__ . '/../../storage/uploads';

    public static function index(): void
    {
        Auth::requireSuperAdmin();

        $stmt = Database::connection()->query(
            'SELECT documents.*, organizations.name AS organization_name, users.name AS uploaded_by_name
             FROM documents
             JOIN organizations ON organizations.id = documents.organization_id
             LEFT JOIN users ON users.id = documents.uploaded_by
             ORDER BY documents.created_at DESC LIMIT 300'
        );

        View::render('admin/documents', ['title' => 'Documents', 'documents' => $stmt->fetchAll()]);
    }

    public static function download(string $id): void
    {
        Auth::requireSuperAdmin();

        $stmt = Database::connection()->prepare('SELECT * FROM documents WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        if (!$doc) { http_response_code(404); die('Document introuvable.'); }

        $path = self::UPLOAD_DIR . '/' . $doc['stored_name'];
        if (!is_file($path)) { http_response_code(404); die('Fichier introuvable sur le serveur.'); }

        AdminActivityLog::record('document_download', 'document', (int) $id, $doc['original_name']);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $doc['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public static function destroy(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $stmt = Database::connection()->prepare('SELECT * FROM documents WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if ($doc) {
            $path = self::UPLOAD_DIR . '/' . $doc['stored_name'];
            if (is_file($path)) {
                unlink($path);
            }
            Database::connection()->prepare('DELETE FROM documents WHERE id = ?')->execute([$id]);
            AdminActivityLog::record('document_delete', 'document', (int) $id, $doc['original_name']);
        }

        Session::flash('success', 'Document supprimé.');
        redirect('/admin/documents');
    }
}
