<?
class CvsServer {
	var $cvsroot; // cvs@10.25.25.181:/usr/local/cvsroot
	var $privatekey;
	
	function CvsServer($cvsroot) {
		$this->cvsroot = $cvsroot;
	}
	
	// check out this path and return it's absolute filesystem location
	function co($path) {
		$tmpdir = $this->mktmpdir();
		$cmd = $this->getEnvironment(). " && ".
			" cd $tmpdir && cvs -Q co $path 2>&1";
		
		exec($cmd, $output, $retval);
		if ($retval == 0)
			return "$tmpdir/$path";
		else
			return false;
	}
	
	// copy default project into new project
	function copyDefault($project) {
		$tmpdir = $this->mktmpdir();
		$cmd = $this->getEnvironment(). " && ".
			" cd $tmpdir && cvs -Q export -rHEAD -d $project default && ".
			" cd $project && cvs -Q import -m \"initial\" $project cvs initial";
		
		exec($cmd, $output, $retval);
		if ($retval == 0)
			return true;
		else
			return false;
	}
	
	function getEnvironment() {
		return "export HOME=/tmp/ && export CVSROOT=$this->cvsroot";
	}
	
	function mktmpdir() {
		do {
			$dir = sys_get_temp_dir(). "/cvs-". mt_rand();
		} while (file_exists($dir));
		mkdir($dir, 0700, true);
		return $dir;
	}
}
?>