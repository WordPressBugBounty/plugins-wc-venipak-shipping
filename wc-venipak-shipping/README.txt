=== Shipping with Venipak for WooCommerce ===
Contributors: shopup
Tags: Venipak
Requires at least: 4.4
Tested up to: 6.8.3
Stable tag: 1.25.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Venipak delivery method plugin for WooCommerce. Provides delivery via courier and pickup points.

== Description ==

* Delivery to customer address.
* Delivery to Venipak pickup points and lockers. A pickup map is displayed during checkout for user convenience.
* Cash on delivery (COD) service for collection of money by cash or card.
* To use this extension, you must have an active contract with Venipak. https://www.venipak.com/
* Additionally, you must have user credentials for the Venipak API. Please contact Venipak sales. https://www.venipak.com/

Support email: hello@akadrama.com

== Installation ==

1. Install the plugin
2. Configure with your Venipak details. You must have user credentials for the Venipak API. Please contact Venipak sales. https://www.venipak.com/
3. Create Venipak shipping methods

== Screenshots ==

== Changelog ==

= 1.25.8 =
* Fix: Stricter locker dimension check (oversize items no longer show lockers)
* Tweak: Block checkout pickup selector stability (no double fetch, restore selection)
* Fix: Remove shop-specific locker warning message from checkout (block + assets)

= 1.25.7 =
* Fix: Avoid early translation loading notice by delaying header translations

= 1.25.6 =
* Fix: Prevent Venipak map from loading twice and double zooming on checkout
* Fix: Keep classic Google Maps markers (no Map ID needed) for compatibility

= 1.25.5 =
* Feature: Add animated loading spinner to pickup point selection dropdown while fetching pickup points

= 1.25.4 =
* Fix: Use correct contact person name instead of company name in consignee contact_person field

= 1.25.3 =
* Fix: Add address to pickup point in blocks list

= 1.25.2 =
* Fix: Missing dispatch button

= 1.25.1 =
* Fix: Undefined variable warning

= 1.25.0 =
* Feature: Show delivery status in order list
* Fix: Deprecation warning

= 1.24.3 =
* Fix: Checkout page CSS
* Fix: Parcel size calculation improvements

= 1.24.2 =
* Fix: Validation when no pickup point is selected

= 1.24.1 =
* Fix: Order edit page error

= 1.24.0 =
* Feature: Tracking
* Fix: Validation priority fix

= 1.23.4 =
* Fix: Blocks API translations

= 1.23.3 =
* Fix: Blocks API fixes

= 1.23.2 =
* Feature: WordPress 6.7.1 support

= 1.23.0 =
* Feature: Blocks API support

= 1.22.6 =
* Fix: Use billing address phone if phone is not available in shipping address

= 1.22.5 =
* Fix: Improve shipping address validation

= 1.22.4 =
* Fix: Pickup selection width
* Fix: Security issue
* Fix: Use billing address if shipping address is not available

= 1.22.3 =
* Fix: Pickup selection validation error
* Fix: Pickups loading issue

= 1.22.1 =
* Fix: Get order_id from global scope if not set

= 1.22.0 =
* Feature: Add shortcut [venipak_tracking order_id="{order_id}"]

= 1.21.5 =
* Fix: Resolved an issue that caused errors during order processing when no shipping method was selected

= 1.21.4 =
* Fix: Auto package count

= 1.21.3 =
* Fix: Use shipping company in label if defined

= 1.21.2 =
* Fix: Pickup list loading issue

= 1.21.1 =
* Fix: Pickup list loading

= 1.21.0 =
* Feature: Predefined product-specific shipment counts
* Fix: Use phone from shipping details
* Fix: Deprecated PHP error


= 1.20.0 =
* Feature: HPOS support
* Feature: Remember the last selected pickup point
* Fix: Size restrictions for variations
* Fix: Pickup list update

= 1.19.8 =
* Fix: Error log cleanup

= 1.19.7 =
* Fix: Sequence of labels

= 1.19.6 =
* Fix: Security vulnerability - Cross Site Scripting (XSS)

= 1.19.5 =
* Fix: Set default product count for one label

= 1.19.4 =
* Fix: Load JS and CSS only on cart or checkout pages

= 1.19.3 =
* Fix: Lockers list update period set to 1 day

= 1.19.2 =
* Fix: Locker weight conditions. It is now possible to create multiple locker shipping methods based on weight

= 1.19.1 =
* Fix: Multiple labels print order

= 1.19.0 =
* New Feature: Print multiple labels
* Fix: The courier method was not displayed because the minimum weight was not set
* Fix: Pickup selection alignment to the right

= 1.18.0 =
* New Feature: Return label service

= 1.17.13 =
* Fix: PHP warning

= 1.17.12 =
* Fix: Disable 30kg locker limit

= 1.17.11 =
* Fix: Pickup selector
* Fix: Shipping method title design
* Fix: COD validation
