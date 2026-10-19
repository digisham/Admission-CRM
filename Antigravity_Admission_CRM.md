# Antigravity Master Prompt: School Admission CRM & Lead Management System

## Project identity

Build a complete, secure, production-minded **School Admission CRM &
Lead Management System** for **GD Goenka Public School, Purnea**.

The primary purpose is to manage admission enquiries for the **2027--28
academic session**, import leads from Excel, assign leads to admission
employees, track calls and follow-ups, assign tasks with remarks, and
manage optional admission documents after a lead reaches the configured
admission stage.

This must be a working application with real database persistence---not
just a static UI, clickable prototype, or mockup.

------------------------------------------------------------------------

# 1. Strict technology requirements --- NO REACT

Use this stack for a new project:

-   **Frontend:** HTML5, CSS3, vanilla JavaScript (ES6+).
-   **UI framework:** Bootstrap 5.
-   **Font:** Inter.
-   **Icons:** Lucide icons, used sparingly.
-   **Backend:** Core PHP 8.2+ using PDO and prepared statements.
-   **Database:** MySQL 8+ or compatible MariaDB.
-   **Excel import/export:** PHP PhpSpreadsheet via Composer, with CSV
    support.
-   **Optional Python:** Utility scripts only when useful. The main CRM
    must not require a Python server.
-   **Hosting target:** Standard PHP/MySQL hosting, including cPanel and
    DirectAdmin.

## Explicitly prohibited

**Do not use React or React Native. Do not use TypeScript, Vite, JSX,
TSX, Next.js, Node.js, Express, Supabase, PostgreSQL, Vue, Angular, or
another frontend framework.**

Use normal `.html`, `.css`, `.js`, `.php`, and `.sql` files. Prefer
PHP-rendered pages with reusable PHP includes/partials. Use vanilla
JavaScript and `fetch()` with PHP JSON endpoints for asynchronous
interactions where appropriate.

Do not create a React app, install React dependencies, generate JSX/TSX
files, or introduce a Node.js build step.

If a project already exists, inspect its structure and current stack
first. Do not overwrite unrelated work. If the existing stack conflicts
with these requirements, explain the migration needed and implement the
requested PHP/MySQL stack rather than introducing React or Supabase.

------------------------------------------------------------------------

# 2. First inspect, then implement

Before coding:

1.  Inspect the repository, existing files, hosting assumptions,
    database configuration, and available dependencies.
2.  Identify what can be reused and what needs to be created.
3.  Briefly outline the implementation plan.
4.  Build the complete core application end-to-end. Do not stop after
    creating a plan or a dashboard mockup.
5.  Keep the code modular, secure, readable, and suitable for ordinary
    PHP hosting.

Deliver these project artifacts:

-   PHP application source.
-   HTML, CSS, and vanilla JavaScript assets.
-   MySQL schema and versioned migration SQL.
-   Seed/setup scripts that safely create the initial academic session.
-   `.env.example` or documented environment-based configuration.
-   `composer.json` for required PHP packages.
-   `README.md` with setup, database, local development, testing, and
    deployment instructions.
-   Tests or repeatable test instructions for critical workflows.

Never commit secrets, production credentials, database passwords,
private keys, or tokens. Never expose database credentials to browser
JavaScript.

------------------------------------------------------------------------

# 3. School details and academic session management

**School:** GD Goenka Public School, Purnea\
**Initial active admission session:** 2027--28

Academic sessions must be dynamic and managed through the admin
interface. Do not hardcode the session into business logic.

## Academic session features

Administrators must be able to:

-   Add a new academic session.
-   Edit session details.
-   Set a session as the active admission session.
-   Deactivate or archive a session.
-   View older sessions and their records.
-   Configure admission opening and closing dates.

Suggested session fields:

-   Session ID.
-   Session label, such as `2027–28`.
-   Start date and end date.
-   Admission opening date and closing date.
-   Active/default status.
-   Notes.
-   Created by, created at, updated at.

