<?php
class UploadModel {
	public function __construct(Database $db, $user){
		$this->db = $db;
	}
	
	public function getDatabaseImage(
		$filename,
		$writeToFile = true
	){
		if (
			$blobs = $this->db->qSelect(
				"
					SELECT	`File`
					FROM	`Image`
					WHERE	`Filename` = ?
				",
				's',
				[$filename]
			)
		){
			$blob = $blobs[0]['File'];
			if ($writeToFile)
				$this->writeImageFile(
					$filename,
					$blob
				);
				
			return $blob;
		}
			
		return false;
	}
	
	public function writeImageFile(
		$filename,
		$blob
	){
		$path = strtolower(UPLOADS_PATH . $filename);
		return
			file_exists($path) ||
			(
				file_put_contents(
					$path,
					$blob
				) &&
				chmod(
					$path,
					0644
				)
			);
	}
}
