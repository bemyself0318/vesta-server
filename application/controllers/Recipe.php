<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include_once "models/inc.php";

class Recipe extends CI_Controller {

	public function __construct() {
		parent::__construct();
		date_default_timezone_set("Asia/Taipei");
	}
	
	public function recommend() {
		$input = $this->input->post();
		$rtn = $this->_mst($input['people'], $input['dishes'], $input['budget'], $input['main_soup']);

		$tc = 0;
		$tp = 0;
		foreach($rtn['data'] as $node) {
			$r = RecipeModel::getById($node);				
			$tc += $r['calorie'];
			$tp += $r['price'];
		}

		$loop_cnt = 0;
		while($rtn['code'] == 0 || $tp > 1.2 * $input['budget']) {
			//refind
			$loop_cnt++;
			if ($loop_cnt == 1000) {
				break; 	
			}
			$rtn = $this->_mst($input['people'], $input['dishes'], $input['budget'], $input['main_soup']);
			$tc = 0;
			$tp = 0;
			foreach($rtn['data'] as $node) {
				$r = RecipeModel::getById($node);				
				$tc += $r['calorie'];
				$tp += $r['price'];
			}
		}
		
		$recipe_set = array();
		$cost = 0;
		foreach ($rtn['data'] as $v) {
			$r = RecipeModel::getById($v);
			array_push($recipe_set, $r);
			$cost += $r['price'];
		}
		$ajax_data = array('recipe'=>$recipe_set, 'cost'=>$cost);
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST');
		header('Content-type: application/json; chartset=utf-8');
		echo json_encode($ajax_data);
	}
	
	public function changeOne() {
		$post_data = $this->input->post();
		$recipe_list = $post_data['all_id'];
		
		$change_key = array_search($post_data['change_id'], $recipe_list);
		unset($recipe_list[$change_key]);
		
		$need_change_recipe = RecipeModel::getById($post_data['change_id']);
		$dish_nutrition = $need_change_recipe['calorie'];
		
		$current_cost = 0;
		foreach($recipe_list as $r) {
			$tmp = RecipeModel::getById($r);
			$current_cost += $tmp['price'];
		}
		$dish_budget = $post_data['budget'] - $current_cost;
		
		$new_recipes = array();
		foreach($recipe_list as $id) {
			$tmp = RecipeModel::getSuccessor($id, $dish_nutrition, $dish_budget);
			if($tmp) {
				foreach($tmp as $r) {
					array_push($new_recipes, $r);
				}
			}
		}
		
		$ajax_data = array('new_recipe'=>array(), 'find'=>false);
		// $min_calorie = -1;
		$void_infinite = count($new_recipes);
		$new_id = rand(0, count($new_recipes)-1);
		while(1) {
			if ($new_id != $post_data['change_id'] && !in_array($new_id, $recipe_list)) {
				$tmp = RecipeModel::getById($new_id);
				if($tmp) {
					$ajax_data['find'] = true;
					$ajax_data['new_recipe'] = $tmp;
					break;
				}
				else {
					continue;
				}
				
			}
			$new_id = rand(0, count($new_recipes)-1);
			$void_infinite -= 1;
			if($void_infinite < 0) {
				break;
			}
		} 
		
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST');
		header('Content-type: application/json; chartset=utf-8');
		echo json_encode($ajax_data);
		
		
	}

	public function finalList() {
		$post_data = $this->input->post();

		$recipe_set = array();
		foreach($post_data['rids'] as $rid) {
			$tmp = RecipeModel::getById($rid);
			$recipe_set[] = $tmp;
		}
		
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST');
		header('Content-type: application/json; chartset=utf-8');
		echo json_encode($recipe_set);
	}
	
	public function score() {
		$post = $this->input->post();
		$star = $post['stars'];
		$uid = $post['uid'];
		$rid_array = $post['rids'];
		$rid_str = '';
		$flag = 0;
		foreach($rid_array as $rid) {
			if($flag == 0) {
				$rid_str .= $rid;
				$flag = 1;
			}
			else{
				$rid_str .= ",".$rid;
			}
		}
		
		$ajax_data = array('success'=>1);
		if($row = StarModel::getByUidRids($uid, $rid_str)) {
			StarModel::updateStar($row['id'], $star);
		}
		else {
			$lid = StarModel::insert($uid, $rid_str, $star, date('Y-m-d H:m:s'));
			// if lid == 0 means fail
			if ($lid == 0) {
				$ajax_data['success'] = 0;
			}
		}
		
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST');
		header('Content-type: application/json; chartset=utf-8');
		echo json_encode($ajax_data);
	}
	
