<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Demo;
use App\Core\Session;
use App\Core\StripeBilling;
use App\Core\View;
use App\Models\Module;
use App\Models\ModulePackage;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationSubscriptionItem;
use App\Models\Plan;
use App\Models\SystemSetting;

/**
 * Tenant-facing subscription management: current plan/usage, catalog of
 * available plans/modules/packages, initial Stripe Checkout, and — once a
 * Stripe subscription exists — direct add-on/plan changes via the
 * Subscription Items API (no repeat Checkout needed, card already on file).
 */
class SubscriptionController
{
    public static function index(): void
    {
        Auth::requireAdmin();

        $orgId = Auth::organizationId();
        $subscription = OrganizationSubscription::forOrganization($orgId);
        $items = $subscription ? OrganizationSubscriptionItem::forSubscription($subscription['id']) : [];

        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE organization_id = ?');
        $stmt->execute([$orgId]);
        $memberCount = (int) $stmt->fetchColumn();

        View::render('subscription/index', [
            'title' => 'Abonnement',
            'subscription' => $subscription,
            'items' => $items,
            'memberCount' => $memberCount,
            'plans' => Plan::activeOrdered(),
            'modules' => Module::activeOrdered(),
            'packages' => ModulePackage::activeOrdered(),
            'stripeConfigured' => StripeBilling::isConfigured(),
        ]);
    }

