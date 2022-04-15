<?php
/*
Script ini dibuat oleh Akbar Yahya (Yuki) dengan style sendiri dan terbuka untuk semuanya untuk modifikasi tapi mohon untuk tidak dijual script ini dan hapus pesan ini, jika saya lihat ada yang hapus atau jual script ini akan saya tutup repo ini untuk umum.
*/
ini_set('memory_limit', '8G');
require 'vendor/autoload.php';
require 'lib/tool.php';

// Config file
if (!file_exists("config.php")) {
    require 'config.sampel.php';
} else {
    require 'config.php';
}

$info = getopt("", ["doing:","resolution:","link_source:",'limit_ongoing:','limit_dl:','autodl:','notif:']);

$isdoing         = @$info['doing'];
$set_resolution  = @$info['resolution'];
$set_link_source = @$info['link_source'];
$set_autodl      = @$info['autodl'] ?: false;
$set_notif       = @$info['notif'] ?: false;
$set_limit_ongoing     = @$info['limit_ongoing'] ?: 1;
$set_limit_dl          = @$info['limit_dl'] ?: 1;

$GoRSS = new RSSAnime($config);

if ($isdoing == "tes123") {
    print_r($GoRSS->desudrive());
//$raww = Bot::DiscordWbhooks($config['Discord_Wbhooks'],array("content" => "HOLA", "username" => "Bot"));
    //print_r($raww);
} elseif ($isdoing == "dl") {
    $GoRSS->dl($set_resolution, $set_link_source, $set_limit_dl, $set_limit_ongoing, $set_autodl, $set_notif);
}

/*
$n = "595030%2";
$b = "595030%3";
$z = "595033";
$nt = explode('%', $n);
$bt = explode('%', $b);
echo(($nt[0] % $nt[1]) + ($bt[0] % $bt[1]) + $z - 3);
*/
//nilai asli 595031

class RSSAnime
{
    private $DB_DL;
    private $host = "mongodb://localhost:27017";

    private $config;

    public function __construct($config = null)
    {
        $this->config = $config;
        //$this->db = new MongoDB\Client($this->host);
        //$this->DB_DL  = $this->db->dl->link;
    }

