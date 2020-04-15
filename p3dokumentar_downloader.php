<?php

/* P3 Dokumentär Downloader
   Based on PHP-RSS-Podcast-Downloader by Meridien 
   <https://github.com/Meridien/PHP-RSS-Podcast-Downloader>

*** How to use *** 
   1. Save files main.php and p3dokumentar_downloader.php in directory
   2. Edit settings below
   3. Run with php p3dokumentar_downloader.php

   Files are saved in $download_folder/YYYY/YYYYmmdd - Episode title.mp3

*** Settings here - this is the only part you (should) need to edit *** */ 

// Base path, var php-filerna finns och xml-filen laddas ner. 
$data_folder = "/home/dir-admins/mahriman/scripts/p3dokumentar_downloader";
// I $download_path läggs alla avsnitt i $download_path/<årtal>/<episod>.mp3 
$download_path = "/datastore-mirrored/Music/mp3/P3 Dokumentär";
# URL till P3 Dokumentärs XML-fil. 
$rss_url = "https://api.sr.se/api/rss/pod/3966";
# Framtidssäkrat antal avsnitt att ladda ner :) 
$n_podcasts_to_download = 1000;

/*---------------------------------------------------------------------
 * main.php has all the code and custom alterations from the original script
*/

require_once($data_folder."/".'main.php');

?>
