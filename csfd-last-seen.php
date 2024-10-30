<?php
/*
Plugin Name: ČSFD Last Seen
Plugin URI: http://wordpress.org/extend/plugins/csfd-last-seen
Description: Adds a widget, which shows the last X movies rated on CSFD.cz (Czech-Slovak movie database).
Version: 1.9.2
Author: Josef Štěpánek
Author URI: https://josefstepanek.cz/kontakt


Copyright 2009	Josef Štěpánek	(email : josef.stepanek@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA	 02110-1301	 USA
*/


// Default settings
$instance_default = array(
	'title' => __('Naposledy jsem viděl', 'csfd-last-seen'),
	'rating_url' => 'https://www.csfd.cz/uzivatel/29153-joste/',
	'rating_num' => 10,
	'update_int' => 2,
	'display_color' => true,
	'display_year' => true,
	'rel_nofollow' => false,
	'csfd_data' => '<p>'.__('Probíhá první nastavování pluginu. Zkuste aktualizovat stránku.', 'csfd-last-seen').'</p>',
	'id' => '__i__'
);


class csfdLastSeen extends WP_Widget {


	public function __construct() {
		$widget_ops = array('description' => __( 'Widget, který zobrazuje vaše poslední ohodnocené filmy na CSFD.cz', 'csfd-last-seen' ) );
		$control_ops = array('width' => 300);
		parent::__construct('csfdLastSeen', __('ČSFD Last Seen', 'csfd-last-seen'), $widget_ops, $control_ops);
	}


