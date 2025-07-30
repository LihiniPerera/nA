=== Export Products, Orders & Customers for WooCommerce ===
Contributors: wpcodefactory, omardabbas, karzin, anbinder, algoritmika, kousikmukherjeeli
Tags: woocommerce, export
Requires at least: 4.4
Tested up to: 6.8
Stable tag: 2.3.1
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Advanced export tools for all your WooCommerce store data: Orders, Products Customers & More, export to XML or CSV in one click.

== Description ==

Export ALL your WooCommerce store data to CSV or XML using this free plugin, export Products, Orders, Order Items, Customers, and Customers from Orders in single click.

The plugin will allow you to choose from more 100 fields to include in your exported report, more than 30 unique field in each report to select from.

Export & Download your reports to either CSV (with a custom separator of your choice) or XML, and with an easy-to-use interface, the plugin will show each report in a separate tab to have a better control on your reports.

Once you export your data to CSV, you can use your favorite sheets program (Excel or Google Sheets) to rename fields, change order, filter based on categories, tags, and more.

**Plugin Main Features:**

With this plugin, you can do the following:

* Export WooCommerce **Products**: Choose fields like Product ID, Name, SKU, Reg. Price, Sale Price, Image URL, Status, and more than 40 fields to show in your report.
* Export WooCommerce **Orders**: Order ID, Number, Status, Dated, Time, Currency, Payment method, you name it, and the plugin will include it in your report.
* Export WooCommerce **Orders Items**: Customized report for order items (other than Order report) that includes +40 to select from.
* Export WooCommerce **Customers** (from WordPress users table): Export all your customers details in one report, ID, Email, Name (First & Last), Billing & shipping info and more.
* Export WooCommerce **Customers from Orders**: Retrieve customers information from your orders with this customized report.

**General Options** of the plugin include:

* CSV custom separator.
* CSV wrap.
* UTF-8 BOM.
* User capability.
* Secondary separators.

