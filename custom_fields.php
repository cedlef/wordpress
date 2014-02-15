<?php $lieu = get_post_meta(get_the_id(), 'wpcf-lieu' ,true);?>
<div class="meta">
	<?php if(!empty($lieu)){ ?>
		<span><?php echo $lieu; ?></span>
	<?php } ?>	
</div>

