<?php
/*******************************************************************************
 *
 * CoreModRoleMgmt.php - Core module to handle the role management tasks
 *
 * Copyright (c) 2004-2011 NagVis Project (Contact: info@nagvis.org)
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
 ******************************************************************************/

/**
 * @author Lars Michelsen <lars@vertical-visions.de>
 */
class CoreModRoleMgmt extends CoreModule {
	protected $CORE;
	protected $FHANDLER;
	
	public function __construct($CORE) {
		$this->sName = 'RoleMgmt';
		$this->CORE = $CORE;
		
		$this->aActions = Array('view'         => 'manage',
		                        'getRolePerms' => 'manage',
		                        'doAdd'        => 'manage',
		                        'doEdit'       => 'manage',
		                        'doDelete'     => 'manage');
		
		$this->FHANDLER = new CoreRequestHandler($_POST);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				// The best place for this would be a FrontendModule but this needs to
				// be in CoreModule cause it is fetched via ajax. The error messages
				// would be printed in HTML format in nagvis-js frontend.
				case 'view':
					$VIEW = new NagVisViewManageRoles($this->AUTHORISATION);
					$sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
				case 'getRolePerms':
					// Parse the specific options
					$aVals = $this->getCustomOptions(Array('roleId' => MATCH_INTEGER));
					$roleId = $aVals['roleId'];
					
					// Get current permissions of role
					$sReturn = json_encode($this->AUTHORISATION->getRolePerms($roleId));
				break;
				case 'doAdd':
					$aReturn = $this->handleResponseAdd();
					
					if($aReturn !== false) {
						// Try to apply
						if($this->AUTHORISATION->createRole($aReturn['name'])) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The role has been created.'));
							$sReturn = '';
						} else {
							new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The user could not be created.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'doEdit':
					$aReturn = $this->handleResponseEdit();
					
					if($aReturn !== false) {
						if($this->AUTHORISATION->updateRolePerms($aReturn['roleId'], $aReturn['perms'])) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The permissions for this role have been updated.'));
							$sReturn = '';
						} else {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('Problem while updating role permissions.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'doDelete':
					$aReturn = $this->handleResponseDelete();
					
					if($aReturn !== false) {
						if($this->AUTHORISATION->deleteRole($aReturn['roleId'])) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The role has been deleted.'));
							$sReturn = '';
						} else {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('Problem while deleting the role.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function handleResponseDelete() {
		$bValid = true;
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('roleId'))
			$bValid = false;
		
		// Regex validate
		if($bValid && !$this->FHANDLER->match('roleId', MATCH_INTEGER))
			$bValid = false;
		
		// Parse the specific options
		$roleId = intval($this->FHANDLER->get('roleId'));
		
		// FIXME: Check not to delete any referenced role
		
	  // Store response data
	  if($bValid === true)
		  return Array('roleId' => $roleId);
		else
			return false;
	}
	
	private function handleResponseEdit() {
		$bValid = true;
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('roleId'))
			$bValid = false;
		
		// Regex validate
		if($bValid && !$this->FHANDLER->match('roleId', MATCH_INTEGER))
			$bValid = false;
		
		// Parse the specific options
		$roleId = intval($this->FHANDLER->get('roleId'));
		
		// Load perm options
		$aPerms = Array();
		foreach($this->FHANDLER->getKeys() AS $key) {
			// Only load permission keys
			if(strpos($key, 'perm_') !== false) {
				$aKey = explode('_', $key);
				$permId = $aKey[1];
				
				if($this->FHANDLER->isSetAndNotEmpty($key)) {
					$aPerms[$permId] = true;
				} else {
					$aPerms[$permId] = false;
				}
			}
		}
		
	  // Store response data
	  if($bValid === true)
		  return Array('roleId' => $roleId, 'perms' => $aPerms);
		else
			return false;
	}
	
	private function handleResponseAdd() {
		$bValid = true;
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('name'))
			$bValid = false;
		
		// Check length limits
		if($bValid && $this->FHANDLER->isLongerThan('name', AUTH_MAX_ROLENAME_LENGTH))
			$bValid = false;
		
		// Regex validate
		if($bValid && !$this->FHANDLER->match('name', MATCH_ROLE_NAME))
			$bValid = false;

		// Check if the role already exists
		if($bValid && $this->AUTHORISATION->checkRoleExists($this->FHANDLER->get('name'))) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The rolename is invalid or does already exist.'));
			$bValid = false;
		}
		
		//@todo Escape vars?
		
	  // Store response data
	  if($bValid === true)
		  return Array('name' => $this->FHANDLER->get('name'));
		else
			return false;
	}
}
?>
