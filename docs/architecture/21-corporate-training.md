# 21 — Corporate Training

Phase 13. README §46, plus §58's "Corporate Training" delivery mode on the public site.

Migrations 097–104, `app/models/Corporate.php`, `app/controllers/CorporateController.php`,
`public/corporate.php`, `public/corporate-invite.php`, `app/views/corporate/`.

---

## 1. The chain

```
ORGANISATION ──▶ TRAINING REQUEST ──▶ PROPOSAL ──▶ CONTRACT ──▶ PARTICIPANTS ──▶ REPORT
                  (enquiry)          (quotation)   + cohort      (invited,        (attendance,
                                                   + invoice      enrolled)        assessment,
                                                                                   certificates)
```

Two decisions hold the module together, and both are about *not* building a parallel
system.

**A contract is delivered through a real cohort.** Corporate participants get ordinary
enrolments and flow through the same attendance, assessment and certificate machinery as
everyone else. A separate "corporate learning" path would mean two of everything, and the
second copy always rots.

**A contract is invoiced through the ordinary invoice spine**, with
`payable_type = 'corporate_contract'`. Migration 103 is one `ALTER` adding an enum value —
the fourth payable kind, and the fourth time that column has absorbed a new business model
without a new money path.

---

## 2. An organisation is not a user

A bank is a counterparty; the people at it are users. Inventing a fake account to
represent a company would put it in student lists, notification audiences and enrolment
counts.

`organisation_contacts.user_id` is nullable — an HR manager who only appears on an email
thread needs no account. It is populated when they need one: to receive an invoice, or to
read the report.

---

## 3. Nominating is not consent

An employer supplying a name and an email is **not** that person's agreement to hold an
account, and it is not proof the address is theirs.

So `contract_participants.user_id` stays null until the person clicks the link in their
own inbox. Only then is an account created and an enrolment made.

Two related guards:

- **An existing account's password is never changed by an invitation.** If it were, anyone
  who could get an employer to nominate an address could seize the account behind it. A
  password is set only when creating the account, or activating a placeholder that has
  never been signed into.
- **`UNIQUE (contract_id, email)`** stops the same person being nominated twice and
  burning two of the seats the client paid for.

The acceptance flow is idempotent: a second click returns the existing enrolment rather
than consuming another seat.

---

## 4. Sell, then commit

| Permission | Who | What it allows |
|---|---|---|
| `corporate.request.manage` | admin, management | triage enquiries |
| `corporate.proposal.manage` | admin, management | quote |
| `corporate.contract.approve` | **management only** | raise the contract |
| `corporate.participant.manage` | admin, management, centre manager | nominate and invite |
| `corporate.report.view` | admin, management, centre manager, accountant | the client report |

A salesperson may quote. Signing binds the company, and that is a management act — the
same approve/execute split that runs through admissions, refunds and affiliate payouts.

Centre managers are scoped: `assertCentreScope()` refuses a contract delivered at another
centre. Online contracts (`centre_id` null) stay with unscoped viewers, consistent with
Decision 8.

---

## 5. Raising a contract is one transaction

`createFromProposal()` creates the contract, a private cohort and the invoice together, or
none of them.

Partially applied, the failure modes are all bad: a contract with no cohort cannot be
delivered; a cohort with no invoice is unbilled training; an invoice for a contract that
failed to save is a demand for money against nothing.

It refuses early, with a reason, when the proposal has no programme (the cohort needs one)
or the organisation has no contact (the invoice needs someone to address).

---

## 6. The public form records, it does not create

`public/corporate.php` writes a `training_request` and nothing else. It does **not**
create an organisation.

A public form that silently creates company records fills the CRM with typos, duplicates
and whatever a bot typed. The typed name is kept on the request; linking it to a real
organisation is a deliberate act by whoever triages it, and until they do, the request
shows as "unlinked".

Rate limited to 5 per IP per hour, captcha-gated where configured.

---

## 7. The report §46 ends with

Per participant: sessions marked, attendance rate, average assessment score, certificates
issued. CSV export, audited.

All of it comes from the ordinary tables — which is the payoff for delivering through a
real cohort rather than a corporate-shaped copy of one.

**`n/a` is not `0%`** here too. A participant with no marked sessions has no attendance
rate; printing 0% would report them absent from classes that were never registered.

---

## 8. Tests

`tests/CorporateTest.php` — 5 assertions on proposal pricing: seats × unit price, discount
off the total rather than the seat, a floor at zero so an over-large discount cannot
produce a negative, zero as a legitimate price (pilot cohorts are real), and exactness at
scale.

Not covered without a database: the contract-creation transaction, invitation acceptance
and its idempotency, seat-cap enforcement, and centre scoping. The transaction and the
acceptance flow are the two most worth testing when Phase 14 gets a database.

---

## 9. Off by default

`corporate_enabled` ships as `0`. The public enquiry form is closed and the nav link does
not appear — a link to a closed form is a dead end. The internal pipeline still works, so
enquiries arriving by phone or email can be recorded from day one.

---

## 10. Not built

| Feature | Note |
|---|---|
| Proposal PDF | The proposal exists as a record; sending it is still email + your own document |
| E-signature | `signed_at` is a date somebody types |
| Instalment billing per contract | One invoice per contract. Instalments exist in Finance (Decision 17) but are not wired here |
| Bulk participant upload (CSV) | One at a time today. The obvious next addition |
| Client-facing portal | The client's contact has an account and gets the invoice; they cannot log in to watch progress. The report is exported and sent |
| Per-organisation pricing agreements | Price is per proposal |
| Automatic invitation email | The link is generated and shown for copying; nothing sends it yet |

The last one is the sharpest edge: **invitations are generated, not sent.** Whoever manages
the contract copies each link and sends it. Wiring it to `Notify` is small, but it needs
the outbound email path proven first — the same caveat as everywhere else in this system.

| # | Decision | Default taken |
|---|---|---|
| 39 | Proposal validity | 30 days, then auto-expired |
| 40 | Are corporate seats invoiced up front or on delivery? | Up front, one invoice at contract |
| 41 | May a participant keep their account after the contract ends? | Yes — the account is theirs |
