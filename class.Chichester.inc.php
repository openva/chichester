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
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$allowed_protocols = CURLPROTO_HTTP | CURLPROTO_HTTPS;
		curl_setopt($ch, CURLOPT_PROTOCOLS, $allowed_protocols);
		curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, $allowed_protocols & ~(CURLPROTO_FILE | CURLPROTO_SCP));
		$this->html = curl_exec($ch);
		curl_close($ch);
		
		/*
		 * If our query failed, then we can't continue.
		 */
		if ($this->html === FALSE)
		{
			throw new Exception('cURL could not retrieve content for ' . $this->url . ', with the
				following error: ' . $curl_error($ch));
		}
		
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
	 * @requires $this->html
	 * @returns TRUE or FALSE
	 * @sets 
	 */
	function parse_toc()
	{
		
		/*
		 * Fetch the table of contents.
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
		 * are noise, but we can avoid those easily.
		 */
		$i=0;
		foreach ($dom->find('tr') as $agency)
		{
			
			/*
			 * If this isn't an agency listing, then skip it.
			 */
			if (stristr($report->find('td', 0)->plaintext, 'Agency') === FALSE)
			{
				continue;
			}
			
			/*
			 * Save each field.
			 */
			$this->agencies->{$i}->toc_id = $report->find('td', 0)->find('a', 0);
			$this->agencies->{$i}->??? = trim($report->find('td', 0)->plaintext);
			$this->agencies->{$i}->title = trim($report->find('td', 1)->plaintext);
			
		}
		
		// walk DOM
		// /000\+reg\+TOC([0-9]+)">(^<)<\/a><\/td><td>(^<)/
		// turn into object
		/*
		if (stristr($title, '(ABOLISHED)') !== FALSE)
		{
			$abolished = TRUE;
			$title = str_replace('(ABOLISHED)', '', $title);
			$title = trim($title);
		}
		*/
		
		return TRUE;
		
	}
	
	/**
	 * Fetch and structure the table of contents for a single agency.
	 * 
	 * @requires $this->html
	 * @returns TRUE or FALSE
	 * @sets TBD
	 */
	function parse_agency()
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
		
		return TRUE;
		
	}


}
