<?php

/*
 * Our time zone. (This is, of course, EST, but we have to define this to keep PHP from
 * complaining.)
 */
date_default_timezone_set('America/New_York');

/*
 * Include the Simple HTML DOM Parser.
 */
include('class.simple_html_dom.inc.php');

/*
 * Include the Chichester library.
 */
include('class.Chichester.inc.php');

// fetch the TOC
// parse the TOC

// iterate through the TOC
	// fetch the agency
	// parse the agency
	// store the agency
	// append the agency to the TOC object

// store the now-complete TOC as a JSON file

// iterate through the TOC
	// fetch the section
	// parse the section
	// store the section as a JSON file
	