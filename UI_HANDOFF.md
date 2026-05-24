# UI Handoff Notes

## Pages important for UI improvement
- `index.php` – homepage with trending products and recommendation widget
- `filtrage.php` – product search and filter results
- `product.php` – product detail / quick-view content
- `ajouter_panier.php` / cart offcanvas in `index.php` – cart interaction flow
- `effectuer_commande.php` – checkout page
- `confirmation.php` – order confirmation page
- `interface_admin.php` – admin page if admin UI should be refreshed

## Current visual / UX issues
- The homepage is built from an older Bootstrap template and feels dated
- Product cards are inconsistent in spacing and CTA placement
- Recommended cards should better match the existing trending product card design
- The recommendation section is inserted as a separate widget but does not feel fully integrated
- Some card actions and buttons are placeholder-style and items use hard-coded text
- The quick-view/product modal experience is not clearly separated from page navigation
- The UI mixes inline styling, template HTML, and custom CSS, which makes design updates fragile
- The user recommendation widget only appears for logged-in users, so guest state should be handled gracefully in UI design

## Existing design/template base
- Bootstrap 5 layout and base components are used throughout
- `style.css` contains the main visual theme and custom overrides
- Third-party CSS is in `css/normalize.css` and `css/vendor.css`
- `js/script.js` provides product sliders, quantity controls, and modal behavior
- The storefront uses Bootstrap grid, badges, buttons, cards, and utility classes
- Do not replace Bootstrap with a different frontend framework

## Constraints for redesign
- Do not break backend logic: keep PHP server-side pages, POST forms, and session flows intact
- Preserve PHP session auth and cart flow (`$_SESSION`, `ajouter_panier.php`, `effectuer_commande.php`)
- Preserve routing and page structure; avoid converting to SPA or React
- Keep the existing PHP + Bootstrap technology stack
- Keep `connexionbd.php` as the DB connection entry point for backend logic
- Do not remove or replace the FastAPI recommendation integration
- Maintain the current `product.php` quick-view / add-to-cart mechanism

## Specific homepage improvement goals
- Make the homepage feel cohesive and modern while preserving the existing Bootstrap structure
- Improve product card layout consistency, including image, title, price, badge, and CTA
- Ensure recommendation cards visually match trending cards:
  - same card dimensions and spacing
  - same image ratio
  - same badge and price styling
  - same CTA button look and positioning
- Ensure recommended products are clearly labeled as personalized recommendations
- Make the recommendation block responsive and consistent with the rest of the page
- Preserve the user experience for logged-in users and show a friendly fallback or call-to-action for guests
- Keep mobile usability strong for product grids and add-to-cart controls

## Recommendation card guidance
- Use the same visual design language as trending cards
- Keep the green `Recommended` badge or similar highlight
- Match border, shadow, hover states, and button styling to trending cards
- Keep product metadata, price, and category text aligned with existing cards
- Avoid introducing a completely different card style for recommendations

## Notes for the designer
- The recommendation section should feel like a natural extension of the homepage, not a separate section pasted in
- Use existing brand colors and Bootstrap spacing rules from `style.css`
- Focus first on homepage and product card consistency, then refine filters and checkout pages
- If updating the admin page, do so only for style consistency, not backend workflows

## Recommended screenshots to capture for Kimi
- Homepage full view including trending and recommendation sections
- Recommendation widget displayed for a logged-in user
- Product card details and quick-view modal state
- Filter/search page (`filtrage.php`) with active filter controls
- Cart offcanvas and checkout page `effectuer_commande.php`
- Admin dashboard `interface_admin.php` if admin UI is part of scope
- Streamlit dashboard screens only if dashboard UI is also part of the handoff
