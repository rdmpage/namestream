# namestream
Harvesting new taxonomic names and publications from RSS, OAI and other feeds


Mycobank

Mycobank has REST API here http://www.mycobank.org/Services/Generic/Help.aspx?s=searchservice

Can query by date ranges, e.g. http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000003562&filter=Creation_Date%3E%2220150201000000%22&NameYear_%3E%2220150101000000%22

finds records created since 2015-02-1 for taxa published in 2015.

Note that Mycobank numbers translate directly to IndexFungorum ids (i.e., MB800022 is urn:lsid:indexfungorum.org:names:800022, which can be resolved http://www.indexfungorum.org/IXFWebService/Fungus.asmx/NameByKeyRDF?NameLsid=urn:lsid:indexfungorum.org:names:800022 )

Hawksworth, D. L. (2005, November 2). Universal fungus register offers pattern for zoology. Nature. Nature Publishing Group. http://doi.org/10.1038/438024b
