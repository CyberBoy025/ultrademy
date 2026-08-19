# 03 — Roles, Permissions & Centre Scoping

Phase 1 deliverable. Covers §15 (centre-scoped management), §29 (cashier ≠ accountant),
§32 (manager ≠ admin), §33 and §42.

---

## 1. The three questions

Every authorisation check answers three questions in order. If any fails, deny.

```
1. AUTHENTICATED?   Is there a verified user?
2. PERMITTED?       Does any of their roles carry the required permission?
3. IN SCOPE?        Is the target record inside the centre(s) that role covers,
                    or does the user own the record?
```

Question 3 is the one most systems forget, and it is the one §42 is most emphatic
about: *"A centre manager should not automatically see another centre's private
operational data."*

---

## 2. Roles

`roles.is_scopable` marks roles whose assignment may carry a `centre_id`.

| Code | Name | Scopable | Description |
|---|---|---|---|
| `super_admin` | Super Administrator | no | Unrestricted. Two people maximum. |
| `management` | Management | no | Read across all centres, approve high-value actions. |
| `administrator` | Administrator | no | System configuration, content, moderation. |
| `centre_manager` | Centre Manager | **yes** | Full operations for assigned centre(s). |
| `accountant` | Accountant | optional | Full finance. Global unless scoped. |
| `cashier` | Cashier | **yes** | Record and receipt payments at assigned centre. |
| `instructor` | Instructor | **yes** | Own classes, own students. |
| `receptionist` | Receptionist | **yes** | Front desk, applicant intake. |
| `student` | Student | no | Own learning records. Scope is ownership, not centre. |
| `applicant` | Applicant | no | Own applications. |
| `affiliate` | Affiliate | no | Own referrals and commissions. |

**Roles are additive.** A person holding `instructor@Gwagwalada` and `student` sees the
union of both — their own coursework plus the classes they teach at Gwagwalada. This is
the mechanism that delivers §3's "one account, multiple relationships" without
duplicate logins.

`student`, `applicant` and `affiliate` are granted automatically by the system when the
corresponding record is created, and revoked when it closes. They are never assigned by
hand.

---

## 3. Permission naming

`module.resource.action` — lower snake case, always singular resource.

```
admissions.application.review
finance.payment.verify
operations.attendance.mark
education.programme.publish
identity.user.suspend
```

Actions in use: `view`, `view_any`, `create`, `update`, `delete`, `approve`, `reject`,
`publish`, `verify`, `export`, `assign`, `moderate`.

Two conventions that matter:

- **`view` vs `view_any`.** `view` is one record and always runs the scope check.
  `view_any` is a listing and is *always* filtered by scope — it never means "see
  everything".
- **`approve` is never implied by `update`.** Approval is a distinct permission
  everywhere it appears, so an operator who can edit an application cannot also decide
  it.

---

## 4. Scope resolution

```
resolveScope(user, permission) →

  if user has permission globally (user_roles.centre_id IS NULL)
      → GLOBAL          (all records)

  else
      → CENTRES [ids]   (records whose centre_id ∈ ids)

  plus, always
      → OWN             (records the user owns, regardless of centre)
```

Applied to a query:

```sql
-- Centre manager at Gwagwalada listing students
SELECT * FROM enrolments
WHERE centre_id IN (1)                 -- scope
  AND status = 'active';

-- Accountant, global, listing outstanding invoices
SELECT * FROM invoices
WHERE status IN ('issued','part_paid','overdue');   -- no scope clause

-- Student listing their own invoices
SELECT * FROM invoices WHERE user_id = 42;          -- ownership
```

**Enforcement point.** In Laravel this is a global query scope on every centre-bearing
model plus a policy per model — not a `where` clause typed by hand at each call site.
The rule: *if a developer can forget it, it is in the wrong place.*

Records with `centre_id IS NULL` (online cohorts, global invoices) are visible to
GLOBAL scope only. A centre manager does not see online-only revenue. This is a
deliberate reading of §31 and should be confirmed — see Decision 8 below.

---

## 5. Permission matrix

`●` full · `◐` scoped to own centre(s) · `○` own records only · blank = none

### Identity & staff

| Permission | super | mgmt | admin | centre mgr | acct | cashier | instr | student |
|---|---|---|---|---|---|---|---|---|
| `identity.user.view_any` | ● | ● | ● | ◐ | | | ◐ | |
| `identity.user.create` | ● | | ● | | | | | |
| `identity.user.update` | ● | | ● | | | | | |
| `identity.user.suspend` | ● | | ● | | | | | |
| `identity.role.assign` | ● | | ◐ | | | | | |
| `identity.profile.update` | ● | ○ | ○ | ○ | ○ | ○ | ○ | ○ |
| `staff.member.view_any` | ● | ● | ● | ◐ | | | | |
| `staff.member.assign_centre` | ● | ● | ● | | | | | |

> `identity.role.assign` is scoped for administrators: an admin may not grant
> `super_admin`, and may not grant a role broader than one they hold. Privilege
> escalation is blocked at the service, not the UI.

### Education

| Permission | super | mgmt | admin | centre mgr | instr | student |
|---|---|---|---|---|---|---|
| `education.programme.view_any` | ● | ● | ● | ◐ | ◐ | ● (published) |
| `education.programme.create` | ● | | ● | | | |
| `education.programme.update` | ● | | ● | | | |
| `education.programme.approve` | ● | ● | | | | |
| `education.programme.publish` | ● | | ● | | | |
| `education.course.update` | ● | | ● | | ◐ (assigned) | |
| `education.lesson.view` | ● | ● | ● | ◐ | ◐ | ○ (enrolled) |
| `education.assignment.grade` | ● | | | | ◐ | |
| `education.certificate.issue` | ● | ● | ● | | | |

