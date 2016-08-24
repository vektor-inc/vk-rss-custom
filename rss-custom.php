<?php
/**
 * Plugin Name: VK RSS Custom
 * Plugin URI:
 * Version: 0.0.1
 * Author: Vektor,Inc.
 * Author URI: http://www.vektor-inc.co.jp
 * Description:
 * Text Domain: rss-custom
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

$data = get_file_data( __FILE__, array( 'version' => 'Version','textdomain' => 'Text Domain' ) );
define( 'VK_RSS_CUSTOM_VERSION', $data['version'] );
define( 'VK_RSS_CUSTOM_BASENAME', plugin_basename( __FILE__ ) );
define( 'VK_RSS_CUSTOM_URL', plugin_dir_url( __FILE__ ) );
define( 'VK_RSS_CUSTOM_DIR', plugin_dir_path( __FILE__ ) );

// require_once( VK_RSS_CUSTOM_DIR . 'class.pluginname-common.php' );
/*-------------------------------------------*/
/*  Load plugin_name css
/*-------------------------------------------*/

remove_filter('do_feed_rss2', 'do_feed_rss2', 10);
function custom_feed_rss2(){
	// break();
	$template_file = '/feed-rss2.php';
	load_template( VK_RSS_CUSTOM_DIR . $template_file);
}
add_action('do_feed_rss2', 'custom_feed_rss2', 10);

add_action( 'widgets_init', 'vrc_register_widgets' );
function vrc_register_widgets(){
    if( function_exists( 'wp_safe_remote_get' ) )
	    register_widget("vrc_widget_rss");
}

/*-------------------------------------------*/
/*	RSS widget
/*-------------------------------------------*/
class vrc_widget_rss extends WP_Widget {

	function __construct() {

		$widget_name = 'VK ' . __( 'RSS Entries For Top', 'biz-vektor' );

		parent::__construct(
			'vk_rsswidget',
			$widget_name,
			array( 'description' => __( 'Displays entries list from a RSS feed link.', 'biz-vektor' ) )
		);
	}

	function standardization( $instance=array() ) {
		$defaults = array(
			'url'       => 'https://bizvektor.com/feed/?post_type=info',
			'label'     => 'BizVektorからのお知らせ',
		);
		return wp_parse_args((array)$instance, $defaults);
	}

	function widget($args, $instance){
		$instance = $this->standardization( $instance );
		if( preg_match('/^http.*$/',$instance['url']) || preg_match('/^https.*$/',$instance['url']) ){
			echo '<div id="rss_widget">';
			vrc_post_list($instance);
			echo '</div>';
		}
	}

	function form( $instance ){
		$instance = $this->standardization( $instance );

		?>
<Label for="<?php echo $this->get_field_id('label'); ?>"><?php _e( 'Heading title', 'biz-vektor' ) ?></label><br/>
<input type="text" id="<?php echo $this->get_field_id('label'); ?>-title" name="<?php echo $this->get_field_name('label'); ?>" value="<?php echo $instance['label']; ?>" />
<br/>
<Label for="<?php echo $this->get_field_id('url'); ?>">URL</label><br/>
<input type="text" id="<?php echo $this->get_field_id('url'); ?>" name="<?php echo $this->get_field_name('url'); ?>" value="<?php echo $instance['url']; ?>" />
<p></p>
<p>外部ブログなどにRSS機能がある場合、RSSのURLを入力することにより一覧を表示することができます。</p>
<p>URLの先がRSSでなかったりと正しくない場合は何も表示されません。<br/>
RSSページの接続が遅い場合はウィジェットの表示速度もそのまま遅くなるのでURLの設定には注意を払う必要があります。</p>
<p>※ コンテンツエリア（トップページ）への設置推奨</p>
		<?php
	}

	function update( $new_instance, $old_instance ){
		$instance = $old_instance;
		$instance['url'] = $new_instance['url'];
		$instance['label'] = $new_instance['label'];
		return $instance;
	}
}
/*-------------------------------------------*/
/*	Home page _ blogList（RSS）
/*-------------------------------------------*/
function vrc_post_list( $option = array('url'=>null,'label'=>null) )	{

	if( ! function_exists( 'wp_safe_remote_get' ) ) return;

	$blogRss = ( $option['url'] ) ? $option['url'] : '';

	if ( $blogRss ) {
		$titlelabel = 'ブログエントリー';
		if ( $option['label'] ){ $titlelabel = $option['label']; }
		elseif ( $blogRss['rssLabelName'] ){ $titlelabel = $option['rssLabelName']; }

		$content = wp_safe_remote_get( $blogRss );
		if( $content['response']['code'] != 200 ) return;

		$xml = @simplexml_load_string( $content['body'] );
		if( empty( $xml ) ) return;
?>
	<div id="topBlog" class="infoList">
	<h2><?php echo esc_html( $titlelabel ); ?></h2>
	<div class="rssBtn"><a href="<?php echo $blogRss ?>" id="blogRss" target="_blank">RSS</a></div>
		<?php
		if ($xml->channel->item){
			$date_format = get_option( 'date_format' );
			foreach( $xml->channel->item as $entry ){
				$entrydate = date ( $date_format,strtotime ( $entry->pubDate ) );
				?>
				<!-- [ .infoListBox ] -->
				<div class="infoListBox ttBox">
					<div class="entryTxtBox<?php if ( $entry->thumbnailUrl ) echo ' ttBoxTxt haveThumbnail'; ?>">
					<h4 class="entryTitle">
					<a href="<?php echo esc_url( $entry->link ); ?>" target="_blank"><?php echo esc_html( $entry->title ); ?></a>
					</h4>
					<p class="entryMeta">
					<span class="infoDate"><?php echo esc_html( $entrydate ); ?></span><span class="infoCate"><?php echo $entry->taxCatList; ?></span>
					</p>
					<?php echo $entry->description; ?>
					<div class="moreLink"><a href="<?php $entry->link; ?>" target="_blank"><?php _e('Read more', 'biz-vektor'); ?></a></div>
					</div><!-- [ /.entryTxtBox ] -->
					
					<?php if ( $entry->thumbnailUrl ) { ?>
						<div class="thumbImage ttBoxThumb">
						<div class="thumbImageInner">
						<a href="<?php echo esc_url( $entry->link ); ?>" target="_blank"><img src="<?php echo $entry->thumbnailUrl; ?>" alt="<?php echo esc_html( $entry->title ); ?>" /></a>
						</div>
						</div><!-- [ /.thumbImage ] -->
					<?php } ?>	
				</div><!-- [ /.infoListBox ] -->
			<?php
			}
		}
		?>
	</div><!-- [ /#topBlog ] -->
<?php
	}
}