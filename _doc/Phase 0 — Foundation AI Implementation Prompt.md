You are a senior Laravel architect, enterprise ERP/HMS developer, database architect, and healthcare software engineer.

We are building an international-standard Hospital Management System (HMS) using:

- PHP 8.4+
- Laravel
- MySQL/PostgreSQL
- Redis
- Blade
- AdminLTE
- REST API
- Modular Monolith architecture

You must implement **Phase 0 — Foundation** only.

The Foundation must provide the reusable platform required by all future HMS modules.

PHASE 0 MODULES:

1. Application Core
2. Organization
3. Hospital
4. Branch
5. Department
6. Authentication
7. Users
8. Roles
9. Permissions
10. Multi-hospital access scope
11. Settings
12. Master Data
13. Audit Logging
14. Activity Logging
15. Security Events
16. File Management
17. Notification Framework
18. Workflow/Approval Framework
19. API Foundation
20. Queue
21. Scheduler
22. Dashboard Framework
23. System Health
24. Localization
25. Testing Foundation

IMPORTANT:

Do NOT implement Patient, OPD, IPD, Emergency, EMR, Pharmacy, Laboratory, Radiology, Billing, Insurance, Procurement, Inventory, Finance, HR, OT, ICU, Blood Bank, or other business modules during Phase 0.

Before making any changes:

1. Inspect the existing project structure.
2. Inspect composer.json.
3. Inspect package.json.
4. Inspect .env.example.
5. Inspect existing migrations.
6. Inspect existing models.
7. Inspect existing controllers.
8. Inspect routes.
9. Inspect middleware.
10. Inspect policies.
11. Inspect tests.
12. Identify installed packages.
13. Reuse existing functionality where possible.
14. Do not overwrite unrelated code.

ARCHITECTURE REQUIREMENTS:

- Use modular/domain-oriented architecture.
- Keep controllers thin.
- Use service classes for business logic.
- Use Form Requests for validation.
- Use Policies/Gates for authorization.
- Use Events/Listeners where appropriate.
- Use Jobs for asynchronous processing.
- Use Redis queues where configured.
- Use database transactions for multi-step operations.
- Use dependency injection.
- Follow PSR-12 and Laravel conventions.
- Avoid unnecessary dependencies.
- Avoid duplicate functionality.
- Avoid giant controllers/models/services.

MULTI-HOSPITAL REQUIREMENTS:

The hierarchy must support:

Organization
    -> Hospital
        -> Branch
            -> Department

Users must have an explicit access scope.

A user assigned to Hospital A must not be able to access Hospital B unless explicitly authorized.

Authorization must be enforced server-side.

DATABASE REQUIREMENTS:

Create normalized tables with:

- Primary keys
- Foreign keys
- Unique constraints
- Appropriate indexes
- Status fields where appropriate
- created_at
- updated_at
- deleted_at only where justified

Do not create an unnecessarily complicated database.

AUTHENTICATION:

Implement:

- Login
- Logout
- Password change
- Password reset
- Session management
- Login history
- Failed login tracking
- Account status
- Email verification architecture
- MFA-ready architecture

AUTHORIZATION:

Implement:

- Roles
- Permissions
- User-role assignment
- Role-permission assignment
- Organization scope
- Hospital scope
- Branch scope
- Department scope

Use granular permissions such as:

core.user.view
core.user.create
core.user.update
core.user.delete

core.role.view
core.role.create
core.role.update

core.hospital.view
core.hospital.create
core.hospital.update

AUDIT:

Implement centralized audit logging.

Track:

- Authentication events
- User changes
- Role changes
- Permission changes
- Settings changes
- Data changes
- Data exports
- Security-sensitive actions

Audit records should contain:

- user_id
- organization_id
- action
- module
- entity_type
- entity_id
- old_values
- new_values
- ip_address
- user_agent
- request_id
- created_at

Do not allow ordinary users to modify or delete audit records.

SETTINGS:

Implement a reusable settings service.

Separate:

Technical configuration:
.env

Business/application settings:
Database

MASTER DATA:

Create a reusable master-data mechanism for simple lookup/configuration data.

NOTIFICATIONS:

Create a notification framework supporting:

- Database
- Email
- SMS-ready
- WhatsApp-ready
- Push-ready

Do not hard-code notification messages in controllers.

FILES:

Create a secure file-management service.

Files must be private by default.

Validate:

- MIME type
- Extension
- File size

Store metadata in the database.

WORKFLOW:

Create a reusable workflow engine supporting:

- Draft
- Submit
- Approve
- Reject
- Return
- Complete
- Sequential approval
- Role-based approval
- User-based approval

API:

Create versioned APIs under:

/api/v1

Use consistent JSON responses.

QUEUE:

Configure Redis queues and Laravel Horizon if appropriate.

SCHEDULER:

Configure Laravel Scheduler.

DASHBOARD:

Create a reusable dashboard/widget architecture.

SYSTEM HEALTH:

Create checks for:

- Application
- Database
- Redis
- Queue
- Storage
- Mail
- Scheduler

SECURITY:

Implement:

- CSRF protection
- Rate limiting
- Secure password hashing
- Authorization
- Session protection
- Secure file upload
- Sensitive-data protection
- Error sanitization
- Security events

TESTING:

Create:

- Unit tests
- Feature tests
- Authorization tests
- Multi-hospital isolation tests
- API tests
- Security tests

CRITICAL TEST:

A user belonging only to Hospital A must not access Hospital B data.

UI:

Use Blade + AdminLTE.

Create a clean menu:

Dashboard

Administration
- Organization
- Hospitals
- Branches
- Departments
- Users
- Roles
- Permissions
- Access Control
- Master Data
- Workflows
- Notifications
- Settings

Security & Audit
- Audit Logs
- Activity Logs
- Security Events
- Login History
- User Sessions

System
- System Health
- Queue Monitor
- Scheduled Jobs
- API Logs
- About

IMPLEMENTATION PROCESS:

Work incrementally.

For each sub-module:

1. Explain the implementation plan.
2. Inspect existing code.
3. Create/update migrations.
4. Create/update models.
5. Create relationships.
6. Implement services.
7. Implement validation.
8. Implement policies.
9. Implement controllers.
10. Implement routes.
11. Implement UI.
12. Implement API where required.
13. Implement audit.
14. Implement tests.
15. Run tests.
16. Review security.
17. Review performance.
18. Update documentation.

Do not implement the next sub-module until the current one is coherent and testable.

AFTER EACH IMPLEMENTATION:

Report:

1. Files created
2. Files modified
3. Database changes
4. Routes added
5. Permissions added
6. Environment variables added
7. Artisan commands required
8. Tests created
9. Test results
10. Security considerations
11. Deployment considerations
12. Rollback considerations

Do not claim that tests passed unless they were actually run.

The final result of Phase 0 must be a secure, testable, reusable HMS platform foundation upon which Patient Management can be implemented as Phase 1.