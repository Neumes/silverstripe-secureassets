<?php

/**
 * Adds a field to Folder which allows files inside the folder to be archived.
 */
class ArchiveFolderExtension extends DataExtension {
	
	private static $db = array(
		'CanArchive' => 'Boolean'
	);

	/**
	 * Updates the CMS fields presented
	 * 
	 * @param FieldList $fields - The fields in the CMS
	 * @return void
	 */
	public function updateCMSFields(FieldList $fields)
	{
		if(($this->owner instanceof Folder) && $this->owner->ID) {

			// See if we have at least a single file that is archived. Quantity doesn't matter.
			$file = File::get()->filter(array('ParentID' => $this->owner->ID, 'IsArchived' => 1))->first();

			// Create a header
			$fields->push(new HeaderField('FileArchiving', 'Folder Archive Settings', 3));

			// Create our dropdown to choose from
			$dropdown = new DropdownField('CanArchive', 'Allow File Archiving', array(0 => 'No', 1 => 'Yes'));

			/* If an Archived File exists then we need to disable the dropdown and let the user know
			   the reason for why they cannot revert the Archiving Functionality */
			if($file) {
				$dropdown->setDisabled(true);
				$fields->push(new LiteralField('FilesArchived', 'There are files currently archived. Please move or unarchive the files to be able to disable archive functionality'));
				$fields->push($dropdown);
			}
			else {
				// Otherwise just show the field as-is
				$fields->push($dropdown);
			}
		}
	}
}