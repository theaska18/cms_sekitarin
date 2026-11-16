# TASK: Add Finance Group Menu and Menu Items to Existing CMSNivo

You are a senior software engineer working on the existing CMSNivo application.

Your task is to add a Finance navigation structure into the existing Group Menu and Menu management system.

IMPORTANT:
This task ONLY covers navigation structure, menu database records, menu integration, routing registration, and permissions.

DO NOT implement Finance business logic, accounting calculations, transaction processing, invoice processing, or new Finance business modules.

DO NOT create a new application.

---

# PHASE 1 – ANALYZE EXISTING MENU SYSTEM

Before making any changes, perform a complete analysis of the existing application.

## 1. Project Analysis

1. Read AGENTS.md and all applicable project instructions.
2. Analyze the existing project structure.
3. Identify the technology stack and framework.
4. Inspect the README and package manifests.
5. Understand the existing application architecture.
6. Identify the existing database connection configuration.
7. Identify the authentication and authorization mechanisms.
8. Identify the multi-tenant architecture, if applicable.

## 2. Group Menu and Menu Analysis

Inspect the existing Group Menu and Menu implementation.

Identify:

- Group Menu database table.
- Menu database table.
- Primary keys.
- Foreign keys.
- Column definitions.
- Indexes and unique constraints.
- Group-to-menu relationships.
- Menu identifiers.
- Menu labels and descriptions.
- Menu icons.
- Menu routes and URLs.
- Menu ordering and sorting.
- Active/inactive status.
- Parent-child menu relationships, if supported.
- Tenant association, if applicable.
- Permission and role relationships.

Inspect existing menu CRUD functionality.

Find existing examples of other menu groups and understand how they are registered.

## 3. Sidebar and Navigation Analysis

Inspect:

- Sidebar implementation.
- Navigation rendering logic.
- Group Menu display logic.
- Menu display logic.
- Nested menu behavior.
- Route registration.
- Permission filtering.
- Active menu highlighting.
- Icon mapping.
- Responsive navigation behavior.

Do not assume any table names, columns, route structures, or framework conventions.

Use the existing implementation as the source of truth.

## 4. Database Migration Analysis

Identify:

- Existing database migration mechanism.
- Existing seeders.
- Database schema management conventions.
- Existing menu insertion patterns.
- Existing migration rollback procedures.

Determine the safest method to add the Finance Group Menu and its menu records.

## 5. Phase 1 Output

Present a detailed analysis containing:

1. Existing technology stack.
2. Existing Group Menu table structure.
3. Existing Menu table structure.
4. Relationships between Group Menu and Menu.
5. Existing menu creation mechanism.
6. Sidebar and navigation architecture.
7. Permission and authorization architecture.
8. Existing migration/seeder mechanism.
9. Proposed database changes.
10. Proposed files to create or modify.
11. Potential risks and conflicts.
12. Implementation plan.

IMPORTANT:
PHASE 1 MUST BE READ-ONLY.

Do not modify source files.
Do not create database tables.
Do not insert database records.
Do not execute migrations.
Do not modify existing database records.

Wait for user approval before proceeding.

---

# PHASE 2 – CREATE FINANCE GROUP MENU

After approval, create one Group Menu:

Group Name: Finance

Follow the existing Group Menu implementation.

Configure the following properties according to existing conventions:

- Group identifier.
- Group name.
- Display label.
- Icon.
- Sort order.
- Active status.
- Tenant association, if applicable.
- Any other required fields.

Use an appropriate finance-related icon consistent with the existing icon library.

Do not introduce a new Group Menu structure.

---

# PHASE 3 – ADD FINANCE MENU ITEMS

Create the following menu records under the Finance Group Menu.

## 1. Dashboard

- Dashboard

## 2. Transactions

- All Transactions
- Income
- Expenses
- Cash/Bank Transfer

## 3. Sales

- Sales Invoices
- Customer Payments
- Accounts Receivable

