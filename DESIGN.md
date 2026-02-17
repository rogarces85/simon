# Design System for Coach Dashboard

This design system is based on the existing `dashboard.php` and `theme.css`.

## Core Identity
- **Vibe**: Professional sports management, modern, clean, high-performance.
- **Font**: 'Lexend', sans-serif.

## Color Palette

### Light Mode (Default)
- **Primary**: `#0df280` (Emerald Green) - Used for primary actions, active states.
- **Primary Hover**: `#0bc568`
- **Background Main**: `#f8fafc` (Slate 50)
- **Background Card**: `#ffffff` (White)
- **Text Main**: `#0f172a` (Slate 900)
- **Text Muted**: `#64748b` (Slate 500)
- **Border**: `#e2e8f0` (Slate 200)

### Dark Mode
- **Background Main**: `#0f172a` (Slate 900)
- **Background Card**: `#1e293b` (Slate 800)
- **Text Main**: `#f1f5f9` (Slate 100)
- **Text Muted**: `#94a3b8` (Slate 400)
- **Border**: `#334155` (Slate 700)

## UI Components

### Buttons
- **Primary**: Background `var(--primary)`, Text `#0f172a`, Rounded `8px`.
- **Secondary**: Transparent background, Border `var(--border)`, Text `var(--text-main)`.

### Cards
- **Style**: White background (or slate 800 in dark), thin border `var(--border)`, shadow `var(--shadow)`, rounded `8px`.
- **Hover**: Slight lift (`translateY(-4px)`).

### Badges
- **Shape**: Pill-shaped (`border-radius: 9999px`).
- **Emerald**: Background `rgba(13, 242, 128, 0.1)`, Text `var(--primary)`.
- **Blue**: Background `rgba(59, 130, 246, 0.1)`, Text `#3b82f6`.
- **Red**: Background `rgba(239, 68, 68, 0.1)`, Text `#ef4444`.

### Navigation
- **Sidebar**: Fixed width `280px`, light background.
- **Links**: Flex layout, `lucide` icons, hover effect with primary color tint.

## Layout
- **Container**: Max width `1200px`.
- **Grid**: Responsive grids for stats and cards.
