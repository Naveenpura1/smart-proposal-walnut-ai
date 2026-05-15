# Business Requirements Document (BRD)
## SmartProposal — AI-Assisted Sales Proposal Generator

**Document Version:** 1.0  
**Prepared From:** PHP Developer Assignment — Senior PHP Developer Evaluation  
**Role:** Senior PHP Developer  
**Project Duration:** 5–7 Days  
**Submission:** GitHub Repository + Loom walkthrough video (10–15 min)  
**Difficulty:** Advanced  

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Project Overview](#2-project-overview)
3. [Tech Stack](#3-tech-stack)
4. [System Actors & Roles](#4-system-actors--roles)
5. [Functional Requirements](#5-functional-requirements)
   - 5.1 [Authentication & Role Management](#51-authentication--role-management)
   - 5.2 [Proposal Management](#52-proposal-management)
   - 5.3 [Walnut AI Integration](#53-walnut-ai-integration)
   - 5.4 [Tracking & Analytics](#54-tracking--analytics)
   - 5.5 [Notifications](#55-notifications)
6. [Non-Functional Requirements](#6-non-functional-requirements)
7. [Database Design](#7-database-design)
8. [User Stories & Acceptance Criteria](#8-user-stories--acceptance-criteria)
9. [Evaluation Criteria](#9-evaluation-criteria)
10. [Deliverables Checklist](#10-deliverables-checklist)
11. [Bonus Features](#11-bonus-features)
12. [Submission Instructions](#12-submission-instructions)

---

## 1. Executive Summary

SmartProposal is a full-stack web application that enables a sales team to create, manage, and deliver personalised client proposals. Walnut AI is a core component — used to generate or enhance proposal content (narrative summaries, feature highlights, or slide content) based on client profile inputs.

The system must support two user roles (Admin and Sales Rep), full proposal lifecycle management, AI-assisted content generation, proposal engagement tracking, and an admin analytics dashboard.

---

## 2. Project Overview

| Field | Detail |
|---|---|
| **Application Name** | SmartProposal |
| **Core Purpose** | AI-assisted sales proposal generation and management |
| **Primary Users** | Sales Representatives, Administrators |
| **AI Tool** | Walnut AI (walnut.ai) — primary, mandatory |
| **Deployment** | Docker (docker-compose, one-command local setup) |

### Core Workflows

1. Sales reps create and manage a proposal library
2. Proposals are dynamically generated based on client profile inputs (client name, industry, pain points, deal size)
3. Walnut AI generates or enhances proposal content (narrative, slides, or sections)
4. Proposals can be tracked for opens, views, and engagement
5. An admin dashboard displays proposal performance analytics

---

## 3. Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8.2+ — Laravel 11 preferred (or vanilla PHP with PSR standards) |
| **Frontend** | Blade, Vue.js, React, or plain HTML/CSS (developer's choice) |
| **Database** | MySQL or PostgreSQL |
| **AI Integration** | Walnut AI — API calls or iframe embed |
| **Authentication** | Laravel Breeze / Sanctum / JWT (developer's choice) |
| **Email** | Mailpit / Mailtrap or similar SMTP trap |
| **Containerisation** | Docker + docker-compose (required) |

---

## 4. System Actors & Roles

| Role | Description |
|---|---|
| **Admin** | Full platform access: views all proposals, analytics dashboard, manages users, views engagement metrics across all sales reps |
| **Sales Rep** | Scoped access: creates and manages only their own proposals, views their own analytics |
| **Client (passive)** | Receives a proposal link; their opens/views are tracked without requiring a login |

---

## 5. Functional Requirements

---

### 5.1 Authentication & Role Management

#### Requirements

| ID | Requirement |
|---|---|
| AUTH-01 | User registration with name, email, password, and role selection (Admin / Sales Rep) |
| AUTH-02 | User login with email and password; failed attempts return a validation error |
| AUTH-03 | User logout invalidates the session |
| AUTH-04 | Role-based access control: Admin routes are inaccessible to Sales Reps (403 Forbidden) |
| AUTH-05 | A Sales Rep cannot access, view, or modify another Sales Rep's proposals |
| AUTH-06 | Password reset via email (forgot password flow) |
| AUTH-07 | Email verification after registration (optional but preferred) |

#### Acceptance Criteria

- Unauthenticated users are redirected to the login page
- Attempting to access `/admin/*` as a Sales Rep returns HTTP 403
- A Sales Rep accessing `/proposals/{id}` for a proposal they do not own receives HTTP 403 or 404
- Successful login redirects: Admin → admin dashboard, Sales Rep → proposals list

---

### 5.2 Proposal Management

#### Requirements

| ID | Requirement |
|---|---|
| PROP-01 | Sales Rep can create a proposal by providing: client name, industry, pain points, deal size |
| PROP-02 | Upon creation, Walnut AI is invoked to generate proposal content (narrative, summary, or slide outline) |
| PROP-03 | Sales Rep can edit any field of their own proposals, including regenerating AI content |
| PROP-04 | Sales Rep can clone (duplicate) an existing proposal |
| PROP-05 | Sales Rep can delete their own proposals (with confirmation) |
| PROP-06 | Proposals have a status lifecycle: `Draft` → `Sent` → `Accepted` |
| PROP-07 | Sales Rep can manually update the status of any of their proposals |
| PROP-08 | Each proposal has a unique shareable link that can be sent to the client |
| PROP-09 | Proposal list view supports search (client name, industry) and filter (by status) with pagination |
| PROP-10 | Clicking a proposal row navigates to the proposal detail/view page |
| PROP-11 | Empty state is shown when no proposals exist or no results match filters |

#### Acceptance Criteria

- Creating a proposal with all required fields succeeds and redirects to the proposal detail page
- AI-generated content is stored in the `generated_content` column and displayed on the detail page
- Editing a proposal updates all provided fields; validation errors are shown inline
- Cloning creates a new proposal with all fields copied and status reset to `Draft`
- Deleting a proposal requires confirmation and removes it permanently
- The proposal list is paginated (10 / 25 / 50 per page, configurable)
- Status can be updated from the detail or list view via a dropdown or button

---

### 5.3 Walnut AI Integration

#### Requirements

| ID | Requirement |
|---|---|
| WAI-01 | Walnut AI must be a meaningful, documented part of the workflow — not a cosmetic addition |
| WAI-02 | At least one major feature component must be generated or drafted using Walnut AI (e.g., proposal narrative, slide deck outline, or email copy) |
| WAI-03 | Integration can be via: (a) Walnut AI API called from the PHP backend, or (b) Walnut AI iframe embed within the proposal view |
| WAI-04 | The generated content is stored server-side in the database and displayed in the proposal view |
| WAI-05 | A "Regenerate" button allows the sales rep to re-trigger AI content generation for an existing proposal |
| WAI-06 | The README must contain a dedicated section explaining: what prompts were given, what was generated, what was modified manually |
| WAI-07 | The Loom video must demonstrate a before/after of AI-generated content vs. the final implementation |
| WAI-08 | If API access is limited, the developer must document what was attempted, what was constrained, and how they adapted |

#### Acceptance Criteria

- Submitting a new proposal triggers an AI content generation call (API or mock)
- Generated content appears on the proposal detail page within the same request or via a background job
- The README AI section includes at least: prompt used, raw output received, changes made manually
- If the AI call fails, the proposal is still created with a fallback placeholder and an error is logged

---

### 5.4 Tracking & Analytics

#### Requirements

| ID | Requirement |
|---|---|
| TRK-01 | Each proposal has a unique public URL; opening this URL logs a "view" event with timestamp and IP |
| TRK-02 | View events are stored in a `proposal_views` table linked to the proposal |
| TRK-03 | Admin dashboard displays aggregate metrics: total proposals, open rate, accepted rate, top performing sales rep |
| TRK-04 | Sales Rep dashboard displays their own metrics: total proposals, drafts, sent, accepted |
| TRK-05 | Admin can see a per-proposal breakdown: how many times viewed, last viewed at, current status |
| TRK-06 | Analytics data is calculated server-side (not purely client-side JS) |

#### Acceptance Criteria

- Visiting `/proposals/{token}/view` (public link) increments the view count for that proposal
- The admin dashboard shows accurate counts drawn from the database (not hardcoded)
- "Open rate" = proposals with at least 1 view / total sent proposals
- "Top performing rep" = sales rep with the most accepted proposals
- Sales rep's own dashboard shows only their own proposal counts

---

### 5.5 Notifications

#### Requirements

| ID | Requirement |
|---|---|
| NOTIF-01 | When a client opens a proposal link, an email notification is sent to the owning Sales Rep |
| NOTIF-02 | The email includes: proposal title (client name), client IP (optional), timestamp of view |
| NOTIF-03 | Emails are sent via Laravel's mail system configured with Mailpit / Mailtrap |
| NOTIF-04 | Notifications should use a queue (preferred) to avoid blocking the client's page load |
| NOTIF-05 | A notification is not sent more than once per hour per proposal (throttle to avoid spam) |

#### Acceptance Criteria

- Opening a proposal's public link triggers a queued job that sends an email to the owning rep
- The email is catchable in Mailpit/Mailtrap and contains the correct proposal and time details
- If the queue worker is not running, the notification falls back to synchronous sending
- Duplicate notifications within 60 minutes for the same proposal are suppressed

---

## 6. Non-Functional Requirements

| ID | Requirement |
|---|---|
| NFR-01 | **Code Quality:** Clean architecture, PSR-12 compliance, SOLID principles, meaningful comments |
| NFR-02 | **Security:** All routes behind `auth` middleware; CSRF protection on all forms; no SQL injection via raw queries without binding |
| NFR-03 | **Performance:** Proposal list page loads in under 2s with 100 proposals; database queries use appropriate indexes |
| NFR-04 | **Containerisation:** `docker-compose up` brings the entire application up (web server, DB, mail catcher, queue worker) |
| NFR-05 | **Environment Config:** All secrets in `.env`; `.env.example` committed; no credentials in code |
| NFR-06 | **Git History:** Meaningful commit messages; PR-style branch structure preferred; no "WIP" mega-commits |
| NFR-07 | **README:** Must cover: setup, architecture decisions, Walnut AI usage log, known limitations |
| NFR-08 | **Responsive UI:** Application is usable on mobile and tablet viewports |
| NFR-09 | **Error Handling:** 404 and 403 pages are customised; unhandled exceptions do not expose stack traces in production mode |

---

## 7. Database Design

### Tables

#### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `name` | varchar(255) | |
| `email` | varchar(255) | Unique |
| `email_verified_at` | timestamp | Nullable |
| `password` | varchar(255) | Hashed (bcrypt) |
| `role` | enum('admin','sales') | Default: `sales` |
| `remember_token` | varchar(100) | Nullable |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `proposals`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `user_id` | bigint FK | References `users.id`, cascade delete |
| `client_name` | varchar(255) | |
| `industry` | varchar(255) | |
| `pain_points` | text | Primary AI input |
| `deal_size` | decimal(15,2) | |
| `generated_content` | text | Nullable — Walnut AI output |
| `status` | enum('Draft','Sent','Accepted') | Default: `Draft` |
| `public_token` | varchar(64) | Unique — used in shareable link |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

#### `proposal_views`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `proposal_id` | bigint FK | References `proposals.id`, cascade delete |
| `ip_address` | varchar(45) | IPv4/IPv6 |
| `user_agent` | text | Nullable |
| `viewed_at` | timestamp | |

#### `notifications` (optional — Laravel default)
| Column | Type | Notes |
|---|---|---|
| `id` | char(36) PK | UUID |
| `type` | varchar(255) | Notification class name |
| `notifiable_type` | varchar(255) | |
| `notifiable_id` | bigint | |
| `data` | text | JSON payload |
| `read_at` | timestamp | Nullable |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Key Relationships

- `users` 1→N `proposals` (a user owns many proposals)
- `proposals` 1→N `proposal_views` (a proposal has many view events)

---

## 8. User Stories & Acceptance Criteria

---

### Epic 1 — User Authentication & Role Management
**Key:** WB-025 | Priority: Medium

---

#### Story WB-024 — User Registration & Login
**As a** new user, **I want to** register and log in, **so that** I can access the platform.

**Acceptance Criteria:**
1. Registration form collects name, email, password, password confirmation, and role (Admin / Sales Rep)
2. Duplicate email addresses are rejected with a clear validation message
3. Password must meet minimum security requirements (min 8 chars)
4. After registration, the user is logged in and redirected to their appropriate dashboard
5. Login form accepts email + password; invalid credentials show "These credentials do not match our records."
6. "Forgot Password" link sends a reset email
7. Logout clears the session and redirects to the login page

---

#### Story WB-023 — Role-Based Access Control
**As an** Admin, **I want** role-based access enforced throughout, **so that** Sales Reps cannot access admin functions.

**Acceptance Criteria:**
1. Admin routes (`/admin/*`) return HTTP 403 when accessed by a Sales Rep
2. Sales Rep routes (`/proposals/*`) return HTTP 403 or 404 when a Sales Rep attempts to access another user's proposal
3. Navigation only shows links relevant to the authenticated user's role
4. The admin dashboard is not linked or accessible from the Sales Rep interface

---

#### Story WB-018 — Sales Rep Access Restricted to Own Proposals
**As a** Sales Rep, **I want to** only see and manage proposals I have created, **so that** client data remains confidential.

**Acceptance Criteria:**
1. Proposal list only shows proposals owned by the authenticated user
2. Attempting to access another user's proposal via direct URL returns 403/404
3. Sales Rep cannot access admin analytics
4. Sales Rep cannot view or modify other users' account details
5. Ownership checks are enforced server-side (policy + scoped route model binding)

---

### Epic 2 — Proposal Management
**Key:** WB-020 | Priority: High

---

#### Story WB-019 — Create Proposal with AI Content Generation
**As a** Sales Rep, **I want to** create a proposal by entering client details, **so that** Walnut AI can generate personalised proposal content.

**Acceptance Criteria:**
1. Form collects: client name, industry, pain points (text area), deal size (numeric)
2. All fields are required; inline validation errors are shown
3. On submission, Walnut AI is called with the input data to generate proposal content
4. Generated content is stored in `proposals.generated_content`
5. User is redirected to the proposal detail page upon successful creation
6. If AI generation fails, the proposal is created with a placeholder and an error notice is shown

---

#### Story WB-021 — Proposal List with Search and Filter
**As a** Sales Rep, **I want to** view a list of all my proposals with search and filter options, **so that** I can quickly find and manage specific proposals.

**Acceptance Criteria:**
1. List displays: client name, industry, status badge, deal size, created date, last modified date
2. Search by client name or industry (case-insensitive `LIKE`)
3. Filter by status: All / Draft / Sent / Accepted
4. Pagination: 10 / 25 / 50 results per page (user-configurable, preserved in URL)
5. Clicking a row navigates to the proposal detail page
6. Empty state shown when no proposals match the filter or none exist yet

---

#### Story WB-016 — View Proposal Detail
**As a** Sales Rep, **I want to** view the full details of a proposal including the AI-generated content, **so that** I can review it before sending.

**Acceptance Criteria:**
1. Detail page shows: client name, industry, pain points, deal size, status, created/modified dates
2. AI-generated content is displayed in a formatted read-only section
3. "Edit" and "Delete" action buttons are present
4. "Send" button (or status change to `Sent`) is available
5. A shareable public link is displayed and copyable
6. The page is accessible only by the proposal owner (403 for others)

---

#### Story WB-015 — Edit Proposal
**As a** Sales Rep, **I want to** edit a proposal's details and optionally regenerate AI content, **so that** I can refine it before sending.

**Acceptance Criteria:**
1. Edit form pre-populates all existing field values
2. All fields are editable; same validation rules as create
3. A "Regenerate AI Content" button re-invokes the AI with current field values
4. Saving redirects to the proposal detail page with a success message
5. Only the proposal owner can edit (403 for others)

---

#### Story WB-014 — Clone Proposal
**As a** Sales Rep, **I want to** duplicate an existing proposal, **so that** I can quickly create a variant for a similar client.

**Acceptance Criteria:**
1. "Clone" action is available on the proposal detail and list pages
2. Cloning creates a new proposal with all fields copied; status reset to `Draft`; client name prefixed with "Copy of "
3. The new proposal's `public_token` is freshly generated (unique)
4. User is redirected to the new proposal's detail page
5. AI-generated content from the original is copied into the clone (not regenerated automatically)

---

#### Story WB-013 — Delete Proposal
**As a** Sales Rep, **I want to** delete proposals I no longer need, **so that** my proposal list stays clean.

**Acceptance Criteria:**
1. "Delete" action available on detail page and list page
2. A confirmation dialog is shown before deletion
3. Deletion removes the proposal and all associated view events (cascade)
4. User is redirected to the proposals list with a success flash message
5. Only the proposal owner can delete; attempting as another user returns 403

---

#### Story WB-012 — Update Proposal Status
**As a** Sales Rep, **I want to** change a proposal's status (Draft → Sent → Accepted), **so that** I can track its lifecycle.

**Acceptance Criteria:**
1. Status can be updated via a dropdown or action buttons on the detail page
2. Valid transitions: Draft → Sent, Sent → Accepted (no backwards movement enforced in MVP)
3. Status change is persisted immediately with a success message
4. Status badge updates visually on the page without a full reload (or with one)

---

### Epic 3 — Walnut AI Integration
**Key:** WB-017 | Priority: High

---

#### Story WB-011 — AI Content Generation from PHP Backend
**As a** Sales Rep, **I want** Walnut AI to generate proposal narrative content from my inputs, **so that** I save time crafting personalised proposals.

**Acceptance Criteria:**
1. When a proposal is created or "Regenerate" is clicked, a call is made to the Walnut AI API or embed
2. The payload sent includes: client name, industry, pain points, deal size
3. The response (narrative, outline, or slide content) is stored in `generated_content`
4. If the API call fails, a fallback placeholder is stored and the error is logged to `storage/logs`
5. The README documents the exact prompt template used

---

#### Story WB-010 — Walnut AI Embed View
**As a** Sales Rep, **I want** an embedded Walnut AI proposal view within the application, **so that** I can present interactive proposal content to clients.

**Acceptance Criteria:**
1. The proposal detail page includes a Walnut AI iframe embed (if API access allows)
2. The embed renders the proposal content generated from the client's profile
3. If embed is unavailable, a formatted HTML fallback displays the `generated_content` field
4. The integration approach is clearly documented in the README

---

### Epic 4 — Tracking & Analytics
**Key:** WB-009 | Priority: High

---

#### Story WB-008 — Proposal View Tracking
**As a** Sales Rep, **I want to** know when a client opens my proposal, **so that** I can follow up at the right time.

**Acceptance Criteria:**
1. Each proposal has a unique public URL using a `public_token` (e.g., `/p/{token}`)
2. Visiting the public URL logs a `proposal_views` record: proposal_id, IP, user agent, timestamp
3. The Sales Rep sees a "Views" count on the proposal detail page
4. The public URL is copyable from the detail page
5. Viewing the public URL does not require authentication

---

#### Story WB-007 — Sales Rep Dashboard Stats
**As a** Sales Rep, **I want** a dashboard showing my proposal stats, **so that** I can track my performance at a glance.

**Acceptance Criteria:**
1. Dashboard shows: Total Proposals, Drafts, Sent, Accepted (real counts from DB)
2. A "Recent Proposals" list shows the 5 most recently updated proposals
3. Each recent proposal row shows: client name, industry, status badge, relative time
4. Clicking a recent proposal row navigates to its detail page
5. Stats update on every page load (not cached stale data)

---

#### Story WB-006 — Admin Analytics Dashboard
**As an** Admin, **I want** a dashboard showing platform-wide analytics, **so that** I can monitor proposal performance across all reps.

**Acceptance Criteria:**
1. Dashboard shows: total proposals (all users), overall open rate, overall accepted rate
2. "Top performing rep" — the sales rep with the most accepted proposals
3. Table of all proposals with: rep name, client, status, view count, last viewed at
4. Filter by rep or status
5. Accessible only by users with `role = admin`

---

### Epic 5 — Notifications
**Key:** WB-005 | Priority: Medium

---

#### Story WB-004 — Email Notification on Proposal View
**As a** Sales Rep, **I want** an email notification when a client views my proposal, **so that** I can follow up promptly.

**Acceptance Criteria:**
1. Opening a proposal's public link triggers an email to the proposal owner
2. Email subject: "Your proposal for [Client Name] was viewed"
3. Email body includes: client name, proposal link, view timestamp
4. Email is sent via Mailpit/Mailtrap (catchable in dev)
5. Notification is queued (not synchronous) to avoid blocking the client page load
6. If the same proposal is viewed multiple times within 60 minutes, only 1 email is sent (throttle)

---

### Epic 6 — DevOps & Setup
**Key:** WB-003 | Priority: Medium

---

#### Story WB-002 — Docker Compose Setup
**As a** developer, **I want** a `docker-compose.yml` that brings up the entire stack, **so that** reviewers can run the app with a single command.

**Acceptance Criteria:**
1. `docker-compose up` starts: PHP app server, MySQL/PostgreSQL, Mailpit, Redis (if queues used)
2. `.env.example` contains all required environment variables with placeholder values
3. Database migrations run automatically on startup (or a clear `docker-compose exec` command is documented)
4. The app is accessible at `http://localhost:8000` (or documented port) after startup
5. No hardcoded credentials in any tracked file

---

#### Story WB-001 — README & Documentation
**As a** reviewer, **I want** a comprehensive README, **so that** I can understand, run, and evaluate the application.

**Acceptance Criteria:**
1. README covers: prerequisites, local setup steps, how to run with Docker
2. Architecture section explains key design decisions (auth approach, AI integration pattern, queue strategy)
3. Walnut AI usage log: what prompts were used, what was generated, what was modified
4. Known limitations section is honest about what is incomplete or constrained
5. Loom video link is included in the README

---

## 9. Evaluation Criteria

| Criterion | What We Look For | Weight |
|---|---|---|
| **PHP Code Quality** | Clean architecture, PSR-12, SOLID principles, meaningful comments | 25% |
| **Walnut AI Integration** | Depth of use, creativity, clear documentation of AI usage | 25% |
| **Full-Stack Completeness** | Auth, CRUD, analytics, notifications all working end-to-end | 20% |
| **Database Design** | Normalised schema, migrations, seeders with realistic test data | 10% |
| **DevOps / Setup** | Docker setup, `.env` config, README quality | 10% |
| **Code Review Readiness** | Git history, PR-style commits, inline code comments | 10% |

---

## 10. Deliverables Checklist

| # | Deliverable | Required |
|---|---|---|
| 1 | GitHub repository (public or shared) with full commit history | Yes |
| 2 | `README.md` — setup, architecture, Walnut AI log, limitations | Yes |
| 3 | Loom video (10–15 min) — app walkthrough + key decisions | Yes |
| 4 | `docker-compose.yml` — one-command local setup | Yes |
| 5 | Database migrations and seeders | Yes |
| 6 | `.env.example` with all variables | Yes |
| 7 | Walnut AI integration (API or embed) with documentation | Yes |

---

## 11. Bonus Features

| Feature | Notes |
|---|---|
| **Unit / Feature Tests** | PHPUnit or Pest; cover auth, proposal CRUD, policy checks |
| **CI Pipeline** | GitHub Actions running tests + linting on push |
| **API Documentation** | Swagger / OpenAPI spec or Postman collection |
| **Mobile-Responsive UI** | Tested on 375px–768px viewport widths |
| **Queue-based Notifications** | Laravel Horizon or `php artisan queue:work` with Redis |
| **Proposal Clone Feature** | One-click duplicate from list or detail page |

---

## 12. Submission Instructions

| Field | Detail |
|---|---|
| **Submission Method** | Share GitHub repository link via email to the hiring team |
| **Include in README** | Loom video link |
| **Email Subject Line** | `[PHP Assignment] Your Full Name` |
| **Deadline** | 7 days from the date the assignment document was received |
| **Questions** | Reach out to the hiring manager |

> **Note from Assignment:** We evaluate process as much as outcome — honest documentation of challenges is valued. Creative problem-solving under constraints is also evaluated.

---

## Appendix A — Story Priority Matrix

| Priority | Stories |
|---|---|
| **Critical** | WB-018 (access control), WB-019 (create + AI), WB-024 (auth) |
| **High** | WB-021 (proposal list), WB-016 (detail view), WB-015 (edit), WB-011 (AI backend), WB-008 (view tracking), WB-006 (admin analytics) |
| **Medium** | WB-023 (RBAC), WB-007 (sales dashboard), WB-004 (email notification), WB-002 (Docker), WB-001 (README) |
| **Low / Bonus** | WB-014 (clone), WB-013 (delete), WB-012 (status update), WB-010 (AI embed), WB-002 (Docker) |

---

## Appendix B — Suggested Implementation Order

```
Week 1 (Days 1–3):
  Day 1: Docker setup, Laravel install, DB migrations, auth (Breeze)
  Day 2: Role middleware, proposal CRUD (create, list, detail, edit, delete)
  Day 3: Walnut AI integration (API call on create, stored content, regenerate)

Week 1 (Days 4–5):
  Day 4: Proposal view tracking (public token, proposal_views table, view count display)
  Day 5: Admin analytics dashboard, sales rep dashboard with live stats

Week 1 (Days 6–7):
  Day 6: Email notifications (queued), Mailpit config, throttle logic
  Day 7: Polish UI, write README + Walnut AI log, record Loom video
```

---

*End of BRD — SmartProposal v1.0*
