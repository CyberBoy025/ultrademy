# 02 — Domain Model & ERD

Phase 1 deliverable. The database designed from the business domain (§40), not carried
over from the old LMS.

**Conventions used throughout**

- `id` — unsigned big integer, auto-increment, primary key
- `*_id` — foreign key; `RESTRICT` on delete unless stated
- Money — integer **minor units** (kobo) in `*_amount`, paired with `currency` (ISO
  4217). Never a float.
- Timestamps — UTC, `created_at` / `updated_at` on every table
- Soft deletes only where history matters (users, programmes, invoices); hard delete
  elsewhere
- Status columns are constrained enums, listed per table

---

## 1. Identity & access

One person, one row. Roles attach with an optional centre scope.

```mermaid
erDiagram
    users ||--o| user_profiles : has
    users ||--o{ user_roles : "assigned"
    roles ||--o{ user_roles : "granted via"
    roles ||--o{ role_permissions : has
    permissions ||--o{ role_permissions : "in"
    centres ||--o{ user_roles : "scoped to"
    users ||--o{ audit_logs : performed

    users {
        bigint id PK
        string email UK
        string phone UK "nullable"
        string password_hash
        string status "pending|active|suspended|closed"
        timestamp email_verified_at
        timestamp phone_verified_at
        timestamp last_login_at
    }
    user_profiles {
        bigint user_id PK "also FK to users"
        string first_name
        string last_name
        date date_of_birth
        string gender "nullable"
        string address_line
        string city
        string state
        string country
        string photo_path
        int completion_pct "derived"
    }
    roles {
        bigint id PK
        string code UK "super_admin|management|centre_manager|..."
        string name
        bool is_scopable "may be centre-scoped"
    }
    permissions {
        bigint id PK
        string code UK "resource.action"
        string module
    }
    user_roles {
        bigint id PK
        bigint user_id FK
        bigint role_id FK
        bigint centre_id FK "nullable = global"
        timestamp granted_at
        bigint granted_by FK
    }
```

**Why `user_roles` carries `centre_id`:** §15 requires the Gwagwalada manager to manage
Gwagwalada and not Kubwa. Putting the scope on the *assignment* rather than the user
means the same person can be centre manager at one hub and an instructor at another,
which the brief's "one account, many relationships" principle demands.

---

## 2. Centres & facilities

```mermaid
erDiagram
    centres ||--o{ rooms : contains
    centres ||--o{ equipment : holds
    rooms ||--o{ equipment : "located in"
    centres ||--o{ staff_centres : staffs
    users ||--o{ staff_centres : "posted to"

    centres {
        bigint id PK
        string code UK "GWG|KBW"
        string name
        string slug UK
        string address_line
        string city
        string state
        decimal latitude "nullable"
        decimal longitude "nullable"
        string phone
        string email
        bigint manager_user_id FK "nullable"
        string status "active|inactive|planned"
    }
    rooms {
        bigint id PK
        bigint centre_id FK
        string name
        string type "classroom|computer_lab|office|hall"
        int capacity
        string status "available|maintenance|retired"
    }
    equipment {
        bigint id PK
        bigint centre_id FK
        bigint room_id FK "nullable"
        string asset_tag UK
        string name
        string status "in_service|repair|retired"
        date acquired_on
    }
    staff_centres {
        bigint id PK
        bigint user_id FK
        bigint centre_id FK
        bool is_primary
    }
```

Gwagwalada and Kubwa are **seed rows**, not constants (§12, §45). Nothing in code
references them by name.

`ONLINE` is deliberately *not* a centre row. Online delivery is `delivery_mode` on the
programme and a `NULL` `centre_id` on the cohort. Modelling it as a fake centre would
corrupt every centre-based financial and attendance report (§31).

---

## 3. Education

