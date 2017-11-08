<?php
class StarModel {

	public static function getByUidRids($uid, $rid_str) {
		global $_db;

		$sql = "SELECT * FROM combination WHERE uid = ? AND rids = ?";
		$input = array($uid, $rid_str);
		$dbh = $_db->prepare($sql);
		$dbh->execute($input); 

		return $dbh->fetch(PDO::FETCH_ASSOC);
	}

	public static function insert($uid, $rid_str, $star, $ctime) {
		global $_db;
		
		$sql = "INSERT INTO combination (rids, uid, star, create_time) VALUES (?,?,?,?)";
		$input = array($rid_str, $uid, $star, $ctime);
		$dbh = $_db->prepare($sql);
		$success = $dbh->execute($input);
		
		if ($success) {
			return 1;
		}
		else {
			return 0;
		}
	}
	
	public static function updateStar($id, $star) {
		global $_db;
		
		$sql = "UPDATE combination set star = ? WHERE id = ?";
		$input = array($star, $id);
		$dbh = $_db->prepare($sql);
		$success = $dbh->execute($input);
		
		if ($success) {
			return true;
		}
		else {
			return false;
		}
	}
}
