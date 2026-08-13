<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Models\Document;

class DocumentController
{
    private const UPLOAD_DIR = __DIR__ . '/../../storage/uploads';

    public static function store(): void
    {
        Csrf::verifyOrFail();

        $clientId = input('client_id') !== '' ? (int) input('client_id') : null;
        $eventId = input('event_id') !== '' ? (int) input('event_id') : null;
        $redirectTo = $eventId ? '/events/' . $eventId : ($clientId ? '/clients/' . $clientId : '/');

        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Aucun fichier valide reçu.');
            redirect($redirectTo);
        }

        $file = $_FILES['file'];
        if ($file['size'] > 15 * 1024 * 1024) {
            Session::flash('error', 'Le fichier dépasse la taille maximale autorisée (15 Mo).');
            redirect($redirectTo);
        }

        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt', 'csv'];
        if (!in_array($ext, $allowed, true)) {
            Session::flash('error', "Type de fichier non autorisé (extensions acceptées : " . implode(', ', $allowed) . ").");
            redirect($redirectTo);
        }

        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], self::UPLOAD_DIR . '/' . $storedName)) {
            Session::flash('error', "Échec de l'enregistrement du fichier.");
            redirect($redirectTo);
        }

        Document::create([
            'client_id' => $clientId,
            'event_id' => $eventId,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'category' => input('category', ''),
            'uploaded_by' => Auth::id(),
        ]);

        Session::flash('success', 'Document ajouté.');
        redirect($redirectTo);
    }

    public static function download(string $id): void
    {
        $stmt = Database::connection()->prepare('SELECT * FROM documents WHERE id = ?');
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if (!$doc) { http_response_code(404); die('Document introuvable.'); }

        $path = self::UPLOAD_DIR . '/' . $doc['stored_name'];
        if (!is_file($path)) { http_response_code(404); die('Fichier introuvable sur le serveur.'); }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $doc['original_name']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public static function destroy(string $id): void
    {
        Csrf::verifyOrFail();

        $stmt = Database::connection()->prepare('SELECT * FROM documents WHERE id = ?');
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if ($doc) {
            $path = self::UPLOAD_DIR . '/' . $doc['stored_name'];
            if (is_file($path)) {
                unlink($path);
            }
            $redirectTo = $doc['event_id'] ? '/events/' . $doc['event_id'] : ($doc['client_id'] ? '/clients/' . $doc['client_id'] : '/');
            Document::delete((int) $id);
        } else {
            $redirectTo = '/';
        }

        Session::flash('success', 'Document supprimé.');
        redirect($redirectTo);
    }
}
