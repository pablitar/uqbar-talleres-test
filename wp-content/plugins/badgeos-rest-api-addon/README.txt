=== BadgeOS REST API Addon ===
Contributors: BadgeOS
Tags: badgeos, badges, REST API, API
Requires at least: 4.0
Tested up to: 5.4.1
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The BadgeOS Rest API Addon introduces rest API endpoints to extend interaction between your BadgeOS environment and external applications.

== Description ==

The BadgeOS Rest API Addon introduces rest API endpoints to extend interaction between your BadgeOS environment and external applications.

= Prerequisites: =

* WordPress
* BadgeOS (at least 3.6.3)

= Features: =

Following are the available end points:

**Achievements EndPoints**

*Getting all Achievement Types*
/wp-json/badgeos-api/get-achievements-types/

*Getting Achievement Types By ID*
/wp-json/badgeos-api/get-achievement-type-by-id/ (achievement type’s ID)

*Getting Achievements By ID*
/wp-json/badgeos-api/get-achievement-by-id/(achievement’s ID)

*Getting all Achievements*
/wp-json/badgeos-api/get-all-achievements

*Getting all Awarded Achievements*
/wp-json/badgeos-api/awarded-achievements

*To Award any Achievement*
/wp-json/badgeos-api/award-achievement

*To Revoke any Achievement*
/wp-json/badgeos-api/revoke-achievement

*To get steps count by a specific trigger’s name*
/wp-json/badgeos-api/steps-by-trigger/ (trigger’s name)


**Ranks Endpoints**

*Getting all Rank Types*
/wp-json/badgeos-api/get-rank-types/

*Getting Rank Types By ID*
/wp-json/badgeos-api/get-rank-type-by-id/(rank type’s ID)

*Getting Ranks By ID*
/wp-json/badgeos-api/get-rank-by-id/(rank’s ID)

*Getting all Ranks*
/wp-json/badgeos-api/get-all-ranks/

*Getting all Awarded Ranks*
/wp-json/badgeos-api/awarded-ranks/

*To Award any Rank*
/wp-json/badgeos-api/award-rank

*To Revoke any Rank*
/wp-json/badgeos-api/revoke-rank

*To get steps count by a specific trigger’s name*
/wp-json/badgeos-api/rank-steps-by-trigger/ (trigger’s name)


**Point Types Endpoints**

*Getting all Point Types*
/wp-json/badgeos-api/get-point-types

*Getting Point Types By ID*
/wp-json/badgeos-api/get-point-type-by-id/(point type’s ID)

*Getting Point Type’s Balance By ID and User ID*
/wp-json/badgeos-api/get-point-balance/(point type’s ID)/(user’s id)

*To award point steps by Trigger’s name*
/wp-json/badgeos-api/award-point-steps-by-trigger/ (trigger’s name)

*To deduct point steps by Trigger’s name*
/wp-json/badgeos-api/deduct-point-steps-by-trigger/(trigger’s name)

*To Award points*
/wp-json/badgeos-api/award-point/

*To deduct points*
/wp-json/badgeos-api/deduct-point


**Installation Instructions**

Before installation please make sure you have latest BadgeOS plugin installed.

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

== Frequently Asked Questions ==

= Is it necessary to have BadgeOS plugin activated to use this add-on?

Yes, you must have BadgeOS plugin enabled to use this add-on.

== Changelog ==

= 1.0 =
* Initial
