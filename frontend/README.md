# Campus Esports Ethiopia - Frontend (React + Inertia + TypeScript)

This directory provides the companion frontend workspace for the Campus Esports Ethiopia platform.

## Architecture

- **Engine:** React 19 + Inertia.js 2.x + TypeScript + Tailwind CSS 4.x
- **Source Code Location:** `backend/resources/js`
  - `pages/`: Inertia pages (Landing, Auth, Profile, Dashboard)
  - `components/ui/`: Accessible base UI library (Button, Input, Card, Badge, Modal, ThemeSwitcher, LanguageSwitcher)
  - `layouts/`: AppLayout with responsive header, Ethiopian university network footer, flash alerts
  - `hooks/`: `useTranslation` (English & Amharic), `useTheme` (Dark & Light)
  - `i18n/`: English (`en.json`) and Amharic (`am.json`) dictionaries
- **Asset Compilation:** Vite outputs to `backend/public/build/`

## Available Commands

From either this `/frontend` directory or `/backend`:
- `npm run dev`: Launch Vite dev server with Hot Module Replacement
- `npm run build`: Production build and asset compression
- `npm run lint`: Run TypeScript type-checking without emitting files
