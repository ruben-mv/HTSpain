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
	
	<div class="form linearForm languageCheckForm">
		<form method="post" action="index.php?pag=lang_check">
			<fieldset>
				<legend><?php echo i18n('lang.check.legend');?></legend>
				<div class="field">
					<select id='lang1' name='lang1'>
					<?php foreach($languages as $l) : ?>
						<option value="<?php echo $l;?>"><?php echo strtoupper($l);?></option>
					<?php endforeach; ?>
				</select>
				</div>
				<div class="field">				
					<select id='lang2' name='lang2'>
						<?php foreach($languages as $l) : ?>
							<option value="<?php echo $l;?>"><?php echo strtoupper($l);?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="submit">
					<input type='submit' name='submit' value="<?php echo i18n('lang.check.submit')?>"/>
				</div>
			</fieldset>
		</form>
	</div>
	
	<?php if(!empty($values)) : ?>
		<div>
			<p><?php echo i18n(($diff)?'lang.check.diff':'lang.check.ok', $lang1, $lang2);?>:</p>
			<table class="tableList">
				<tr>
					<th><?php echo i18n('lang.check.header.key');?></th>
					<th><?php echo i18n('lang.check.header.value', strtoupper($lang1));?></th>
					<th><?php echo i18n('lang.check.header.value', strtoupper($lang2));?></th>
				</tr>
				<?php $i=1; ?>
				<?php foreach($values as $key=>$val) :?>
					<?php
						$class = (($i%2==0)?'even':'odd');
						if(empty($val[0]) || empty($val[1]))
							$class .= " invalid";
					?>
					<tr class="result <?php echo $class;?>">
						<td><?php echo $key;?></td>
						<td><?php echo $val[0];?></td>
						<td><?php echo $val[1];?></td>
					</tr>
					<?php $i++;?>
				<?php endforeach; ?>
			</table>
		</div>
	<?php endif; ?>
</div>
