<?php
class UserModel {
	
	public static function new_user($user, $ctime) {
		global $_db;
		
		$sql = "INSERT INTO user (name, google_id, email, create_time, is_login) VALUES (?,?,?,?,?)";
		$input = array($user['name'], $user['google_id'], $user['email'], $ctime, 1);
		$dbh = $_db->prepare($sql);
		$success = $dbh->execute($input);
		
		if ($success) {
			return $dbh->lastInsertId();
		}
		else {
			return 0;
		}
	}
	
	public static function getByGid($gid) {
		global $_db;
		
		$sql = "SELECT * FROM user WHERE google_id = ?";
		$input = array($gid);
		$dbh = $_db->prepare($sql);
		$success = $dbh->execute($input);
		
		if ($success) {
			return $dbh->fetch(PDO::FETCH_ASSOC);;
		}
		else {
			return false;
		}
	}
	
	public static function login($uid) {
		global $_db;
		
		$sql = "UPDATE user set is_login = 1 WHERE id = ?";
		$input = array($uid);
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
