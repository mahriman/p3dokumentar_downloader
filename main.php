<?php
 
//Derive Settings
$rss_file = basename($rss_url);

// Kontrollera att $data_folder finns, annars försök skapa den
if(!is_dir($data_folder))
  {
    echo "[info] \$data_folder '" . $data_folder . "' does not exist, creating...\n";
    exec("mkdir -p \"" . $data_folder . "\"", $output, $exit_status); 
    if($exit_status !== 0)
    {
      echo "[!] Unable to create directory '" . $data_folder . "', exiting (1)!\n";
      exit(1);
    }
  }

//Download RSS File
shell_exec("wget --quiet --no-check-certificate -N " . $rss_url . " -P \"" . $data_folder . "\"");

//Open RSS File as XML
$rss_xml=simplexml_load_file($data_folder."/".$rss_file) or die("Error opening XML");

//Get Podcast URLs from the XML
$podcasts_to_download = array();
$i=0;
foreach($rss_xml->channel->children() as $podcast) {
    if($i <= $n_podcasts_to_download)
      {
        $podcast_title = (string) $podcast->title[0];

        # Hämta inte om pubDate är null
        if($podcast->pubDate[0] === NULL) { continue; }

        # Hämta inte om titeln är tom
        if($podcast_title === "") { continue; }

        // Hämta inte PODDTIPS, NY PODD, VÅRENS SLÄPP, KOMMANDE P3 DOKUMENTÄRER
        if(preg_match('/PODDTIPS/', $podcast_title)) { continue; }
        if(preg_match('/NY PODD/', $podcast_title)) { continue; }
        if(preg_match('/VÅRENS SLÄPP/', $podcast_title)) { continue; }
        if(preg_match('/KOMMANDE P3 DOKUMENTÄRER/', $podcast_title)) { continue; }

        // Ta bort överflödiga whitespaces i början och slutet av titeln
        $podcast_title = ltrim(rtrim($podcast_title));

        // Hämta inte avsnittet med titeln "P3 Dokumentär 2015-04-12 kl. 18.03" som är en repris av DC 3an-avsnittet (avsnitt 897557)
        if(preg_match('/P3 Dokumentär 2015-04-12 kl. 18.03/', $podcast_title)) { continue; }

        // Byt ut namn till egenpåhittade för avsnitt som saknar titel
        $podcast_title = preg_replace('/P3 Dokumentär 2016-01-10 kl. 18.03/', 'Stormen Gudrun och skogsskadorna', $podcast_title);

        // Ta bort "P3 Dokumentär" från titeln
        $podcast_title = preg_replace('/P3 Dokumentär /', '', $podcast_title);

        // Flytta "nyhetsspecial: " till slutet av titeln
        $podcast_title = preg_replace('/(nyhetsspecial): (.*)/i', '\2 - \1', $podcast_title);

        // Ersätt tecken i titlar med tecken som Windows tillåter i filnamnet
        // Byt ut "Del x/y" till "Del x av y" och flytta till slutet av titeln
        $podcast_title = preg_replace('/Del ([0-9])\/([0-9]): (.*)/', '\3 - Del \1 av \2', $podcast_title);
        // Byt ut ampersand & till o (t. ex "Vår Krog & Bar" -> "Vår Krog o Bar") 
        $podcast_title = preg_replace('/\&/', 'o', $podcast_title);
        // Byt ut snedsträck / till bindestreck (t.ex "M/S Scandinavian Star" -> "M-S Scandinavian Star", "1969/70" till "1969-70)
        $podcast_title = preg_replace('/\//', '-', $podcast_title);
        // Ta bara bort \, <, >, |, ?, *
        $podcast_title = preg_replace('/\\\/', '', $podcast_title);
        $podcast_title = preg_replace('/\</', '', $podcast_title);
        $podcast_title = preg_replace('/\>/', '', $podcast_title);
        // Ersätt kolon : efter siffror med ingenting (t. ex "DC3:an" -> "DC3an")
        $podcast_title = preg_replace('/([0-9]):/', '\1', $podcast_title);
        // Ersätt kolon : med " -"
        $podcast_title = preg_replace('/:/', ' -', $podcast_title);
        // Ersätt frågetecken ? med inget (t. ex "Vem får bo i Europa?" -> "Vem får bo i Europa")
        $podcast_title = preg_replace('/\?/', '', $podcast_title);
        // Ersätt pipe-tecken | med inget 
        $podcast_title = preg_replace('/\|/', '', $podcast_title);
        // Ersätt asterisk-tecken * med inget 
        $podcast_title = preg_replace('/\*/', '', $podcast_title);
        // Ersätt citations-tecken " med apostrof ' med begynnande backslash \' eftersom output-filens namn omges av apostrofer vid wget-hämtning senare  
        $podcast_title = preg_replace('/\"/', '\'', $podcast_title);

        $podcast_pubyear = date("Y", strtotime($podcast->pubDate[0]));
        $podcast_pubdate = date("Ymd", strtotime($podcast->pubDate[0]));
        $podcast_url = $podcast->enclosure[0]['url'];
        $podcast_url = preg_replace('/\?.*/', '', $podcast_url);
        $podcasts_to_download[$i]['link'] = $podcast_url;
        # Sätt alltid första bokstaven i titeln till stor bokstav
        $podcasts_to_download[$i]['title'] = ucfirst($podcast_title);
        $podcasts_to_download[$i]['pubyear'] = $podcast_pubyear;
        $podcasts_to_download[$i]['pubdate'] = $podcast_pubdate;
        $i++;
      } 
}

