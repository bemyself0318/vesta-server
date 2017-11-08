<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include_once "models/inc.php";

class User extends CI_Controller {

	public function __construct() {
		parent::__construct();
		date_default_timezone_set("Asia/Taipei");
	}
	
	public function login() {
		$post_data = $this->input->post();
		$rtn = array('login_err' => 0, 'new_user_err' => 0);
	
		if ($user = UserModel::getByGid($post_data['google_id'])) {
			$login = UserModel::login($user['id']);
			if($login == false)
				$rtn['login_err'] = 1;
				//TODO: login fail
		}
		else {
			$new_user = UserModel::new_user($post_data, date('Y-m-d H:m:s'));
			if ($new_user == 0)
				$rtn['new_user_err'] = 1;
				//TODO: new user fail
		}

		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST');
		header('Content-type: application/json; chartset=utf-8');
		echo json_encode($rtn);
	}

}
