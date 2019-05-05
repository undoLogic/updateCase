<?php
/**
 * Instructions how to setup
 * Download this file from UpdateCase.com
 */
$token = 'ADD-TOKEN-FOR-SECURITY';
$pathJsonFile = '../Config/Schema/';

function writeToLog($message, $newLine = true) {

    if (is_array($message)) {
        $message = implode("\n", $message);
    }

    if ($newLine) {
        $message = "\n".date('Ymd-His').' > '.$message;
    } else {
        $message = ' > '.$message;
    }
    file_put_contents('updateCase.log', $message, FILE_APPEND);

    //echo APP.'tmp/logs/'.$type;
}
writeToLog('client receiving GET '.json_encode($_GET));

//external communcations
if (isset($_POST['token'])) {
    if ($_POST['token'] != $token) {
        writeToLog('BAD TOKEN');

        die ('405: NO ACCESS');
    } else {
        if (isset($_GET['test'])) {
            if ($_GET['test'] == 'true') {
                echo 'access granted';
            }
        }

        if (isset($_GET['version'])) {
            if ($_GET['version'] == 3) {
                writeToLog('V3');

                $decoded = json_decode($_POST['variant']);
                //$uuid = $decoded->Variant->uuid;
                $uuid = $decoded[0]->Variant->uuid;
                $variant_id = $decoded[0]->Variant->id;

                if (empty($variant_id)) $variant_id = 'unknown';

                $pathJsonFile = $pathJsonFile.$variant_id.'/';

                if (!file_exists($pathJsonFile)) {
                    mkdir($pathJsonFile);
                }

                $myfile = fopen($pathJsonFile . $uuid . ".json", "w") or die("Unable to open file!");
                fwrite($myfile, $_POST['variant']);
                fclose($myfile);

                echo 'IMPORTED';
                writeToLog('Imported');
            } else {
                echo 'Command not recognized';
                writeToLog('Command not recognized');
            }
        } else {
            writeToLog('NO VERSION SUPPLIED');
        }
    }
} else {


    //if this being accessed directly
    writeToLog('accessed directly');
    if (isset($_SERVER)) {
        if (strpos($_SERVER['SCRIPT_NAME'], 'updateCase.php') !== false) {
            //setupLocal();
        }
    }

}

