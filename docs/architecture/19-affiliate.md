# 19 — Affiliate Programme

Phase 11. README §25, implemented in migrations 091–095, `app/models/Affiliate.php`,
`app/controllers/AffiliateController.php`, `public/r.php` and `app/views/affiliate/`.

Closes the last case of a feature being sold with nothing behind it: the
`affiliate_programme` entitlement has been granted by every package since Phase 6.

---

## 1. Shape

```
AFFILIATE ──▶ referral link (/r.php?c=CODE) ──▶ cookie, 30 days
                                                     │
                                            visitor registers
                                                     │
                                              REFERRAL (pending)
                                                     │
                                     their first qualifying payment
                                                     │
                                              COMMISSION (pending)
                                                     │
                                    management approves ──▶ approved
                                                     │
                                     finance sweeps ──▶ PAYOUT ──▶ paid
```

Four states of money, three different people deciding. An affiliate cannot approve their
own commission; the person who approves it does not send it.

---

## 2. Three constraints doing the real work

Each of these is a database guarantee, not a check someone has to remember:

**`UNIQUE (referrals.referred_user_id)`** — a person can be referred once, ever. Without
it, the obvious fraud is re-attributing an existing customer to a new affiliate, or to
yourself, and collecting on someone who was already going to buy.

**`UNIQUE (commissions.payment_id)`** — one payment earns at most one commission. This is
the idempotency guard for the earning hook: a replayed webhook, a double-clicked verify
button or a re-run reconciliation cannot pay twice. The hook catches error 1062 and
treats it as normal, because it is.

**`rate_bps` and `base_amount` snapshotted on the commission** — renegotiating a rate next
month must not silently revalue commissions already earned.

---

## 3. What earns a commission

| Payable type | Earns? | Why |
|---|---|---|
| `enrolment` | yes | the referral did real work |
| `subscription` | yes | same |
| `donation` | **no** | paying commission out of a charitable gift spends money the donor intended for the cause |
| `application_fee` | no | too small to be worth the accounting |

**First qualifying payment only** (Decision 6's default). Not everything the referred
person ever buys. That is the narrower reading, chosen deliberately: widening it later is
a one-line change, and narrowing it after affiliates have been paid on repeat purchases
is a conversation nobody wants to have.

The hook sits in `PaymentService::afterSuccessful()`, not `fulfil()`, so commission is
earned on money actually received — a part payment counts — and it fires identically for
card, bank transfer and cash.

**It swallows its own errors.** This code runs inside the payment flow. An affiliate
problem must never break someone's enrolment, so every failure is logged and dropped
rather than thrown.

---

## 4. Attribution

`/r.php?c=CODE` sets an `HttpOnly`, `SameSite=Lax` cookie for 30 days (configurable) and
forwards to the site. A dedicated endpoint rather than a `?ref=` parameter honoured
everywhere, so the tracking lives in one auditable file.

The `to` parameter is checked against a whitelist pattern and cannot leave the site — an
open redirect here would let an affiliate link forward anywhere while wearing UltrAdemy's
domain.

`Affiliate::attributeRegistration()` runs at registration and refuses silently on every
rejection path: programme closed, unknown code, **self-referral**, or a user who has
already been referred. A registration form is not the place to explain that a referral
was declined, and the reasons are not the new user's business.

Codes are **generated, never chosen**. A self-chosen code lets someone claim a lookalike
of a programme name or of another affiliate's code. The alphabet excludes `0/O` and
`1/I/L`, because these get read aloud and copied off screens.

---

## 5. What affiliates can and cannot see

An affiliate's own dashboard lists their referrals **by date and status only**. Not
names, not email addresses. They see what they earned, not who they earned it from.

Someone who refers a colleague should not thereby acquire a feed of that colleague's
purchasing behaviour. This costs the affiliate nothing they legitimately need and closes
a privacy hole that most affiliate systems leave open.

---

## 6. Permissions

| Permission | Held by |
|---|---|
| `affiliate.application.review` / `.approve` | super_admin, administrator |
| `affiliate.referral.view_any` | super_admin, administrator, management, accountant |
| `affiliate.commission.approve` | super_admin, management |
| `affiliate.payout.process` | super_admin, accountant |

Management approves what is owed; finance sends it. Separate people, per
05-finance-payments.md §8.

The `affiliate` role is granted by the system on approval and revoked on suspension or
rejection — never assigned by hand (03-rbac.md §2).

---

## 7. Payouts

`createPayout()` sweeps every approved, unpaid commission into one payout, inside a
transaction that re-reads the commissions with `FOR UPDATE`. Without that lock, two
operators clicking "pay" simultaneously would each sweep the same commissions into a
separate payout and the affiliate is paid twice.

There is a configurable minimum (default ₦5,000) so the programme is not sending ₦40
transfers.

Marking a payout paid records the bank reference and flips its commissions to `paid` in
the same transaction.

---

## 8. Off by default

`affiliate_enabled` ships as `0`. Referral links do not attribute, no commission is
earned, and the application form is closed. Turn it on in Settings when the commercial
terms are actually agreed.

Worth settling before switching on: the rate (default 5%), whether it really is
first-payment-only, and the payout minimum.

---

## 9. Tests

`tests/AffiliateTest.php` — 7 assertions on the commission arithmetic, including the two
that matter: it **rounds down**, so the house never pays out money it did not receive;
and a thousand commissions sum exactly, which is why rates are basis-point integers
rather than `0.05` floats.

Not covered without a database: attribution, the earning hook's idempotency, and the
payout sweep's locking. Those are Phase 14 work — and the payout lock in particular
deserves a concurrency test, because it is the one place two humans racing each other
costs real money.

---

## 10. Not built

| Feature | Note |
|---|---|
| Multi-tier / sub-affiliates | Structurally different; a real decision, not an oversight |
| Per-programme commission rates | Rate is per affiliate today |
| Affiliate marketing assets (banners, copy) | CMS territory |
| Public affiliate landing page (§64) | The in-app page exists; the public pitch does not |
| Self-service payout requests | Finance raises payouts today |

**Clawback on refund — resolved 21 Aug 2026.** `Affiliate::clawback()`, called from
`Refund::decide()` the moment a refund is approved, voids the commission earned on that
payment. A `pending` or `approved` commission (not yet paid out) is voided outright —
no money has left the business. An already-`paid` commission is voided too, so it stops
counting toward the affiliate's totals, but recovering money already sent to the
affiliate stays a deliberate, manual finance decision — automatically debiting a future
payout would need a running-balance mechanism (and the possibility of a negative payout)
that was out of scope for this pass. Tested against a real database in
`tests/ClawbackTest.php`.

| # | Decision | Default taken |
|---|---|---|
| 6 | Commission on first payment, or all payments? | First only |
| 32 | Default commission rate | 5% |
| 33 | Payout minimum | ₦5,000 |
| 34 | Clawback on refund | **Resolved 21 Aug 2026 — reverse automatically; see above** |
| 35 | Cookie window | 30 days |
