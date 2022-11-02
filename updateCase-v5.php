<?php class UpdateCase
{
    var $variant_id;
    var $lang;
    var $version;
    var $debug;
    var $debugMessages = array();

    var $json = '';
    var $slug = '';
    var $local_uuid = FALSE;
    var $hostPath = 'http://site.updatecase.com/';

    var $site;
    var $variant;
    var $pages;
    var $page;
    var $allPages;

    var $liveServer;
    var $currentServer;

    var $date = false;

    var $seoLocationName = 'SEO';

/////////////////////////////////////////////////////// INITIALIZE ///////////////////////

    /**
     * $this->updateCase = new UpdateCase(['debug' => true,'variant_id' => $variant_id,'version' => 5,'lang' => $this->request->getAttribute('lang')]);
     * @param array $options
     * # debug true will force network connections while false will only use local cached data
     * # variant_id required to interface with updateCase.com and get the correct data
     * # version 5 is the current version
     * # lang which language to use (supported: en/es/fr)
     * @return array entire structure which can be passed into the view
     */
    public function __construct($options) {

        if (isset($options['variant_id'])) {$this->variant_id = $options['variant_id'];}
        if (isset($options['lang'])) {$this->lang = $options['lang'];}
        if (isset($options['version'])) {$this->version = $options['version'];}
        if (isset($options['debug'])) {$this->debug = $options['debug'];}
        if (isset($options['liveServer'])) {$this->liveServer = $options['liveServer'];}

        //handle errors here
        if (empty($this->variant_id)) die('missing variant_id');
        if (empty($this->lang)) die('missing lang');
        if (empty($this->version)) die('missing version');

        //prepare the current site
        //pr ($_SERVER);
        $this->currentServer = $_SERVER['SERVER_NAME'];

        $this->init();

    }

    private function addDebugMessage($msg, $newLine = true) {

        if (!$newLine) {
            //same line
            end($this->debugMessages);
            $lastKey = key($this->debugMessages);

            $this->debugMessages[$lastKey] = $this->debugMessages[$lastKey] . ' -> '.$msg;
        } else {
            $this->debugMessages[] = $msg;
        }


//        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
//        $msg = "Ping !";
//        $len = strlen($msg);
//        socket_sendto($sock, $msg, $len, 0, '127.0.0.1', 1223);
//        socket_close($sock);

//        $address = '127.0.0.1';
//        $port = 9000;
//        $data = 'testing update case';
//        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
//        socket_bind($socket, "0.0.0.0", 9000) or onSocketFailure("Failed to bind to 0.0.0.0:9000", $socket);
//        socket_sendto($socket, $data, strlen($data), 0, $address, $port);
//        die('kkkkk');

    }

    private function init() {

        //our current version
        $this->local_uuid = $this->getMostRecentFilename();

        if ($this->debug) {
            $this->addDebugMessage('Debug is true - initializing');
            $this->addDebugMessage('local_uuid: '.$this->local_uuid, false);
            //check if a newer version exists on the server
            $this->local_uuid = $this->downloadFromUpdateCase(
                $this->getVariantId(),
                $this->local_uuid
            );
        } else {
            //debug is false, so startup quietly

            //if we are missing our localjson file, let's get it
            if (empty($this->local_uuid)) {
                $this->local_uuid = $this->downloadFromUpdateCase(
                    $this->getVariantId(),
                    $this->local_uuid
                );
            }
        }

        $this->prepareJson();

    }

    private function isPrepared() {

        $isReady = true;
        if (empty($this->json)) {
            $this->addDebugMessage('JSON data not loaded');
            $isReady = false;
        }
        if (empty($this->slug)) {
            $this->addDebugMessage('Slug is not loaded');
            $isReady = false;
        }

        return $isReady;
    }

    private function ensureDirExists($path) {
        if (!file_exists($path)) {
            $currDir = exec('pwd');
            $this->addDebugMessage($currDir . ' - mkdir: ' . $path);
            $didCreate = mkdir($path);
            if (!$didCreate) die('ERROR: Please create manually: '.$path);
        } else {
            //already exists
        }
    }

    private function getMostRecentFilename()
    {
        $this->jsonPath = '..' . DS . 'config' . DS . 'schema' . DS . $this->getVariantId();

        $this->ensureDirExists($this->jsonPath);

//        if (!file_exists($this->jsonPath)) {
//            $currDir = exec('pwd');
//            $this->addDebugMessage($currDir . ' - mkdir: ' . $this->jsonPath, true);
//            $didCreate = mkdir($this->jsonPath);
//            if (!$didCreate) die('ERROR: Please create: '.$this->jsonPath);
//        }

        $files = scandir($this->jsonPath);

        foreach ($files as $fileKey => $file) {
            $ext = substr($file, -4);
            if ($ext != 'json') {
                unset($files[$fileKey]);
            }
        }

        foreach ($files as $key => $file) {
            if (strlen($file) < 20) { //we don't want to use the older manual name of sites
                unset($files[$key]);
            }
        }

        if (empty($files)) {
            return false;
        } else {

            sort($files);
            $newestFile = end($files);
            $newestFile = str_replace(".json", '', $newestFile);
            $this->local_uuid = $newestFile;


            return $newestFile;

        }
    }

    private function getVariantId()
    {
        if (!$this->variant_id) {
            $this->addDebugMessage('getVariant_ID: variant_id is empty');
        } else {
            return $this->variant_id;
        }
    }

    private function download($pathToUse)
    {
        $this->addDebugMessage('downloading: '.$pathToUse, false);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pathToUse);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); //improves speed
        $output = curl_exec($ch);
        curl_close($ch);

        $this->addDebugMessage(substr($output, 0, 200), false);
        return $output;
    }

    private function downloadFromUpdateCase($variant_id, $local_uuid = false)
    {
        $pathToUse = $this->hostPath . 'Variants/getCurrentUUID/' . $variant_id;
        $this->addDebugMessage('downloadFromUpdateCase: get UUID only from updateCase: ' . $pathToUse, false);
        $server_uuid = $this->download($pathToUse);

        if (empty($server_uuid)) {
            $this->addDebugMessage('Server did not return the current UUID', false);
            return false;
        } else {
            if ($local_uuid == $server_uuid) {
                //we are already up to date
                $this->addDebugMessage('No action required: Local is up-to-date with server', false);
                return $local_uuid;
            } else {
                $pathToUse = $this->hostPath . 'Variants/get/' . $variant_id;
                $this->addDebugMessage('GETTING NEW FILE: current_uuid: ' . $local_uuid . ' - server_uuid: ' . $server_uuid . ' - get full content updateCase: ' . $pathToUse, false);

                $newJsonContent = $this->download($pathToUse);

                $folder = $this->jsonPath;

                $locationToWrite = $folder . DS . $server_uuid . '.json';

                // pr ($locationToWrite);
                // pr ($folder);exit;
                $this->addDebugMessage('Writing to : ' . $locationToWrite, false);
                file_put_contents($locationToWrite, $newJsonContent);
                $this->addDebugMessage('Downloaded NEW JSON: ' . $server_uuid, false);

                return $server_uuid;
            }
        }
    }

    private function isLive() {
        if (in_array($this->currentServer, $this->liveServer)) {
            return true;
        }
        return false;
    }

    private function prepareJson()
    {
        if (!$this->local_uuid) {
            $this->addDebugMessage('404: no local uuid specified', true);
            return false;
        } else {
            if (empty($this->json)) {
                //first time let's get our data
                $this->addDebugMessage('Preparing JSON (first time only)');

                $data = file_get_contents($this->jsonPath . DS . $this->local_uuid . '.json');

                $this->json = json_decode($data, true);

                if (isset($this->json[0]['Site'])) {
                    $this->addDebugMessage('Decoded json', false);
                    $this->site = $this->json[0]['Site'];
                    $this->variant = $this->json[0]['Variant'];
                    $this->pages = $this->json[0]['Page'];
                    $this->page = array();
                    //setup the all
                    foreach ($this->pages as $pageKey => $page) {

                        //pr ($page);exit;
                        //if visible_testing is set do NOT show to the liveServer
                        if ($page['visible_testing']) {

                            //pr ($page);exit;
                            if ($this->isLive()) {
                                unset($this->pages[ $pageKey ]);
                                continue;
                            }
                        }

                        //remove pages if visble_testing is set
                        if (strtolower($page['slug']) == 'all') {
                            $this->allPages = $page;
                        }
                    }

                    $this->addDebugMessage(count($this->pages) . ' pages loaded', false);
                    return true;
                } else {
                    $this->addDebugMessage(' - ERROR decoding json', true);
                    return false;
                }
            } else {
                $this->addDebugMessage('JSON already loaded', false);
                return -1;
            }
        }
    }