```mermaid
erDiagram
    programme_categories ||--o{ programmes : classifies
    programmes ||--o{ programme_centres : "offered at"
    centres ||--o{ programme_centres : hosts
    programmes ||--o{ programme_courses : includes
    courses ||--o{ programme_courses : "part of"
    courses ||--o{ modules : contains
    modules ||--o{ lessons : contains
    lessons ||--o{ lesson_materials : has
    courses ||--o{ assignments : sets
    courses ||--o{ assessments : sets
    assessments ||--o{ assessment_questions : contains
    assessment_questions ||--o{ assessment_options : offers

    programmes {
        bigint id PK
        string code UK
        string title
        string slug UK
        bigint category_id FK
        text description
        text objectives
        text requirements
        int duration_weeks
        string delivery_mode "physical|online|hybrid"
        bigint fee_amount
        string currency
        int capacity
        bool grants_certificate
        string status "draft|pending_approval|approved|published|archived"
        timestamp published_at
    }
    courses {
        bigint id PK
        string title
        string slug UK
        text description
        int estimated_minutes
        bool standalone "sellable outside a programme"
        string status "draft|published|archived"
    }
    modules {
        bigint id PK
        bigint course_id FK
        string title
        int sort_order
    }
    lessons {
        bigint id PK
        bigint module_id FK
        string title
        string content_type "video|text|document|link"
        int duration_minutes
        int sort_order
        bool is_preview "visible to non-enrolled"
    }
    lesson_materials {
        bigint id PK
        bigint lesson_id FK
        string type "video|document|link"
        string path_or_url
        bigint size_bytes
    }
```

**Programme ≠ course.** A programme is what you apply and pay for; a course is
teachable content. `programme_courses` is many-to-many so one course can serve several
programmes without duplication (§9).

`courses.standalone` supports §6's PREMIUM package — online courses consumed by
subscription, with no application or cohort involved.

---

## 4. Admissions & enrolment

```mermaid
erDiagram
    users ||--o{ applications : submits
    programmes ||--o{ applications : "applied for"
    centres ||--o{ applications : "preferred"
    applications ||--o{ application_documents : attaches
    applications ||--o| enrolments : becomes
    cohorts ||--o{ enrolments : admits
    programmes ||--o{ cohorts : runs
    centres ||--o{ cohorts : hosts
    users ||--o{ enrolments : holds
    enrolments ||--o{ certificates : earns

    applications {
        bigint id PK
        string reference UK
        bigint user_id FK
        bigint programme_id FK
        bigint preferred_centre_id FK "nullable if online"
        bigint cohort_id FK "nullable until assigned"
        string status "draft|submitted|under_review|approved|rejected|withdrawn"
        timestamp submitted_at
        bigint reviewed_by FK
        timestamp reviewed_at
        text decision_note
    }
    application_documents {
        bigint id PK
        bigint application_id FK
        string type "id_card|certificate|passport_photo|other"
        string path
        string status "pending|accepted|rejected"
        text reviewer_note
    }
    cohorts {
        bigint id PK
        bigint programme_id FK
        bigint centre_id FK "nullable = online"
        string code UK
        string name
        date starts_on
        date ends_on
        int capacity
        string status "planned|open|running|completed|cancelled"
    }
    enrolments {
        bigint id PK
        string student_no UK
        bigint user_id FK
        bigint programme_id FK
        bigint cohort_id FK
        bigint centre_id FK "nullable, denormalised from cohort"
        bigint application_id FK "nullable — direct enrolment possible"
        string status "pending_payment|active|suspended|withdrawn|completed"
        timestamp enrolled_at
        timestamp completed_at
    }
```

`enrolments` is the join between a person and a centre (§14). The user row carries no
centre. A student who transfers gets a new enrolment row; the old one stays for history.

`centre_id` is denormalised onto `enrolments` deliberately — every attendance and
finance report filters by centre, and the alternative is a three-table join on the
hottest query in the system. It is written once at enrolment and never edited; a
transfer creates a new row.

---

## 5. Operations — timetable, attendance, progress

```mermaid
erDiagram
    cohorts ||--o{ class_groups : "divided into"
    class_groups ||--o{ class_sessions : schedules
    rooms ||--o{ class_sessions : "held in"
    users ||--o{ class_groups : instructs
    class_sessions ||--o{ attendance_records : records
    enrolments ||--o{ attendance_records : "marked for"
    enrolments ||--o{ lesson_progress : tracks
    lessons ||--o{ lesson_progress : "progress on"

    class_groups {
        bigint id PK
        bigint cohort_id FK
        bigint instructor_user_id FK
        string name
        int capacity
    }
    class_sessions {
        bigint id PK
        bigint class_group_id FK
        bigint room_id FK "nullable = online"
        bigint lesson_id FK "nullable"
        string topic
        timestamp starts_at
        timestamp ends_at
        string mode "physical|online"
        string status "scheduled|held|cancelled"
    }
    attendance_records {
        bigint id PK
        bigint class_session_id FK
        bigint enrolment_id FK
        string status "present|late|absent|excused"
        bigint marked_by FK
        timestamp marked_at
    }
    lesson_progress {
        bigint id PK
        bigint enrolment_id FK "nullable for standalone courses"
        bigint user_id FK
        bigint lesson_id FK
        int progress_pct
        timestamp completed_at
    }
```

