<?php
/*****************************************************************************
 *
 * NagVisFrontend.php - Class for handling the NagVis frontend
 *
 * Copyright (c) 2004-2008 NagVis Project (Contact: lars@vertical-visions.de)
 *
 * License:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *****************************************************************************/
 
/**
 * @author	Lars Michelsen <lars@vertical-visions.de>
 */
class NagVisFrontend extends GlobalPage {
	var $CORE;
	var $MAPCFG;
	var $BACKEND;
	
	var $ROTATION;
	
	var $MAP;
	
	var $headerTemplate;
	var $htmlBase;
	
	/**
	 * Class Constructor
	 *
	 * @param 	GlobalCore 	$CORE
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisFrontend(&$CORE, $MAPCFG = '', $BACKEND = '', $ROTATION= '') {
		$prop = Array();
		
		$this->CORE = &$CORE;
		$this->MAPCFG = &$MAPCFG;
		$this->BACKEND = &$BACKEND;
		
		if(!$ROTATION) {
			$this->ROTATION = new NagVisRotation($this->CORE);
		} else {
			$this->ROTATION = &$ROTATION;
		}
		
		$this->htmlBase = $this->CORE->MAINCFG->getValue('paths','htmlbase');
		
		$prop['title'] = $this->CORE->MAINCFG->getValue('internal', 'title');
		$prop['cssIncludes'] = Array($this->htmlBase.'/nagvis/includes/css/style.css', $this->htmlBase.'/nagvis/includes/css/frontendEventlog.css');
		$prop['jsIncludes'] = Array($this->htmlBase.'/nagvis/includes/js/nagvis.js',
															$this->htmlBase.'/nagvis/includes/js/json2.js',
															$this->htmlBase.'/nagvis/includes/js/frontend.js',
															$this->htmlBase.'/nagvis/includes/js/frontendEventlog.js',
															$this->htmlBase.'/nagvis/includes/js/NagVisObject.js',
															$this->htmlBase.'/nagvis/includes/js/NagVisStatefulObject.js',
															$this->htmlBase.'/nagvis/includes/js/NagVisStatelessObject.js',
															$this->htmlBase.'/nagvis/includes/js/NagVisHost.js',
															$this->htmlBase.'/nagvis/includes/js/NagVisService.js',
															$this->htmlBase.'/nagvis/includes/js/NagVisHostgroup.js',
															$this->htmlBase.'/nagvis/includes/js/NagVisServicegroup.js',
															$this->htmlBase.'/nagvis/includes/js/NagVisMap.js',
															$this->htmlBase.'/nagvis/includes/js/NagVisShape.js',
															$this->htmlBase.'/nagvis/includes/js/NagVisTextbox.js',
															$this->htmlBase.'/nagvis/includes/js/overlib.js',
															$this->htmlBase.'/nagvis/includes/js/dynfavicon.js',
															$this->htmlBase.'/nagvis/includes/js/ajax.js',
															$this->htmlBase.'/nagvis/includes/js/hover.js',
															$this->htmlBase.'/nagvis/includes/js/wz_jsgraphics.js', 
															$this->htmlBase.'/nagvis/includes/js/lines.js');
		$prop['extHeader'] = '<link rel="shortcut icon" href="'.$this->htmlBase.'/nagvis/images/internal/favicon.png">';
		$prop['languageRoot'] = 'nagvis';
		
		// Only do this, when a map needs to be displayed
		if(get_class($this->MAPCFG) != '') {
			$prop['extHeader'] .= '<style type="text/css">body.main { background-color: '.$this->MAPCFG->getValue('global',0, 'background_color').'; }</style>';
			$prop['allowedUsers'] = $this->MAPCFG->getValue('global',0, 'allowed_user');
		}
		
		parent::GlobalPage($CORE, $prop);
	}
	
	/**
	 * If enabled, the header menu is added to the page
	 *
	 * @param  Bool    Enable/Disable the header menu
	 * @param  String  Header template name
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHeaderMenu($headerMenuEnabled, $headerTemplateName) {
		if($headerMenuEnabled) {
			// Parse the header menu
			$HEADER = new GlobalHeaderMenu($this->CORE, $headerTemplateName, $this->MAPCFG);
			$this->addBodyLines($HEADER);
		}
	}
	
	/**
	 * Adds the index to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getIndexPage() {
		$this->addBodyLines('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>');
		$this->addBodyLines('<div class="infopage">');
		$this->INDEX = new GlobalIndexPage($this->CORE, $this->BACKEND);
		$this->addBodyLines($this->INDEX->parse());
		$this->addBodyLines('</div>');
		$this->addBodyLines($this->parseJs($this->INDEX->parseJson()));
	}
	
	/**
	 * Adds the map to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMap() {
		$this->addBodyLines('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>');
		$this->addBodyLines('<div id="map" class="map">');
		$this->MAP = new NagVisMap($this->CORE, $this->MAPCFG, $this->BACKEND);
		$this->MAP->MAPOBJ->checkMaintenance(1);
		$this->addBodyLines('</div>');
		$this->addBodyLines($this->parseJs($this->MAP->parseMapJson()));
	}
	
	/**
	 * Adds the automap to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getAutoMap($arrOptions) {
		$this->addBodyLines('<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>');
		$this->addBodyLines('<div id="map" class="map">');
		$this->MAP = new NagVisAutoMap($this->CORE, $this->BACKEND, $arrOptions);
		$this->addBodyLines($this->MAP->parseMap());
		$this->addBodyLines('</div>');
		$this->addBodyLines($this->parseJs($this->MAP->parseMapJson()));
	}
	
	/**
	 * Adds the user messages to the page
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getMessages() {
		$this->addBodyLines($this->getUserMessages());
	}
	
	/**
	 * Gets the javascript code for the map refresh/rotation
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getPagePropertiesJson($bRefresh) {
		$arr = Array();
		
		if($this->ROTATION->getPoolName() != '') {
			$arr['rotationEnabled'] = 1;
			$arr['nextStepUrl'] = $this->ROTATION->getNextStepUrl();
			$arr['nextStepTime'] = $this->ROTATION->getStepInterval();
		} else {
			$arr['rotationEnabled'] = 0;
			$arr['nextStepUrl'] = '';
			if($bRefresh) {
				$arr['nextStepTime'] = $this->ROTATION->getStepInterval();
			} else {
				$arr['nextStepTime'] = '';
			}
		}
		
		return json_encode($arr);
	}
}
?>