## Session rules

-   Seed `2027–28` as the initial active session when it does not
    already exist.
-   Avoid creating duplicate sessions during setup or repeated seeding.
-   Only one session should be the default active admission session at a
    time.
-   New leads default to the active session.
-   Authorized users may select a different session when appropriate.
-   Every lead must have an academic session. Tasks, follow-ups,
    documents, and admissions must be linked to the correct session
    through their lead or their own session reference where needed.
-   Switching the active session must never change the session of
    existing records.
-   Keep historical records available for filtering and reporting.
-   Include session filters on leads, tasks, follow-ups, imports, and
    reports.
-   During Excel import, use the active session if no session is
    supplied. If a session is supplied, validate it and flag unknown
    values for correction.
-   Do not silently move old leads into a new session.

------------------------------------------------------------------------

# 4. User accounts, roles, and permissions

Implement secure login and server-enforced role-based access control.

## Roles

### Super Admin

-   Full access to all modules and settings.
-   Create, edit, activate, and deactivate users.
-   Manage roles and permissions.
-   Manage academic sessions, leads, assignments, tasks, documents,
    reports, and imports.
-   View audit logs and all employee activity.

### Admin / Admission Manager

-   View and manage all leads within granted permissions.
-   Add and edit leads.
-   Import and export Excel/CSV data.
-   Assign or bulk-assign leads.
-   Create and assign tasks.
-   Review follow-ups, remarks, and document status.
-   View reports and employee workloads.
-   Manage employees only when explicitly granted user-management
    permission.

### Admission Employee

-   By default, see only leads assigned to them.
-   View and update permitted fields on assigned leads.
-   Add call logs, remarks, and follow-up dates.
-   Create or complete permitted tasks.
-   Update permitted lead statuses.
-   Update document statuses only when authorized.
-   Cannot manage roles, create admin users, or access other employees'
    leads unless explicitly permitted.

### Optional Read-only role

-   View approved leads and reports.
-   Cannot create, edit, assign, or delete records.

## User management

-   Admin can create or invite employee accounts, update profile
    information, assign roles, activate/deactivate accounts, and trigger
    a secure password reset flow.
-   Never store plaintext passwords. Use `password_hash()` and
    `password_verify()`.
-   Deactivated users cannot sign in.
-   If an employee with active leads or tasks is deactivated, show a
    warning and require the admin to reassign records or explicitly
    confirm how to proceed.
-   Preserve historical assignments and actions after deactivation.
-   Enforce access rules in PHP for every protected request, not merely
    by hiding buttons.
-   Use a central authorization helper so permission checks remain
    consistent.

------------------------------------------------------------------------

# 5. Lead management

Create a complete lead-management module with a list, filters, add/edit
form, detail page, assignment actions, and activity timeline.

## Lead fields

-   System-generated unique lead ID/reference.
-   Academic session.
-   Student first name and last name, or full name.
-   Parent/guardian name.
-   Primary mobile number.
-   Alternate mobile number (optional).
-   Email address (optional).
-   Class/grade applied for.
-   Enquiry date and time.
-   Enquiry source.
-   Assigned employee (optional until assigned).
-   Lead status.
-   Priority.
-   Next follow-up date/time.
-   Address/city (optional).
-   General remarks/notes.
-   Created by and created at.
-   Last updated by and updated at.
-   Optional campaign/source details.

Store phone numbers as strings, not numeric values, so leading zeros and
country codes are preserved.

## Default enquiry sources

-   Website
-   Phone Call
-   Walk-in
-   Referral
-   Social Media
-   Advertisement
-   School Event
-   Existing Parent
-   Other

Allow admins to configure sources where practical.

## Default lead statuses

