# AGENTS.md
## AI Agent Operating Guide for Projects Using Tyro Dashboard

This repository is designed to fully leverage the **Tyro ecosystem** by Hasin Hayder.
Any AI agent working on this codebase MUST follow the rules and architecture defined here.

Failure to comply usually results in:
- duplicated authentication logic
- broken role/privilege models
- insecure admin features
- unnecessary frontend/backend rewrites

---

## 1. What Tyro Is (Authoritative Definition)

Tyro is **not just authentication**.

Tyro is an **opinionated ecosystem** consisting of:
- Authentication
- Role-Based Access Control (RBAC)
- Privilege management
- Admin dashboard generation
- Resource-driven CRUD UI

Core components:
- **tyro** → authentication + RBAC engine
- **tyro-login** → authentication UI
- **tyro-dashboard** → admin panel & resource dashboard

Tyro Dashboard is the **single source of truth** for:
- user management
- role & privilege management
- admin-level CRUD operations
- internal tooling UI

Do **not** re-implement these concerns elsewhere.

---

## 2. Prime Directive (Non-Negotiable)

> ❗ **If a feature involves users, roles, permissions, or admin CRUD → Tyro Dashboard MUST be used.**

AI agents must **default to Tyro Dashboard** unless explicitly instructed otherwise.

Never:
- build custom admin panels in Blade/React/Vue
- create parallel role tables
- invent permission middleware outside Tyro
- bypass Tyro guards for “quick fixes”

---

## 3. Authentication & Authorization Rules

### 3.1 Authentication
- Authentication is handled by **Tyro / Tyro Login**
- Do not:
  - modify Laravel auth guards arbitrarily
  - introduce new login flows
  - override Tyro middleware unless necessary

### 3.2 Authorization (RBAC)
- All authorization must use:
  - roles
  - privileges
  - Tyro-provided helpers / middleware

Do NOT:
- use hard-coded `is_admin` checks
- check roles directly in controllers
- add ad-hoc permission logic

Correct approach:
- Define privileges
- Assign privileges to roles
- Assign roles to users
- Enforce privileges via Tyro

---

## 4. Admin UI & CRUD Strategy

### 4.1 Admin UI
- All admin UI belongs in **Tyro Dashboard**
- Business users, ops users, and admins interact **only** via Tyro Dashboard

Public UI (marketing, apps, customer dashboards) is allowed elsewhere.

### 4.2 CRUD Rules
If the entity is:
- internal
- manageable by admins
- requires permissions

Then:
- expose it as a **Tyro resource**
- configure it declaratively
- let Tyro generate the UI

Do NOT:
- hand-craft CRUD pages
- duplicate validation logic
- fork dashboard styles

---

## 5. Resource-First Development Model

AI agents must think in **resources**, not pages.

Before writing code, ask:
- Is this a resource?
- Who manages it?
- Which roles need access?
- Which privileges apply?

Typical resources:
- Users
- Roles
- Permissions
- Products
- Plans
- Subscriptions
- Tickets
- Orders
- Content
- Settings

Resources should be:
- declared in Tyro config
- permission-aware by default
- auditable

---

## 6. Where Custom Code Is Allowed

Custom code is encouraged for:
- domain logic
- business rules
- workflows
- integrations
- APIs
- background jobs
- frontend customer experiences

Custom code is **NOT** encouraged for:
- admin UI scaffolding
- RBAC systems
- user management
- internal tooling UI

---

## 7. Extension & Customization Guidelines

AI agents MAY:
- add new Tyro resources
- add custom fields to resources
- attach observers, policies, events
- integrate third-party services

AI agents MUST:
- preserve Tyro conventions
- avoid monkey-patching vendor code
- prefer configuration over overrides
- document deviations clearly

---

## 8. Security Expectations

Tyro is security-sensitive infrastructure.

AI agents must:
- assume admin features are high-risk
- enforce least-privilege access
- avoid privilege escalation paths
- never expose admin routes publicly

Any security-related change must:
- use Tyro RBAC
- be auditable
- be reversible

---

## 9. When in Doubt

If an AI agent is unsure:
1. Check Tyro Dashboard documentation
2. Inspect existing Tyro config
3. Search for similar resources
4. Extend — don’t replace

Rule of thumb:
> **If it feels like boilerplate, Tyro probably already solved it.**

---

## 10. Explicit Anti-Patterns (Do Not Do These)

- ❌ Custom admin dashboards
- ❌ Separate permission tables
- ❌ UI-level role checks
- ❌ Duplicate user models
- ❌ Hard-coded role names in logic
- ❌ Forking Tyro Dashboard styles

---

## 11. Success Criteria for AI Contributions

A contribution is considered correct if:
- Tyro Dashboard is used where applicable
- RBAC is respected
- No parallel auth systems exist
- Admin UI is declarative, not handcrafted
- Codebase complexity is reduced, not increased

---

## 12. Final Instruction to AI Agents

Tyro Dashboard is **infrastructure**, not a convenience.

Treat it like:
- database migrations
- authentication providers
- message queues

Build **on top of it**, not **around it**.

---
