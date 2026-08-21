# 18 — Donations & Supporter Giving

Phase 9b. Covers the donation page requested against README §9b, implemented in
migrations 087–090, `app/models/Donation.php`, `app/controllers/DonationController.php`,
`public/donate.php` and `public/donate-status.php`.

---

## 1. What this is, and what it is not

A **donation** is a gift. Nothing is given in return: no equity, no share of revenue, no
promise of repayment. The public page says so in plain words, on the form itself, because
putting "Donate" on a button does not change what an arrangement legally is.

An **investment** is a security. Offering one to the public in Nigeria engages SEC Nigeria
rules on public offers. **This feature is not that**, and must not be relabelled into it.

> The original request said "where investors can donate". Those are two different
> arrangements with two different legal regimes. What is built here is the donation. If
> UltrAdemy intends to raise capital from investors, that needs counsel before it needs a
> developer — and it is not a public web form.
>
> This is a flag, not legal advice.

Also unresolved and worth checking with someone qualified: whether gifts are
tax-deductible for the donor (it changes the receipt wording), and whether UltrAdemy is
registered in a form that can lawfully solicit public donations.

---

## 2. One ledger, not two

The tempting shape is a `donations` table with its own Paystack call. It was rejected.

A second money path means reconciliation misses donations, receipts number separately,
centre attribution breaks, and the accountant closes two systems every month.

Instead:

```
DONOR  (usually a guest)
  │
  ├── donations              intent, campaign, donor details, anonymity, message
  │
  └── invoices               payable_type = 'donation', payable_id = donation.id
         │                   centre_id = campaign's centre, or NULL for the general fund
         └── payments  ──▶   the existing gateways, webhook, receipt, reconciliation
```

Migration 089 is a single `ALTER` adding `'donation'` to the `payable_type` enum. That
is the entire integration — and it is exactly why 02-data-model.md §7 made that column
polymorphic instead of adding a nullable foreign key per payable kind.

`PaymentService::fulfil()` gains one branch. Everything else — signature verification,
idempotency, amount checking, manual bank-transfer verification, receipts, refunds,
centre reporting — works unchanged because none of it knows or cares what the invoice is
for.

---

## 3. Guest giving, and why donors get a `users` row

Requiring registration before someone can give is the single largest cause of abandoned
donations. So the form asks for a name, an email and an amount. Nothing else.

But `invoices.user_id` and `payments.user_id` are both `NOT NULL` with foreign keys. A
donation with no user could not be invoiced or receipted at all. Three options:

| Option | Verdict |
|---|---|
| Require login to donate | Rejected — kills the conversion the feature exists for |
| Make three financial tables nullable | Rejected — invasive, and every existing finance query assumes NOT NULL |
| **Create a `users` row for the donor** | **Taken** |

The third is not a workaround, it is what README §3 actually asks for: *one identity
record per person, gaining relationships over time*. A donor is someone UltrAdemy has a
relationship with. If they register properly later, they are the same person rather than
a duplicate.

The created row has `status = 'pending'` and a password hash of 64 random bytes nobody
holds — unusable by construction, not an empty string that some verification path might
accept. It cannot be signed into.

### Abuse controls on an open endpoint

An unauthenticated form that creates users and invoices needs guarding:

- **Rate limited** — 8 attempts per IP per hour.
- **Captcha**, when one is configured.
- **No enumeration signal.** An email that already has an account is reused silently; the
  response is identical either way. A public form that answers differently for
  known addresses is an account-enumeration oracle.
- **An existing profile's name is never overwritten** by whatever is typed into a
  donation form.
- **Amount floor and ceiling** — below ₦100 is refused, above ₦5,000,000 is directed to a
  conversation. The ceiling is not a limit on generosity; it catches the fat-fingered
  extra zero and the card tester probing with a large number.

---

## 4. The guest return path

Gateway callback URLs are configured per provider, not per payment, and
`payment-return.php` requires a login. A guest donor would hit a login wall holding a
receipt.

