# ULTRADEMY

**Unified Student, Training, Subscription & Company Management Platform**

> **Status:** Phase 11 — Careers Portal & Recruitment. Phases 2–3 (public website, UI/UX
> design system) are UX/visual builds; **Phases 4–11 are real, running production code**:
>
> - a MySQL database (80 migrations) and session auth enforcing centre-scoped permissions
> - centres, programmes, cohorts, timetabling and attendance
> - a package/entitlement engine in which no code ever names a package
> - the full applicant → admitted → student journey on **one** account
> - an LMS with progress, assignments, grading and publicly verifiable certificates
> - finance: invoices, gateways, manual-transfer verification, receipts, refunds, reports
> - a notification engine with chat, groups, announcements and moderation
> - a public careers portal and recruitment back office, on its own session and roles
>
> See `docs/architecture/09-` through `16-`, and [Working notes](#working-notes) at the end
> for local setup, demo logins and repo workflow.
>
> **Before any production deployment:** point the web server's DocumentRoot at
> `ultra/public`. Serving the project folder directly exposes `.env` and uploaded
> documents — see `docs/architecture/12-applications-students.md` §8.

---

## PROJECT DIRECTIVE

We are going to build a completely new digital platform for UltrAdemy from the ground up.

This is NOT simply an LMS redesign.

We want to create a comprehensive **ULTRADEMY COMPANY MANAGEMENT & LEARNING PLATFORM**.

The platform will combine:

- Universal user accounts
- Student management
- Applicant management
- Training management
- Learning Management System (LMS)
- Subscription/package management
- Programme applications
- Physical training centre management
- Online learning
- Assignments and assessments
- Attendance
- Payments
- Accounting
- Cashier operations
- Affiliate programme
- Chat and groups
- Notifications
- Staff management
- Management dashboards
- Administration
- Reporting and analytics
- Corporate training
- Future company services

The long-term objective is for this platform to become the central digital operating
system of UltrAdemy.

---

## 1. IMPORTANT — BUILD THE NEW SYSTEM FRESH

We are building a NEW system from a clean foundation.

An existing LMS may be provided as a reference. The existing LMS should NOT
automatically become the foundation of the new architecture. Instead, treat the
existing LMS as a REFERENCE SYSTEM.

Use it to understand:

- Existing functionality
- Existing workflows
- Existing business rules
- Existing LMS capabilities
- Existing user experience
- Existing database concepts
- Existing features that may be valuable
- Existing data that may eventually need migration

However:

- DO NOT blindly copy its architecture.
- DO NOT blindly copy its database.
- DO NOT carry forward bad design decisions.
- DO NOT immediately modify the old application.

The correct process is:

```
DISCOVER → AUDIT → UNDERSTAND → EXTRACT REQUIREMENTS → DESIGN → REVIEW
→ APPROVE → BUILD → TEST → MIGRATE DATA → DEPLOY
```

The new system should have a clean, modular, scalable and maintainable architecture.

---

## 2. FIRST RULE — DO NOT START CODING IMMEDIATELY

Before writing production code, study everything provided.

Materials that may be provided:

1. Existing LMS source code
2. Existing SQL/database
3. Existing documentation
4. UltrAdemy operational plans
5. UI screenshots
6. Logos/branding
7. Existing workflows
8. Existing business requirements
9. Existing forms/documents
10. Additional requirements during development

The first responsibility is to understand the project. DO NOT begin major
implementation immediately. First produce a complete discovery and architecture report.

---

## 3. ULTRADEMY PLATFORM VISION

The new platform should be built around one central idea:

**ONE ULTRADEMY ACCOUNT → MULTIPLE ULTRADEMY SERVICES**

A person creates one Universal UltrAdemy Account. That account can then participate in
different parts of the platform.

For example, one user may simultaneously be:

- A student
- An applicant
- An online learner
- A physical training participant
- An affiliate
- A member of a community/group

Do not create unnecessary duplicate accounts. The same person should have ONE identity
record with different relationships, roles, services and entitlements.

```
                    ULTRADEMY ACCOUNT
                           |
        -----------------------------------------
        |                  |                    |
     STUDENT           APPLICANT            AFFILIATE
        |                  |                    |
        -----------------------------------------
                           |
                    ULTRADEMY SERVICES
                           |
       ------------------------------------------
       |              |              |          |
    Learning      Programmes    Subscription  Community
```

The architecture should support additional relationships in the future.

---

## 4. REGISTRATION & ACCOUNT CREATION

Build a professional registration system. Users should be able to create an UltrAdemy
account.

Potential registration information:

- Full name
- Email
- Phone number
- Password
- Profile information
- Date of birth where required
- Address where required
- Profile photograph
- Other required information

The registration system should be configurable.

```
REGISTER → VERIFY ACCOUNT → COMPLETE PROFILE → USER DASHBOARD
→ ACTIVATE SERVICES → USE ULTRADEMY PLATFORM
```

Account verification should support appropriate verification methods.

---

## 5. USER PROFILE

Every user must have a centralized profile.

**Personal Information** — full name, contact information, profile photo, address,
other relevant information.

**UltrAdemy Relationship** — student status, applicant status, affiliate status,
active programmes, subscriptions, certificates, payment history.

**Activity** — courses, assignments, assessments, attendance, applications,
transactions, notifications, messages, groups.

The profile should be the central identity record for the person.

---

## 6. SUBSCRIPTION & PACKAGE SYSTEM

The platform must support different subscription packages. Packages determine what
services and features a user can access.

| Package | Example scope |
|---|---|
| BASIC | Basic account features |
| STANDARD | Calendar, programme applications, selected learning features |
| PREMIUM | Online courses, assignments, assessments, certificates |
| ADVANCED | Additional premium services |

These are examples only. Administrators must be able to create and configure packages.

Each package may contain: name, description, price, duration, billing period, features,
entitlements, access restrictions, status, eligibility rules.

---

## 7. FEATURE ENTITLEMENT SYSTEM

Do NOT hard-code package access throughout the application. Build a centralized
entitlement/feature-access system.

```
SUBSCRIPTION → PACKAGE → ENTITLEMENTS → FEATURES
```

Examples of features: calendar, programme applications, online learning, assignments,
assessments, certificates, chat, groups, affiliate programme, premium resources,
events, special programmes.

Administrators should be able to determine what each package provides. This should be
scalable so new features can be introduced without rebuilding the subscription system.

---

## 8. USER DASHBOARD

The user's dashboard should dynamically reflect their role, subscriptions, programmes,
applications, learning activity, permissions and available features.

Potential dashboard sections:

- **Overview** — welcome message, profile completion, active subscription, subscription
  expiry, available services
- **Learning** — current courses, progress, upcoming lessons, assignments, assessments,
  results
- **Programmes** — available programmes, applied programmes, application status,
  enrolled programmes
- **Calendar** — classes, lessons, assessments, exams, events, deadlines
- **Finance** — payments, invoices, receipts, outstanding balances
- **Communication** — notifications, messages, groups
- **Affiliate** — affiliate status, referral code, referrals, commission information

The dashboard should be modular.

---

## 9. PROGRAMME MANAGEMENT

The system must distinguish between: PROGRAMME, COURSE, MODULE, LESSON, CLASS, COHORT.

A programme may contain multiple courses/modules.

```
PROGRAMME → COHORT → CLASS → COURSES / MODULES → LESSONS
→ ASSIGNMENTS / ASSESSMENTS → COMPLETION → CERTIFICATE
```

Programmes should support: programme name, description, category, objectives,
requirements, duration, mode, fees, schedule, capacity, available centres, online
availability, instructors, courses, course outline, completion requirements,
certification, status.

---

## 10. PROGRAMME APPLICATION SYSTEM

Users should be able to browse and apply for programmes.

```
BROWSE PROGRAMMES → VIEW PROGRAMME → APPLY → APPLICATION REVIEW
→ APPROVED / REJECTED / PENDING → PAYMENT → ENROLMENT → TRAINING → COMPLETION
```

Applications should support: applicant information, programme, preferred centre,
application date, required documents, application status, review, approval/rejection,
assignment, admission, enrolment.

Avoid duplicate accounts when an applicant becomes a student.

---

## 11. APPLICANT EXPERIENCE

Applicants should have their own environment. They should be able to create an account,
complete their profile, browse programmes, apply, upload required documents, select a
preferred centre, track application status, receive notifications, view the admission
decision, complete registration, make required payments, and become a student after
admission.

```
APPLICANT → APPLICATION → UNDER REVIEW → APPROVED → ADMITTED → REGISTERED
→ STUDENT → ACTIVE LEARNER → GRADUATE
```

---

## 12. CENTRE & LOCATION MANAGEMENT

UltrAdemy will operate physical training hubs. The initial centres are:

1. **GWAGWALADA HUB**
2. **KUBWA HUB**

These must be treated as actual entities within the system. DO NOT hard-code these
locations. Build a proper multi-centre architecture so additional locations can be added
later.

```
ULTRADEMY
├── GWAGWALADA HUB
│   ├── Rooms
│   ├── Staff
│   ├── Instructors
│   ├── Programmes
│   ├── Cohorts
│   ├── Classes
│   ├── Students
│   └── Equipment
└── KUBWA HUB
    ├── Rooms
    ├── Staff
    ├── Instructors
    ├── Programmes
    ├── Cohorts
    ├── Classes
    ├── Students
    └── Equipment
```

Each centre should support: centre name, location, address, contact information, centre
manager, staff, instructors, rooms, classrooms, computer labs, capacity, equipment,
operating status, programmes, cohorts, classes, timetables, students, centre
announcements, operational records.

---

## 13. CENTRE VS LOCATION VS ROOM

Design the data model carefully.

- **CENTRE** — Gwagwalada Hub, Kubwa Hub
- **LOCATION** — physical address/geographic information
- **ROOM** — specific classroom, office, lab or facility inside a centre
- **ONLINE** — non-physical learning environment

Do not confuse these concepts. A programme may be centre-specific, multi-centre, online
or hybrid.

| Example | Availability |
|---|---|
| Programme A | Gwagwalada only |
| Programme B | Gwagwalada + Kubwa |
| Programme C | Online |
| Programme D | Online + Physical |

---

## 14. CENTRE-SPECIFIC STUDENT MANAGEMENT

Students should be connected to their relevant centre through their
enrolment/assignment rather than simply hard-coding a centre onto the user.

A student should potentially be able to have a primary centre, enrol at a centre,
transfer between centres, participate in multiple centre programmes, participate in
online programmes, and maintain historical centre records.

```
STUDENT → ENROLMENT → PROGRAMME → COHORT → CENTRE → CLASS / ROOM
```

Centre assignment should affect: schedule, classes, attendance, instructors,
announcements, physical activities, centre events.

---

## 15. CENTRE-SPECIFIC MANAGEMENT

Centre managers should have scoped access.

- Gwagwalada Centre Manager → primarily manages Gwagwalada
- Kubwa Centre Manager → primarily manages Kubwa
- Management → can view all centres
- Super Admin → full system access

The permission system must support centre-level scope.

---

## 16. CENTRE DASHBOARD

Centre managers should see: students, applicants, programmes, cohorts, classes,
attendance, instructors, staff, centre revenue, centre expenses, outstanding payments,
equipment, operational issues, announcements.

Management should be able to compare Gwagwalada Hub vs Kubwa Hub vs all centres.

---

## 17. PHYSICAL TRAINING MANAGEMENT

The platform must support physical training: centres, rooms, classrooms, computer
laboratories, programmes, cohorts, classes, timetables, instructors, student allocation,
attendance, assessments, centre events, equipment, maintenance, operational incidents.

The online and physical learning environments must operate within the same platform.

---

## 18. ONLINE LEARNING / LMS

The platform must retain strong LMS capabilities: courses, modules, lessons, course
outlines, videos, documents, learning materials, assignments, quizzes, examinations,
assessments, results, progress, completion, certificates.

```
COURSE → MODULE → LESSON → LEARNING MATERIAL → ASSIGNMENT → ASSESSMENT
→ RESULT → COMPLETION → CERTIFICATE
```

---

## 19. COURSE OUTLINE

Every online course should have a structured course outline containing: course
description, objectives, prerequisites, modules, lessons, learning outcomes, resources,
assignments, assessments, estimated duration, completion requirements.

Students should be able to see their course structure and progress.

---

## 20. STUDENT LEARNING ENVIRONMENT

Students should have a dedicated learning environment where they can view courses, view
course outlines, access lessons, watch/read learning materials, submit assignments, take
quizzes, complete assessments, view results, track progress, view feedback, download
permitted materials and view certificates.

---

## 21. ATTENDANCE

Attendance should connect: STUDENT + PROGRAMME + COHORT + CLASS + INSTRUCTOR + CENTRE +
DATE/TIME.

Support daily attendance, class attendance, centre attendance, attendance percentage,
late attendance, absence, attendance reports.

Attendance should contribute to student progress where appropriate.

---

## 22. CALENDAR

Create a centralized calendar. Events may include classes, physical training, online
lessons, assessments, exams, assignments, deadlines, centre events, meetings, programme
events, announcements.

Users should only see events relevant to them.

---

## 23. ASSIGNMENTS & ACTIVITIES

Support assignment creation, instructions, due dates, file submission, text submission,
grading, feedback, scores, resubmission where enabled, assignment status.

---

## 24. CHAT & GROUPS

Build a communication/community system.

- **Direct chat** — user ↔ user
- **Instructor chat** — instructor ↔ student
- **Group chat** — admin/instructor → group → students
- **Programme groups** — programme → cohort → members

Features: text messages, group conversations, announcements, notifications, file sharing
where appropriate, moderation, group membership.

Administrators should have moderation controls.

---

## 25. AFFILIATE PROGRAMME

Support affiliate registration, approval, profile, referral link, referral code,
referral tracking, referred users, qualified referrals, commission tracking, commission
approval, payout records, affiliate reports.

```
AFFILIATE → REFERRAL LINK → NEW USER → REGISTRATION → QUALIFYING ACTION
→ COMMISSION → APPROVAL → PAYOUT
```

Commission rules must be configurable.

---

## 26. PAYMENT SYSTEM

The platform must support online and manual payments.

Required gateways:

- **Paystack**
- **Flutterwave**

The gateway architecture must be modular so additional providers can be added later.

Support: payment initiation, payment verification, transaction references, successful
payments, failed payments, pending payments, webhooks, receipts, payment history.

---

## 27. MANUAL BANK TRANSFER

Users should be able to select BANK TRANSFER / MANUAL PAYMENT and then view bank payment
instructions, select an invoice/payment, submit a payment reference, upload proof where
required, and submit for verification.

Accountant/cashier should be able to review, approve, reject, request clarification,
verify the transaction and record it.

Every manual payment must have an audit trail.

---

## 28. ACCOUNTING BACKEND

Accountants should be able to manage: revenue, expenses, payments, invoices, receipts,
student fees, programme fees, outstanding balances, refunds, manual bank transfers,
reconciliation, financial reports.

Financial records should be linked to: user, student, programme, subscription, centre
where applicable, invoice, payment, transaction.

---

## 29. CASHIER BACKEND

Cashiers should have a more restricted financial environment: record payments, verify
permitted payments, issue receipts, view student payment records, handle daily
transactions, cash reconciliation, view relevant invoices.

Cashiers should NOT automatically have full accountant privileges. Use granular
permissions.

---

## 30. FINANCIAL STRUCTURE

```
USER → PROGRAMME / SUBSCRIPTION → INVOICE → PAYMENT → VERIFICATION
→ RECEIPT → FINANCIAL RECORD
```

Every transaction should contain: unique transaction ID, user, amount, currency, payment
method, reference, status, date/time, related service, centre where applicable,
verification status, audit information.

---

## 31. CENTRE-BASED FINANCE

Financial records should optionally be associated with a centre, allowing reports such
as revenue and expenses by centre, outstanding balances by centre, and centre financial
performance.

Online-only transactions should be able to use ONLINE / GLOBAL rather than forcing an
incorrect physical location.

---

## 32. MANAGER BACKEND

Managers should have overall visibility into users, students, applicants, programmes,
classes, attendance, instructors, staff, centre operations, finance overview, affiliate
activity, reports and system activity.

Managers may also have permission to perform selected operational functions. Do not
automatically give every manager full administrator privileges.

---

## 33. ADMINISTRATOR BACKEND

**User Management** — view, edit, activate, suspend users; assign roles; manage
permissions.

**Programme Management** — create, edit, approve, publish; assign instructors; assign
centres; manage cohorts.

**Subscription Management** — create/edit packages, set pricing, configure features,
manage subscription status.

**Application Management** — review, approve, reject; assign programme, centre, cohort.

**Content Management** — courses, lessons, materials, announcements.

**Communication Moderation** — groups, chats, reports, announcements.

**System Management** — settings, permissions, audit logs, integrations, payment
configuration.

---

## 34. ADMIN APPROVAL WORKFLOWS

| Object | Workflow |
|---|---|
| Application | Submitted → Under Review → Approved / Rejected → Assigned → Enrolled |
| Manual payment | Submitted → Pending Verification → Verified / Rejected |
| Affiliate | Applied → Under Review → Approved → Active |
| Programme | Draft → Pending Approval → Approved → Published |

These statuses should be configurable where appropriate.

---

## 35. STAFF MANAGEMENT

Support staff beyond instructors: management, centre managers, accountants, cashiers,
receptionists, HR, marketing, IT support, instructors.

Staff should have profiles, roles, permissions, centre assignments, work assignments and
activity history.

---

## 36. INSTRUCTOR MANAGEMENT

Instructors should have their own dashboard showing assigned programmes, assigned
classes, centre, timetable, students, attendance, learning materials, assignments,
assessments, results and announcements.

---

## 37. NOTIFICATION SYSTEM

Create a centralized notification engine, triggered by registration, account
verification, application, admission, payment, subscription, assignment, assessment,
class schedule, attendance, programme updates, affiliate activity and administrative
actions.

Channels: in-app, email, SMS in the future.

---

## 38. REPORTING & ANALYTICS

Build role-specific dashboards.

- **Management** — total users, active students, applications, enrolments, revenue,
  expenses, active programmes, centre performance, affiliate performance, training
  performance
- **Accountant** — revenue, expenses, payments, outstanding balances, manual transfers,
  reconciliation, financial reports
- **Centre Manager** — centre students, classes, attendance, staff, instructors, centre
  revenue, centre expenses, operational issues
- **Instructor** — students, attendance, assignments, assessments, progress
- **Admin** — users, applications, content, moderation, system activity

Reports should support filters such as date, centre, programme, course, cohort,
instructor, student and payment status. Provide export functionality where appropriate.

---

## 39. AUDIT LOGGING

Important actions must be recorded: who performed the action, what happened, when,
relevant record, previous value where applicable, new value where applicable, IP/device
information where appropriate.

Important actions include permission changes, user changes, financial transactions,
payment verification, application approval, programme assignment, subscription changes
and administrative actions.

---

## 40. DATABASE ARCHITECTURE

Design the new database from the business domain. Do not simply copy the old LMS
database.

| Group | Conceptual entities |
|---|---|
| Core | Users, Profiles, Roles, Permissions, Settings, Audit Logs |
| Subscriptions | Packages, Features, Entitlements, Subscriptions |
| Centres | Centres, Locations, Rooms, Equipment, Centre Staff |
| Education | Programmes, Courses, Modules, Lessons, Assignments, Assessments, Results, Certificates |
| Admissions | Applications, Admissions, Enrolments, Cohorts, Classes |
| Operations | Timetables, Attendance, Events |
| Finance | Invoices, Payments, Receipts, Expenses, Refunds, Reconciliation |
| Affiliate | Affiliates, Referrals, Commissions, Payouts |
| Communication | Conversations, Messages, Groups, Group Members, Notifications |
| Staff | Staff, Instructors, Assignments |
| Reporting | Reports, Audit Logs |

These are conceptual entities only. Do not create unnecessary tables simply because they
are listed here. Design the actual schema based on business relationships.

---

## 41. API ARCHITECTURE

The system should be API-ready. Future applications may include an UltrAdemy Student
App, Instructor App, Staff App and Management App.

Therefore: use proper API architecture, authentication and authorization; keep business
logic reusable; avoid tightly coupling web pages to business logic; design for future
mobile applications.

---

## 42. SECURITY

Security must be designed from the beginning: secure authentication, authorization,
RBAC, permission controls, centre-scoped access, input validation, CSRF protection, API
authentication, rate limiting, secure file uploads, password security, session security,
audit logging, database backup strategy, data protection, secure payment webhooks.

Financial and personal information must receive appropriate protection.

- A student must NEVER be able to access another student's private information.
- A cashier must NOT automatically have accountant privileges.
- A centre manager should not automatically see another centre's private operational
  data unless authorized.

---

## 43. USER EXPERIENCE

The entire system should feel like one unified UltrAdemy platform. Do not make each
module feel like a separate application.

Maintain consistent navigation, a consistent design system, consistent components,
responsive layouts, clear dashboards, a mobile-friendly interface and role-specific
navigation.

**ONE ULTRADEMY ACCOUNT → ONE ULTRADEMY EXPERIENCE → MULTIPLE SERVICES**

---

## 44. DASHBOARD STRUCTURE

| Role | Dashboard |
|---|---|
| Student | Learning → Schedule → Progress → Attendance → Assignments → Assessments → Payments → Messages → Groups |
| Applicant | Application → Status → Required Documents → Programme → Centre → Payments → Notifications |
| Affiliate | Referrals → Performance → Commissions → Payouts |
| Instructor | Classes → Students → Timetable → Attendance → Assignments → Assessments |
| Cashier | Payments → Invoices → Receipts → Daily Transactions |
| Accountant | Finance → Payments → Expenses → Reconciliation → Reports |
| Centre Manager | Centre → Students → Classes → Staff → Attendance → Operations → Centre Finance |
| Management | Company Overview → Centres → Students → Programmes → Finance → Staff → Performance → Reports |
| Administrator | System → Users → Roles → Permissions → Programmes → Applications → Subscriptions → Content → Moderation → Settings |

---

## 45. MULTI-CENTRE ARCHITECTURE

The initial centres are Gwagwalada Hub and Kubwa Hub, but the architecture must support
future centres without code changes — for example Abuja Central Hub, Lagos Hub, Kaduna
Hub, Port Harcourt Hub or other locations.

Administrators should be able to create new centres from the backend.

---

## 46. CORPORATE TRAINING

The platform should be designed to support future corporate training for banks,
government agencies, parastatals, companies and institutions.

```
ORGANIZATION → CORPORATE CLIENT → TRAINING REQUEST → PROPOSAL → QUOTATION
→ CONTRACT → TRAINING PROGRAMME → PARTICIPANTS → ASSESSMENT → COMPLETION
→ CORPORATE REPORT
```

This module may be implemented later, but the architecture should not prevent it.

---

## 47. FUTURE BUSINESS EXPANSION

Do not design the platform only for today's training hub. UltrAdemy may eventually have
more physical centres, more training programmes, online courses, corporate training,
affiliate services, additional digital services and additional business units.

The architecture must allow new modules to be added without rebuilding the platform.

---

## 48. MIGRATION FROM EXISTING LMS

The old LMS should NOT be directly converted into the new application.

```
OLD DATABASE → AUDIT → DATA MAPPING → NEW DATABASE → MIGRATION SCRIPTS
→ VALIDATION → IMPORT
```

Do not migrate blindly. Identify users, students, courses, instructors, applications,
payments and other relevant records. Map old records to the new architecture.

Do not destroy the old database. Maintain a rollback strategy.

---

## 49. DEVELOPMENT PHASES

**PHASE 0 — DISCOVERY.** Study all provided materials. Produce: existing LMS audit,
existing feature inventory, business requirements, new feature requirements, user roles,
existing workflows, database analysis, technology assessment.

**PHASE 1 — ARCHITECTURE.** Design system architecture, module architecture, database
architecture, ERD, role/permission matrix, centre architecture, subscription
architecture, entitlement architecture, API architecture, notification architecture,
payment architecture, audit architecture.

**PHASE 2 — UI/UX.** Design system, navigation, authentication, registration, and every
role dashboard: user, student, applicant, affiliate, instructor, accountant, cashier,
centre manager, management, admin.

**PHASE 3 — CORE FOUNDATION.** Authentication, universal accounts, profiles, roles,
permissions, settings, notifications, audit logs.

**PHASE 4 — CENTRES & OPERATIONS.** Centre management, Gwagwalada Hub, Kubwa Hub, rooms,
staff assignments, programmes, cohorts, classes, timetables, attendance.

**PHASE 5 — SUBSCRIPTIONS.** Packages, features, entitlements, subscriptions, access
control, subscription billing.

**PHASE 6 — ADMISSIONS & STUDENTS.** Applications, applicant dashboard, application
review, admission, enrolment, student profiles, student dashboard.

**PHASE 7 — LMS.** Courses, modules, lessons, course outlines, learning materials,
assignments, quizzes, assessments, results, progress, certificates.

**PHASE 8 — FINANCE.** Invoices, payments, Paystack, Flutterwave, manual bank transfer,
payment verification, receipts, expenses, reconciliation, accounting dashboard, cashier
dashboard.

**PHASE 9 — COMMUNICATION.** Notifications, direct chat, groups, programme groups,
instructor communication, announcements, moderation.

**PHASE 10 — AFFILIATE.** Affiliate registration, approval, referral links, referral
tracking, commission, payouts, affiliate dashboard.

**PHASE 11 — MANAGEMENT & REPORTING.** Management dashboard, centre dashboards,
financial reports, student reports, programme reports, attendance reports, affiliate
reports, operational reports, analytics.

**PHASE 12 — CORPORATE TRAINING.** Organizations, corporate clients, training requests,
proposals, contracts, corporate participants, corporate reporting.

**PHASE 13 — TESTING.** Unit, feature, integration, UI, permission, centre-scope,
payment, webhook, security, database, API and performance testing.

**PHASE 14 — DATA MIGRATION.** Only after the new system is stable: map old data, create
migration scripts, import test data, validate, reconcile, import production data, verify
integrity.

**PHASE 15 — DEPLOYMENT.** Production environment, database, environment configuration,
backups, monitoring, logging, security, deployment process, rollback strategy.

> **Note:** §80 revises this ordering to bring the public website forward. The revised
> roadmap is the operative one.

---

## 50. DEVELOPMENT RULES

**ALWAYS:** build modularly; use clean architecture; use migrations; use reusable
components; maintain data integrity; use proper authorization; maintain audit trails;
write tests; document major decisions; design for scalability; protect sensitive
information; keep APIs in mind; keep future centres in mind; keep future business
modules in mind.

**NEVER:** start coding before discovery; destroy the old LMS; modify production data
unnecessarily; blindly copy old architecture; hard-code package permissions; hard-code
centre permissions; hard-code roles; duplicate user accounts unnecessarily; mix
unrelated business logic; put everything into one controller/module; introduce
unnecessary dependencies; skip testing; skip payment verification; give excessive
permissions by default.

---

## 51. FIRST RESPONSE REQUIRED

After receiving this brief and all project files, DO NOT begin implementation. First
provide a comprehensive **ULTRADEMY DISCOVERY & ARCHITECTURE REPORT** containing:

1. Executive understanding of the UltrAdemy vision
2. Existing LMS audit
3. Existing technology stack
4. Existing architecture
5. Existing modules
6. Existing database analysis
7. Existing user roles
8. Existing workflows
9. New feature inventory
10. Proposed system modules
11. Universal account architecture
12. Subscription/package architecture
13. Feature entitlement architecture
14. Student journey
15. Applicant journey
16. Affiliate journey
17. Learning journey
18. Payment workflow
19. Manual payment workflow
20. Accounting workflow
21. Cashier workflow
22. Centre management architecture
23. Gwagwalada Hub structure
24. Kubwa Hub structure
25. Multi-centre architecture
26. Centre-scoped permissions
27. Manager architecture
28. Administrator architecture
29. Instructor architecture
30. Communication architecture
31. Chat/group architecture
32. Reporting architecture
33. Database architecture
34. ERD
35. API architecture
36. Security architecture
37. UI/UX architecture
38. Migration/data strategy
39. Testing strategy
40. Development roadmap
41. Risks
42. Assumptions
43. Decisions required

Clearly distinguish between EXISTING FUNCTIONALITY, REQUIRED NEW FUNCTIONALITY,
PROPOSED ENHANCEMENTS and FUTURE FEATURES.

Do not assume something exists if it has not been verified. If information is missing,
clearly identify it as a question or decision rather than inventing an answer.

---

## 52. FINAL PRODUCT OBJECTIVE

The final system should feel like **ONE ULTRADEMY PLATFORM**.

```
                ULTRADEMY
                     |
            UNIVERSAL ACCOUNT
                     |
   ---------------------------------------
   |            |            |           |
STUDENT      APPLICANT    AFFILIATE    STAFF
   |            |            |           |
   ---------------------------------------
                     |
              ULTRADEMY SERVICES
                     |
  --------------------------------------------
  |          |             |         |       |
LEARNING  PROGRAMMES  SUBSCRIPTIONS CHAT  PAYMENTS
  |          |             |         |       |
  --------------------------------------------
                     |
              CENTRE MANAGEMENT
                     |
          -----------------------
          |                     |
   GWAGWALADA HUB           KUBWA HUB
          |                     |
      Students               Students
      Classes                Classes
      Staff                  Staff
      Instructors            Instructors
      Rooms                  Rooms
      Attendance             Attendance
          |                     |
          -----------------------
                     |
             COMPANY OPERATIONS
                     |
   ------------------------------------
   |           |          |           |
FINANCE   MANAGEMENT    ADMIN    REPORTING
   |           |          |           |
ACCOUNTANT MONITORING  CONTROL   ANALYTICS
CASHIER    OVERSIGHT   APPROVALS REPORTS
                     |
              FUTURE SERVICES
                     |
            CORPORATE TRAINING
            MORE CENTRES
            MORE PROGRAMMES
            MORE BUSINESS UNITS
```

The goal is NOT to create a collection of disconnected features. The goal is a single
integrated UltrAdemy ecosystem where one account, one identity, one profile, one
dashboard, multiple services, multiple programmes, multiple centres, multiple learning
experiences, multiple payment methods and multiple internal departments all operate
within one secure and scalable platform.

**START WITH DISCOVERY. DO NOT WRITE PRODUCTION CODE YET. DO NOT MODIFY THE EXISTING
LMS. DO NOT MODIFY THE PRODUCTION DATABASE. DO NOT ASSUME THE EXISTING DATABASE IS THE
FINAL DATABASE DESIGN.**

Priority:

```
UNDERSTAND → AUDIT → DESIGN → VALIDATE → APPROVE → BUILD → TEST → MIGRATE
→ DEPLOY → OPTIMIZE
```

We are building the long-term digital foundation of UltrAdemy, not merely another LMS.

---

## 53. PUBLIC WEBSITE & ULTRADEMY LANDING PAGE

The platform must include a professional public-facing website/landing page accessible
to visitors without an account. This is different from the authenticated
student/management application.

Its purpose is to introduce UltrAdemy, explain what UltrAdemy does, showcase training
programmes, promote physical training hubs, promote online learning, allow visitors to
discover programmes, encourage registration, allow visitors to apply for programmes,
promote subscription packages, explain the affiliate programme, provide contact
information, build trust and credibility, and provide access to important company
information.

```
                    PUBLIC WEBSITE
                         │
                 ULTRADEMY HOMEPAGE
                         │
        ┌────────────────┼────────────────┐
        │                │                │
    PROGRAMMES        CENTRES          ABOUT US
        │                │                │
        └────────────────┼────────────────┘
                         │
                  CALL TO ACTION
                         │
              REGISTER / APPLY / LOGIN
                         │
                  ULTRADEMY ACCOUNT
                         │
                   MAIN PLATFORM
```

---

## 54. HOMEPAGE / LANDING PAGE STRUCTURE

The homepage should be a modern, professional landing page rather than simply a login
form. It should immediately communicate: What is UltrAdemy? What does UltrAdemy offer?
Who is it for? Why choose UltrAdemy? How can someone get started?

The visitor should understand the value of UltrAdemy within the first few sections.

---

## 55. HERO SECTION

The first section should be a strong hero area containing a strong headline, supporting
description, primary CTA, secondary CTA, high-quality visual, and trust/value indicators
where appropriate.

```
--------------------------------------------------
        BUILD YOUR SKILLS.
        BUILD YOUR FUTURE.

        Practical training, digital learning
        and career-focused programmes designed
        to help you grow.

        [ Explore Programmes ]   [ Get Started ]

              [ Visual / Training Image ]
--------------------------------------------------
```

Do not blindly use this exact wording. Create professional marketing copy based on
UltrAdemy's actual services and positioning.

Potential CTAs: Explore Programmes, Start Learning, Apply Now, Register, Visit Our
Centres.

---

## 56. NAVIGATION

```
ULTRADEMY LOGO
Home · Programmes · Learning · Centres · About · Affiliate · Contact
                                        [ Login ]  [ Get Started ]
```

The exact navigation should be determined after reviewing the business requirements. It
should remain simple and mobile-friendly.

---

## 57. PROGRAMMES SECTION

The homepage should showcase selected training programmes.

```
EXPLORE OUR PROGRAMMES

[ Programme Card ]
Programme Name
Short description
Duration · Mode · Centre / Online
[ View Programme ]

[ View All Programmes ]
```

Programme cards should be dynamically generated from the backend rather than hard-coded.
Administrators should be able to control featured programmes, programme visibility,
programme order and programme status.

---

## 58. TRAINING MODES

The landing page should clearly communicate the different learning environments:

- **Physical Training** — learn at one of our physical training hubs
- **Online Learning** — learn remotely through the UltrAdemy LMS
- **Hybrid Learning** — combine online learning with physical training
- **Corporate Training** — training programmes for organizations and institutions

These should link to appropriate pages.

---

## 59. OUR CENTRES

A dedicated homepage section introducing UltrAdemy's physical hubs — initially
Gwagwalada Hub and Kubwa Hub.

The section could display centre name, location, short description, available
programmes, facilities, training environment, contact information and map/location
information where appropriate.

The centre information must come from the backend. When new centres are added, they
should automatically become available on the public website where appropriate.

---

## 60. WHY ULTRADEMY

A section explaining the value proposition: practical learning, industry-relevant
skills, experienced instructors, flexible learning, physical training environment,
online learning, structured programmes, student support, certification, career
development.

Do not invent claims that UltrAdemy cannot substantiate. This content should eventually
be manageable through the CMS.

---

## 61. HOW IT WORKS

```
01  CREATE YOUR ACCOUNT
02  EXPLORE PROGRAMMES
03  APPLY OR ENROL
04  START LEARNING
05  COMPLETE YOUR TRAINING
06  ACHIEVE YOUR GOALS
```

This should explain the relationship between the public website and the platform.

---

## 62. STUDENT JOURNEY CTA

```
READY TO START YOUR JOURNEY?

Create your UltrAdemy account and
discover programmes designed for your goals.

[ Get Started ]   [ Explore Programmes ]
```

The CTA should lead to the appropriate registration/application workflow.

---

## 63. ONLINE LEARNING SECTION

Promote the LMS experience: structured courses, course outlines, lessons, assignments,
assessments, progress tracking, learning resources, certificates.

CTA: `[ Explore Online Learning ]`

---

## 64. AFFILIATE PROGRAMME SECTION

Introduce the affiliate programme: what it is, who can participate, how referrals work,
benefits, how to get started.

CTA: `[ Become an Affiliate ]`

---

## 65. TESTIMONIALS / SUCCESS STORIES

The homepage should support testimonials and success stories. **Do not create fake
testimonials.**

The CMS should allow administrators to add student testimonials, success stories,
graduate experiences and corporate testimonials. Each testimonial should be approved
before publication.

---

## 66. UPCOMING PROGRAMMES / EVENTS

The homepage may dynamically display upcoming programmes, cohorts, classes, events and
registration deadlines. Only publicly available information should be displayed.

---

## 67. NEWS / ARTICLES / BLOG

The public website should support a content/blog section with categories such as
Training, Technology, Career, Education, UltrAdemy News, Student Stories and Events.

Administrators should be able to manage articles, categories, featured posts, images,
authors and publication status.

---

## 68. ABOUT ULTRADEMY

A dedicated About page explaining who UltrAdemy is, mission, vision, values, training
philosophy, learning approach, physical training centres, online learning and company
story.

The exact content should be provided or approved by UltrAdemy. **Do not fabricate
company history, statistics or achievements.**

---

## 69. CONTACT PAGE

Include a contact form, phone, email, centre information, address, social media links
where applicable, business hours where applicable, and location/map information where
appropriate.

Contact form submissions should enter the management/admin backend.

---

## 70. PROGRAMME DETAIL PAGE

```
PROGRAMME NAME
Description
What You Will Learn
Course Outline
Duration
Learning Mode
Available Centres
Requirements
Start Date
Fees
Who Is This For?

[ Apply Now ]   [ Register ]
```

The page should dynamically retrieve information from the programme management system.

---

## 71. PUBLIC VS AUTHENTICATED EXPERIENCE

**Public website** (no login) — homepage, programmes, programme details, centres, about,
blog, contact, affiliate information, public information.

**Authenticated platform** (login required) — dashboard, student learning, applications,
payments, assignments, attendance, chat, groups, affiliate dashboard, staff dashboards,
management, administration.

Do not expose private platform data on the public website.

---

## 72. PUBLIC WEBSITE CMS

The public website should not require developers to edit code whenever basic content
changes. Build CMS functionality for authorized administrators to manage homepage
sections, hero content, programme highlights, featured programmes, centre information,
blog, testimonials, announcements, FAQs, contact information, images and SEO metadata.

CMS permissions must be restricted. Not every administrator should automatically be able
to modify everything.

---

## 73. SEARCH ENGINE OPTIMIZATION

Support page titles, meta descriptions, Open Graph metadata, structured URLs, canonical
URLs, sitemap, robots configuration, proper headings, image alt text and structured data
where appropriate.

```
/programmes
/programmes/web-development
/programmes/data-analysis
/centres
/centres/gwagwalada
/centres/kubwa
/about
/contact
/blog
```

Do not expose internal IDs unnecessarily in public URLs.

---

## 74. PUBLIC WEBSITE PERFORMANCE

Optimize for fast loading, mobile devices, desktop, tablets, low-bandwidth connections
where practical, image optimization, lazy loading, caching and efficient API/database
queries.

Do not overload the homepage with unnecessary animations or huge assets.

---

## 75. PUBLIC WEBSITE DESIGN DIRECTION

The landing page should feel professional, modern, educational, technology-focused,
trustworthy, clean, premium and accessible.

It should NOT feel like a generic admin dashboard, an old-fashioned school website, a
template filled with unnecessary sections, or an overly complicated corporate website.

The public website and authenticated dashboard should share the same UltrAdemy design
language while serving different purposes.

---

## 76. HOMEPAGE INFORMATION ARCHITECTURE

```
HOME
├── Hero
├── Introduction / Value Proposition
├── Featured Programmes
├── Training Modes
│   ├── Physical
│   ├── Online
│   └── Hybrid
├── Our Centres
│   ├── Gwagwalada Hub
│   └── Kubwa Hub
├── Why UltrAdemy
├── How It Works
├── Online Learning
├── Upcoming Programmes
├── Student Success Stories
├── Affiliate Programme
├── Blog / Resources
├── FAQ
├── Strong CTA
└── Footer
```

Do not automatically include every section if the content makes the homepage too long.
Use good UX judgment.

---

## 77. FOOTER

- **UltrAdemy** — short company description
- **Explore** — Programmes, Learning, Centres, Blog
- **Company** — About, Contact, Careers where applicable
- **Support** — Help, FAQs, Privacy Policy, Terms & Conditions, Cookie Policy
- **Account** — Login, Register, Student Portal
- **Centres** — Gwagwalada Hub, Kubwa Hub
- **Social** — official social media links where applicable

---

## 78. PUBLIC WEBSITE ADMINISTRATION

| Object | Workflow |
|---|---|
| Programme | Draft → Pending Review → Approved → Published |
| Blog | Draft → Review → Published |
| Testimonial | Submitted → Approved → Published |

This ensures public content is moderated.

---

## 79. LANDING PAGE CONVERSION STRATEGY

The homepage should guide visitors toward meaningful actions.

```
VISITOR → EXPLORE PROGRAMMES → PROGRAMME DETAILS → REGISTER / APPLY
→ ACCOUNT → ENROLMENT

VISITOR → ONLINE LEARNING → EXPLORE COURSES → REGISTER → SUBSCRIBE → LEARN

VISITOR → AFFILIATE PROGRAMME → LEARN MORE → REGISTER → AFFILIATE ACCOUNT
```

The landing page should make these journeys obvious without overwhelming the visitor.

---

## 80. FINAL PUBLIC WEBSITE REQUIREMENT

The UltrAdemy public website should function as **the front door to the UltrAdemy
platform**, while the authenticated platform functions as **the UltrAdemy digital
operating system**.

```
                 ULTRADEMY.COM
                      │
                PUBLIC WEBSITE
                      │
       ┌──────────────┼──────────────┐
       │              │              │
   PROGRAMMES      CENTRES        ABOUT
       │              │              │
       └──────────────┼──────────────┘
                      │
                GET STARTED
                      │
              UNIVERSAL ACCOUNT
                      │
             ULTRADEMY PLATFORM
                      │
     ┌────────────────┼────────────────┐
     │                │                │
  LEARNING         SERVICES         OPERATIONS
     │                │                │
  Students        Affiliates        Finance
  Courses         Applications      Centres
  Classes         Payments          Staff
  Assignments     Subscriptions     Management
```

The homepage must therefore be designed **before or alongside the core application
architecture**, because it defines how visitors enter the UltrAdemy ecosystem.

### Revised development order

This supersedes the ordering in §49.

| Phase | Scope |
|---|---|
| 0 | Discovery |
| 1 | Business & System Architecture |
| 2 | Brand + Public Website / Landing Page UX |
| 3 | UI/UX Design System |
| 4 | Core Platform Foundation |
| 5 | Centres & Operations |
| 6 | Subscriptions & Entitlements |
| 7 | Applications & Students |
| 8 | LMS |
| 9 | Finance & Payments |
| 10 | Communication |
| 11 | Affiliate |
| 12 | Management & Reporting |
| 13 | Corporate Training |
| 14 | Testing & Security |
| 15 | Data Migration |
| 16 | Deployment |

This way UltrAdemy has two clearly connected products: the **public website that
attracts and converts users**, and the **private platform that manages their entire
journey after they enter the ecosystem**.

---

## Working notes

### Brand

| Role | Colour |
|---|---|
| Primary | Cyan `#22C7E3` |
| Secondary | Magenta `#FF00FF` |
| Dark base | Black `#000000` |
| Light base | White `#FFFFFF` |

Typography: **Neulis Alt** (primary — headings, nav, buttons) and **Neue Helvetica**
(secondary — body, metadata). Both commercial; see `docs/UI-REFERENCE.md` §7 for the
interim fallback stack.

Light and dark themes are both first-class. Theme is a token swap driven by
`data-theme` on `<html>`, toggled from the sidebar and persisted to `localStorage`.

### Docs

- `docs/DESIGN-SYSTEM.md` — colour ramps, typography scale, spacing, radius,
  elevation, dark theme, gradients
- `docs/UI-REFERENCE.md` — student dashboard reference decomposition, grid, component
  inventory, responsive strategy
- `docs/architecture/07-public-website.md` — Phase 2 public website: pages built,
  brand application, IA, what's real vs. placeholder content
- `docs/architecture/08-ui-design-system.md` — Phase 3 UI/UX: shared component kit,
  all 9 role dashboards, auth screens, what's real vs. placeholder content
- `docs/architecture/09-core-foundation.md` — Phase 4: database, auth, permission
  model, demo logins
- `docs/architecture/10-centres-operations.md` — Phase 5: centres, programmes,
  cohorts, timetabling, attendance
- `docs/architecture/11-subscriptions-entitlements.md` — Phase 6: packages, feature
  registry, entitlement resolution, subscription lifecycle
- `docs/architecture/12-applications-students.md` — Phase 7: applications, document
  handling, admission, enrolment, transfers
- `docs/architecture/13-lms.md` — Phase 8: courses, lessons, materials, progress,
  assignments, grading, certificates (assessments deferred — see §7)
- `docs/architecture/14-finance-payments.md` — Phase 9: invoices, payment gateways,
  manual transfer verification, receipts, refunds, expenses, reports, reconciliation
- `docs/architecture/15-communication.md` — Phase 10: notification engine, chat,
  groups, announcements, moderation
- `docs/architecture/16-careers-portal.md` — Phase 11: public careers site, recruitment
  back office, separate session and roles

### Current scaffold

```
app/
  controllers/   request handlers
  models/        database access
  views/         page templates
config/          app + database configuration
storage/
  app/documents/   application documents — PII, outside the web root, gitignored
  app/materials/   course materials
  app/submissions/ assignment submissions
  app/proofs/      proof-of-payment uploads
database/
  migrations/    schema changes, in order (80 files as of Phase 11)
  migrate.php    runner — applies new migrations, tracked in a `migrations` table
  seed.php       dev/demo data — roles, permissions, centres, users, programmes,
                 packages and the feature matrix. Idempotent; safe to re-run.
  expire-subscriptions.php  daily job — hard-stops subscriptions past ends_at
public/          web root — the only folder the browser should reach
  css/ js/       shared site + app-shell stylesheets
  app.php        authenticated-app front controller (?r=route.name)
  login.php, register.php, logout.php
docs/            design system, architecture docs and Phase 3 dashboard previews
```

This scaffold is now live — Phase 4/5 code runs inside it directly (`app/core`,
`app/models`, `app/controllers`, `app/views`), no framework swap happened.

### Local setup

1. Copy `.env.example` to `.env` — defaults match a stock XAMPP install (`root`, no
   password, database `ultrademy`).
2. Create the database: `mysql -u root -e "CREATE DATABASE ultrademy CHARACTER SET utf8mb4"`.
3. Run migrations: `php database/migrate.php`.
4. Seed demo data: `php database/seed.php` — prints the demo login list.
5. Visit `http://localhost/ultra/public` for the marketing site, or
   `http://localhost/ultra/public/login.php` to sign in (e.g. `super@ultrademy.com` /
   `Password123!`) and reach the authenticated app at `public/app.php`.

### Pushing changes

From `C:\xampp\htdocs\ultra` in PowerShell:

```powershell
.\push.bat "what changed"
```

Stages, commits and pushes to `origin/main`.
