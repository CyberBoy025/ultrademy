# 04 — Subscription & Entitlement Architecture

Phase 1 deliverable. Covers §6 (packages) and §7 (*"do NOT hard-code package access
throughout the application"*).

---

## 1. The rule this design exists to enforce

The failure mode §7 is warning about looks like this:

```php
if ($user->package === 'premium' || $user->package === 'advanced') {
    // show assignments
}
```

Every one of those is a place that breaks when a package is renamed, a fifth tier is
added, or one customer gets a bespoke arrangement. The replacement is a single question
asked everywhere:

```php
if ($user->can_use('assignments')) { … }
```

The application never names a package. Only the seed data and the admin UI do.

---

## 2. Four concepts, kept separate

```
  PACKAGE          what is sold          "Premium — ₦15,000/month"
     │
     ▼
  FEATURE          what can be done      "assignments"
     │
     ▼
  ENTITLEMENT      resolved answer       "this user, right now, may use assignments"
     │
     ▼
  LIMIT            how much              "10 GB storage" / "3 programmes"
```

Packages change constantly. Features change rarely. The application code depends only
on **features**, so pricing can be reorganised without touching a line of it.

---

## 3. Feature registry

`features` is a controlled vocabulary. Adding a row makes a new capability sellable;
removing one is a migration. Initial registry, drawn from §7:

| Code | Module | Limit type |
|---|---|---|
| `calendar` | operations | none |
| `programme_applications` | admissions | count (concurrent) |
| `online_learning` | education | none |
| `assignments` | education | none |
| `assessments` | education | none |
| `certificates` | education | none |
| `chat_direct` | comms | none |
| `chat_groups` | comms | count |
| `affiliate_programme` | affiliate | none |
| `premium_resources` | education | none |
| `events` | operations | none |
| `special_programmes` | education | none |
| `file_storage` | platform | bytes |

`limit_type` drives how `package_features.limit_value` is interpreted: `none` means the
feature is on/off, `count` and `bytes` mean it is metered.

**Naming rule:** a feature code describes *a capability*, never a package tier and
never a UI element. `online_learning`, not `premium_tab`.

---

## 4. Example package configuration

Seed data, not code. Administrators edit this through the admin UI (§6).

| Feature | Basic | Standard | Premium | Advanced |
|---|---|---|---|---|
| `calendar` | — | ✓ | ✓ | ✓ |
| `programme_applications` | — | 1 | 3 | unlimited |
| `online_learning` | — | ✓ | ✓ | ✓ |
| `assignments` | — | — | ✓ | ✓ |
| `assessments` | — | — | ✓ | ✓ |
| `certificates` | — | — | ✓ | ✓ |
| `chat_direct` | ✓ | ✓ | ✓ | ✓ |
| `chat_groups` | — | 2 | 10 | unlimited |
| `affiliate_programme` | ✓ | ✓ | ✓ | ✓ |
| `premium_resources` | — | — | — | ✓ |
| `file_storage` | 100 MB | 1 GB | 10 GB | 50 GB |

A blank `limit_value` means unlimited; absence of the row means the feature is off.
Note that `affiliate_programme` is on at every tier — earning UltrAdemy referrals should
not be gated (§25), but that is a business call worth confirming.

---

## 5. Resolution

```
EntitlementService::resolve(user) →

  1. features granted by the user's ACTIVE subscription's package
        subscriptions.status = 'active' AND now() BETWEEN starts_at AND ends_at

  2. apply entitlement_overrides for this user
        granted = true   → add (staff comp, promotional grant, corporate deal)
        granted = false  → remove (abuse sanction)
        ignore rows where expires_at < now()

  3. apply role-based implicit grants
        staff roles get operational features regardless of subscription
        — an instructor does not buy Premium to mark attendance

  → EntitlementSet { feature_code => limit|UNLIMITED }
```

Cached per user, keyed `entitlements:{user_id}`, TTL 15 minutes. Invalidated
immediately on: subscription created, renewed, expired or cancelled; override written;
package features edited (flushes every user on that package); role assigned or revoked.

The 15-minute TTL is a safety net, not the mechanism. Correctness comes from
invalidation; the TTL only bounds the damage if an invalidation is missed.

---

## 6. Enforcement points

Three layers, all three required. Any one alone is a hole.

**Route middleware** — coarse, cheap, catches whole sections:

```php
Route::middleware('entitled:online_learning')->group(function () {
    Route::get('/learn/{course}', …);
});
```

**Service guard** — the real boundary. This is what protects the API and any future
mobile client:

```php
public function submit(User $user, Assignment $a, array $payload): Submission
{
    $this->entitlements->require($user, 'assignments');   // throws 402
    …
}
```

**View** — presentation only, never security:

```blade
@entitled('certificates')
    <a href="{{ route('certificates.index') }}">My Certificates</a>
@else
    <x-upgrade-prompt feature="certificates" />
@endentitled
```

The view check exists so the UI degrades gracefully into an upgrade prompt rather than a
dead link. It is not a gate — the service guard is.

**Metered features** additionally check consumption:

```php
$this->entitlements->requireWithin($user, 'programme_applications',
    current: $user->openApplications()->count());
```

---

## 7. Lifecycle

```
 PENDING ──payment verified──▶ ACTIVE ──ends_at passes──▶ EXPIRED
    │                            │                          │
    │                            ├──user cancels──▶ CANCELLED│
    │                            │   (runs to ends_at)       │
    └──payment fails──▶ VOID     └──renewal succeeds──▶ ACTIVE (ends_at extended)
```

Rules:

- A subscription becomes `active` only when its invoice is **paid**, never on
  initiation. This is why `subscriptions.status` starts at `pending`.
- Cancelling sets `cancelled_at` and `auto_renew = false` but leaves the subscription
  active until `ends_at`. The customer paid for the period.
- Expiry is a scheduled job, not a computed property, so that expiry **events** fire and
  notifications go out (§37).
- A grace period is possible (`ends_at + N days`) but is not in the default design —
  see Decision 12.

**What happens to work in progress when a subscription lapses:** access to the feature
stops; data is retained. An expired Premium student keeps their submitted assignments
and certificates, and regains access on renewal. Nothing is deleted on expiry — that is
a hard rule, because deleting a student's coursework over a missed payment is not
recoverable.

---

## 8. Interaction with programme enrolment

These are separate purchases and must not be conflated:

| | Subscription | Enrolment |
|---|---|---|
| Buys | platform features | a specific programme |
| Billed | recurring | one-off, or instalments |
| Ends | on expiry | on completion or withdrawal |
| Gates | `can_use('assignments')` | `is_enrolled($programme)` |

A student may hold a Premium subscription and no enrolment (self-directed online
learning), or an enrolment and only Basic (physical training at Gwagwalada, no online
extras). Both are valid and the system must not assume one implies the other.

> **DECISION 3 restated** — does enrolling in a programme require an active
> subscription? Default taken: **no**. Physical training is sold on its own. If
> UltrAdemy wants programmes to require at least Standard, that is one guard in
> `EnrolmentService`, but it needs deciding before Phase 7.

---

## 9. Open decisions

| # | Decision | Default taken |
|---|---|---|
| 12 | Grace period after expiry before access is cut? | None — hard stop at `ends_at` |
| 13 | Proration on mid-cycle upgrade? | None — new period starts at upgrade |
| 14 | Can a user hold two active subscriptions? | No — one active per user |
| 15 | Is `affiliate_programme` available on Basic? | Yes |
| 16 | Do staff get all student features implicitly? | Operational features only, not `premium_resources` |
