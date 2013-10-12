# Chichester

A scraper for Virginia's Administrative Code.

Named for Cassius M. Chichester, the Director of Statutory Research and Drafting from 1946–1951, a founding member of the Commission on Code Recodification.

## Planned functionality

### Load TOC

Load `http://leg1.state.va.us/000/reg/TOC.HTM`.

Example TOC entry:

```html
<a href="/cgi-bin/legp504.exe?000+reg+TOC01017">Agency   17</a></td><td>DESIGN-BUILD/CONSTRUCTION MANAGEMENT REVIEW BOARD (ABOLISHED) </td>
```

From this, get the ID, agency number, title, and whether it's been abolished.

Use a regular expression a little like this:

```
/000\+reg\+TOC([0-9]+)">(^<)<\/a><\/td><td>(^<)</td>/
```

Abolished:

```php
if (stristr($title, '(ABOLISHED)') !== FALSE)
{
	$abolished = TRUE;
	$title = str_replace('(ABOLISHED)', '', $title);
	$title = trim($title);
}
```


### Load each agency TOC

The URL is like this: `http://leg1.state.va.us/000/reg/TOC18085.HTM`.

That format is `http://leg1.state.va.us/000/reg/TOC[ID].HTM`.

This is a list of chapters and those chapters' sections. We don't care about the chapters, but we do care about the sections.

Example agency TOC entry:

```html
<a href="/cgi-bin/legp504.exe?000+reg+18VAC85-20-10">Section 10</a></td><td>Definitions </td>
```

From this, get the complete section number, the described section number, the section title, and whether it's repealed.


### Load each section

p.part		[unclear]

p.vacno		section number and title

p.sectind	the actual text of the regulation

p.auth		Statutory Authority

p.history	History