//////////////////////////////////////// PUBLIC METHODS ///////////////////////

    /**
     * @param $maxRecentRows # default to 20 can be increased to see further back
     * @return string # can be echo'd to see a complete log of the actions to diagnose any issues and understand exactly what is happening
     */
    public function debug($maxRecentRows = 20) {
        $array = array_reverse(array_slice($this->debugMessages, $maxRecentRows * -1, 20));

        $string = '';
        foreach ($array as $k => $v) {
            $string .= '<div style="padding-left: 22px; text-indent: -22px; padding-bottom: 10px; ">';
            $string .= $v;
            $string .= '</div>';
        }

        // $string .= implode('<br/>', $array);

        return $string;
    }

    public function getApiList() {
        preg_match_all('/public function (\w+)/', file_get_contents(__FILE__), $m);

        $list = [];
        $commentLoc = -4;
        $publicLoc = -2;
        $functionName = +2;

        $tokens = token_get_all( file_get_contents(__FILE__) );
        //dd($tokens);
        foreach ($tokens as $key => $token) {
            if (!isset($token[1])) continue;
            if ($token[1] == 'function') {
                //dd($token);
                if (!isset($tokens[$key + $publicLoc][1])) continue;

                if ($tokens[ $key + $publicLoc ][1] == 'public') {
                    $list = [];
                    if (isset($tokens[ $key  + $functionName ][1])) {

                        if ($tokens[ $key + $functionName ][1] == 'getApiList') continue;

                        $list['function'] = $tokens[ $key  + $functionName ][1];

                    }
                    //get args
                    $offset = $functionName + 1;
                    $continue = true;
                    $string = '';
                    do {
                        //dd($tokens[ $key + $offset ]);
                        if (isset($tokens[ $key + $offset ])) {
                            if (isset( $tokens[ $key + $offset ][1] )) {
                                $string .= $tokens[ $key + $offset ][1];
                            } else {
                                $string .= $tokens[ $key + $offset ];
                                if ($tokens[ $key + $offset ] == ')') {$continue = false;}
                            }
                        }
                        $offset++;
                        if ($offset > 50) $continue = false;
                    } while($continue);

                    $list['args'] = $string;

                    if (isset($tokens[ $key + $commentLoc ][1])) {
                        $comment = $tokens[ $key + $commentLoc ][1];
                        if (substr($comment, 0, 2) == '/*') {
                            //it is a comment
                            $list['comment'] = $tokens[ $key + $commentLoc ][1];
                        }
                    }



                    $offset = -10;
                    $continue = true;
                    $string = '';
                    do {
                        //dd($tokens[ $key + $offset ]);
                        if (isset($tokens[ $key + $offset ][1])) {
                            $string .= $tokens[ $key + $offset ][1];
                        } else {
                            $string .= $tokens[ $key + $offset ];
                        }
                        $offset++;
                        if ($offset > 50) $continue = false;
                    } while($continue);

                   // $list['surrounding'] = $string;
                    $list['key'] = $key;
                    //dd($token);
                    //it is a public function so let's track it
                    $lists[] = $list;

                }
            }
        }

       // pr ($lists); exit;

        //dd($tokens);

        //convert to markup for github
        foreach ($lists as $list) {
            echo "## ".$list['function'].$list['args'];
            echo "<br/>";

            if (isset($list['comment'])) {
                echo "```php";
                //echo "<br/>";


                $comment = $list['comment'];

                //see the newlines
                //echo json_encode($comment); exit;

                //remove the comment /*,*'s
                $comment = str_replace("*/", "", $comment);
                $comment = str_replace("\n     *", "\n", $comment);
                $comment = str_replace("/**", "", $comment);
                $comment = nl2br($comment);

                //$comment = ltrim($comment);
                //$comment = ltrim($comment, "\n");

                //echo json_encode($comment);exit;

                echo $comment;
                echo "```";
                echo "<br/>";
            }

            echo "<br/>";
            echo "---";
            echo "<br/>";
        }


        //dd($m);
        //var_dump($m[1]);

        exit;

    }

    /**
     * Allows to switch to a different variant_id which is useful when you want to share content between websites
     * @param $variant_id
     * @return void
     */
    public function changeVariant($variant_id) {
        $this->variant_id = $variant_id;

        //reset our json so it reloads from the file
        $this->json = '';
        $this->init();
    }

    /**
     * Switch to a different slug (or page on UpdateCase.com)
     * @param $slug
     * @return bool
     */
    public function changeSlug($slug) {

        $this->addDebugMessage('changeSlug: '.$slug);

        if (empty($this->json)) {
            $this->addDebugMessage('Cannot load json', false);
            return false;
        }
        foreach ($this->pages as $page) {
            if (trim($page['slug']) == trim($slug)) {
                $this->page = $page;
                $this->slug = $slug; //our current slug
                $this->addDebugMessage('"'.$slug.'" Loaded', false);
                return true;
            }
        }

        $this->addDebugMessage('WARNING: Nothing changed for slug: '.$slug);

        return false; //nothing changed
    }

    /**
     * DEPRECATED: This is now 'changeSlug' and will be removed on the next major release
     * @param $slug
     * @return void
     */
    public function loadPageBySlug($slug){ $this->changeSlug($slug); }

    /**
     * Display content added to UpdateCase. Best to copy-and-paste directly from site to save time setting up.
     * @param string $locationName
     * @param string $elementName
     * @param string $groupName # allows to use groups and loop blocks
     * @param string $slug # allows to display content from a specific slug without 'changeSlug'
     * @return string content which was added to UpdateCase.com
     */
    public function getContentBy($locationName, $elementName, $groupName = false, $slug = false)
    {
        if ($slug) {
            $this->changeSlug($slug);
        }

        if (!$this->isPrepared()) {
            $this->addDebugMessage('NOT isPrepared');
            return false;
        }

        if ($groupName == 'false') $groupName = false;

        if (empty($this->page)) {
            $this->addDebugMessage('Page not setup', true);
            return false;
        }

        $this->addDebugMessage('getContentBy: '.$this->locationViewString($locationName, $elementName, $groupName));

        $element = $this->getElement($locationName, $elementName, $groupName);

        //@todo add is location active

        if (isset($element['name'])) {
            if ($element['name'] == 'image') {
                return true;
            } else {
                $text = $element['Revision'][0]['content_text'];
                if (empty($text)) {
                    $this->addDebugMessage('WARNING: emptyCONTENT: '.$this->locationViewString($locationName, $elementName, $groupName), true);
                } else {
                    $this->addDebugMessage('found: "'.$text.'"', false);
                }

                return $text;
            }
        } else {
            return false;
        }

    }

    /**
     * Get the active language (en/es/fr)
     * @return mixed
     */
    public function currLang() {
        return $this->lang;
    }

    private function isCurrentLanguage($fieldLang)
    {
        if (
            strtolower(substr($fieldLang, 0, 2))
            ==
            strtolower(substr($this->lang, 0, 2))
        ) {
            $this->addDebugMessage('CorrectLang', false);
            return true;
        } else if ($fieldLang == 'ALL') {
            $this->addDebugMessage('AllLang', false);
            return true;
        }

        $this->addDebugMessage('LangMismatch', false);
        return false;
    }

    /**
     * Get the Meta TITLE either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
     * @return string
     */
    public function getMetaTitle() {
        return $this->getMetaGeneral('title');
    }

    /**
     * Get the Meta DESCRIPTION either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->getMetaGeneral('description');
    }

    /**
     * Get the Meta KEYWORDS either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
     * @return string
     */
    public function getMetaKeywords()
    {
        return $this->getMetaGeneral('keywords');
    }

    private function getMetaGeneral($field) {
        $title = '';

        $slug = $this->slug;

        if (!empty($slug)) { //we have a page specific slug loaded, let's check that
            $title = $this->cleanUpStringForQuotedSections(
                $this->getByWithoutLoading(
                    $slug,
                    $this->seoLocationName,
                    $field
                )
            );
        }

        if (empty($title)) { //let's check ALL instead
            $title = $this->cleanUpStringForQuotedSections(
                $this->getByWithoutLoading(
                    'All',
                    $this->seoLocationName,
                    $field
                )
            );
        }

        return $title;
    }

    /**
     * Get the Meta TITLE/KEYWORDS/DESCRIPTION either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
     * @param $name # use 'title' OR 'keywords' OR 'description'
     * @return string
     */
    public function getMetaProperty($name)
    {
        return $this->getMetaGeneral($name);
    }

    /**
     * OG LOCALE requires a full language string
     * @param $lang
     * @return false|string
     */
    public function getMetaOgLocale($lang)
    {
        //valid OG choices

        $send = array(
            'en' => 'en_CA',
            'fr' => 'fr_CA',
            'eng' => 'en_CA',
            'fre' => 'fr_CA'
        );
        if (isset($send[$lang])) {
            return $send[$lang];
        }
        return false;
    }

    /**
     * Allows to change the date which is used to simulate events in the future / past to test conditional locations
     * @param $date
     * @return void
     */
    public function setDate($date)
    {
        //override the date
        $this->date = date('Y-m-d H:i:s', strtotime($date));
        $this->addDebugMessage('Date set to: ' . $this->date);
    }

    /**
     * What date the UpdateCase client is using
     * @return false|string
     */
    public function getDate()
    {
        if (!$this->date) {
            //allow to override since it is not set yet
            if (isset($_GET['testDate'])) {
                $this->date = date('Y-m-d H:i:s', strtotime($_GET['testDate']));
            } else {
                $this->date = date('Y-m-d H:i:s');
            }
        } else {
            //date already set
        }

        return $this->date;
    }

    /**
     * This will cache images from UpdateCase.com to website (webroot)
     * @param $location
     * @param $element
     * @param $group
     * @param $size # medium / thumb / large (still alpha functionality)
     * @param $slug # conditional to get image without using changeSlug
     * @return false|string # will return the url to the image on the webroot, can be added to img src="$webroot.$updateCase->getImageBy.....
     */
    public function getImageBy($location, $element, $group = false, $size = 'medium', $slug = false)
    {
        $debug = true;

        if ($slug) {
            $this->changeSlug($slug);
        }

        if ($group == 'false') {
            $group = false;
        }

        $cache = 'images' . DS . $this->getVariantId() . DS;

        $this->ensureDirExists($cache);

        $element = $this->getElement($location, $element, $group);

        if (!isset($element['Revision'][0])) {
            $this->addDebugMessage('No revision for the image');
            return false;
        }


        $mime = $element['Revision'][0]['mime'];
        $id = $element['Revision'][0]['id'];

        //pr ($element);exit;
        //pr ($mime);
        //exit;

        $this->addDebugMessage('mime: ' . $mime . ' id: ' . $id);

        if ($mime == 'image/jpeg') {
            $filename = $id . '.jpg';
        } elseif ($mime == 'image/png') {
            $filename = $id . '.png';
        } else {

            //@todo find a way to get the mime when it is not available
            //default
            $filename = $id . '.jpg';

            //pr ($this->revision);

            //echo $message;
            //exit;
            //return false;
        }

        if (file_exists($cache . $filename)) {
            //return the local file
            $this->addDebugMessage('have local file: ' . $cache . $filename);
            return $cache . $filename;
        } else {
            //create the file locally
            $this->addDebugMessage('No cached image');

            if (!file_exists($cache)) {
                $this->addDebugMessage('Trying to create folder: ' . $cache);
                mkdir($cache);
            }

            if (!file_exists($cache)) {
                $this->addDebugMessage('ERROR: could not create (check permissions) Image Cache missing: ' . $cache);
                return false;
            }

            //new
            $imageLink = $this->hostPath . 'imagesGet/' . $id . '/' . $size . '/pic.jpg';

            $output = $this->download($imageLink);

            $this->addDebugMessage('Writing image: ' . $imageLink . ' to ' . $cache . $filename);
            $res = file_put_contents($cache . $filename, $output);

            //return where the image is
            return $cache . $filename;
        }
    }

    /**
     * DEPRECATED use 'getImageBy' instead
     */
    public function getImage($location, $element, $group = false, $size = 'medium') {
        return $this->getImageBy($location, $element, $group, $size);
    }

    /**
     * @param $location
     * @param $element
     * @param $group
     * @param $slug
     * @return false|string
     */
    public function getFileBy($location, $element, $group = false, $slug = false)
    {

        $this->addDebugMessage('getFileBy: '.$this->locationViewString($location, $element, $group));

        if ($slug) {
            $this->loadPageBySlug($slug);
        }

        $cache = 'images' . DS . $this->getVariantId() . DS;

        if ($group == '') {
            $group = false;
        }
        //pr ($element);
        $id = $this->getIdBy($location, $element, $group);

        //dd($id);

        if (!$id) {
            $message = 'File cannot load | Location: ' . $location . ' / Element ' . $element . ' / Group:' . $group;
            $this->addDebugMessage($message);
            return false;
        }

        $element = $this->getElement($location, $element, $group);

        $revision = $element['Revision'][0];

        //pr ($revision);exit;
        //pr ($id);
        if ($revision['mime'] == 'application/pdf') {
            $filename = $id . '.pdf';
        } elseif ($revision['mime'] == 'application/epub') {
            $filename = $id . '.epub';
        } elseif ($revision['mime'] == 'application/epub+zip') {
            $filename = $id . '.epub';
        } elseif ($revision['mime'] == 'application/mobi') {
            $filename = $id . '.mobi';
        } elseif ($revision['mime'] == 'application/octet-stream') {
            $filename = $id . '.mobi';
        } elseif ($revision['mime'] == 'image/jpeg') {
            $filename = $id . '.jpg';
        } else {

            //echo 'cannot load slug';
            //pr ($this->revision);
            //pr ($id);exit;

            //exit;
            //pr ($this->revision);
            //$message = 'File cannot load | SLUG: ' . $this->slug . ' / Location: ' . $location . ' / Element ' . $element . ' / Group:' . $group;

            //echo $message;
            //exit;
            //return $message;
            return false;
        }


        //pr ($filename);
        //does a cached version exist
        //$file = new File($cache . $filename);

        $fileExists = file_exists($cache.$filename);
         //pr ($filename);exit;

        if ($fileExists) {

            $this->addDebugMessage('Cached image exists', false);
            //return the local file
            return $cache . $filename;
        } else {
            //create the file locally
            $this->addDebugMessage('NOcachedImage: downloading...', false);

            //@todo should create a auto create shared method to handle when directory does not exist
            //$dir = new Folder($cache, true, 0775);
            if (file_exists($cache)) {
                $imageLink = 'https://site.updatecase.com/download/' . $id . '/' . $filename;

                $this->addDebugMessage('imageLink: '.$imageLink, false);
                $arrContextOptions = array(
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ),
                );
                $output = file_get_contents($imageLink,
                    false,
                    stream_context_create($arrContextOptions)
                );

                file_put_contents($cache.$filename, $output);

                $this->addDebugMessage('writing image to cache: '.$cache.$filename, false);
                //$file->write($output);
                return $cache . $filename;
            } else {
                $this->addDebugMessage('ERROR: image cache folder not working cannot create image', true);
                return false;
            }
        }
    }

    /**
     * @param $locationName
     * @param $elementName
     * @param $groupName
     * @param $slug
     * @param $prefix
     * @return mixed|string
     */
    public function getLinkBy($locationName, $elementName, $groupName = false, $slug = false, $prefix = 'http://')
    {
        return $this->ensureHttpOrHttps(
            $this->getContentBy($locationName, $elementName, $groupName, $slug), $prefix
        );
    }

    /**
     * @param $currLocationName
     * @return bool
     */
    public function isLocationActive($currLocationName)
    {

        //pr ($this->page);exit;
        foreach ($this->page['Location'] as $location) {
            if ($currLocationName == $location['name']) {

                if ($location['date_active'] === '0000-00-00 00:00:00' && $location['date_expire'] === '0000-00-00 00:00:00') {
                    //set to zeros
                    return true;
                } else if (
                    (strtotime('now') > strtotime($location['date_active']))
                    and
                    (strtotime('now') < strtotime($location['date_expire']))
                ) {
                    return true;
                }

            }
        }
        //die('false');
        return false;
    }

    /**
     * Check if a slug was created on UpdateCase.com
     * @param $slug
     * @return bool
     */
    public function doesSlugExist($slug)
    {

        foreach ($this->pages as $page) {
            if ($page['slug'] == $slug) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does not require to change the slug to get content
     * @param $slug
     * @param $location_to_check
     * @param $element_to_check
     * @param $lang
     * @return false|string|void
     */
    public function getByWithoutLoading($slug, $location_to_check, $element_to_check, $lang = false)
    {

        if (!$this->isPrepared()) {
            return false;
        }

        foreach ($this->pages as $page) {

            if ($page['slug'] == $slug) {

                foreach ($page['Location'] as $location) {
                    if ($location['name'] == $location_to_check) {
                        foreach ($location['Element'] as $element) {
                            if ($element['name'] == $element_to_check) {

                                if ($this->isCurrentLanguage($element['language'])) {
                                    if (isset($element['Revision'][0])) {
                                        return trim($element['Revision'][0]['content_text']);
                                    }
                                }

                            }
                        }
                    }
                }
            }

        }
    }

    /**
     * List of slugs from a tag useful to get and display all pages from a tag
     * @param $tagName
     * @param $sortBy
     * @param $ensureAllTags
     * @param $limitToLang
     * @return array
     */
    public function getPageSlugsByTag($tagName, $sortBy = 'DATE-ASC', $ensureAllTags = false, $limitToLang = false)
    {

        $pageNames = array();

        $sort = array();
        $available = '';
        //get the page

        //pr ($this->pages);exit;


        //pr ($tagName);
        //exit;

        $pagesWithTags = array();

        foreach ($this->pages as $keyPage => $page) {

            if (!empty($page['Tag'])) {
                //loop through our tags
                //if any are missing not match
                if (is_array($tagName)) {
                    foreach ($tagName as $eachTagName) {
                        foreach ($page['Tag'] as $pageTag) {
                            if ($pageTag['name'] == $eachTagName) {
                                $pagesWithTags[$page['slug']]['tag'][$eachTagName] = $eachTagName;
                                $pagesWithTags[$page['slug']]['date'] = $page['date'];
                            }
                        }
                    }
                } else {
                    foreach ($page['Tag'] as $pageTag) {
                        if ($pageTag['name'] == $tagName) {
                            $pagesWithTags[$page['slug']]['tag'][$tagName] = $tagName;
                            $pagesWithTags[$page['slug']]['date'] = $page['date'];

                        }
                    }
                }


            }
        }

        if ($ensureAllTags) {
            foreach ($pagesWithTags as $slug => $eachPageWithTags) {
                //we need all our tags, so unset the pages that do not have both
                if (is_array($tagName)) {
                    foreach ($tagName as $eachTagName) {
                        if (!isset($eachPageWithTags['tag'][$eachTagName])) {
                            unset($pagesWithTags['tag'][$slug]);
                        }
                    }
                } else {
                    if (!isset($eachPageWithTags['tag'][$tagName])) {
                        unset($pagesWithTags['tag'][$slug]);
                    }
                }
            }
        }


        //pr ($sortBy);
        //exit;
        //pr ($pagesWithTags);
        //exit;
        if ($sortBy == 'ASC') {
            //sort by the date which is the key
            ksort($pagesWithTags);
        } else if ($sortBy == 'DESC') {
            krsort($pagesWithTags);
        } else if ($sortBy == 'DATE-ASC') {
            uasort($pagesWithTags, function ($item1, $item2) {
                if ($item1['date'] == $item2['date']) return 0;
                return $item1['date'] > $item2['date'] ? -1 : 1;
            });
        } else if ($sortBy == 'DATE-DESC') {
            //pr ('here');
            uasort($pagesWithTags, function ($item1, $item2) {
                if ($item1['date'] == $item2['date']) return 0;
                return $item1['date'] > $item2['date'] ? -1 : 1;
            });
        }

        //pr ($pagesWithTags);
        //exit;
        $return = array();
        foreach ($pagesWithTags as $slug => $tags) {
            $return[$slug] = $slug;
        }
        return $return;
    }

    /**
     * Languages present in current page (slug) within ALL locations / elements
     * @return array
     */
    public function getPageLangs()
    {
        $page = $this->page;

        $langs = array();

        foreach ($page['Location'] as $location) {
            foreach ($location['Element'] as $element) {
                $langs[
                $this->getBaseLang($element['language'])
                ] =
                    $this->getBaseLang($element['language']);
            }
        }
        return $langs;
    }

    /**
     * Page date which is set on UpdateCase.com
     * @param $format # PHP date format
     * @return false|string
     */
    public function getPageDate($format = 'Y-m-d H:i:s')
    {

        $date = strtotime($this->page['date']);
        $lang = $this->currLang();
        if ($lang == 'fr') {

            if ($format == 'Y') {
                return date($format, $date);
            } else {
                //french
                setlocale(LC_ALL, 'fr_FR.UTF-8');
                //echo date('D d M, Y');
                //return strftime("%a %d %b %Y", $date);
                return strftime("%e %B %Y", $date);
                //$shortDate = strftime("%d %b %Y", $date);
            }
        } else {
            return date($format, $date);
        }

    }

    /**
     * The page that was loaded from changeSlug
     * @return array
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * All the tags for every page within the loaded variant
     * @param $ignore # array allowing to ignore a list
     * @return array
     */
    public function getTagsFromAllPages($ignore = [])
    {
        //pr ($this->pages);

        $allTags = array();
        foreach ($this->pages as $this->page) {
            $allTags = $allTags + $this->getTags($ignore);
        }
        return $allTags;

    }

    /**
     * Is the tag present in the loaded page (slug)
     * @param $tag
     * @return bool
     */
    public function isTagPresent($tag)
    {
        $tags = $this->getTags();

        if (isset($tags[$tag])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * The tags from the current loaded page (slug)
     * @param $ignore
     * @return array
     */
    public function getTags($ignore = [])
    {
        //pr ($this->page);

        if (!$this->isPrepared()) {
            return false;
        } else {
            $tags = array();
            if (isset($this->page['Tag'])) {
                foreach ($this->page['Tag'] as $tag) {
                    if (in_array($tag['name'], $ignore)) {
                        //ignore
                    } else {
                        $tags[$tag['name']] = $tag['name'];
                    }
                }
            }
        }

        //pr ($tags);
        return $tags;
        //exit;
    }

    /**
     * A unique ID for the revision on the element. Each time text is changed the ID will change
     * @param $locationName
     * @param $elementName
     * @param $groupName
     * @return false|mixed
     */
    public function getIdBy($locationName, $elementName, $groupName = false)
    {

        if ($groupName == 'false') $groupName = false;

        if (empty($this->page)) {
            $this->addDebugMessage('Page not setup');
            return false;
        }

        $element = $this->getElement($locationName, $elementName, $groupName);
        //@todo add is location active

        if (isset($element['Revision'])) {
            return $element['Revision'][0]['id'];
        } else {
            return false;
        }
    }

    /**
     * @param $tagName
     * @param $sortBy
     * @param $location
     * @param $element
     * @param $group
     * @param $limit
     * @param $offset
     * @param $options
     * @return array|void
     */
    public function getPageSlugsByTagWithLocationElement($tagName, $sortBy = 'ASC', $location, $element, $group = false, $limit = false, $offset = false, $options = false)
    {

        $this->addDebugMessage('getPageSlugsByTagWithLocationElement', true);

        if ($this->isPrepared()) {

            $pageNames = array();
            $sort = array();
            foreach ($this->pages as $keyPage => $page) {
                //pr ($page); exit;

                if (!$this->existsInPage($page['slug'], $location, $element, $group)) {
                    //die ('does not exist');
                    continue;
                }

                //pr ($this->language);exit;

                $pagesHasStuffForThisLanguage = false;
                //let's ensure we have the following location / element
                foreach ($page['Location'] as $tierLocation) {
                    foreach ($tierLocation['Element'] as $tierElement) {

                        //dd($tierElement);
                        if ( $this->isCurrentLanguage($tierElement['language']) ) {
                            $pagesHasStuffForThisLanguage = true;
                        }
//                        //pr ($tierElement);exit;@todo by sacha
//                        if(isset($this->possibleLanguages[
//                            $tierElement['language']
//                            ])){
//
//                            if ($this->language == $this->possibleLanguages[$tierElement['language']]) {
//                                $pagesHasStuffForThisLanguage = true;
//                            }
//                         }
                    }
                }

                //skip this page it has not element of this language
                if (!$pagesHasStuffForThisLanguage) continue;

                if (!empty($page['Tag'])) {
                    foreach ($page['Tag'] as $tag) {

                        if (is_array($tagName)) {
                            if (in_array($tag['name'], $tagName)) {
                                //this tag is present
                                $sort[$page['slug']] = strtotime($page['date']);
                            }
                        } else {
                            if ($tag['name'] == $tagName) {
                                //this tag is present
                                $sort[$page['slug']] = strtotime($page['date']);
                            }
                        }
                    }
                }
            }

            //die('after');


            if ($sortBy == 'ASC') {
                //sort by the date which is the key
                asort($sort);
            } else {
                arsort($sort);
            }

            foreach ($sort as $slug => $num) {
                $pageNames[$slug] = $slug;
            }

            if ($options) {
                if ($options == 'SHUFFLE') {

                    $keys = array_keys($pageNames);
                    shuffle($keys);
                    foreach ($keys as $key) {
                        $new[$key] = $pageNames[$key];
                    }
                    $pageNames = $new;
                }

            }
            //pr ($pageNames);exit;

            $this->total = count($pageNames);

            if (empty($pageNames)) {
                return array();
//            $message = 'Tag not found: ' . $tagName;
//            return $this->missingMessage($message);
                exit;
            }

            if (!$limit) {
                return $pageNames;
            } else {
                $pageNames = array_slice($pageNames, (($offset - 1) * $limit), $limit);
                return $pageNames;
            }

            //pr ($pageNames);
            //exit;

        } else {


        }


    }

    /**
     * Get a ALT string to be used in the image tag eg img src="" alt="<?= $updateCase->getImageAltTag(.....
     * @param $location
     * @param $element
     * @param $group
     * @param $size
     * @return string
     */
    public function getImageAltTag($location, $element, $group = false, $size = 'medium')
    {
        if ($size != 'medium') {
            $alt = 'alt="' . $location . '-' . $element . '-' . $group . '-' . $size;
        } else {
            $alt = 'alt="' . $location . '-' . $element . '-' . $group;
        }
        $alt = rtrim($alt, '-');
        $alt .= '"'; //close the hyphen

        return $alt;
    }

    /**
     * Translate a word using the translation function located in UpdateCase.com
     * @param $term
     * @param $element # eg en->fr meaning we are adding english terms which appear as french when echo'd
     * @return false|mixed|string
     */
    public function Translate($term, $element = 'en->fr')
    {

        if (empty($term)) {
            return $term;
        }

        if ($this->isCurrentLanguage('en')) {
            //do we have a en->en translations or modifications to the same word
            $translated = $this->prepareTranslation('en->en', $term);
        } else {
            $translated = $this->prepareTranslation($element, $term);
        }

        return $translated;
    }

    private function prepareTranslation($element_to_check, $term)
    {

        //$translations = $this->getByWithoutLoading('All', 'Translations', $element, 'en-ca');

        if (!$this->isPrepared()) {
            return false;
        } else {

            //get the page

            $translations = '';
            //pr ($this->pages);exit;
            foreach ($this->pages as $page) {

                if ($page['slug'] == 'All') {

                    foreach ($page['Location'] as $location) {
                        if ($location['name'] == 'Translations') {
                            foreach ($location['Element'] as $element) {
                                if ($element['name'] == $element_to_check) {


                                    if (isset($element['Revision'][0])) {
                                        $translations = trim($element['Revision'][0]['content_text']);
                                    }


                                }
                            }
                        }
                    }
                }

            }
        }

        //pr ($translationsText);

        //pr ($translations);

        //echo 'trans'.$translations.'222';
        //exit;

        if (!empty($translations)) {
            //pr ($this->getByWithoutLoading('All', 'Translations', $element));
            $title = $this->cleanUpStringForQuotedSections($translations);
            //pr ($title);exit;
        } else {
            //die ('no transl');
            //no translation
            return $term;
        }
        $title = str_replace("\n", "<-->", $title);
        $title = str_replace("<br><br/>", "<-->", $title);
        $title = str_replace("<br />", "<-->", $title);
        $title = str_replace("<br/>", "<-->", $title);
        $title = trim($title);
        //echo $title;
        //exit;
        $tmp = explode("<-->", $title);
        //print_r ($tmp);exit;
        //pr ($tmp);
        //exit;

        foreach ($tmp as $eRow) {
            //print_r ($eRow);
            $tmp_term = explode(">", trim($eRow));
            //pr ($tmp_term);exit;
            if (strtolower(trim($tmp_term[0])) == strtolower(trim($term))) {
                if (empty($tmp_term[1])) {
                    return $term;
                } else {
                    return $tmp_term[1];
                }

            }
        }

        //IF WE ARE TESTING THEN ADD FOR FUTURE
//		$debugSTATUS = $this->decideDebug();
//
//		if ($debugSTATUS == 'ON') {
//			//keep track if we do NOT have a translation and add to the file
//			$this->keepTrackOfNewTranslations($term);
//		}

        return $term;
    }

    private function keepTrackOfNewTranslations($newWord)
    {


        if (!file_exists('updateCaseTranslations.txt')) {
            file_put_contents('updateCaseTranslations.txt', '');
        }
        //check if we already have this word
        $current = file_get_contents('updateCaseTranslations.txt');
        //pr ($current);
        $lines = explode("\n", $current);
        foreach ($lines as $line) {
            $check = str_replace(">", "", $line);


            if (strtoupper($newWord) == strtoupper($check)) {
                //already have it
                return false;
            } else {
                //save it
            }
        }

        if (empty($newWord)) {
            return false;
        }

        $current = $current . "\n" . $newWord . '>';
        file_put_contents('updateCaseTranslations.txt', $current);
    }

    /**
     * Check if this location / element can be used and exists on UpdateCase.com
     * @param $locationName
     * @param $elementName
     * @param $groupName
     * @return bool|void
     */
    public function exists($locationName, $elementName = false, $groupName = false)
    {

        if (!$this->isPrepared()) {
            $this->addDebugMessage('NOT isPrepared');
            return false;
        }

        $debug = false;
        $this->addDebugMessage('exists: '.$this->locationViewString($locationName , $elementName, $groupName));

        //pr ($this->page['Location']);exit;
        foreach ($this->page['Location'] as $location) {
            //echo $locationName.' -> '.$location->name."<br/>";
            if ($locationName !== $location['name']) {
                continue;
            } else {


                //pr ($location);exit;
                //the location matches

                if (!$elementName) {
                    //no element so let's return true
                    return true;
                } else {


                    //we are looking for an element
                    foreach ($location['Element'] as $element) {

                        if (!$this->isCurrentLanguage($element['language'])) continue;

                        if ($elementName !== $element['name']) {


                            //echo 'does not '.$elementName;
                            //exit;
                            continue;
                        } else {

                            //pr ($element);exit;

                            if (!$groupName) {
                                return true;
                            } else {

                                if ($element['groupBy'] === $groupName) {
                                    return true;
                                }
                            }

                        }

                    }

                }

            }

            return false;

        }


//            $quit = $this->setup($locationName, $elementName, $groupName);
//            if ($quit) {
//                return false;
//            }
//            if (isset($this->element->Revision[0])) {
//                return true;
//            } else {
//                return false;
//            }
    }

    /**
     * When it exists by if the content is empty
     * @param $locationName
     * @param $elementName
     * @param $groupName
     * @return bool
     */
    public function isEmpty($locationName, $elementName = false, $groupName = false)
    {

        //pr ($this->page);exit;

        $test = $this->getContentBy($locationName, $elementName, $groupName);

        if (empty($test)) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Not empty so there IS content available
     * @param $locationName
     * @param $elementName
     * @param $groupName
     * @return bool
     */
    public function isNotEmpty($locationName, $elementName = false, $groupName = false)
    {

        //pr ($this->page);exit;
        $test = $this->getContentBy($locationName, $elementName, $groupName);

        //pr ($test);exit;
        if (!empty($test)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Useful for searches to see if a string of text is available
     * @param $search # string search term
     * @param $locationName
     * @param $elementName # optional
     * @param $groupName # optianal
     * @return bool
     */
    public function doesContain($search, $locationName, $elementName = false, $groupName = false)
    {

        //pr ($this->page);exit;
        $test = $this->getContentBy($locationName, $elementName, $groupName);

        if (!empty($test)) {
            if (strpos($test, $search) !== false) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    /**
     * @param $slug
     * @param $locationName
     * @param $elementName # optional
     * @param $groupName # optional
     * @return bool|void
     */
    public function existsInPage($slug, $locationName, $elementName = false, $groupName = false)
    {

        //echo 'hi';
        //pr ($locationName);

        //pr ($elementName);

        // pr ($this->page);exit;

        $this->addDebugMessage('existsInPage' . $this->locationViewString($locationName, $elementName, $groupName), false);

        foreach ($this->pages as $page) {

            if ($slug != $page['slug']) {
                continue;
            }

            //pr ($this->page['Location']);exit;
            foreach ($page['Location'] as $location) {

                //echo $locationName.' -> '.$location->name."<br/>";
                if ($locationName != $location['name']) {

                    $this->addDebugMessage('Location does not match: ' . $locationName . ' / ' . $location['name'], false);
                    continue;
                } else {
                    $this->addDebugMessage('Matches: ' . $locationName . ' / ' . $location['name'], false);

                    //pr ($location);exit;
                    //the location matches

                    if (!$elementName) {
                        //no element so let's return true
                        return true;
                    } else {

                        //we are looking for an element
                        foreach ($location['Element'] as $element) {

                            //pr ($location->Element);

                            if ($elementName != $element['name']) {
                                //echo 'does not '.$elementName;
                                //exit;
                                continue;
                            } else {

                                //pr ($element);exit;

                                if (!$groupName) {
                                    return true;
                                } else {

                                    if ($element['groupBy'] == $groupName) {
                                        return true;
                                    }
                                }

                            }

                        }

                    }


                }

                return false;
            }
        }


//            $quit = $this->setup($locationName, $elementName, $groupName);
//            if ($quit) {
//                return false;
//            }
//            if (isset($this->element->Revision[0])) {
//                return true;
//            } else {
//                return false;
//            }
    }

    /**
     * Search feature to give string or array of search terms to get the slug where they are available
     * @param array|string $searches # allows to search for slugs from
     * @param $limitToTag
     * @return array|false|void # this returns a complex array with all the data
     */
    public function getPagesBySearch($searches, $limitToTag = false)
    {
        if ($this->isPrepared()) {
            $results = array();

            if (is_array($searches)) {
                //already an array
            } else {

                $searches = array(0 => $searches);
            }

            $available = '';
            //get the page
            foreach ($this->pages as $page) {

                $pageTags = array();
                //pr ($page);exit;

                $tags = array();
                foreach ($page['Tag'] as $tag) {
                    $tags[$tag['name']] = $tag['name'];
                    $pageTags[$tag['name']] = $tag['name'];
                }

                if (!$limitToTag) {
                    //not limit
                } else {
                    if (!in_array($limitToTag, $pageTags)) {
                        continue;//skip this page
                    }
                }

                foreach ($page['Location'] as $location) {


                    foreach ($location['Element'] as $element) {

                        foreach ($element['Revision'] as $revision) {

                            foreach ($searches as $search) {
                                if (stripos($revision['content_text'], strtolower($search)) !== false) {
                                    //echo 'true';
                                    $found = array(
                                        'slug' => $page['slug'],
                                        'tags' => implode(',', $tags),
                                        'location' => $location['name'],
                                        'element' => $element['name'],
                                        'language' => $element['language'],
                                        'text' => strip_tags($revision['content_text'])
                                    );
                                    $results[$page['slug']] = $found;
                                }
                            }


                            //pr ($revision);exit;
                        }
                    }
                }

            }

            //pr ($results);
            if (!empty($results)) {
                return $results;
            } else {
                return false;
            }
        } else {

        }


    }


    /**
     * Allow to search for a string or array or strings and get the slugs where they are available
     * @param array|string $searches
     * @return array|false # slugs which contain the search
     */
    public function getPageSlugsBySearch($searches)
    {

        $results = array();

        if (is_array($searches)) {
            //already an array
        } else {

            $searches = array(0 => $searches);
        }

        $available = '';
        //get the page
        foreach ($this->pages as $page) {


            foreach ($page['Location'] as $location) {


                foreach ($location['Element'] as $element) {

                    foreach ($element['Revision'] as $revision) {


                        foreach ($searches as $search) {

                            if (stripos($revision['content_text'], strtolower($search)) !== false) {
                                //echo 'true';
                                $found = array(
                                    'slug' => $page['slug'],
                                    'location' => $location['name'],
                                    'element' => $element['name'],
                                    'language' => $element['language'],
                                    'text' => strip_tags($revision['content_text'])
                                );
                                $results[$page['slug']] = $page['slug'];
                            }

                        }


                        //pr ($revision);exit;
                    }
                }
            }

        }


        //pr ($results);
        if (!empty($results)) {
            return $results;
        } else {
            return false;
        }

    }

    /**
     * All pages (slugs) are stored on UpdateCase.com with a date, this allows to get a list from a specific year
     * @param $year
     * @return array|false
     */
    public function getPageSlugsByYear($year)
    {


        $results = array();

        $available = '';
        //get the page
        foreach ($this->pages as $page) {

//			pr ($page['date']);
//			pr (
//				date('Y', strtotime($page['date']))
//			);
//			exit;
            if (date('Y', strtotime($page['date'])) == $year) {

            } else {
                continue;
            }

            $results[$page['slug']] = $page['slug'];

//
//			pr ( date('Y', strtotime($page['date'])) );
//			pr ($year);
//			pr ($page);
//			exit;


        }

        //pr ($results);
        if (!empty($results)) {
            return $results;
        } else {
            return false;
        }

    }

    /**
     * DEPRECATED use getFileBy instead
     */
    public function getFile($location, $element, $group = false)
    {
        return $this->getFileBy($location, $element, $group);
    }

    /**
     * List of location names from the currently loaded page (slug)
     * @param $ignore # conditional
     * @return void
     */
    public function getLocationNames($ignore = false)
    {


        $this->count = 0;

        if (!$this->page) {
            $msg = 'Page not loaded';
            $this->addDebugMessage($msg);
            die ($msg);

        }

        foreach ($this->page['Location'] as $location) {
            if ($ignore == $location['name']) {
                //we want to ignore this location
            } else {
                $this->locationNames[$location['name']] = $location['name'];
            }
        }

        return $this->locationNames;
    }

    /**
     * @param $locationName
     * @param $field
     * @return array
     */
    public function getUniqueNameForFieldByLocation($locationName, $field)
    {

        $categories = array();

        //pr ($this->page);exit;
        foreach ($this->page['Location'] as $location) {
            if ($location['name'] == $locationName) {
                foreach ($location['Element'] as $element) {

                    //pr ($element);exit;

                    if ($element['name'] == $field) {
                        //this is our field
                        //pr ($element);exit;
                        $categories[str_replace(' ', '', $element['Revision'][0]['content_text'])] = $element['Revision'][0]['content_text'];
                    }

                }
            }
        }

        return $categories;

    }

////////////////////////////////////// SUPPORT METHODS ///////////////////////

    private function getElement($locationName, $elementName, $groupName = false)
    {
        if (!$this->isPrepared()) {
            $this->addDebugMessage('getElement not prepared');
            return false;
        }

        $this->addDebugMessage('getElement: '.$this->locationViewString($locationName, $elementName, $groupName), false);

        $locationKey = $this->getLocationKey($locationName);

        $elementKey = $this->getElementKey($locationKey, $elementName, $groupName);

        if (isset($this->page['Location'][$locationKey]['Element'][$elementKey])) {
            return $this->page['Location'][$locationKey]['Element'][$elementKey];
        } else {
            $this->addDebugMessage('ERROR: Element: "'.$elementName.'" Location: "'.$locationName.'" group: "'.$groupName.'" NOT FOUND - '.' slug: '.$this->page['slug'].' lang: '.$this->lang, true);
        }

    }

    private function isGroupCorrect($groupToFind, $currentLoopedGroup) {
        if ($groupToFind == $currentLoopedGroup) {
            //exactly the same so good
            $this->addDebugMessage('GroupMatch:'.$groupToFind, false);
            return true;
        } else {
            $this->addDebugMessage('GroupMismatch:'.$groupToFind.'/'.$currentLoopedGroup, false);
            return false;
        }
    }

    private function getElementKey($locationKey, $elementToFind, $groupToFind) {
        if (!$this->isPrepared()) {
            $this->addDebugMessage('getElementKey not prepared');
            return false;
        }
        $this->addDebugMessage('getElementKey: '.$this->locationViewString($locationKey, $elementToFind, $groupToFind), true);
        $groupMsg = '';
        if (isset($this->page['Location'])) {
            if (isset($this->page['Location'][$locationKey])) {
                foreach ($this->page['Location'][$locationKey]['Element'] as $elementKey => $element) {

                    $this->addDebugMessage(' checkingElmKey:'.$elementKey, true);
                    //@todo add all the logic about if it's a dated one etc
                    if ($elementToFind == $element['name']) {
                        if ($this->isGroupCorrect($groupToFind, $element['groupBy'])) {
                            if ($this->isCurrentLanguage($element['language'])) {
                                $this->addDebugMessage('FOUND ELEMENT: '.$elementKey, false);
                                return $elementKey;
                            }
                        }
                    }
                    $this->addDebugMessage('ElementNameNotMatch', false);
                }
            } else {
                $this->addDebugMessage(' -LocationNOTexist', false);
            }
        } else {
            $this->addDebugMessage(' -PageLocationNOTexist', false);
        }
        //nothing
        $this->addDebugMessage('ERROR: No elements were found matching "'.$elementToFind.'" '.$groupMsg);
    }

    private function getLocationKey($locationToFind) {

        if (!$this->isPrepared()) {
            // die('not prepared in getLocationKey');
            return false;
        } else {
            $date = $this->getDate();

            //for barwards compatiblity, we will take the last lcoation, in case older onces don't have it prepared
            $locationToUse = null;

            foreach ($this->page['Location'] as $locationKey => $location) {

                $extendedLocationName = $this->getExtendedName($location['name']);
                $this->addDebugMessage('checking: '.$location['name'].' our to find is: '.$locationToFind.' extendedname is: '.$extendedLocationName, false);

                if (trim($locationToFind) === trim($extendedLocationName)) {

                    //This will check all names even with :::name at the end
                    //now we need to ensure the date is correct

                    $this->addDebugMessage('Matches: active_status: '.$location['active_status'], false);

                    if ($location['active_status'] == 0){
                        //this is active
                        //return $locationKey;
                        $locationToUse = $locationKey;
                    } else if ($location['active_status'] == 1) {
                        //by date
                        if ($location['date_active'] === '0000-00-00 00:00:00' && $location['date_expire'] === '0000-00-00 00:00:00') {
                            //set to zeros
                            //return true;
                            $locationToUse = $locationKey;
                        } else if (
                            (strtotime($date) >= strtotime($location['date_active']))
                            and
                            (strtotime($date) <= strtotime($location['date_expire']))
                        ) {
                            //return $locationKey;
                            $locationToUse = $locationKey;
                        } else {
                            $this->addDebugMessage('outside our date range - did NOT assign anything', false);
                        }
                    } else if ($location['active_status'] == 2) {
                        //NOT active
                    } else {
                        $this->addDebugMessage('did NOT assign anything', false);
                    }
                } else {
                    $this->addDebugMessage($locationToFind.' != '.$extendedLocationName, false);
                }
            }

            $this->addDebugMessage('done loop: locationToUse: '.$locationToUse, false);

            if (is_null($locationToUse)) {
                $this->addDebugMessage('ERROR: Location "'.$locationToFind.'" was NOT found (page: '.$this->page['name'].')', true);
            } else {
                $this->addDebugMessage('locationToUse: '.$locationToUse, false);
                return $locationToUse;
            }

        }
    }
    /*
     * if a date is active the loation name will be appended by :::name this will remove so it can be checked
     */
    private function getExtendedName($name){

        //handle the time specific
        $tmp = explode(':::', $name);
        if (isset($tmp[1])) {
            // this is an extended name
            return $tmp[0];
        } else {
            //this is NOT an extneded name, so we send normal name
            return $name;
        }
    }

    private function getBaseLang($lang) {
        return substr($lang, 0, 2);
    }

    private function locationViewString($location, $element, $group) {

        $string = '';
        $string .= 'loc: "'.$location.'"';
        $string .= ' elm: "'.$element.'"';

        if (empty($group)){
            $string .= ' NOgrp';
        } else {
            $string .= ' grp: "'.$group.'"';
        }

        $variant_id = $this->page['variant_id'];
        $pageSlug = $this->page['slug'];

        $string .= ' (v'.$variant_id;
        $string .= '/s'.$pageSlug.')';

        return $string;
    }

    private function prepareElementsInLocation($locationName)
    {
        foreach ($this->page['Location'] as $location) {
            if ($location['name'] == $locationName) {
                foreach ($location['Element'] as $element) {
                    if (empty($element['groupBy'])) {

                        //pr ($element);exit;
                        $this->singleNames[$element['name']] = $element['name'];
                    } else {
                        $this->groupNames[$element['groupBy']] = $element['groupBy'];
                    }
                }
            }
        }
    }

/////////////////////////////// STATIC / SIMPLE METHODS ///////////////////////

    /**
     * Text without any html tags
     * @param string $text # html content
     * @param int $limit # allow to get a portion back with '...' appended
     * @return false|string
     */
    public function getTextOnly($text, $limit = false)
    {
        if ($limit) {
            $textShort = substr(strip_tags($text), 0, $limit);
            if (strlen($text) > $limit) {
                return $textShort . '...';
            } else {
                return $textShort;
            }
        } else {
            return strip_tags($text);
        }
    }

    /**
     * Remove the image tags from a string
     * @param $string
     * @return string
     */
	public function removeImages($string)
	{
		return preg_replace("/<img[^>]+\>/i", "", $string);
	}

    /**
     * Remove the HTML elements from a string
     * @param $str
     * @return array|string|string[]|null
     */
	public function removeHtmlElements($str)
	{
		$str = preg_replace('/\<[\/]{0,1}div[^\>]*\>/i', '', $str);
		return $str;
	}

    /**
     * Easily make sure your path has either https:// OR http://
     * @param $url
     * @param $prefix # http:// OR https://
     * @return string
     */
	public function ensureHttpOrHttps($url, $prefix = 'http://')
	{
		if (substr($url, 0, 7) == 'http://') {
			return $url;
		} else if (substr($url, 0, 8) == 'https://') {
			return $url;
		} else {
			//add it
			return $prefix . $url;
		}
	}

    /**
     * Quotes will only be SINGLE QUOTES, allows to safely add to html tags that are double quotes and not break the tag
     * @param $str
     * @return array|string|string[]
     */
	private function cleanUpStringForQuotedSections($str)
	{
        return $str ? str_replace('"', "'", $str): "";
	}

    /**
     * @param $remove
     * @param $string
     * @return array|string|string[]
     */
    public function removeTextFrom($remove, $string)
    {
        return str_replace($remove, '', $string);
    }

    /**
     * DEPRECATED use ensureHttpOrHttps
     */
    public function ensureHttp($url)
    {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
        return $url;
    }

///////////////////// DEPRECIATED DELETE /////////////

    /**
     * DEPRECATED
     */
	public function isEvery($nth, $count)
	{
		//2
		if ($count == $nth) {
			return true;
		}
		return false;
	}

    /**
     * DEPRECATED
     */
	public function getSingleNamesByLocation($locationName, $sort = 'ASC', $slug = false)
	{
		if ($slug) {
			$this->loadPageBySlug($slug);
		}

		$this->groupNames = array();
		$this->singleNames = array();

		$this->prepareElementsInLocation($locationName);

		//pr ($this->singleNames);exit;
		if ($sort == 'ASC') {
			natsort($this->singleNames);
		} else {
			krsort($this->singleNames);
		}
		return $this->singleNames;
	}

    /**
     * DEPRECATED
     */
	public function getGroupNamesByLocation($locationName, $sort = 'ASC', $slug = false)
	{
		if ($slug) {
			$this->loadPageBySlug($slug);
		}

		$this->groupNames = array();
		$this->singleNames = array();

		$this->prepareElementsInLocation($locationName);

		if ($sort == 'ASC') {
			natsort($this->groupNames);
		} else {
			krsort($this->groupNames);
		}
		return $this->groupNames;
	}

    /**
     * DEPRECATED
     */
	public function getTotalRecords()
	{
		return $this->total;
	}

    /**
     * DEPRECATED
     */
	public function convertString($from, $to, $string)
	{
		foreach ($from as $kFrom => $vFrom) {
			$string = str_replace($vFrom, $to[$kFrom], $string);
		}
		//return "";
		return $string;
	}

}
