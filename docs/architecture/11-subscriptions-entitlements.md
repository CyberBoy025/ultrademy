# Phase 6 — Subscriptions & Entitlements

Status: **built, running against the live database.** Implements
[04-subscriptions-entitlements.md](04-subscriptions-entitlements.md) (README §6, §7).
Decisions 12–16 were unanswered, so the documented defaults were taken — see §6 below.

---

## 1. What was built

| Area | File(s) |
|---|---|
| Entitlement engine | [`app/core/Entitlements.php`](../../app/core/Entitlements.php) |
| Schema | migrations [`021`](../../database/migrations/021_create_features.sql)–[`025`](../../database/migrations/025_create_entitlement_overrides.sql) |
| Models | [`Feature`](../../app/models/Feature.php), [`Package`](../../app/models/Package.php), [`Subscription`](../../app/models/Subscription.php), [`EntitlementOverride`](../../app/models/EntitlementOverride.php) |
| Package + feature-matrix admin | [`PackageController`](../../app/controllers/PackageController.php), `packages/index`, `packages/show` |
| Subscriptions (user + admin) | [`SubscriptionController`](../../app/controllers/SubscriptionController.php), `subscriptions/mine`, `subscriptions/index`, `subscriptions/overrides` |
| Paywall | [`app/views/errors/402.php`](../../app/views/errors/402.php) |
| First gated feature | `My Calendar` — [`OperationsController::calendar()`](../../app/controllers/OperationsController.php), `calendar/index` |
| Expiry job | [`database/expire-subscriptions.php`](../../database/expire-subscriptions.php) |

## 2. The rule this phase exists to enforce

§1 of the architecture doc bans `if ($package === 'premium')`. Nothing in this codebase
names a package. The only places tier names appear are seed data, the admin UI, and
this document. Application code asks one question:

```php
Entitlements::can('assignments');          // may they?
Entitlements::withinLimit('chat_groups', $n); // metered — is there room?
Entitlements::requireFeature('calendar');  // gate — 402 if not
```

**This was verified, not assumed.** Adding `premium_resources` to the Premium package
*through the admin feature-matrix form* immediately gave a Premium subscriber that
capability, with no deployment and no code edit:

```
kelvin premium_resources BEFORE : false
save feature matrix -> 302
kelvin premium_resources AFTER  : true
```

## 3. Resolution order

Per §5, implemented in `Entitlements::resolve()`:

1. Features from the user's **active** subscription's package (with limits).
2. **Staff implicit grants** — features in modules `operations` and `comms`, because an
   instructor should not have to buy Premium to see the class they teach (Decision 16).
   These only *add*; they never overwrite a metered limit the package already set.
3. **Per-user overrides**, which win over both. `granted = 1` adds, `granted = 0` removes
   even if the package includes it. Rows past `expires_at` are ignored.

Verified across four user shapes:

| User | Resolves to |
|---|---|
| Premium student | 11 features, incl. `chat_groups(10)`, `programme_applications(3)`, `file_storage(10 GB)` |
| Student, no subscription | *nothing* |
| Instructor (staff) | `calendar`, `events`, `chat_direct`, `chat_groups` — no education features |
| Super admin | same as instructor — **staff status is not a licence to premium content** |

That last row matters: Decision 16 says staff get *operational* features only. A super
admin has unlimited **permissions** and still no `premium_resources` **entitlement**,
because those are two different systems (03-rbac.md §6).

## 4. Entitlements vs permissions, kept apart

| | Permission | Entitlement |
|---|---|---|
| Asks | may this **role** do it? | does this **subscription** include it? |
| Fails with | **403** Access Denied | **402** upgrade prompt |
| Enforced by | `Auth::requirePermission()` | `Entitlements::requireFeature()` |

Confirmed live: a student with no subscription hitting `?r=calendar` gets **HTTP 402**
and an upgrade page — not a security error. After activation the same URL returns
**200**. After the expiry job runs, it returns **402** again.

## 5. Lifecycle

`pending → active → expired | cancelled`, plus `void` for a failed payment.

One documentation conflict had to be resolved. §7's **diagram** shows
`ACTIVE --cancels--> CANCELLED`, but the **prose** directly beneath says cancelling
"leaves the subscription active until `ends_at` … The customer paid for the period."
The implementation follows the prose, since that states a business rule:

- `cancel()` — sets `cancelled_at` and `auto_renew = 0`; status stays `active`, access continues.
- expiry job — at `ends_at`, the row becomes `cancelled` if it was cancelled, else `expired`.

So the terminal status still records *why* it ended without cutting off paid-for access.
This reading is recorded in the `Subscription` class docblock too, so the next person
does not have to re-derive it.

**Activation is manual in this phase.** §7 requires a subscription to become active only
when its invoice is *paid* — but invoices are Phase 9. Rather than fake a payment, the
`subscriptions.subscription.activate` permission sits with the **accountant** (who will
own payment verification per 05-finance-payments.md) and the admin UI says plainly that
this becomes automatic in Phase 9.

