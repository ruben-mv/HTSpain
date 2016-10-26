<div class="form usersForm">
	<form method="post" action="index.php?pag=users">
		<table class="tableList">
			<tr>
				<th><?php echo i18n('users.header.name');?></th>
				<th><?php echo i18n('users.header.type');?></th>
				<th><?php echo i18n('users.header.team');?></th>
				<th><?php echo i18n('users.header.description');?></th>
				<?php if($_SESSION['user_type'] == $user_types['admin']) : ?>
					<th><?php echo i18n('users.header.password');?></th>
				<?php endif;?>
				<th><?php echo i18n('users.header.validate');?></th>
				<th><?php echo i18n('users.header.delete');?></th>
			</tr>
			<?php
				$i = 1;
				$nt_options = array(''=>i18n('nt.none'), 'u20'=>i18n('nt.u20'), 'abs'=>i18n('nt.abs'), 'both'=>i18n('nt.both'));
				$ut_options = array();
				for($j=$_SESSION['user_type']; $j>0; $j--)
					$ut_options[$j] = i18n('userRole.'.user_type($user_types, $j));
			?>
			<?php foreach ($users as $u): ?>
				<tr class="result <?php echo (($i%2==0)?'even':'odd');?> <?php echo (($u['type']<0)?'invalid':'');?>">
					<td><?=html_entity_decode($u['name'])?></td>
					<td class='field'><?php
						if($u['type']>0 && $_SESSION['user_type'] >= $u['type'])
							echo form_select('usertype['.$u['name'].']', $ut_options, false, $u['type'], ($u['type']<0)?'disabled':'');
						else
							echo i18n('userRole.'.user_type($user_types,$u['type']));
					?></td>
					<td class='field'><?php
						if($u['type']>0 && $_SESSION['user_type'] == $user_types['admin'])
							echo form_select('team['.$u['name'].']', $nt_options, false, $u['team']);
						else
							echo i18n('nt.'.$u['team']);
					?></td>
					<td class='field'>
						<?php if($u['type']>0): ?>
							<input type="text" id="description[<?php echo $u['name']?>]" name="description[<?php echo $u['name']?>]" value="<?php echo str_replace(chr(34),'\'\'',html_entity_decode($u['description']))?>" />
						<?php else: ?>
							<?=html_entity_decode($u['description'])?>
						<?php endif; ?>
					</td>
					<?php if($_SESSION['user_type'] == $user_types['admin']) : ?>
					<td class='field'>
						<?php if($u['type']>0): ?>
							<input type="password" id="password[<?php echo $u['name']?>]" name="password[<?php echo $u['name']?>]" value="" />
						<?php endif; ?>
					</td>
					<?php endif; ?>
					<td class='field'>
						<?php if($u['type']<0): ?>
							<input type="checkbox" id="valid[<?php echo $u['name']?>]" name="valid[<?php echo $u['name']?>]" value="<?php echo (-$u['type']) ?>" />
						<?php endif; ?>
					</td>
					<td class='field'>
						<input type="checkbox" id="delete[<?php echo $u['name']?>]" name="delete[<?php echo $u['name']?>]" value="1" />
					</td>
				</tr>
				<?php $i++;?>
			<?php endforeach; ?>
		</table>
		<div class="submit">
			<input type="submit" name="update" value="<?php echo i18n('users.submit'); ?>"/>
		</div>
	</form>
</div>
