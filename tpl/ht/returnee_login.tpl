<div>
	<?php if(!empty($errors)) :?>
		<div class="error_list">
			<ul>
				<?php foreach($errors as $error): ?>
					<li><?php echo $error;?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	<?php if(!empty($deleted)) :?>
			<div class="ok_list">
				<?php echo i18n('ht.returnee.info.deleted'); ?>
			</div>
		<?php endif; ?>
	<div class="form verticalForm loginFormHT">
		<form method="post" action="index.php?pag=returnee">
			<fieldset>
				<legend><?php echo i18n('ht.login.form.legend');?></legend>
				<div class="field">
					<label for="username"><?php echo i18n('ht.login.form.user');?>:</label>
					<input type="text" name="username" id="username" value="" />
				</div>
				<div class="field">
					<label for="password"><?php echo i18n('ht.login.form.security_code');?>:</label>
					<input type="password" name="password" id="password" value="" />
				</div>
				<div class="submit">
					<input type="submit" name="cmdweblogin" value="<?php echo i18n('ht.login.form.submit.returnee'); ?>"/>
				</div>
			</fieldset>
		</form>
	</div>
	<p><?php echo i18n('ht.login.info');?></p>
	<?php require_once('ht_info.tpl');?>
</div>
