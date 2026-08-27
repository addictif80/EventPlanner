<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AdminActivityLog;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Models\SiteMenuItem;
use App\Models\SitePage;

/**
 * Super admin management of the public landing page's content: standalone
 * pages (viewable at /page/{slug}) and the header/footer navigation menus.
 */
class AdminSiteController
{
    public static function index(): void
    {
        Auth::requireSuperAdmin();

        $items = SiteMenuItem::allOrdered();
        View::render('admin/site', [
            'title' => 'Pages & menus',
            'pages' => SitePage::allOrdered(),
            'headerItems' => array_filter($items, fn($i) => $i['location'] === 'header'),
            'footerItems' => array_filter($items, fn($i) => $i['location'] === 'footer'),
        ]);
    }

    // --- Pages ---

    public static function createPage(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/page_form', ['title' => 'Nouvelle page', 'page' => null]);
    }

    public static function editPage(string $id): void
    {
        Auth::requireSuperAdmin();
        $page = SitePage::find((int) $id);
        if (!$page) {
            http_response_code(404);
            die('Page introuvable.');
        }
        View::render('admin/page_form', ['title' => 'Modifier ' . $page['title'], 'page' => $page]);
    }

    public static function storePage(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        self::savePage(null);
    }

    public static function updatePage(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        self::savePage((int) $id);
    }

    private static function savePage(?int $id): void
    {
        $title = trim(input('title', ''));
        $slug = self::slugify(input('slug', '') !== '' ? input('slug', '') : $title);

        if ($title === '' || $slug === '') {
            Session::flash('error', 'Le titre est obligatoire.');
            redirect($id ? '/admin/pages/' . $id . '/edit' : '/admin/pages/create');
        }

        if (SitePage::slugExists($slug, $id)) {
            Session::flash('error', "Ce slug est déjà utilisé par une autre page.");
            redirect($id ? '/admin/pages/' . $id . '/edit' : '/admin/pages/create');
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'content' => input('content', ''),
            'meta_description' => input('meta_description', ''),
            'is_published' => input('is_published') ? 1 : 0,
        ];

        if ($id) {
            SitePage::update($id, $data);
        } else {
            $id = SitePage::create($data);
        }

        AdminActivityLog::record('page_saved', 'site_page', $id, $title);
        Session::flash('success', 'Page enregistrée.');
        redirect('/admin/pages');
    }

    public static function destroyPage(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        SitePage::delete((int) $id);
        AdminActivityLog::record('page_deleted', 'site_page', (int) $id);
        Session::flash('success', 'Page supprimée.');
        redirect('/admin/pages');
    }

    private static function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    // --- Menu items ---

    public static function storeMenuItem(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $location = input('location') === 'footer' ? 'footer' : 'header';
        $label = trim(input('label', ''));
        $url = trim(input('url', ''));

        if ($label === '' || $url === '') {
            Session::flash('error', 'Le libellé et le lien sont obligatoires.');
            redirect('/admin/pages');
        }

        $id = SiteMenuItem::create([
            'location' => $location,
            'label' => $label,
            'url' => $url,
            'sort_order' => (int) input('sort_order', 0),
            'is_active' => 1,
        ]);

        AdminActivityLog::record('menu_item_saved', 'site_menu_item', $id, $label);
        Session::flash('success', 'Élément de menu ajouté.');
        redirect('/admin/pages');
    }

    public static function updateMenuItem(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();

        $label = trim(input('label', ''));
        $url = trim(input('url', ''));

        if ($label === '' || $url === '') {
            Session::flash('error', 'Le libellé et le lien sont obligatoires.');
            redirect('/admin/pages');
        }

        SiteMenuItem::update((int) $id, [
            'label' => $label,
            'url' => $url,
            'sort_order' => (int) input('sort_order', 0),
            'is_active' => input('is_active') ? 1 : 0,
        ]);

        AdminActivityLog::record('menu_item_saved', 'site_menu_item', (int) $id, $label);
        Session::flash('success', 'Élément de menu modifié.');
        redirect('/admin/pages');
    }

    public static function destroyMenuItem(string $id): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyOrFail();
        SiteMenuItem::delete((int) $id);
        AdminActivityLog::record('menu_item_deleted', 'site_menu_item', (int) $id);
        Session::flash('success', 'Élément de menu supprimé.');
        redirect('/admin/pages');
    }
}
