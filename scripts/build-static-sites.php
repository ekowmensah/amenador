<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
chdir(dirname(__DIR__));
$presets=require 'config/public_page_layouts.php';
function h($value){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
$folders=[];
foreach (array_keys($presets) as $key) {
    $folders[$key] = ['general' => 'general-goods', 'restaurant' => 'bar-restaurant'][$key] ?? $key;
}
foreach($folders as $key=>$folder){
 $p=$presets[$key];$name='Amenador '.$p['name'];
 $dir='main-domain-site/'.$folder;
 foreach(['assets/css','assets/img','assets/js'] as $sub)if(!is_dir($dir.'/'.$sub))mkdir($dir.'/'.$sub,0755,true);
 copy('public/css/bar-pages.css',$dir.'/assets/css/base.css');copy('public/css/public-page-layouts.css',$dir.'/assets/css/layouts.css');
 copy('main-domain-site/template-kit/site.css',$dir.'/assets/css/site.css');copy('main-domain-site/template-kit/site.js',$dir.'/assets/js/site.js');
 foreach(array_unique(array_column($presets,'image')) as $image)copy('public/img/'.$image,$dir.'/assets/img/'.$image);
 copy('main-domain-site/.htaccess',$dir.'/.htaccess');
 file_put_contents($dir.'/robots.txt',"User-agent: *\nAllow: /\n");
 $config=['businessName'=>$name,'phone'=>'','email'=>'','address'=>'','openingHours'=>'','posBaseUrl'=>'','enableCatalogue'=>false,'catalogueUrl'=>'','currency'=>'GHS','locale'=>'en-GH'];
 if (!file_exists($dir.'/assets/js/site-config.js')) file_put_contents($dir.'/assets/js/site-config.js',"// Edit these public business details before deployment.\n// Empty posBaseUrl detects this local project or uses /shop/public on hosting.\n// Set enableCatalogue to true and configure catalogueUrl to show live products.\nwindow.SITE_CONFIG = ".json_encode($config,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).";\n");
 ob_start();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?=h($p['description'])?>">
    <meta name="theme-color" content="<?=h($p['theme'])?>">
    <title><?=h($name)?> | <?=h($p['name'])?></title>
    <link rel="icon" type="image/svg+xml" href="assets/img/<?=h($p['image'])?>">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/layouts.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <script src="assets/js/site-config.js" defer></script>
    <script src="assets/js/site.js" defer></script>
</head>
<body class="bar-site public-layout-<?=h($key)?>" data-site-label="<?=h($p['name'])?>">
<a href="#main" class="bar-eyebrow public-skip-link">Skip to content</a>
<div class="bar-wrap">
    <header class="bar-nav" id="top">
        <a class="bar-logo static-brand" href="#top"><span data-business-name><?=h($name)?></span><small><?=h($p['name'])?></small></a>
        <button class="site-menu-toggle" type="button" aria-controls="site-navigation" aria-expanded="false" aria-label="Toggle navigation">&#9776;</button>
        <nav class="bar-nav-links" id="site-navigation" aria-label="Main navigation"><a href="#discover"><?=h($p['nav'])?></a><a href="#experience">About us</a><a href="#live-catalogue" data-catalogue-link hidden>Products</a><a href="#visit">Visit us</a><a class="bar-button outline" href="/shop/public/login" data-portal>Staff sign in &rarr;</a></nav>
    </header>
    <main id="main">
        <section class="bar-hero">
            <div><span class="bar-eyebrow"><?=h($p['eyebrow'])?></span><h1><?=h($p['title'])?><br><em><?=h($p['emphasis'])?></em></h1><p class="bar-lead"><?=h($p['lead'])?></p><div class="bar-actions"><a class="bar-button" href="#discover"><?=h($p['primary'])?> &rarr;</a><a class="bar-button outline" href="#visit">Plan your visit</a></div></div>
            <div class="bar-art"><img src="assets/img/<?=h($p['image'])?>" alt="<?=h($p['alt'])?>" width="500" height="550"><span class="bar-art-caption"><?=h($p['caption'])?></span></div>
        </section>
        <div class="bar-strip"><?php foreach($p['highlights'] as $text):?><span><?=h($text)?></span><?php endforeach;?></div>
        <section class="bar-section" id="discover">
            <span class="bar-eyebrow"><?=h($p['section_eyebrow'])?></span><h2><?=h($p['section_title'])?></h2><p class="bar-section-intro"><?=h($p['section_intro'])?></p>
            <div class="bar-cards"><?php foreach($p['cards'] as $i=>$card):?><article class="bar-card"><span><?=str_pad($i+1,2,'0',STR_PAD_LEFT)?> / <?=h($card['eyebrow'])?></span><h3><?=h($card['title'])?></h3><p><?=h($card['text'])?></p></article><?php endforeach;?></div>
        </section>
        <section class="bar-section static-catalogue" id="live-catalogue" aria-labelledby="catalogue-title" hidden>
            <span class="bar-eyebrow">EXPLORE WHAT IS AVAILABLE</span><h2 id="catalogue-title"><?= $key==='restaurant'?'Browse the menu.':($key==='pharmacy'?'Explore our products.':($key==='hardware'?'Find your project supplies.':($key==='general'?'Browse every department.':'Find your everyday favourites.'))) ?></h2>
            <form class="catalogue-toolbar" id="catalogue-form" role="search">
                <label class="catalogue-search"><span>Find a product</span><input type="search" id="catalogue-search" placeholder="Search by name" maxlength="80"></label>
                <label><span>Category</span><select id="catalogue-category"><option value="">All categories</option></select></label>
                <label><span>Location</span><select id="catalogue-location"><option value="">All locations</option></select></label>
                <button class="bar-button" type="submit">Search</button>
            </form>
            <p id="catalogue-status" role="status" aria-live="polite"></p><div class="catalogue-grid" id="product-grid"></div><button class="bar-button outline catalogue-more" id="catalogue-more" type="button" hidden>Show more products</button>
        </section>
        <section class="bar-section public-experience" id="experience"><div class="bar-callout"><div><span class="bar-eyebrow"><?=h($p['callout_eyebrow'])?></span><h2><?=h($p['callout_title'])?><br><?=h($p['callout_emphasis'])?></h2><p><?=h($p['callout_text'])?></p></div><a class="bar-button outline" href="#discover"><?=h($p['primary'])?> &uarr;</a></div></section>
        <section class="bar-section site-contact-panel" id="visit"><span class="bar-eyebrow">WE LOOK FORWARD TO WELCOMING YOU</span><h2>Come say hello.</h2><p class="bar-section-intro">Visit us in store or get in touch with the team to plan your visit.</p><div class="site-contact"><a data-phone hidden></a><a data-email hidden></a><span data-address hidden></span><span data-hours hidden></span></div></section>
        <noscript><style>.site-menu-toggle{display:none}.bar-nav-links{display:flex}</style><p class="noscript-note">The main page is available without JavaScript. Enable JavaScript to use navigation controls, configured contact details and the optional live product catalogue.</p></noscript>
    </main>
    <footer class="bar-footer"><span>&copy; <span data-year>2026</span> <span data-business-name><?=h($name)?></span></span><span><?=h($p['closing'])?></span><a href="/shop/public/login" data-portal>Staff portal &rarr;</a></footer>
</div>
</body>
</html>
<?php
 file_put_contents($dir.'/index.html',ob_get_clean());
 file_put_contents($dir.'/README.md',"# ".$p['name']." website\n\nThis folder is a complete static website. Upload its contents into your domain's document root. No PHP or build step is required.\n\nEdit `assets/js/site-config.js` to set the business name, phone, email, address, opening hours and POS base URL. Leave unknown contact details blank; they stay hidden.\n\nThe live catalogue is optional and off by default. Enable it with `enableCatalogue: true` and point `catalogueUrl` at your POS public catalogue endpoint. The endpoint controls which business products are published.\n\nWhen testing inside the project's main-domain-site folder on localhost, the staff portal is detected automatically. On hosting, the default POS base URL is `/shop/public`; change it to the actual POS installation URL.\n\nStandalone copies do not read Laravel Business Settings automatically. Update this configuration file when public details change.\n");
 echo 'Created '.$dir.PHP_EOL;
}

// Rebuild the gallery from the same business-type registry as the websites.
ob_start();
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Explore Amenador website templates for mixed retail, hardware, pharmacy, supermarket and hospitality businesses."><title>Amenador | Website templates</title><link rel="stylesheet" href="template-gallery.css"></head>
<body><main><span class="eyebrow">AMENADOR / WEBSITE COLLECTION</span><h1>A home for every<br>kind of business.</h1><p class="lead">Choose General Goods for a mixed business, or pick a specialist design. Every version is a complete standalone website.</p><div class="templates">
<?php foreach($folders as $key=>$folder): $p=$presets[$key]; ?>
<a href="<?=h($folder)?>/" class="template <?=h($key)?>"><div class="art"><img src="<?=h($folder)?>/assets/img/<?=h($p['image'])?>" alt="<?=h($p['alt'])?>"></div><div class="copy"><span><?=h($p['eyebrow'])?></span><h2><?=h($key==='general'?'General Goods / Mixed Business':$p['name'])?></h2><p><?=h($p['description'])?></p><strong>Open preview &rarr;</strong></div></a>
<?php endforeach; ?>
</div><footer>Each folder is a standalone website. Edit its site-config.js file before deployment.<a href="index.html">Existing main page &rarr;</a></footer></main></body>
</html>
<?php
file_put_contents('main-domain-site/templates.html',ob_get_clean());
