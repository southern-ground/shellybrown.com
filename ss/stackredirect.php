<?PHP
ob_start(); // Kill output buffering;

/*
 *
 * This page is a redirect for sharing purposes.
 *
 * It pulls an underscore-delimited list of skus
 * ... then writes a cookie with those translated
 * ... then redirects to the stack.
 *
 * */

define(CANONICAL_URL, "http://www.shellybrown.com/stack/");
define(CANONICAL_REDIRECT_URL, "http://www.shellybrown.com/ss/ref=");
define(COOKIE_NAME_READ, "com_shellybrown");
define(COOKIE_NAME_WRITE, "com.shellybrown");

$skus = explode("_", urldecode($_GET['skus'] ?: "")); // Array.

if (count($skus) > 0) {

    // Write the share-specific cookie:
    setcookie("sharedStack", implode(' ', $skus), time() + 60*60*24*7, '/');

}

ob_end_flush(); // Resume output buffering;
?>
<htm>
    <header>
        <meta property="og:title" content="Check out my stack!"/>
        <meta property="og:type" content="product.group"/>
        <meta property="og:image" content="http://www.shellybrown.com/img/stack-share.jpg"/>
        <meta property="og:image:type" content="image/jpeg"/>
        <meta property="og:image:width" content="1200"/>
        <meta property="og:image:height" content="630"/>
        <meta property="og:site_name" content="Shelly Brown"/>
        <meta property="og:description" content="I've created a stack of bracelets on Shelly Brown's website!"/>
    </header>
    <body>

    <noscript>
        <meta http-equiv="refresh" content="0; URL='<?= CANONICAL_URL ?>'"/>
    </noscript>

    <script>
        window.location = "<?=CANONICAL_URL?>";
    </script>

    </body>
</htm>