<div>
	<table class="tableList">
		<tr>
			<th><?php echo i18n('nt.abs.skill.header.playerType');?></th>
			<th><?php echo i18n('nt.abs.skill.header.potential');?></th>
			<th class="iniColBlock"><?php echo i18n('player.skill.keeper');?></th>
			<th><?php echo i18n('player.skill.defender');?></th>
			<th><?php echo i18n('player.skill.playmaker');?></th>
			<th><?php echo i18n('player.skill.passing');?></th>
			<th><?php echo i18n('player.skill.winger');?></th>
			<th><?php echo i18n('player.skill.scorer');?></th>
			<th><?php echo i18n('player.skill.setPieces');?></th>
			<th class="iniColBlock"><?php echo i18n('nt.abs.skill.header.specialty');?></th>
		</tr>
		<?php
		$i = 1;
		foreach ($requirements as $r): ?>
        	<tr class="result <?php echo (($i%2==0)?'even':'odd');?>">
				<td><?php echo $r['player_type']?></td>
				<td><?php echo sprintf("%.2f", $r['age'])?></td>
				<td class="iniColBlock"><?php echo (empty($r['KeeperSkill'])) ? '&nbsp;' : player_skill($r['KeeperSkill'])." (".$r['KeeperSkill'].")"?></td>
				<td><?php echo (empty($r['DefenderSkill'])) ? '&nbsp;' : player_skill($r['DefenderSkill'])." (".$r['DefenderSkill'].")"?></td>
				<td><?php echo (empty($r['PlaymakerSkill'])) ? '&nbsp;' : player_skill($r['PlaymakerSkill'])." (".$r['PlaymakerSkill'].")"?></td>
				<td><?php echo (empty($r['PassingSkill'])) ? '&nbsp;' : player_skill($r['PassingSkill'])." (".$r['PassingSkill'].")"?></td>
				<td><?php echo (empty($r['WingerSkill'])) ? '&nbsp;' : player_skill($r['WingerSkill'])." (".$r['WingerSkill'].")"?></td>
				<td><?php echo (empty($r['ScorerSkill'])) ? '&nbsp;' : player_skill($r['ScorerSkill'])." (".$r['ScorerSkill'].")"?></td>
				<td><?php echo (empty($r['SetPiecesSkill'])) ? '&nbsp;' : player_skill($r['SetPiecesSkill'])." (".$r['SetPiecesSkill'].")"?></td>
				<td class="iniColBlock"><?php echo player_specialty($r['Specialty'])?></td>
			</tr>
			<?php $i++; ?>
		<?php endforeach; ?>
	</table>
	<p><a href='index.php?pag=abs_pot'><?php echo i18n('nt.abs.skill.help');?></a></p>
</div>
