<?php
$file = "WiseService.php";
$content = file_get_contents($file);

// Update API_WEBSITES_URL usage in reportSync
$content = preg_replace('/self::API_WEBSITES_URL/', 'WiseCrypt::getEndpoint("websites")', $content);

// Update all other self::API_... usages if they exist elsewhere
$content = preg_replace('/self::API_LICENSE_ACTIVATE_URL/', 'WiseCrypt::getEndpoint("activate")', $content);
$content = preg_replace('/self::API_PRODUCT_URL/', 'WiseCrypt::getEndpoint("product")', $content);
$content = preg_replace('/self::API_BROADCAST_MESSAGES_URL/', 'WiseCrypt::getEndpoint("broadcast")', $content);

file_put_contents($file, $content);
echo "Sync URL and others Updated";
?>