-   New
-   Assigned
-   Contact Attempted
-   Contacted
-   Interested
-   Follow-up Required
-   School Visit Scheduled
-   School Visit Completed
-   Application In Progress
-   Admission Confirmed
-   Not Interested
-   Lost / Unreachable

Use consistent status values and clearly distinguish open leads,
confirmed admissions, and lost/closed leads. Allow configurable statuses
if practical without breaking reports.

## Lead list features

-   Search by student name, parent name, phone number, email, and lead
    ID.
-   Filter by academic session, status, class, source, assigned
    employee, priority, enquiry date range, and follow-up date.
-   Sort and paginate results on the server for scalability.
-   Create, view, and edit leads.
-   Support multi-select and bulk actions for authorized users.
-   Export filtered results.
-   Show the assigned employee and next follow-up prominently.
-   Provide an unassigned-leads queue.
-   Show the full activity timeline on each lead.
-   Record who created and updated each record.

## Duplicate handling

-   Check likely duplicates using mobile number and academic session.
-   Warn the user and show matching records before saving/importing.
-   Provide authorized options to review, skip, update, or deliberately
    create a separate record.
-   Do not automatically merge or silently discard leads; siblings or
    separate enquiries may legitimately share a phone number.
-   Make repeated import attempts safe against accidental duplicate
    insertion.

Use archive/soft-delete behavior when appropriate and retain
auditability. Require confirmation for bulk or destructive actions.

------------------------------------------------------------------------

# 6. Excel and CSV import/export

Excel import is a core requirement. Build a multi-step import wizard
with preview and validation.

## Downloadable template

Provide a downloadable `.xlsx` and/or `.csv` template with clear headers
and example rows.

Suggested columns:

-   Student Name
-   Parent Name
-   Mobile Number
-   Alternate Mobile
-   Email
-   Class Applied
-   Enquiry Date
-   Enquiry Source
-   Lead Status
-   Priority
-   Academic Session
-   Assigned Employee
-   Remarks
-   Next Follow-up Date

## Import wizard

1.  Select and upload `.xlsx` or `.csv` files. Support `.xls` only if
    the installed library safely supports it.
2.  Validate file extension, MIME type, file size, and upload
    permissions.
3.  Parse the file and preview the rows before saving.
4.  Provide column mapping when spreadsheet headers differ from CRM
    fields.
5.  Validate required fields, dates, phone numbers, class, status,
    session, and employee assignments.
6.  Show row-level errors and warnings.
7.  Identify duplicate candidates.
8.  Let the user correct mappings, skip invalid rows, or download an
    error report.
9.  Show a final summary of valid, invalid, duplicate, skipped, and
    ready-to-import rows.
10. Require explicit confirmation before committing the import.
11. Display a result summary after completion.

## Import behavior

-   Do not create records from rows missing required values.
-   Never silently truncate or corrupt phone numbers.
-   Default missing session values to the active session.
-   Validate supplied sessions and employee names/IDs.
-   Allow assignment during import only to active employees and only for
    users with permission.
-   Support configurable duplicate handling: skip, update an existing
    record if authorized, or import after explicit review.
-   Use transactions or safe chunking so a failed import does not leave
    misleading partial results.
-   Save import history: file name, importing user, timestamp, total
    rows, imported rows, skipped rows, failed rows, and error details.
-   Prevent accidental duplicate insertion when the same file is
    uploaded again.
-   Protect against oversized or malicious files.

## Export

-   Export filtered lead lists and permitted reports to CSV/Excel.
-   Only export fields the current user is authorized to access.
-   Protect against spreadsheet formula injection when exporting
    untrusted text.
-   Record important exports in the audit log when appropriate.

------------------------------------------------------------------------

# 7. Lead assignment and employee workload

Admins must be able to assign leads individually and in bulk.

Features:

-   Assign one lead from the list or detail page.
-   Select multiple leads and assign them to an employee.
-   Clearly distinguish selection on the current page from all records
    matching a filter.
-   Confirm the employee and number of selected leads before bulk
    assignment.
