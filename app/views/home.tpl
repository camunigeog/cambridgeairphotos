{* This is a generic content page *}
{extends file="application.tpl"}
{block name=body}

<div id="introduction">
	<p>The Cambridge University Collection of Aerial Photography (CUCAP) is the result of airborne survey campaigns which were started in 1947 by the pioneering JK St Joseph. Since then the collection has grown to almost 500,000 images of obliques and verticals in black and white, colour and infra-red. Virtually the whole of Britain has been covered, with the obliques depicting a wide variety of landscapes and features and the verticals being of survey quality, can be used in mapping projects.</p>
	<p>Some of the uses of CUCAP images include archaeology, geology, social history, law (land/border disputes), environmental issues (coastal erosion), planning as well as general interest.</p>
</div>


<div class="panel overlay" id="featured">
	<h3><a href="{$baseUrl}/featured/">Featured images</a></h3>
	<p>Explore some of the very best images in the collection.</p>
	{$featured}
</div>


<div class="panel box" id="browse">
	<h3><a href="{$baseUrl}/map/">Browse the map</a></h3>
	<p>Browse 450,000+ clickable locations.</p>
	<p><a href="{$baseUrl}/map/"><img src="/images/map.png" alt="" border="0" /></a></p>
</div>

<div class="panel box" id="themes">
	<h3><a href="{$baseUrl}/themes/">Themes</a></h3>
	<p>Browse by keyword.</p>
	{$themes}
</div>

<div class="panel box" id="search">
	<h3><a href="{$baseUrl}/search/">Search the catalogue</a></h3>
	<p>Search image captions.</p>
	<form action="/search/">
		<input type="search" name="search" />
		<input type="submit" value="Go!" />
	</form>
	
	<p id="swatch">
		<img src="/images/swatch1.jpg" alt="Thumbnail" />
		<img src="/images/swatch2.jpg" alt="Thumbnail" />
		<img src="/images/swatch3.jpg" alt="Thumbnail" />
		<img src="/images/swatch4.jpg" alt="Thumbnail" />
		<img src="/images/swatch5.jpg" alt="Thumbnail" />
		<img src="/images/swatch6.jpg" alt="Thumbnail" />
	</p>
	
</div>

<div class="panel box" id="areas">
	<h3><a href="{$baseUrl}/areas/">Areas</a></h3>
	<p>Browse by geographical area.</p>
	{$areas}
</div>

<div class="panel box" id="purchase" style="height: 35em;">
	<h3><a href="{$baseUrl}/contacts/">Purchasing images</a></h3>
	<p>The Collection is currently closed although you may contact us to register an interest in particular imagery.</p>
	<p>A restricted number of medium resolution jpegs from the Collection are available for download through the <a href="https://cudl.lib.cam.ac.uk/collections/landscapehistories/1" target="_blank">Cambridge University Digital Library website</a> under a Creative Commons (CC-BY-NC) license. We are, however, currently unable to deal with requests for high-resolution versions of these images for publication, other than for press purposes.</p>
	<p>Please <a href="/contacts/">contact us</a> if you have an urgent enquiry.</p>
</div>



{/block}
