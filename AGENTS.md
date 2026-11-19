# Project Guidelines & Rules

## Slash Commands & Skills

### `ask-matt` & `/ask-matt`
- When the user prefixes their prompt with `ask-matt` or `/ask-matt` (e.g. `ask-matt <คำถามหรือรายละเอียดงาน>` or `/ask-matt <คำถามหรือรายละเอียดงาน>`):
  1. Follow the guidance in [.agents/skills/ask-matt/SKILL.md](file:///c:/IT%20Annoucement%20-%20PHP/.agents/skills/ask-matt/SKILL.md).
  2. Act as the **Ask Matt router**: analyze the user's situation and determine which development workflow fits best:
     - **Idea → Ship (Main Flow)**: `/grill-with-docs` (interview & refine idea, build ADRs/GLOSSARY) → `/prototype` (if needed) → `/to-spec` & `/to-tickets` → `/implement` (via `/tdd` & `/code-review`) → `/retro`.
     - **Bug Diagnosis**: `/diagnosing-bugs` (build tight feedback loop & regression test before theorizing).
     - **Large / Foggy Greenfield Project**: `/wayfinder` (map decision tickets before writing specs).
     - **Bugs / Requests Piling Up**: `/triage` (triage roles, verify, agent-ready issues).
     - **Codebase Health / Architecture**: `/improve-codebase-architecture` & `/codebase-design`.
     - **Need Clarification / Interview**: `/grilling` or `/grill-me`.
  3. Respond directly by identifying the matching flow/phase and recommending or executing the immediate next step.

---

<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>
