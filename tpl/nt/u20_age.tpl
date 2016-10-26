<div>
	<table class="tableList">
		<?php foreach($calendar as $worldCup => $matchs): ?>
			<tr>
				<th width="25%"><?php echo i18n('nt.u20.age.header.worldCup'); ?></th>
				<th width="25%"><?php echo i18n('nt.u20.age.header.round'); ?></th>
				<th width="18%"><?php echo i18n('nt.u20.age.header.date'); ?></th>
				<th width="22%"><?php echo i18n('nt.u20.age.header.maxAge'); ?></th>
			</tr>
			<?php $i=1; ?>
			<?php foreach ($matchs as $round => $match): ?>
				<tr class="result <?php echo (($i%2==0)?'even':'odd');?> <?php echo (($match['age']['years']>=21)?'invalid':'');?>">
					<td class="rowIndex"><?php echo i18n("nt.u20.worldCup", $worldCup);?></td>
					<td><?php
						$str = '&nbsp;';
						switch(substr($round,0,1)) {
							case 'C':
								$ar = explode("-",$round);
								$str = i18n('nt.match.qualify',$ar[1]);
								break;
							case 'R':
								$ar = explode("-",$round);
								$str = i18n('nt.match.round',$ar[1],$ar[2]);
								break;
							case 'S':
								$str = i18n('nt.match.semifinal');
								break;
							case 'F':
								$str = i18n('nt.match.final');
								break;
						}
						echo $str;
					?></td>
					<td><?php echo $match['date']?></td>
					<td><?php echo i18n('player.age.value', $match['age']['years'], $match['age']['days']);?></td>
				</tr>
				<?php $i++; ?>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</table>
</div>
