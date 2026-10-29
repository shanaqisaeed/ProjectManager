<div align="center">

# ProjectManager

### Enterprise Project & Task Management System built with Laravel

سامانه مدیریت پروژه و وظایف سازمانی با چارت سازمانی، گردش کار تأیید و سطوح دسترسی پیشرفته

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Pest](https://img.shields.io/badge/Tested_with-Pest-F28D1A)](https://pestphp.com)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)

</div>

---

## Overview

**ProjectManager** is a self-hosted, open-source project management platform for organizations, companies and government agencies. It brings projects, tasks, people and organizational structure together in one place — with multi-stage approval workflows, role-based access control (RBAC), fine-grained per-project permissions, Gantt charts and management reports.

The UI is designed for Persian (RTL) teams with full **Jalali (Shamsi) calendar** support.

## Key Features

| Module | Capabilities |
|---|---|
| **Projects** | Create, edit, archive/unarchive, priorities, statuses, progress tracking |
| **Tasks** | Hierarchical sub-tasks, weighted progress, status flow, supervisor review, chat & file attachments |
| **Gantt Chart** | Visual project timeline per project |
| **Templates** | Reusable project templates with roles, tasks, supervisors and approval stages; save any project as a template |
| **Approval Workflow** | Multi-stage supervisor approvals for projects and member join requests |
| **Organization Chart** | Tree-based organizational structure (organization, units, teams) |
| **Users & Roles** | Global roles (Super Admin, Organization Manager, Unit Manager, Team Lead, Project Manager, Expert, Member, Viewer) |
| **Permissions** | Global project-role permission matrix plus per-user overrides inside each project |
| **Reports** | Project status reports and team workload reports |
| **Dashboard** | Personal overview of projects, tasks and pending approvals |

## Tech Stack

- **Backend:** Laravel 13, PHP 8.3
- **Frontend:** Blade, Tailwind CSS 4, Vite
- **Database:** SQLite / MySQL / PostgreSQL
- **Date:** Jalali calendar via `hekmatinasser/verta`
- **Testing:** Pest

## Installation

```bash
git clone https://github.com/shanaqisaeed/ProjectManager.git
cd ProjectManager

composer install
cp .env.example .env
php artisan key:generate

php artisan migrate --seed

npm install
npm run build
```

Run the development server:

```bash
composer run dev
```

Default admin account (seeded): `admin` / `admin123` — change it immediately in production.

## Testing

```bash
php artisan test --compact
```

## Contributing

Contributions are welcome. Fork the repository, create a feature branch and open a Pull Request.

## License

This project is licensed under the **GNU General Public License v3.0** — see [LICENSE](LICENSE).

You are free to use, modify and distribute this software, including for commercial purposes, provided that:

- Derivative works are released under the same GPL-3.0 license.
- The source code of distributed versions remains available.
- **Original credit is preserved:** "ProjectManager by Saeed Shanaqi — https://github.com/shanaqisaeed/ProjectManager"

## Author

**Saeed Shanaqi**
GitHub: [@shanaqisaeed](https://github.com/shanaqisaeed)

If you find this project useful, please give it a ⭐ on GitHub.

<!-- Keywords: laravel project management, task management system, open source project manager, gantt chart laravel, rbac permissions, approval workflow, organization chart, persian project management, سامانه مدیریت پروژه, مدیریت وظایف, لاراول -->