    public function dl($set_resolution="", $set_link_source="", $set_limit_dl=1, $set_limit_ongoing=1, $set_autodl=false, $set_notif=false)
    {
        $source = array();
        $source['source']['otakudesu'] = $this->otakudesu($set_limit_ongoing);

        // Get Source
        foreach ($source['source'] as $gsource) {
            if (empty(@$gsource['data'])) {
                //print_r($source);
                echo "Error:".$gsource['error']['status'];
                continue; // skip source
            }

            // Get episode
            foreach ($gsource['data'] as $gep) {
                // Get Format
                //print_r($gep);
                $name_nm = $gep['name'];
                $name_nx = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $name_nm);
                foreach ($gep['episode'] as $gdetail) {
                    $name = $gdetail['name'];
                    $link = $gdetail['link'];
                    echo "-> $name ($link)" . PHP_EOL;
                    // Format file
                    usort($gdetail['DL'], function ($a, $b) {
                        return @$b['resolution'] <=> @$a['resolution'];
                    });
                    $tmp_limit_dl=0;
                    foreach ($gdetail['DL'] as $gdl) {
                        $format = $gdl['format'];
                        $resolu = $gdl['resolution'];

                        // Fiter Format
                        if (!empty($set_resolution)) {
                            $set_resolutionn  = explode(',', $set_resolution);
                            if (!contains($resolu, $set_resolutionn)) {
                                continue;
                            }
                        }
                        if ($tmp_limit_dl >= $set_limit_dl) {
                            break;
                        }

                        echo "--> $format / $resolu" . PHP_EOL;
                        //print_r($gdl);

                        // Source link
                        foreach ($gdl['link'] as $glk) {
                            $lname = $glk['name'];
                            $llink = $glk['link'];
                            // Fiter link
                            if (!empty($set_link_source)) {
                                $set_link_sourcen = explode(',', $set_link_source);
                                if (!contains($lname, $set_link_sourcen)) {
                                    continue;
                                }
                            }
                            echo "---> $lname ($llink)" . PHP_EOL;
                            if ((bool)$set_autodl == true) {
                                $setpo   = @$this->link($llink); //TODO: buat cek ini hanya jika belum ada filenya jadi gak kena rate limit
                                $gtrealx = @$setpo['dl'];
                                if (!empty($gtrealx)) {
                                    $tmp_limit_dl++;

                                    $fileName = @$setpo['fileName'];
                                    echo "----> DL: $gtrealx ($fileName)" . PHP_EOL;
                                    $linkfd = "dl/$name_nx/";

                                    // some stupid judul
                                    $linkfd = str_replace(".", "", $linkfd);

                                    // CHECK FOLDER IF NO FOUND MAKE IT
                                    if (!file_exists($linkfd)) {
                                        echo "----> DL: No Found Folder so Make it: $name_nx" . PHP_EOL;
                                        mkdir($linkfd, 0777, true);
                                    }

                                    // CHECK IF link.txt found folder start use it
                                    $checklink = $linkfd."link.txt";
                                    if (file_exists($checklink)) {
                                        $linkz = file_get_contents($checklink);
                                        echo "----> DL: OK FOUND LINK.TXT: $linkz" . PHP_EOL;
                                        $linkfd = $linkz."/"; // if not found / try add it
                                    }
                                    
                                    $spot=$linkfd.$fileName;
                                    $send_notif=true;

                                    // Downloader
                                    if (!file_exists($spot)) {
                                        $dw = new Downloader($gtrealx, $spot);
                                        $dw->download();
                                        echo "----> DL: DONE ;)" . PHP_EOL;
                                    } else {
                                        echo "----> DL: file already exists" . PHP_EOL;
                                        $send_notif=false; //for debug
                                    }

                                    if ($send_notif) {
                                        if ($set_notif) {
                                            echo "-----> Start Send Notifications" . PHP_EOL;

                                            // Tambah URL Server, agar bisa di akses lewat cloud server?
                                            if (!empty(@$this->config['Server_URL_Local'])) {
                                                if (!file_exists($checklink)) {
                                                    // Localhost
                                                    $spot=$this->config['Server_URL_Local'].$spot;
                                                } else {
                                                    // Cloud Server, skip
                                                }
                                            }

                                            if (!empty(@$this->config['Discord_Wbhooks'])) {
                                                echo "------> Send to Discord" . PHP_EOL;
                                                Bot::DiscordWbhooks($this->config['Discord_Wbhooks'], array("content" => "Anime dengan judul $name sudah selesai di download, link-nya untuk nonton-nya $spot", "username" => "Bot"));
                                            }
                                        }
                                    }
                                } else {
                                    echo "----> DL: No Found" . PHP_EOL;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function link($url)
    {
        // wtf
        $body = "";
        if (strpos($url, 'desudrive') !== false) {
            $body = $this->desudrive($url);
            $url = $body['header'][0]['location'];
        }

        if (strpos($url, 'zippyshare') !== false) {
            return $this->zippyshare($url, $body);
        }

        return "";
    }
    public function desudrive($url="https://desudrive.com/go/?id=NkVNRnlNQjM4OUk5cTFZRVRyc3hDS01uWDMwbVRaY0YzOUZpVTdCWHdkRTFOL2hqbFNudmZraXJvc1ZVU1JWZyt3PT0=")
    {
        return SEND($url);
    }
    // https://www85.zippyshare.com/v/ZbBrv80H/file.html - NEW RUMUS " + (902256 % 51245 + 902256 % 913) + "
    public function zippyshare($url="https://www87.zippyshare.com/v/SGTX2ZT5/file.html", $raw="")
    {
        $data= array();
        $data['url']=$url;
        $url_real="";

        // GET POST
        if (empty($raw)) {
            $raw = SEND($url);
        }
    
        if ($raw['code']==200) {
            $raw_body = $raw['body'];
            // GET HTML
            $raw_linkz = voku\helper\HtmlDomParser::str_get_html($raw_body);
            $javaScript = $raw_linkz->find("#lrbox > div:nth-child(2) > div:nth-child(2) > div > script")[0]->plaintext;
            $fileName   = $raw_linkz->find("#lrbox > div:nth-child(2) > div:nth-child(1) > font:nth-child(4)")[0]->plaintext;

            $data['fileName']=$fileName;
            $data['script']=$javaScript;
            
            $formula = "";

            // coba trik pertama
            try {
                $n = cut_str($javaScript, "var n = ", ';');
                $b = cut_str($javaScript, "var b = ", ';');
                $z = cut_str($javaScript, "var z = ", ';');
                $nt = explode('%', $n);
                $bt = explode('%', $b);
                $formula = (($nt[0] % $nt[1]) + ($bt[0] % $bt[1]) + $z - 3);
            } catch (\Throwable $th) {

                // Coba trik kedua jika gagal
                try {
                    $satu = strpos($javaScript, '"+(');
                    $hasilSatu=substr($javaScript, $satu);
                    $dua = strpos($hasilSatu, "%");
                    $hasilDua = substr($hasilSatu, 0, $dua);
                    $satu = strpos($javaScript, '+"/');
                    $hasilSatu = substr($javaScript, $satu);
                    $dua = strpos($hasilSatu, '";');
                    $hasilDua = substr($hasilSatu, 0, $dua);
                    $filenameUrl = substr($hasilDua, 3);
                    $mString = explode("=", $filenameUrl)[1];
                    $math = explode("+", $mString);
                    $mathResult = $math[1]."+".$math[2];
                    $mathResult = substr(trim($mathResult), 1, -1);
                    $first = explode("+", $mathResult)[0];
                    $secnd = explode("+", $mathResult)[1];
                    $fst_res = explode("%", $first)[0];
                    $sec_res = explode("%", $first)[1];
                    $tird_res = explode("%", $secnd)[0];
                    $forth_res = explode("%", $secnd)[1];
                    $formula = $fst_res % $sec_res + $tird_res % $forth_res;
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }

            $url = str_replace("/v/", "/d/", $url);
            $url = str_replace("/file.html", "", $url);

            // Jika ada formula
            if (!empty($formula)) {
                $url_real = "$url/$formula/$fileName";
            }
        }
        $data['dl']=$url_real;
        //$data['formula']=$formula;
        return $data;
    }
    public function rapidleech($url="https://www87.zippyshare.com/v/SGTX2ZT5/file.html", $server="https://s2.rapidleech.gq")
    {
        $DATA = http_build_query([
            "audl" => "doum",
            "link" => $url,
           // "cookie"=>"%85%F7%88%5Bv%F3d%EF%8Btif%D8%1D%A3%83T%FA%EA%0F%96%AF%D9%D9%5C%28%22%C1%18%98%7D%3F%9E6%0F%C5%97%1C%23%96%22%8F%3D%FF%90%C8%02%DF%13s%C0%A6%84%07%CA-%C8%BEk%EFn%FD%09%06%95%26%01%EA%A6%24%E3%E6",
           // "cookie_encrypted"=>1,
           // "referer"=>$url,
            "cleanname"=>1
        ]);
        $raw = SEND($server."/index.php", array(
            'metode' => "GET",
            'post' => $DATA
        ));
        return $raw;
    }
    public function otakudesu($limit_ongoing=3, $limit_episode=1)
    {
        $data = array();
        // GET ONGOING, TODO: batch mode
        $raw = SEND("https://otakudesu.info/ongoing-anime/");
        $document = voku\helper\HtmlDomParser::str_get_html($raw['body']);
        $list = $document->find("div.venz > ul > li");
        $count=0;
        foreach ($list as $anime) {
            if ($count >= $limit_ongoing) {
                break;
            }
            $name  = $anime->find("h2[class=jdlflm]")[0]->plaintext;
            $epz   = $anime->find("div[class=epz]")[0]->plaintext;
            $link  = $anime->find("a")[0]->getAttribute('href');
            $data['data'][$count]['name']=$name;
            $data['data'][$count]['ep_num']=$epz;
            $data['data'][$count]['link']=$link;
            // GET LIST EPISODE
            $raw_ep  = SEND($link);
            $fd_ep    = voku\helper\HtmlDomParser::str_get_html($raw_ep['body']);
            $get_ep   = $fd_ep->find("div.episodelist > ul > li");
            $countep = 0;
            foreach ($get_ep as $ep) {
                if ($countep >= $limit_episode) {
                    break;
                }
                $glink = $ep->find("a")[0];
                $nama_ep  = $glink->plaintext;
                if (strpos($nama_ep, 'BATCH') !== false) {
                    continue;
                }
                $link_ep  = $glink->getAttribute('href');
                $data['data'][$count]['episode'][$countep]['name']=$nama_ep;
                $data['data'][$count]['episode'][$countep]['link']=$link_ep;
                
                // GET DETAILS EPISODE
                $raw_detail  = SEND($link_ep);
                $fd_dl    = voku\helper\HtmlDomParser::str_get_html($raw_detail['body']);

                $get_dl   = $fd_dl->find("div.download > ul > li > ul > li");

                // jika kosong skip
                if (empty($get_dl->plaintext)) {
                    $get_dl   = $fd_dl->find("div.download > ul > li");
                }

                $countdl=0;

                foreach ($get_dl as $dl) {
                    $type      = $dl->find("strong")[0]->plaintext;
                    $ukur      = $dl->find("i")[0]->plaintext;
                    $ntype     = explode(' ', $type);
                    
                    // Count Link
                    $countlink=0;

                    // GET LINK DOWNLOAD
                    $dl_linl   = $dl->find("a");
                    foreach ($dl_linl as $zglink) {
                        $nmser  = $zglink->plaintext;
                        $linkdl = $zglink->getAttribute('href');
                        // jika tidak ada link skip
                        if (!empty($linkdl)) {
                            $data['data'][$count]['episode'][$countep]['DL'][$countdl]['link'][$countlink]['name']=$nmser;
                            $data['data'][$count]['episode'][$countep]['DL'][$countdl]['link'][$countlink]['link']=$linkdl;
                        } else {
                            continue;
                        }
                        $countlink++;
                    }

                    // Jika Link ada 1 tampil.
                    if ($countlink >= 1) {
                        $data['data'][$count]['episode'][$countep]['DL'][$countdl]['format']=$ntype[0];
                        $data['data'][$count]['episode'][$countep]['DL'][$countdl]['resolution']=intval($ntype[1]);
                        $data['data'][$count]['episode'][$countep]['DL'][$countdl]['size']=$ukur;
                    } else {
                        continue;
                    }
                    $countdl++;
                }
                $countep++;
                //break;
            }
            $count++;
            //break;
        }
        if ($count == 0) {
            $data['error'] = $raw;
        }
        return $data;
    }
}