	public function widget($args, $instance) {
		extract($args,EXTR_SKIP);

		$data = get_transient( $instance['id'] );

		// Update data from CSFD.cz
		if( $data === false ) {

			// Fix user defined profile URL
			$url = $instance['rating_url'].'/hodnoceni/';
			$url = str_replace('//hodnoceni','/hodnoceni',$url);
			$url = str_replace('//','//www.',str_replace('//www.','//',$url));
			$url = str_replace('http:','https:',$url);

			$ch = curl_init();

			if($ch) {

				$timeout = 5;
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				$csfd_data = curl_exec($ch);
				curl_close($ch);

				// data parsing
				$csfd_data_start = strpos( $csfd_data, '<tbody>' );
				$csfd_data_end = strpos( $csfd_data, '</tbody>' );
				$csfd_data = substr( $csfd_data, $csfd_data_start, $csfd_data_end - $csfd_data_start );
				$csfd_data = str_replace( 'href="/', 'href="//csfd.cz/', $csfd_data );
				$csfd_data = str_replace( '	 ','', $csfd_data );
				$csfd_data = preg_replace( "/\r|\n/", "", $csfd_data );
				$csfd_data = str_replace( '<h3 class="film-title-nooverflow">','', $csfd_data );
				$csfd_data = str_replace( '</h3>','', $csfd_data );
				$csfd_data = explode("</tr>", $csfd_data); // ratings list to array
				array_pop($csfd_data); // remove last empty element

				for($i=0;$i<count($csfd_data);$i++) {
					if ($csfd_data[$i] == '') continue;
					if ($i>$instance['rating_num']) break;
					$csfd_data_parsed .= $csfd_data[$i];
				}

				// clean html code a little bit
				$csfd_data_parsed .= "<style type=\"text/css\">@font-face { font-family: 'csfdicons'; src: url('data:font/woff2;charset=utf-8;base64,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') format('woff2'); font-weight: normal; font-style: normal; } table#csfd, table#csfd td { border: none; border-collapse: collapse; } table#csfd h3 { font-weight: normal; line-height: 1; margin: 0; padding: 0; } table#csfd .name span { font-size: initial; } table#csfd .film-title-info { opacity: 0.6; } table#csfd .film-title-info .info + .info".( $instance['display_year'] == false ? ", table#csfd .film-title-info .info" : "")." { display: none; } table td.name .icon-rounded-square { display:inline-block; width:10px; height:10px; margin-right:6px; } .icon-rounded-square.red, .awards-explanation .icon-dot.red { color: #ba0305; background: #ba0305; } .icon-rounded-square.blue, .awards-explanation .icon-dot.blue { color:#7585ac; background:#7585ac; } .icon-rounded-square.grey, .awards-explanation .icon-dot.grey { color:#494949; background:#494949; } .icon-rounded-square.lightgrey, .awards-explanation .icon-dot.lightgrey { color:#c9c9c9; background:#c9c9c9; } .stars-rating{display:block;height:30px;font-size:0;line-height:0;font-weight:700;white-space:nowrap}.stars-rating .star{display:inline-block;position:relative;height:30px;font-size:16px;line-height:30px;cursor:pointer}.stars-rating .star.computed:after{color:#393939}.stars-rating .star:after,.stars-rating .star:before{content:'\\e001';display:inline-block;width:24px;height:30px;font-family:'csfdicons';font-size:22px;text-align:center;vertical-align:top}.stars-rating .star:before{color:#d2d2d2}.stars-rating .star:after{position:absolute;top:0;left:0;opacity:0;color:#ba0305;transition:color 150ms ease;overflow:hidden}.stars-rating .star.active:after,.stars-rating .star.hover-on:after{opacity:1}.stars-rating .star.active.hover-off:after{opacity:0}.stars-rating .star-0{margin:0}.stars-rating .star-0:after,.stars-rating .star-0:before{content:'\\e011';padding:0;width:24px;height:30px;font-size:16px;line-height:32px;text-align:left}.stars-rating:not([disabled]) .star:hover:after{opacity:1}.star-rating{display:inline-block}.star-rating .stars{font-size:11px;line-height:18px;display:inline-block;white-space:nowrap;vertical-align:top}.star-rating .stars:after,.star-rating .stars:before{font-family:'csfdicons'}.star-rating .stars:before{color:#ba0305}.star-rating .stars:after{color:#aaa;opacity:0.45}.star-rating .stars.stars-5:before{content:'\\e001 \\e001 \\e001 \\e001 \\e001'}.star-rating .stars.stars-4:after{content:'\\e001'}.star-rating .stars.stars-4:before{content:'\\e001 \\e001 \\e001 \\e001'}.star-rating .stars.stars-3:after{content:'\\e001 \\e001'}.star-rating .stars.stars-3:before{content:'\\e001 \\e001 \\e001'}.star-rating .stars.stars-2:after{content:'\\e001 \\e001 \\e001'}.star-rating .stars.stars-2:before{content:'\\e001 \\e001'}.star-rating .stars.stars-1:after{content:'\\e001 \\e001 \\e001 \\e001'}.star-rating .stars.stars-1:before{content:'\\e001'}.star-rating .stars.stars-0:after{content:'\\e001 \\e001 \\e001 \\e001 \\e001'}.star-rating .trash{padding-left:4px;white-space:nowrap;font-weight:600;color:#000}.star-rating .trash:before{content:'\\e011';margin:0 4px 0 0;color:#000}.star-rating.computed .stars{color:#393939}.star-rating.computed .stars:before{color:#393939}.star-rating.computed .trash{color:#535353}.star-rating.computed .trash:before{color:#535353}.star-rating.star-vote{max-width:80px;overflow:hidden}.star-rating.star-vote .stars{cursor:pointer;float:right}.star-rating.star-vote .stars:after{content:'\\e001'}.star-rating.star-vote .stars.on:after,.star-rating.star-vote .stars:hover:after,.star-rating.star-vote .stars:hover~.stars:after{color:#ba0305}table .star-rating .trash{padding-left:0}</style>";
				$csfd_data_parsed = str_replace('<a', '<a target="_blank" title="'.__('Detail na ČSFD (v novém okně)', 'csfd-last-seen').'"', $csfd_data_parsed);
				$csfd_data_parsed = preg_replace("/<td>.{10}<\/td>/", "", $csfd_data_parsed); // remove rating date
				$csfd_data_parsed = '<table id="csfd" data-last-update="'.date('j. n. Y, G:i:s').'" data-source="'.$url.'">'.$csfd_data_parsed.'</table>';

				// parsing according to options
				if( $instance['display_color'] == false ) {
					$csfd_data_parsed = preg_replace("/ class=\"icon icon-rounded-square.{3,9}\"/", '', $csfd_data_parsed);
				}

				$csfd_data_parsed = preg_replace("/<td class=\"date\-only\">.{6,17}<\/td>/", '</tr>', $csfd_data_parsed);

				if( $instance['rel_nofollow'] == true ) {
					$csfd_data_parsed = str_replace('<a', '<a rel="nofollow"', $csfd_data_parsed);
				}

				$url = $instance['rating_url'].'/hodnoceni';
				$url = str_replace('//hodnoceni','/hodnoceni/',$url);
				$csfd_data_parsed .= '<p><a href="'.$url.'" target="_blank" rel="nofollow" title="'.__('Zobrazit starší hodnocení na CSFD.cz (v novém okně)', 'csfd-last-seen').'">'.__('Starší hodnocení na ČSFD »', 'csfd-last-seen').'</a></p>';
				$csfd_data_parsed .= '<!-- CSFD Last Seen WordPress Plugin by JosefStepanek.cz -->';
				$csfd_data_parsed .= '<!-- Last refresh: '.date('j. n. Y, G:i:s').' z '.$url.' -->';
				$data = $csfd_data_parsed;

			}

			set_transient( $instance['id'], $data, $instance['update_int']*60*60 );
		}

		echo $before_widget;
		echo $before_title . $instance['title'] . $after_title;
		echo $data;
		echo $after_widget;
	}


