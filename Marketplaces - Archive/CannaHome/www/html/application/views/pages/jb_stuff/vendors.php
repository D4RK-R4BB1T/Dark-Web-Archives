<?php
	$post		= isset($_SESSION['vendorApplication']['post']) ? $_SESSION['vendorApplication']['post'] : FALSE;
	$respone	= isset($_SESSION['vendorApplication']['response']) ? $_SESSION['vendorApplication']['response'] : FALSE;
	
	unset($_SESSION['vendorApplication']);
?>
<?php if( $this->UserVendor) { ?>
<h2>Your application has been accepted!</h2>
<fieldset class="formatted">
	<p>Your application to become a vendor has been accepted and you may submit your listings and start selling on the marketplace</p>
</fieldset>
<?php } else { ?>
<form method="post" action="<?php echo URL . 'account/submit_vendor_application/' ?>">
	<h2>Apply to become a vendor</h2>	
	<fieldset>
		<label class="label">Vendor Alias</label>
		<label class="text<?php echo isset($respone['alias']) ? ' invalid' : FALSE ?>">
			<input type="text" required pattern="[A-Za-z0-9_-]{3,16}" name="alias" value="<?php echo isset($post['alias']) ? $post['alias'] : $this->UserAlias; ?>">
			<?php if( isset($respone['alias']) ){ ?>
			<p class="note"><?php echo $respone['alias']; ?></p>
			<?php } ?>
		</label>
	</fieldset>
	<?php /*?><fieldset>
		<label class="label">Bitcoin Public Key</label>
		<label class="text<?php echo isset($respone['btc_public_key']) ? ' invalid' : FALSE ?>">
			<input type="text" required name="btc_public_key" value="<?php echo isset($post['btc_public_key']) ? $post['btc_public_key'] : $this->page['data']['publicKey']; ?>">
			<?php if( isset($respone['btc_public_key']) ){ ?>
			<p class="note"><?php echo $respone['btc_public_key']; ?></p>
			<?php } ?>
		</label>
	</fieldset><?php */?>
	<fieldset class="formatted">
		<p>We intend to maintain the highest standards for vendor quality on any darknet market.</p>
		<p>We don't require vendor bonds, but we do require significant verifiable vendor experience and high ratings from either a currently existing market or from trusted sources like the Grams website.</p>
		<p class="grey-box"><strong>NOTE:</strong> If you have an invite code from market staff, you can enter it at the bottom of the page &mdash; you will not need to provide any additional application materials.</p>
		<p>If you have the <strong>200+ transactions</strong> and a <strong>vendor rating of 4.95 or higher</strong> on Agora or an equivalent rating on another market, all you need to do to get a vendor account on <?php echo $this->SiteName_Short ?> is to provide a link to your current account on Agora or a link to your Grams page showing evidence of that experience.</p>
		<p>To get fast-track approval for a vendor account, write an application that includes the following:</p>
		<ol>
			<li>Statement that you are requesting <?php echo $this->SiteName_Short ?> vendor account.</li>
			<li>Links to an external market vendor profile or Grams profile page verifying the required experience/ratings.</li>
			<li>Sign the message with you PGP key. Key should be the same key as displayed on linked profiles.</li>
			<li>Paste the signed message into the space below</li>
		</ol>
		<p>If you don't quite meet the above requirements, we will still consider your application.</p>
		<p>Submit the same information as described above, as well as a statement with any additional information you think is relevant to your application. We are reasonable and if there is a good explanation for lower ratings than we usually require, we will seriously consider your request. Making a decision may take slightly longer if we need to search for reviews on Reddit or forums to get a complete understanding of your qualifications.</p>
	</fieldset>
	<fieldset>
		<label class="label">Application</label>
		<label class="textarea">
			<textarea name="application" rows="15"><?php echo isset($post['application']) ? $post['application'] : $this->page['data']['application'] ?></textarea>
		</label>
	</fieldset>
	<fieldset class="formatted">
		<p>At <?php echo $this->SiteName_Short ?>, we believe that vendors know best how to run their businesses and should be given flexibility to design a refund policy that makes sense for their particular business.</p>
		<p>These terms are what the market will use to mediate any disputes between vendor and buyer. Buyer should read and agree to a vendor's refund policy before making a purchase and vendor should be expected to follow their own stated policy.</p>
		<p>Your policy must be reasonable and fair. An unfair or one-sided refund policy is grounds for denial of a vendor application.</p>
		<p>Please provide a summary of your refund policy beneath. Upon approval of your application, you may expand your reund policy.</p>
	</fieldset>
	<fieldset>
		<label class="label">Refund Policy</label>
		<label class="textarea">
			<textarea name="policy" rows="15"><?php echo isset($post['policy']) ? $post['policy'] : $this->page['data']['policy'] ?></textarea>
		</label>
	</fieldset>
	<fieldset>
		<label class="row label">Invite Code(s)</label>
		<?php if($this->page['data']['codes']) { ?>
		<label class="textarea dotted-bottom">
			<textarea rows="<?php echo count($this->page['data']['codes']); ?>" readonly><?php
			
			echo implode(
				PHP_EOL,
				array_map(
					function($array){
						return $array['code'] . ' &mdash; ' . $array['type'] . ' endorsement';
					},
					$this->page['data']['codes']
				)
			);
			
?></textarea>
		</label>
		<?php } ?>
		<label class="textarea<?php echo isset($respone['codes']) ? ' invalid' : FALSE ?>">
			<textarea name="codes" pattern="(?:\w{10}\n?)*" rows="5"><?php echo isset($post['codes']) ? $post['codes'] : FALSE ?></textarea>
			<?php if( isset($respone['codes']) ){ ?>
			<p class="note"><?php echo $respone['codes']; ?></p>
			<?php } else { ?>
			<p class="note">One code per line</p>
			<?php } ?>
		</label>
	</fieldset>
	<fieldset><input type="submit" class="btn big blue" value="Submit Application"></fieldset>
</form>
<?php } ?>