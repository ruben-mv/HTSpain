<div id="welcome">
	<div class="body">
		<?php echo i18n('welcome.message.body'); ?>
	</div>
</div>



<div id="news">
	<?php foreach($news as $n): ?>
		<?php
			$img_not = '';
			switch($n['team']) {
				case 0: $img_not = 'ico_news_ht.png';  break;
				case 1: $img_not = 'ico_news_abs.png'; break;
				case 2: $img_not = 'ico_news_u20.png'; break;
			}
		?>
		<div class="notice">
			<div class="noticeTitle">
				<div>
					<b class="spiffy">
						<b class="spiffy1"><b></b></b>
						<b class="spiffy2"><b></b></b>
						<b class="spiffy3"></b>
						<b class="spiffy4"></b>
						<b class="spiffy5"></b>
					</b>
					<div class="spiffyfg">
						<table>
							<tr>
								<td><img src="./img/<?php echo $img_not;?>" alt="[<?php echo i18n('news.team.'.$n['team']);?>]"/></td>
								<td><?php echo $n['title'];?></td>
							</tr>
						</table>
					</div>
					<b class="spiffy">
						<b class="spiffy5"></b>
						<b class="spiffy4"></b>
						<b class="spiffy3"></b>
						<b class="spiffy2"><b></b></b>
						<b class="spiffy1"><b></b></b>
					</b>
				</div>
			</div>
			<div class="noticeBody">
				<?php echo $n['text']?>
			</div>
			<hr class="line"/>
			<div class="noticeFooter">
				<?php echo $n['user']?> [<?php echo i18n('news.team.'.$n['team']);?>] - <?php echo $n['publication_date']?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