	public function update($new_instance, $old_instance) {

		global $instance_default;
		if( !isset($new_instance['title']) ) {
			return false;
		}

		$instance = $old_instance;
		$instance['title'] = wp_specialchars( $new_instance['title'] );
		$instance['rating_url'] = wp_specialchars( $new_instance['rating_url'] );
		$instance['rating_num'] = wp_specialchars( $new_instance['rating_num'] );
		$instance['update_int'] = wp_specialchars( $new_instance['update_int'] );
		$instance['display_color'] = (isset($new_instance['display_color']) ? true : false);
		$instance['display_year'] = (isset($new_instance['display_year']) ? true : false);
		$instance['rel_nofollow'] = (isset($new_instance['rel_nofollow']) ? true : false);
		$instance['csfd_data'] = $instance['csfd_data'];

		$instance['id'] = $this->id;
		delete_transient( $this->id );

		foreach($instance as $opt_name => &$value) { // Set default values to empty options
			if( $value==='' ) {
				$value = $instance_default[$opt_name];
			}
		}

		return $instance;
	}


	public function form($instance) {

		global $instance_default;

		if(!isset($instance['title'])) {
			$instance = $instance_default;
		}

		?>
		<p><label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Nadpis:', 'csfd-last-seen'); ?></label>
		<input class="widefat" type="text" id="<?php echo $this->get_field_id('title') ?>" name="<?php echo $this->get_field_name('title') ?>" value="<?php echo htmlspecialchars($instance['title'],ENT_QUOTES) ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('rating_url') ?>"><?php _e('Adresa profilu na CSFD.cz:', 'csfd-last-seen'); ?></label>
		<input class="widefat" type="text" id="<?php echo $this->get_field_id('rating_url') ?>" name="<?php echo $this->get_field_name('rating_url') ?>" value="<?php echo htmlspecialchars($instance['rating_url'],ENT_QUOTES) ?>" />
		<br /><small class="setting-description"><em>Např. https://csfd.cz/uzivatel/29153-joste/</em></small></p>

		<p><label for="<?php echo $this->get_field_id('rating_num') ?>"><?php _e('Počet zobrazených filmů: ', 'csfd-last-seen'); ?></label>
		<input size="3" type="text" id="<?php echo $this->get_field_id('rating_num') ?>" name="<?php echo $this->get_field_name('rating_num') ?>" value="<?php echo htmlspecialchars($instance['rating_num'],ENT_QUOTES) ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('update_int') ?>"><?php _e('Aktualizovat po ', 'csfd-last-seen'); ?>
		<input size="3" type="text" id="<?php echo $this->get_field_id('update_int') ?>" name="<?php echo $this->get_field_name('update_int') ?>" value="<?php echo htmlspecialchars($instance['update_int'],ENT_QUOTES) ?>" /> hod.</label></p>

		<p><input type="checkbox" id="<?php echo $this->get_field_id('display_color') ?>" name="<?php echo $this->get_field_name('display_color') ?>"<?php echo ($instance['display_color']==true ? ' checked value="true"' : ' value="false"') ?> />
		<label for="<?php echo $this->get_field_id('display_color') ?>"><?php _e('Zobrazovat barvu filmu', 'csfd-last-seen'); ?></label>
		<br />
		<input type="checkbox" id="<?php echo $this->get_field_id('display_year') ?>" name="<?php echo $this->get_field_name('display_year') ?>"<?php echo ($instance['display_year']==true ? ' checked value="true"' : ' value="false"') ?> />
		<label for="<?php echo $this->get_field_id('display_year') ?>"><?php _e('Zobrazovat rok vydání filmu', 'csfd-last-seen'); ?></label>
		<br />
		<input type="checkbox" id="<?php echo $this->get_field_id('rel_nofollow') ?>" name="<?php echo $this->get_field_name('rel_nofollow') ?>"<?php echo ($instance['rel_nofollow']==true ? ' checked value="true"' : ' value="false"') ?> />
		<label for="<?php echo $this->get_field_id('rel_nofollow') ?>"><?php _e('Přidat odkazům <code title="Nastavení pro SEO">rel="nofollow"</code>', 'csfd-last-seen'); ?></label></p>

		<input type="hidden" id="<?php echo $this->get_field_id('submit') ?>" name="<?php echo $this->get_field_name('submit') ?>" value="1" />
		<?php
	}


}


function csfdLastSeen_init() {
	register_widget('csfdLastSeen');
}
add_action('widgets_init', 'csfdLastSeen_init');



?>