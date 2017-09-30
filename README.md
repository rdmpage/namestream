# namestream
Harvesting new taxonomic names and publications from RSS, OAI and other feeds


## Mycobank

Mycobank has REST API here http://www.mycobank.org/Services/Generic/Help.aspx?s=searchservice

Can query by date ranges, e.g. http://www.mycobank.org/Services/Generic/SearchService.svc/rest/xml?layout=14682616000003562&filter=Creation_Date%3E%2220150201000000%22&NameYear_%3E%2220150101000000%22

finds records created since 2015-02-1 for taxa published in 2015.

Note that Mycobank numbers translate directly to IndexFungorum ids (i.e., MB800022 is urn:lsid:indexfungorum.org:names:800022, which can be resolved http://www.indexfungorum.org/IXFWebService/Fungus.asmx/NameByKeyRDF?NameLsid=urn:lsid:indexfungorum.org:names:800022 )

Hawksworth, D. L. (2005, November 2). Universal fungus register offers pattern for zoology. Nature. Nature Publishing Group. http://doi.org/10.1038/438024b

## ION

ION has RSS feeds, and provides LSIDs. Not that recently ION has started serving multiple original descriptions. For example, the record [866756](http://www.organismnames.com/details.htm?lsid=866756) has this metadata:

<?xml version=“1.0” encoding=“utf-8”?>
<rdf:RDF xmlns:dc=“http://purl.org/dc/elements/1.1/“ xmlns:dcterms=“http://purl.org/dc/terms/“ xmlns:tdwg_pc=“http://rs.tdwg.org/ontology/voc/PublicationCitation#” xmlns:tdwg_co=“http://rs.tdwg.org/ontology/voc/Common#” xmlns:tdwg_tn=“http://rs.tdwg.org/ontology/voc/TaxonName#” xmlns:rdfs=“http://www.w3.org/2000/01/rdf-schema#” xmlns:rdf=“http://www.w3.org/1999/02/22-rdf-syntax-ns#”>
<tdwg_tn:TaxonName rdf:about=“866756”>
    <dc:identifier>866756</dc:identifier>
    <dc:creator rdf:resource=“http://www.organismnames.com”/>
    <dc:Title>Rana cavitympanum</dc:Title>
    <tdwg_tn:nameComplete>Rana cavitympanum</tdwg_tn:nameComplete>
    <tdwg_tn:nomenclaturalCode rdf:resource=“http://rs.tdwg.org/ontology/voc/TaxonName#ICZN”/>
    <tdwg_co:PublishedIn>[Title unknown.] Proceedings of the Zoological Society of London  1893: Unpaginated.   [Zoological Record Volume 30]</tdwg_co:PublishedIn>
    <tdwg_co:microreference/>
    <tdwg_co:PublishedIn>Descriptions of new Reptiles and Batrachians obtained in Borneo by Mr. A. Everett and Mr. C. Hose. Proceedings of the Zoological Society of London  1893: pp. 522-528.  525 [Zoological Record Volume 30]</tdwg_co:PublishedIn>
    <tdwg_co:microreference>525</tdwg_co:microreference>
    <rdfs:seeAlso rdf:resource=“http://www.organismnames.com/namedetails.htm?lsid=866756”/>
</tdwg_tn:TaxonName>
</rdf:RDF>

This is an old record (as indicated by it’s numeric id 866756) but has had more complete bibliographic details added. Any post-processing would need to take this into account.
