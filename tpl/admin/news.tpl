<div>
	<div class="form verticalForm newsForm">
		<ul>
			<li><?php echo i18n('news.form.help.bold');?>: [b][/b]</li>
			<li><?php echo i18n('news.form.help.italic');?>: [i][/i]</li>
			<li><?php echo i18n('news.form.help.underline');?>: [u][/u]</li>
			<li><?php echo i18n('news.form.help.url');?>: [url][/url]</li>
		</ul>
		<form method="post" action="index.php?pag=news" accept-charset="UTF-8">
			<input type="hidden" name="id" id="id" value="<?php echo isset($piece_of_news)?$piece_of_news['id']:'';?>" />
			<fieldset>
				<legend><?php echo i18n('news.form.legend');?></legend>
				<div class="field">
					<label for="lang"><?php echo i18n('news.form.lang');?>:</label>
					<select <?php echo (isset($piece_of_news)&&isset($op)&&($op=='edit'))?'disabled':'name="lang" id="lang"';?>>
						<?php foreach($languages as $lang): ?>
							<option value="<?php echo $lang;?>" <?php echo (isset($piece_of_news)&&$piece_of_news['lang']==$lang)?'selected':'';?>>
								<?php echo i18n('lang.'.$lang);?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php if(isset($piece_of_news)&&isset($op)&&($op=='edit')): ?>
						<input type="hidden" name="lang" id="lang" value="<?php echo $piece_of_news['lang'];?>" />
					<?php endif; ?>
					<?php if(isset($piece_of_news)&&isset($op)&&($op=='translate')): ?>
						<input type="hidden" name="old_lang" id="old_lang" class="button" value="<?php echo isset($piece_of_news)?$piece_of_news['lang']:'';?>" />
						<input type="submit" name="load" value="<?php echo i18n('news.form.loadLang');?>"/>
						<?php if($load_lang_fail): ?>
							<?php echo i18n('news.form.error.noTrans', i18n('lang.'.$_REQUEST['lang'])); ?>
						<?php endif; ?>
					<?php endif; ?>
				</div>
				<?php if(isset($piece_of_news)&&isset($op)&&($op=='translate')): ?>
					<div class="field">
						<label for="lang"></label>
						<?php echo i18n('lang.'.$piece_of_news['lang']);?>
					</div>
				<?php endif; ?>
				<div class="field">
					<label for="title"><?php echo i18n('news.form.title');?>:</label>
					<input type="text" name="title" id="title" value="<?php echo isset($piece_of_news)?$piece_of_news['title']:'';?>" />
				</div>
				<?php if(isset($piece_of_news)&&isset($op)&&($op=='translate')): ?>
					<div class="field">
						<label for="title"></label>
						<?php echo $piece_of_news['title']; ?>
					</div>
				<?php endif; ?>
				<div class="field">
					<label for="team"><?php echo i18n('news.form.team');?>:</label>
					<?php if(isset($piece_of_news)&&isset($op)&&($op=='translate')): ?>
						<input type="hidden" name="team" id="team" value="<?php echo $piece_of_news['team'];?>" />
					<?php endif; ?>
					<select <?php echo (isset($piece_of_news)&&isset($op)&&($op=='translate'))?'disabled':'name="team" id="team"';?>>
						<?php for($i=0; $i<3; $i++): ?>
							<option value="<?php echo $i;?>" <?php echo (isset($piece_of_news)&&$piece_of_news['team']==$i)?'selected':'';?>>
								<?php echo i18n('news.team.'.$i);?>
							</option>
						<?php endfor; ?>
					</select>
				</div>
				<div class="field">
					<label for="text"><?php echo i18n('news.form.text');?>:</label>
					<textarea name="text" id="text"><?php echo isset($piece_of_news)?$piece_of_news['text']:'';?></textarea>
				</div>
				<?php if(isset($piece_of_news)&&isset($op)&&($op=='translate')): ?>
					<div class="field">
						<label for="text"></label>
						<?php echo $piece_of_news['text'];?>
					</div>
				<?php endif; ?>
				<div class="submit">
					<input type="submit" name="submit" value="<?php echo i18n('news.form.submit');?>"/>
				</div>
			</fieldset>
		</form>
	</div>
</div>

<div>
	<p><a href='index.php?pag=news'><?php echo i18n('news.list.op.create');?></a></p>
	<table class="tableList">
		<tr>
			<th><?php echo i18n('news.list.date');?></th>
			<th><?php echo i18n('news.list.team');?></th>
			<th><?php echo i18n('news.list.lang');?></th>
			<th><?php echo i18n('news.list.title');?></th>
			<th colspan='3'><?php echo i18n('news.list.op');?></th>
		</tr>
		<?php $i = 1; ?>
		<?php foreach ($news as $n): ?>
			<tr class="result <?php echo (($i%2==0)?'even':'odd');?>">
				<td><?php echo $n['publication_date'];?></td>
				<td><?php echo i18n('news.team.'.$n['team']);?></td>
				<td><?php
					$langs = '';
					$first_lang = null;
					foreach($languages as $lang) {
						if(isset($n['langs'][$lang])) {
							$langs .= $lang.'-';
							if($first_lang==null)
								$first_lang = $lang;
						}
					}
					echo substr($langs,0,-1);
				?></td>
				<td><?php echo "[$first_lang] ".$n['langs'][$first_lang]['title'];?></td>
				<td><a href="index.php?pag=news&op=edit&id=<?php echo $n['id'];?>&lang=<?php echo $first_lang;?>"><?php echo i18n('news.list.op.edit');?></a></td>
				<td><a href="index.php?pag=news&op=translate&id=<?php echo $n['id'];?>&lang=<?php echo $first_lang;?>"><?php echo i18n('news.list.op.translate');?></a></td>
				<td><a href="index.php?pag=news&op=delete&id=<?php echo $n['id'];?>"><?php echo i18n('news.list.op.delete');?></a></td>
			</tr>
			<?php $i++;?>
		<?php endforeach; ?>
	</table>
</div>
