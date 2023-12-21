<?php

$post = isset($_SESSION['blog_post'])
	? $_SESSION['blog_post']
	: FALSE;
$feedback = isset($_SESSION['blog_feedback'])
	? $_SESSION['blog_feedback']
	: FALSE;

unset($_SESSION['blog_post'], $_SESSION['blog_feedback']);

?>
<form class="rows-10" method="post" action="<?php echo URL . 'forum/create_blog_post/' ?>">
	<fieldset>
		<div class="cols-15">
			<div class="col-4">
				<label class="label">Blog</label>
				<label class="select<?php echo isset($feedback['blog']) ? ' invalid' : false ?>">
					<select name="blog">
						<?php foreach($this->blogCategories as $categoryAlias => $blogs) { ?>
						<optgroup label="<?php echo $blogs[0]['CategoryName']; ?>">
						<?php foreach($blogs as $blog){ 
						
						$blogIsSelected =
							(
								!isset($post['blog']) &&
								(
									strtolower($this->blogAlias) == strtolower($blog['Alias']) ||
									(
										$this->blogAlias == FALSE &&
										$blog['MyBlog']
									)
								)
							) ||
							(
								$post['blog'] &&
								$post['blog'] == $blog['Alias']
							);
						
						?>
						<option<?php echo $blogIsSelected ? ' selected' : FALSE; ?> value="<?php echo $blog['Alias']; ?>"><?php echo $blog['Title']; ?></option>
						<?php } ?>
						</optgroup>
						<?php } ?>
					</select>
					<i></i>
				</label>
			</div>
			<div class="col-8">
				<label class="label">Title</label>
				<label class="text<?php echo isset($feedback['title']) ? ' invalid' : false ?>">
					<input name="title" type="text" autofocus pattern="<?php echo REGEX_BLOG_POST_TITLE; ?>" <?php echo isset($post['title']) ? ' value="' . $post['title'] . '"' : FALSE; ?>>
					<?php if ( isset($feedback['title']) ) { ?>
					<p class="note"><?php echo $feedback['title']; ?></p>
					<?php } ?>
				</label>
			</div>
		</div>
	</fieldset>
	<fieldset>
		<label class="textarea<?php echo isset($feedback['content']) ? ' invalid' : false ?>">
			<textarea rows="15" name="content" required pattern=".+" ><?php echo isset($post['content']) ? $post['content'] : false; ?></textarea>
			<?php if ( isset($feedback['content']) ) { ?>
			<p class="note"><?php echo $feedback['content']; ?></p>
			<?php } else { ?>
			<p class="note"><strong>Allowed tags:</strong> [b] <strong>bold text</strong> [/b], [i] <em>italicized text</em> [/i] and [pgp] [/pgp] for pgp blocks or other non-formatted text.</p>
			<?php } ?>
		</label>
	</fieldset>
	<fieldset>
		<input type="submit" class="btn big blue color" value="Create Blog Post" />
	</fieldset>
</form>
