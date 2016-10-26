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
	<div class="form linearForm">
		<form method="post" action="index.php?pag=staff_players">
			<fieldset>
				<legend>Actualizados despu&eacute;s de:</legend>
				<div class="field">
					<label for="date">Fecha (aaaa-mm-dd):</label>
					<input type="date" name="date" id="date" value="<?=$date?>" />
				</div>
				<div class="field">
					<label for="time">Hora (hh:mm)(24h):</label>
					<input type="time" name="time" id="time" value="<?=$time?>" />
				</div>
				<?php if($both): ?>
					<div class="field">
					<label for="nt_team"><?php echo i18n('ht.login.form.team');?>:</label>
						<select name="nt_team" id="nt_team">
							<option value="abs">ABS</option>
							<option value="u20">U20</option>
						</select>
					</div>
				<?php else : ?>
					<input type="hidden" name="nt_team" id="nt_team" value="<?=$_SESSION['team'];?>"/>
				<?php endif; ?>
				<div class="submit">
					<input type="submit" name="cmdexport" value="EXPORTAR"/>
				</div>
			</fieldset>
		</form>
	</div>
</div>
