<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">

<!--  Version: Multiflex-3 Update-2 / Overview             -->
<!--  Date:    November 29, 2006                           -->
<!--  Author:  G. Wolfgang                                 -->
<!--  License: Fully open source without restrictions.     -->
<!--           Please keep footer credits with a link to   -->
<!--           G. Wolfgang (www.1-2-3-4.info). Thank you!  -->

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="cache-control" content="no-cache" />
	<meta http-equiv="expires" content="3600" />
	<meta name="revisit-after" content="2 days" />
	<meta name="robots" content="index,follow" />
	<meta name="author" content="Design: G. Wolfgang www.1-2-3-4.info / Author: KaiHansen &amp; reuvem &amp; Nitta" />
	<meta name="distribution" content="global" />
	<meta name="description" content="HTSpain U20/ABS tracker" />
	<meta name="keywords" content="HTSpain Hattrick traker" />
	<link rel="stylesheet" type="text/css" media="screen,projection,print" href="./css/main.css" />
	<link rel="stylesheet" type="text/css" media="screen,projection,print" href="./css/layout.css" />
	<link rel="stylesheet" type="text/css" media="screen,projection,print" href="./css/table.css" />
	<link rel="stylesheet" type="text/css" media="screen,projection,print" href="./css/form.css" />
	<link rel="icon" type="image/x-icon" href="./img/favicon.ico" />
	<title><?=$title?></title>
</head>

<!-- Global IE fix to avoid layout crash when single word size wider than column width -->
<!--[if IE]><style type="text/css"> body {word-wrap: break-word;}</style><![endif]-->

<body>
	<!-- Main Page Container -->
	<div id="page-container">
		
		<!-- HEADER BEGIN -->
		<div id="header">
			
			<div id="header-middle">
				
				<a class="sitelogo" href="index.php" title="<?php echo i18n('main.sitelogo.linkTitle');?>"></a>
				
				<div id="lang-menu">
					<ul>
						<?php foreach($languages as $lang): ?>
							<li><a href="index.php?gui_lang=<?php echo $lang;?>"><img src="./img/<?php echo strtoupper($lang);?>.png" alt="<?php echo i18n('lang.'.$lang);?>" /></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
				
				<div id="session-menu">
					<?php echo ($is_logued) ? $_SESSION['name'] : '';?>
					<?php $log_key = ($is_logued) ? 'logout' : 'login';?>
					<a href="index.php?pag=<?php echo $log_key;?>" title="<?php echo i18n('main.'.$log_key.'.linkTitle');?>">
						<img src="./img/<?php echo $log_key ?>.png" alt="<?php echo i18n('main.'.$log_key.'.imgAlt');?>" />
					</a>
				</div>
			</div>
			
			<!-- Navigation Level 2 (Drop-down menus) -->
			<div id="main-menu">
				<?=$menu;?>
			</div>
			
		</div>
		<!-- HEADER END -->
		
		<!-- MAIN BEGIN -->
		<div id="main">
			<br/>
			<div id="main-content">
				<h1><?=$page_title?></h1>
				<?=$content;?>
			</div>
		</div>
		<!-- MAIN END -->
    	
		<!-- FOOTER BEGIN -->
		<div id="footer">
			<p>Copyright &copy; 2009 HT Spain | <?php echo i18n('main.rights.reserved') ?></p>
			<p class="credits">
				<?php echo i18n('main.rights.original', "<a href='http://www.1-2-3-4.info' title='".i18n('main.rights.original.linkTitle')."' target='_blank'>G. Wolfgang</a>"); ?>
				| <?php echo i18n('main.rights.adapted', "KaiHansen &amp; reuvem &amp; Nitta"); ?>
				| <a href="http://validator.w3.org/check?uri=referer" title="<?php echo i18n('main.w3c.xhtml.linkTitle'); ?>" target="_blank">W3C XHTML 1.0</a>
				| <a href="http://jigsaw.w3.org/css-validator/" title="<?php echo i18n('main.w3c.css.linkTitle'); ?>" target="_blank">W3C CSS 2.0</a>
			</p>
		</div>
		<!-- FOOTER END -->
	
	</div>
	<!-- END main page container -->
</body>

</html>
