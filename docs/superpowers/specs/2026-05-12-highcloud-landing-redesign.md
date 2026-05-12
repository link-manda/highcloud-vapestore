# Design Spec: Highcloud Vapestore Landing Page Redesign - "The Gallery"

**Date:** 2026-05-12
**Status:** Approved
**Topic:** Landing Page Redesign
**Concept:** The Gallery (Minimalist Editorial & Pure Premium Light Mode)

## 1. Overview
Redesain total landing page Highcloud Vapestore dari tema "Tropical Futurism" yang gelap dan ramai menjadi "The Gallery" yang minimalis, bersih (Light Mode), dan berestetika editorial kelas atas. Fokus utama adalah pada *negative space*, tipografi kuat, dan layout asimetris yang menyerupai majalah fashion/lifestyle premium.

## 2. Visual Identity

### 2.1 Color Palette
- **Primary Background:** `#FAFAFA` (Clean off-white)
- **Secondary Background:** `#FFFFFF` (Pure white for cards/sections)
- **Deep Background:** `#000000` (Used for Footer and primary CTAs)
- **Primary Text:** `#000000` (Headings)
- **Secondary Text:** `#404040` (Body text for readability)
- **Muted Text/Labels:** `#999999`
- **Subtle Borders:** `#EEEEEE` (1px solid)

### 2.2 Typography
- **Headings:** `Plus Jakarta Sans`
  - Style: Extrabold
  - Letter-spacing: `-0.04em` (Tight for Display)
- **Body:** `Manrope`
  - Style: Medium/Regular
  - Line-height: `1.6` (Spacious)
- **Labels:** `Plus Jakarta Sans`
  - Style: Bold, Uppercase
  - Letter-spacing: `0.2em`

### 2.3 Visual Effects
- **Shadows:** Large soft shadows (`blur: 100px`, `opacity: 5%`, `color: #000000`) to create depth without harsh edges.
- **Interactions:** 
  - Smooth transitions (300-500ms) for all hover states.
  - Subtle scale effects (1.02x to 1.05x).
  - Floating animations for hero imagery.

## 3. Section Architecture

### 3.1 Header & Hero
- **Navigation:** Transparent, minimal links with wide tracking.
- **Layout:** Asymmetric. Large display typography ("Elevate The Cloud") with staggered positioning.
- **Imagery:** One primary premium product with a floating effect and large background text ("AESTHETIC").
- **Call to Action:** Black pill-shaped button with white text.

### 3.2 Featured Drops (Collections)
- **Grid:** Asymmetric 12-column grid.
- **Card Design:** Pure white containers, no borders, large padding around product images.
- **Metadata:** Minimal (Category + Name). No prices on landing page to maintain clean aesthetic.
- **Interaction:** Cards lift on hover.

### 3.3 Departments (Categories)
- **Layout:** Vertical typographic list.
- **Index:** Numeric indexing (01, 02, etc.).
- **Hover:** Horizontal expansion with arrow reveal and background shift to white.

### 3.4 Footer
- **Background:** Solid black (`#000000`).
- **Text:** White (`#FFFFFF`) with varying opacities for hierarchy.
- **Structure:** 4-column grid (Brand info + 3 link columns).

## 4. Technical Constraints
- **Framework:** Laravel Blade + Tailwind CSS.
- **Icons:** SVG only (no emojis). Use Material Symbols Outlined or Lucide.
- **Responsive:** Mobile-first approach. Stack asymmetric elements vertically on smaller screens.
- **Performance:** Maintain light assets to ensure fast initial load (Gallery feel requires speed).

---
*Verified and approved by user.*
