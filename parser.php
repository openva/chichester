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

/*
 * Get a list of every section that we already have a copy of.
 */
$existing_sections = scandir('output/sections/');
foreach ($existing_sections as &$section)
{
	$section = str_replace('.json', '', $section);
}

/*
 * Create a new instance of our parser.
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
	
	echo $agency->name . PHP_EOL;
	
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
// We're getting duplicate agency records. For instance, the ABC (agency 
	file_put_contents('output/agency-' . $agency->toc_id . '.json', json_encode($chichester->sections));
	
	/*
	 * Now iterate through each section in this agency.
	 */
	foreach ($chichester->sections as $section)
	{
		
		/*
		 * If we already have a copy of this section, skip it.
		 */
		$components = explode('+', $section->official_url);
		$section_number = $components[2];
		if (in_array($section_number, $existing_sections))
		{
			echo 'Skipping ' . $section_number . PHP_EOL;
			continue;
		}
		
		// THIS IS A MISTAKE. Ultimately, we even want to save repealed and remove sections.
		if ( ($section->repealed === FALSE) && ($section->removed === FALSE) )
		{
		
			$chichester->url = $section->official_url;
			$chichester->fetch_html();
			$chichester->parse_section();
			
			file_put_contents('output/sections/' . $chichester->section->section_number . '.json',
				json_encode($chichester->section));
			echo '* ' . $chichester->section->section_number . PHP_EOL;
			
		}
		
		/*
		 * Sleep for .51 seconds. If we don't do this, we'll be locked out of leg1.state.va.us,
		 * which limits requests to 30 per 60 seconds.
		 */
		usleep(510000);
	
	}

}

// store the now-complete TOC as a JSON file

// iterate through the TOC
	// fetch the section
	// parse the section
	// store the section as a JSON file
	