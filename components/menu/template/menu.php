<ul id="<?php echo $identifier; ?>" class="menu">
<?php foreach($items as $item) : ?>
	<li class="item <?php echo ($item['active']) ? 'active' : ''; ?> <?php echo isset($item['class']) ? $item['class'] : ''; ?>">
		<a href="<?php echo $item['url']; ?>" title="<?php echo $item['title']; ?>"<?php echo (isset($item['target'])) ? ' target="_'.$item['target'].'"' : ''; ?>>
			<?php echo $item['title']; ?>
		</a>
	</li>
<?php endforeach; ?>
</ul>