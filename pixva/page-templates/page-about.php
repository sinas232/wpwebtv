<?php
/**
 * Template Name: درباره پیکسوا
 * Template Post Type: page
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php pixva_page_hero( __( 'داستان پیکسوا: کارگاهی که با «نه» گفتن بزرگ شد', 'pixva' ), __( 'ما با گفتن «این تعمیر نمی‌صرفد» به مشتری، اعتماد ساختیم. این‌جا با عدد و عکس تصمیم می‌گیریم، نه با حدس و طمع.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">

		<div class="pixva-grid pixva-grid--2 pixva-about-intro" style="align-items:center">
			<div class="entry-content">
				<span class="pixva-badge"><?php esc_html_e( 'از ۱۳۹۲ تا امروز', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'از یک میز ۲ متری تا کارگاه بندینگ', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'پیکسوا با یک میز کوچک و یک اسیلوسکوپ دست‌دوم شروع شد؛ با یک قانون که هنوز روی دیوار کارگاه است: «اگر تعمیرش به‌صرفه نیست، همان اول بگو». همین یک جمله، ما را از تعمیرکار محله به کارگاه تخصصی پنل و برد رساند؛ جایی که امروز دستگاه بندینگ COF، میکروسکوپ صنعتی و میز تست تصویر دارد و ماهانه صدها دستگاه از تهران و شهرستان می‌پذیرد.', 'pixva' ); ?></p>
				<p><?php esc_html_e( 'افتخار ما این نیست که همه دستگاه‌ها را تعمیر می‌کنیم؛ افتخار ما این است که هیچ‌وقت دستگاهی را بیهوده باز نکرده‌ایم. هر پرونده با علت، سند و قیمت شفاف بسته می‌شود — چه با تعمیر، چه با یک «نه» صادقانه.', 'pixva' ); ?></p>
			</div>
			<img src="<?php echo esc_url( PIXVA_URI . '/assets/images/bonding-lab.jpg' ); ?>" alt="<?php esc_attr_e( 'دستگاه بندینگ پنل در کارگاه پیکسوا', 'pixva' ); ?>" width="960" height="640" style="border-radius:22px" loading="lazy">
		</div>

		<dl class="pixva-stats pixva-stats--light">
			<div class="pixva-stat"><dt><?php esc_html_e( 'سال تجربه', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( '۱۲+' ) ); ?></dd></div>
			<div class="pixva-stat"><dt><?php esc_html_e( 'دستگاه تعمیرشده', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( '۸۰۰۰+' ) ); ?></dd></div>
			<div class="pixva-stat"><dt><?php esc_html_e( 'برند تحت پوشش', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( '۲۴' ) ); ?></dd></div>
			<div class="pixva-stat"><dt><?php esc_html_e( 'گارانتی کتبی', 'pixva' ); ?></dt><dd><?php echo esc_html( pixva_fa_num( '۱۸۰ روز' ) ); ?></dd></div>
		</dl>

		<h2><?php esc_html_e( 'سه قولی که همیشه نگه می‌داریم', 'pixva' ); ?></h2>
		<div class="pixva-grid pixva-grid--3">
			<article class="pixva-card pixva-reveal">
				<span class="pixva-why-card__icon"><?php echo pixva_icon( 'search' ); ?></span>
				<h3><?php esc_html_e( 'شفافیت قبل از درآمد', 'pixva' ); ?></h3>
				<p><?php esc_html_e( 'علت خرابی را با ولتاژ و عکس نشان می‌دهیم، بعد قیمت می‌گوییم. اگر راه ارزان‌تر هست، اول همان را پیشنهاد می‌دهیم — حتی اگر سود ما کمتر شود.', 'pixva' ); ?></p>
			</article>
			<article class="pixva-card pixva-reveal">
				<span class="pixva-why-card__icon"><?php echo pixva_icon( 'cpu' ); ?></span>
				<h3><?php esc_html_e( 'تعمیر عمیق، نه تعویض کور', 'pixva' ); ?></h3>
				<p><?php esc_html_e( 'برد را در سطح قطعه عیب‌یابی می‌کنیم، نه این‌که با اولین حدس برد کامل را عوض کنیم. برای همین هزینه‌مان معمولاً زیر سقف برآورد درمی‌آید.', 'pixva' ); ?></p>
			</article>
			<article class="pixva-card pixva-reveal">
				<span class="pixva-why-card__icon"><?php echo pixva_icon( 'shield' ); ?></span>
				<h3><?php esc_html_e( 'پاسخ‌گویی بعد از تحویل', 'pixva' ); ?></h3>
				<p><?php esc_html_e( 'گارانتی ما کاغذبازی نیست؛ اگر همان ایراد در ۱۸۰ روز برگردد، بدون چون‌وچرا رایگان درستش می‌کنیم. شماره پرونده‌ات همیشه معتبر است.', 'pixva' ); ?></p>
			</article>
		</div>

		<h2 style="margin-top:2.5rem"><?php esc_html_e( 'داخل کارگاه چه خبر است؟', 'pixva' ); ?></h2>
		<div class="entry-content">
			<ul>
				<li><?php esc_html_e( 'دستگاه بندینگ COF برای ترمیم فلت پنل — همان چیزی که خط عمودی را بدون تعویض شیشه می‌بندد', 'pixva' ); ?></li>
				<li><?php esc_html_e( 'میکروسکوپ صنعتی و هیتر دقیق برای مسیرهای ظریف برد', 'pixva' ); ?></li>
				<li><?php esc_html_e( 'اسیلوسکوپ و منبع تغذیه محدودکننده جریان برای تست امن برد پاور', 'pixva' ); ?></li>
				<li><?php esc_html_e( 'پروگرامر حافظه مین‌بردهای رایج ایرانی و خارجی', 'pixva' ); ?></li>
				<li><?php esc_html_e( 'میز تست تصویر با الگوی رنگ و خاکستری برای تحویل بدون نقص', 'pixva' ); ?></li>
			</ul>
		</div>

		<h2><?php esc_html_e( 'مسیر پذیرش تا تحویل', 'pixva' ); ?></h2>
		<ol class="pixva-steps">
			<li><strong><?php esc_html_e( '۱. ثبت علائم', 'pixva' ); ?></strong><p><?php esc_html_e( 'برند، مدل، تعداد چشمک و عکس صفحه. اگر آب‌خوردگی یا ضربه هست همان اول گفته شود — پنهان‌کاری فقط هزینه را بالا می‌برد.', 'pixva' ); ?></p></li>
			<li><strong><?php esc_html_e( '۲. عیب‌یابی مستند', 'pixva' ); ?></strong><p><?php esc_html_e( 'مسیر تغذیه، پنل و مین‌برد جدا تست می‌شود تا معلوم شود دقیقاً کدام بخش خراب است، نه «احتمالاً».', 'pixva' ); ?></p></li>
			<li><strong><?php esc_html_e( '۳. اعلام هزینه قطعی', 'pixva' ); ?></strong><p><?php esc_html_e( 'قبل از تعویض هر قطعه، مبلغ نهایی و زمان را تأیید می‌کنی. بدون تأیید، کاری انجام نمی‌شود.', 'pixva' ); ?></p></li>
			<li><strong><?php esc_html_e( '۴. تست و گارانتی', 'pixva' ); ?></strong><p><?php esc_html_e( 'تست حرارت و تصویر، بعد برگه ۱۸۰ روزه برای برد و بک‌لایت. وضعیت را هم آنلاین دیده‌ای.', 'pixva' ); ?></p></li>
		</ol>

		<div style="margin-top:1.5rem">
			<?php pixva_render_before_after( PIXVA_URI . '/assets/images/panel-before.jpg', PIXVA_URI . '/assets/images/panel-after.jpg', __( 'نمونه ترمیم خط پنل با بندینگ', 'pixva' ) ); ?>
		</div>

		<div style="margin-top:1.5rem">
			<?php pixva_cta_box(); ?>
		</div>

		<?php
		while ( have_posts() ) :
			the_post();
			if ( get_the_content() ) {
				echo '<div class="entry-content" style="margin-top:1.5rem">';
				the_content();
				echo '</div>';
			}
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