    /** Initial subscription: only usable while the organization has no active Stripe subscription yet. */
    public static function checkout(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();
        self::blockInDemo();

        $orgId = Auth::organizationId();
        $subscription = OrganizationSubscription::forOrganization($orgId);

        if ($subscription && !empty($subscription['stripe_subscription_id']) && in_array($subscription['status'], ['active', 'trialing'], true)) {
            Session::flash('error', 'Vous avez déjà un abonnement actif. Utilisez les actions ci-dessous pour le modifier.');
            redirect('/subscription');
        }

        $planId = input('plan_id') !== '' ? (int) input('plan_id') : null;
        $moduleIds = array_values(array_filter(array_map('intval', $_POST['module_ids'] ?? [])));
        $packageIds = array_values(array_filter(array_map('intval', $_POST['package_ids'] ?? [])));

        $plan = $planId ? Plan::find($planId) : null;
        if ($planId && (!$plan || !$plan['is_active'])) {
            Session::flash('error', 'Offre invalide.');
            redirect('/subscription');
        }

        $priceIds = [];
        if ($plan && !empty($plan['stripe_price_id'])) {
            $priceIds[] = $plan['stripe_price_id'];
        }
        foreach ($moduleIds as $moduleId) {
            $module = Module::find($moduleId);
            if ($module && $module['is_active'] && !empty($module['stripe_price_id'])) {
                $priceIds[] = $module['stripe_price_id'];
            }
        }
        foreach ($packageIds as $packageId) {
            $package = ModulePackage::find($packageId);
            if ($package && $package['is_active'] && !empty($package['stripe_price_id'])) {
                $priceIds[] = $package['stripe_price_id'];
            }
        }

        if (empty($priceIds)) {
            OrganizationSubscription::upsertForOrganization($orgId, ['plan_id' => $planId, 'status' => 'active']);
            $localSubId = OrganizationSubscription::forOrganization($orgId)['id'];
            foreach ($moduleIds as $moduleId) {
                OrganizationSubscriptionItem::create(['organization_subscription_id' => $localSubId, 'item_type' => 'module', 'module_id' => $moduleId]);
            }
            foreach ($packageIds as $packageId) {
                OrganizationSubscriptionItem::create(['organization_subscription_id' => $localSubId, 'item_type' => 'package', 'package_id' => $packageId]);
            }
            Session::flash('success', 'Offre activée.');
            redirect('/subscription');
        }

        if (!StripeBilling::isConfigured()) {
            Session::flash('error', "Le paiement des abonnements n'est pas configuré côté plateforme pour le moment.");
            redirect('/subscription');
        }

        $baseUrl = ($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
        $successUrl = $baseUrl . url('/subscription?checkout=success');
        $cancelUrl = $baseUrl . url('/subscription?checkout=cancel');

        $user = Auth::user();

        try {
            $session = StripeBilling::createCheckoutSession(
                $priceIds,
                $successUrl,
                $cancelUrl,
                [
                    'organization_id' => (string) $orgId,
                    'plan_id' => $planId ? (string) $planId : '',
                    'module_ids' => implode(',', $moduleIds),
                    'package_ids' => implode(',', $packageIds),
                ],
                $subscription['stripe_customer_id'] ?? null,
                $user['email'] ?? null
            );
        } catch (\RuntimeException $e) {
            Session::flash('error', "Erreur Stripe : " . $e->getMessage());
            redirect('/subscription');
        }

        if (empty($session['url'])) {
            Session::flash('error', 'Réponse Stripe invalide : ' . ($session['error']['message'] ?? 'erreur inconnue'));
            redirect('/subscription');
        }

        header('Location: ' . $session['url']);
        exit;
    }

    /** Changes the base plan on an already-subscribed organization (proration handled by Stripe). */
    public static function changePlan(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();
        self::blockInDemo();

        $orgId = Auth::organizationId();
        $subscription = OrganizationSubscription::forOrganization($orgId);
        if (!$subscription) {
            Session::flash('error', "Aucun abonnement en cours. Utilisez d'abord Souscrire.");
            redirect('/subscription');
        }

        $planId = (int) input('plan_id');
        $plan = Plan::find($planId);
        if (!$plan || !$plan['is_active']) {
            Session::flash('error', 'Offre invalide.');
            redirect('/subscription');
        }

        $items = OrganizationSubscriptionItem::forSubscription($subscription['id']);
        $planItem = null;
        foreach ($items as $item) {
            if ($item['item_type'] === 'plan') {
                $planItem = $item;
                break;
            }
        }

        try {
            if (!empty($plan['stripe_price_id'])) {
                if (empty($subscription['stripe_subscription_id'])) {
                    Session::flash('error', "Aucun abonnement Stripe actif. Utilisez Souscrire pour choisir une offre payante.");
                    redirect('/subscription');
                }
                if ($planItem && !empty($planItem['stripe_subscription_item_id'])) {
                    StripeBilling::updateSubscriptionItemPrice($planItem['stripe_subscription_item_id'], $plan['stripe_price_id']);
                    OrganizationSubscriptionItem::updatePrice((int) $planItem['id'], $plan['stripe_price_id']);
                } else {
                    $result = StripeBilling::addSubscriptionItem($subscription['stripe_subscription_id'], $plan['stripe_price_id']);
                    if (empty($result['id'])) {
                        throw new \RuntimeException($result['error']['message'] ?? 'ajout du prix impossible.');
                    }
                    OrganizationSubscriptionItem::create([
                        'organization_subscription_id' => $subscription['id'],
                        'item_type' => 'plan',
                        'stripe_subscription_item_id' => $result['id'],
                        'stripe_price_id' => $plan['stripe_price_id'],
                    ]);
                }
            } elseif ($planItem && !empty($planItem['stripe_subscription_item_id'])) {
                StripeBilling::removeSubscriptionItem($planItem['stripe_subscription_item_id']);
                OrganizationSubscriptionItem::delete((int) $planItem['id']);
            }
        } catch (\RuntimeException $e) {
            Session::flash('error', 'Erreur Stripe : ' . $e->getMessage());
            redirect('/subscription');
        }

        OrganizationSubscription::upsertForOrganization($orgId, ['plan_id' => $planId]);
        Session::flash('success', 'Offre mise à jour.');
        redirect('/subscription');
    }

    public static function addAddon(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();
        self::blockInDemo();

        $orgId = Auth::organizationId();
        $type = input('type', '');
        $itemId = (int) input('item_id');

        if (!in_array($type, ['module', 'package'], true)) {
            Session::flash('error', 'Sélection invalide.');
            redirect('/subscription');
        }

        $subscription = OrganizationSubscription::forOrganization($orgId);
        if (!$subscription) {
            Session::flash('error', "Souscrivez d'abord à une offre avant d'ajouter des modules.");
            redirect('/subscription');
        }

        $entity = $type === 'package' ? ModulePackage::find($itemId) : Module::find($itemId);
        if (!$entity || !$entity['is_active']) {
            Session::flash('error', 'Module introuvable.');
            redirect('/subscription');
        }

        $itemData = [
            'organization_subscription_id' => $subscription['id'],
            'item_type' => $type,
            'module_id' => $type === 'module' ? $itemId : null,
            'package_id' => $type === 'package' ? $itemId : null,
        ];

        if (empty($entity['stripe_price_id'])) {
            OrganizationSubscriptionItem::create($itemData);
            Session::flash('success', 'Module activé.');
            redirect('/subscription');
        }

        if (empty($subscription['stripe_subscription_id'])) {
            Session::flash('error', "Souscrivez d'abord à une offre payante avant d'ajouter un module payant (un moyen de paiement doit être enregistré).");
            redirect('/subscription');
        }

        try {
            $result = StripeBilling::addSubscriptionItem($subscription['stripe_subscription_id'], $entity['stripe_price_id']);
        } catch (\RuntimeException $e) {
            Session::flash('error', 'Erreur Stripe : ' . $e->getMessage());
            redirect('/subscription');
        }

        if (empty($result['id'])) {
            Session::flash('error', 'Erreur Stripe : ' . ($result['error']['message'] ?? 'ajout impossible.'));
            redirect('/subscription');
        }

        $itemData['stripe_subscription_item_id'] = $result['id'];
        $itemData['stripe_price_id'] = $entity['stripe_price_id'];
        OrganizationSubscriptionItem::create($itemData);

        Session::flash('success', 'Module activé.');
        redirect('/subscription');
    }

    public static function removeAddon(string $itemId): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();
        self::blockInDemo();

        $orgId = Auth::organizationId();
        $subscription = OrganizationSubscription::forOrganization($orgId);
        if (!$subscription) {
            redirect('/subscription');
        }

        $item = OrganizationSubscriptionItem::find((int) $itemId);
        if (!$item || (int) $item['organization_subscription_id'] !== (int) $subscription['id'] || $item['item_type'] === 'plan') {
            Session::flash('error', 'Module introuvable.');
            redirect('/subscription');
        }

        if (!empty($item['stripe_subscription_item_id'])) {
            try {
                StripeBilling::removeSubscriptionItem($item['stripe_subscription_item_id']);
            } catch (\RuntimeException $e) {
                Session::flash('error', 'Erreur Stripe : ' . $e->getMessage());
                redirect('/subscription');
            }
        }

        OrganizationSubscriptionItem::delete((int) $itemId);
        Session::flash('success', 'Module retiré.');
        redirect('/subscription');
    }

