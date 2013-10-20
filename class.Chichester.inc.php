<?php

class Chichester
{

	/**
	 * Fetch the HTML for a given URL.
	 *
	 * @requires $this->url
	 * @returns true or false
	 * @sets $this->html
	 */
	function fetch_html()
	{
	
		if (!isset($this->url))
		{
			throw new Exception('No URL has been provided.');
			return FALSE;
		}
		
		if (filter_var($this->url, FILTER_VALIDATE_URL) === FALSE)
		{
			throw new Exception('The URL ' . $this->url . ' is invalid.');
			return FALSE;
		}
					
		/*
		 * Via cURL, retrieve the contents of the URL.
		 */
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, TRUE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5000);
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		$allowed_protocols = CURLPROTO_HTTP | CURLPROTO_HTTPS;
		curl_setopt($ch, CURLOPT_PROTOCOLS, $allowed_protocols);
		curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, $allowed_protocols & ~(CURLPROTO_FILE | CURLPROTO_SCP));
		$this->html = curl_exec($ch);
		
		/*
		 * If our query failed, then we can't continue.
		 */
		if ($this->html === FALSE)
		{
			throw new Exception('cURL could not retrieve content for ' . $this->url . ', with the '
				.'following error: ' . curl_error($ch));
		}
		
		curl_close($ch);
		
		/*
		 * This HTML is invalid. Clean it up with HTML Tidy.
		 */
		if (class_exists('tidy', FALSE))
		{
			
			$tidy = new tidy;
			$tidy->parseString($this->html);
			$tidy->cleanRepair();
			$this->html = $tidy;
			
		}

		elseif (exec('which tidy'))
		{

			$filename = '/tmp/' . $period_id .'.tmp';
			file_put_contents($filename, $this->html);
			exec('tidy --show-errors 0 --show-warnings no -q -m ' . $filename);
			$this->html = file_get_contents($filename);
			unlink($filename);

		}
		
		return TRUE;
		
	}
	
	/*
	 * Turn HTML into a DOM-based object
	 *
	 * @requires $this->html
	 * @returns TRUE or FALSE
	 * @sets $this->dom
	 */
	function html_to_object()
	{
	
		if (!isset($this->html))
		{
			throw new Exception('No HTML has been provided.');
			return FALSE;
		}
		
		/*
		 * Render this as an object with PHP Simple HTML DOM Parser.
		 */
		$this->dom = str_get_html($this->html);
		
		/*
		 * If this can't be rendered, then there's a serious HTML error.
		 */
		if ($this->dom === FALSE)
		{
			throw new Exception('Invalid HTML.');
			return FALSE;
		}
		
		return TRUE;
		
	}
	
	/**
	 * Fetch and structure the table of contents for the entire Administrative Code.
	 *
	 * @requires	nothing
	 * @returns		TRUE or FALSE
	 * @sets		$this->agencies
	 * @todo		Intelligently title-case agency names.
	 */
	function parse_toc()
	{
		
		/*
		 * The TOC's URL.
		 */
		$this->url = 'http://leg1.state.va.us/000/reg/TOC.HTM';
		
		try
		{
			$this->fetch_html();
		}
		catch (Exception $e)
		{
			 throw new Exception($e->getMessage());
			 return FALSE;
		}
		
		/*
		 * Convert the HTML to an object.
		 */
		try
		{
			$this->html_to_object();
		}
		catch (Exception $e)
		{
			 throw new Exception($e->getMessage());
			 return FALSE;
		}
		
		/*
		 * Iterate through the table rows -- each row is a single TOC entry. (A bunch of table rows
		 * are noise, but we can avoid those easily.)
		 */
		$i=0;
		foreach ($this->dom->find('tr') as $agency)
		{
			
			/*
			 * If this isn't an agency listing, then skip it. (We check the length because otherwise
			 * we get an initial match against the entire page content.)
			 */
			if ( strlen($agency->find('td', 0)->plaintext) > 100 || (stristr($agency->find('td', 0)->plaintext, 'Agency') === FALSE) )
			{
				continue;
			}
			
			/*
			 * Save each field.
			 */
			$this->agencies->{$i}->toc_id = str_replace('/cgi-bin/legp504.exe?000+reg+TOC',
				'', $agency->find('td', 0)->find('a', 0)->href);
			$this->agencies->{$i}->number = trim(str_replace('Agency ', '', $agency->find('td', 0)->plaintext));
			$this->agencies->{$i}->name = ucwords(strtolower(trim($agency->find('td', 1)->plaintext)));
			
			/*
			 * Determine if this agency has been abolished. If it has been, strip that flag out of
			 * the agency title, with which it's comingled. Save that flag as its own variable.
			 */
			if (stristr($this->agencies->{$i}->name, '(ABOLISHED)') !== FALSE)
			{
				$this->agencies->{$i}->abolished = TRUE;
				$this->agencies->{$i}->name = str_ireplace('(ABOLISHED)', '', $this->agencies->{$i}->name);
				$this->agencies->{$i}->name = trim($this->agencies->{$i}->name);
			}
			else
			{
				$this->agencies->{$i}->abolished = FALSE;
			}
			
			$i++;
			
		}
		
		return TRUE;
		
	}
	
	/**
	 * Fetch and structure the table of contents for a single agency.
	 * 
	 * @requires	$this->agency_id
	 * @returns		TRUE or FALSE
	 * @sets		
	 */
	function parse_agency()
	{
		
		/*
		 * We need the agency ID, as found in the URL.
		 */
		if (!isset($this->agency_id))
		{
			throw new Exception('No agency ID has been provided');
			return FALSE;
		}
		
		/*
		 * Get the HTML of the page.
		 */
		$this->url = 'http://leg1.state.va.us/cgi-bin/legp504.exe?000+reg+TOC' . $this->agency_id;
		$this->fetch_html();
		
		/*
		 * Convert the HTML to an object.
		 */
		try
		{
			$this->html_to_object();
		}
		catch (Exception $e)
		{
			 throw new Exception($e->getMessage());
			 return FALSE;
		}
		
		/*
		 * Fetch each table row.
		 */
		$i=0;
		foreach ($this->dom->find('tr') as $section)
		{
		
			/*
			 * If this cell's contents are suspiciously long, then we're looking at a table row used
			 * for page layout. Skip it.
			 */
			if (strlen($section->find('td', 0)->innertext) > 100 )
			{
				continue;
			}
			
			/*
			 * See if this is a table row that we actually want. We look at the first cell to
			 * determine this, and see if it's a section, a collection of forms, or a "DIBR"—a
			 * list of documents included by reference.
			 */
			$td_1 = $section->find('td', 0)->plaintext;
			
			/*
			 *	Is this a section, a collection of forms, or documents included by reference? If
			 * it's none of those things, then skip it.
			 */
			if (stristr($td_1, 'section') !== FALSE)
			{
				$entry_type = 'section';
			}
			elseif (stristr($td_1, 'forms') !== FALSE)
			{
				$entry_type = 'forms';
			}
			elseif (stristr($td_1, 'dibr') !== FALSE)
			{
				$entry_type = 'dibr';
			}
			else
			{
				continue;
			}
			
			/*
			 * Save the type of entry that this is.
			 */
			$this->sections->{$i}->type = $entry_type;
			
			/*
			 * Save the official URL for this section.
			 */
			$this->sections->{$i}->official_url = 'http://leg1.state.va.us'
				. $section->find('td', 0)->find('a', 0)->href;
			
			/*
			 * Save the section number (e.g., "2VAC5-585-2500"). We extract this from the URL.
			 */
			$this->sections->{$i}->section_number = str_replace('http://leg1.state.va.us/cgi-bin/legp504.exe?000+reg+',
				'', $this->sections->{$i}->official_url);
			
			/*
			 * Save the section catch line.
			 */
			$this->sections->{$i}->catch_line = trim($section->find('td', 1)->plaintext);
			
			/*
			 * If the catch line contains "[Repealed]" then strip that text out of the catch line
			 * and mark it as repealed.
			 */
			if (stristr($this->sections->{$i}->catch_line, '[Repealed]') !== FALSE)
			{
				$this->sections->{$i}->catch_line = trim(str_replace('[Repealed]', '', $this->sections->{$i}->catch_line));
				$this->sections->{$i}->repealed = TRUE;
				$this->sections->{$i}->removed = FALSE;
			}
			
			/*
			 * And if the catch line 
			 */
			elseif ($this->sections->{$i}->catch_line == '[Removed]')
			{
				unset($this->sections->{$i}->catch_line);
				$this->sections->{$i}->removed = TRUE;
				$this->sections->{$i}->repealed = FALSE;
			}
			else
			{
				$this->sections->{$i}->repealed = FALSE;
				$this->sections->{$i}->removed = FALSE;
			}
			
			$i++;
			
		}
		
		return TRUE;
		
	}
	
	/**
	 * Fetch and structure the contents of a single section.
	 * 
	 * @requires $this->html
	 * @returns TRUE or FALSE
	 * @sets TBD
	 * @todo // use <https://github.com/statedecoded/subsection-identifier>
	 */
	function parse_section()
	{
		
		/*
		 * Convert the HTML to an object.
		 */
		try
		{
			$this->html_to_object();
		}
		catch (Exception $e)
		{
			 throw new Exception($e->getMessage());
			 return FALSE;
		}
		
		/*
		 * Save the section number and the catch line.
		 *
		 * For example:
		 * <p class=vacno>16VAC30-12-40. Information to be sent to persons on the list.</p>
		 */
		$tmp = $this->dom->find('p.vacno', 0);
		$tmp = $tmp->plaintext;
		
		/*
		 * If the section number and catch line are found in the HTML, extract it.
		 */
		if (!empty($tmp))
		{
			$pos = strpos($tmp, '. ');
			$this->section->section_number = substr($tmp, 0, $pos);
			$this->section->catch_line = html_entity_decode(substr($tmp, ($pos+2)));
		}
		
		/*
		 * But if the section number and catch line aren't found in the HTML, then this is a form
		 * list or a DIBR list. Get the pseudo-section-number from the URL.
		 */
		else
		{
			$components = explode('+', $this->url);
			$this->section->section_number = $components[2];
		}
		
		/*
		 * Break the section number into its constitutents, to extract its ancestry (title, agency,
		 * and chapter).
		 */
		preg_match('/([0-9]{1,2})VAC([0-9]{1,3})-([0-9]{1,3})-([0-9]{1,4})/', $this->section->section_number, $parts);
		
		/*
		 * Produce an object containing all of our structural data.
		 */
		$structure = array(
			1 => 'title',
			2 => 'agency',
			3 => 'chapter',
			4 => 'section');
		$this->section->structure = new stdClass();
		$i=1;
		foreach ($structure as $number => $label)
		{
			$tmp = new stdClass();
			$tmp->label = $label;
			$tmp->identifier = $parts[$number];
			$tmp->TO_BE_NAMED = 'TO_BE_DEFINED';
			$tmp->level = $parts[$i];
			$this->section->structure->unit[] = $tmp;
			$i++;
		}

		/*
		 * Find all "parts." I have no idea what this means.
		 */
		$this->dom->find('p.part');
		
		/*
		 * Save the statutory authority under which this regulation was established. This may be
		 * multiple paragraphs, so we build it up with a loop.
		 */
		$this->section->authority = '';
		foreach ($this->dom->find('p.auth') as $auth)
		{
			
			/*
			 * Skip the section header (which is just a P tag).
			 */
			if ($auth->plaintext == 'Statutory Authority')
			{
				continue;
			}
			
			$this->section->authority .= $auth->plaintext;
			
		}
		
		/*
		 * Convert HTML entities to Unicode.
		 */
		$this->section->authority = html_entity_decode($this->section->authority);
		
		/*
		 * Save the actual text of this regulation. We do this by getting a list of all paragraphs
		 * with a class that starts with "sect," since we know that sections of text can have the
		 * class "sectind" or "sectbi", which means it's possible that othe classes are lurking out
		 * there somewhere.
		 */
		$this->section->text = '';
		foreach ($this->dom->find('p[class^=sect]') as $text)
		{
			$this->section->text .= $text->innertext . "\n\n";
		}
		
		/*
		 * If we didn't get any text, eliminate the variable.
		 */
		$this->section->text = trim($this->section->text);
		if (empty($this->section->text))
		{
			unset($this->section->text);
		}
		
		/*
		 * If we got any text.
		 */
		if (!empty($this->section->text))
		{
		
			/*
			 * Convert HTML entities to Unicode.
			 */
			$this->section->text = html_entity_decode($this->section->text);
		
			/*
			 * Take our unstructured text and give it structure.
			 */
			$structurer = new SubsectionIdentifier();
			$structurer->text = $this->section->text;
			$structurer->parse();
			$this->section->text = $structurer->structured;
			
		}
		
		/*
		 * Save the history of the establishment of and modifications to this regulation.
		 */ 
		$this->section->history = '';
		foreach ($this->dom->find('p[class^=history]') as $history)
		{
			
			/*
			 * Skip the section header (which is just a P tag).
			 */
			if ($history->innertext == 'Historical Notes')
			{
				continue;
			}
			$this->section->history .= $history->innertext . "\n\n";
			
		}
		
		/*
		 * Convert HTML entities to Unicode.
		 */
		$this->section->history = html_entity_decode($this->section->history);
		
		/*
		 * If we didn't get any history data, eliminate the variable.
		 */
		$this->section->history = trim($this->section->history);
		if (empty($this->section->history))
		{
			unset($this->section->history);
		}
		
		return TRUE;
		
	}

}
