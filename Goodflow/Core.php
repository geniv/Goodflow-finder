<?php
/*
 * core.php
 *
 * Copyright 2014 geniv
 *
 */

namespace Goodflow;

/**
 * hlavni trida s nejpouzivanenejsimi statickymi metodami
 * - nevytvoritelna (abstraktni)
 *
 * @package unstable
 * @author geniv
 * @version 1.06
 */
abstract class Core {

//TODO test!
    public static function getBasePath(\Nette\Http\Request $request) {
        return rtrim($request->getUrl()->getBasePath(), '/');
    }


    /**
     * nacitani opravneni souboru
     *
     * @param string path cesta souboru
     * @param bool full pro plny format (rwx) true, pro octalovy 0751 false
     * @return string opravneni souboru v textovem nebo octalovem tvaru
     */
    public static function getFilePermissions($path, $full = false) {
        $result = NULL;
        $perms = fileperms($path);
        if ($full) {
            if (($perms & 0xC000) == 0xC000) {
                // Socket
                $info = 's';
            } elseif (($perms & 0xA000) == 0xA000) {
                // Symbolic Link
                $info = 'l';
            } elseif (($perms & 0x8000) == 0x8000) {
                // Regular
                $info = '-';
            } elseif (($perms & 0x6000) == 0x6000) {
                // Block special
                $info = 'b';
            } elseif (($perms & 0x4000) == 0x4000) {
                // Directory
                $info = 'd';
            } elseif (($perms & 0x2000) == 0x2000) {
                // Character special
                $info = 'c';
            } elseif (($perms & 0x1000) == 0x1000) {
                // FIFO pipe
                $info = 'p';
            } else {
                // Unknown
                $info = 'u';
            }
            // Owner
            $info .= (($perms & 0x0100) ? 'r' : '-');
            $info .= (($perms & 0x0080) ? 'w' : '-');
            $info .= (($perms & 0x0040) ?
                        (($perms & 0x0800) ? 's' : 'x' ) :
                        (($perms & 0x0800) ? 'S' : '-'));
            // Group
            $info .= (($perms & 0x0020) ? 'r' : '-');
            $info .= (($perms & 0x0010) ? 'w' : '-');
            $info .= (($perms & 0x0008) ?
                        (($perms & 0x0400) ? 's' : 'x' ) :
                        (($perms & 0x0400) ? 'S' : '-'));
            // World
            $info .= (($perms & 0x0004) ? 'r' : '-');
            $info .= (($perms & 0x0002) ? 'w' : '-');
            $info .= (($perms & 0x0001) ?
                        (($perms & 0x0200) ? 't' : 'x' ) :
                        (($perms & 0x0200) ? 'T' : '-'));
            $result = $info;
        } else {
            $result = substr(sprintf('%o', $perms), -4);
        }
        return $result;
    }


    /**
     * vraceni vlastnika souboru
     *
     * @param string path cesta souboru
     * @param bool numerical pro ciselne id uzivatele true, jinak hleda v posixu
     * @return string vlastnik souboru
     */
    public static function getFileOwner($path, $numerical = true) {
        $result = fileowner($path);
        if (!$numerical) {
            $res = posix_getpwuid($result);
            $result = $res['name'];
        }
        return $result;
    }


    /**
     * je vlastnikem Apache?
     *
     * @param string path cesta souboru
     * @return bool true pokud je vlastnikem apache
     */
    public static function isApacheOwner($path) {
        return (posix_getgid() == fileowner($path));
    }


    /**
     * je opravneni v poradku?
     * - alias k is_writable()
     *
     * @param string path cesta souboru
     * @return bool true pokud je soubor zapisovatelny
     */
    public static function isPermissionReady($path) {
        return is_writable($path);
    }