-   Optionally create an initial task and due date during assignment.
-   Reassign leads with a reason or remark.
-   Maintain assignment history: previous employee, new employee,
    changed by, timestamp, and reason.
-   Show unassigned leads separately.
-   Show active lead counts and pending tasks by employee.
-   Provide a workload overview to help avoid uneven assignment.
-   Ensure employees only see records permitted by their role.

Bulk assignment must not change the academic session of any lead.

------------------------------------------------------------------------

# 8. Tasks, calls, remarks, and follow-ups

Build a task and follow-up system connected to leads.

## Task fields

-   Task ID.
-   Related lead.
-   Academic session, inherited from the lead or validated against it.
-   Task title.
-   Task type.
-   Description/instructions.
-   Assigned employee.
-   Due date and time.
-   Priority.
-   Status.
-   Completion remarks/outcome.
-   Created by and created at.
-   Updated at.
-   Completed at.

## Default task types

-   Call Parent
-   Follow-up Call
-   WhatsApp Follow-up
-   Schedule School Visit
-   Conduct School Visit
-   Send Information
-   Collect Documents
-   Application Follow-up
-   Other

## Task statuses

-   Pending
-   In Progress
-   Completed
-   Cancelled

Calculate overdue state from due date and completion status; do not
require users to manually set an inconsistent overdue status.

## Follow-up/call log fields

-   Related lead.
-   Date and time.
-   Contact method: Phone, WhatsApp, Email, In Person, Other.
-   Outcome: No Answer, Busy, Call Back Requested, Interested, Not
    Interested, Visit Scheduled, Documents Requested, Other.
-   Remark/outcome note.
-   Next follow-up date/time (optional; encouraged when follow-up is
    still needed).
-   Created by and timestamp.

## Required behavior

-   Employees can record call outcomes and schedule the next follow-up.
-   Admins can create and assign tasks to active employees.
-   Employees can view today's tasks, upcoming tasks, overdue tasks, and
    completed tasks.
-   Overdue tasks and follow-ups must be visually prominent.
-   Completing a task records completion time and outcome.
-   Updating the next follow-up must never erase historical follow-up
    entries.
-   Display a chronological lead timeline containing assignment changes,
    calls, remarks, task events, status changes, and document updates.
-   Validate date/time fields and prevent inconsistent states.
-   Provide in-app reminders and due-date indicators.
-   Do not claim that email, SMS, or WhatsApp notifications work unless
    a real integration is configured and tested.
-   Do not send messages automatically without an explicitly configured
    integration and appropriate authorization.

------------------------------------------------------------------------

# 9. Optional admission document tracking

Document tracking is optional. Enable the checklist when a lead reaches
a configurable qualified/confirmed status. Admins should be able to
configure when the document checklist becomes available.

A lead must be creatable, assignable, and followable even when no
documents are present.

## Default documents

1.  Birth Certificate (Attested Copy)
2.  Previous Year's Academic Report Card
3.  Transfer Certificate (if applicable)
4.  Passport-size Photographs (of student and parents)
5.  Address Proof

## Per-document fields

-   Document type.
-   Status: Pending, Available, Not Available, Not Applicable.
-   Remarks (optional).
-   File upload (optional).
-   Uploaded by and uploaded at.
-   Last updated by and updated at.
-   Verification status: Not Reviewed, Verified, Rejected (optional).
-   Verification remarks (optional).

## Document rules

-   Every document has its own independent status and remarks.
-   A document may be marked Available without an uploaded file if a
    physical copy has been received and school policy permits.
-   Transfer Certificate can be marked Not Applicable.
-   Missing documents must not block lead creation or routine
    follow-ups.
-   Authorized employees and admins can update document statuses.
-   File uploads must be private and access-controlled.
-   Validate extension, MIME type, size, and actual file content where
    feasible.
-   Store uploaded files outside publicly accessible paths where
    possible, or use protected download endpoints.
