<?php
declare(strict_types=1);

/**
 * Donations, staff side — campaigns and the supporter ledger (README §9b).
 *
 * The public giving flow lives in `public/donate.php`, deliberately outside this
 * controller: it must work with no session and no logged-in user, and running it through
 * the authenticated front controller would mean bolting an exception onto an auth path.
 *
 * Two permissions, kept apart on purpose: `donation.campaign.manage` is content work,
 * `donation.view_any` is financial. Whoever writes the appeal has no reason to see every
 * supporter's name, email and amount.
 */
final class DonationController
{
    public static function campaigns(): void
    {
        Auth::requirePermission('donation.campaign.manage');
        $main = View::render('donations/campaigns', [
            'campaigns' => Donation::campaigns(),
            'centres'   => Centre::all(),
            'enabled'   => Donation::enabled(),
            'totals'    => Donation::totals(),
        ]);
        View::shell('donations', 'Donation Campaigns', $main);
    }

    public static function storeCampaign(): void
    {
        Auth::requirePermission('donation.campaign.manage');
        Csrf::requireValid();

        $title = trim((string) $_POST['title']);
        if ($title === '') {
            Session::flash('error', 'A campaign needs a title.');
            header('Location: app.php?r=donations.campaigns');
            exit;
        }
        $id = Donation::createCampaign(self::campaignForm());
        Audit::log('donation.campaign_created', 'donation_campaigns', $id, null, ['title' => $title]);
        Session::flash('success', 'Campaign created as a draft. Add the story, then publish it.');
        header('Location: app.php?r=donations.campaign&id=' . $id);
        exit;
    }

    public static function campaign(): void
    {
        Auth::requirePermission('donation.campaign.manage');
        $campaign = Donation::findCampaign((int) ($_GET['id'] ?? 0));
        if (!$campaign) {
            http_response_code(404);
            echo 'Campaign not found.';
            return;
        }
        $main = View::render('donations/campaign-form', [
            'campaign' => $campaign,
            'centres'  => Centre::all(),
            'progress' => Donation::progressPercent($campaign),
            'wall'     => Donation::wall((int) $campaign['id'], 10),
        ]);
        View::shell('donations', $campaign['title'], $main);
    }

    public static function updateCampaign(): void
    {
        Auth::requirePermission('donation.campaign.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        Donation::updateCampaign($id, self::campaignForm());
        Audit::log('donation.campaign_updated', 'donation_campaigns', $id, null, ['title' => $_POST['title']]);
        Session::flash('success', 'Saved.');
        header('Location: app.php?r=donations.campaign&id=' . $id);
        exit;
    }

    public static function campaignStatus(): void
    {
        Auth::requirePermission('donation.campaign.manage');
        Csrf::requireValid();
        $id = (int) $_POST['id'];
        $status = (string) $_POST['status'];
        if (!in_array($status, ['draft', 'published', 'closed'], true)) {
            Session::flash('error', 'Unknown status.');
            header('Location: app.php?r=donations.campaign&id=' . $id);
            exit;
        }

        // Publishing a campaign while the master switch is off would produce a live
        // appeal that refuses every gift. Say so rather than letting it happen.
        if ($status === 'published' && !Donation::enabled()) {
            Session::flash('error', 'Donations are switched off in Settings — turn "donations_enabled" on before publishing an appeal.');
            header('Location: app.php?r=donations.campaign&id=' . $id);
            exit;
        }

        $before = Donation::findCampaign($id);
        Donation::setCampaignStatus($id, $status);
        Audit::log('donation.campaign_status', 'donation_campaigns', $id,
            ['status' => $before['status'] ?? null], ['status' => $status]);
        Session::flash('success', 'Campaign ' . $status . '.');
        header('Location: app.php?r=donations.campaign&id=' . $id);
        exit;
    }

    /** The supporter ledger. Centre-scoped exactly like invoices are. */
    public static function index(): void
    {
        Auth::requirePermission('donation.view_any');
        $centreIds = Auth::scopeCentres('donation.view_any');
        $status = (string) ($_GET['status'] ?? '');

        $main = View::render('donations/index', [
            'donations' => Donation::listing($centreIds, $status !== '' ? $status : null),
            'totals'    => Donation::totals(),
            'status'    => $status,
            'canExport' => Auth::can('donation.export'),
        ]);
        View::shell('donations', 'Donations', $main);
    }

    /**
     * CSV of the supporter ledger.
     *
     * Exporting donor names, emails and amounts is a data-protection event, so it is a
     * separate permission and it is audited — §38 requires exports to be.
     */
    public static function export(): void
    {
        Auth::requirePermission('donation.export');
        $centreIds = Auth::scopeCentres('donation.view_any');
        $rows = Donation::listing($centreIds, null);

        Audit::log('donation.exported', 'donations', 0, null, ['rows' => count($rows)]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ultrademy-donations-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Reference', 'Date', 'Campaign', 'Donor', 'Email', 'Amount', 'Currency', 'Anonymous', 'Status']);
        foreach ($rows as $d) {
            fputcsv($out, [
                $d['reference'],
                $d['created_at'],
                $d['campaign_title'] ?? 'General fund',
                $d['donor_name'],
                $d['donor_email'],
                Money::toMajorString((int) $d['amount']),
                $d['currency'],
                (int) $d['is_anonymous'] === 1 ? 'yes' : 'no',
                $d['status'],
            ]);
        }
        fclose($out);
        exit;
    }

    /** @return array<string,mixed> */
    private static function campaignForm(): array
    {
        return [
            'title'           => trim((string) $_POST['title']),
            'summary'         => trim((string) ($_POST['summary'] ?? '')),
            'story'           => trim((string) ($_POST['story'] ?? '')),
            'target_amount'   => Money::toMinor((string) ($_POST['target_amount'] ?? '')),
            'currency'        => 'NGN',
            'centre_id'       => (int) ($_POST['centre_id'] ?? 0) ?: null,
            'starts_on'       => $_POST['starts_on'] ?: null,
            'ends_on'         => $_POST['ends_on'] ?: null,
            'show_donor_wall' => isset($_POST['show_donor_wall']) ? 1 : 0,
        ];
    }
}
