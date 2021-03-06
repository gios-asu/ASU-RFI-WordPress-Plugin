# ASU-RFI-WordPress-Plugin
[![Build Status](https://travis-ci.org/gios-asu/ASU-RFI-WordPress-Plugin.svg?branch=develop)](https://travis-ci.org/gios-asu/ASU-RFI-WordPress-Plugin)

WordPress plugin to submit Request For Information requests into Salesforce

# Requirements
* php > 5.5
* [GitHub Updater WordPress Plugin](https://github.com/afragen/github-updater)
* The active theme should have a recent verson of Twitter Boostrap.

# Install Notes
Please note that you will need to run `composer install` and `yarn` in the project directory to bring in all required libraries.


# Site Settings
![screen shot 2016-12-13 at 12 43 24 pm](https://cloud.githubusercontent.com/assets/295804/21156084/c728ccae-c131-11e6-8e0f-7cbc1a6e3db6.png)

**source_id** You will need to obtain a original source identifier from the [ASU Enrollment Services Department](mailto:ecomm@asu.edu) for your college or department.

**College Code** 2-5 character string, usually all caps, eg `LA` for `College of Liberal Arts and Sciences` or `SU` for `School of Sustainability`. This will be the default college used for RFI forms on the site.


# Shortcodes

`[asu-rfi-form]` - For displaying an Request For Information form.
* attributes:
  * **type** = `full` or leave blank for the default simple form
  * **degree_level** = `ugrad` or `grad` Default is `ugrad`, (alternative spellings `undergraduate` and `graduate` will also work)
  * **test_mode** = `test` or leave blank for the default production mode. **Note:** the `test_mode` attribute is used to determine which internal ASU endpoint is used. If you are actually wanting to test an RFI form, and do not want it to end up in PeopleSoft, you **must** set this attribute to the word 'test' **in all lower case** .
  * **major_code** = eg `SUSMSUS` - for hard coding a specific major for a form
  * **major_code_picker** = `true` to enable, leave off or blank to disable showing a drop down of majors based on the campus, school and degree level specified.
  * **source_id** = integer site identifier (issued by Enrollment services department) will default to the site wide setting configured in the plugin admin settings.
  * **college_program_code** = 2-5 character string, usually all caps, eg `LA` for `College of Liberal Arts and Sciences` or `SU` for `School of Sustainability`, it will default to the value set in the RFI Admin Options menu so only use this attribute if you want to override one specific form.
  * **campus** = eg `TEMPE` or leave blank for all Campuses.
  * **semesters** = comma-delimited list of semesters allowed to be selected in 'My anticipated start date' dropdown (eg: `spring,summer,fall`). If omitted, the dropdown will be auto-filled with Spring, Summer, Fall for Undergrad Forms, and Spring, Fall for Grad Forms.
  * **test_mode** = either 'test' or 'prod' (anything other than the literal string 'test', including not having this attribute at all, will result in 'prod'). The use of this flag by the actual endpoint is uknown to us, but it's here just in case you need to specify it.
  * **endpoint** = either 'test' or 'prod' (anything other than the literal string 'test', including not having this attribute at all, will result in 'prod'). Determines which RFI endpoint is used. Setting this to 'test' will send requests to the QA endpoint where, presumably, the **do not** end result in actual submissions to SalesForce.
  * **thank_you_page** = A URL to which we will send the user after an RFI submission has received a passing grade from Google's reCAPTCHA system, and was successfully submitted. To redirect to a page that is already set up in Wordpress, you can use a relative URL, as in `/about/thank-you`.

### Available Major Codes for SOS
	SUSUSTBA - Sustainability (BA)
	SUSUSTBS - Sustainability (BS)
	SUSUSTMA - Sustainability, MA
	SUSUSTMS - Sustainability, MS
	SUSUSTPHD - Sustainability, PhD
	SUSUEPHD - Sustainability, PhD in Sustainable Energy
	SUSUSTCPHD - Sustainability (Complex Adaptive Systems Science), PhD
	SUEMSLEMSL - Sustainability Leadership - Executive, EMSL
	SUSUSLMSL - Sustainability Leadership, MSL
	SUSUSOMSUS - Sustainability Solutions, MSUS
	SUGSUSSMS - Global Sustainability Science, MS


![Screenshot](http://i.imgur.com/PFWa83O.png)

# Analytics
If your site is using Google Analytics and has made the `ga` function available in the global scope, uppon successful submission of the form will load the ecommerce plugin and trigger the correct evaluation of the form submission based on the geographical location of the client. Currently the formula is $100 for in state, $200 for nationall, and $300 for international.

Update 3/22/19: The geo-location endpoint we were using for this process is no longer available. I'm leaving the original text here for now, but I don't believe this is true any longer.
