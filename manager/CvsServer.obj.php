<?
class CvsServer {
	var $cvsroot; // cvs@10.25.25.181:/usr/local/cvsroot
	var $tempdir;
	
	function CvsServer($cvsroot) {
		$this->cvsroot = $cvsroot;
	}
	
	// check out this path and return it's absolute filesystem location
	function co($path) {
		$tmpdir = $this->getTempDir();
		$cmd = $this->getEnvironment(). " && ".
			" cd $tmpdir && cvs -Q co $path 2>&1";
		
		exec($cmd, $output, $retval);
		if ($retval == 0)
			return "$tmpdir/$path";
		else
			return false;
	}
	
	// commit this path into cvs
	function commit($path) {
		global $MANAGERUSER;
		$cmd = "cd ". $this->getTempDir();
		if (is_dir($path))
			$cmd .= " && cd $path";
		else
			$cmd .= " && cd ". dirname($path);
		
		$cmd .= " && cvs -Q commit -m \"{$MANAGERUSER->login} : commit\" 2>&1";
		
		exec($cmd, $output, $retval);
		
		if ($retval == 0)
			return true;
		else
			return false;
	}
	
	// copy default project into new project
	function copyDefault($project) {
		$tmpdir = $this->getTempDir();
		$cmd = $this->getEnvironment(). " && ".
			" cd $tmpdir && cvs -Q export -rHEAD -d $project default && ".
			" cd $project && cvs -Q import -m \"$MANAGERUSER : initial\" $project cvs initial 2>&1";
		
		exec($cmd, $output, $retval);
		
		$this->cleanupTempFiles();
		
		if ($retval == 0)
			return true;
		else
			return false;
	}
	
	private function getTempDir() {
		if (!$this->tempdir) {
			do {
				$dir = sys_get_temp_dir(). "/cvs-". mt_rand();
			} while (file_exists($dir));
			$this->tempdir = $dir;
		}
		if (!file_exists($this->tempdir))
			mkdir($this->tempdir, 0700, true);
		return $this->tempdir;
	}
	
	function cleanupTempFiles() {
		// clean up filesystem
		$cmd = "rm -rf ". $this->getTempDir();
		exec($cmd);
	}
	
	private function getEnvironment() {
		return "export CVSROOT=$this->cvsroot";
	}
}
?>