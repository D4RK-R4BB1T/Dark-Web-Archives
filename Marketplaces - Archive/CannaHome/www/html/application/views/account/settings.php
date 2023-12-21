<?php 
	if ( NULL !== Session::get('settings_feedback') )
		$feedback = Session::get('settings_feedback');
	if ( NULL !== Session::get('settings_post') )
		$post = Session::get('settings_post');
	
	Session::set('settings_feedback', null);
	Session::set('settings_post', null);

$this->renderNotifications(array('Settings'));

?>
<form action="<?php echo URL; ?>account/update_settings/" class="rows-30" method="post" enctype="multipart/form-data">
	<input type="hidden" name="csrf" value="<?= $this->getCSRFToken(); ?>">
	<?php if( isset($_SESSION['authorize_settings']) && $authorize = $_SESSION['authorize_settings'] ){ unset($_SESSION['authorize_settings']['authorize_username'], $_SESSION['authorize_settings']['authorize_password'], $_SESSION['authorize_settings']['authorize_code']); ?>
	<div class="modal" id="authorize">
		<div>
			<a class="close" href="#close">&times;</a>
			<div class="rows-10">
				<label class="label"><?php echo $authorize['title'] ?></label>
				<label class="row text<?php echo isset($authorize['authorize_username']) ? ' invalid' : false ?>">
					<input class="prepend" name="authorize_username" placeholder="Your Username" readonly value="<?php echo $this->UserAlias; ?>">
					<i class="<?php echo Icon::getClass('USER'); ?>"></i>
					<?php if ( isset($authorize['authorize_username']) ) { ?>
					<p class="note"><?php echo $authorize['authorize_username']; ?></p>
					<?php } ?>
				</label>
				<label class="row text<?php echo isset($authorize['authorize_password']) ? ' invalid' : false ?>">
					<input type="password" autofocus class="prepend" name="authorize_password" placeholder="Your Password"<?php echo isset($post['authorize_password']) ? ' value="' . $post['authorize_password'] . '"' : false; ?>>
					<i class="<?php echo Icon::getClass('LOCK'); ?>"></i>
					<?php if ( isset($authorize['authorize_password']) ) { ?>
					<p class="note"><?php echo $authorize['authorize_password']; ?></p>
					<?php } ?>
				</label>
				<?php if( $pgp = $authorize['pgp'] ){ ?>
				<div class="row">
					<label class="label">
						<a class="tooltip left">Decrypt this message with PGP</a>
						<div>
							<p>Your account has PGP authentication enabled. To continue, decrypt the message below to find your one-time authentication code. Paste this code in the box below.
						</div>
						<span> to authenticate:</span>
					</label>
					<label class="textarea">
						<textarea readonly rows="15"><?php echo $authorize['message'] ?></textarea>
					</label>
				</div>
				<label class="row text<?php echo isset($authorize['authorize_code']) ? ' invalid' : false ?>">
					<input class="prepend" tabindex="3" type="text" placeholder="Authentication Code" name="authorize_code">
					<i class="<?php echo Icon::getClass('SHIELD'); ?>"></i>
					<?php if ( isset($authorize['authorize_code']) ) { ?>
					<p class="note"><?php echo $authorize['authorize_code']; ?></p>
					<?php } ?>
				</label>
				<?php } ?>
				<input name="authorizing" type="submit" class="row btn wide color" value="Authorize">
			</div>
		</div>
	</div>
	<?php } ?>
	<div class="row">
		<fieldset>
			<div class="cols-5">
				<div class="col-7">
					<label class="label">Password (leave blank to keep old password)</label>
					<div class="cols-15">
						<div class="col-6">
							<label class="text">
								<input class="prepend" type="password" placeholder="&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;" name="password" <?php echo isset($post['password']) ? ' value="'.$post['password'].'"' : false; ?> placeholder="New password"/>
								<i class="<?php echo Icon::getClass('LOCK'); ?>"></i>
							</label>
						</div>
						<div class="col-6">
							<label class="text<?php echo isset($feedback['password_repeat']) ? ' invalid' : false ?>">
								<input class="prepend" type="password" placeholder="&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;&#9679;" name="password_repeat" <?php echo isset($post['password_repeat']) ? ' value="'.$post['password_repeat'].'"' : false; ?> placeholder="Confirm new password" />
								<i class="<?php echo Icon::getClass('LOCK'); ?>"></i>
								<?php if( isset($feedback['password_repeat']) ) { ?>
								<div class="note"><strong>Error:</strong> <?php echo $feedback['password_repeat'] ?></div>
								<?php } ?>
							</label>
						</div>
					</div>
				</div>
				<?php if ($this->UserVendor){ ?>
				<div class="col-1"></div>
				<div class="col-4">
					<label class="label">&nbsp;</label>
					<label class="label" for="show-chat">
						<a class="tooltip left color-<?php echo $this->preferences['allowMultipleSessions'] ? 'green' : 'red'; ?>">Multi-Login is <strong><?php echo $this->preferences['allowMultipleSessions'] ? 'Enabled' : 'Disabled'; ?></strong> (?)</a>
						<div>
							<p>The multi-login feature allows more than one person to be logged into your account at one time. It may be two business partners or a vendor and a staff member.</p>
							<p>Since this feature has security implications, support will help you set up this feature and the additional security measures which may be appropriate.</p>
						</div>
					</label>
					<?php /*
					<label class="row checkbox label"<?php echo !$this->preferences['allowMultipleSessions'] ? ' for="show-chat"' : false; ?>>
						<input class="expand" <?php echo $this->preferences['allowMultipleSessions'] ? 'name="allow_multiple_sessions" checked' : 'disabled'; ?> type="checkbox"><i></i>Allow Multiple Simultaneous Sessions
					</label> */ ?>
				</div>
				<?php } ?>
			 </div>
		 </fieldset>
	</div>
	<hr>
	<div class="row" id="profile">
		<fieldset>
			<div class="cols-30">
				<div class="col-6">
					<label class="label">PGP public key</label>
					<label class="textarea pgp<?php echo isset($feedback['pgp']) ? ' invalid' : false; ?>">
						<textarea name="pgp" rows="14" spellcheck="false"><?php echo isset($post['pgp']) ? $post['pgp'] : $this->preferences['pgp']['key']; ?></textarea>
						<?php if ( isset($feedback['pgp']) ) { ?>
						<p class="note"><?php echo $feedback['pgp']; ?></p>
						<?php } else { 
						echo empty($this->preferences['pgp']['key']) ? '<p class="note">You may be asked to verify ownership of the PGP public key.</p>' : false; } ?>
					</label>
					<?php if( isset($_SESSION['new_pgp']) && $new_pgp = $_SESSION['new_pgp'] ){ ?>
					<div class="modal" id="verify-pgp">
						<div>
							<a class="close" href="#close">&times;</a>
							<div class="rows-10">
								<div class="row">
									<label class="label">Decrypt this message to verify PGP public key:</span></label>
									<label class="textarea">
										<textarea readonly rows="15"><?php echo $new_pgp['message'] ?></textarea>
									</label>
								</div>
								<label class="row text<?php echo isset($feedback['new_pgp_code']) ? ' invalid' : false ?>">
									<input class="prepend" type="text" placeholder="Authentication Code" name="new_pgp_code">
									<i class="<?php echo Icon::getClass('SHIELD'); ?>"></i>
									<?php if ( isset($feedback['new_pgp_code']) ) { ?>
									<p class="note"><?php echo $feedback['new_pgp_code']; ?></p>
									<?php } ?>
								</label>
								<input type="submit" class="row btn wide color" value="Verify">
							</div>
						</div>
					</div>
					<?php } ?>
				</div>	
				<div class="col-6 rows-15">
					<label class="label">&nbsp;</label>
					<?php /*<label class="row checkbox label">
						<input type="checkbox" <?php echo $this->preferences['pgp']['invalid'] ? 'disabled' : 'name="double_encryption" ' . (isset($post['double_encryption']) || (!isset($post) && $this->preferences['pgp']['encrypt']) ? ' checked' : false); ?>><i></i>Enable PGP Encryption
						<?php echo $this->preferences['pgp']['invalid'] ? '<a class="tooltip inline top color-red">Unsupported Key Type</a><div><p>Encryption is only supported for RSA key types.</p></div>' : '<a class="tooltip inline top">What is this?</a><div><p>With PGP encryption, all unencrypted, incoming messages' . ($this->UserVendor && !$this->isForum ? ' and order details' : FALSE) . ' are automatically encrypted with your PGP public key.</p><p>Note that this <strong>does not</strong> apply to outgoing messages unless the recipient has enabled PGP encryption.</p><p>This feature is inteded as a &#8216;fail-safe feature&#8217;, and cannot be relied on for strong security &mdash; all users are advised to encrypt their outgoing messages manually.</p></div>'; ?>
					</label>
					<label class="row checkbox label disabled">
						<input type="checkbox" disabled><i></i>Enable PGP encryption
						<a class="tooltip inline top color-red">( Discontinued )</a><div><p>All users are advised to encrypt sensitive messages manually.</p></div>
					</label> */ ?>
					<label class="row checkbox label">
						<input class="expand" type="checkbox" <?php echo $this->preferences['pgp']['invalid'] ? 'disabled' : 'name="two_factor_authentication" ' . (isset($post['two_factor_authentication']) || (!isset($post) && $this->preferences['pgp']['twoFA']) ? ' checked' : false); ?>><i></i>Enable PGP authentication (2FA)
						<?php if($this->preferences['pgp']['invalid']){ ?>
						<a class="tooltip inline top color-red">Unsupported Key Type</a>
						<div>
							<p>PGP Authentication is only supported for RSA key types.</p>
						</div>
						<?php } else { ?>
						<a class="tooltip inline top">What is this?</a>
						<div>
							<p>With PGP authentication enabled, you will be asked to decrypt an authentication message each time you log in.</p>
							<p>This adds an extra layer of security to your account.</p>
						</div>
						<?php if( $this->preferences['pgp']['twoFA'] == FALSE ){ ?>
						<p class="note expandable color-red"><strong>If you lose access to your PGP key, you may lose access to your account!</strong></p>
						<?php } } ?>
					</label>
				</div>
			</div>
		</fieldset>
		<?php 	if ($this->canUploadPicture) { ?>
		<fieldset>
			<label class="label">Profile picture <span class="note">(<?= $this->UserVendor ? AVATAR_IMAGE_WIDTH . '&times;' . AVATAR_IMAGE_HEIGHT : USER_CLASS_PRIVILEGES_AVATAR_WIDTH_STAR_BUYERS . '&times;' . USER_CLASS_PRIVILEGES_AVATAR_HEIGHT_STAR_BUYERS ?>)</span></label>
			<div class="cols-15">
				<div class="col-7">
					<div class="picture-uploader">
						<?php if( $this->preferences['image'] ){ ?>
						<div class="pic" style="background-image:url(<?php echo $this->preferences['image'] ?>)">
							<label for="delete-picture">Delete</label>
							<input type="checkbox" id="delete-picture" hidden>
							<div class="modal">
								<label for="delete-picture"></label>
								<div class="rows-10">
									<label class="close" for="delete-picture">&times;</label>
									<p class="row">Are you sure you wish to delete this picture?</p>
									<div class="row cols-10">
										<div class="col-6"><button type="submit" class="btn wide" name="delete_pic">Delete</button></div>
										<div class="col-6"><label for="delete-picture" class="btn wide red">Never mind</label></div>
									</div>
								</div>
							</div>
						</div>
						<?php } ?>
						<?php if( !$this->preferences['image'] ) { ?>
						<label class="input-file">
							<span>Upload a picture</span>
							<input name="MAX_FILE_SIZE" value="<?php echo MAX_FILE_SIZE ?>" type="hidden">
							<input name="file" type="file">
						</label>
						<?php } ?>
					</div>
				</div>
			</div>
		</fieldset>
		<?php } ?>
	</div>
	<hr>
	<?php if($this->isForum){ ?>
	<div class="row" id="forum">
		<fieldset>
			<label class="label">Signature</label>
			<div class="cols-15">
				<div class="col-6">
					<label class="textarea">
						<textarea name="signature" rows="10"><?php echo isset($post['signature']) ? $post['signature'] : ( !empty($this->preferences['signature']) ? $this->preferences['signature'] : false ); ?></textarea>
					</label>
				</div>
				<div class="col-6">
					<div class="meta-table">
						<table>
							<tbody>
								<tr>
									<td>[b] bold [/b]</td>
									<td><strong>bold</strong></td>
								</tr>
								<tr>
									<td>[i] italicized [/i]</td>
									<td><em>italicized</em></td>
								</tr>
								<tr>
									<td>[a=http://...] link [/a]</td>
									<td><a>link</a></td>
								</tr>
								<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
								<tr>
									<td>[pgp] block [/pgp]</td>
									<td class="formatted"><pre>block</pre></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</fieldset>
	</div>
	<?php } else { ?>
	<div class="row" id="crypto">
		<?php $this->renderNotifications(['PaymentMethods']); ?>
		<fieldset>
			<div class="cols-5">
				<div class="col-6">
					<?php if ($this->UserVendor){ ?>
					<label class="label">Accepted cryptocurrencies (click to enable/disable)
					<?php } else { ?>
					<label class="label">Multisig configuration</label>
					<?php } ?>
				</div>
				<div class="col-6">
					<label class="label">
						&nbsp;
						<a href="/p/<?= PAGE_MULTISIG_SETUP . '/' . ($this->UserVendor ? '#how' : false) ?>" target="_blank" class="tooltip inline top">Need help setting up multisig?</a>
						<div><p>Click here to view our guide on configuring your account for multisig escrow.</p></div>
					</label>
				</div>
			</div>
			<ul class="list-expandable lefthanded">
				<?php foreach($this->preferences['paymentMethods'] as $paymentMethod){
					$inputID = 'payment_method-' . $paymentMethod['Identifier'];
					
					//$publicKeyName = $inputID . '-public_key';
					$extendedPublicKeyName = $inputID . '-extended_public_key';
					
					$configureListingsModalID = $inputID . '-configure_listings';
					$configureListingsToggleName = $configureListingsModalID . '-toggle';
					$configureListingsToggleID_all = $configureListingsModalID . '-all';
					$configureListingsToggleID_selected = $configureListingsModalID . '-selected';
					$configureListingsAllActive = $paymentMethod['allActive'] || !$paymentMethod['configured'];
					
					$paymentMethodEnabled =
						(
							!isset($post) &&
							$paymentMethod['Enabled']
						) ||
						(
							isset($post['payment_method']) &&
							in_array(
								$paymentMethod['Identifier'],
								$post['payment_method']
							)
						);
				?>
				<li>
					<?php if ($paymentMethod['configured']){ ?>
					<input type="hidden" name="<?= $inputID . '-configured'; ?>" value="1">
					<input type="hidden" name="<?= $inputID . '-currency_name'; ?>" value="<?= $paymentMethod['Name']; ?>">
					<?php } ?>
					<input<?= $paymentMethodEnabled ? ' checked' : false; ?> id="<?= $inputID; ?>" name="payment_method[]" value="<?= $paymentMethod['Identifier']; ?>" class="expand" type="checkbox">
					<div class="alt-label">
						<div class="cols-5">
							<div class="col-2">&nbsp;</div>
							<div class="<?= $this->UserVendor ? 'col-7' : 'col-10' ?>">
								<div class="text<?= isset($feedback[$extendedPublicKeyName]) ? ' invalid' : false; ?>">
									<input<?php
										if ($extendedPublicKey = isset($post[$extendedPublicKeyName]) ? $post[$extendedPublicKeyName] : $paymentMethod['ExtendedPublicKey'])
											echo ' value="' . $extendedPublicKey . '"';
											
										if ($paymentMethod['ExtendedPublicKey'] && $this->UserVendor)
											echo ' disabled';
										
										echo ' name="' . $extendedPublicKeyName . '"';
									?> pattern="<?= REGEX_CRYPTOCURRENCY_EXTENDED_PUBLIC; ?>" placeholder="Master Public Key" class="small prepend" type="text">
									<i class="<?= Icon::getClass('KEY', true); ?>"></i>
								</div>
							</div>
							<?php if ($this->UserVendor){ ?>
							<div class="col-3 align-right">
								<label for="<?= $configureListingsModalID; ?>" class="btn"><i class="<?= Icon::getClass('TH-LIST'); ?>"></i>Configure Listings</label>
								<input type="checkbox" hidden id="<?= $configureListingsModalID; ?>">
								<div class="modal wide">
									<label for="<?= $configureListingsModalID; ?>"></label>
									<div>
										<label class="close" for="<?= $configureListingsModalID; ?>">&times;</label>
										<div class="rows-15">
											<h5 class="row band bigger"><span><strong><?= $paymentMethod['Name'];?></strong>: Configure Listing Availability</span></h5>
											<ul class="row list-expandable radios lefthanded">
												<li>
													<input<?= $configureListingsAllActive ? ' checked' : false; ?> id="<?= $configureListingsToggleID_all; ?>" class="expand" name="<?= $configureListingsToggleName; ?>" value="all" type="radio">
													<label for="<?= $configureListingsToggleID_all; ?>">All active listings<i></i></label>
												</li>
												<?php if ($paymentMethod['listings']){ ?>
												<li>
													<input<?= !$configureListingsAllActive ? ' checked' : false; ?> id="<?= $configureListingsToggleID_selected; ?>" class="expand" name="<?= $configureListingsToggleName; ?>" value="selected" type="radio">
													<label for="<?= $configureListingsToggleID_selected; ?>">Only for selected listings<i></i></label>
													<div class="expandable">
														<label class="select-multiple">
															<select multiple style="height: 200px;" name="<?= $configureListingsModalID . '-listings[]' ;?>">
																<?php foreach($paymentMethod['listings'] as $listing){ ?>
																<option<?= $listing['Enabled'] && !$configureListingsAllActive ? ' selected' : false; ?> value="<?= $listing['ID']; ?>"><?= $listing['label']; ?></option>
																<?php } ?>
															</select>
															<p class="note">Hold down <strong>CTRL</strong> or <strong>CMD</strong> to select multiple options.</p> 
														</label>
													</div>
												</li>
												<?php } ?>
											</ul>
										</div>
									</div>
								</div>
							</div>
							<?php } ?>
						</div>
					</div>
					<label<?= $this->UserVendor ? ' for="' . $inputID . '"' : false; ?>><i class="<?= $paymentMethod['Icon']; ?>-m"></i><?= $paymentMethod['Name']; ?></label>
				</li>
				<?php } ?>
			</ul>
		</fieldset>
	</div>
	<?php } ?>
	<input class="row btn big blue" name="submit" type="submit" value="Save Changes">
</div>
</form>