    public static function cancel(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();
        self::blockInDemo();

        $orgId = Auth::organizationId();
        $subscription = OrganizationSubscription::forOrganization($orgId);

        if ($subscription && !empty($subscription['stripe_subscription_id'])) {
            try {
                StripeBilling::cancelSubscription($subscription['stripe_subscription_id'], true);
                Database::connection()->prepare('UPDATE organization_subscriptions SET cancel_at_period_end = 1 WHERE id = ?')->execute([$subscription['id']]);
                Session::flash('success', "Votre abonnement sera annulé à la fin de la période en cours.");
            } catch (\RuntimeException $e) {
                Session::flash('error', 'Erreur Stripe : ' . $e->getMessage());
            }
        }

        redirect('/subscription');
    }

    /** Public, unauthenticated: Stripe calls this server-to-server, verified via signature. */
    public static function webhook(): void
    {
        $payload = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $secret = SystemSetting::get()['stripe_webhook_secret'] ?? '';

        if (!StripeBilling::verifyWebhookSignature($payload, $sigHeader, $secret)) {
            http_response_code(400);
            die('Signature invalide.');
        }

        $event = json_decode($payload, true);
        $type = $event['type'] ?? '';
        $object = $event['data']['object'] ?? [];

        switch ($type) {
            case 'checkout.session.completed':
                self::handleCheckoutCompleted($object);
                break;
            case 'customer.subscription.updated':
                self::handleSubscriptionUpdated($object);
                break;
            case 'customer.subscription.deleted':
                self::handleSubscriptionDeleted($object);
                break;
            case 'invoice.payment_failed':
                self::handlePaymentFailed($object);
                break;
        }

        http_response_code(200);
        echo 'ok';
    }

    private static function handleCheckoutCompleted(array $session): void
    {
        $meta = $session['metadata'] ?? [];
        $orgId = (int) ($meta['organization_id'] ?? 0);
        $subscriptionId = $session['subscription'] ?? null;
        if (!$orgId || !$subscriptionId) {
            return;
        }

        $stripeSub = StripeBilling::retrieveSubscription($subscriptionId);
        $periodEnd = isset($stripeSub['current_period_end']) ? date('Y-m-d H:i:s', $stripeSub['current_period_end']) : null;
        $planId = !empty($meta['plan_id']) ? (int) $meta['plan_id'] : null;

        $localSubId = OrganizationSubscription::upsertForOrganization($orgId, [
            'plan_id' => $planId,
            'status' => 'active',
            'stripe_customer_id' => $session['customer'] ?? '',
            'stripe_subscription_id' => $subscriptionId,
            'current_period_end' => $periodEnd,
            'cancel_at_period_end' => 0,
        ]);

        self::clearPastDueAndReactivate($localSubId, $orgId);

        $moduleIds = array_filter(array_map('intval', explode(',', $meta['module_ids'] ?? '')));
        $packageIds = array_filter(array_map('intval', explode(',', $meta['package_ids'] ?? '')));

        foreach (($stripeSub['items']['data'] ?? []) as $stripeItem) {
            $priceId = $stripeItem['price']['id'] ?? null;
            $stripeItemId = $stripeItem['id'] ?? null;
            if (!$priceId || !$stripeItemId || OrganizationSubscriptionItem::findByStripeItemId($stripeItemId)) {
                continue;
            }

            $type = null;
            $moduleId = null;
            $packageId = null;

            if ($planId && ($plan = Plan::find($planId)) && $plan['stripe_price_id'] === $priceId) {
                $type = 'plan';
            }
            if (!$type) {
                foreach ($moduleIds as $mid) {
                    $module = Module::find($mid);
                    if ($module && $module['stripe_price_id'] === $priceId) {
                        $type = 'module';
                        $moduleId = $mid;
                        break;
                    }
                }
            }
            if (!$type) {
                foreach ($packageIds as $pid) {
                    $package = ModulePackage::find($pid);
                    if ($package && $package['stripe_price_id'] === $priceId) {
                        $type = 'package';
                        $packageId = $pid;
                        break;
                    }
                }
            }
            if (!$type) {
                continue;
            }

            OrganizationSubscriptionItem::create([
                'organization_subscription_id' => $localSubId,
                'item_type' => $type,
                'module_id' => $moduleId,
                'package_id' => $packageId,
                'stripe_subscription_item_id' => $stripeItemId,
                'stripe_price_id' => $priceId,
            ]);
        }
    }

