<?php

$post	= isset($_SESSION['discussion_post'])
	? $_SESSION['discussion_post']
	: FALSE;
$feedback	= isset($_SESSION['discussion_feedback'])
	? $_SESSION['discussion_feedback']
	: FALSE;

unset($_SESSION['discussion_post'], $_SESSION['discussion_feedback']);

?>
<form class="rows-10" method="post" action="<?php echo URL . 'forum/create_discussion/' ?>">
	<fieldset>
		<div class="cols-15">
			<?php
			switch ($this->category){
				case 'review': ?>
					<div class="col-4">
						<label class="label">Listing</label>
						<label class="select<?= isset($feedback['listing']) ? ' invalid' : false ?>">
							<select name="listing">
								<?php foreach ($this->reviewableListings as $reviewableListing){ ?>
								<option<?= isset($post['listing']) && $post['listing'] == $reviewableListing['ID'] ? ' selected' : false; ?> value="<?= $reviewableListing['ID']; ?>"><?= $reviewableListing['Name']; ?></option>
								<?php } ?>
							</select>
							<i></i>
						</label>
					</div>
					<div class="col-4">
						<label class="label">Title</label>
						<label class="text<?= isset($feedback['title']) ? ' invalid' : false ?>">
							<input name="title" required type="text" pattern="^[\w][^\n]{1,<?php echo (MAX_LENGTH_DISCUSSION_TITLE - 1); ?>}" <?php echo isset($post['title']) ? ' value="' . $post['title'] . '"' : FALSE; ?>>
							<?php if ( isset($feedback['title']) ) { ?>
							<p class="note"><?php echo $feedback['title']; ?></p>
							<?php } ?>
						</label>
					</div>
					<div class="col-4">
						<label class="label">&nbsp;</label>
						<label class="row checkbox">
							<input<?= isset($post['submit_anonymous']) ? ' checked' : false; ?> name="submit_anonymous" type="checkbox">
							<i></i>
							<span class="small">Submit review anonymously</span>
						</label>
					</div><?php
					break;
				default: ?>
				<div class="col-4">
					<label class="label">Category</label>
					<label class="select<?php echo isset($feedback['category']) ? ' invalid' : false ?>">
						<select name="category">
							<?php foreach($this->discussionCategories as $key => $category) { if($key == 0) continue; ?>
							<option <?php
						
							echo $category['hasPostingPrivileges']
								?	'value="' . $category['ID'] . '"' . (
										(
											isset($post['category']) &&
											$post['category'] == $category['ID']
										) ||
										$this->categoryID == $category['ID']
											?	' selected'
											:	FALSE
									)
								:	'disabled'
							?>><?php echo $category['name']; ?></option>
							<?php } ?>
						</select>
						<i></i>
					</label>
				</div>
				<div class="col-8">
					<label class="label">Title</label>
					<label class="text<?php echo isset($feedback['title']) ? ' invalid' : false ?>">
						<input name="title" required type="text" pattern="^[\w][^\n]{1,<?php echo (MAX_LENGTH_DISCUSSION_TITLE - 1); ?>}" <?php echo isset($post['title']) ? ' value="' . $post['title'] . '"' : FALSE; ?>>
						<?php if ( isset($feedback['title']) ) { ?>
						<p class="note"><?php echo $feedback['title']; ?></p>
						<?php } ?>
					</label>
				</div>
		<?php	} ?>
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
		<input type="submit" class="btn big blue color" value="Submit Post" />
	</fieldset>
</form>
