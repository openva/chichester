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

/*
 * Include the Subsection Identifier library.
 */
include('class.SubsectionIdentifier.inc.php');

// Here's a regular expression to identify citations.
//([0-9]{1,2})VAC([0-9]{1,3})-([0-9]{1,3})-([0-9]{1,4})/

/*
 * Creat a new instance of our parser.
 */
$chichester = new Chichester();

/*
 * Fetch and save the table of contents page.
 */
$chichester->parse_toc();
file_put_contents('output/agencies.json', json_encode($chichester->agencies));

/*
 * Iterate through the agency list and retrieve each agency's TOC (section list).
 */
foreach ($chichester->agencies as $agency)
{
	
	$chichester->agency_id = $agency->toc_id;
	
	/*
	 * Retrieve the list of sections for this agency.
	 */
	try
	{
		$chichester->parse_agency();
	}
	catch (Exception $e)
	{
		 echo ('Fatal error for agency ' . $chichester->agency_id . ': ' . $e->getMessage());
	}
	
	//echo '<pre>' . print_r($chichester->sections) . '</pre>';
	file_put_contents('output/agency-' . $agency->toc_id . '.json', json_encode($chichester->sections));
	/*
	 * Now iterate through each section in this agency.
	 */
		// THIS IS A MISTAKE. Ultimately, we even want to save repealed and remove sections.
			file_put_contents('output/sections/' . $chichester->section->section_number . '.json',
				json_encode($chichester->section));

}

// store the now-complete TOC as a JSON file

// iterate through the TOC
	// fetch the section
	// parse the section
	// store the section as a JSON file
	