    private static function handleSubscriptionUpdated(array $sub): void
    {
        $subscriptionId = $sub['id'] ?? null;
        if (!$subscriptionId) {
            return;
        }
        $local = OrganizationSubscription::findByStripeSubscriptionId($subscriptionId);
        if (!$local) {
            return;
        }

        $status = self::mapStripeStatus($sub['status'] ?? '');
        $periodEnd = isset($sub['current_period_end']) ? date('Y-m-d H:i:s', $sub['current_period_end']) : null;

        Database::connection()->prepare('UPDATE organization_subscriptions SET status = ?, current_period_end = ?, cancel_at_period_end = ? WHERE id = ?')
            ->execute([$status, $periodEnd, !empty($sub['cancel_at_period_end']) ? 1 : 0, $local['id']]);

        if ($status === 'active') {
            self::clearPastDueAndReactivate((int) $local['id'], (int) $local['organization_id']);
        }
    }

    /**
     * Payment recovered: clears the past_due clock, and if the organization
     * had been auto-suspended for non-payment (see bin/suspend_unpaid_organizations.php),
     * reactivates it. A manually-suspended organization (suspension_reason = 'manual')
     * is left untouched — only the super admin lifts that one.
     */
    private static function clearPastDueAndReactivate(int $subscriptionId, int $organizationId): void
    {
        Database::connection()->prepare('UPDATE organization_subscriptions SET past_due_since = NULL WHERE id = ?')->execute([$subscriptionId]);

        $organization = Organization::find($organizationId);
        if ($organization && $organization['status'] === 'suspended' && $organization['suspension_reason'] === 'non_payment') {
            Organization::update($organizationId, ['status' => 'active', 'suspension_reason' => null]);
            Notification::toPlatform(
                'system',
                'Organisation réactivée',
                $organization['name'] . ' a régularisé son paiement et a été réactivée automatiquement.',
                '/admin/organizations/' . $organizationId
            );
        }
    }

    private static function handleSubscriptionDeleted(array $sub): void
    {
        $subscriptionId = $sub['id'] ?? null;
        if (!$subscriptionId) {
            return;
        }
        $local = OrganizationSubscription::findByStripeSubscriptionId($subscriptionId);
        if ($local) {
            OrganizationSubscription::updateStatus((int) $local['id'], 'canceled');
        }
    }

    private static function handlePaymentFailed(array $invoice): void
    {
        $subscriptionId = $invoice['subscription'] ?? null;
        if (!$subscriptionId) {
            return;
        }
        $local = OrganizationSubscription::findByStripeSubscriptionId($subscriptionId);
        if ($local) {
            OrganizationSubscription::updateStatus((int) $local['id'], 'past_due');
            // Only start the grace-period clock the first time it goes past_due —
            // Stripe retries a failing invoice several times over ~3 weeks, and
            // each retry re-fires this webhook; the clock must not keep resetting.
            Database::connection()
                ->prepare('UPDATE organization_subscriptions SET past_due_since = COALESCE(past_due_since, NOW()) WHERE id = ?')
                ->execute([$local['id']]);
        }
    }

    /** The demo account already has every module unlocked (see bin/seed_demo_data.php) — no real Stripe account to charge. */
    private static function blockInDemo(): void
    {
        if (Demo::isActive()) {
            Session::flash('error', "Le changement d'abonnement est désactivé en mode démo.");
            redirect('/subscription');
        }
    }

    private static function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active' => 'active',
            'trialing' => 'trialing',
            'past_due', 'unpaid' => 'past_due',
            'canceled', 'incomplete_expired' => 'canceled',
            default => 'incomplete',
        };
    }
}
