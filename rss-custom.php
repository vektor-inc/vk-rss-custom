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

$data = get_file_data(
	__FILE__, array(
		'version'    => 'Version',
		'textdomain' => 'Text Domain',
	)
);
define( 'VK_RSS_CUSTOM_VERSION', $data['version'] );
define( 'VK_RSS_CUSTOM_BASENAME', plugin_basename( __FILE__ ) );
define( 'VK_RSS_CUSTOM_URL', plugin_dir_url( __FILE__ ) );
define( 'VK_RSS_CUSTOM_DIR', plugin_dir_path( __FILE__ ) );

// require_once( VK_RSS_CUSTOM_DIR . 'class.pluginname-common.php' );
/*-------------------------------------------*/
/*  Load plugin_name css
/*-------------------------------------------*/

remove_filter( 'do_feed_rss2', 'do_feed_rss2', 10 );
function custom_feed_rss2() {
	// break();
	$template_file = '/feed-rss2.php';
	load_template( VK_RSS_CUSTOM_DIR . $template_file );
}
add_action( 'do_feed_rss2', 'custom_feed_rss2', 10 );

add_action( 'widgets_init', 'vrc_register_widgets' );
function vrc_register_widgets() {
	if ( function_exists( 'wp_safe_remote_get' ) ) {
		register_widget( 'vrc_widget_rss' );
	}
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

	function standardization( $instance = array() ) {
		$defaults = array(
			'url'    => 'https://bizvektor.com/feed/?post_type=info',
			'label'  => 'BizVektorからのお知らせ',
			'layout' => 'layout_a',
			'count'  => '',
		);
		return wp_parse_args( (array) $instance, $defaults );
	}

	function form( $instance ) {
		$instance = $this->standardization( $instance );

		?>
<br>
<Label for="<?php echo $this->get_field_id( 'label' ); ?>">■ <?php _e( 'Heading title', 'biz-vektor' ); ?></label><br/>
<input type="text" id="<?php echo $this->get_field_id( 'label' ); ?>-title" name="<?php echo $this->get_field_name( 'label' ); ?>" value="<?php echo $instance['label']; ?>" />
<br>
<br>
<Label for="<?php echo $this->get_field_id( 'url' ); ?>">■ URL</label><br/>
<input type="text" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" value="<?php echo $instance['url']; ?>" />
<p></p>
<p>外部ブログなどにRSS機能がある場合、RSSのURLを入力することにより一覧を表示することができます。</p>
<p>URLの先がRSSでなかったりと正しくない場合は何も表示されません。<br/>
RSSページの接続が遅い場合はウィジェットの表示速度もそのまま遅くなるのでURLの設定には注意を払う必要があります。</p>



<Label for="<?php echo $this->get_field_id( 'layout' ); ?>">■ 表示箇所/要素</label><br/>
<label><input type="radio" name="<?php echo $this->get_field_name( 'layout' ); ?>" value="" <?php echo ( $instance['layout'] != 'layout_b' ) ? 'checked' : ''; ?> > コンテンツエリア <br>
　（画像/タイトル/日付/抜粋/続きを読む）</label><br>
<label><input type="radio" name="<?php echo $this->get_field_name( 'layout' ); ?>" value="layout_b" <?php echo ( $instance['layout'] == 'layout_b' ) ? 'checked' : ''; ?> > サイドバー<br>
　（画像/タイトル）</label>
<br/>
<br>
<Label for="<?php echo $this->get_field_id( 'count' ); ?>">■ 表示件数</label><br/>
<input type="text" id="<?php echo $this->get_field_id( 'count' ); ?>-title" name="<?php echo $this->get_field_name( 'count' ); ?>" value="<?php echo $instance['count']; ?>" />
<br/>


<p>表示件数が未入力の場合はRSS配信先のWordPressの「設定 > 表示設定」で指定した数が表示されます。<br>
「設定 > 表示設定」で指定した数よりも多い数を入力しても表示されません。</p>
		<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance           = $old_instance;
		$instance['url']    = $new_instance['url'];
		$instance['label']  = $new_instance['label'];
		$instance['layout'] = $new_instance['layout'];
		$instance['count']  = $new_instance['count'];
		return $instance;
	}

	function widget( $args, $instance ) {
		$instance = $this->standardization( $instance );
		if ( preg_match( '/^http.*$/', $instance['url'] ) || preg_match( '/^https.*$/', $instance['url'] ) ) {

			if ( ! function_exists( 'wp_safe_remote_get' ) ) {
				return;
			}

			$blogRss = ( $instance['url'] ) ? $instance['url'] : '';

			// if ( $blogRss ) {

				$titlelabel = 'ブログエントリー';
			if ( $instance['label'] ) {
				$titlelabel = $instance['label'];
			} elseif ( $blogRss['rssLabelName'] ) {
				$titlelabel = $instance['rssLabelName'];
			}

				$content = wp_safe_remote_get( $blogRss );
			if ( $content['response']['code'] != 200 ) {
				return;
			}

				$xml = @simplexml_load_string( $content['body'] );

			if ( empty( $xml ) ) {
				return;
			}

				// 全角数字を半角に変換 + 文字列から数値に変換
				$max_count = intval( mb_convert_kana( $instance['count'], 'a' ) );

				echo $args['before_widget'];
			if ( isset( $instance['layout'] ) && $instance['layout'] == 'layout_b' ) {

				echo $args['before_title'] . $titlelabel . $args['after_title'];
				echo '<div class="ttBoxSection">';
				$this->layout_b( $instance, $xml, $max_count );
				echo '</div>';
			} else {
				echo '<div id="rss_widget">';
				$this->layout_a( $instance, $titlelabel, $xml, $max_count );
				echo '</div>';

			}
				echo $args['after_widget'];

			// } // if ( $blogRss ) {
		}
	}

	function layout_b( $instance, $xml, $max_count ) {
		if ( $xml->channel->item ) {
			$date_format = get_option( 'date_format' );
			$count       = 0;
			foreach ( $xml->channel->item as $entry ) {
				$entrydate = date( $date_format, strtotime( $entry->pubDate ) );
				/* 画像URLがオブジェクトで返ってくるためfilter_varでチェックし、空の場合は値が '' になるようにする */
				if ( filter_var( $entry->thumbnailUrl ) ) {
					$thumbnailUrl = esc_url( $entry->thumbnailUrl );
				} else {
					$thumbnailUrl = '';
				}
				?>
				<div class="ttBox">
				<?php if ( isset( $thumbnailUrl ) && $thumbnailUrl ) : ?>
				<?php // 画像がある時 ?>
					<div class="ttBoxTxt ttBoxRight"><a href="<?php echo esc_url( $entry->link ); ?>" target="_blank"><?php echo strip_tags( $entry->title ); ?></a></div>
					<div class="ttBoxThumb ttBoxLeft">
						<a href="<?php echo esc_url( $entry->link ); ?>" target="_blank">
							<img src="<?php echo $thumbnailUrl; ?>" alt="<?php echo esc_html( $entry->title ); ?>" />
						</a>
					</div>
				<?php else : ?>
				<?php // 画像がない時 ?>
					<div>
						<a href="<?php echo esc_url( $entry->link ); ?>" target="_blank"><?php echo strip_tags( $entry->title ); ?></a>
					</div>
				<?php endif; ?>
				</div>

			<?php
			$count++;
			// 数字が入っていてカウントと現在の表示件数と同じになったらループ処理を中断する
			if ( $max_count && $max_count <= $count ) {
				break;
			}
			}
		} // if ( $xml->channel->item ){
	}

	function layout_a( $instance = array(
		'url'   => null,
		'label' => null,
	), $titlelabel = '', $xml = '', $max_count ) {
	?>
		<div id="topBlog" class="infoList">
		<h2><?php echo esc_html( $titlelabel ); ?></h2>
		<div class="rssBtn"><a href="<?php echo esc_url( $instance['url'] ); ?>" id="blogRss" target="_blank">RSS</a></div>
			<?php
			if ( $xml->channel->item ) {
				$date_format = get_option( 'date_format' );
				$count       = 0;
				foreach ( $xml->channel->item as $entry ) {
					$entrydate = date( $date_format, strtotime( $entry->pubDate ) );
					/* 画像URLがオブジェクトで返ってくるためfilter_varでチェックし、空の場合は値が '' になるようにする */
					if ( filter_var( $entry->thumbnailUrl ) ) {
						$thumbnailUrl = esc_url( $entry->thumbnailUrl );
					} else {
						$thumbnailUrl = '';
					}
					?>
					<!-- [ .infoListBox ] -->
					<div class="infoListBox ttBox">
						<div class="entryTxtBox
						<?php
						if ( $thumbnailUrl ) {
							echo ' ttBoxTxt haveThumbnail';
						}
?>
">
						<h4 class="entryTitle">
						<a href="<?php echo esc_url( $entry->link ); ?>" target="_blank"><?php echo esc_html( $entry->title ); ?></a>
						</h4>
						<p class="entryMeta">
						<span class="infoDate"><?php echo esc_html( $entrydate ); ?></span><span class="infoCate"><?php echo $entry->taxCatList; ?></span>
						</p>
						<?php echo $entry->description; ?>
						<div class="moreLink"><a href="<?php echo esc_url( $entry->link ); ?>" target="_blank"><?php _e( 'Read more', 'biz-vektor' ); ?></a></div>
						</div><!-- [ /.entryTxtBox ] -->

						<?php if ( $thumbnailUrl ) { ?>
							<div class="thumbImage ttBoxThumb">
							<div class="thumbImageInner">
							<a href="<?php echo esc_url( $entry->link ); ?>" target="_blank"><img src="<?php echo $thumbnailUrl; ?>" alt="<?php echo esc_html( $entry->title ); ?>" /></a>
							</div>
							</div><!-- [ /.thumbImage ] -->
						<?php } //  if ( $thumbnailUrl ) { ?>
					</div><!-- [ /.infoListBox ] -->
					<?php
					$count++;
					// 数字が入っていてカウントと現在の表示件数と同じになったらループ処理を中断する
					if ( $max_count && $max_count <= $count ) {
						break;
					}
				} // foreach( $xml->channel->item as $entry ){
			?>
		</div><!-- [ /#topBlog ] -->
	<?php
			}
	}
}
