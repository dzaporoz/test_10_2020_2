<div id="header">
    <h1><?=$title?></h1>
</div>
<div id="wrapper">
    <div id="content">
        <div id="posts">
            <?php
                if (empty($posts)) { ?>
                    <h2>Nothing to show...</h2>
                <?php } else {
                    foreach ($posts as $post) { ?>
                        <div class="post">
                            <h2 class="title"><a href="/posts/<?=$post['id']?>"><?=$post['title']?></a></h2>
                            <div class="story">
                                <?=$post['excerpt']?>...
                                <button onclick="location.href='/posts/<?=$post['id']?>';">Подробнее</button>
                            </div>
                        </div>
                    <?php }
                }
            ?>
        </div>
    </div>
</div>