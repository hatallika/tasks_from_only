<?php
$arUrlRewrite=array (
  2 => 
  array (
    'CONDITION' => '#^/online/([\\.\\-0-9a-zA-Z]+)(/?)([^/]*)#',
    'RULE' => 'alias=$1',
    'ID' => NULL,
    'PATH' => '/desktop_app/router.php',
    'SORT' => 100,
  ),
  1 => 
  array (
    'CONDITION' => '#^/video([\\.\\-0-9a-zA-Z]+)(/?)([^/]*)#',
    'RULE' => 'alias=$1&videoconf',
    'ID' => NULL,
    'PATH' => '/desktop_app/router.php',
    'SORT' => 100,
  ),
  21 => 
  array (
    'CONDITION' => '#^/examples/my-components/news/#',
    'RULE' => NULL,
    'ID' => 'demo:news',
    'PATH' => '/examples/my-components/news_sef.php',
    'SORT' => 100,
  ),
  4 => 
  array (
    'CONDITION' => '#^\\/?\\/mobileapp/jn\\/(.*)\\/.*#',
    'RULE' => 'componentName=$1',
    'ID' => NULL,
    'PATH' => '/bitrix/services/mobileapp/jn.php',
    'SORT' => 100,
  ),
  6 => 
  array (
    'CONDITION' => '#^/bitrix/services/ymarket/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/bitrix/services/ymarket/index.php',
    'SORT' => 100,
  ),
  10 => 
  array (
    'CONDITION' => '#^/e-store/books/reviews/#',
    'RULE' => '',
    'ID' => 'bitrix:news',
    'PATH' => '/e-store/books/reviews/index.php',
    'SORT' => 100,
  ),
  11 => 
  array (
    'CONDITION' => '#^/e-store/xml_catalog/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog',
    'PATH' => '/e-store/xml_catalog/index.php',
    'SORT' => 100,
  ),
  13 => 
  array (
    'CONDITION' => '#^/communication/forum/#',
    'RULE' => '',
    'ID' => 'bitrix:forum',
    'PATH' => '/communication/forum/index.php',
    'SORT' => 100,
  ),
  16 => 
  array (
    'CONDITION' => '#^/communication/blog/#',
    'RULE' => '',
    'ID' => 'bitrix:blog',
    'PATH' => '/communication/blog/index.php',
    'SORT' => 100,
  ),
  3 => 
  array (
    'CONDITION' => '#^/online/(/?)([^/]*)#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/desktop_app/router.php',
    'SORT' => 100,
  ),
  0 => 
  array (
    'CONDITION' => '#^/stssync/calendar/#',
    'RULE' => '',
    'ID' => 'bitrix:stssync.server',
    'PATH' => '/bitrix/services/stssync/calendar/index.php',
    'SORT' => 100,
  ),
  7 => 
  array (
    'CONDITION' => '#^/content/articles/#',
    'RULE' => '',
    'ID' => 'bitrix:news',
    'PATH' => '/content/articles/index.php',
    'SORT' => 100,
  ),
  15 => 
  array (
    'CONDITION' => '#^/content/gallery/#',
    'RULE' => '',
    'ID' => 'bitrix:photogallery_user',
    'PATH' => '/content/gallery/index.php',
    'SORT' => 100,
  ),
  12 => 
  array (
    'CONDITION' => '#^/personal/lists/#',
    'RULE' => '',
    'ID' => 'bitrix:lists',
    'PATH' => '/personal/lists/index.php',
    'SORT' => 100,
  ),
  9 => 
  array (
    'CONDITION' => '#^/e-store/books/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog',
    'PATH' => '/e-store/books/index.php',
    'SORT' => 100,
  ),
  14 => 
  array (
    'CONDITION' => '#^/content/photo/#',
    'RULE' => '',
    'ID' => 'bitrix:photogallery',
    'PATH' => '/content/photo/index.php',
    'SORT' => 100,
  ),
  8 => 
  array (
    'CONDITION' => '#^/content/news/#',
    'RULE' => '',
    'ID' => 'bitrix:news',
    'PATH' => '/content/news/index.php',
    'SORT' => 100,
  ),
  17 => 
  array (
    'CONDITION' => '#^/content/idea/#',
    'RULE' => '',
    'ID' => 'bitrix:idea',
    'PATH' => '/content/idea/index.php',
    'SORT' => 100,
  ),
  19 => 
  array (
    'CONDITION' => '#^/club/gallery/#',
    'RULE' => '',
    'ID' => 'bitrix:photogallery_user',
    'PATH' => '/club/gallery/index.php',
    'SORT' => 100,
  ),
  22 => 
  array (
    'CONDITION' => '#^/content/faq/#',
    'RULE' => '',
    'ID' => 'bitrix:support.faq',
    'PATH' => '/content/faq/index.php',
    'SORT' => 100,
  ),
  20 => 
  array (
    'CONDITION' => '#^/club/forum/#',
    'RULE' => '',
    'ID' => 'bitrix:forum',
    'PATH' => '/club/forum/index.php',
    'SORT' => 100,
  ),
  5 => 
  array (
    'CONDITION' => '#^/rest/#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/bitrix/services/rest/index.php',
    'SORT' => 100,
  ),
  18 => 
  array (
    'CONDITION' => '#^/club/#',
    'RULE' => '',
    'ID' => 'bitrix:socialnetwork',
    'PATH' => '/club/index.php',
    'SORT' => 100,
  ),
);