    /**
     * nacitani IPv4 adresy s ohledem na proxy server
     *
     * @param string proxy defaultni index pro server klic proxy
     * @return string ip adresa
     */
    public static function getIP($proxy = 'HTTP_X_FORWARDED_FOR') {
        return ($proxy && isset($_SERVER[$proxy]) ? $_SERVER[$proxy] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null));
    }


    /**
     * nacita hostname s ohledem na aktualni IPv4
     *
     * @param string ip ip-adresa pokud se nezada, pouzije aktualni
     * @return string hostname dane ip adresy
     */
    public static function getHost($ip = null) {
        $addr = ($ip ?: self::getIP(null));
        return gethostbyaddr($addr);
    }


    /**
     * vrati aktualni user agent
     *
     * @param void
     * @return vraci aktualniho user-agenta
     */
    public static function getUserAgent() {
        return self::isFill($_SERVER, 'HTTP_USER_AGENT', null);
    }


    /**
     * osetrovnani prazdnoty indexu pole, funkci "empty"
     *
     * @param array array vstupní pole
     * @param int|string key klic pole
     * @param string default defaultni hodnota
     * @return mixed hodnota pole pod danym klicem pokud je neprazdne
     */
    public static function isFill($array, $key, $default = '') {
        return (!empty($array[$key]) ? $array[$key] : $default);
    }


    /**
     * osetrovani pokud klic pole existuje, funkci "array_key_exists"
     *
     * @param array array vstupni pole
     * @param int|string key klic pole
     * @param string default defaultni hodnota
     * @return mixed hodnota z pole pokud v poli existuje
     */
    public static function isNull($array, $key, $default = '') {
        return (is_array($array) && array_key_exists($key, $array) ? $array[$key] : $default);
    }


    /**
     * vkladani hodnoty pokud neni prazdna, funkci "empty"
     *
     * @param mixed vstupni value
     * @param mixed defaultni|string default
     * @return mixed hodnota value pokud je neprazdna, jinak vraci default
     */
    public static function isEmpty($value, $default = '') {
        return (!empty($value) ? $value : $default);
    }


    /**
     * jde o Firefox?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o firefox
     */
    public static function isFirefox($agent = null) { //pokud by bylo zapotrebi tak by se to rozsirilo podobne jako u chrome
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('#(Firefox|Shiretoko)/([a-zA-Z0-9\.]+)#i', $ua) == 1);
    }


    /**
     * jde o Chrome?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o chrome
     */
    public static function isChrome($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('#Chrome/([a-zA-Z0-9\.]+) Safari/([a-zA-Z0-9\.]+)#i', $ua) == 1);
    }


    /**
     * jde o Safari?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true oikud jde o safari
     */
    public static function isSafari($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('#Safari/([a-zA-Z0-9\.]+)#i', $ua) == 1);
    }


    /**
     * jde o Operu?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o operu
     */
    public static function isOpera($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('#Opera[ /]([a-zA-Z0-9\.]+)#i', $ua) == 1);
    }


    /**
     * jde o IE?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o ie
     */
    public static function isIExplorer($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('#MSIE ([a-zA-Z0-9\.]+)#i', $ua) == 1);
    }


    /**
     * jde o Android?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o android
     */
    public static function isAndroid($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('#Android ([a-zA-Z0-9\.]+)#i', $ua) == 1);
    }


    /**
     * jde o iPhone?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o iphone
     */
    public static function isiPhone($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('/(iPhone)/i', $ua) == 1);
    }


    /**
     * jde o iPod?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o ipod
     */
    public static function isiPod($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('/(iPod)/i', $ua) == 1);
    }


    /**
     * jde o webOS?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o webos
     */
    public static function iswebOS($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('#webOS/([a-zA-Z0-9\.]+)#i', $ua) == 1);
    }


    /**
     * jde o Linux?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o linux
     */
    public static function isLinux($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('/(Linux)|(Android)/i', $ua) == 1);
    }


    /**
     * jde o Mac?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o mac
     */
    public static function isMac($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('/(Mac OS)|(Mac OS X)|(Mac_PowerPC)|(Macintosh)/i', $ua) == 1);
    }


    /**
     * jde o Windows?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o windows
     */
    public static function isWindows($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('/(Windows)/i', $ua) == 1);
    }


    /**
     * jde o Webkit?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o webkit
     */
    public static function isWebKit($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('#AppleWebKit/([a-zA-Z0-9\.]+)#i', $ua) == 1);
    }


    /**
     * jde o Gecko?
     *
     * @param string|null agent manualne vlozeny user agent
     * @return bool true pokud jde o gecko
     */
    public static function isGecko($agent = null) {
        $ua = $agent ?: self::getUserAgent();
        return (preg_match('#Gecko/([a-zA-Z0-9\.]+)#i', $ua) == 1);
    }


    /**
     * get current php version
     *
     * @param void
     * @return php version
     */
    public static function getPHPVersion() {
        return PHP_VERSION;
    }


    /**
     * Nastavovani header hlavicky
     *
     * @param string charset znakova sada
     * @return void
     */
    public static function setCharset($charset = 'UTF-8') {
        header('Content-type: text/html; charset=' . $charset);
    }


    /**
     * vrati svatek pro zadane datum
     *
     * @param int|string date datum, defaultne bere aktualni datum
     * @throws ExceptionCore
     * @return string jmeno svatku
     */
    public static function getNameDay($date = 'now') {
        $svatky = array(
            //leden
            array('Nový rok', 'Karina', 'Radmila', 'Diana', 'Dalimil',
                'Tři králové', 'Vilma', 'Čestmír', 'Vladan', 'Břetislav',
                'Bohdana', 'Pravoslav', 'Edita', 'Radovan', 'Alice',
                'Ctirad', 'Drahoslav', 'Vladislav', 'Doubravka', 'Ilona',
                'Běla', 'Slavomír', 'Zdeněk', 'Milena', 'Miloš', 'Zora',
                'Ingrid', 'Otýlie', 'Zdislava', 'Robin', 'Marika'),
            //unor
            array('Hynek', 'Nela/Hromnice', 'Blažej', 'Jarmila', 'Dobromila',
                'Vanda', 'Veronika', 'Milada', 'Apolena', 'Mojmír',
                'Božena', 'Slavěna', 'Věnceslav', 'Valentýn', 'Jiřina',
                'Ljuba', 'Miloslava', 'Gizela', 'Patrik', 'Oldřich',
                'Lenka', 'Petr', 'Svatopluk', 'Matěj', 'Liliana',
                'Dorota', 'Alexandr', 'Lumír', 'Horymír'),
            //brezen
            array('Bedřich', 'Anežka', 'Kamil', 'Stela', 'Kazimír',
                'Miroslav', 'Tomáš', 'Gabriela', 'Františka', 'Viktorie',
                'Anděla', 'Řehoř', 'Růžena', 'Rút/Matylda', 'Ida',
                'Elena/Herbert', 'Vlastimil', 'Eduard', 'Josef', 'Světlana',
                'Radek', 'Leona', 'Ivona', 'Gabriel', 'Marián',
                'Emanuel', 'Dita', 'Soňa', 'Taťána', 'Arnošt',
                'Kvido'),
            //duben
            array('Hugo', 'Erika', 'Richard', 'Ivana', 'Miroslava',
                'Vendula', 'Heřman/Hermína', 'Ema', 'Dušan', 'Darja',
                'Izabela', 'Julius', 'Aleš', 'Vincenc', 'Anastázie',
                'Irena', 'Rudolf', 'Valérie', 'Rostislav', 'Marcela',
                'Alexandra', 'Evženie', 'Vojtěch', 'Jiří', 'Marek',
                'Oto', 'Jaroslav', 'Vlastislav', 'Robert', 'Blahoslav'),
            //kveten
            array('Svátek práce', 'Zikmund', 'Alexej', 'Květoslav', 'Klaudie, Květnové povstání českého lidu',
                'Radoslav', 'Stanisla', 'Den osvobození od fašismu', 'Ctibor', 'Blažena',
                'Svatava', 'Pankrác', 'Servác', 'Bonifác', 'Žofie',
                'Přemysl', 'Aneta', 'Nataša', 'Ivo', 'Zbyšek',
                'Monika', 'Emil', 'Vladimír', 'Jana', 'Viola',
                'Filip', 'Valdemar', 'Vilém', 'Maxmilián', 'Ferdinand',
                'Kamila'),
            //cerven
            array('Laura', 'Jarmil', 'Tamara', 'Dalibor', 'Dobroslav',
                'Norbert', 'Iveta/Slavoj', 'Medard', 'Stanislav', 'Gita',
                'Bruno', 'Antonie', 'Antonín', 'Roland', 'Vít',
                'Zbyněk', 'Adolf', 'Milan', 'Leoš', 'Květa',
                'Alois', 'Pavla', 'Zdeňka', 'Jan', 'Ivan',
                'Adriana', 'Ladislav', 'Lubomír', 'Petr a Pavel', 'Šárka'),
            //cervenec
            array('Jaroslava', 'Patricie', 'Radomír', 'Prokop', 'Den slovanských věrozvěstů Cyrila a Metoděje',
                'Upálení mistra Jana Husa', 'Bohuslava', 'Nora', 'Drahoslava', 'Libuše/Amálie',
                'Olga', 'Bořek', 'Markéta', 'Karolína', 'Jindřich',
                'Luboš', 'Martina', 'Drahomíra', 'Čeněk', 'Ilja',
                'Vítězslav', 'Magdeléna', 'Libor', 'Kristýna', 'Jakub',
                'Anna', 'Věroslav', 'Viktor', 'Marta', 'Bořivoj',
                'Ignác'),
            //srpen
            array('Oskar', 'Gustav', 'Miluše', 'Dominik', 'Kristián',
                'Oldřiška', 'Lada', 'Soběslav', 'Roman', 'Vavřinec',
                'Zuzana', 'Klára', 'Alena', 'Alan', 'Hana',
                'Jáchym', 'Petra', 'Helena', 'Ludvík', 'Bernard',
                'Johana', 'Bohuslav', 'Sandra', 'Bartoloměj', 'Radim',
                'Luděk', 'Otakar', 'Augustýn', 'Evelína', 'Vladěna',
                        'Pavlína'),
            //zari
            array('Linda/Samuel', 'Adéla', 'Bronislav', 'Jindřiška', 'Boris',
                'Boleslav', 'Regína', 'Mariana', 'Daniela', 'Irma',
                'Denisa', 'Marie', 'Lubor', 'Radka', 'Jolana',
                'Ludmila', 'Naděžda', 'Kryštof', 'Zita', 'Oleg',
                'Matouš', 'Darina', 'Berta', 'Jaromír', 'Zlata',
                'Andrea', 'Jonáš', 'Václav, Den české státnosti', 'Michal', 'Jeroným'),
            //rijen
            array('Igor', 'Olívie', 'Bohumil', 'František', 'Eliška',
                'Hanuš', 'Justýna', 'Věra', 'Štefan/Sára', 'Marina',
                'Andrej', 'Marcel', 'Renáta', 'Agáta', 'Tereza',
                'Havel', 'Hedvika', 'Lukáš', 'Michaela', 'Vendelín',
                'Brigita', 'Sabina', 'Teodor', 'Nina', 'Beáta',
                'Erik', 'Šarlota/Zoe', 'Den vzniku samostatného československého státu', 'Silvie', 'Tadeáš',
                'Štěpánka'),
            //listopad
            array('Felix', 'Památka zesnulých', 'Hubert', 'Karel', 'Miriam',
                'Liběna', 'Saskie', 'Bohumír', 'Bohdan', 'Evžen',
                'Martin', 'Benedikt', 'Tibor', 'Sáva', 'Leopold',
                'Otmar', 'Mahulena, Den boje studentů za svobodu a demokracii', 'Romana', 'Alžběta', 'Nikola',
                'Albert', 'Cecílie', 'Klement', 'Emílie', 'Kateřina',
                'Artur', 'Xenie', 'René', 'Zina', 'Ondřej'),
            //prosinec
            array('Iva', 'Blanka', 'Svatoslav', 'Barbora', 'Jitka',
                'Mikuláš', 'Ambrož/Benjamín', 'Květoslava', 'Vratislav', 'Julie',
                'Dana', 'Simona', 'Lucie', 'Lýdie', 'Radana',
                'Albína', 'Daniel', 'Miloslav', 'Ester', 'Dagmar',
                'Natálie', 'Šimon', 'Vlasta', 'Adam a Eva, Štědrý den', 'Boží hod vánoční - svátek vánoční',
                'Štěpán - svátek vánoční', 'Žaneta', 'Bohumila', 'Judita', 'David',
                'Silvestr - Nový rok')
            );

        $dat = strtotime($date);
        if (date('Y', $dat) > 1970) {
            return $svatky[date('n', $dat) - 1][date('j', $dat) - 1];
        } else {
            throw new ExceptionCore('Spatny format datumu!');
        }
    }


    /**
     * vraci cesky nazev mesice
     * - pouziva: date('n')
     *
     * @param int month cislo mesice 1-12
     * @param bool tvar1 true (duben), false (dubna)
     * @param bool timestamp true pokud je vstupem timestamp
     * @return string cesky mesic
     */
    public static function getCzechMonth($month, $tvar1 = true, $timestamp = true) {
        $mesice1 = array(1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec');
        $mesice2 = array(1 => 'ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince');
        $m =  ($tvar1 ? $mesice1 : $mesice2);
        return ($timestamp ? $m[date('n', $month)] : $m[$month]);
    }


    /**
     * vraci cesky den v tydnu
     * - pouziva: date('w')
     *
     * @param int day cislo dne, 0-6, 0 = sunday
     * @param bool timestamp true pokud je vstupem timestamp
     * @return string cesky den
     */
    public static function getCzechDay($day, $timestamp = true) {
        $dny = array('neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota');
        return ($timestamp ? $dny[date('w', $day)] : $dny[$day]);
    }


    /**
     * nacitani ceskeho datumu
     * - format: pondeli 1.ledna 1970
     *
     * @param string|int date vstupni datum
     * @param bool timestamp true prijem datumu v timestamp (int) formatu, jinak textovy datum
     * @return string slozeny datum
     */
    public static function getCzechDate($date, $timestamp = false) {
         $source = $timestamp ? $date : strtotime($date);
        return self::getCzechDay($source) . ' ' . date('j.', $source) . ' ' . self::getCzechMonth($source, false) . ' ' . date('Y', $source);
    }


    /**
     * nacitani ceskeho datumu s casem
     * - format: pondeli 1.ledna 1970, 12:00:00
     *
     * @param string|int date vstupni datum
     * @param bool timestamp true prijem datumu v timestamp (int) formatu, jinak textovy datum
     * @return string slozeny datum a cas
     */
    public static function getCzechDateTime($date, $timestamp = false) {
        $source = $timestamp ? $date : strtotime($date);
        return self::getCzechDay($source) . ' ' . date('j.', $source) . ' ' . self::getCzechMonth($source, false) . ' ' . date('Y, H:i:s', $source);
    }


    /**
     * nacitani pluraniho tvaru primarne pro cestinu
     * - pracuje s absolutnim poctem
     *
     * @param int count pocet polozek
     * @param array version verze podle poctu pro 1; 2-4; 0,5>=
     * @return string spravny textovy tvar
     */
    public static function getCzechPlural($count, $version = array('1', '2-4', '0,5>=')) {
        switch (abs($count)) {
            case 1:     // 1 okno
                return $version[0];

            case 2:     // 2,3,4 okna
            case 3:
            case 4:
                return $version[1];

            case 0:     // 0 oken
            default:    // 150 oken
                return $version[2];
        }
    }


    /**
     * nastaveni intervalu pro presmerovani
     *
     * @param int time cas pro vyckani
     * @param string path cesta pro vysledne presmerovani
     * @return void
     */
    public static function setRefresh($time, $path) {
        $url = htmlspecialchars_decode($path);
        header('Refresh: ' . $time . '; URL=' . $url);
    }


    /**
     * zaslani hlavicky okamziteho presmerovani
     * - prepisuje hlavicku
     *
     * @param string path cesta presmerovani
     * @param int code http response kod
     * @return void
     */
    public static function setLocation($path, $code = 303) {
        header('Location: ' . $path, true, $code);
    }


    /**
     * zaslani hlavicek pro download dialog v prohlizeci
     *
     * @throws ExceptionCore
     * @param string path cesta souboru na stazeni
     * @param string|null newname nove jmeno ktere se nabydne pri stazeni
     * @return void
     */
    public static function getDownloadFile($path, $newname = null) {
        if (file_exists($path) && is_readable($path)) {
            header('Content-type: ' . mime_content_type($path));  // nastaveni content-typu
            $name = ($newname ?: basename($path));  // nove jmeno / zaklad puvodniho
            // nastaveni noveho jmena souboru
            header('Content-Disposition: attachment; filename=' . $name);
            header("Content-Length: " . filesize($path));
            header('Expires: 0');
            header('Pragma: no-cache'); // nekesevat
            readfile($path);  // precteni souboru na stdout
            exit;
        } else {
            $nm = '"'.$path.'"'.($newname ? ' ('.$newname.')' : null);
            if (!file_exists($path)) {
                throw new ExceptionCore('file '.$nm.' for download does not exists!');
            }

            if (!is_readable($path)) {
                throw new ExceptionCore('file '.$nm.' for download does not readable!');
            }
        }
    }


    /**
     * byli hlavicky poslany?
     *
     * @param void
     * @return true pokud byli poslany
     */
    public static function isSentHeaders() {
        return headers_sent();
    }


    /**
     * nacitani hodnoty cookie
     *
     * @param string key klic cooke
     * @param string|null default defaultni hodnota cookie pokud klic neexistuje
     * @return string hodnota cookie
     */
    public static function getCookie($key, $default = null) {
        return (isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default);
    }


    /**
     * nastavovani hodnoty cookie
     *
     * @throws ExceptionCore
     * @param string name jmeno klice cookie
     * @param string value hodnota pro dany klic
     * @param int time cas expirace
     * @param string path cesta pro cookie
     * @param string domain domena pro cookie
     * @param bool secure pouzivane v https rezimu
     * @param bool httpOnly posilani pouze v http hlavicce
     * @return void
     */
    public static function setCookie($name, $value, $time, $path = null, $domain = null, $secure = null, $httpOnly = null) {
        if (headers_sent($file, $line)) {
            throw new ExceptionCore('nelze odeslat hlavicky, via: '.$file.', '.$line);
        }

        $cookiePath = '/';
        $cookieDomain = '';
        $cookieSecure = false;
        $cookieHttpOnly = true;

        setcookie($name,
                $value,
                $time ? DateAndTime::from($time)->format('U') : 0,
                is_null($path) ? $cookiePath : $path,
                is_null($domain) ? $cookieDomain : $domain,
                is_null($secure) ? $cookieSecure : $secure,
                is_null($httpOnly) ? $cookieHttpOnly : $httpOnly
                );
    }


    /**
     * mazani hodnoty cookie
     *
     * @param string name jmeno klice cookie
     * @param string path cesta pro cookie
     * @param string domain domena pro cookie
     * @param bool secure pouzivane v https rezimu
     * @return void
     */
    public static function deleteCookie($name, $path = null, $domain = null, $secure = null) {
        self::setCookie($name, false, 0, $path, $domain, $secure);
    }


    /**
     * hesovani na zaklade loginu
     *
     * @param string login vstupni login
     * @param string pass vstupni heslo
     * @param string hash1 typ heshu 1
     * @param string hash2 typ heshu 2
     * @return string zahesovany text
     */
    public static function getCleverHash($login, $pass, $hash1 = 'sha256', $hash2 = 'ripemd320') {
        $p = $pass;
        for ($i = 0; $i < strlen($login); $i++) {
            $p = hash($hash1, $p);
        }
        $p .= md5($login);
        return hash($hash2, $p);
    }


    /**
     * hesovani pro htpasswd
     *
     * @param string pass vstupni heslo
     * @return string vystupni heslo
     */
    public static function getHtpasswdHash($pass) {
        return crypt($pass, base64_encode($pass));
    }


    /**
     * nacitani dlouheho unikatniho id tvoreneho z aktualni slozly
     *
     * @param string prefix predpona
     * @param bool more_entropy true pro vice unikatni
     * @return string unikatni id
     */
    public static function getUniqId($prefix = __DIR__, $more_entropy = false) {
        return uniqid($prefix, $more_entropy);
    }


    /**
     * nacitani ciselneho unikatniho textu
     *
     * @param string prefix volitelny prefix textu
     * @return string unikatni text
     */
    public static function getUniqText($prefix = null) {
        return uniqid($prefix ?: rand());
    }


    /**
     * vrati bezpecny email pro a-href
     *
     * @param string email vstupni email
     * @return array pole pro a-href [href,text]
     */
    public static function getSafeEmail($email) {
        $result['href'] = sprintf('mailto:%s', str_replace('@', '%40', $email));
        $result['text'] = str_replace('@', '&#064;', $email);
        return $result;
    }


    /**
     * cisteni souboru (zaloh) za casovy interval
     * - posouva casovy interval do minulosti
     *
     * @param array list pole souboru (s uplnou cestou)
     * @param string time cas expirace (bez znamenka)
     * @return int pocet smazanych polozek
     */
    public static function cleanExpire($list, $time) {
        $posun = strtotime('-' . $time); // zaporny posun
        $ret = array_map(function($r) use ($posun) {
            if (file_exists($r) && filemtime($r) < $posun) {
                return unlink($r);
            }
        }, $list);
        return array_sum($ret);
    }


    /**
     * cisteni souboru (zaloh) na konkretni pocet
     *
     * @param array list pole souboru
     * @param int count pocet souboru ktere maji zustat
     * @return int pocet smazanych polozek
     */
    public static function cleanCount($list, $count) {
        $ret = array();
        if (count($list) > $count) {
            $slice = array_slice($list, $count);
            $ret = array_map(function($r) {
                return file_exists($r) && unlink($r);
            }, $slice);
        }
        return array_sum($ret);
    }


    /**
     * kontrola zavislosti
     *
     * @throws ExceptionCore
     * @param array paths pole souboru na kontrolu
     * @return void
     */
    public static function checkDependency($paths) {
        foreach ($paths as $i => $v) {
            if (!file_exists($v)) {
                throw new ExceptionCore('dependency ' . $i . ' is broken!');
            }
        }
    }


    /**
     * kontrola datumu v rozsahu
     * @param  [type]  $from  od
     * @param  [type]  $to    do
     * @param  [type]  $value now
     * @return boolean        [description]
     */
    public static function isDateInRange($from, $to, $value = null)
    {
        $d1 = new \DateTime($from);
        $d2 = new \DateTime($to);
        $now = new \DateTime($value);
        if ($from && $to) {
            return \Nette\Utils\Validators::isInRange($now, array($d1, $d2));
        }
        return false;
    }
}


/**
 * trida vyjimky pro Core
 *
 * @package goodflow
 * @author geniv
 * @version 1.00
 */
class ExceptionCore extends \Exception {}
