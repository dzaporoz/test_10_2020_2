<div id="header">
    <a href="/">&#10229; на главную</a>
    <h1><?=$title?></h1>
</div>
<div id="wrapper">
    <div id="content">
        <?php if (! empty($post['image_url'])) { ?>
            <img src="<?=$post['image_url']?>"/>
        <?php } ?>
        <?=$post['content']?>
    </div>
</div>