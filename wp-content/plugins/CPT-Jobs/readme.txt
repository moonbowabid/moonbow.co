=== CPT Jobs ===
Contributors: Ewall Developer Team
Tags: custom post type, jobs, careers, taxonomy, acf, shortcode
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight custom post type plugin to manage career job listings with department taxonomy and shortcode rendering.

== Description ==

CPT Jobs allows you to create and manage job listings inside WordPress.

Features:

- Custom Post Type: Jobs
- Custom Taxonomy: Job Departments
- ACF Field Support:
  - Job Location (Text)
  - Job Timings (Checkbox: Full-time / Part-time)
  - Job Apply Link (URL/Text)
- Frontend rendering via shortcode
- Clean BEM class structure
- Lightweight and developer-friendly architecture

This plugin is ideal for building a dynamic “We Are Hiring” section on your website.

== Installation ==

1. Upload the `CPT-Jobs` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to "Jobs" in the WordPress admin to add job listings.
4. Add the shortcode `[render_jobs]` to any page to display job listings.

== Usage ==

After activation:

1. Create Departments under:
   Jobs → Job Departments

2. Add a new Job:
   Jobs → Add New

3. Fill in:
   - Title
   - Description
   - Department
   - Job Location
   - Job Timings
   - Job Apply Link

4. Insert shortcode into a page:

   [render_jobs]

All job listings will be displayed dynamically.

== Shortcode ==

[render_jobs]

Displays all published job posts including:

- Department label
- Job title
- Description
- Job timings
- Location
- Apply button

== Custom Post Type ==

Post Type Slug:
job

== Taxonomy ==

Taxonomy Slug:
job-department

Hierarchical:
Yes (checkbox style)

== ACF Field Requirements ==

This plugin expects the following ACF fields attached to the "job" post type:

1. Field Label: Job Location
   Field Name: job_location
   Field Type: Text

2. Field Label: Job Timings
   Field Name: job_timings
   Field Type: Checkbox
   Choices:
     full_time : Full-time
     part_time : Part-time

3. Field Label: Job Apply Link
   Field Name: job_apply_link
   Field Type: Text or URL

== Folder Structure ==

CPT-Jobs/
│
├── assets/
├── includes/
│   ├── enqueue.php
│   ├── helpers.php
│   └── shortcodes.php
├── templates/
└── career-jobs-plugin.php

== Changelog ==

= 1.0.0 =
- Initial release
- Custom Post Type: Job
- Taxonomy: Job Department
- Shortcode rendering
- BEM class structure

== License ==

This plugin is licensed under the GPL v2 or later.

