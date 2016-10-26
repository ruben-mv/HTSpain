<div>
	<table class="tableList">
		<tr>
			<th><?php echo i18n('nt.u20.skill.header.playerType');?></th>
			<th><?php echo i18n('nt.u20.skill.header.age');?></th>
			<th colspan="2" class="iniColBlock"><?php echo i18n('nt.u20.skill.header.main');?></th>
			<th colspan="2" class="iniColBlock"><?php echo i18n('nt.u20.skill.header.secondary1');?></th>
			<th colspan="2" class="iniColBlock"><?php echo i18n('nt.u20.skill.header.secondary2');?></th>
			<th colspan="2" class="iniColBlock"><?php echo i18n('nt.u20.skill.header.secondary3');?></th>
		</tr>
		<?php
		$i = 1;
		$last_type = '';
		foreach($requirements as $r): ?>
			<?php if(!empty($last_type) && $last_type!=$r['player_type']) : ?>
				<tr class="<?php echo (($i%2==0)?'even':'odd');?>">
					<th colspan="2">&nbsp;</th>
					<th colspan="2" class="iniColBlock">&nbsp;</th>
					<th colspan="2" class="iniColBlock">&nbsp;</th>
					<th colspan="2" class="iniColBlock">&nbsp;</th>
					<th colspan="2" class="iniColBlock">&nbsp;</th>
				</tr>
				<?php $i++; ?>
			<?php endif; ?>
        	<tr class="result <?php echo (($i%2==0)?'even':'odd');?>">
				<td><?php echo ($r['player_type']==$last_type) ? '&nbsp;' : $r['player_type']?></td>
				<td><?php echo sprintf("%.3f", $r['age'])?></td>
				<td class="iniColBlock"><?php echo (empty($r['p_skill'])) ? '&nbsp;' : i18n('player.skill.'.strtolower(substr($r['p_skill'],0,-strlen('Skill'))));?></td>
				<td><?php echo (empty($r['p_skill']))  ? '&nbsp;' : player_skill($r['p_value'])." (".$r['p_value'].")"?></td>
				<td class="iniColBlock"><?php echo (empty($r['s1_skill'])) ? '&nbsp;' : i18n('player.skill.'.strtolower(substr($r['s1_skill'],0,-strlen('Skill'))));?></td>
				<td><?php echo (empty($r['s1_skill'])) ? '&nbsp;' : player_skill($r['s1_value'])." (".$r['s1_value'].")"?></td>
				<td class="iniColBlock"><?php echo (empty($r['s2_skill'])) ? '&nbsp;' : i18n('player.skill.'.strtolower(substr($r['s2_skill'],0,-strlen('Skill'))));?></td>
				<td><?php echo (empty($r['s2_skill'])) ? '&nbsp;' : player_skill($r['s2_value'])." (".$r['s2_value'].")"?></td>
				<td class="iniColBlock"><?php echo (empty($r['s3_skill'])) ? '&nbsp;' : i18n('player.skill.'.strtolower(substr($r['s3_skill'],0,-strlen('Skill'))));?></td>
				<td><?php echo (empty($r['s3_skill'])) ? '&nbsp;' : player_skill($r['s3_value'])." (".$r['s3_value'].")"?></td>
			</tr>
			<?php $i++; ?>
			<?php $last_type = $r['player_type'];?>
		<?php endforeach; ?>
	</table>
</div>
