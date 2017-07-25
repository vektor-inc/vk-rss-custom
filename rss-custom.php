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
			'layout'     => 'layout_a',
		);
		return wp_parse_args((array)$instance, $defaults);
	}

	function form( $instance ){
		$instance = $this->standardization( $instance );

		?>
<Label for="<?php echo $this->get_field_id('label'); ?>">■ <?php _e( 'Heading title', 'biz-vektor' ) ?></label><br/>
<input type="text" id="<?php echo $this->get_field_id('label'); ?>-title" name="<?php echo $this->get_field_name('label'); ?>" value="<?php echo $instance['label']; ?>" />
<br/>
<Label for="<?php echo $this->get_field_id('url'); ?>">■ URL</label><br/>
<input type="text" id="<?php echo $this->get_field_id('url'); ?>" name="<?php echo $this->get_field_name('url'); ?>" value="<?php echo $instance['url']; ?>" />
<p></p>
<p>外部ブログなどにRSS機能がある場合、RSSのURLを入力することにより一覧を表示することができます。</p>
<p>URLの先がRSSでなかったりと正しくない場合は何も表示されません。<br/>
RSSページの接続が遅い場合はウィジェットの表示速度もそのまま遅くなるのでURLの設定には注意を払う必要があります。</p>
<Label for="<?php echo $this->get_field_id('layout'); ?>">■ 表示箇所/要素</label><br/>
<label><input type="radio" name="<?php echo $this->get_field_name('layout'); ?>" value="" <?php echo ($instance['layout'] != 'layout_b')? 'checked' : ''; ?> > コンテンツエリア <br>
　（画像/タイトル/日付/抜粋/続きを読む）</label><br>
<label><input type="radio" name="<?php echo $this->get_field_name('layout'); ?>" value="layout_b" <?php echo ($instance['layout'] == 'layout_b')? 'checked' : ''; ?> > サイドバー<br>
　（画像/タイトル）</label>
<p>表示件数はRSS配信先のWordPressの「設定 > 表示設定」より設定してください。</p>
		<?php
	}

	function update( $new_instance, $old_instance ){
		$instance = $old_instance;
		$instance['url'] = $new_instance['url'];
		$instance['label'] = $new_instance['label'];
		$instance['layout'] = $new_instance['layout'];
		return $instance;
	}

	function widget($args, $instance){
		$instance = $this->standardization( $instance );
		if( preg_match('/^http.*$/',$instance['url']) || preg_match('/^https.*$/',$instance['url']) ){

			if( ! function_exists( 'wp_safe_remote_get' ) ) return;

			$blogRss = ( $instance['url'] ) ? $instance['url'] : '';

			// if ( $blogRss ) {

				$titlelabel = 'ブログエントリー';
				if ( $instance['label'] ){ 
					$titlelabel = $instance['label']; 
				} elseif ( $blogRss['rssLabelName'] ){ 
					$titlelabel = $instance['rssLabelName']; 
				}

				$content = wp_safe_remote_get( $blogRss );
				if( $content['response']['code'] != 200 ) return;

				$xml = @simplexml_load_string( $content['body'] );

				if( empty( $xml ) ) return;

				echo $args['before_widget'];
				if ( isset( $instance['layout'] ) && $instance['layout'] == 'layout_b'){
					
					echo $args['before_title'] . $titlelabel .$args['after_title'];
					echo '<div class="ttBoxSection">';
					$this->layout_b( $xml );
					echo '</div>';
				} else {
					echo '<div id="rss_widget">';
					$this->layout_a( $instance, $titlelabel, $xml );
					echo '</div>';
						
				}
				echo $args['after_widget'];

			// } // if ( $blogRss ) {
		}
	}

	function layout_b($xml){
		if ( $xml->channel->item ){
			$date_format = get_option( 'date_format' );
			foreach( $xml->channel->item as $entry ){
				$entrydate = date ( $date_format,strtotime ( $entry->pubDate ) );
				?>
				<div class="ttBox">
				<?php if ( isset( $entry->thumbnailUrl ) && $entry->thumbnailUrl ) : ?>
					<div class="ttBoxTxt ttBoxRight"><a href="<?php echo esc_url( $entry->link ); ?>"><?php echo strip_tags( $entry->title ); ?></a></div>
					<div class="ttBoxThumb ttBoxLeft">
						<a href="<?php echo esc_url( $entry->link ); ?>">
							<img src="<?php echo $entry->thumbnailUrl; ?>" alt="<?php echo esc_html( $entry->title ); ?>" />
						</a>
					</div>
				<?php else : ?>
					<div>
						<a href="<?php echo esc_url( $entry->link ); ?>"><?php echo strip_tags( $entry->title ); ?></a>
					</div>
				<?php endif; ?>
				</div>

			<?php
			}
		} // if ( $xml->channel->item ){
		echo '</div>';
	}

	function layout_a( $option = array('url'=>null,'label'=>null), $titlelabel = '', $xml = '' )	{
	?>
		<div id="topBlog" class="infoList">
		<h2><?php echo esc_html( $titlelabel ); ?></h2>
		<div class="rssBtn"><a href="<?php echo esc_url($option['url']) ?>" id="blogRss" target="_blank">RSS</a></div>
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
						<div class="moreLink"><a href="<?php echo esc_url( $entry->link ); ?>" target="_blank"><?php _e('Read more', 'biz-vektor'); ?></a></div>
						</div><!-- [ /.entryTxtBox ] -->
						
						<?php if ( $entry->thumbnailUrl ) { ?>
							<div class="thumbImage ttBoxThumb">
							<div class="thumbImageInner">
							<a href="<?php echo esc_url( $entry->link ); ?>" target="_blank"><img src="<?php echo $entry->thumbnailUrl; ?>" alt="<?php echo esc_html( $entry->title ); ?>" /></a>
							</div>
							</div><!-- [ /.thumbImage ] -->
						<?php } //  if ( $entry->thumbnailUrl ) { ?>	
					</div><!-- [ /.infoListBox ] -->
				<?php
				}
			?>
		</div><!-- [ /#topBlog ] -->
	<?php
		}
	}
}
