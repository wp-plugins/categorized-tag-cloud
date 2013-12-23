<?php
/*
Plugin Name: Categorized Tag Cloud
Plugin URI: http://www.whiletrue.it/
Description: Takes the website tags and aggregates them into a categorized cloud widget for sidebar.
Author: WhileTrue
Version: 1.0.3
Author URI: http://www.whiletrue.it/
*/

/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2, 
    as published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

function categorized_tag_cloud ($instance) {
	
	$plugin_name = 'categorized-tag-cloud';

	// RETRIEVE TAGS
	$words_color   = $instance['words_color'];
	$hover_color   = (isset($instance['hover_color'])   && $instance['hover_color']!='') ? $instance['hover_color'] : 'black';
	$number        = (isset($instance['words_number'])  && is_numeric($instance['words_number'])  && $instance['words_number'] >0) ? $instance['words_number'] : 20;
	$smallest_size = (isset($instance['smallest_size']) && is_numeric($instance['smallest_size']) && $instance['smallest_size']>0) ? $instance['smallest_size'] : 7;
	$largest_size  = (isset($instance['largest_size'])  && is_numeric($instance['largest_size'])  && $instance['largest_size'] >0) ? $instance['largest_size']  : 14;

  $exclude_items = array();
  $category_filters = (array)json_decode($instance['category_filters']);
  for ($i=0; $i<count($category_filters['cat']); $i++) {
    if (is_category($category_filters['cat'][$i]) || (is_single() && in_category($category_filters['cat'][$i]))) {
      $exclude_items[] = $category_filters['tag'][$i];
    }
  }

	$tags = wp_tag_cloud('smallest=14&largest=30&number='.$number.'&order=RAND&format=array&exclude='.implode(',',$exclude_items) );

	$out = '';
	$out_style = '';
	foreach ($tags as $num=>$tag) {
		$i = $num+1;
		$out .=  '<span id="'.$plugin_name.'-el-'.$i.'">'.$tag.'</span> ';

		$the_color = ($words_color=='') ? '#'.str_pad(dechex(rand(0,4096)),3,'0',STR_PAD_LEFT) : $words_color;
		$out_style .=  '
  		#'.$plugin_name.'-el-'.$i.' a, #'.$plugin_name.'-el-'.$i.' a:visited { text-decoration:none; color:'.$the_color.'; }';	
	}	
	
	return '
    <div id="'.$plugin_name.'">'.$out.'</div>
  	<style>
  	'.$out_style.'
  	</style>';
}


//////////


/**
 * CategorizedTagCloudWidget Class
 */
