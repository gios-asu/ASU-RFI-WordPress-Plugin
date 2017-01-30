# ASU-RFI-WordPress-Plugin
[![Build Status](https://travis-ci.org/gios-asu/ASU-RFI-WordPress-Plugin.svg?branch=develop)](https://travis-ci.org/gios-asu/ASU-RFI-WordPress-Plugin)

WordPress plugin to submit Request For Information requests into Salesforce

# Requirements
* php > 5.5 
* [GitHub Updater WordPress Plugin](https://github.com/afragen/github-updater)
* The active theme should have a recent verson of Twitter Boostrap.


# Site Settings
![screen shot 2016-12-13 at 12 43 24 pm](https://cloud.githubusercontent.com/assets/295804/21156084/c728ccae-c131-11e6-8e0f-7cbc1a6e3db6.png)

**source_id** You will need to uptain a orignal source identifier from the [ASU Enrollment Services Department](mailto:ecomm@asu.edu) for your college or department.

**College Code** 2-5 character string, usually all caps, like `LA` for `College of Liberal Arts and Sciences` or `SU` for `School of Sustainability`. This will be the default college used for RFI forms on the site.


# Shortcodes

`[asu-rfi-form]` - For displaying an Request For Information form.
* attributes:
   *     type = 'full' or leave blank for the default simple form
   *     degree_level = 'ugrad' or 'grad' Default is 'ugrad', (alternative spellings `undergraduate` and `graduate` will also work)
   *     test_mode = 'test' or leave blank for the default production
   *     major_code = eg 'SUSMSUS' - for hard coding a specific major for a form
   *     major_code_picker = 'true' to enable, leave off or blank to disable showing a drop down of majors based on the campus, school and degree level specified. 
   *     source_id = integer site identifier (issued by Enrollment services department) will default to the site wide setting made in the admin options.
   *     college_program_code = 2-5 character string, usually all caps, like `LA` for `College of Liberal Arts and Sciences` or `SU` for `School of Sustainability`, it will default to the value set in the RFI Admin Options menu so only use this attribute if you want to override one specific form.
   *     campus    = eg 'TEMPE' or leave blank for all Campuses.
   *

![screen shot 2016-12-12 at 3 38 20 pm](https://cloud.githubusercontent.com/assets/295804/21119802/6a034bc2-c081-11e6-8d86-c5d55b0efc9c.png)