//Check if podcast episode meet the criteria (not already downloaded), download 
foreach($podcasts_to_download as $item){

    $download_link = $item['link'];
    $download_year = $item['pubyear'];
    $download_filename = $item['pubdate'] . " - " . $item['title'] . ".mp3";

    //Comment these out in PROD - only for debugging
    #var_dump($item);
    #echo $download_link . "\n";
    #echo $download_year . "\n";
    #echo $download_filename . "\n";
    #echo "---\n";

    $continue = true;

    // Kontrollera att $download_path finns, annars försök skapa den
    if(!is_dir($download_path))
    {
      echo "[info] Directory '" . $download_path . "' does not exist, creating...\n";
      exec("mkdir -p \"" . $download_path . "\"", $output, $exit_status); 
      if($exit_status !== 0)
      {
        echo "[!] Unable to create directory '" . $download_path . "', exiting (2)!\n";
        exit(2);
      }
    }

    // Kontrollera att $download_path/<årtal> finns, annars försök skapa den
    if(!is_dir($download_path."/".$download_year))
    {
      echo "[info] Directory '" . $download_path."/".$download_year . "' does not exist, creating...\n";
      exec("mkdir -p \"" . $download_path."/".$download_year . "\"", $output, $exit_status); 
      if($exit_status !== 0)
      {
        echo "[!] Unable to create directory '" . $download_path."/".$download_year . "', exiting (3)!\n";
        exit(3);
      }
    }

    if(file_exists($download_path."/".$download_year."/".$download_filename ))
    {
        $continue = false;
        echo "[info] '" . $download_path."/".$download_year."/".$download_filename . "' already downloaded, skipping... \n";
    }
    
    if($continue == true)
    {
        echo "[+] Downloading '". $download_filename . "' to " . $download_path."/".$download_year . "...\n";
        exec("wget --quiet --no-check-certificate -O \"" . $download_path."/".$download_year . "/" . $download_filename . "\"" . " " . $item['link'], $download_output, $download_exit_status);
        if($download_exit_status !== 0)
        {
          echo "[!] wget exited with non-zero exit code:\n";
          foreach($download_output as $error_output)
          {
            echo $error_output;
          }
          echo "Fatal error, exiting (4)!";
          exit(4);
        }
    }
}

?>
