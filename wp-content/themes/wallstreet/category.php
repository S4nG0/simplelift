<?php get_header(); ?>

<!-- Page Title Section -->
<div class="page-mycarousel">
	<div class="page-title-col">
		<div class="container">
			<div class="row">
				<div class="page-header-title">
					<h1><?php echo single_cat_title("", false); ?></h1>		
				</div>
			</div>	
		</div>
		<?php get_template_part('index', 'breadcrumb'); ?>
	</div>
</div>
<!-- /Page Title Section -->

<!-- Blog & Sidebar Section -->
<div class="container">
	<div class="row">

		<div class="<?php if(is_active_sidebar('sidebar_primary')){ echo 'col-md-8'; } else { echo 'col-md-12'; } ?>" >
			<?php if ( have_posts() ) { 
			while(have_posts()){ the_post(); ?>
			<div id="post-<?php the_ID(); ?>" <?php post_class('blog-section-right'); ?>>
				<?php if(has_post_thumbnail()){ ?>
				<?php $defalt_arg =array('class' => "img-responsive"); ?>
				<div class="blog-post-img">
					<?php the_post_thumbnail('', $defalt_arg); ?>
				</div>
				<?php } ?>
				<div class="clear"></div>
				<div class="blog-post-title">
					<div class="blog-post-date"><span class="date"><a href="<?php the_permalink();?>"><?php echo get_the_date('j'); ?> <small><?php echo get_the_date('M'); ?></small></a></span>
						<span class="comment"><i class="fa fa-comment"></i><?php comments_number('0', '1','%'); ?></span>
					</div>
					<div class="blog-post-title-wrapper">
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php the_content( __( 'Read More' , 'wallstreet' ) ); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'wallstreet' ), 'after' => '</div>' ) ); ?>
						<div class="blog-post-meta">
							<a id="blog-author" href="<?php echo get_author_posts_url( get_the_author_meta( 'ID' ) ); ?>"><i class="fa fa-user"></i> <?php the_author(); ?></a>
							<?php 	$tag_list = get_the_tag_list();
							if(!empty($tag_list)) { ?>
							<div class="blog-tags">
								<i class="fa fa-tags"></i><?php the_tags('', ', ', ''); ?>
							</div>
							<?php } ?>
							<?php 	$cat_list = get_the_category_list();
							if(!empty($cat_list)) { ?>
							<div class="blog-tags">
								<i class="fa fa-star"></i><?php the_category(', '); ?>
							</div>
							<?php } ?>
						</div>
					</div>
				</div>	
			</div>
			<?php } } ?>
			<div class="blog-pagination">					
				<?php if(get_previous_posts_link() ): ?>
				<?php previous_posts_link(); ?>
				<?php endif; ?>					
				<?php if ( get_next_posts_link() ): ?>
				<?php next_posts_link(); ?>
				<?php endif; ?>
			</div>
		</div><!--/Blog Area-->
		<?php get_sidebar(); ?>
	</div>
</div>
<?php get_footer(); ?>
<!-- /Blog & Sidebar Section -->