`class_sessions` *is* the timetable (§17) and *is* the source of calendar events (§22).
There is no separate timetable table — a second representation of the same fact would
drift.

Unique constraint on `(class_session_id, enrolment_id)` makes attendance idempotent.

---

## 6. Subscriptions & entitlements

Detailed in `04-subscriptions-entitlements.md`; shown here for completeness.

```mermaid
erDiagram
    packages ||--o{ package_features : grants
    features ||--o{ package_features : "granted by"
    packages ||--o{ subscriptions : "sold as"
    users ||--o{ subscriptions : holds
    users ||--o{ entitlement_overrides : "granted directly"
    features ||--o{ entitlement_overrides : overrides

    packages {
        bigint id PK
        string code UK "basic|standard|premium|advanced"
        string name
        bigint price_amount
        string currency
        string billing_period "monthly|quarterly|annual|one_off"
        int duration_days
        string status "draft|active|retired"
        int sort_order
    }
    features {
        bigint id PK
        string code UK "calendar|programme_applications|online_learning|..."
        string name
        string module
        string limit_type "none|count|bytes"
    }
    package_features {
        bigint id PK
        bigint package_id FK
        bigint feature_id FK
        bigint limit_value "nullable = unlimited"
    }
    subscriptions {
        bigint id PK
        bigint user_id FK
        bigint package_id FK
        string status "pending|active|expired|cancelled"
        timestamp starts_at
        timestamp ends_at
        bool auto_renew
        timestamp cancelled_at
    }
    entitlement_overrides {
        bigint id PK
        bigint user_id FK
        bigint feature_id FK
        bool granted "true=grant false=revoke"
        timestamp expires_at
        string reason
        bigint granted_by FK
    }
```

---

## 7. Finance

Detailed in `05-finance-payments.md`.

```mermaid
erDiagram
    users ||--o{ invoices : owes
    invoices ||--o{ invoice_lines : "itemised by"
    invoices ||--o{ payments : "settled by"
    payments ||--o| receipts : produces
    payments ||--o{ payment_proofs : evidenced
    payments ||--o{ refunds : refunded
    centres ||--o{ expenses : incurs
    centres ||--o{ invoices : "attributed to"

    invoices {
        bigint id PK
        string number UK
        bigint user_id FK
        string payable_type "enrolment|subscription|application_fee"
        bigint payable_id
        bigint centre_id FK "nullable = online/global"
        bigint subtotal_amount
        bigint discount_amount
        bigint total_amount
        string currency
        string status "draft|issued|part_paid|paid|void|overdue"
        date due_on
        timestamp issued_at
    }
    payments {
        bigint id PK
        string reference UK
        bigint invoice_id FK
        bigint user_id FK
        string method "paystack|flutterwave|bank_transfer|cash"
        string gateway_reference "nullable"
        bigint amount
        string currency
        string status "initiated|pending_verification|successful|failed|reversed"
        timestamp paid_at
        bigint verified_by FK "nullable"
        timestamp verified_at
        bigint centre_id FK "nullable"
        json meta
    }
    expenses {
        bigint id PK
        bigint centre_id FK "nullable = head office"
        string category
        bigint amount
        string currency
        text description
        date incurred_on
        bigint recorded_by FK
        bigint approved_by FK "nullable"
        string status "draft|submitted|approved|rejected"
    }
```

`payable_type` + `payable_id` is a deliberate polymorphic reference: an invoice may be
for an enrolment, a subscription, or an application fee, and §46 will add corporate
contracts. A nullable FK per type would mean a schema change per new payable.

---

## 8. Affiliate

```mermaid
erDiagram
    users ||--o| affiliates : "registered as"
    affiliates ||--o{ referrals : generates
    users ||--o| referrals : "referred as"
    referrals ||--o{ commissions : earns
    affiliates ||--o{ payouts : "paid via"

    affiliates {
        bigint id PK
        bigint user_id UK "FK to users"
        string code UK "referral code"
        string status "applied|under_review|approved|suspended"
        int commission_rate_bps "basis points"
        bigint approved_by FK
        timestamp approved_at
    }
    referrals {
        bigint id PK
        bigint affiliate_id FK
        bigint referred_user_id UK "FK to users"
        timestamp landed_at
        timestamp registered_at
        timestamp qualified_at "nullable"
        string status "pending|qualified|void"
    }
    commissions {
        bigint id PK
        bigint affiliate_id FK
        bigint referral_id FK
        bigint payment_id FK "the qualifying payment"
        bigint amount
        string currency
        string status "pending|approved|paid|void"
        bigint approved_by FK
        timestamp approved_at
    }
    payouts {
        bigint id PK
        bigint affiliate_id FK
        bigint amount
        string currency
        string method
        string reference
        string status "requested|processing|paid|failed"
        timestamp paid_at
    }
```

