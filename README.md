# ASU-RFI-WordPress-Plugin
[![Build Status](https://travis-ci.org/gios-asu/ASU-RFI-WordPress-Plugin.svg?branch=develop)](https://travis-ci.org/gios-asu/ASU-RFI-WordPress-Plugin)

WordPress plugin to submit Request For Information requests into Salesforce

# Requirements
* php > 5.5 
* [GitHub Updater WordPress Plugin](https://github.com/afragen/github-updater)


# Site Settings
**source_id** you need to uptain a orignal source identifier from the [ASU Enrollment Services Department](mailto:ecomm@asu.edu) for your college or department.

![screen shot 2016-12-13 at 12 43 24 pm](https://cloud.githubusercontent.com/assets/295804/21156084/c728ccae-c131-11e6-8e0f-7cbc1a6e3db6.png)


# Shortcodes

`[asu-rfi-form]` - For displaying an Request For Information form.
* attributes:
   *     type = 'full' or leave blank for the default simple form
   *     degree_level = 'ugrad' or 'grad' Default is 'ugrad'
   *     test_mode = 'test' or leave blank for the default production
   *     source_id = integer site identifier (issued by Enrollment services department) will default to site wide setting

![screen shot 2016-12-12 at 3 38 20 pm](https://cloud.githubusercontent.com/assets/295804/21119802/6a034bc2-c081-11e6-8d86-c5d55b0efc9c.png)

