# Design System Strategy: Tropical Futurism

## 1. Overview & Creative North Star: "The Neon Monsoon"
This design system is built to transcend the standard e-commerce template. Our Creative North Star is **"The Neon Monsoon"**—a fusion of Bali’s organic, mist-shrouded landscapes and a high-octane, futuristic digital aesthetic. 

We are moving away from the "boxy" internet. To achieve a high-end editorial feel, the layout must embrace **intentional asymmetry** and **tonal depth**. Elements should feel as though they are floating in a humid, neon-lit atmosphere. We use overlapping components, large-scale typography, and subtle Balinese motifs (palm silhouettes and ocean mist) to break the grid, ensuring the interface feels curated, not generated.

---

## 2. Colors & Surface Logic
The palette leverages a deep charcoal base (`#060e20`) to allow neon accents to vibrate.

### The "No-Line" Rule
**Strict Mandate:** Designers are prohibited from using 1px solid borders for sectioning or containment. Boundaries must be defined through:
*   **Background Shifts:** Moving from `surface` (`#060e20`) to `surface-container-low` (`#091328`).
*   **Tonal Transitions:** Using subtle gradients to suggest the end of one content area and the start of another.

### Surface Hierarchy & Nesting
Treat the UI as a series of layered, frosted glass panes. Use the `surface-container` tiers to create "nested" depth:
*   **Background:** `surface` (`#060e20`)
*   **Deepest Level:** `surface-container-lowest` (`#000000`) for immersive hero sections.
*   **Standard Cards:** `surface-container` (`#0f1930`) or `surface-container-high` (`#141f38`).
*   **Interactive Overlays:** `surface-bright` (`#1f2b49`) for high-level importance.

### The "Glass & Gradient" Rule
Floating elements (modals, navigation bars, featured product cards) must use **Glassmorphism**. 
*   **Implementation:** Use a semi-transparent `surface-variant` (`#192540` at 60% opacity) with a `backdrop-filter: blur(20px)`.
*   **Signature Textures:** Main CTAs should never be flat. Use a "Sunset Gradient" transitioning from `primary` (`#ba9eff`) to `primary-dim` (`#8455ef`) at a 135-degree angle to provide visual "soul."

---

## 3. Typography: Editorial Authority
The typography system balances the technical precision of **Manrope** with the expressive, high-fashion energy of **Plus Jakarta Sans**.

*   **Display & Headlines (Plus Jakarta Sans):** Used for brand statements and product names. The large scale (`display-lg` at 3.5rem) should be used with tight letter-spacing (-0.02em) to create an authoritative, "high-end boutique" feel.
*   **Body & Utility (Manrope):** Chosen for its extreme legibility in dark mode. `body-lg` (1rem) is the workhorse for product descriptions, ensuring the user's eye can glide over technical specs without fatigue.
*   **Labels:** Small, uppercase labels (`label-md`) should be tracked out (+0.1em) to add an extra layer of sophistication to technical metadata.

---

## 4. Elevation & Depth: Atmospheric Layering
In this design system, "Elevation" is a lighting effect, not a structural one.

*   **The Layering Principle:** Avoid shadows for static cards. Instead, place a `surface-container-high` element on top of a `surface-container-low` background. The subtle 2-3% shift in luminosity creates a cleaner, more modern "lift."
*   **Ambient Shadows:** For floating elements (e.g., a "Quick Add" FAB), use a custom ambient shadow. The shadow should be `primary-container` (`#ae8dff`) at 8% opacity with a 40px blur, mimicking the glow of neon light hitting Bali's evening mist.
*   **The "Ghost Border" Fallback:** If accessibility requires a stroke, use a "Ghost Border": the `outline-variant` (`#40485d`) token at **15% opacity**. Never use a 100% opaque border.

---

## 5. Components

### High-Contrast Buttons
*   **Primary:** A gradient-fill button (`primary` to `primary-dim`) with `on-primary-fixed` (`#000000`) text. Use `xl` (1.5rem) roundedness for a pill-shape that feels premium.
*   **Secondary:** An "Inner-Glow" button. Use a transparent background with a "Ghost Border" and `secondary` (`#53ddfc`) text. On hover, the background fills with 10% `secondary` opacity.

### Glassmorphic Product Cards
*   **Structure:** No borders. Use `surface-container` with a `backdrop-filter`.
*   **Interaction:** On hover, the card should scale slightly (1.02x) and the ambient glow shadow should intensify.
*   **Content:** Product images should use a "Deep Shadow" to appear 3D, as if sitting on top of the glass.

### Navigation Elements
*   **The "Mist" Header:** A fixed top nav using `surface` at 70% opacity with a heavy blur. 
*   **Asymmetric Menu:** Navigation links should use `headline-sm` with a vertical staggered entrance animation to mimic the organic feel of palm leaves.

### Inputs & Fields
*   **Standard State:** `surface-container-highest` background, no border.
*   **Focus State:** A 1px "Ghost Border" appears and the text `secondary` color glows subtly.
*   **Error State:** Use `error` (`#ff6e84`) sparingly, primarily through a 2px bottom-border "glow" rather than a full box stroke.

---

## 6. Do's and Don'ts

### Do:
*   **Use Whitespace as a Divider:** Use the Spacing Scale (8px, 16px, 32px, 64px) to separate content. If a section feels crowded, increase the vertical margin instead of adding a line.
*   **Embrace the Glow:** Use `tertiary` (`#699cff`) for subtle "ocean mist" background blurs to guide the eye toward conversion points.
*   **Maintain Typography Hierarchy:** Ensure `display-lg` is only used once per scroll-depth to maintain its impact.

### Don't:
*   **Don't use Pure White:** Avoid `#FFFFFF`. Use `on-background` (`#dee5ff`) to keep the "dark mode" softness intact and reduce eye strain.
*   **Don't use Rigid Grids for Imagery:** Overlap product images with Balinese motifs or text to create a high-fashion, editorial layout.
*   **Don't use Standard Shadows:** Never use black/grey shadows. Shadows must be tinted with the surface or accent colors to maintain the "Tropical Futurism" vibe.