`referrals.referred_user_id` is unique — a person can be referred once, ever. This
closes the obvious fraud path of re-attributing an existing user to a new affiliate.

Commission rate is stored in **basis points** (integer) so 2.5% is `250` and no float
rounding enters the money path.

---

## 9. Communication

```mermaid
erDiagram
    conversations ||--o{ conversation_participants : includes
    users ||--o{ conversation_participants : "member of"
    conversations ||--o{ messages : carries
    users ||--o{ messages : sends
    users ||--o{ notifications : receives
    cohorts ||--o| conversations : "auto-group"

    conversations {
        bigint id PK
        string type "direct|group|programme_cohort"
        string title "nullable for direct"
        bigint cohort_id FK "nullable"
        bigint created_by FK
        bool is_moderated
    }
    conversation_participants {
        bigint id PK
        bigint conversation_id FK
        bigint user_id FK
        string role "member|moderator"
        timestamp joined_at
        timestamp muted_until
        timestamp last_read_at
    }
    messages {
        bigint id PK
        bigint conversation_id FK
        bigint sender_id FK
        text body
        string attachment_path "nullable"
        timestamp edited_at
        timestamp deleted_at
        bigint deleted_by FK "moderation"
    }
    notifications {
        bigint id PK
        bigint user_id FK
        string type
        string title
        text body
        json data
        string channel "in_app|email|sms"
        timestamp read_at
        timestamp sent_at
    }
```

Deleting a message is a soft delete with `deleted_by`, because §39 requires moderation
actions to be auditable and a hard delete destroys the evidence.

---

## 10. Platform — audit, settings, CMS

```mermaid
erDiagram
    users ||--o{ audit_logs : performs
    posts ||--o{ post_categories : "filed under"
    testimonials }o--|| users : "from"

    audit_logs {
        bigint id PK
        bigint actor_user_id FK "nullable = system"
        string action "application.approved|payment.verified|..."
        string auditable_type
        bigint auditable_id
        json old_values
        json new_values
        string ip_address
        string user_agent
        bigint centre_id FK "nullable, for scoped filtering"
        timestamp created_at
    }
    settings {
        bigint id PK
        string key UK
        json value
        string group
        bool is_public
    }
    posts {
        bigint id PK
        string title
        string slug UK
        text excerpt
        longtext body
        string cover_path
        bigint author_id FK
        string status "draft|review|published"
        timestamp published_at
    }
    testimonials {
        bigint id PK
        bigint user_id FK "nullable"
        string author_name
        string author_role
        text body
        string status "submitted|approved|published|rejected"
        bigint approved_by FK
    }
```

`audit_logs` has no update or delete path in application code — insert only.

---

## 11. Indexing notes

The queries that will hurt first, and what they need:

| Query | Index |
|---|---|
| Student dashboard — my enrolments | `enrolments (user_id, status)` |
| Centre manager — students at my centre | `enrolments (centre_id, status)` |
| Attendance sheet for a session | `attendance_records (class_session_id)` |
| Calendar — my sessions this month | `class_sessions (class_group_id, starts_at)` |
| Outstanding balances by centre | `invoices (centre_id, status, due_on)` |
| Webhook idempotency | `webhook_events (provider, event_id)` UNIQUE |
| Referral attribution | `referrals (referred_user_id)` UNIQUE |
| Audit lookup for a record | `audit_logs (auditable_type, auditable_id, created_at)` |

`audit_logs` will become the largest table. Plan monthly partitioning or an archive job
before it passes ~10M rows.

---

## 12. What is deliberately absent

Named so nobody adds them by reflex:

- **No `students` table.** A student is a user with an active enrolment.
- **No `applicants` table.** An applicant is a user with an open application.
- **No `online` centre row.** See §2 above.
- **No `timetables` table.** `class_sessions` is the timetable.
- **No `roles` column on `users`.** Roles live in `user_roles` with scope.
- **No corporate training tables yet** (§46). The polymorphic invoice and the
  centre-nullable cohort already accommodate them; tables get added in Phase 13 rather
  than sitting empty for a year.