-   Never expose student documents through public URLs.
-   Use authorization checks on every document download.
-   Show checklist completion accurately; do not count Pending or Not
    Available items as complete.
-   Record document status changes in the activity/audit history.

------------------------------------------------------------------------

# 10. Dashboards and reporting

All metrics must come from the actual database. Never display fabricated
production numbers.

## Admin dashboard

Show:

-   Total leads for the selected session.
-   New leads.
-   Unassigned leads.
-   Leads by status.
-   Leads by class.
-   Leads by enquiry source.
-   Follow-ups due today.
-   Overdue follow-ups and tasks.
-   Upcoming school visits.
-   Confirmed admissions.
-   Conversion rate with a documented formula.
-   Employee-wise assigned lead count and workload.
-   Recent activity.
-   Academic session selector, defaulting to the active session.

## Employee dashboard

Show:

-   My assigned leads.
-   New leads assigned to me.
-   Calls and follow-ups due today.
-   Overdue tasks.
-   Upcoming tasks.
-   School visits.
-   Recently updated leads.
-   Completed tasks.
-   Personal conversion summary only if permitted.

## Reports

-   Leads by academic session.
-   Leads by status, class, source, and date range.
-   Employee-wise assignment and follow-up activity.
-   Follow-up completion and overdue report.
-   Admission confirmation and conversion report.
-   Import history and import errors.
-   Document checklist completion report, restricted to authorized
    roles.
-   Exportable filtered reports based on permissions.

Define conversion rate clearly, such as confirmed admissions divided by
eligible leads for the selected session. Document exclusions and avoid
division-by-zero errors.

If no records exist, show zero with a helpful empty state rather than
sample numbers.

------------------------------------------------------------------------

# 11. UI/UX and design system

Create a modern, premium, professional school-administration interface.
Prioritize efficient daily office use over decorative complexity.

## Design tokens

-   Primary blue: `#123985`
-   Secondary gold: `#B59859`
-   Font: Inter.
-   Bootstrap 5 responsive layout.
-   Light neutral backgrounds and white content surfaces.
-   Subtle borders and restrained shadows.
-   Consistent spacing and compact but readable tables.
-   Clear status badges and priority indicators.
-   Minimal icons; avoid clutter.
-   Accessible contrast, visible focus states, keyboard navigation,
    proper labels, and useful validation messages.
-   Desktop-first administration experience, fully responsive on tablets
    and phones.

Include:

-   Loading and skeleton states where useful.
-   Helpful empty states.
-   Inline field validation.
-   Success/error feedback.
-   Retryable error states.
-   Confirmation dialogs for bulk and destructive actions.
-   Pagination and filters that work with real data.
-   No dead buttons, decorative controls that do nothing, or fake
    success messages.

## Main navigation

-   Dashboard
-   Leads
-   Unassigned Leads
-   Tasks
-   Follow-ups
-   School Visits
-   Admission Documents
-   Reports
-   Academic Sessions
-   Employees & Roles
-   Import History
-   Settings
-   Audit Log (authorized roles only)

Navigation should reflect the current user's permissions, but hiding a
link must never replace backend authorization.

## Required screens

1.  Login and secure password reset/invitation flow.
2.  Admin dashboard.
3.  Employee dashboard.
4.  Leads list with filters, search, pagination, bulk selection, and
    assignment.
5.  Add/edit lead form.
6.  Lead details with Overview, Timeline, Tasks, Follow-ups, and
    Documents sections.
7.  Excel/CSV import wizard with preview and validation.
8.  Task list and create/edit task form.
9.  Follow-up list/calendar.
10. Employee management and permissions.
11. Academic session management.
12. Reports and exports.
13. Settings.
14. Audit log.

------------------------------------------------------------------------

# 12. Database design

Use MySQL relational integrity, foreign keys, indexes, and versioned SQL
schema/migration files.

