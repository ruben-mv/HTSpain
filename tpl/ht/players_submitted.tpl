<h2><?php echo i18n('ht.players.submitted.numPlayers',count($players));?></h2>
<div>
	<table class="tableList">
		<tr>
			<th><?php echo i18n('ht.players.submitted.header.name');?></th>
			<th><?php echo i18n('ht.players.submitted.header.id');?></th>
			<th><?php echo i18n('ht.players.submitted.header.salary');?></th>
			<th class="iniColBlock"><?php echo i18n('ht.players.submitted.header.age');?></th>
			<th><?php echo i18n('ht.players.submitted.header.tsi');?></th>
			<th><?php echo i18n('ht.players.submitted.header.injury');?></th>
			<th><?php echo i18n('ht.players.submitted.header.specialty');?></th>
			<th><?php echo i18n('ht.players.submitted.header.trainer_type');?></th>
			<th class="iniColBlock"><?php echo i18n('player.agreeability.short');?></th>
			<th><?php echo i18n('player.aggressiveness.short');?></th>
			<th><?php echo i18n('player.honesty.short');?></th>
			<th class="iniColBlock"><?php echo i18n('player.form.short');?></th>
			<th><?php echo i18n('player.experience.short');?></th>
			<th><?php echo i18n('player.leadership.short');?></th>
			<th class="iniColBlock"><?php echo i18n('player.skill.stamina.short');?></th>
			<th><?php echo i18n('player.skill.keeper.short');?></th>
			<th><?php echo i18n('player.skill.defender.short');?></th>
			<th><?php echo i18n('player.skill.playmaker.short');?></th>
			<th><?php echo i18n('player.skill.winger.short');?></th>
			<th><?php echo i18n('player.skill.passing.short');?></th>
			<th><?php echo i18n('player.skill.scorer.short');?></th>
			<th><?php echo i18n('player.skill.setPieces.short');?></th>
			<th class="iniColBlock"><?php echo i18n('ht.players.submitted.header.player_type_abs');?></th>
			<th><?php echo i18n('ht.players.submitted.header.potential');?></th>
			<th class="iniColBlock"><?php echo i18n('ht.players.submitted.header.player_type_u20');?></th>
			<th class="iniColBlock"><?php echo i18n('ht.players.submitted.header.action');?></th>
		</tr>
		<?php
		$i = 1;
		foreach ($players as $p): ?>
        	<tr class="result <?php echo (($i%2==0)?'even':'odd');?>">
				<td class="rowIndex" scope="row"><?=$p['PlayerName']?></td>
				<td class="right"><?=$p['PlayerID']?></td>
				<td class="right"><?=$p['Salary']?> &euro;</td>
				<td class="center iniColBlock"><?=$p['Age'].' ('.$p['AgeDays'].')'?></td>
				<td class="right"><?=$p['TSI']?></td>
				<td><?php echo player_injury($p['InjuryLevel'],'img'); ?></td>
				<td><?php echo player_specialty($p['Specialty'],'short'); ?></td>
				<td><?php echo player_trainer_type($p['TrainerType'],'short'); ?></td>
				<td class="iniColBlock"><?=$p['Agreeability']?></td>
				<td><?=$p['Aggressiveness']?></td>
				<td><?=$p['Honesty']?></td>
				<td class="center iniColBlock"><?=$p['PlayerForm']?></td>
				<td><?=$p['Experience']?></td>
				<td><?=$p['Leadership']?></td>
				<td class="iniColBlock"><?=$p['StaminaSkill']?></td>
				<td><?=$p['KeeperSkill']?></td>
				<td><?=$p['DefenderSkill']?></td>
				<td><?=$p['PlaymakerSkill']?></td>
				<td><?=$p['WingerSkill']?></td>
				<td><?=$p['PassingSkill']?></td>
				<td><?=$p['ScorerSkill']?></td>
				<td><?=$p['SetPiecesSkill']?></td>
				<td class="iniColBlock"><?php echo (empty($p['ABS_player_type'])) ? "&nbsp;" : $p['ABS_player_type'];?></td>
				<td><?php echo ($p['potencial']>0) ? sprintf("%.3F",$p['potencial']) : "&nbsp;"; ?></td>
				<td class="iniColBlock"><?php echo (empty($p['U20_player_type'])) ? "&nbsp;" : $p['U20_player_type'];?></td>
				<td class="iniColBlock"><?=$p['action']?></td>
			</tr>
			<?php $i++; ?>
		<?php endforeach; ?>
	</table>
</div>
