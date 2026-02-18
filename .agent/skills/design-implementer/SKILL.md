---
name: design-implementer
description: Generates UI designs for specific pages or identifies undesigned pages using the project's DESIGN.md system and Stitch.
---

# Design Implementer Skill

This skill helps you generate UI designs for the Simon project using the `stitch` MCP server and the local `DESIGN.md` file.

## Goal
To streamline the process of creating consistent, high-quality UI designs for the application by leveraging the existing design system.

## When to use this skill
- When the user asks to "design a page" (e.g., "design the login page").
- When the user asks to "find pages without design" or "complete the design".
- When the user wants to apply the `DESIGN.md` style to a specific file.

## Step-by-Step Instructions

### 1. Load the Design System
- Read the content of `DESIGN.md` in the project root.
- Extract the core design principles:
    - **Vibe/Theme**: Professional sports management, modern, clean.
    - **Colors**: Emerald Green (#0df280) primary, Slate text/backgrounds.
    - **Typography**: 'Lexend', sans-serif.
    - **Components**: Cards (white/slate-800, shadow), Buttons (primary/secondary), Badges (pill-shaped).

### 2. Identify the Target Page
- **If the user specifies a page**: Use that page name (e.g., "Login", "Profile").
- **If the user asks to find undesigned pages**:
    - List all `.php` files in the root and `views/` directories using `list_dir`.
    - Look for files that likely contain UI (e.g., `login.php`, `register.php`, `profile.php`, `dashboard.php`) but may lack the new design.
    - Ask the user which one they want to design if it's not obvious.

### 3. Generate the Design with Stitch
- Construct a detailed prompt for `stitch_generate_screen_from_text` (or `stitch_create_project` if a new project is needed).
- **Prompt Template**:
    ```text
    Create a [Page Name] for a sports management web app.
    
    Design System Requirements:
    [Insert summary of colors, fonts, and component styles extracted from DESIGN.md in Step 1]
    
    Page Specifics:
    [Add specific requirements for the page here, based on its filename or user description]
    ```
- **Context**: Pass the `projectId` if you are working within an existing Stitch project (check conversation history or ask user). If not, create a new project named "Simon App".
- Call the `stitch` tool.

### 4. Present the Result
- Show the generated Stitch preview URL to the user.
- Ask if they want to proceed with implementing this design into the code.

## Constraints
- Always check `DESIGN.md` for the latest color codes and component styles.
- Use the `stitch` MCP tool for generation.