Model at least:

-   `users`
-   `roles` and/or `permissions`
-   `user_roles` if multiple roles are supported
-   `academic_sessions`
-   `leads`
-   `lead_assignment_history`
-   `tasks`
-   `follow_up_logs` / `call_logs`
-   `lead_notes` if notes are separate
-   `document_types`
-   `lead_documents`
-   `import_batches`
-   `import_row_errors` or structured import results
-   `audit_logs`
-   `school_settings`
-   Optional configurable `lead_sources`, `lead_statuses`, and
    `classes/grades`

Requirements:

-   Primary keys and appropriate foreign keys.
-   Created/updated timestamps and actor references where useful.
-   Indexes for session, status, phone, assigned employee, due date, and
    common filters.
-   Constraints that prevent invalid relationships.
-   Current lead assignment and assignment history stored separately.
-   Preserve historical records when users or sessions are deactivated.
-   Seed the default five document types and the initial active
    `2027–28` session safely.
-   Provide a repeatable setup process; do not depend on undocumented
    manual database edits.

------------------------------------------------------------------------

# 13. PHP application architecture

Use a clear, maintainable Core PHP structure. A suggested layout is:

``` text
project/
  app/
    Config/
    Controllers/
    Middleware/
    Models/
    Services/
    Validators/
    Helpers/
    Views/
      layouts/
      auth/
      dashboard/
      leads/
      tasks/
      followups/
      documents/
      sessions/
      users/
      reports/
      settings/
  public/
    index.php
    assets/
      css/
      js/
      images/
    uploads/  # only if protected correctly; prefer private storage outside public/
  routes/
  database/
    migrations/
    seeds/
  storage/
    logs/
    private_uploads/
    imports/
  tests/
  .env.example
  composer.json
  README.md
```

Adapt the layout to the repository and hosting environment. The exact
folder names may vary, but keep routing, business logic, data access,
validation, and presentation separate.

Use:

-   PDO prepared statements for all database queries.
-   A central authentication and authorization layer.
-   PHP-rendered HTML pages.
-   Vanilla JavaScript for interactions.
-   `fetch()` and PHP JSON endpoints for asynchronous operations where
    useful.
-   Reusable PHP includes/partials for header, navigation, footer,
    tables, forms, and modals.
-   Server-side pagination and filtering.
-   Consistent JSON response shapes for asynchronous endpoints.
-   Correct HTTP status codes and safe user-facing error messages.

Do not use a frontend build system that requires React or Node.js.

------------------------------------------------------------------------

# 14. Security, privacy, and reliability

This CRM stores student and parent contact information and potentially
sensitive admission documents. Apply security throughout.

-   Use secure PHP sessions and regenerate session IDs after login.
-   Use `password_hash()` and `password_verify()`.
-   Use PDO prepared statements.
-   Add CSRF tokens to state-changing forms and requests.
-   Escape untrusted output to prevent XSS.
-   Validate all inputs server-side, even if the browser also validates
    them.
-   Enforce role and record-level permissions on every protected PHP
    route and JSON endpoint.
-   Use secure cookie settings when supported by HTTPS.
-   Add login throttling or rate limiting where practical.
-   Protect file uploads and downloads.
-   Keep private documents outside the public web root when possible.
-   Never store uploaded files under guessable public URLs.
-   Never expose secrets in HTML or JavaScript.
-   Avoid logging passwords, tokens, or document contents.
-   Use transactions for critical multi-step database operations.
-   Avoid duplicate form submissions and repeated import side effects.
-   Add indexes and pagination for larger datasets.
-   Maintain audit history for assignments, status changes, session
    changes, user/permission changes, and document updates.
-   Do not permanently delete important business records without an
    authorized, deliberate process.
-   Configure production errors so stack traces and database details are
    not shown to end users.
-   Provide backups and database recovery guidance in the README.

------------------------------------------------------------------------

