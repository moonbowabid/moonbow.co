=== Case Study Carousel ===
Contributors: Ewall developer team
Tags: case studies, custom post type, acf, shortcode, carousel, grid
Stable tag: 1.1.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A dynamic solution to display related case studies as a grid or carousel based on selection and count.

== Description ==

The Case Study Carousel functionality displays **related case studies** selected
from the Case Study edit page.

This implementation no longer relies on the old standalone plugin logic. Instead,
it dynamically pulls **selected related case studies** and renders them based on count.

Display behavior:

- If **3 related case studies are selected**, they are displayed as a **carousel (slider)**.
- If **2 or fewer case studies are selected**, they are displayed in a **grid layout**.
- If **no related case studies are selected**, the system will automatically fetch the **latest 2 published case studies** and display them in a grid.

This ensures the section always has content and maintains a consistent layout.

== Features ==

* Uses selected related case studies from the edit page
* Automatic fallback to latest published case studies
* Dynamic layout switching (Grid / Carousel)
* Carousel enabled only when exactly 3 items are available
* Clean and maintainable structure
* No dependency on old unused files or legacy implementation

== Notes ==

* Old carousel-related files are no longer in use and can be safely ignored.
* Ensure related case studies are selected in the edit page to control output.
* Fallback content (latest case studies) is automatically handled when no selection is made.
