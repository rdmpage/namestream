# Transforming PubMed Central XML to HTML

## Fetching XML and images

Given a PubMed Central identifier, append it to http://www.ncbi.nlm.nih.gov/pmc/utils/oa/oa.fcgi?id= to retrieve information where to get the archive containing the XML and images, e.g. http://www.ncbi.nlm.nih.gov/pmc/utils/oa/oa.fcgi?id=PMC3661548

## Transforming

Examples of transforming PMC XML to web page (where pmc.xsl is the XSLT style sheet).

xsltproc --stringparam path 12/24/Stud_Mycol_2014_Mar_15_77\(1\)_1-143 pmc.xsl 12/24/Stud_Mycol_2014_Mar_15_77\(1\)_1-143/simycol_77_1_001.nxml

xsltproc --stringparam path e1/3e/Zookeys_2012_May_21_\(196\)_1-10 pmc.xsl e1/3e/Zookeys_2012_May_21_\(196\)_1-10/ZooKeys-196-001.nxml

xsltproc --stringparam path  8a/41/PLoS_One_2013_May_22_8\(5\)_e63616 pmc.xsl 8a/41/PLoS_One_2013_May_22_8\(5\)_e63616/pone.0063616.nxml
