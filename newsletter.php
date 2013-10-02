<?php
require('lib/simplepie/autoloader.php');
require('lib/sendgrid-php/SendGrid_loader.php');

# Settings
define('YOUR_FEED_URL', ''); # Base tumblr URL without http://, e.g. foo.tumblr.com
define('SENDGRID_USER', ''); # Sendgrid username
define('SENDGRID_PASS', ''); # Sendgrid password
define('TO_EMAIL', ''); # Digest recipient's email
define('FROM_EMAIL', ''); # Digest sender's email
define('FROM_NAME', ''); # Digest sender's name

$feed = new SimplePie();
$feed->set_feed_url(YOUR_FEED_URL);
$feed->set_cache_location(dirname(__FILE__) . '/cache');
$feed->init();

$postedLinks = array();
$oneWeekAgo = strtotime('-1 week', strtotime('now'));

$items = $feed->get_items();
foreach($items as $item) {
    if($item->get_gmdate('U') > $oneWeekAgo) {
        $postedLinks[] = $item->get_content();
    }
}

if(count($postedLinks) > 0) {
    $style = file_get_contents(dirname(__FILE__) . '/newsletter.css');
    $contents = "<ul><li>".implode("</li><li>", $postedLinks)."</li></ul>";
    $footer = sprintf('<p class="footer">Read more at <a href="http://%1$s"></a>%1$s</p>', YOUR_FEED_URL);
    $body = sprintf("<style>%s</style>%s%s", $style, $contents, $footer);

    $sendgrid = new SendGrid(SENDGRID_USER, SENDGRID_PASS);
    $mail = new SendGrid\Mail();
    $mail->setFrom(FROM_EMAIL)->
           setFromName(FROM_NAME)->
           addTo(TO_EMAIL)->
           setSubject(sprintf('Tumblr RSS digest for week %s', date('W', $oneWeekAgo)))->
           setHtml($body);
    $sendgrid->smtp->send($mail);
    printf("%s - Sent newsletter\n", date('Y-m-d H:i:s'));
} else {
    printf("%s - No news links found. Newsletter not sent.\n", date('Y-m-d H:i:s'));
}