# 15. Testing and acceptance criteria

The build is not complete until the following critical workflows have
been implemented and tested.

1.  Admin can sign in securely.
2.  Admin can create, edit, deactivate, and manage employee accounts
    according to permissions.
3.  Admin can create academic sessions and set `2027–28` as active.
4.  New leads default to the active academic session.
5.  Switching active sessions never changes historical lead sessions.
6.  Admin can manually create, view, search, filter, and edit a lead.
7.  Admin can download an import template.
8.  Admin can upload an Excel/CSV file and preview its rows before
    importing.
9.  Invalid rows, duplicate candidates, unknown sessions, and unknown
    employees are clearly reported.
10. Import results and error details are saved.
11. Re-uploading the same file does not silently duplicate all its
    records.
12. Admin can assign one lead and bulk-assign multiple leads.
13. Reassignments are recorded in assignment history.
14. Employees cannot access leads or documents they are not authorized
    to view.
15. An employee can log a call, add remarks, schedule a follow-up, and
    create/complete a task.
16. Overdue tasks and follow-ups are clearly shown.
17. Completing a task preserves its outcome and completion timestamp.
18. The lead timeline preserves historical activities.
19. The optional document checklist includes all five default document
    types.
20. Each document has its own status and remarks.
21. Documents can be tracked without uploading a file.
22. Private document uploads and downloads enforce authorization.
23. Admin can filter reports by academic session and export permitted
    data.
24. User roles are enforced in PHP on the server, not only in the
    interface.
25. Loading, empty, validation, success, and error states work.
26. All primary navigation links and visible controls work.
27. The application runs using the documented PHP/MySQL setup.
28. No React, React Native, TypeScript, Vite, JSX/TSX, Node.js,
    Supabase, or PostgreSQL is introduced.
29. No fake production data or misleading success messages are
    displayed.
30. Critical permissions, session switching, import, assignment, and
    document access are tested.

------------------------------------------------------------------------

# 16. Build and delivery process

-   Inspect the repository before modifying files.
-   Implement in logical stages, but complete the core end-to-end
    workflows.
-   Use maintainable code and reusable components.
-   Add schema/migrations and safe seed scripts.
-   Add validation and error handling in both frontend and backend.
-   Run available PHP syntax checks, tests, and static checks.
-   Verify database queries and authorization behavior.
-   Test the app on a standard PHP/MySQL development setup.
-   Fix errors found during testing.
-   Verify the production setup does not depend on React, Vite, Node.js,
    or Supabase.
-   Update `README.md` with:
    -   PHP and MySQL prerequisites.
    -   Composer dependencies.
    -   Database creation and configuration.
    -   Migration/seed steps.
    -   Local development instructions.
    -   Excel import setup.
    -   Private upload storage setup.
    -   Test commands.
    -   cPanel/DirectAdmin deployment instructions.
    -   Backup and recovery guidance.
-   Clearly list any credentials or third-party services that still
    require manual setup.
-   If a service cannot be configured due to missing credentials,
    explain the setup needed instead of pretending it works.

------------------------------------------------------------------------

# Final instruction

Build a complete, secure, polished **School Admission CRM for GD Goenka
Public School, Purnea** using **HTML5, CSS3, vanilla JavaScript,
Bootstrap 5, Core PHP 8.2+, and MySQL 8+/MariaDB**.

**No React. No TypeScript. No Vite. No Node.js. No Supabase. No
PostgreSQL.**

The initial active admission session must be **2027--28**, and
administrators must be able to add future sessions without altering
historical records.

The finished system must support manual and Excel lead entry, duplicate
review, bulk lead assignment, employee administration, tasks,
follow-ups, remarks, lead activity history, optional per-document
admission tracking, reports, and secure role-based access.

Prioritize real functionality, data integrity, privacy, reliable
PHP/MySQL hosting, and a complete end-to-end workflow over decorative
complexity.
