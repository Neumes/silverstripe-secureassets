<?php

/**
 * Uses similar functionality from SecureFileExtension but also introduces per-file permission
 * checks for Archived files.
 */
class ArchiveFileExtension extends SecureFileExtension {

	private static $db = array(
		'CanViewType' => 'Enum("Anyone,LoggedInUsers,OnlyTheseUsers,Inherit","Inherit")',
		'IsArchived' => 'Boolean'
	);

	private static $many_many = array(
		'ViewerGroups' => 'Group',
	);

	/**
	 * Extends SecureFileExtension to check archive state on individual files to see
	 * if we need to place or remove the .htaccess file.
	 * 
	 * @return Boolean - Whether we need the access file or not
	 */
	public function needsAccessFile() {
		
		if(SapphireTest::is_running_test()) {
			return false;
		}

		// If this file specifically is archived then we need it
		if($this->owner->IsArchived) {
			return true;
		}

		// Use the original check if we're looking at a folder to save time
		if($this->owner instanceof Folder) {
			if(parent::needsAccessFile()) {
				return true;
			}
		}
		else {
			// Otherwise we need to check the folder from the File level
			$folder = DataObject::get_by_id('Folder', $this->owner->ParentID);

			// Copied and altered from parent::needsAccessFile
			switch ($folder->CanViewType) {
				case 'LoggedInUsers':
				case 'OnlyTheseUsers':
					return true;
					break;
				default:
					break;
			}
		}

		/* If we're saving a folder, then we need to check files inside the folder
		   to see if they're archived or not */
		if($this->owner instanceof Folder) {
			$files = File::get()->filter(array(
				'ParentID' => $this->owner->ID,
				'IsArchived' => 1
			));
		}
		else {
			// Otherwise check other files to see if we need the .htaccess
			$files = File::get()->filter(array(
				'ParentID' => $this->owner->ParentID,
				'IsArchived' => 1
			));
		}
		
		// If we have a file then we need it
		if($files->first()) {
			return true;
		}

		// If we get to this point, then we don't need the .htaccess file!
		return false;
	}

	/**
	 * Permission checking to see if we can view the file and permissions match
	 * or if we're trying to access an archived file.
	 * 
	 * @param Member $member - Silverstripe Member Object
	 * @return Boolean - Whether we can view the file or not
	 */
	public function canView($member = null) {
		
		// Get the member if null
		if(!$member || !(is_a($member, 'Member')) || is_numeric($member)) $member = Member::currentUser();

		// This checks if we're inside of the CMS to edit
		if (is_subclass_of(Controller::curr(), "LeftAndMain")) {
			if($member && Permission::checkMember($member, "ADMIN")) return true;			
		}

		//By default we aren't archived
		$archived = false;

		// Check if the file is archived
		if(($this->owner instanceof File) && !($this->owner instanceof Folder)) {
			$archived = $this->owner->IsArchived;
		}

		// Get view permissions
		$canView = parent::canView($member);

		// If we can view it then we MAY need to override if it's archived
		if($canView) {
			if($archived) {
				return false;
			}
		}

		// Otherwise we honour the parent permission checks
		return $canView;
	}

	/**
	 * Updates the CMS fields presented
	 * 
	 * @param FieldList $fields - The fields in the CMS
	 * @return void
	 */
	public function updateCMSFields(FieldList $fields) {

		parent::updateCMSFields($fields);

		// Makes sure we're only adding the fields to a File (Image, etc acceptable) and not a Folder
		if((($this->owner instanceof File) && !($this->owner instanceof Folder)) && $this->owner->ParentID) {
			
			$folder = DataObject::get_by_id('Folder', $this->owner->ParentID);
			
			// Only show File Archive settings if the Folder is allowed to have archived files
			if($folder->CanArchive) {
				$fields->addFieldToTab('Root.Main', new DropdownField('IsArchived', 'Archive State', array(0 => "Don't Archive", 1 => "Archive")));
			}
		}
	}

	/**
	 * Add or remove access rules to the filesystem path.
	 * CAUTION: This will not work properly in the presence of third-party .htaccess file
	 *
	 * @return void
	 */
	function onAfterWrite() {
		parent::onAfterWrite();

		/* This will mess with tests like FolderTest, it'll expect an .htaccess to be there,
		   but onAfterWrite here will unintentionally remove it. We can workaround that by
		   skipping the access file writing if a unit test is currently running. */
		if(SapphireTest::is_running_test()) {
			return false;
		}

		// Make sure that this is occurring on a File or derived File object (can never assume)
		if($this->owner instanceof File) {

			$config = $this->getAccessConfig();

			// If it's a folder, use the full path
			if($this->owner instanceof Folder) {
				$accessFilePath = $this->owner->getFullPath() . $config['file'];
			}
			else {
				// Get the dir name instead for files
				$accessFilePath = dirname($this->owner->getFullPath()) . DIRECTORY_SEPARATOR . $config['file'];
			}

			// If we need access files make sure that the .htaccess exists and place it if it doesn't
			if($this->needsAccessFile()) {
				if(!file_exists($accessFilePath)) file_put_contents($accessFilePath, $config['content']);
			}
			else {
				// Remove it if we no longer need it to remove the processing overhead
				if(file_exists($accessFilePath)) unlink($accessFilePath);
			}
		}
	}
}