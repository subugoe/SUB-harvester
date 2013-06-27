<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:oai="http://www.openarchives.org/OAI/2.0/"
	xmlns:pan="http://www.pangaea.de/MetaData"
	xmlns:eromm_oai="http://www.eromm.org/eromm_oai_harvester/"
	exclude-result-prefixes="oai"
	version="1.0">

	<xsl:import href="iso-639-1-to-639-2b.xsl"/>
	<xsl:output method="xml" indent="yes"/>

	<!--
		Parameters
	-->

	<!-- Misc  | default = "unset" -->
	<xsl:param name="timestamp" select="string('unset')"/>
	<xsl:param name="country_code" select="string('unset')"/>
	<xsl:param name="oai_repository_id" select="string('unset')"/>
	<xsl:param name="oai_set_id" select="string('unset')"/>



	<!-- Templates for Pangaea Metadata -->
	
	<!-- Data set metadata -->
	<xsl:template match="pan:citation">
		<xsl:apply-templates select="*"/>
	</xsl:template>
	
	<xsl:template match="pan:citation/pan:author">
   		<field name="author">
   			<xsl:call-template name="authorName"/>
   		</field>
	</xsl:template>
	
	<xsl:template name="authorName">
		<xsl:value-of select="pan:lastName"/>
		<xsl:text>, </xsl:text>
		<xsl:value-of select="pan:firstName"/>
	</xsl:template>

	<xsl:template match="pan:title">
   		<field name="title">
   			<xsl:value-of select="."/>
   		</field>
	</xsl:template>

	<xsl:template match="pan:year">
   		<field name="date">
   			<xsl:value-of select="."/>
   		</field>
	</xsl:template>
	
	<xsl:template match="pan:URI">
		<xsl:choose>
			<xsl:when test="substring(., 1, 4) = 'doi:'">
				<field name="doi">
					<xsl:value-of select="substring-after(., 'doi:')"/>
				</field>
			</xsl:when>
			<xsl:when test="substring(., 1, 18) = 'http://dx.doi.org/'">
				<field name="doi">
					<xsl:value-of select="substring-after(., 'http://dx.doi.org/')"/>
				</field>
			</xsl:when>
			<xsl:otherwise>
				<field name="url">
					<xsl:value-of select="."/>
				</field>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="pan:abstract">
		<field name="abstract">
			<xsl:value-of select="."/>
		</field>
	</xsl:template>
	
	
	<!-- Related paper -->
  	<xsl:template match="pan:reference | pan:supplementTo">
  		<field name="relation">
	  		<xsl:apply-templates select="pan:author"/>
	  		<xsl:if test="pan:title">
	  			<xsl:text>: </xsl:text>
	  			<xsl:value-of select="pan:title"/>
	  		</xsl:if>
	  		<xsl:if test="pan:source">
		  		<xsl:text>, </xsl:text>
		  		<xsl:value-of select="pan:source"/>
	  		</xsl:if>
	  		<xsl:if test="pan:pages">
	  			<xsl:text>, </xsl:text>
	  			<xsl:value-of select="pan:pages"/>
	  		</xsl:if>
	  		<xsl:if test="pan:year">
	  			<xsl:text>, </xsl:text>
	  			<xsl:value-of select="pan:year"/>
	  		</xsl:if>
  		</field>
  		<xsl:apply-templates select="pan:URI"/>
  	</xsl:template>
  	
  	<xsl:template match="pan:reference/pan:URI">
  		<field name="relation-url_s">
  			<xsl:choose>
  				<xsl:when test="substring(., 1, 4) = 'doi:'">
  					<xsl:text>http://dx.doi.org/</xsl:text>
  					<xsl:value-of select="substring-after(., 'doi:')"/>
  				</xsl:when>
  				<xsl:when test="substring(., 1, 4) = 'hdl:'">
  					<xsl:text>http://hdl.handle.net/</xsl:text>
  					<xsl:value-of select="substring-after(., 'hdl:')"/>  					
  				</xsl:when>
  				<xsl:otherwise>
	  				<xsl:value-of select="."/>
  				</xsl:otherwise>
  			</xsl:choose>
  		</field>
  	</xsl:template>
  	
  	<xsl:template match="pan:reference/pan:author">
  		<xsl:call-template name="authorName"/>
  	</xsl:template>


  	<!-- Geographic Coordinates -->
  	<xsl:template match="pan:extent">
  		<xsl:apply-templates select="*"/>
  	</xsl:template>
  	
  	<xsl:template match="pan:geographic">
  		<xsl:apply-templates select="*"/>
  	</xsl:template>
  	
  	<xsl:template match="pan:westBoundLongitude">
  		<field name="geo-west_f">
  			<xsl:value-of select="."/>
  		</field>
  	</xsl:template>
  
  	<xsl:template match="pan:eastBoundLongitude">
  		<field name="geo-east_f">
  			<xsl:value-of select="."/>
  		</field>
  	</xsl:template>
  
  	<xsl:template match="pan:northBoundLatitude">
  		<field name="geo-north_f">
  			<xsl:value-of select="."/>
  		</field>
  	</xsl:template>
  	
  	<xsl:template match="pan:southBoundLatitude">
  		<field name="geo-south_f">
  			<xsl:value-of select="."/>
  		</field>
  	</xsl:template>
  
  
  	<!-- Additional terms to index -->
 	<xsl:template match="pan:project">
 		<xsl:apply-templates mode="indexOnly" select="*"/>
 	</xsl:template> 	
 
 	<xsl:template match="pan:keywords">
 		<xsl:apply-templates mode="indexOnly" select="pan:keyword"/>
 	</xsl:template>
 
 	<xsl:template match="pan:event">
 		<xsl:apply-templates mode="indexOnly" select="pan:label | pan:optionalLabel | pan:location | pan:campaign/* | pan:basis/* | pan:device/*"/>
 	</xsl:template>
 
  	<xsl:template match="*" mode="indexOnly">
	  	<field name="indexedField">
	  		<xsl:value-of select="."/>
	  	</field>
  	</xsl:template>
  
  
  	<!-- other project information -->
  	<xsl:template match="pan:topoType">
  		<field name="description">
  			<xsl:value-of select="."/>
  		</field>
  	</xsl:template>
  
  	<xsl:template match="pan:project">
		<field name="project-name_s">
			<xsl:value-of select="pan:name"/>
		</field>
		<field name="project-url_s">
			<xsl:value-of select="pan:URI"/>
		</field>
  	</xsl:template>


  	<!-- Record type information -->
  	<xsl:template match="pan:technicalInfo">
  		<xsl:apply-templates select="*"/>
  	</xsl:template>
  	
  	<xsl:template match="pan:entry[@key = 'hierarchyLevel']">
  		<field name="hierarchyLevel_s">
  			<xsl:value-of select="@value"/>
  		</field>
  	</xsl:template>



  	<!--
  		Standard OAI processing
  	-->


	<xsl:template match="*"/>


	<xsl:template match="/">
		<xsl:comment><xsl:value-of select="string(' ')" />Generated by EROMM-OAI-Harvester with panmd2solr.xsl @ <xsl:value-of select="concat($timestamp, ' ')"/></xsl:comment>
		<xsl:apply-templates />
	</xsl:template>

	
	<xsl:template match="oai:OAI-PMH">
		<update>
			<!-- Do new records exist? -->
			<xsl:if test="oai:ListRecords/oai:record/oai:metadata">
				<add overwrite="true">
				   <xsl:apply-templates select="oai:ListRecords/oai:record" mode="add"/>
				</add>
			</xsl:if>

			<!-- Do deleted records exist? (sounds funny) -->
			<xsl:if test="oai:ListRecords/oai:record/oai:header[@status='deleted']">
				<delete>
				   <xsl:apply-templates select="oai:ListRecords/oai:record" mode="delete"/>
				</delete>
			 </xsl:if>
		</update>
	</xsl:template>


	<!-- Grab records for indexing and create fields -->
	<xsl:template match="oai:ListRecords/oai:record" mode="add">
		<xsl:if test="not(oai:header[@status='deleted'])">
		<doc>
			<!-- Default fields -->
			<field name="indexed">NOW</field>
			<field name="type">oai</field>

			<!-- Parameter-fields -->
			<field name="country_code">
				<xsl:value-of select="$country_code"/>
			</field>
			<field name="oai_repository_id">
				<xsl:value-of select="$oai_repository_id"/>
			</field>
			<field name="oai_set_id">
				<xsl:value-of select="$oai_set_id"/>
			</field>

			<!-- Record-Header generated fields -->
			<field name="id">
				<xsl:value-of select="oai:header/oai:identifier"/>
			</field>
			<xsl:apply-templates select="oai:header/oai:setSpec"/>


			<!-- Although datestamp is a mandatory element, its not always there... -->
			<xsl:choose>
				<!-- If there is no datestamp, use the datestamp of the preceeding or following records -->
				<xsl:when test="oai:header/oai:datestamp and string-length(oai:header/oai:datestamp) &gt; 0">
					<xsl:call-template name="parse_datestamp">
						<xsl:with-param name="value" select="oai:header/oai:datestamp" />
					 </xsl:call-template>
				</xsl:when>
				<xsl:when test="preceding-sibling::oai:record/oai:header/oai:datestamp[node()]">
					<xsl:call-template name="parse_datestamp">
						<xsl:with-param name="value" select="preceding-sibling::oai:record/oai:header/oai:datestamp[node()][1]"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:when test="following-sibling::oai:record/oai:header/oai:datestamp[node()]">
					<xsl:call-template name="parse_datestamp">
						<xsl:with-param name="value" select="following-sibling::oai:record/oai:header/oai:datestamp[node()][1]"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<!-- Even the preceding and following records don't contain a datestamp, use the responseDate (another mandatory element...) -->
					<xsl:call-template name="parse_datestamp">
						<xsl:with-param name="value" select="//oai:responseDate"/>
					</xsl:call-template>
				</xsl:otherwise>
			</xsl:choose>

			<!-- Pangaea-Metadata generated fields -->
			<xsl:apply-templates select="oai:metadata/pan:MetaData/*"/>
		</doc>
		</xsl:if>
	</xsl:template>


	<!-- Grab records for deletion -->
	<xsl:template match="oai:ListRecords/oai:record" mode="delete">
		<xsl:if test="oai:header[@status='deleted']">
			<id>
				<xsl:value-of select="oai:header/oai:identifier"/>
			</id>
		</xsl:if>
	</xsl:template>


	<!-- Template for setSpec -->
	<xsl:template match="oai:header/oai:setSpec">
		<!-- Ignore eimpty setSpec elements (GDZ...) -->
		<xsl:if test="string-length(.) > 0">
			<field name="oai_setspec">
				<xsl:value-of select="."/>
			</field>
		</xsl:if>
	</xsl:template>


	<!-- Template for datestamp -->
	<xsl:template name="parse_datestamp">
		<xsl:param name="value" select="unset" />
		<field name="oai_datestamp">
			<!-- OAI allows datestamp with and without time (e.g. 2011-01-01 or 2011-01-01T00:00:00Z)
				 Solr requires with time, so it has to be appended if missing -->
			<xsl:choose>
			   <!-- If the value is 10 chars, time is missing -->
				<xsl:when test="string-length($value) = 10">
					<xsl:value-of select="concat($value, 'T00:59:59Z')"/>
			  </xsl:when>
			  <xsl:otherwise>
				  <xsl:value-of select="$value"/>
			  </xsl:otherwise>
			</xsl:choose>
		</field>
	</xsl:template>


	<!-- Template for URLs -->
	<xsl:template match="eromm_oai:oai_url">
		<field name="oai_url">
			<xsl:value-of select="."/>
		</field>
	</xsl:template>


</xsl:stylesheet>