//internal communications
if (class_exists('Object')) {
    // Put class TestClass here

    App::uses('Folder', 'Utility');
    App::uses('File', 'Utility');
    App::uses('HttpSocket', 'Network/Http');

    class UpdateCase extends Object
    {

        var $jsonPath = '';
        var $state = 'PROD';

        var $jsonData = array(); //all the json data
        var $hostPath = 'http://site.updatecase.com/';

        var $uuid = '';

        var $variant = array();
        var $site = array();
        var $pages = array();
        var $page = array(); //this is the page information

        var $allPages = array();

        var $language = '';
        var $possibleLanguages = array(
            'eng' => 'eng',
            'en-us' => 'eng',
            'en-ca' => 'eng',
            'eng' => 'eng',
            'fr-ca' => 'fre',
            'fre' => 'fre',
            'fra' => 'fre',
            'ALL' => 'ALL'
        );

        // loads the available json and
        function loadPageBySlug($slug) {


            $this->writeToLog('STATE: '.$this->state);

            switch($this->state) {
                case 'TEST':
                    $this->setDebugOn();

                    $variant_id = Configure::read("UpdateCase.variant_id");

                    //get latest uuid from server
                    $local_uuid = $this->getMostRecentFile($variant_id);

                    if ($this->doesNewerExist($variant_id, $local_uuid)) {
                        //download
                        $this->downloadFromUpdateCase($variant_id);

                        $local_uuid = $this->getMostRecentFile($variant_id);

                        $this->writeToLog('Newer exists: download');
                    } else {
                        //we have the most recent
                        $this->writeToLog('We have the most recent');
                    }

                    //prepare our page
                    break;
                case 'PROD':
                    $variant_id = Configure::read("UpdateCase.variant_id");

                    //get latest uuid from server
                    $local_uuid = $this->getMostRecentFile($variant_id);

                    if (empty($local_uuid)) {
                        //we do not have any files - let's get one
                        $this->downloadFromUpdateCase($variant_id);
                        $local_uuid = $this->getMostRecentFile($variant_id);
                        $this->writeToLog('PROD - no file, downloading one');
                    }
                    break;
            }

            //exit;
            $this->decideDebug();

            $this->prepareJson($variant_id, $local_uuid, $slug);

            $this->language = Configure::read('UpdateCase.language');
            if (empty($this->language)) {
                $this->language = $this->possibleLanguages['eng'];
            } else {
                $this->language = $this->possibleLanguages[$this->language];
            }
        }



        private function decideDebug() {
            $lastTimeDebugEdited = filemtime(APP . 'Config' . DS . 'core.php');
            $now = strtotime('now');
            $diff = $now - $lastTimeDebugEdited;
            if ($diff > 3600) { //it has been 15 minutes since we saved our file
                $this->writeToLog('Turning OFF debug');
                $this->setDebugOff();
            } else {
                $this->writeToLog('NOT Turning OFF debug YET');
                $this->writeToLog('NOT Turning OFF debug YET');
            }
        }

        ///////////////////////////////////////////////////////////////////////////// AUTO DEBUG
        ///
        var $on_message = "Configure::write('debug',2);";
        var $off_message = "Configure::write('debug',0);";
        function setDebugOff()
        {

            //this will set the debug to
            $path_to_file = APP . 'Config';
            $file_contents = file_get_contents($path_to_file . DS . 'core.php');
            //print_r ($file_contents);exit;

            $file_contents = $this->turnOffDebug($file_contents);
            if ($file_contents) {
                //let's save it
                file_put_contents($path_to_file . DS . 'core.php', $file_contents);
                //$this->Session->setFlash('Debug mode is now OFF');
                //echo 'Debug mode is now OFF';

            } else {
                // echo 'Debug mode already off';
            }

        }

        function setDebugOn()
        {


            //this will set the debug to
            $path_to_file = APP . 'Config';
            $file_contents = file_get_contents($path_to_file . DS . 'core.php');
            //print_r ($file_contents);exit;

            $file_contents = $this->turnOnDebug($file_contents);
            if ($file_contents) {
                //let's save it
                file_put_contents($path_to_file . DS . 'core.php', $file_contents);
                //$this->Session->setFlash('Debug mode is now OFF');
                //echo 'Debug mode is now OFF';

            } else {
                // echo 'Debug mode already off';
            }

        }

        function turnOnDebug($contents)
        {

            //if the debug mode is on then return same string
            //if the debug mode if off, replace the contents
            //        $on_message = "Configure::write('debug',2);";
            //        $off_message = "Configure::write('debug',0);";
            $pos = strpos($contents, $this->on_message);
            if ($pos === false) {
                //there is no on message, let's check if there is an off
                $pos_off = strpos($contents, $this->off_message);
                if ($pos_off === false) {
                    //there is a problem,manual intervention is required
                    $msg = 'manual intervention required the debug message needs to be exactly: ' . $this->off_message;
                    $this->writeToLog($msg);
                    die ($msg);

                } else {
                    //it's ok, there is an off, so let's repalce it
                    $contents_modified = str_replace($this->off_message, $this->on_message, $contents);
                    return $contents_modified;
                }
            } else {
                //it's already on
                return false;
            }
        }

        function turnOffDebug($contents)
        {

            //if the debug mode is on then return same string
            //if the debug mode if off, replace the contents

            $pos = strpos($contents, $this->off_message);
            if ($pos === false) {
                //there is no off message, let's check if there is an on
                $pos_off = strpos($contents, $this->on_message);
                if ($pos_off === false) {
                    //there is a problem,manual intervention is required
                    $msg = 'manual intervention required the debug message needs to be exactly: ' . $this->on_message;
                    $this->writeToLog($msg);
                    die ($msg);

                } else {
                    //it's ok, there is an off, so let's repalce it
                    $contents_modified = str_replace($this->on_message, $this->off_message, $contents);
                    return $contents_modified;
                }
            } else {
                //it's already on
                return false;
            }
        }



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
         * getting content without loading
         */
        private function getByWithoutLoading($slug, $location_to_check, $element_to_check, $lang = false)
        {


            if ($lang) {

            } else {
                $lang = $this->convertToLongLang[Configure::read("UpdateCase.language")];
            }

            if (empty($lang)) {
                $msg = 'Missing in APP_CONTROLLER: Configure::write("UpdateCase.language", "eng")';
                $this->writeToLog($msg);

            }
            //pr ($lang);exit;

            //get the page
            foreach ($this->pages as $page) {
                if ($page['slug'] == $slug) {


                    foreach ($page['Location'] as $location) {
                        if ($location['name'] == $location_to_check) {

                            foreach ($location['Element'] as $element) {
                                if ($element['name'] == $element_to_check) {


                                    if ($element['language'] == $lang) {
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


        public function getPageSlugsByTag($tagName, $sortBy = 'ASC') {

            $pageNames = array();

            $sort = array();
            $available = '';
            //get the page
            foreach ($this->pages as $keyPage => $page) {

                //pr ($page);
                //pr ($page->Tag);
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

            if ($sortBy == 'ASC') {
                //sort by the date which is the key
                asort($sort);
            } else {
                arsort($sort);
            }

            foreach ($sort as $slug => $num) {
                $pageNames[$slug] = $slug;
            }



            if (empty($pageNames)) {

                return array();

//            $message = 'Tag not found: ' . $tagName;
//            return $this->missingMessage($message);
                exit;
            }

            return $pageNames;
        }

        private function cleanUpStringForQuotedSections($str)
        {
            return str_replace('"', "'", $str);
        }


        private function prepareJson($variant_id, $local_uuid, $slug = false) {
            //open the file
            $this->writeToLog('in function');

            $this->jsonPath = APP . 'Config' . DS . 'Schema' . DS . $variant_id;
            $data = file_get_contents($this->jsonPath.DS.$local_uuid.'.json');

            //$this->writeToLog('trying to decode - if it stops here the json decode crashed');


            $this->writeToLog('trying to decode');
            try {
                $jsonData = file_get_contents($this->jsonPath.DS.$local_uuid.'.json') . ']';
                $jsonObj  = json_decode($jsonData, true);

                if (is_null($jsonObj)) {
                    $this->writeToLog('ERROR TRY part: NULL');
                    //throw ('Error');
                } else {

                }

            } catch (Exception $e) {
                $this->writeToLog('ERROR: '.'{"result":"FALSE","message":"Caught exception: ' .
                    $e->getMessage() . ' ~' . $this->jsonPath.DS.$local_uuid.'.json' . '"}');
                exit;
            }

            $json = json_decode($data, true);
            //pr ($json);
            //exit;
            $this->writeToLog('Decoded json');

            //$json[0] = (array)$json[0];

            //$this->writeToLog('JSON: '.json_last_error());

            //pr ($json);exit;
            //pr ('have data');
            //exit;

            $this->site = $json[0]['Site'];
            $this->variant = $json[0]['Variant'];
            $this->pages = $json[0]['Page'];



            foreach ($this->pages as $page) {

                if (strtolower($page['slug']) == 'all') {
                    $this->allPages = $page;
                }

                if ($page['slug'] == $slug) {
                    $this->page = $page;
                }
            }

        }

        public function getMostRecentFile($variant_id = false, $reverse = false)
        {

            $this->jsonPath = APP . 'Config' . DS . 'Schema' . DS . $variant_id;

            $dir = new Folder($this->jsonPath);
            $files = $dir->find('.*\.json');

            foreach ($files as $key => $file) {
                if (strlen($file) < 20) { //we don't want to use the older manual name of sites
                    unset($files[$key]);
                }
            }
            if (empty($files)) {
                $this->writeToLog('NO LOCAL JSON');
                return false;
            } else {

                $this->writeToLog('found: '.count($files).' file(s)');

                sort($files);
                $newestFile = end($files);
                $newestFile = str_replace(".json", '', $newestFile);

                $this->writeToLog('file: '.$newestFile, false);
                return $newestFile;
            }
        }

        private function doesNewerExist($variant_id, $newestUuid)
        {
            $HttpSocket = new HttpSocket();

            $pathToUse = $this->hostPath . 'public/variants/uuid/' . $variant_id . '/' . $newestUuid;

            //pr ($pathToUse);exit;
            $this->writeToLog('get file from updateCase: '.$pathToUse, true);

            $response = $HttpSocket->post($pathToUse, array(
                'token' => Configure::read('updateCase.token'),
            ));

            //pr ($response->body);
            //exit;
            if (empty($response->body)) {
                $this->writeToLog('we have current file', false);
                return false;
            } else {
                $tmp = explode(":", $response->body);
                switch($tmp[0]) {
                    case '200':
                        return false;
                        break;
                    default:
                        return true;
                }
            }
        }

        private function downloadFromUpdateCase($variant_id, $specific_uuid = false)
        {
            $this->jsonPath = APP . 'Config' . DS . 'Schema' . DS;


            //die ('hi');
            $HttpSocket = new HttpSocket();

            $pathToUse = $this->hostPath . 'public/variants/checkIn/' . $variant_id . '/' . $specific_uuid;

            //pr ($pathToUse);
            //exit;

            $this->writeToLog('get file from updateCase: '.$pathToUse, false);


            $response = $HttpSocket->post($pathToUse, array(
                'token' => Configure::read('Token.general'),
            ));

            //pr ($response);
            //exit;

            if (empty($response->body)) {
                $this->writeToLog('Nothing available to get', false);
                return false;
            } else {

                $json = json_decode($response->body, true);
                $uuid = $json[0]['Variant']['uuid'];

                $folder = $this->jsonPath.$variant_id;

                $dir = new Folder($folder, true, 0775);

                file_put_contents($folder.DS.$uuid.'.json', $response->body);

                $this->writeToLog('Downloaded: '.$uuid, false);
                return true;
            }
        }

        private function getElement($locationName, $elementName, $groupName = false) {

            foreach ($this->page['Location'] as $location) {
                if ($location['name'] == $locationName) {
                    foreach ($location['Element'] as $element) {

                        $use = false;

                        if ($element['name'] == $elementName) {

                            if ($groupName) {

                                if ($element['groupBy'] == $groupName) {
                                    $use = true;
                                }

                            } else {
                                $use = true;
                            }
                        }

                        if ($use) {
                            if ($element['language'] == 'ALL') {

                                return $element;
                            } elseif ($this->language == $this->possibleLanguages[ $element['language'] ]) {
                                return $element;
                            } else {

                            }
                        }

                    }
                }
            }



            $this->writeToLog('No specific element found, lets check the all');

            foreach ($this->allPages['Location'] as $location) {
                if ($location['name'] == $locationName) {
                    foreach ($location['Element'] as $element) {

                        $use = false;

                        if ($element['name'] == $elementName) {

                            if ($groupName) {

                                if ($element['groupBy'] == $groupName) {
                                    $use = true;
                                }

                            } else {
                                $use = true;
                            }
                        }

                        if ($use) {
                            if ($element['language'] != 'ALL') {

                                return $element;
                            } elseif ($this->language == $this->possibleLanguages[ $element['language'] ]) {
                                return $element;
                            } else {

                            }
                        }

                    }
                }
            }

            $this->writeToLog('No Element found: '.$locationName.' '.$elementName.' '.$groupName);
            return false;
        }
        public function getContentBy($locationName, $elementName, $groupName = false) {

            if ($groupName == 'false') $groupName = false;

            if (empty($this->page)) {
                $this->writeToLog('Page not setup', true);
                return false;
            }

            $element = $this->getElement($locationName,$elementName,$groupName);
            //@todo add is location active

            return $element['Revision'][0]['content_text'];
        }
        public function getIdBy($locationName, $elementName, $groupName = false) {

            if ($groupName == 'false') $groupName = false;

            if (empty($this->page)) {
                $this->writeToLog('Page not setup', true);
                return false;
            }

            $element = $this->getElement($locationName,$elementName,$groupName);
            //@todo add is location active

            if (isset($element['Revision'])) {
                return $element['Revision'][0]['id'];
            } else {
                return false;
            }
        }

        public function getMetaTitle()
        {
            //do we have a set slug



            $title = '';
            $slug = Configure::read("UpdateCase.slug");


            if (!empty($slug)) { //we have a page specific
                if ($this->doesSlugExist($slug)) {
                    $title = $this->cleanUpStringForQuotedSections($this->getByWithoutLoading($slug, $this->seoLocationName, 'title'));
                }
            }
            if (empty($title)) {

                if ($this->doesSlugExist('All')) {
                    $title = $this->cleanUpStringForQuotedSections($this->getByWithoutLoading('All', $this->seoLocationName, 'title'));
                }
            }

            return $title;

            //do we have a all page with meta
            //return false;
        }

        public function getMetaDescription() {

            $field = '';
            $slug = Configure::read("UpdateCase.slug");

            if (!empty($slug)) { //we have a page specific
                if ($this->doesSlugExist($slug)) {
                    $field = $this->cleanUpStringForQuotedSections($this->getByWithoutLoading($slug, $this->seoLocationName, 'description'));
                }
            }
            if (empty($title)) {

                if ($this->doesSlugExist('All')) {
                    $field = $this->cleanUpStringForQuotedSections($this->getByWithoutLoading('All', $this->seoLocationName, 'description'));
                }
            }
            return $field;
        }
        public function getMetaKeywords() {
            $field = '';
            $slug = Configure::read("UpdateCase.slug");

            if (!empty($slug)) { //we have a page specific
                if ($this->doesSlugExist($slug)) {
                    $field = $this->cleanUpStringForQuotedSections($this->getByWithoutLoading($slug, $this->seoLocationName, 'keywords'));
                }
            }
            if (empty($title)) {

                if ($this->doesSlugExist('All')) {
                    $field = $this->cleanUpStringForQuotedSections($this->getByWithoutLoading('All', $this->seoLocationName, 'keywords'));
                }
            }
            return $field;
        }

        public function getMetaProperty($name) {
            $desc = '';

            //do we have a set slug
            $slug = Configure::read("UpdateCase.slug");
            if (!empty($slug)) { //we have a page specific
                if ($this->doesSlugExist($slug)) {
                    $desc = $this->cleanUpStringForQuotedSections($this->getByWithoutLoading($slug, $this->seoLocationName, $name));
                }
            }

            if (empty($desc)) {
                if ($this->doesSlugExist('All')) {
                    $desc = $this->cleanUpStringForQuotedSections($this->getByWithoutLoading('All', $this->seoLocationName, $name));
                }
            }

            return $desc;

            //do we have a all page with meta
            //return false;
        }

        //OG
        public function getMetaOgLocale($lang) {


            $send = array(
                'en' => 'en_CA',
                'fr' => 'fr_CA',
                'eng' => 'en_CA',
                'fre' => 'fr_CA'
            );
            if (isset($send[ $lang ])) {
                return $send[ $lang ];
            }
            return false;
        }
        public function getMetaOgLocaleAlternate($lang) {
            $send = array(
                'eng' => 'fr_CA',
                'en' => 'fr_CA',
                'fre' => 'en_CA',
                'fr' => 'en_CA'
            );
            if (isset($send[ $lang ])) {
                return $send[ $lang ];
            }
            return false;
        }
        public function getMetaOgUrl($webroot, $params) {

            return $webroot.$params->url;

            //pr ($webroot);
            //pr ($params);
            //pr ($webroot. ltrim($params->here, '/'));exit;
        }
        public function getMetaOgSiteName()
        {
            //do we have a set slug

            $title = '';
            $slug = Configure::read("UpdateCase.slug");


            if (!empty($slug)) { //we have a page specific
                if ($this->doesSlugExist($slug)) {
                    $title = $this->cleanUpStringForQuotedSections($this->getByWithoutLoading($slug, $this->seoLocationName, 'og-site_name'));
                }
            }
            if (empty($title)) {

                if ($this->doesSlugExist('All')) {
                    $title = $this->cleanUpStringForQuotedSections($this->getByWithoutLoading('All', $this->seoLocationName, 'og-site_name'));
                }
            }

            return $title;

            //do we have a all page with meta
            //return false;
        }
        public function getMetaOgImage($webroot = false)
        {
            //do we have a set slug

            $imageUrl = '';
            $slug = Configure::read("UpdateCase.slug");


            if (!empty($slug)) { //we have a page specific
                if ($this->doesSlugExist($slug)) {
                    $this->loadPageBySlug($slug);
                    $image = $this->getImage('SEO', 'og-image');
                }
            }
            if (empty($title)) {
                $this->loadPageBySlug('All');

                if ($this->doesSlugExist('All')) {
                    $image = $this->getImage('SEO', 'og-image');
                }
            }

            if ($webroot) {
                $imageUrl = $webroot.$image;
            } else {
                $imageUrl = $image;
            }
            //$title = str_replace("<img src='", '', $title);
            // $title = str_replace("' />", '', $title);
            return $imageUrl;

            //do we have a all page with meta
            //return false;
        }

        var $seoLocationName = 'SEO';


        var $convertToLongLang = array(
            'eng' => 'en-ca',
            'fre' => 'fr-ca'
        );

        public function getPageSlugsByTagWithLocationElement($tagName, $sortBy = 'ASC', $location, $element, $group = false, $limit = false, $offset = false, $options = false) {

            $pageNames = array();
            //$this->page = false;

            $sort = array();

            //get the page

            //pr ($this->pages);exit;

            foreach ($this->pages as $keyPage => $page) {


               // pr ($page);

                if (!$this->existsInPage($page['slug'], $location, $element, $group)) {
                    //die ('does not exist');
                    continue;
                }


                //pr ($this->language);exit;


                $pagesHasStuffForThisLanguage = false;
                //let's ensure we have the following location / element
                foreach ($page['Location'] as $tierLocation) {
                    foreach ($tierLocation['Element'] as $tierElement) {

                        //pr ($tierElement);exit;

                        if ($this->language == $this->possibleLanguages[ $tierElement['language'] ]) {
                            $pagesHasStuffForThisLanguage = true;
                        }
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
                    foreach($keys as $key) {
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

        }


        public function getImageAltTag($location, $element, $group = false, $size = 'medium') {
            if ($size != 'medium') {
                $alt = 'alt="' . $location . '-' . $element . '-' . $group . '-' . $size;
            } else {
                $alt = 'alt="' . $location . '-' . $element . '-' . $group;
            }
            $alt = rtrim($alt, '-');
            $alt .= '"'; //close the hyphen

            return $alt;
        }


        public function getImage($location, $element, $group = false, $size = 'medium')
        {

            if ($group == 'false') {
                $group = false;
            }
            //pr ($this->page);
            //return false;

            //APP.'webroot'.DS.
            $cache = 'images' . DS . Configure::read("UpdateCase.variant_id") . DS;

            $element = $this->getElement($location, $element, $group);

            if (!isset($element['Revision'][0])) {
                $this->writeToLog('No revision for the image');
                return false;
            } else {
                $mime = $element['Revision'][0]['mime'];
                $id = $element['Revision'][0]['id'];

                if ($mime == 'image/jpeg') {
                    $filename = $id . '.jpg';
                } elseif ($mime == 'image/png') {
                    $filename = $id . '.png';
                } else {
                    //pr ($this->revision);
                    $this->writeToLog('cannot load image', true);
                    //echo $message;
                    //exit;
                    return false;
                }
                //pr ($cache.$filename);exit;
                //does a cached version exist
                $file = new File($cache . $filename);
                $this->writeToLog('open image: '.$cache.$filename);

                // pr ($filename);exit;

                if ($file->exists()) {
                    //return the local file
                    return $cache . $filename;
                } else {
                    //create the file locally
                    $this->writeToLog('create folder: '.$cache);

                    $dir = new Folder($cache, true, 0775);

                    if (!file_exists($cache)) {
                        $this->writeToLog('Image Cache missing: '.$cache);
                    }

                    if (file_exists($cache)) {
                        $imageLink = 'http://files.setupcase.com/ImageId/' . $id . '/' . $size . '/pic.jpg';
                        $this->writeToLog('Writing image: '.$imageLink.' to '.$cache . $filename);
                        $file->write(file_get_contents($imageLink));
                        return $cache . $filename;
                    } else {
                        //something went wrong with creating the folder, so let's just return the link from our server
                        $imageLink = 'http://files.setupcase.com/ImageId/' . $id . '/' . $size . '/pic.jpg';
                        return $imageLink;
                    }
                }
            }



        }

        public function Translate($term) {
            return $term;

        }


        public function exists($locationName, $elementName = false, $groupName = false) {

            //echo 'hi';
            //pr ($locationName);

            //pr ($elementName);

            // pr ($this->page);exit;

            $this->writeToLog('Does location exist: '.$locationName.' element: '.$elementName.' gr:'.$groupName);

            //pr ($this->page['Location']);exit;
            foreach ($this->page['Location'] as $location) {
                //echo $locationName.' -> '.$location->name."<br/>";
                if ($locationName != $location['name']) {

                    $this->writeToLog('Location does not match: ' . $locationName . ' / ' . $location['name']);
                    continue;
                } else {
                    $this->writeToLog('Matches: ' . $locationName . ' / ' . $location['name']);

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

        public function isEvery($nth, $count) {
            //2
            if ($count == $nth) {
                return true;
            }
            return false;
        }


        public function getGroupNamesByLocation($locationName, $sort = false) {

            $this->groupNames = array();

            foreach ($this->page['Location'] as $location) {
                if ($location['name'] == $locationName) {
                    foreach ($location['Element'] as $element) {
                        if (empty($element['groupBy'])) {
                            //skip
                        } else {
                            $this->groupNames[ $element['groupBy'] ] = $element['groupBy'];
                        }
                    }
                }
            }

            if ($sort == 'ASC') {
                ksort($this->groupNames);
            } else {
                krsort($this->groupNames);
            }

            return $this->groupNames;

        }


        public function isNotEmpty($locationName, $elementName = false, $groupName = false) {
            //pr ($this->page);exit;
            $test = $this->getContentBy($locationName, $elementName, $groupName);
            if (!empty($test)) {
                return true;
            } else {
                return false;
            }
        }



        public function doesContain($search, $locationName, $elementName = false, $groupName = false) {

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

        public function isEmpty($locationName, $elementName = false, $groupName = false) {

            //pr ($this->page);exit;

            $test = $this->getContentBy($locationName, $elementName, $groupName);

            if (empty($test)) {
                return true;
            } else {
                return false;
            }

        }

        public function getPageDate($format = 'Y-m-d H:i:s') {

            $date = strtotime($this->page['date']);
            $lang = Configure::read('UpdateCase.language');
            if ($lang == 'fre') {

                //french
                setlocale(LC_ALL, 'fr_FR.UTF-8');
                //echo date('D d M, Y');
                //return strftime("%a %d %b %Y", $date);
                return strftime("%B %Y", $date);
                //$shortDate = strftime("%d %b %Y", $date);

            } else {
                return date($format, $date);
            }

        }

        var $total = 0;

        public function getTotalRecords()
        {
            return $this->total;
        }

        public function existsInPage($slug, $locationName, $elementName = false, $groupName = false) {

            //echo 'hi';
            //pr ($locationName);

            //pr ($elementName);

            // pr ($this->page);exit;

            $this->writeToLog('Does location exist: '.$locationName.' element: '.$elementName.' gr:'.$groupName);

            foreach ($this->pages as $page) {

                if ($slug != $page['slug']) {
                    continue;
                }

                //pr ($this->page['Location']);exit;
                foreach ($page['Location'] as $location) {

                    //echo $locationName.' -> '.$location->name."<br/>";
                    if ($locationName != $location['name']) {

                        $this->writeToLog('Location does not match: ' . $locationName . ' / ' . $location['name']);
                        continue;
                    } else {
                        $this->writeToLog('Matches: ' . $locationName . ' / ' . $location['name']);

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

        public function getPagesBySearch($search) {
            $results = array();

            $search = strtolower($search);
            $available = '';
            //get the page
            foreach ($this->pages as $page) {

                $tags = array();
                foreach ($page['Tag'] as $tag) {
                    $tags[ $tag['name'] ] = $tag['name'];
                }

                foreach ($page['Location'] as $location) {


                    foreach ($location['Element'] as $element) {

                        foreach ($element['Revision'] as $revision) {

                            if (stripos($revision['content_text'],$search) !== false) {
                                //echo 'true';
                                $found = array(
                                    'slug' => $page['slug'],
                                    'tags' => implode(',',$tags),
                                    'location' => $location['name'],
                                    'element' => $element['name'],
                                    'language' => $element['language'],
                                    'text' => strip_tags($revision['content_text'])
                                );
                                $results[$page['slug']] = $found;
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
//
        public function getPageSlugsBySearch($search) {

            $results = array();

            $search = strtolower($search);

            $available = '';
            //get the page
            foreach ($this->pages as $page) {


                foreach ($page['Location'] as $location) {


                    foreach ($location['Element'] as $element) {

                        foreach ($element['Revision'] as $revision) {

                            if (stripos($revision['content_text'],$search) !== false) {
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


        public function convertString($from, $to, $string)
        {
            foreach ($from as $kFrom => $vFrom) {
                $string = str_replace($vFrom, $to[$kFrom], $string);
            }
            //return "";
            return $string;
        }


        public function getFile($location, $element, $group = false)
        {


            $cache = 'images' . DS . Configure::read("UpdateCase.variant_id") . DS;

            //pr ($element);
            $id = $this->getIdBy($location, $element, $group);

            if (!$id) {
                $message = 'File cannot load | Location: ' . $location . ' / Element ' . $element . ' / Group:' . $group;
                $this->writeToLog($message, true);
                return false;
            }

            //pr ($id);
            //pr ($this->revision);exit;
            //pr ($id);
            //pr ($element);exit;

            //pr ($this->revision);

            $element = $this->getElement($location, $element, $group);

            $revision = $element['Revision'][0];


            //pr ($id);
            if ($revision['mime'] == 'application/pdf') {
                $filename = $id . '.pdf';
            } elseif ($revision['mime'] == 'application/epub+zip') {
                $filename = $id . '.epub';
            } elseif ($revision['mime'] == 'application/mobi+zip') {
                $filename = $id . '.mobi';
            } elseif ($revision['mime'] == 'application/octet-stream') {
                $filename = $id . '.mobi';
            } elseif ($revision['mime'] == 'image/jpeg') {
                $filename = $id.'.jpg';
            } else {

                //echo 'cannot load slug';
                //pr ($this->revision);
                //pr ($id);exit;

                //exit;
                //pr ($this->revision);
                //$message = 'File cannot load | SLUG: ' . $this->slug . ' / Location: ' . $location . ' / Element ' . $element . ' / Group:' . $group;
                //$this->writeToLog($message, true);
                //echo $message;
                //exit;
                //return $message;
                return false;
            }

            //pr ($filename);
            //does a cached version exist
            $file = new File($cache . $filename);

            // pr ($filename);exit;

            if ($file->exists()) {
                //return the local file


                return $cache . $filename;
            } else {
                //create the file locally

                $dir = new Folder($cache, true, 0775);

                if (file_exists($cache)) {
                    $imageLink = 'http://files.setupcase.com/display/' . $id . '/file.png';
                    $file->write(file_get_contents($imageLink));
                    return $cache . $filename;
                } else {
                    //something went wrong with creating the folder, so let's just return the link from our server
                    $imageLink = 'http://files.setupcase.com/display/' . $id . '/file.png';
                    return $imageLink;
                }
            }
        }

        public function removeTextFrom($remove, $string) {
            return str_replace($remove, '', $string);
        }

        public function getLocationNames($ignore = false) {


            $this->count = 0;

            if (!$this->page) {
                $msg = 'Page not loaded';
                $this->writeToLog($msg);
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


        private function writeToLog($message, $newLine = true) {

            if (is_array($message)) {
                $message = implode("\n", $message);
            }

            if ($newLine) {
                $message = "\n".date('Ymd-His').' > '.$message;
            } else {
                $message = ' > '.$message;
            }
            file_put_contents('updateCase.log', $message, FILE_APPEND);

            //echo APP.'tmp/logs/'.$type;
        }
    }
}
