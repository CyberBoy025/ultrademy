# 01 — System Architecture

Phase 1 deliverable. Defines the shape of the system: what the layers are, where
business logic lives, how modules are bounded, and what runs where.

---

## 1. Architectural principles

These are the constraints every later decision is checked against. They come directly
from the brief (§41 API-ready, §42 security, §43 one unified platform, §47 expansion,
§50 development rules).

1. **One identity, many relationships.** A person is one `users` row. Student,
   applicant, affiliate, instructor, cashier are *relationships and roles* attached to
   that row, never separate accounts.
2. **Business logic lives in services, not controllers or views.** A controller
   translates HTTP to a service call and back. The same service is called by the web
   controller and the API controller. This is what makes §41's future mobile apps
   possible without a rewrite.
3. **Authorisation is centralised.** No `if ($user->role === 'admin')` scattered through
   the code. One policy layer, one permission registry, one centre-scope resolver.
4. **Entitlements are data, not code.** Package access is resolved at runtime from the
   database (§7). Adding a feature means inserting a row, not editing a conditional.
5. **Every module owns its tables.** Cross-module reads go through the owning module's
   service, not direct joins into its tables from elsewhere. This keeps §47's "add a
   business unit without rebuilding" credible.
6. **Money is never a float.** Integer minor units (kobo) plus a currency code.
7. **Anything that changes money, permissions or admission status is audited.**

---

## 2. Stack — decision required

**Recommendation: Laravel 11 + MySQL 8, served by XAMPP locally.**

The brief asks for migrations (§50), RBAC with centre scoping (§15, §42), queued
notifications (§37), payment webhooks (§26), an API with token auth for future mobile
apps (§41), and audit trails (§39). Hand-rolling that in vanilla PHP is roughly six
months of building framework before building product, and every piece of it is a place
to introduce a security bug.

| Requirement | Laravel provides | Vanilla PHP cost |
|---|---|---|
| Schema migrations | `artisan migrate` | build a migration runner |
| RBAC + scoping | Policies, Gates, global scopes | build from scratch |
| Queued notifications | Queues, Notification channels | build a worker + retry logic |
| API auth for mobile | Sanctum tokens | build token issue/revoke/refresh |
| Webhook safety | Signed routes, middleware | build signature verification |
| Audit trail | Model observers | manual calls at every write site |
| CSRF, hashing, sessions | Built in, maintained | your own, unmaintained |

Laravel runs on XAMPP without ceremony — point the vhost at `public/`, and `php artisan
serve` works for development. It does not conflict with the XAMPP requirement.

**Cost of the choice:** Composer dependency, a `vendor/` directory, and a learning curve
if the team hasn't used it. **Cost of not choosing it:** every table in the data model
below needs hand-written CRUD, and §42's security checklist becomes your problem alone.

> **DECISION 1 — required before Phase 4.** Laravel, or vanilla PHP? Phases 1–3 are
> unaffected; the domain model, RBAC matrix and entitlement design below are
> framework-agnostic and hold either way. This only becomes blocking at Core Foundation.

Everything from §3 onward is written to survive either answer. Where a name is
Laravel-specific it is marked.

---

## 3. Layers

```
┌─────────────────────────────────────────────────────────────┐
│  DELIVERY                                                    │
│  Public web (guest)  │  Platform web (auth)  │  REST API     │
│  Blade + minimal JS  │  Blade + minimal JS   │  JSON, tokens │
└──────────┬───────────────────┬───────────────────┬──────────┘
           │                   │                   │
┌──────────▼───────────────────▼───────────────────▼──────────┐
│  HTTP LAYER — controllers, form requests, resources          │
│  Thin. Validate input, call a service, shape the response.   │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  AUTHORISATION — policies, permission registry, centre scope │
│  Every service entry point passes through here.              │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  DOMAIN SERVICES — the actual business rules                 │
│  ApplicationService, EnrolmentService, EntitlementService,   │
│  InvoiceService, PaymentService, CommissionService, …        │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  PERSISTENCE — models, query scopes, migrations              │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  INFRASTRUCTURE                                              │
│  MySQL · queue · mail · SMS · Paystack · Flutterwave · files │
└─────────────────────────────────────────────────────────────┘
```

**Cross-cutting, applied at every layer:** audit logging, notification dispatch,
entitlement checks.

---

## 4. Module map

Each module owns its tables and exposes a service. Dependencies point downward only —
a module never reaches up.