`payment-return.php` now checks, *before* requiring authentication, whether the
referenced payment belongs to a donation invoice — and if so redirects to
`donate-status.php?t=<token>`. Everything else still requires a login before it reveals
anything.

`donations.public_token` is 128 bits of randomness. The status page shows only what the
person who made the gift already knows: their own amount, reference and status. No other
donor, no ledger, no account details.

The status page never marks anything paid. It asks the gateway server-side, exactly as
`payment-return.php` does — the rule from 05-finance-payments.md §5 holds here too.

---

## 5. Centre attribution

`donations.centre_id` is inherited from the campaign, and `NULL` means the general fund.
Per §31, `NULL` is reported as its own line and never folded into a physical centre. A
campaign earmarked for Gwagwalada attributes its income to Gwagwalada; general giving
does not.

---

## 6. Permissions

| Permission | Held by | Why separate |
|---|---|---|
| `donation.campaign.manage` | super_admin, administrator | Content work — writing the appeal |
| `donation.view_any` | super_admin, accountant, management | Financial — the supporter ledger, centre-scoped |
| `donation.export` | super_admin, accountant | Exporting names, emails and amounts is a data-protection event, and is audited (§38) |

Whoever writes the fundraising copy has no reason to see every supporter's name, email
and amount.

---

## 7. Two safety interlocks

**`donations_enabled` is off by default.** The public page shows a "not accepting
donations" notice, and the nav link does not appear at all — a "Support Us" link leading
to an empty page costs more trust than it earns.

**A campaign cannot be published while the master switch is off.** Otherwise you get a
live appeal that refuses every gift, which is the worst possible state to discover in
production.

---

## 8. Do not turn this on yet

`webhook_events` is empty in the live database. No gateway callback has ever reached this
system, and per 05-finance-payments.md §5 the webhook **is** the source of truth for
payment status.

Donations are the worst place to discover that. A student whose enrolment payment fails
silently notices when their enrolment does not activate. **A donor notices nothing** —
they have no invoice to chase, no service to miss, and no reason to contact you. The
money is simply gone from your side of the ledger.

So: prove the payment path first.

1. `ngrok http 80`
2. Point Paystack's test webhook at
   `https://<id>.ngrok.io/ultra/public/webhook.php?provider=paystack`
3. Pay a test invoice; confirm a row appears in `webhook_events` with
   `signature_valid = 1` and `processed_at` set
4. Replay the same event; confirm it returns "Duplicate event ignored" and does not
   credit twice
5. Only then set `donations_enabled` to `1`

---

## 9. Tests

`tests/DonationTest.php` — 12 assertions covering the campaign window (including the
off-by-one that would shut an appeal a day early, on the day people are most likely to
give), the progress arithmetic and its cap, the amount guards, and reference
uniqueness.

Not covered without a database: `resolveDonorUser`, the invoice creation transaction, and
`markCompleted` idempotency. Those belong with the Phase 14 database tests.

---

## 10. Not built

| Feature | Note |
|---|---|
| Recurring / monthly giving | Needs gateway subscription tokens; a real feature in its own right |
| Gift Aid or tax-receipt numbering | Depends on UltrAdemy's registration status — see §1 |
| Donor thank-you email templates | Currently an in-app notification; the recruitment module's template system is the obvious thing to reuse |
| Campaign cover images | Column exists (`cover_path`), upload UI does not |
| Offline / cheque donations entered by finance | Cash is possible today via the existing cashier flow against the donation invoice |
| Corporate matched giving | Phase 13 territory |

| # | Decision | Default taken |
|---|---|---|
| 29 | Are donations tax-deductible for the donor? | Assumed no — receipt wording is neutral |
| 30 | Minimum and maximum gift | ₦100 and ₦5,000,000 |
| 31 | Is the donor wall on by default? | Yes, per campaign, with per-donation anonymity |