= Do More with Premium Version =
When exporting products and/or orders, you can add one additional product and/or order **meta field** to export. Our [Pro version](https://wpfactory.com/item/export-woocommerce/) allows adding unlimited number of additional meta fields.

== Screenshots ==

1. Main Page with General Settings
2. Export Product Settings
3. Dashboard Page
4. Product Report Results Page

= Feedback =
* We are open to your suggestions and feedback.
* Visit the [Export WooCommerce plugin page](https://wpfactory.com/item/export-woocommerce/).
* Thank you for using or trying out one of our plugins!

== Installation ==

1. Upload the entire plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Start by visiting plugin settings at "WPFactory > Export".

== Changelog ==

= 2.3.1 - 18/04/2025 =
* Fix - Export Products - Remove empty "Product Attributes" column.
* Fix - Translation loading fixed.
* Dev - Key Manager - Library updated.
* WC tested up to: 9.8.
* Tested up to: 6.8.

= 2.3.0 - 30/03/2025 =
* Dev - Export Products - Add "Variation Product on a New Row" option (defaults to `no`).
* Dev - Export Products - Code refactoring.
* Dev - Export Products - Coding standards improved.

= 2.2.6 - 24/03/2025 =
* Dev - Export Products - Variation product fields data added (width, length, height, weight, downloadable, virtual, manage stock).
* Dev - Export Products - "Grouped Product SKUs" field added.
* WC tested up to: 9.7.

= 2.2.5 - 19/02/2025 =
* Fix - Import section added to the "WPFactory > Export" menu.
* Dev - Import - Code refactoring and cleanup.
* Plugin name updated.

= 2.2.4 - 12/02/2025 =
* Fix - Export Products - Sorting list - Issues after saving changes fixed.
* Fix - Export Products - Sorting list - Layout fixed.
* Dev - Export Products - "Gallery Image URL" field added.
* Dev - Composer - `autoloader-suffix` param added.
* Dev - Code cleanup.
* WC tested up to: 9.6.

= 2.2.3 - 19/12/2024 =
* Fix - Fixed double quotes in CSV export.
* Fix - "Creation of dynamic property is deprecated" notice fixed.
* WC tested up to: 9.5.

= 2.2.2 - 16/12/2024 =
* Fix - Fixed line break issue in CSV export.
* Dev - Key Manager - Library updated.
* Dev - Code refactoring.

= 2.2.1 - 22/11/2024 =
* Fix - Fixed warning "Function _load_textdomain_just_in_time was called incorrectly".
* Fix - Fixed dynamic property warning.

= 2.2.0 - 19/11/2024 =
* Dev - Plugin settings moved to the "WPFactory" menu.
* Dev - "Recommendations" added.
* Dev - "Key Manager" added.
* Dev - Initializing the plugin on the `plugins_loaded` action.
* Dev - Code refactoring.
* WC tested up to: 9.4.
* Tested up to: 6.7.
* Plugin name updated.

= 2.1.0 - 08/10/2024 =
* Fix - Cross-Site Scripting vulnerability.
* Dev - Code refactoring.
* WC tested up to: 9.3.

= 2.0.15 - 10/09/2024 =
* Fix - class-alg-exporter-customers.php adjust for HPOS support code.

= 2.0.14 - 09/09/2024 =
* WC tested up to: 9.2.
* Add - General - Confirm HPOS enabled

= 2.0.13 - 31/07/2024 =
* WC tested up to: 9.1.
* Tested up to: 6.6.

= 2.0.12 - 17/07/2024 =
* Upgrade Broken Cross Site Scripting (XSS) vulnerability.
* WC tested up to: 9.0

= 2.0.11 - 10/03/2024 =
* Upgrade Broken Cross Site Scripting (XSS) vulnerability.
* WC tested up to: 8.5.

= 2.0.10 - 07/01/2024 =
* Export product data by ajax (High Volume data).

= 2.0.9 - 03/01/2024 =
* WC tested up to: 8.4.
* Upgrade Broken Access Control vulnerability.

= 2.0.8 - 07/12/2023 =
* WC tested up to: 8.3.
* Tested up to: 6.4.
* Add "Item Id" to order_items_export_fields().

= 2.0.7 - 29/10/2023 =
* WC tested up to: 8.2.
* Declare HPOS compatibility.

= 2.0.6 - 21/09/2023 =
* WC tested up to: 8.1.
* Tested up to: 6.3.

= 2.0.5 - 17/06/2023 =
* WC tested up to: 7.8.
* Tested up to: 6.2.

= 2.0.4 - 31/03/2023 =
* Move to WPFactory.

= 2.0.3 - 18/03/2023 =
* Added a new option to export SKU number on order items report
* Verified compatibility with WooCommerce 7.5

= 2.0.2 - 08/03/2023 =
* Added new filters to get data before export (to add custom data), new filters are: alg_export_data, alg_export_data_csv, alg_export_data_xml
* Verified compatibility with WooCommerce 7.4

= 2.0.1 - 06/02/2023 =
* Verified compatibility with WooCommerce 7.3

= 2.0 - 06/12/2022 =
* Revamped the Products exporting feature, new export options & filters, XML export available
* Verified compatibility with WooCommerce 7.1

= 1.9.4 - 04/11/2022 =
* Verified compatibility with WordPress 6.1 & WooCommerce 7.0

= 1.9.3 - 06/09/2022 =
* Verified compatibility with WooCommerce 6.8

= 1.9.2 - 12/06/2022 =
* Verified compatibility with WordPress 6.0 & WooCommerce 6.5

= 1.9.1 - 19/04/2022 =
* Verified compatibility with WooCommerce 6.4
* Bug fixed: Warning for "date() expects parameter"
* Site health message related to PHP session creation was handled

= 1.9 - 19/03/2022 =
* Verified compatibility with WooCommerce 6.3
* Added a new option to export Shipping Method in orders

= 1.8.1 - 08/02/2022 =
* Fixed error message related to session calling in PHP 8.0

= 1.8 - 28/01/2022 =
* Verified compatibility with WordPress 5.9 & WooCommerce 6.1
* New feature: You can order fields in products table before exporting them

= 1.7.8 - 30/08/2021 =
* Checked compatibility with WooCommerce 5.6

= 1.7.7 - 25/07/2021 =
* Verified compatibility with WooCommerce 5.5 & WordPress 5.8

= 1.7.6 - 17/05/2021 =
* Verified compatibility with WooCommerce 5.3

= 1.7.5 - 11/04/2021 =
* Added a section to import products (exported by the plugin)
* Tested compatibility with WC 5.1 & WP 5.7

= 1.7.4 - 28/02/2021 =
* Tested compatibility with WC 5.0

= 1.7.3 - 27/01/2021 =
* Tested compatibility with WC 4.9

= 1.7.2 - 23/12/2020 =
* Fixed a bug in handling comma between columns
* Tested compatibility with WC 4.8 & WP 5.6

= 1.7.1 - 21/11/2020 =
* Tested compatibility with WC 4.7

= 1.7 - 30/10/2020 =
* Tested compatibility with WC 4.6
* Added an option to export attributes

= 1.6 - 16/08/2020 =
* Tested compatibility with WP 5.5
* Tested compatibility with WC 4.3
* Added a new item to export Order Shipping Total in Orders report

= 1.5.5 - 02/01/2020 =
* Text updates over the plugin pages.
* Copyrights Update
* Added a section to review the plugin

= 1.5.4 - 20/11/2019 =
* Dev - Code refactoring.
* WC tested up to: 3.8.
* Tested up to: 5.3.
* Plugin author changed.

= 1.5.3 - 05/08/2019 =
* Fix - Export Customers from Orders - "Total Spent (Period)", "Order Count (Period)" and "Item Count (Period)" columns fixed (`do_calculate_tmp_data`).
* Dev - Advanced Options - "Block Size" option added.
* Dev - Advanced Options - "Time Limit" option added.
* Dev - HTTP headers updated for both CSV and XML file exports.

= 1.5.2 - 10/05/2019 =
* Dev - General - Datepicker - "Timepicker" option added.
* Dev - General - Datepicker - Loading `jquery-ui.css` from `code.jquery.com` now.
* Tested up to: 5.2.

= 1.5.1 - 04/05/2019 =
* Dev - Export Orders / Orders Items - "Backend Order Notes" field added.
* Dev - General - "Secondary Separators" options added.
* Dev - General - "Raw" input is now allowed in "CSV Separator" and "CSV Wrap" options.
* Dev - General - Settings descriptions updated.
* Dev - "WC tested up to" updated.

= 1.5.0 - 12/03/2019 =
* Fix - Datepicker fixed to include change month and year options. Date format forced to `yy-mm-dd`.
* Dev - Export Customers from Orders - "Total Spent (Period)" field added.
* Dev - Export Customers from Orders - "Order Count (Period)" field added.
* Dev - Export Customers from Orders - "Item Count (Period)" field added.
* Dev - Export Customers from Orders - "Total Spent (Lifetime)" field added.
* Dev - Export Customers from Orders - "Order Count (Lifetime)" field added.
* Dev - Export Customers from Orders - "First Order Date" field added.
* Dev - Export Customers from Orders - "Customer First Name" field added.
* Dev - Export Customers from Orders - "Customer Last Name" field added.
* Dev - Export Customers - "Total Spent (Lifetime)" field added.
* Dev - Export Customers - "Order Count (Lifetime)" field added.
* Dev - Export Customers - "Last Order Date" field added.
* Dev - General - "Content Length Header" option added.

= 1.4.0 - 16/02/2019 =
* Dev - Export Customers - "Customer Nr." field added.
* Dev - Export Customers - "User Roles" field added.
* Dev - Export Customers - "Last Update" field added.
* Dev - Export Customers from Orders - "Customer ID" field added.
* Dev - Export Customers from Orders - "User Roles" field added.
* Dev - Export Customers - "User Roles" option added.
* Dev - Code refactoring.
* Dev - Admin settings restyled.
* Dev - readme.txt description expanded.

= 1.3.0 - 29/10/2018 =
* Fix - "Download CSV" and "Download XML" buttons links fixed to include full home url.
* Dev - "Reset Settings" option added.
* Dev - Date range menu - Active link styling added.
* Dev - Code refactoring.
* Dev - Admin settings restyled.
* Dev - Plugin URI updated.

= 1.2.1 - 13/06/2018 =
* Dev - Export Orders - "Order Product Input Fields" field added.
* Dev - Export Orders Items - "Item Product Input Fields" field added.
* Dev - "WC tested up to" added to plugin header.

= 1.2.0 - 10/12/2017 =
* Fix - Export restricted to users with `manage_options` capability only (and "General > User Capability" option added).
* Fix - "Filter by All Fields" fixed.
* Dev - WooCommerce 3.2.0 compatibility - Admin Settings - `select` option type display fixed.
* Dev - General - "CSV Wrap" option added.
* Dev - Code refactoring.
* Dev - Settings array stored as main class property.
* Dev - Admin Settings - Option descriptions minor update.

= 1.1.0 - 28/07/2017 =
* Dev - WooCommerce 3.x.x compatibility - Orders - Using methods instead of accessing order properties directly.
* Dev - WooCommerce 3.x.x compatibility - Orders - `get_order_currency()` replaced with `get_currency()`.
* Dev - WooCommerce 3.x.x compatibility - Orders - `alg_get_order_item_meta_info()` - `has_meta()` replaced with `get_meta_data()`.
* Dev - WooCommerce 3.x.x compatibility - Export Customers from Orders - Using methods instead of accessing order properties directly (fixes "Order properties should not be accessed directly" notice).
* Dev - WooCommerce 3.x.x compatibility - Products - `get_categories()` and `get_tags()` replaced with `wc_get_product_category_list()` and `wc_get_product_tag_list()`.
* Dev - WooCommerce 3.x.x compatibility - Products - `get_dimensions()` replaced with `wc_format_dimensions( get_dimensions( false ) )`.
* Dev - WooCommerce 3.x.x compatibility - Products - `get_price_including_tax()`, `get_price_excluding_tax()` and `get_display_price()` replaced with `wc_get_price_including_tax()`, `wc_get_price_excluding_tax()` and `wc_get_price_to_display()`.
* Dev - WooCommerce 3.x.x compatibility - Products - `post->post_excerpt`, `post->post_content` and `post->post_status` replaced with `get_short_description()`, `get_description()` and `get_status()`.
* Dev - WooCommerce 3.x.x compatibility - Products - `get_formatted_variation_attributes()` replaced with `wc_get_formatted_variation()`.
* Dev - WooCommerce 3.x.x compatibility - Products - `get_total_stock()` replaced with `get_stock_quantity()`.
* Dev - WooCommerce 3.x.x compatibility - Products - Using `wc_get_product()` instead of `get_child()`.
* Dev - WooCommerce 3.x.x compatibility - Products - Using methods instead of accessing product properties directly (fixes "Product properties should not be accessed directly" notice).
* Dev - Filter export by date options added.
* Dev - Export Customers from Orders - Code refactoring.
* Dev - Export Customers from Orders - Shipping info fields added (9 new fields).
* Dev - Export Customers - 24 new fields added.
* Dev - Plugin link updated from `http://coder.fm` to `https://wpcodefactory.com`.
* Dev - Plugin header ("Text Domain" etc.) updated.
* Dev - POT file added.

= 1.0.0 - 22/12/2016 =
* Initial Release.

== Upgrade Notice ==

= 1.0.0 =
This is the first release of the plugin.