## 4. Purchases

- Purchase Bills
- Supplier Payments
- Accounts Payable

## 5. Cash & Bank

- Cash Accounts
- Bank Accounts
- Bank Reconciliation

## 6. Accounting

- Chart of Accounts
- Journal Entries
- General Ledger
- Opening Balances
- Accounting Periods

## 7. Reports

- Profit & Loss
- Balance Sheet
- Cash Flow
- Trial Balance
- Accounts Receivable Report
- Accounts Payable Report

## 8. Master Data

- Customers
- Suppliers
- Categories
- Departments
- Tax Configuration

## 9. Settings

- Financial Settings
- Fiscal Year
- Numbering Configuration

TOTAL:
34 menu items under one Finance Group Menu.

Follow the existing application conventions for:

- Menu identifiers.
- Menu names.
- Menu labels.
- Route names.
- URLs.
- Icons.
- Sort order.
- Parent-child relationships, if supported.
- Active status.
- Permission associations.

If the existing system supports nested groups or parent menus, use its existing hierarchy mechanism.

Otherwise, register these items using the existing Group Menu and Menu relationship.

Do not invent a new hierarchy mechanism.

---

# PHASE 4 – DATABASE IMPLEMENTATION

Database operations are part of this task.

## 1. Database Inspection

Before modifying the database:

1. Inspect the actual database schema.
2. Identify existing Group Menu and Menu tables.
3. Inspect all relevant columns.
4. Inspect primary keys and foreign keys.
5. Inspect indexes and unique constraints.
6. Inspect existing menu records.
7. Identify existing permission relationships.
8. Identify tenant relationships.
9. Determine which fields are mandatory.

Never assume database table names or column names.

## 2. Database Changes

Reuse existing Group Menu and Menu tables.

Required operations:

1. Insert the Finance Group Menu.
2. Insert all 34 Finance menu records.
3. Link all menu records to the correct Finance Group Menu ID.
4. Configure menu ordering.
5. Configure menu identifiers.
6. Configure icons.
7. Configure routes according to existing conventions.
8. Configure active status.
9. Preserve existing permissions.
10. Preserve existing menu records.

Do not create duplicate menu tables.

Do not modify unrelated database tables.

Do not overwrite existing menu records.

## 3. Migration and Seeder

Use the existing migration or seeder mechanism.

Requirements:

- Create migration or seeder scripts following project conventions.
- Make database operations idempotent.
- Prevent duplicate Group Menu records.
- Prevent duplicate Menu records.
- Use existing unique identifiers or stable menu keys.
- Maintain correct foreign key relationships.
- Support safe rollback where applicable.
- Do not delete unrelated records during rollback.

If the existing application uses seeders instead of migrations for menu registration, follow that convention.

## 4. Database Safety

Before executing any database changes:

1. Present the proposed database operations.
2. Present the migration or seeder plan.
3. Explain the expected records to be inserted.
4. Identify potential conflicts with existing records.
5. Request user approval.

Do not execute database changes without approval.

After approval:

- Execute the migration or seeder.
- Verify the Finance Group Menu record.
- Verify all 34 menu records.
- Verify Group Menu relationships.
- Verify there are no duplicate records.
- Verify existing records remain unchanged.

---

# PHASE 5 – ROUTING AND MENU INTEGRATION

Integrate the new Finance menus into the existing application.

Requirements:

1. Follow existing route conventions.
2. Register menu routes according to existing architecture.
3. Ensure the Finance Group Menu appears in the existing sidebar.
4. Ensure all menu items appear under the correct group.
5. Configure appropriate icons.
6. Configure correct menu ordering.
7. Respect existing role and permission mechanisms.
8. Preserve existing navigation behavior.
9. Ensure active menu highlighting works correctly.
10. Ensure responsive sidebar behavior remains consistent.

## Routes Without Implemented Pages

This task does not include Finance business functionality.

If a menu does not have an existing page:

- Use the application's existing approach for pending or unavailable routes.
- Do not create fake functional pages.
- Do not point multiple unrelated menus to an arbitrary existing page.
- Do not create placeholder business logic.
- Clearly report which routes still require future implementation.

---

# PHASE 6 – PERMISSION AND AUTHORIZATION

Inspect the existing authorization mechanism.

Requirements:

1. Preserve existing role definitions.
2. Follow existing permission naming conventions.
3. Register Finance permissions if required by the current system.
4. Ensure menu visibility follows existing permission rules.
5. Ensure unauthorized users cannot access protected routes.
6. Preserve tenant-level permission isolation, if applicable.
7. Do not grant additional privileges to existing users without approval.

Do not bypass the existing authentication or authorization system.

---

# PHASE 7 – UI AND DESIGN CONSISTENCY

Follow the existing CMSNivo design system.

Requirements:

- Reuse existing sidebar components.
- Reuse existing menu components.
- Reuse existing icon libraries.
- Follow existing typography.
- Follow existing spacing.
- Follow existing color conventions.
- Preserve existing navigation layout.
- Maintain responsive behavior.
- Avoid introducing a new UI framework.
- Do not redesign unrelated pages.

The Finance menu must look consistent with all existing menu groups.

---

# PHASE 8 – TESTING AND VALIDATION

After implementation, verify the following.

## Database Validation

1. Finance Group Menu exists.
2. All 34 menu records exist.
3. Every menu references the correct Group Menu.
4. No duplicate menu records exist.
5. Menu ordering is correct.
6. Required fields are populated.
7. Existing menu records remain unchanged.
8. Foreign key relationships are valid.

## Application Validation

1. Finance appears in the sidebar.
2. All menu items are displayed correctly.
3. Menu ordering is correct.
4. Icons render correctly.
5. Active menu highlighting works.
6. Existing navigation still works.
7. Permission filtering works.
8. Unauthorized access is handled correctly.
9. Tenant isolation is preserved.
10. No existing functionality is broken.

## Build and Test Validation

Use the actual commands discovered from README, package manifests, and project configuration.

Execute relevant:

- Build commands.
- Unit tests.
- Integration tests.
- Migration/seeder tests.
- Existing menu-related tests.

Do not invent commands.

Do not claim successful tests unless they were actually executed.

---

# PHASE 9 – FINAL IMPLEMENTATION REPORT

Provide a detailed final report containing:

## 1. Summary
- Finance Group Menu status.
- Total menu items created.
- Implementation status.

## 2. Database
- Existing tables reused.
- Database records inserted.
- Migration files created.
- Seeder files created.
- Relationships configured.
- Verification results.

## 3. Source Code
- Files created.
- Files modified.
- Routing changes.
- Sidebar changes.
- Permission changes.

## 4. Validation
- Database verification.
- Navigation verification.
- Permission verification.
- Build results.
- Test results.

## 5. Remaining Work
- Menus without implemented pages.
- Routes requiring future implementation.
- Any known limitations.

---

# STRICT DEVELOPMENT RULES

1. Never create a new application.
2. Never create duplicate Group Menu or Menu tables.
3. Never assume database schemas.
4. Never modify unrelated modules.
5. Never delete existing menu records.
6. Never overwrite existing menu configurations unnecessarily.
7. Never bypass authentication or authorization.
8. Never compromise tenant isolation.
9. Never use placeholder credentials.
10. Never execute database modifications without approval.
11. Never fabricate test results.
12. Follow AGENTS.md instructions.
13. Follow the existing coding conventions.
14. Prefer minimal and incremental changes.
15. Keep the existing application operational.

# EXECUTION INSTRUCTIONS

Work sequentially.

START WITH PHASE 1 ONLY.

PHASE 1 MUST BE READ-ONLY.

Present the complete analysis and proposed implementation plan.

Do not modify source code or database until the user explicitly approves the implementation plan.

After approval, proceed with the remaining phases incrementally.