```
                    ┌──────────────┐
                    │   IDENTITY   │  users, profiles, roles, permissions
                    └──────┬───────┘
           ┌───────────────┼───────────────┐
           │               │               │
    ┌──────▼─────┐  ┌──────▼─────┐  ┌──────▼──────┐
    │  CENTRES   │  │ SUBSCRIPT. │  │   STAFF     │
    │ rooms      │  │ packages   │  │ instructors │
    │ equipment  │  │ features   │  │ assignments │
    └──────┬─────┘  │ entitlmnts │  └──────┬──────┘
           │        └──────┬─────┘         │
    ┌──────▼─────────────────────────────────────┐
    │            EDUCATION                        │
    │  programmes · courses · modules · lessons   │
    └──────┬──────────────────────────────────────┘
           │
    ┌──────▼──────────┐   ┌─────────────────┐
    │   ADMISSIONS    │   │   OPERATIONS    │
    │ applications    │──▶│ cohorts         │
    │ enrolments      │   │ class sessions  │
    └──────┬──────────┘   │ attendance      │
           │              └─────────────────┘
    ┌──────▼──────┐  ┌───────────┐  ┌──────────────┐
    │   FINANCE   │  │ AFFILIATE │  │ COMMUNICATION│
    │ invoices    │  │ referrals │  │ chat, groups │
    │ payments    │  │ commission│  │ notifications│
    └─────────────┘  └───────────┘  └──────────────┘

    ┌──────────────────────────────────────────────┐
    │  PLATFORM  — audit, settings, reporting, CMS │
    └──────────────────────────────────────────────┘
```

**Dependency rules**

- Identity depends on nothing. Everything may depend on Identity.
- Finance may read Admissions and Subscriptions (what is being paid for) but neither
  may write to Finance directly — they raise an invoice through `InvoiceService`.
- Affiliate observes Finance events; Finance knows nothing about Affiliate.
- Reporting reads everything and writes nothing.
- CMS is isolated — it publishes read-only projections of Education and Centres to the
  public site (§57, §59) and never exposes platform data (§71).

---

## 5. Request lifecycle

Worked example — a student submits a programme application (§10):

```
POST /applications
  │
  ├─ Middleware: authenticate → verify account → resolve active centre scope
  │
  ├─ FormRequest: validate programme_id, preferred_centre_id, documents
  │
  ├─ Policy: ApplicationPolicy@create
  │      · does the user hold the `application.create` permission?
  │      · does their subscription entitle `programme_applications`?  (§7)
  │
  ├─ ApplicationService::submit()
  │      · guard: no existing open application for this programme
  │      · guard: programme is published and open at that centre
  │      · create application (status = submitted)
  │      · attach uploaded documents
  │      · dispatch ApplicationSubmitted event
  │
  ├─ Listeners (queued)
  │      · notify applicant (in-app + email)
  │      · notify centre manager for preferred_centre_id
  │      · write audit_log entry
  │
  └─ Response: 201 + ApplicationResource
```

The web controller and the API controller both call `ApplicationService::submit()`.
They differ only in what they return — a redirect versus JSON.

---

## 6. Public site vs platform (§71)

Two route groups, two middleware stacks, one codebase and one design system.

| | Public | Platform |
|---|---|---|
| Routes | `routes/web-public.php` | `routes/web-app.php` |
| Auth | none | required + verified |
| Data | published projections only | scoped to the actor |
| Caching | aggressive page cache | none |
| Layout | marketing shell | app shell |

The public site reads only rows whose status is `published` and never joins to
enrolments, payments or messages. This is enforced by dedicated read models, not by
remembering to add a `where` clause.

---

## 7. Environments

| | Local | Staging | Production |
|---|---|---|---|
| Host | XAMPP | to be decided | to be decided |
| DB | MySQL 8 (XAMPP) | own instance | own instance, backed up |
| Payments | gateway test keys | test keys | live keys |
| Queue | sync | database | database or Redis |
| Debug | on | on | **off** |

The old LMS database is never a source for staging or production without passing
through the Phase 15 migration scripts (§48).

---

## 8. Non-functional targets

Provisional — these need sign-off, since the brief does not state volumes.

| Concern | Target | Note |
|---|---|---|
| Concurrent users | 500 | assumption; see DECISION 4 |
| Page response | < 400 ms p95 | authenticated dashboard |
| Public page | < 1.5 s first paint on 3G | §74 low-bandwidth |
| Backup | nightly full, 30-day retention | §42 |
| Audit retention | 7 years for financial records | assumption — legal input needed |

---

## 9. Open decisions

| # | Decision | Blocks | Default if unanswered |
|---|---|---|---|
| 1 | Laravel or vanilla PHP | Phase 4 | Laravel |
| 2 | Currency — NGN only, or multi-currency | Finance schema | NGN only, but schema carries a currency column |
| 3 | Does an applicant pay an application fee, or only on admission | Admissions + Finance | fee on admission only |
| 4 | Expected user volume in year one | infrastructure sizing | 500 concurrent |
| 5 | Are certificates verifiable by public serial lookup | Education + public site | yes, serial lookup page |
| 6 | Commission basis — first payment, or all payments by the referred user | Affiliate | first payment only |
| 7 | Can a student hold enrolments at two centres simultaneously | Admissions | yes, model allows it |

Items 2, 3, 5, 6 and 7 change table columns. Answering them before Phase 4 avoids a
migration later.
