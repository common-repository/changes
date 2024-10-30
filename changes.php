<?php
/*
Plugin Name: Changes
Plugin URI: http://www.freeplugin.org/changes-wordpress-plugin.html
Description: Checks your posts against the original content, providing the percentage of changes.
Version: 1.0.3
Author: seoroma
Author URI: http://www.freeplugin.org
 */

//Set constants
$plugin_url = plugin_dir_url( __FILE__ );


add_action('admin_init', 'changes_admin_init');

/* Register settings */
function changes_admin_init() {
	register_setting( 'changes_options', 'changes' );
}

add_action('admin_menu', 'changes_admin_menu');

function changes_admin_menu() {
	add_options_page('Changes', 'Changes', 'manage_options', __FILE__, 'changes_admin_settings_form');
}


// Loads JS to posts screen
add_action( 'load-post.php',     'changes_load_js' );
add_action( 'load-post-new.php', 'changes_load_js' );
 
// Enqueues the needed javascripts
function changes_load_js() {
	global $plugin_url;
	//Post id
	if ( isset( $_GET['post'] ) ) {
		$post_id = intval( $_GET['post'] );
	}
	
	//Enqueue script
	wp_enqueue_style ( 'changes_styles', $plugin_url.'inc/styles.css' );
	wp_enqueue_script( 'changes_js', $plugin_url.'inc/changes.js', array('jquery') );
	$params = array(
		'post_id' => $post_id
	);
	wp_localize_script( 'changes_js', 'changes_js_params', $params );
}

// Meta box to save original article
add_action( 'add_meta_boxes', 'changes_original_mbe' );

function changes_original_mbe() {
	//create a custom meta box
	add_meta_box( 'tbp-dupexam-original', 'Changes', 'changes_original_mbe_function', 'post', 'normal', 'high' );
}

function changes_original_mbe_function( $post ) {
	//retrieve the meta data values if they exist
	$changes_mbe_content = get_post_meta( $post->ID, '_changes_mbe_content', true );

	if ($changes_mbe_content != '' and $post->post_content != '') {
		echo '<p>';

		$content = strip_tags( $post->post_content );
	
		//Check for similarities without stop words
		similar_text($content, $changes_mbe_content, $percentMatch);
		$similar = round($percentMatch);
	?>	
		Your post is <span class="changes-<?php echo ( $similar > '70' ) ? 'bad' : 'good' ?>"><?php echo $similar; ?>%</span> similar to the original,
	<?php
		//Get data
		$changes_mbe_content_stripped = strip_stopwords($changes_mbe_content);
		$content_stripped = strip_stopwords($content);

		//Check for similarities without stop words
		similar_text($content_stripped, $changes_mbe_content_stripped, $percentMatch);
		$similar = round($percentMatch);
		?>
		<span class="changes-<?php echo ( $similar > '70' ) ? 'bad' : 'good' ?>"><?php echo $similar; ?>%</span> similar ignoring stop words.
		</p>
		<?php
	}
	?>
	<p><a id="tbp-viewchanges" href="#" class="button-highlighted">Click to view changes</a></p>
	<div id="changes-viewchanges">
	</div>
	<p>Check this box to copy content from actual post <input id="changes_copy" type="checkbox" name="changes_copy" /></p>
	<p>Original article to compare</p>
	<textarea type="text" id="changes_mbe_content" name="changes_mbe_content" style="height: 4em; width: 98%"><?php echo stripslashes( $changes_mbe_content ); ?></textarea>
	<?php
}

//hook to save the meta box data
add_action( 'save_post', 'changes_mbe_save_meta' );

function changes_mbe_save_meta( $post_id ) {

	//verify the meta data is set
	if ( isset( $_POST['changes_mbe_content'] ) ) {
		update_post_meta( $post_id, '_changes_mbe_content', strip_tags( $_POST['changes_mbe_content'] ) );
	}
}


// Ajax call back to display content difference
add_action ( 'wp_ajax_sppdupexam_diffcheck', 'changes_diffaction_callback' );
function changes_diffaction_callback() {
	$postid = intval( $_POST['post_id'] );
	
	//Get data
	$changes_mbe_content = get_post_meta( $postid, '_changes_mbe_content', true );
	$oldcontent_noshortcodes = strip_shortcodes( $changes_mbe_content );
	
	$getcontent = query_posts( 'p='.$postid );
	$content = $getcontent[0];
	$post_content = strip_tags($content->post_content, '<p><br>');
	$newcontent_noshortcodes = strip_shortcodes( $post_content );
	
	// Diff
	require ('inc/simplediff.php');	
	htmlDiff($oldcontent_noshortcodes, $newcontent_noshortcodes);
	wp_reset_query();
	die();
}


