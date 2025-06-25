=== KPiR ===
Contributors: iworks
Donate link: https://ko-fi.com/iworks?utm_source=kpir&utm_medium=readme-donate
Tags: kpir, faktura, księgowość, vat, jpk
Requires at least: PLUGIN_REQUIRES_WORDPRESS
Tested up to: PLUGIN_TESTED_WORDPRESS
Stable tag: PLUGIN_VERSION
Requires PHP: PLUGIN_REQUIRES_PHP
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

PLUGIN_TAGLINE

== Description ==

PLUGIN_DESCRIPTION

**Features:**
* Manage small business bookkeeping tailored to Polish regulations.
* Generate JPK V7M and JPK-VAT XML reports for Polish tax compliance.
* Track expenses, assets, and VAT rates, including car-related expenses with customizable rates (20%, 75%, 100%).
* Annual and monthly financial reporting.
* Input sanitization and validation for data integrity and security.
* Localization support (Polish translation included).
* Regular updates for compatibility with the latest WordPress and Polish tax law changes.

**Who Is It For?**
KPiR is perfect for small business owners, freelancers, and sole proprietors in Poland who need a straightforward, WordPress-integrated solution for managing their accounting records and fulfilling local tax obligations.

**User Feedback:**
Users appreciate KPiR for its simplicity and effectiveness, especially when managing both private and business vehicle-related expenses.

**Open Source & Development:**
KPiR is open source and actively maintained, with contributions from the community and regular updates reflecting changes in Polish tax regulations. The plugin is available also on [GitHub](https://github.com/iworks/kpir).

== Installation ==

There are 3 ways to install this plugin:

= 1. The super easy way =
1. In your Admin, go to menu Plugins > Add
1. Search for `KPiR`
1. Click to install
1. Activate the plugin
1. A new menu `KPiR` will appear in your Admin

= 2. The easy way =
1. Download the plugin (.zip file) on the right column of this page
1. In your Admin, go to menu Plugins > Add
1. Select button `Upload Plugin`
1. Upload the .zip file you just downloaded
1. Activate the plugin
1. A new menu `KPiR` will appear in your Admin

= 3. The old and reliable way (FTP) =
1. Upload `kpir` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. A new menu `KPiR` will appear in your Admin

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

Project maintained on GitHub at [iworks/kpir](https://github.com/iworks/kpir).

= 1.1.2 - 2025-06-25 =
* Fixed issue with `use cash pit` option.

= 1.1.1 - 2025-06-20 =
* Added `filter_add_sortable_columns`, `apply_filter_order_date_of_payment`, `get_custom_field_basic_type_name` methods to invoice post type.   
* Added sort by payment date.
* Added `use_cash_pit` property and `action_init_set_options()` method; constructor hook adjusted.
* Code quality improvements and documentation update.
* Improved code formatting and consistency.
* Removed `quick_edit` from `row_actions`.
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 3.0.1.
* Updated file paths to use `__DIR__` constant for better compatibility.

= 1.1.0 - 2025-05-08 =
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 3.0.0.
* The cash method of settling income tax has been added. [#1](https://github.com/iworks/kpir/issues/1).

= 1.0.3 - 2025-03-31 =
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.9.9.
* The plugin repository has been moved to GitHub.
* Missing translation domain names have been added.

= 1.0.2 - 2025-02-22 =
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.9.5.
* Empty vat field has been fixed.

= 1.0.1 - 2024-03-25 =
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.8.9.
* The P_20 field has been temoved from JPK-VAT xml if is empty.

= 1.0.0 - 2023-06-07 =
* The BDO number has been added.

= 0.1.9 - 2023-02-21 =
* The person type for JPK V7M has been changed.

= 0.1.8 - 2022-02-24 =
* Added JPK V7M(2) report.
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.8.1.

= 0.1.7 - 2022-01-20 =
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.8.0.

= 0.1.6 - 2021-05-12 =
* Rename directory `vendor` into `includes`.
* Added VAT rates.
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.6.9.

= 0.1.5 - 2020-11-24 =
* Fixed VAT calculation for a car.

= 0.1.4 - 2020-11-24 =
* Added JPK V7M report.
* Removed JPK VAT(3) report.

= 0.1.3 - 2020-01-20 =
* Added annually report.

= 0.1.2 - 2019-11-12 =
* The [iWorks Options](https://github.com/iworks/wordpress-options-class) module has been updated to 2.6.8.

= 0.1.1 =
* Added new car related count: 20%, 75% and 100% for expenses.

= 0.1.0 =
* Netto expenses count only from expenses.

= 0.0.9 =
* Convert '&' char to entity in output JPK VAT fields.

= 0.0.8 =
* Added few checks to avoid PHP warnings.
* Added input sanitization and validation for JPK VAT(3) inputs.

= 0.0.7 =
* Added ability to sum two values.
* Added few checks to avoid PHP warnings.

= 0.0.6 =
* Improved "Asset" type, "Expense" is visible too.

= 0.0.5 =
* Added input month in JPK VAT sanitization.
* Handle translation.

= 0.0.4 =
* Added input sanitization.
* Added button to copy "Date of issue" value into "Event date" field.

= 0.0.3 =
* Improved post types labels.

= 0.0.2 =
* Fixed fractional part of money with leading zero for JPK VAT (3).

= 0.0.1 =
* init version

== Upgrade Notice ==