	public function _mst($people, $dishes, $budget, $main_soup) {
		$rtn = array('code'=>1, 'data'=>array());	
		$dish_nutrition = (int)$people * 700 / $dishes; // 700 means a human need take calorie per meal
		$dish_budget = (int)$budget / $dishes;

		$mst = array(); 
		
		
		if ($main_soup == 3){
			$main_id_array = RecipeModel::getByType('main');
			$soup_id_array = RecipeModel::getByType('soup');
			$main_id = $main_id_array[rand(0, count($main_id_array))]['id'];
			$soup_id = $soup_id_array[rand(0, count($soup_id_array))]['id'];
	
			// main test  
			$first_children = RecipeModel::getSuccessor($main_id, $dish_nutrition, $dish_budget);
			$void = 0;
			while(count($first_children) == 0) {
				$void ++;
				$main_id = $main_id_array[rand(0, count($main_id_array))]['id'];
				$first_children = RecipeModel::getSuccessor($main_id, $dish_nutrition, $dish_budget);
				if($void > 1000) {
					$rtn['code'] = 0;
					break;
				}
			}
			array_push($mst, $main_id);
			
			//soup test
			$first_children = RecipeModel::getSuccessor($soup_id, $dish_nutrition, $dish_budget);
			$void = 0;
			while(count($first_children) == 0) {
				$void ++;
				$soup_id = $soup_id_array[rand(0, count($soup_id_array))]['id'];
				$first_children = RecipeModel::getSuccessor($soup_id, $dish_nutrition, $dish_budget);
				if($void > 1000) {
					$rtn['code'] = 0;
					break;
				}
			}
			array_push($mst, $soup_id);
		}
		else if ($main_soup == 2) {
			$soup_id_array = RecipeModel::getByType('soup');
			$soup_id = $soup_id_array[rand(0, count($soup_id_array))]['id'];
			//soup test
			$first_children = RecipeModel::getSuccessor($soup_id, $dish_nutrition, $dish_budget);
			$void = 0;
			while(count($first_children) == 0) {
				$void ++;
				$soup_id = $soup_id_array[rand(0, count($soup_id_array))]['id'];
				$first_children = RecipeModel::getSuccessor($soup_id, $dish_nutrition, $dish_budget);
				if($void > 1000) {
					$rtn['code'] = 0;
					break;
				}
			}
			array_push($mst, $soup_id);
		}
		else if ($main_soup == 1) {
			$main_id_array = RecipeModel::getByType('main');
			$main_id = $main_id_array[rand(0, count($main_id_array))]['id'];
			// main test  
			$first_children = RecipeModel::getSuccessor($main_id, $dish_nutrition, $dish_budget);
			$void = 0;
			while(count($first_children) == 0) {
				$void ++;
				$main_id = $main_id_array[rand(0, count($main_id_array))]['id'];
				$first_children = RecipeModel::getSuccessor($main_id, $dish_nutrition, $dish_budget);
				if($void > 1000) {
					$rtn['code'] = 0;
					break;
				}
			}
			array_push($mst, $main_id);
		}
		else {
			// all normal
			$root_id = rand(0,95454);
			$first_children = RecipeModel::getSuccessor($root_id, $dish_nutrition, $dish_budget);
			$void = 0;
			while(count($first_children) == 0) {
				$void ++;
				$root_id = rand(0,95454);
				$first_children = RecipeModel::getSuccessor($root_id, $dish_nutrition, $dish_budget);
				if($void > 1000) {
					$rtn['code'] = 0;
					break;
				}
			}
			array_push($mst, $root_id);
		}

		while(count($mst) < $dishes) {
			$void ++;
			$nu_max = 0;
			$min_budget = 999999999 ;
			$chose = 0;
			foreach ($mst as $node_id) {
				$children = RecipeModel::getSuccessor($node_id, $dish_budget, $dish_nutrition, $dish_budget);
				foreach ($children as $child) {
					if ($child['calorie'] > $nu_max && ! in_array($child['id'], $mst)) {
						$nu_max = $child['calorie'];
						$min_budget = $child['price'];
						$chose = $child['id'];

						if ($child['price'] < $min_budget) {
							$min_budget = $child['price'];
							$nu_max = $child['calorie'];
							$chose = $child['id'];
						}	
					}	
				}
			}
			if ($chose != 0) {
				array_push($mst, $chose);
			}
			else {
				$rtn['code'] = 0;
				break;
			}
		}
		$rtn['data'] = $mst;

		return $rtn;
	}
}