class CategorizedTagCloudWidget extends WP_Widget {
    /** constructor */
    function CategorizedTagCloudWidget() {
		$this->options = array(
			array(
        'name'=>'title',             'label'=>'Title:', 
        'type'=>'text'),
			array(
        'name'=>'words_number',      'label'=>'How many tags to show:', 
        'type'=>'text'),
			array(
        'name'=>'words_color',       'label'=>'Word color (random if not entered):', 
        'type'=>'text'),
			array(
        'name'=>'hover_color',       'label'=>'Hover color (black if not entered):', 
        'type'=>'text'),
			array(
        'name'=>'smallest_font',     'label'=>'Smallest font size (default is 7):', 
        'type'=>'text'),
			array(
        'name'=>'largest_font',      'label'=>'Largest font size (default is 14):', 
        'type'=>'text'),
			array(
				'label' => 'Category filters',
				'type'	=> 'separator'			),
			array(
				'type'	=> 'category_filters'			),
			array(
				'type'	=> 'donate'			),
		);

        $control_ops = array('width' => 500);
        parent::WP_Widget(false, 'Categorized Tag Cloud', array(), $control_ops);	
    }

    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;  
		if ( $title ) echo $before_title . $title . $after_title; 
		echo categorized_tag_cloud($instance).$after_widget;
    }

    function update($new_instance, $old_instance) {				
	$instance = $old_instance;

	foreach ($this->options as $val) {
		if ($val['type']=='text') {
			$instance[$val['name']] = strip_tags($new_instance[$val['name']]);
		} else if ($val['type']=='checkbox') {
			$instance[$val['name']] = ($new_instance[$val['name']]=='on') ? true : false;
		}
    
    // CATEGORY FILTERS
    $instance['category_filters'] = '';
    
    if (is_numeric($_POST['categorized-tag-cloud-num-filters'])) {
      $instance['category_filters'] = array();
      for ($i=0; $i<$_POST['categorized-tag-cloud-num-filters']; $i++) {
        if ($_POST['categorized-tag-cloud-cat-'.$i] == '' && $_POST['categorized-tag-cloud-tag-'.$i] == '') {
          continue;
        }
        $instance['category_filters']['cat'][] = esc_html($_POST['categorized-tag-cloud-cat-'.$i]);
        $instance['category_filters']['tag'][] = esc_html($_POST['categorized-tag-cloud-tag-'.$i]);
      }
      $instance['category_filters'] = json_encode($instance['category_filters']);
    }
	}

       return $instance;
    }

    function form($instance) {
		if (empty($instance)) {
			$instance['title']             = 'Categorized Tag Cloud';
			$instance['words_number']      = 20;
			$instance['words_color']       = '';
			$instance['smallest_font']     = '7';
			$instance['largest_font']      = '14';
			$instance['horizontal_spread'] = '60';
			$instance['vertical_spread']   = '60';
		}					

		foreach ($this->options as $val) {
			if ($val['type']=='separator') {
				if (isset($val['label']) && $val['label']!='') {
					echo '<h3>'.__($val['label'], 'categorized-tag-cloud' ).'</h3>';
				} else {
					echo '<hr />';
				}
				if (isset($val['notes']) && $val['notes']!='') {
					echo '<div class="description">'.$val['notes'].'</div>';
				}
			} else if ($val['type']=='category_filters') {
        $category_filters = (array)json_decode($instance['category_filters']);
				echo '<input name="categorized-tag-cloud-num-filters" type="hidden" value="'.(count($category_filters)+2).'" />';
        echo '<table>
          <tr>
            <th>'.__('category slug', 'categorized-tag-cloud').'</th>
            <th style="min-width:300px">'.__('excluded tags id, comma separated', 'categorized-tag-cloud').'</th>
          </tr>';
        for ($i=0; $i<count($category_filters['cat']); $i++) {
          echo '
            <tr>
              <td><input type="text" name="categorized-tag-cloud-cat-'.$i.'" value="'.$category_filters['cat'][$i].'" /></td>
              <td><input type="text" name="categorized-tag-cloud-tag-'.$i.'" value="'.$category_filters['tag'][$i].'" style="min-width:300px" /></td></tr>';
        }
        for ($j=$i; $j<($i+2); $j++) {
          echo '
            <tr>
              <td><input type="text" name="categorized-tag-cloud-cat-'.$j.'" /></td>
              <td><input type="text" name="categorized-tag-cloud-tag-'.$j.'" style="min-width:300px" /></td></tr>';
        }
  			echo '</table>';
      } else if ($val['type']=='donate') {
        echo '<p style="text-align:center; font-weight:bold;">
            '.__('Do you like it? I\'m supporting it, please support me!', 'categorized-tag-cloud').'<br />
      			<form method="post" action="https://www.paypal.com/cgi-bin/webscr">
        			<input value="_s-xclick" name="cmd" type="hidden">
        			<input value="-----BEGIN PKCS7-----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-----END PKCS7----- " name="encrypted" type="hidden"> 
        			<input alt="PayPal - The safer, easier way to pay online!" name="submit" border="0" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" type="image"> 
        			<img height="1" width="1" src="https://www.paypalobjects.com/WEBSCR-640-20110306-1/it_IT/i/scr/pixel.gif" border="0"> 
      			</form>
          </p>';
      } else if ($val['type']=='text') {
  			echo '<p>
  				      <label for="'.$this->get_field_id($val['name']).'">'.__($val['label'], 'categorized-tag-cloud' ).'</label> 
  				   ';
				echo '<input class="widefat" id="'.$this->get_field_id($val['name']).'" name="'.$this->get_field_name($val['name']).'" type="text" value="'.esc_attr($instance[$val['name']]).'" />';
  			echo '</p>';
			} else if ($val['type']=='checkbox') {
  			echo '<p>
  				      <label for="'.$this->get_field_id($val['name']).'">'.__($val['label'], 'categorized-tag-cloud' ).'</label> 
  				   ';
				$checked = ($instance[$val['name']]) ? 'checked="checked"' : '';
				echo '<input id="'.$this->get_field_id($val['name']).'" name="'.$this->get_field_name($val['name']).'" type="checkbox" '.$checked.' />';
  			echo '</p>';
			}
		}


    }

} // class CategorizedTagCloudWidget

// register CategorizedTagCloudWidget widget
add_action('widgets_init', create_function('', 'return register_widget("CategorizedTagCloudWidget");'));
