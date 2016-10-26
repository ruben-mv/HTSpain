<div>
	<table class="tableList">
		<tr>
			<th><?php echo i18n('nt.staff.header.userType');?></th>
			<th><?php echo i18n('nt.staff.header.description');?></th>
			<th><?php echo i18n('nt.staff.header.name');?></th>
		</tr>
		<?php
		$i = 1;
		foreach ($users as $u): ?>
        	<tr class="result <?php echo (($i%2==0)?'even':'odd');?>">
				<td><?php echo i18n('userRole.'.user_type($user_types, $u['type']));?></td>
				<td><?php echo $u['description']?></td>
				<td><?php echo $u['name']?></td>
			</tr>
			<?php $i++; ?>
		<?php endforeach; ?>
	</table>
</div>