### Admissions

| Permission | super | mgmt | admin | centre mgr | recep | applicant |
|---|---|---|---|---|---|---|
| `admissions.application.view_any` | ● | ● | ● | ◐ | ◐ | ○ |
| `admissions.application.create` | ● | | ● | | ● | ○ |
| `admissions.application.review` | ● | | ● | ◐ | | |
| `admissions.application.approve` | ● | ● | ● | | | |
| `admissions.application.reject` | ● | ● | ● | | | |
| `admissions.enrolment.create` | ● | | ● | ◐ | | |
| `admissions.enrolment.transfer` | ● | ● | | | | |

> **Approve is deliberately not granted to `centre_manager`.** A manager reviews and
> recommends; management or an administrator decides. If UltrAdemy wants managers to
> admit directly, that is Decision 9 — one row in the seed, not a code change.

### Operations

| Permission | super | mgmt | centre mgr | instr | student |
|---|---|---|---|---|---|
| `operations.cohort.manage` | ● | ● | ◐ | | |
| `operations.session.schedule` | ● | | ◐ | ◐ | |
| `operations.attendance.mark` | ● | | ◐ | ◐ | |
| `operations.attendance.view_any` | ● | ● | ◐ | ◐ | ○ |
| `operations.room.manage` | ● | | ◐ | | |
| `operations.equipment.manage` | ● | | ◐ | | |

### Finance — the §29 separation

| Permission | super | mgmt | acct | cashier | centre mgr | student |
|---|---|---|---|---|---|---|
| `finance.invoice.view_any` | ● | ● | ● | ◐ | ◐ | ○ |
| `finance.invoice.create` | ● | | ● | | | |
| `finance.invoice.void` | ● | | ● | | | |
| `finance.payment.record` | ● | | ● | ◐ | | |
| `finance.payment.verify` | ● | | ● | | | |
| `finance.receipt.issue` | ● | | ● | ◐ | | |
| `finance.refund.create` | ● | | ● | | | |
| `finance.refund.approve` | ● | ● | | | | |
| `finance.expense.record` | ● | | ● | | ◐ | |
| `finance.expense.approve` | ● | ● | ● | | | |
| `finance.report.view` | ● | ● | ● | | ◐ | |
| `finance.reconciliation.run` | ● | | ● | | | |

This table is the concrete answer to *"a cashier must NOT automatically have accountant
privileges"*. A cashier can take money and issue a receipt. They cannot **verify** a
bank transfer, void an invoice, raise a refund, or see a report. Verification is the
control that stops a cashier confirming their own fabricated transfer.

Note also that `finance.refund.approve` sits with management, not the accountant who
creates it — the same two-person rule.

### Affiliate, communication, platform

| Permission | super | mgmt | admin | acct | affiliate |
|---|---|---|---|---|---|
| `affiliate.application.approve` | ● | ● | ● | | |
| `affiliate.commission.approve` | ● | ● | | ● | |
| `affiliate.payout.process` | ● | | | ● | |
| `affiliate.referral.view_any` | ● | ● | ● | | ○ |
| `comms.conversation.moderate` | ● | | ● | | |
| `comms.announcement.publish` | ● | ● | ● | | |
| `platform.setting.update` | ● | | ● | | |
| `platform.audit.view` | ● | ● | | | |
| `platform.cms.publish` | ● | | ● | | |

---

## 6. Entitlements are not permissions

Two different gates, both required, easy to conflate:

| | Permission | Entitlement |
|---|---|---|
| Answers | *May this role do it?* | *Does this subscription include it?* |
| Source | `user_roles` → `role_permissions` | `subscriptions` → `package_features` |
| Applies to | staff and students | students and applicants |
| Failure | 403 Forbidden | 402 upgrade prompt |

A PREMIUM student has the *entitlement* for online learning and the *permission* to
view lessons of courses they are enrolled in. Losing either blocks access, and the two
produce different responses — a paywall is not a security error, and showing a security
error where an upgrade prompt belongs is a conversion bug.

---

## 7. Additional guarantees

- **Deny by default.** No permission implies another. `super_admin` short-circuits, and
  that short-circuit is the only one in the system.
- **Self-service is ownership, not permission.** A student updating their own profile
  passes on ownership, so no `student` role needs a global update permission.
- **Cross-student isolation** (§42) falls out of ownership scope: a student's queries
  are constrained to `user_id = self`, so there is no query shape that returns another
  student's records.
- **Impersonation**, if built, is `super_admin` only, time-boxed, and writes an audit
  entry on entry *and* exit. Not in Phase 1 scope.
- **Every permission change is audited** with old and new values (§39).

---

## 8. Open decisions

| # | Decision | Default taken |
|---|---|---|
| 8 | Should a centre manager see online-only revenue and online enrolments? | No — GLOBAL scope only |
| 9 | May a centre manager approve admissions directly, or only recommend? | Recommend only |
| 10 | Is the accountant role global by default, or scoped per centre? | Global |
| 11 | Should instructors see student contact details, or only names and progress? | Names and progress only |

Each is a seed-data change, not a code change — which is the point of putting the
matrix in the database.
