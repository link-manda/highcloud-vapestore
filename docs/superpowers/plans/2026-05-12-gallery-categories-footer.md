# Gallery Categories & Footer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the "Departments" (Categories) typographic list and the minimalist black Footer, while applying responsive fixes to existing Gallery components.

**Architecture:** Component-based architecture using Laravel Blade and Tailwind CSS. The Categories component will use a vertical list with hover transitions. The Footer will be a global layout component.

**Tech Stack:** Laravel Blade, Tailwind CSS, Lucide-like SVG icons (Material Symbols).

---

### Task 1: Apply Task 4 Fixes

**Files:**
- Modify: `resources/views/components/gallery/product-card.blade.php`
- Modify: `resources/views/components/gallery/featured-drops.blade.php`

- [ ] **Step 1: Add lazy loading and adjust mobile padding in product-card**
- [ ] **Step 2: Add responsive offsets in featured-drops**

---

### Task 2: Implement Gallery Categories Component

**Files:**
- Create: `resources/views/components/gallery/categories.blade.php`

- [ ] **Step 1: Create the component with vertical typographic list**
- [ ] **Step 2: Add numeric indexing and hover effects**

---

### Task 3: Implement Gallery Footer Component

**Files:**
- Create: `resources/views/components/gallery/footer.blade.php`

- [ ] **Step 1: Create solid black footer with 4-column grid**

---

### Task 4: Integrate Components

**Files:**
- Modify: `resources/views/pages/home.blade.php`
- Modify: `resources/views/layouts/gallery.blade.php`

- [ ] **Step 1: Replace old category section in home.blade.php**
- [ ] **Step 2: Add footer to gallery layout**

---

### Task 5: Verification & Commit

- [ ] **Step 1: Verify visual alignment with "The Gallery" spec**
- [ ] **Step 2: Commit changes**