// Initialize the default set of stopwords.
function setup_stopwords() {
	if( isset($_POST['changes'])) {
		update_option('changes', $_POST['changes']);
	}
	$stopwords = array_filter(explode(',',get_option('changes')));
	if (empty($stopwords)) {
		$stopwords = "a ii about above according across 39 actually ad adj ae af after afterwards ag again against ai al all almost alone along already also although always am among amongst an and another any anyhow anyone anything anywhere ao aq ar are aren aren't around arpa as at au aw az b ba bb bd be became because become becomes becoming been before beforehand begin beginning behind being below beside besides between beyond bf bg bh bi billion bj bm bn bo both br bs bt but buy bv bw by bz c ca can can't cannot caption cc cd cf cg ch ci ck cl click cm cn co co. com copy could couldn couldn't cr cs cu cv cx cy cz d de did didn didn't dj dk dm do does doesn doesn't don don't down during dz e each ec edu ee eg eh eight eighty either else elsewhere end ending enough er es et etc even ever every everyone everything everywhere except f few fi fifty find first five fj fk fm fo for former formerly forty found four fr free from further fx g ga gb gd ge get gf gg gh gi gl gm gmt gn go gov gp gq gr gs gt gu gw gy h had has hasn hasn't have haven haven't he he'd he'll he's help hence her here here's hereafter hereby herein hereupon hers herself him himself his hk hm hn home homepage how however hr ht htm html http hu hundred i i'd i'll i'm i've i.e. id ie if il im in inc inc. indeed information instead int into io iq ir is isn isn't it it's its itself j je jm jo join jp k ke kg kh ki km kn kp kr kw ky kz l la last later latter lb lc least less let let's li like likely lk ll lr ls lt ltd lu lv ly m ma made make makes many maybe mc md me meantime meanwhile mg mh microsoft might mil million miss mk ml mm mn mo more moreover most mostly mp mq mr mrs ms msie mt mu much must mv mw mx my myself mz n na namely nc ne neither net netscape never nevertheless new next nf ng ni nine ninety nl no nobody none nonetheless noone nor not nothing now nowhere np nr nu nz o of off often om on once one one's only onto or org other others otherwise our ours ourselves out over overall own p pa page pe per perhaps pf pg ph pk pl pm pn pr pt pw py q qa r rather re recent recently reserved ring ro ru rw s sa same sb sc sd se seem seemed seeming seems seven seventy several sg sh she she'd she'll she's should shouldn shouldn't si since site six sixty sj sk sl sm sn so some somehow someone something sometime sometimes somewhere sr st still stop su such sv sy sz t taking tc td ten text tf tg test th than that that'll that's the their them themselves then thence there there'll there's thereafter thereby therefore therein thereupon these they they'd they'll they're they've thirty this those though thousand three through throughout thru thus tj tk tm tn to together too toward towards tp tr trillion tt tv tw twenty two tz u ua ug uk um under unless unlike unlikely until up upon us use used using uy uz v va vc ve very vg vi via vn vu w was wasn wasn't we we'd we'll we're we've web webpage website welcome well were weren weren't wf what what'll what's whatever when whence whenever where whereafter whereas whereby wherein whereupon wherever whether which while whither who who'd who'll who's whoever NULL whole whom whomever whose why will with within without won won't would wouldn wouldn't ws www x y ye yes yet you you'd you'll you're you've your yours yourself yourselves yt yu z za zm zr 10 z org inc width length";
		$stopwords = preg_split("~[\s,]+~", $stopwords);
		update_option('changes', implode(',', $stopwords));
	}
	return $stopwords;
}


// Remove stop words from the content
function strip_stopwords($content) {
	$stopwords = setup_stopwords();
	if (is_array($stopwords)) {
		$punctuation = array(' ', ',', '.', ';', ':', '!', '"');
		foreach ($stopwords as $word) {
			$word = trim($word);
			if ($word == '')
				continue;
			foreach ($punctuation as $p) {
				$content = str_ireplace( ' '.$word.$p, $p, $content);
				$content = str_ireplace( $p.$word.' ', $p, $content);
			}
		}
	}
	return trim($content);
}


function changes_admin_settings_form() {
	$stopwords = setup_stopwords();

	echo '
	<div class="wrap">
	<h2>'.__('Changes').' - '.__('Options').'</h2>
	<div id="poststuff" style="padding-top:10px; position:relative;">

	<div style="float:left; width:74%; padding-right:1%;">
		<form method="post" action="">
			'.settings_fields( 'changes_options' ).'
			<p>'.__('Enter a list of common words separated with a comma (leave blank to load default list)').'</p>
			<textarea rows="10" cols="80" name="changes">'.implode(',', $stopwords).'</textarea>
			<p class="submit"><input type="submit" class="button-primary" value="'.__('Save Changes').'" /></p>		
		</form>
	
	</div>

	<div style="float:right; width:25%;">
		<div class="postbox">
			<h3>News feed by PMI Servizi</h3>
			<div class="inside">'.changes_news_feed().'</div>
		</div>
	</div>

	</div>
	</div>
	';
}

function changes_news_feed () {
	$feedurl = 'http://news.pmiservizi.it/feed/';
	$select = 8;

	$rss = fetch_feed($feedurl);
	if (!is_wp_error($rss)) { // Checks that the object is created correctly
		$maxitems = $rss->get_item_quantity($select);
		$rss_items = $rss->get_items(0, $maxitems);
	}
	if (!empty($maxitems)) {
		$out .= '
			<div class="rss-widget">
				<ul>';
    foreach ($rss_items as $item) {
			$out .= '
						<li><a class="rsswidget" href="'.$item->get_permalink().'">'.$item->get_title().'</a> 
							<span class="rss-date">'.date_i18n(get_option('date_format') ,strtotime($item->get_date('j F Y'))).'</span></li>';
		}
		$out .= '
				</ul>
			</div>';
	}
	return $out;
}

