# Chichester

A scraper for Virginia's Administrative Code.

Named for Cassius M. Chichester, the Director of Statutory Research and Drafting from 1946–1951, a founding member of the Commission on Code Recodification.

## Functionality

Scrapes the [table of contents](http://leg1.state.va.us/000/reg/TOC.HTM) of the Virginia Administrative Code, and iterates through every agency. (That structural data is stored as JSON files.) When iterating through each agency, every law is iterated through and turned into JSON. The text of each section is run through [Subsection Identifier](https://github.com/statedecoded/subsection-identifier), to turn it into structured text. Each section is saved as its own JSON file.

This is a sample JSON file for a single section:

```json
{
   "section_number":"3VAC5-11-60",
   "catch_line":". Petition for rulemaking.",
   "authority":"§§ 2.2-4007.02, 4.1-103 and 4.1-111 of the Code of Virginia.",
   "history":"Derived from Virginia Register Volume 25, Issue 6, eff. December 24, 2008.",
   "text":{
      "0":{
         "prefix":"A.",
         "prefix_hierarchy":{
            "0":"A"
         },
         "text":"As provided in § <a href= \"/cgi-bin/legp504.exe?000+cod+2.2-4007\">2.2-4007</a> of the Code of Virginia, any person may petition the agency to consider a regulatory action."
      },
      "1":{
         "prefix":"B.",
         "prefix_hierarchy":{
            "0":"B"
         },
         "text":"A petition shall include but is not limited to the following information:"
      },
      "2":{
         "prefix":"B.1.",
         "prefix_hierarchy":{
            "0":"B",
            "1":"1"
         },
         "text":"The petitioner's name and contact information;"
      },
      "3":{
         "prefix":"B.2.",
         "prefix_hierarchy":{
            "0":"B",
            "1":"2"
         },
         "text":"The substance and purpose of the rulemaking that is requested, including reference to any applicable Virginia Administrative Code sections; and"
      },
      "4":{
         "prefix":"B.3.",
         "prefix_hierarchy":{
            "0":"B",
            "1":"3"
         },
         "text":"Reference to the legal authority of the agency to take the action requested."
      },
      "5":{
         "prefix":"C.",
         "prefix_hierarchy":{
            "0":"C"
         },
         "text":"The agency shall receive, consider and respond to a petition pursuant to § <a href= \"/cgi-bin/legp504.exe?000+cod+2.2-4007\">2.2-4007</a> and shall have the sole authority to dispose of the petition."
      },
      "6":{
         "prefix":"D.",
         "prefix_hierarchy":{
            "0":"D"
         },
         "text":"The petition shall be posted on the Town Hall and published in the Virginia Register."
      },
      "7":{
         "prefix":"E.",
         "prefix_hierarchy":{
            "0":"E"
         },
         "text":"Nothing in this chapter shall prohibit the agency from receiving information or from proceeding on its own motion for rulemaking."
      }
   }
}
```
