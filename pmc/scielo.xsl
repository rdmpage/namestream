<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>

<xsl:output method='html' version='1.0' encoding='utf-8' indent='yes'/>


<xsl:template match="/">

<html>
            <head>
                <style type="text/css">	
body {
	margin: 10;
	padding: 10;	
	font-family: Verdana;
	font-size: 11px!important;
	
	line-height:1.5em;
}
h1 {
	line-height:1.5em;
	font-weight:normal;
}
p{
	margin:0px;
	padding:0px 0px;
	margin-top:10px;
}
tbody{
	font-family: Verdana;
	font-size: 11px!important;
}
                </style>
            </head>
            <body>

<!-- <xsl:apply-templates select="//article-meta"/> -->
<xsl:apply-templates select="//body"/>

	</body>
	</html>

</xsl:template>

<xsl:template match="//article-meta">
<!-- article title -->
	<h1><xsl:value-of select="//article-title" /></h1>
	<xsl:apply-templates select="//contrib-group"/>

<!-- identifiers -->
	<ul>
<li><xsl:text>doi:</xsl:text><xsl:value-of select="//article-id[@pub-id-type='doi']" /></li>
</ul>
</xsl:template>

<!-- authors -->
<xsl:template match="//contrib-group">
	<h2>
		<xsl:apply-templates select="contrib"/>
	</h2>
</xsl:template>

<!-- author -->
    <xsl:template match="contrib">
        <xsl:if test="@contrib-type='author'">
            <xsl:if test="position() != 1"><xsl:text>, </xsl:text></xsl:if>
            <xsl:value-of select="name/given-names" />
            <xsl:text> </xsl:text>
            <xsl:value-of select="name/surname" />
        </xsl:if>
        
    </xsl:template>
    
<xsl:template match="//body">
	<xsl:value-of select="." disable-output-escaping="yes" />
</xsl:template>
    


</xsl:stylesheet>