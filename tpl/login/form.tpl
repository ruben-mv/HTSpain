<div class="column1-unit">
	<div>
		<p><?php echo i18n('login-register.info');?></p>
	</div>
	<?php if(!empty($errors)) :?>
		<div class="error_list">
			<ul>
				<?php foreach($errors as $error): ?>
					<li><?php echo $error;?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	<div class="form verticalForm loginForm">
		<form method="post" action="index.php?pag=login">
			<fieldset class="left">
				<legend><?php echo i18n('login-register.loginForm.legend');?></legend>
				<div class="field">
					<label for="login_username"><?php echo i18n('login-register.loginForm.user');?>:</label>
					<input type="text" name="login_username" id="login_username" value="" />
				</div>
				<div class="field">
					<label for="login_passwd"><?php echo i18n('login-register.loginForm.password');?>:</label>
					<input type="password" name="login_passwd" id="login_passwd" value="" />
				</div>
				<div class="field">
					<label for="login_passwd"><?php echo i18n('login-register.loginForm.remember');?>:</label>
					<input type="checkbox" name="login_remember" id="login_remember" value="1" />
				</div>
				<div class="submit">
					<input type="submit" name="submit_login" id="submit_login" value="<?php echo i18n('login-register.loginForm.submit');?>" />
				</div>
			</fieldset>
		</form>
		<form method="post" action="index.php?pag=register" id='register'>

			<fieldset class="right">
				<legend><?php echo i18n('login-register.registerForm.legend');?></legend>
				<div class="field">
					<label for="username"><?php echo i18n('login-register.registerForm.user');?>:</label>
					<input type="text" name="username" id="username" value="" />
				</div>
				<div class="field">
					<label for="passwd"><?php echo i18n('login-register.registerForm.password');?>:</label>
					<input type="password" name="passwd" id="passwd" value="" />
				</div>
				<div class="field">
					<label for="passwd2"><?php echo i18n('login-register.registerForm.password2');?>:</label>
					<input type="password" name="passwd2" id="passwd2" value="" />
				</div>
				<div class="field">
					<label for="nt"><?php echo i18n('login-register.registerForm.nt');?>:</label>
					<select name="nt" id="nt">
						<option value=""><?php echo i18n('form.select');?></option>
						<option value="u20"><?php echo i18n('nt.u20');?></option>
						<option value="abs"><?php echo i18n('nt.abs');?></option>
					</select>
				</div>
				<div class="field">
					<label for="usertype"><?php echo i18n('login-register.registerForm.post');?>:</label>
					<select name="usertype" id="usertype">
						<option value="0"><?php echo i18n('form.select');?></option>
						<option value="1"><?php echo i18n('userRole.scout');?></option>
						<option value="2"><?php echo i18n('userRole.coach');?></option>
					</select>
				</div>
				<div class="field">
					<label for="description"><?php echo i18n('login-register.registerForm.description');?>:</label>
					<input type="text" name="description" id="description" value="" />
				</div>
				<div class="field">
					<?php echo recaptcha_get_html($pub_key);?>
				</div>
				<div class="submit">
					<input type="submit" name="submit_reg" id="submit_reg" value="<?php echo i18n('login-register.registerForm.submit');?>" />
				</div>
			</fieldset>
		</form>
	</div>
</div>
