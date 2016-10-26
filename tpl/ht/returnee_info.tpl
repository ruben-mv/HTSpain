<div>
	<div class="form verticalForm repatriateInfoForm">
		<?php if(!empty($errors)) :?>
			<div class="error_list">
				<ul>
					<?php foreach($errors as $error): ?>
						<li><?php echo $error;?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<?php if(!empty($saved)) :?>
			<div class="ok_list">
				<?php echo i18n('ht.returnee.info.saved'); ?>
			</div>
		<?php endif; ?>
		<form method="post" action="index.php?pag=returnee">
			<fieldset>
				<legend><?php echo i18n('ht.returnee.info.legend');?></legend>
				<div class="field">
					<label><?php echo i18n('ht.returnee.info.user');?>:</label>
					<?php echo $returnee['UserName']." (".$returnee['UserID'].")" ?>
					<input type="hidden" name="user_id" id="user_id" value="<?php echo $returnee['UserID'] ?>" />
					<input type="hidden" name="user_name" id="user_name" value="<?php echo $returnee['UserName'] ?>" />
				</div>
				<div class="field">
					<label><?php echo i18n('ht.returnee.info.team');?>:</label>
					<?php echo $returnee['TeamName']." (".$returnee['TeamID'].")" ?>
					<input type="hidden" name="team_id" id="team_id" value="<?php echo $returnee['TeamID'] ?>" />
					<input type="hidden" name="team_name" id="team_name" value="<?php echo $returnee['TeamName'] ?>" />
				</div>
				<div class="field">
					<label><?php echo i18n('ht.returnee.info.cash');?>:</label>
					<?php echo number_format($returnee['Cash'],0,'.',' ')." &euro; (".number_format($returnee['ExpectedCash'],0,'.',' ')." &euro;)" ?>
					<input type="hidden" name="cash" id="cash" value="<?php echo $returnee['Cash'] ?>" />
					<input type="hidden" name="expected_cash" id="expected_cash" value="<?php echo $returnee['ExpectedCash'] ?>" />
				</div>
				<div class="field">
					<label><?php echo i18n('ht.returnee.info.trainingType');?>:</label>
					<?php echo training_type($returnee['TrainingType']) ?>
					<input type="hidden" name="training_type" id="training_type" value="<?php echo $returnee['TrainingType'] ?>" />
				</div>
				<div class="field">
					<label><?php echo i18n('ht.returnee.info.trainerSkill');?>:</label>
					<?php echo player_skill($returnee['TrainerSkill'])." (".$returnee['TrainerSkill'].")" ?>
					<input type="hidden" name="trainer_skill" id="trainer_skill" value="<?php echo $returnee['TrainerSkill'] ?>" />
				</div>
				<div class="field">
					<label><?php echo i18n('ht.returnee.info.assistantTrainers');?>:</label>
					<?php echo $returnee['AssistantTrainers'] ?>
					<input type="hidden" name="assistant_trainers" id="assistant_trainers" value="<?php echo $returnee['AssistantTrainers'] ?>" />
				</div>
				<div class="field">
					<label><?php echo i18n('ht.returnee.info.keeperTrainers');?>:</label>
					<?php echo $returnee['KeeperTrainers'] ?>
					<input type="hidden" name="keeper_trainers" id="keeper_trainers" value="<?php echo $returnee['KeeperTrainers'] ?>" />
				</div>
				<div class="field">
					<label><?php echo i18n('ht.returnee.info.trainingLevel');?>:</label>
					<?php echo $returnee['TrainingLevel'] ?> %
					<input type="hidden" name="training_level" id="training_level" value="<?php echo $returnee['TrainingLevel'] ?>" />
				</div>
				<div class="field">
					<label><?php echo i18n('ht.returnee.info.staminaTrainingPart');?>:</label>
					<?php echo $returnee['StaminaTrainingPart'] ?> %
					<input type="hidden" name="stamina_training_part" id="stamina_training_part" value="<?php echo $returnee['StaminaTrainingPart'] ?>" />
				</div>
				<div class="field">
					<label for="nt"><?php echo i18n('ht.returnee.info.nt');?>:</label>
					<select name="nt" id="nt">
						<option value=""><?php echo i18n('form.select');?></option>
						<option value="u20" <?php echo (isset($returnee['NT'])&&($returnee['NT']=='u20'))?'selected':'' ?>><?php echo i18n('nt.u20');?></option>
						<option value="abs"<?php echo (isset($returnee['NT'])&&($returnee['NT']=='abs'))?'selected':'' ?>><?php echo i18n('nt.abs');?></option>
						<option value="both"<?php echo (isset($returnee['NT'])&&($returnee['NT']=='both'))?'selected':'' ?>><?php echo i18n('nt.both');?></option>
					</select>
				</div>
				<div class="field">
					<label for="fast_poll"><?php echo i18n('ht.returnee.info.fastPoll');?>:</label>
					<input type="checkbox" name="fast_poll" id="fast_poll" value="1" <?php echo (empty($returnee['FastPoll']))?'':'checked'?>/>
					<?php echo i18n('ht.returnee.info.fastPoll.desc');?>
				</div>
				<div class="field">
					<label for="comments"><?php echo i18n('ht.returnee.info.comments');?>:</label>
					<textarea name="comments" id="comments"><?php echo isset($returnee['Comments'])?$returnee['Comments']:'' ?></textarea>
				</div>
				<div class="submit">
					<input type="submit" name="submit_info" value="<?php echo i18n('ht.returnee.info.submit'); ?>"/>
				</div>	
				<div class="submit">
					<input type="submit" name="submit_info_delete" value="<?php echo i18n('ht.returnee.info.delete'); ?>"/>
				</div>			
			</fieldset>
		</form>
	</div>
</div>