**Expiry is a real scheduled job**, not a computed property, exactly as §7 demands — so
that expiry events can fire. Run daily:

```bash
php C:\xampp\htdocs\ultra\database\expire-subscriptions.php
```

It is idempotent and logs to `audit_logs` as a system action. The notification hook is a
marked `TODO` pointing at Phase 10 rather than pretending a message was sent.

## 6. Decisions 12–16 — defaults taken

The architecture README states every unanswered default gets implemented. None were
answered, so:

| # | Decision | Implemented as |
|---|---|---|
| 12 | Grace period after expiry | **None** — hard stop at `ends_at` |
| 13 | Proration on mid-cycle upgrade | **None** — activating supersedes the old subscription and starts a full new period |
| 14 | Two active subscriptions at once | **No** — enforced by the *database*, not app code (see below) |
| 15 | `affiliate_programme` on Basic | **Yes** — present at every tier |
| 16 | Staff get all student features | **No** — `operations` + `comms` modules only |

Decision 14 is enforced by a generated column plus a unique index, so it cannot be
bypassed by a code path that forgets to check:

```sql
active_user_id BIGINT UNSIGNED AS (IF(status = 'active', user_id, NULL)) STORED,
UNIQUE KEY uq_one_active_subscription (active_user_id)
```

NULLs do not collide in a unique index, so only `active` rows are constrained. Verified
by attempting a direct second insert — rejected with `uq_one_active_subscription`.

## 7. Two bugs found and fixed while testing

**Cross-centre leak on the Attendance page.** `operations.attendance.view_any` is graded
`◐` (centre-scoped) for staff but `○` (own records only) for students in 03-rbac.md §5.
`Auth::scopeCentres()` models GLOBAL vs CENTRES and has *no concept of ownership*, so a
student's unscoped grant resolved to GLOBAL — every centre's timetable.

It looked fine because the seed only had one cohort. Adding a second cohort at Kubwa
made the leak visible immediately: the Gwagwalada student's page listed it. Fixed by
applying ownership scope for non-staff (`ClassSession::forUser()`), per §7's rule that
"a student's queries are constrained to `user_id = self`". Re-verified afterwards:
student sees only their own cohort, instructor still centre-scoped, super admin still
global.

This is the same class of bug found in Phase 4 on the Users page — twice now, a
listing defaulted to global for a role the matrix marks as restricted. Worth treating
as a known trap in the remaining phases.

**Seed multiplied the timetable.** `class_sessions` had no unique key, so `INSERT IGNORE`
had nothing to match and every seed re-run added duplicate sessions (6 rows for 2 real
sessions). Migration [`026`](../../database/migrations/026_dedupe_class_sessions.sql)
deduplicates (keeping the row attendance already references) and adds
`UNIQUE (class_group_id, starts_at)` — which also prevents genuinely double-booking a
class group. Seed re-runs are now idempotent.

## 8. Deviations from the architecture doc

| Doc says | Built as | Why |
|---|---|---|
| Cache entitlements 15 min, keyed `entitlements:{user_id}` | Per-request memo only, with explicit `flush()` calls | There is no cache backend in this stack (no Redis/Memcached), and at the documented year-one volume — hundreds of users — a per-request memo is sufficient. The invalidation points the doc lists are all implemented as `Entitlements::flush()` calls, so adding a real cache later is a change inside one class. |
| Laravel route middleware, Blade `@entitled` | `Entitlements::requireFeature()` in the controller; `Entitlements::can()` in nav/views | Decision 1 chose vanilla PHP; the doc's snippets are Laravel. The three-layer model (route → service → view) is preserved in substance: the controller gate is the boundary, the nav check is presentation only. |
| `subscriptions.status` enum omits `void` | `void` included | §7's lifecycle diagram has a VOID state for failed payment; the §6 ERD simply missed it. |

## 9. What Phase 6 deliberately does not build

- **No payment.** Requesting a package creates a `pending` row that grants nothing.
  Paystack/Flutterwave and invoice-driven activation are Phase 9.
- **No metered enforcement in anger.** `withinLimit()` is implemented and unit-verified,
  but the things it would meter (`programme_applications`, `chat_groups`, `file_storage`)
  belong to Phases 7, 10 and later. Wiring it to a counter that does not exist would be
  theatre.
- **Only one feature is actually gated today** — `calendar`, via My Calendar. The other
  twelve registry entries are sellable capabilities whose features do not exist yet;
  each gets its `requireFeature()` call in the phase that builds it.
- **No public pricing page.** The Phase 2 marketing site still has no packages section;
  wiring it to the `packages` table is a small follow-up.

## 10. Demo state

`blessing.eze@ultrademy.com` holds an active **Premium** subscription;
`kelvin.musa@ultrademy.com` deliberately holds **none**, so both the granted and the
paywalled paths are reachable from the seed without any setup.
