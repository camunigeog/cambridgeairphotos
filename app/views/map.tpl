{* This is a generic content page *}
{extends file="application.tpl"}
{block name=body}


<h2>Browse the map</h2>

<div id="mapContainer">
	
	<input id="locationfinder" type="text" name="location" placeholder="Move map to&hellip;" />
	
	<div id="map"></div>
	
	<div class="map-tools-overlay">
		<form method="post" id="submitgeometry" name="form" action="{$baseUrl}/search/">
		<div class="panes">
			<input id="issue_loc_json" name="loc_json" type="hidden" value="" />
			
			<div class="pane">
				<h3>Draw an area<br />to obtain report</h3>
				<nav>
					<ul>
						<li><a><span class="draw area"></span>draw area</a></li>
					</ul>
				</nav>
				<p>Click the points to mark an area on the map.</p>
				<p>Double-click to complete the shape.</p>
				<p>Zoom in and pan to select the correct area.</p>
				<input type="submit" id="polygon_submit" value="Submit drawn area" />
			</div>
		</div>
		</form>
		<ul class="undo-clear">
			<!--<li><a class="edit-undo"><span class="icon-undo"></span><span class="txt">undo</span></a></li>-->
			<li><a class="edit-clear"><span class="icon-clear"></span><span class="txt">clear</span></a></li>
		</ul>
	</div>
	
	
</div>

<p id="key">Key: <img src="{$baseUrl}/images/vertical.png" alt="Vertical" border="0" /> Vertical &nbsp; <img src="{$baseUrl}/images/oblique.png" alt="Vertical" border="0" /> Oblique</p>


{$map}


{/block}
