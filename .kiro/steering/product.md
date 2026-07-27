# Product Overview

This repository is **SafetyDojo**, a Learning Management System (LMS) built on top of **Moodle** (currently version 5.2.1, branch 502).

It is a full Moodle core codebase (not a plugin-only repo) with a handful of site-specific customizations layered in:

- `theme/boost_union` — the active theme, a Moodle Boost child theme with extended layout/customization options (flavours, smart menus, accessibility settings).
- `public/local/dashboard` — a lightweight custom local plugin for a site dashboard.
- `public/local/kopere_dashboard` — a third-party "Kopere Dashboard" admin/reporting dashboard plugin.
- `public/local/kopere_bi` — a third-party "Kopere BI" business-intelligence/reporting plugin (blocks + filters for BI views).

Everything else in the tree is stock Moodle core (courses, activities/`mod`, blocks, admin tools, etc.) used to deliver online courses, activities, grading, and reporting to learners and administrators — i.e. standard Moodle LMS functionality, themed and extended for SafetyDojo's needs.

When making changes, assume:
- Core Moodle directories (`public/`, `lib/`, `admin/`) should be treated as upstream code — avoid modifying core files directly; prefer plugin/theme-level overrides and hooks.
- Custom/local functionality belongs in `public/local/*` or `theme/boost_union/*`.
