<?php
/*
Plugin Name: Batch Category Import 
Plugin URI: N/A
Version: v1.0
Author: Guy Maliar
Author URI: http://www.egstudio.biz/
Description: This is a plug-in allowing the user to create large amount of categories on the fly. This is based on the discontinued http://wordpress.org/extend/plugins/category-import/.
*/

if(!class_exists("CategoryImport")) {
	class CategoryImport{

		private function create_category($catname, $catslug, $parent_id) {

			$cat = get_term_by('slug', $catslug, 'category');

			if (!empty($cat->term_id)) 
				return $cat->term_id;

			if (empty($cat->term_id)) {	
				$catarr = array(
					'description'	=> '',
					'slug'		 	=> $catslug,
					'parent' 		=> $parent_id
					);

				$ids = wp_insert_term($catname, 'category', $catarr);
				
				return $ids['term_taxonomy_id'];
			}
		}
		
		public function form() {

			if(isset($_POST['submit'])) {
				$delimiter = strlen(trim($_POST['delimiter'])) != 0 ? $_POST['delimiter']:"$";
				$lines = explode(PHP_EOL, $_POST['bulkCategoryList']);
				foreach($lines as $line) {
					$split_line = explode('/',$line);
					foreach($split_line as $new_line) {
						if (strlen(trim($new_line)) == 0)
							break;
						if (strpos($new_line, $delimiter) !== false) {
							$cat_name_slug = explode($delimiter, $new_line);
							$cat_name = $cat_name_slug[0];
							$cat_slug = $cat_name_slug[1];
						} 
						else {
							$cat_name = $new_line;
							$cat_slug = $new_line;
						}
						
						if (isset($parent_id))
							$parent_id = $this->create_category($cat_name, $cat_slug, $parent_id);
						else
							$parent_id = $this->create_category($cat_name, $cat_slug, 0);
						
						if ($parent_id == 0) {
							$error = true;
							break 2;
						}
					}
					$parent_id = null;
				}
				
				if (isset($error))
					echo '<div id="message" class="updated fade"><p><strong>Exception happened !! Please check your input data !! </strong></p></div>';
				else
					echo '<div id="message" class="updated fade"><p><strong>Import successully finished!! </strong></p></div>';
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

			<textarea id="bulkCategoryList" name="bulkCategoryList" rows="20" style="width: 80%;"></textarea>
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
		add_submenu_page("edit.php", 'Category Import', 'Category Import', 'manage_options', __FILE__, array($dl_categoryImport, 'form'));
	}
}

function order_category_by_id($terms, $taxonomies, $args) {

	if ($taxonomies[0] == "category" && $args['orderby'] == 'name')
		$terms = get_categories(array('hide_empty' => 0,'orderby' => 'id'));

	return $terms;
}

add_action('admin_menu', 'admin_import_menu');

add_filter('get_terms', 'order_category_by_id', 10, 3);

?>