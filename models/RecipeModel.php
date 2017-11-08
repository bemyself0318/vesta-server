<?php
class RecipeModel {
	
	private static $table_name = "recipe";

	public static function getById($id) {
		global $_db;

		$sql = "SELECT * FROM recipe WHERE id = ?";
		$input = array($id);
		$dbh = $_db->prepare($sql);
		$dbh->execute($input); 

		return $dbh->fetch(PDO::FETCH_ASSOC);
	}

	public static function getSuccessor($parent, $dish_nutrition, $dish_price) {
		global $_db;

		$sql = "SELECT jaccard.y, jaccard.value, recipe.* FROM jaccard, recipe WHERE jaccard.x = ? AND jaccard.value < 1 AND recipe.id = jaccard.y AND recipe.calorie >= ? AND recipe.price <= ?";
		$input = array($parent, $dish_nutrition, $dish_price);
		$dbh = $_db->prepare($sql);
		$dbh->execute($input); 

		return $dbh->fetchAll(PDO::FETCH_ASSOC);
	}

}
