<ul>
	<li><a href="index.php"><?php echo i18n('menu.index');?></a></li>
</ul>

<ul>
	<li><a href="index.php?pag=ht_login"><?php echo i18n('menu.ht.players');?></a></li>
</ul>

<ul>
	<li><a href="index.php?pag=returnee"><?php echo i18n('menu.ht.returnees');?></a></li>
</ul>

<ul>
	<li>
		<a href="#"><?php echo i18n('menu.u20');?></a><!--<![endif]-->
		<!--[if lte IE 6]><table><tr><td><![endif]-->
		<ul>
			<?php /*TODO <li><a href="index.php?pag=u20_players"><?php echo i18n('menu.u20.players');?></a></li>*/ ?>
			<li><a href="index.php?pag=u20_age"><?php echo i18n('menu.u20.age');?></a></li>
			<li><a href="index.php?pag=u20_skill"><?php echo i18n('menu.u20.skill');?></a></li>
			<li><a href="index.php?pag=u20_staff"><?php echo i18n('menu.u20.staff');?></a></li>
		</ul>
		<!--[if lte IE 6]></td></tr></table></a><![endif]-->
	</li>
</ul>

<ul>
	<li>
		<a href="#"><?php echo i18n('menu.abs');?></a><!--<![endif]-->
		<!--[if lte IE 6]><table><tr><td><![endif]-->
		<ul>
			<?php /*TODO <li><a href="index.php?pag=abs_players"><?php echo i18n('menu.abs.players');?></a></li>*/ ?>
			<li><a href="index.php?pag=abs_pot"><?php echo i18n('menu.abs.potential');?></a></li>
			<li><a href="index.php?pag=abs_skill"><?php echo i18n('menu.abs.skill');?></a></li> 
			<li><a href="index.php?pag=abs_staff"><?php echo i18n('menu.abs.staff');?></a></li>
		</ul>
		<!--[if lte IE 6]></td></tr></table></a><![endif]-->
	</li>
</ul>

<ul>
	<li>
		<a href="#"><?php echo i18n('menu.guides');?></a><!--<![endif]-->
		<!--[if lte IE 6]><table><tr><td><![endif]-->
		<ul>
			<li><a href="index.php?pag=nt_guide"><?php echo i18n('menu.guides.nt');?></a></li>
			<li><a href="index.php?pag=youth_guide"><?php echo i18n('menu.guides.youth');?></a></li>
		</ul>
		<!--[if lte IE 6]></td></tr></table></a><![endif]-->
	</li>
</ul>

<ul>
	<li>
		<a href="#"><?php echo i18n('menu.tools');?></a><!--<![endif]-->
		<!--[if lte IE 6]><table><tr><td><![endif]-->
		<ul>
			<li><a href="index.php?pag=mid_calc"><?php echo i18n('menu.tools.mid-calc');?></a></li>
			<li><a href="index.php?pag=train_calc"><?php echo i18n('menu.tools.train-calc');?></a></li>
		</ul>
		<!--[if lte IE 6]></td></tr></table></a><![endif]-->
	</li>
</ul>

<?php if($user_type > $user_types['unreg']): ?>
	<ul>
		<li>
			<a href="#"><?php echo i18n('menu.staff');?></a><!--<![edif]-->
			<!--[if lte IE 6]><table><tr><td><![endif]-->
			<ul>
				<li><a href="index.php?pag=staff_returnees"><?php echo i18n('menu.staff.returnee');?></a></li>
				<li><a href="index.php?pag=staff_players"><?php echo i18n('menu.staff.players');?></a></li>
			</ul>
		</li>
	</ul>
<?php endif; ?>

<?php if($user_type > $user_types['unreg']): ?>
	<ul>
		<li>
			<a href="#"><?php echo i18n('menu.admin');?></a><!--<![edif]-->
			<!--[if lte IE 6]><table><tr><td><![endif]-->
			<ul>
				<li><a href="index.php?pag=news"><?php echo i18n('menu.admin.news');?></a></li>
				<li><a href="index.php?pag=users"><?php echo i18n('menu.admin.users');?></a></li>
				<li><a href="index.php?pag=skill_check"><?php echo i18n('menu.admin.skillCheck');?></a></li>
				<?php if($user_type == $user_types['admin']) :?>
					<li><a href="index.php?pag=lang_check"><?php echo i18n('menu.admin.langCheck');?></a></li>
				<?php endif; ?>
			</ul>
		</li>
	</ul>
<?php endif; ?>
