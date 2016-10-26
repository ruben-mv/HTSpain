<div>
	<table class="tableList">
		<tr>
			<th><?php echo i18n('staff.returnee.header.user') ?></th>
			<th><?php echo i18n('staff.returnee.header.team') ?></th>
			<th><?php echo i18n('staff.returnee.header.cash') ?></th>
			<th><?php echo i18n('staff.returnee.header.trainingType') ?></th>
			<th><?php echo i18n('staff.returnee.header.trainerSkill') ?></th>
			<th><?php echo i18n('staff.returnee.header.assistantTrainers') ?></th>
			<th><?php echo i18n('staff.returnee.header.keeperTrainers') ?></th>
			<th><?php echo i18n('staff.returnee.header.trainingLevel') ?></th>
			<th><?php echo i18n('staff.returnee.header.staminaTrainingPart') ?></th>
			<th><?php echo i18n('staff.returnee.header.nt') ?></th>
			<th><?php echo i18n('staff.returnee.header.fastPoll') ?></th>
			<th><?php echo i18n('staff.returnee.header.comments') ?></th>
		</tr>
		<?php
		$i = 1;
		foreach($returnees as $r) : ?>
			<tr class="result <?php echo (($i%2==0)?'even':'odd');?>">
				<td><?php echo $r['UserName'];?></td>
				<td><?php echo $r['TeamName'];?></td>
				<td><?php echo number_format($r['Cash'],0,'.',' ')." &euro; (".number_format($r['ExpectedCash'],0,'.',' ')." &euro;)"?></td>
				<td><?php echo training_type($r['TrainingType']) ?></td>
				<td><?php echo player_skill($r['TrainerSkill'])." (".$r['TrainerSkill'].")" ?></td>
				<td><?php echo $r['AssistantTrainers'] ?></td>
				<td><?php echo $r['KeeperTrainers'] ?></td>
				<td><?php echo $r['TrainingLevel'] ?> %</td>
				<td><?php echo $r['StaminaTrainingPart'] ?> %</td>
				<td><?php echo (empty($r['NT'])) ? "&nbsp;" : i18n('nt.'.$r['NT']) ?></td>
				<td><?php echo (empty($r['FastPoll'])) ? i18n('no') : i18n('yes') ?></td>
				<td><?php echo $r['Comments'] ?></td>
			</tr>
			<?php $i++; ?>
		<?php endforeach; ?>
	</table>
</div>
