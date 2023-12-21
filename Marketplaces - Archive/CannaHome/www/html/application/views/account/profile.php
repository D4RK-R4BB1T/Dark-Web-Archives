<?php 

	$url_prefix = $url_prefix = URL.$filename.'/';

?>
<div class="rows-30">
	<div class="row panel">
		<div class="left">
			<strong><?php echo $this->sections ? ucwords(NXS::formatNumber(count($this->sections))) . ' profile section' . (count($this->sections) == 1 ? false : 's') : 'No profile sections'; ?></strong>
		</div>
		<div class="right">
			<a class="btn blue" target="_blank" href="<?php echo URL . 'v/' . $this->UserAlias . '/'; ?>"><i class="<?php echo Icon::getClass('USER'); ?>"></i>View Profile</a>
			<?php if( $this->new_section ) { ?>
			<button class="btn" form="section_form" name="save_and_insert"><i class="<?php echo Icon::getClass('PLUS'); ?>"></i>New Section</button>
			<?php } else { ?>
			<a class="btn" href="<?php echo URL . 'account/profile/new/' ?>"><i class="<?php echo Icon::getClass('PLUS'); ?>"></i>New Section</a>
			<?php } ?>
		</div>
	</div>
	<form class="row rows-15" id="section_form" method="post" action="<?php echo URL . 'account/update_sections/' ?>">
		<?php if( $this->sections ) { ?>
		<ul class="row list-expandable trashcans">
			<?php $i = 0; foreach($this->sections as $section) { $id = $section['id'] ? $section['id'] : 'new'; ?>
			<li>
				<input id="section-<?php echo $id ?>" name="enable_section[]" value="<?php echo $id ?>" class="expand" type="checkbox" checked>
				<div class="alt-label">
					<div class="cols-5">
						<div class="col-4">
							<label class="text inline">
								<input type="text" maxlength="100" name="<?php echo 'section-' . $id . '_name' ?>" value="<?php echo $section['name'] ?>" placeholder="<?php
									switch(++$i){
										case 1:
											echo 'Refund Policy';
										break;
										case 2:
											echo 'Shipping';
										break;
										case 3:
											echo 'Mission Statement';
										break;
										case 4:
											echo 'Terms of Service';
										break;
										case 5:
											echo 'Privacy Policy';
										break;
									}
								?>" />
								<b></b>
							</label>
						</div>
						<div class="col-5"></div>
						<label class="col-1 label">Order:</label>
						<div class="col-2">
							<label class="select">
								<select name="<?php echo 'section-' . $id . '_order' ?>">
									<?php for($o = 1; $o <= count($this->sections); $o++) { ?>
									<option value="<?php echo $o; ?>"<?php echo $o == $i ? ' selected' : false; ?>><?php echo $o ?></option>
									<?php } ?>
								</select>
							</label>
						</div>
					</div>
				</div>
				<?php if( $section['type'] !== 'policy' ) { ?>
				<label for="section-<?php echo $id ?>">
					<i></i>
				</label>
				<?php } else { ?>
				<label>
					<i class="lock"></i>
				</label>
				<?php } ?>
				<div class="expandable">
					<label class="textarea">
						<textarea name="<?php echo 'section-' . $id . '_content' ?>" rows="10" placeholder="Allowed tags: [b], [i], [a=http://...], [pgp]"><?php echo $section['content'] ?></textarea>
					</label>
				</div>
			</li>
			<?php } ?>
		</ul>
		<button type="submit" name="csrf" value="<?= $this->getCSRFToken(); ?>" class="row btn big blue">Save Changes</button>
		<?php } ?>
	</form>
</div>
