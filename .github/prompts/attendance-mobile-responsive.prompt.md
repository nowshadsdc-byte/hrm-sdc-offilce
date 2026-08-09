---
description: "Make Attendance page tables mobile responsive in this Laravel + Inertia app"
name: "Attendance Tables: Mobile Responsive"
argument-hint: "Optional scope (e.g., all attendances pages, only index page, specific route)"
agent: "agent"
---
You are improving mobile responsiveness for Attendance-related table UIs in this repository.

Use the user-provided prompt argument as scope when present. If no scope is provided, default to all Attendance pages and components.

Requirements:
1. Find all Attendance table views/components in this workspace (Inertia pages and Blade components).
2. Make tables mobile-friendly without breaking desktop layouts.
3. Prefer existing project patterns and Tailwind utility conventions.
4. Preserve semantics and accessibility (table headers, readable labels, keyboard navigation).
5. Avoid changing backend business logic unless strictly required for display.
6. Keep changes minimal and scoped to responsiveness.

Implementation guidance:
- On small screens, use one of these patterns based on current UI structure:
  - Horizontal scroll container with clear spacing and min widths.
  - Stacked/card representation for each row while preserving column labels.
  - Hybrid pattern (scroll on small tablets, cards on very small phones).
- Ensure long values (timestamps, names, remarks) wrap or truncate gracefully.
- Keep action buttons usable on touch devices.

Project-specific checks:
- Include Attendance pages under resources/js/pages/Attendances and shared table UI under resources/views/components/attendance/table.blade.php when applicable.
- If files are missing or routes reference non-existent pages, report that clearly before editing.

Verification:
1. Run the minimum relevant tests after changes.
2. If PHP files changed, run vendor/bin/pint --dirty --format agent.
3. Summarize updated files and explain the responsive strategy used per file.

Output format:
- Files changed
- What changed (mobile behavior)
- Why this approach was chosen
- Verification results
