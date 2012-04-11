<?php
/*
Plugin Name: Batch Category Import
Version: v1.0.1
Author: Guy Maliar
Author URI: http://www.egstudio.biz/
Description: This is a plug-in allowing the user to create large amount of categories on the fly.
*/

if(!class_exists("CategoryImport")) {
	class CategoryImport{

		private function create_category($line, $delimiter) {
			
			$created_categories = array();
			
			$category_tree = explode('/', $line);

			foreach($category_tree as $category) {
				if (strlen(trim($category)) == 0)
					break;
				
				if (strpos($category, $delimiter) !== false) {
					$category = explode($delimiter, $category);
					$category_name = $category[0];
					$category_slug = $category[1];
					$category_description = (isset($category[2]) ? substr($category[2], 1, -1) : '');
				}
				else {
					$category_name = $category;
					$category_slug = $category;
					$category_description = '';
				}
				
				$existing_category = term_exists($category_name, 'category');
				
				if (is_array($existing_category))
					$parent_id = ((Int) $existing_category['term_id']);
				else if ($existing_category) {
					$parent_id = $existing_category;
				}
				else if ($existing_category == false) {
					$category_params = array(
						'description'	=> $category_description,
						'slug'		 	=> $category_slug,
						'parent' 		=> (isset($parent_id) ? $parent_id : 0)
						);
						
					$result = wp_insert_term($category_name, 'category', $category_params);

					if (is_wp_error($result))
						return die("$catname produced this -> ".$result->get_error_message());
		
					$created_categories[] = $category_name;
				}
			}
			
			return $created_categories;
		}
		
		public function form() {

			if(isset($_POST['submit'])) {
				$delimiter = strlen(trim($_POST['delimiter'])) != 0 ? $_POST['delimiter'] : "$";
				
				$textarea = explode(PHP_EOL, $_POST['bulkCategoryList']);
				
				foreach($textarea as $line) {
					$result[] = $this->create_category($line ,$delimiter);
				}
				echo "<div id='message' class='updated fade'><p><strong>Request returned with the following result: </strong><br />";
				$response = "";
				foreach ($result as $line => $categories_created) {
					if (!$categories_created || empty($categories_created))
						$response .= "#$line Could not create category.<br />";
					else {
						$response .= "#$line Created: ";
						foreach ($categories_created as $category) {
							$response .= "$category ";
						}
						$response .= "<br />";
					}
				}
				echo $response;
				echo "</p></div>";					
			}
			
			wp_enqueue_script('jquery');
?>
	<link rel="stylesheet" href="<?php echo WP_PLUGIN_URL; ?>/batch-category-import/css/style.css" type="text/css"/>
	<script type="text/javascript" src="<?php echo WP_PLUGIN_URL; ?>/batch-category-import/treeview.js"></script>

	<div id="formLayer">
		<h2>Category Import</h2>
		<form name="bulk_categories" action="" method="post">
			<span class="description">Enter the category you want to add.</span>
			<br/>

			<span class="description">If you want to make a hierarchy, put a slash between the category and the sub-category in one line.</span>
			<br/>

			<span class="example">Example : Level A/Level B/Level C</span>
			<br/><br/>

			<span class="description">Define a delimiter here to split the category name and slug. (default: $)</span><input type="text" id="delimiter" name="delimiter" maxlength="2" size="2" onchange="validation(this);"/>
			<br/>

			<span class="example">Example : Level A / Level B$level-b1 / Level C$level-c1</span>

			<textarea id="bulkCategoryList" name="bulkCategoryList" rows="20" style="width: 80%;"><?php echo (isset($_POST['bulkCategoryList']) ? $_POST['bulkCategoryList'] : ''); ?></textarea>
			<br/>

			<div id="displayTreeView" name="displayTreeView" style="display:none;">
				<ul id="treeView" name="treeView" class="tree"></ul>
			</div>

			<p class="submit">
				<input type="button" id="preview" name="preview" value="Preview" onclick="treeView();"/>
				<input type="button" id="closePreview" name="closePreview" value="Close Preview" style="display:none;" onclick="hideTreeView();"/>
				<input type="submit" id="submit" name="submit" value="Add categories"/>
			</p>
		</form>
	</div>
<?
		}
	}
}

function admin_import_menu() {

	require_once ABSPATH . '/wp-admin/includes/admin.php';

	if (class_exists("CategoryImport")) {
		$dl_categoryImport = new CategoryImport();
		add_submenu_page("edit.php", 'Batch Category Import', 'Batch Category Import', 'manage_options', __FILE__, array($dl_categoryImport, 'form'));
	}
}

add_action('admin_menu', 'admin_import_menu');

?>