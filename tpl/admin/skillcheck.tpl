<div>
	<div class="form verticalForm">
		<form method="post" action="index.php?pag=skill_check" accept-charset="UTF-8">
			<fieldset>
				<div class="field">
					<label for="text"><?php echo i18n('skillcheck.form.age');?>:</label>
					<input type="text" id="age" name="age" value="<?php echo (isset($_REQUEST['age'])?$_REQUEST['age']:17);?>"/>
				</div>
				<div class="field">
					<label for="text"><?php echo i18n('skillcheck.form.ageDays');?>:</label>
					<input type="text" id="ageDays" name="ageDays" value="<?php echo isset($_REQUEST['ageDays'])?$_REQUEST['ageDays']:0;?>"/>
				</div>
				<?php foreach(array('keeper','defender','playmaker','passing','winger','scorer','setPieces') as $s): ?>
					<div class="field">
						<label for="<?php echo $s;?>"><?php echo i18n('player.skill.'.$s);?>:</label>
						<select id="<?php echo $s;?>" name="<?php echo $s;?>">
							<?php for($i=0; $i<=20; $i++): ?>
								<option value="<?php echo $i;?>" <?php echo (isset($_REQUEST[$s])&&$_REQUEST[$s]==$i)?'selected':'';?>>
									<?php echo i18n('player.level.'.$i);?>
								</option>
							<?php endfor; ?>
						</select>
					</div>
				<?php endforeach; ?>
				<div class="submit">
					<input type="submit" name="submit" value="<?php echo i18n('skillcheck.form.submit');?>"/>
				</div>
			</fieldset>
		</form>
	</div>
	<div>
		<table class="tableList">
			<tr>
				<th><?php echo i18n('skillcheck.player_type');?></th>
				<th><?php echo i18n('skillcheck.potencial');?></th>
				<th><?php echo i18n('skillcheck.potencial_req');?></th>
				<th><?php echo i18n('skillcheck.potencial_req_age');?></th>
				<th><?php echo i18n('skillcheck.potencial_diff');?></th>
			</tr>
			<?php $i = 1; ?>
			<?php foreach ($pots as $p): ?>
				<tr class="result <?php echo (($i%2==0)?'even':'odd');?>">
					<td><?php echo $p['player_type'];?></td>
					<td><?php echo $p['potencial'];?></td>
					<td><?php echo $p['pot_req'];?></td>
					<td><?php echo $p['pot_req_age'];?></td>
					<td><?php echo $p['pot_diff'];?></td>
				</tr>
			<?php $i++;?>
			<?php endforeach; ?>
		</table>
	</div>
</div>
