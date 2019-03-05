<?php
/**
 * Instructions how to setup
 * Download this file from UpdateCase.com
 */
$token = 'SETUP-later-for-more-security';
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

writeToLog('receiving intro '.json_encode($_GET));

//external communcations
if (isset($_POST['token'])) {
    if ($_POST['token'] != $token) {
        die ('405: NO ACCESS');
    } else {
        
        if (isset($_GET['version'])) {
            if ($_GET['version'] == 3) {
                
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
            } else {
                echo 'Command not recognized';
            }
        }
    }
} else {

    //if this being accessed directly
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
        var $state = 'TEST';

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
            switch($this->state) {
                case 'TEST':
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

                    $this->prepareJson($variant_id, $local_uuid, $slug);

                    $this->language = Configure::read('UpdateCase.language');
                    if (empty($this->language)) {
                        $this->language = $this->possibleLanguages['eng'];
                    } else {
                        $this->language = $this->possibleLanguages[$this->language];
                    }


                    //prepare our page
                    break;
            }
        }

        private function prepareJson($variant_id, $local_uuid, $slug = false) {
            //open the file
            $this->jsonPath = APP . 'Config' . DS . 'Schema' . DS . $variant_id;
            $data = file_get_contents($this->jsonPath.DS.$local_uuid.'.json');

            $json = json_decode($data, true);
            //pr ($json);
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
                return false;
            } else {
                sort($files);
                $newestFile = end($files);
                $newestFile = str_replace(".json", '', $newestFile);
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
                'token' => Configure::read('Token.general'),
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

        public function getMetaTitle() {
            return 'coming soon';
        }
        public function getMetaDescription() {
            return 'coming soon';
        }
        public function getMetaKeywords() {
            return 'coming soon';
        }


        public function getImage($location, $element, $group = false, $size = 'medium')
        {

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
        public function exists() {
            return true;
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
