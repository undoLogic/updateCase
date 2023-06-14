# UpdateCase Client

### Overview

Our solution provides a simple website publishing software for your staff to confidentially modify your website content (text & images) independently from all the complex programming & scripting required to maintain your custom business website.

### Versions
updateCase-v5.php is now DEPRECIATED
- Instead logon to UpdateCase.com and download "UpdateCaseUtil.php" from the configuration screen
- Save to your CakePHP 4.x project
  - src/Util/UpdateCaseUtil.php

### Concept
- Prepare your account on UpdateCase.com
- Include this client on your website
- Use UpdateCase.com - You can copy the methods to add to your source files
- Non-technical team can now update content (text / images) independently from technical team while ensuring branding is maintained
- Your technical team can now focus on more complex and effective updates instead of making text and image updates

### API Methods







## __construct
- ARGS: (array $options)
- Useage: $updateCase->__construct(array $options)
```php
$this->updateCase = new UpdateCase(['debug' => true,'variant_id' => $variant_id,'version' => 6,'lang' => $this->request->getAttribute('lang')]);
@param array $options
# debug true will force network connections while false will only use local cached data
# variant_id required to interface with updateCase.com and get the correct data
# version 5 is the current version
# lang which language to use (supported: en/es/fr)
@return array entire structure which can be passed into the view
```

---
## init_prepareJson
- ARGS: ()
- Useage: $updateCase->init_prepareJson()
```php
Sets up the JSON file and formatts all the interal variables
@return bool|int
```

---
## sync
- ARGS: ()
- Useage: $updateCase->sync()
```php
Access this from a public website page eg Pages/sync which will get the new content from UpdateCase
@return local_uuid
```

---
## ensureDirExists
- ARGS: ($path)
- Useage: $updateCase->ensureDirExists($path)
```php
No comment added
```

---
## getMostRecentFilename
- ARGS: ()
- Useage: $updateCase->getMostRecentFilename()
```php
No comment added
```

---
## download
- ARGS: ($pathToUse, $debug =
- Useage: $updateCase->download($pathToUse, $debug =
```php
No comment added
```

---
## isLive
- ARGS: ()
- Useage: $updateCase->isLive()
```php
No comment added
```

---
## debug
- ARGS: ($maxRecentRows = 20)
- Useage: $updateCase->debug($maxRecentRows = 20)
```php
@param $maxRecentRows # default to 20 can be increased to see further back
@return string # can be echo'd to see a complete log of the actions to diagnose any issues and understand exactly what is happening
```

---
## changeVariant
- ARGS: ($variant_id)
- Useage: $updateCase->changeVariant($variant_id)
```php
Allows to switch to a different variant_id which is useful when you want to share content between websites
@param $variant_id
@return void
```

---
## changePage
- ARGS: ($slug)
- Useage: $updateCase->changePage($slug)
```php
Switch to a different slug (or page on UpdateCase.com)
@param $slug
@return bool
```

---
## getContentBy
- ARGS: (string $locationName, string
- Useage: $updateCase->getContentBy(string $locationName, string
```php
Display content added to UpdateCase. Best to copy-and-paste directly from site to save time setting up.
@param string $locationName
@param string $elementName
@param string $groupName # allows to use groups and loop blocks
@param string $slug # allows to display content from a specific slug without 'changeSlug'
@return string content which was added to UpdateCase.com
```

---
## getContentBy_getSingleElementKey
- ARGS: ($locationKey, $elementName)
- Useage: $updateCase->getContentBy_getSingleElementKey($locationKey, $elementName)
```php
No comment added
```

---
## convertLang
- ARGS: ($lang)
- Useage: $updateCase->convertLang($lang)
```php
No comment added
```

---
## getContentBy_getExtendedName
- ARGS: ($name)
- Useage: $updateCase->getContentBy_getExtendedName($name)
```php
No comment added
```

---
## getContentBy_returnImage
- ARGS: ($elm)
- Useage: $updateCase->getContentBy_returnImage($elm)
```php
No comment added
```

---
## appendPassword
- ARGS: ()
- Useage: $updateCase->appendPassword()
```php
No comment added
```

---
## getImageBy
- ARGS: ($locationName, $elementName, $groupName
- Useage: $updateCase->getImageBy($locationName, $elementName, $groupName
```php
This will cache images from UpdateCase.com to website (webroot)
@param $location
@param $element
@param $group
@param $size # medium / thumb / large (still alpha functionality)
@param $slug # conditional to get image without using changeSlug
@return false|string # will return the url to the image on the webroot, can be added to img src="$webroot.$updateCase->getImageBy.....
```

---
## currLang
- ARGS: ()
- Useage: $updateCase->currLang()
```php
Get the active language (en/es/fr)
@return mixed
```

---
## getMetaTitle
- ARGS: ()
- Useage: $updateCase->getMetaTitle()
```php
Get the Meta TITLE either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
@return string
```

---
## getMetaDescription
- ARGS: ()
- Useage: $updateCase->getMetaDescription()
```php
Get the Meta DESCRIPTION either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
@return string
```

---
## getMetaKeywords
- ARGS: ()
- Useage: $updateCase->getMetaKeywords()
```php
Get the Meta KEYWORDS either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
@return string
```

---
## getMetaProperty
- ARGS: ($name)
- Useage: $updateCase->getMetaProperty($name)
```php
Get the Meta TITLE/KEYWORDS/DESCRIPTION either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
@param $name # use 'title' OR 'keywords' OR 'description'
@return string
```

---
## getMetaOgLocale
- ARGS: ($lang)
- Useage: $updateCase->getMetaOgLocale($lang)
```php
OG LOCALE requires a full language string
@param $lang
@return false|string
```

---
## setDate
- ARGS: ($date)
- Useage: $updateCase->setDate($date)
```php
Allows to change the date which is used to simulate events in the future / past to test conditional locations
@param $date
@return void
```

---
## getDate
- ARGS: ()
- Useage: $updateCase->getDate()
```php
What date the UpdateCase client is using
@return false|string
```

---
## isLocationActive
- ARGS: ($currLocationName)
- Useage: $updateCase->isLocationActive($currLocationName)
```php
@todo bring this into the getContentBy above
@param $currLocationName
@return bool
```

---
## doesSlugExist
- ARGS: ($slug)
- Useage: $updateCase->doesSlugExist($slug)
```php
Check if a slug was created on UpdateCase.com
@param $slug
@return bool
```

---
## getPageSlugsByTag
- ARGS: ($tagName, $sortBy =
- Useage: $updateCase->getPageSlugsByTag($tagName, $sortBy =
```php
List of slugs from a tag useful to get and display all pages from a tag
@param $tagName
@param $sortBy
@param $ensureAllTags
@param $limitToLang
@return array
```

---
## getPageLangs
- ARGS: ()
- Useage: $updateCase->getPageLangs()
```php
Languages present in current page (slug) within ALL locations / elements
@return array
```

---
## getPageDate
- ARGS: ($format = 'Y-m-d H:i:s')
- Useage: $updateCase->getPageDate($format = 'Y-m-d H:i:s')
```php
Page date which is set on UpdateCase.com
@param $format # PHP date format
@return false|string
```

---
## getPage
- ARGS: ()
- Useage: $updateCase->getPage()
```php
The page that was loaded from changeSlug
@return array
```

---
## getTagsFromAllPages
- ARGS: ($ignore = [])
- Useage: $updateCase->getTagsFromAllPages($ignore = [])
```php
All the tags for every page within the loaded variant
@param $ignore # array allowing to ignore a list
@return array
```

---
## isTagPresent
- ARGS: ($tag)
- Useage: $updateCase->isTagPresent($tag)
```php
Is the tag present in the loaded page (slug)
@param $tag
@return bool
```

---
## getTags
- ARGS: ($ignore = [])
- Useage: $updateCase->getTags($ignore = [])
```php
The tags from the current loaded page (slug)
@param $ignore
@return array
```

---
## translate
- ARGS: ($term, $element =
- Useage: $updateCase->translate($term, $element =
```php
Translate a word using the translation function located in UpdateCase.com
@param $term
@param $element # eg en->fr meaning we are adding english terms which appear as french when echo'd
@return false|mixed|string
```

---
## translate_keepTrackOfNewTranslations
- ARGS: ($newWord)
- Useage: $updateCase->translate_keepTrackOfNewTranslations($newWord)
```php
No comment added
```

---
## exists
- ARGS: ($locationName, $elementName =
- Useage: $updateCase->exists($locationName, $elementName =
```php
Check if this location / element can be used and exists on UpdateCase.com
@param $locationName
@param $elementName
@param $groupName
@return bool|void
```

---
## isEmpty
- ARGS: ($locationName, $elementName =
- Useage: $updateCase->isEmpty($locationName, $elementName =
```php
When it exists by if the content is empty
@param $locationName
@param $elementName
@param $groupName
@return bool
```

---
## isNotEmpty
- ARGS: ($locationName, $elementName =
- Useage: $updateCase->isNotEmpty($locationName, $elementName =
```php
Not empty so there IS content available
@param $locationName
@param $elementName
@param $groupName
@return bool
```

---
## doesContain
- ARGS: ($search, $locationName, $elementName
- Useage: $updateCase->doesContain($search, $locationName, $elementName
```php
Useful for searches to see if a string of text is available
@param $search # string search term
@param $locationName
@param $elementName # optional
@param $groupName # optianal
@return bool
```

---
## existsInPage
- ARGS: ($slug, $locationName, $elementName
- Useage: $updateCase->existsInPage($slug, $locationName, $elementName
```php
@param $slug
@param $locationName
@param $elementName # optional
@param $groupName # optional
@return bool|void
```

---
## getPagesBySearch
- ARGS: ($searches, $limitToTag =
- Useage: $updateCase->getPagesBySearch($searches, $limitToTag =
```php
Search feature to give string or array of search terms to get the slug where they are available
@param array|string $searches # allows to search for slugs from
@param $limitToTag
@return array|false|void # this returns a complex array with all the data
```

---
## getPageSlugsBySearch
- ARGS: ($searches)
- Useage: $updateCase->getPageSlugsBySearch($searches)
```php
Allow to search for a string or array or strings and get the slugs where they are available
@param array|string $searches
@return array|false # slugs which contain the search
```

---
## getPageSlugsByYear
- ARGS: ($year)
- Useage: $updateCase->getPageSlugsByYear($year)
```php
All pages (slugs) are stored on UpdateCase.com with a date, this allows to get a list from a specific year
@param $year
@return array|false
```

---
## getLocationNames
- ARGS: ($ignore = false)
- Useage: $updateCase->getLocationNames($ignore = false)
```php
List of location names from the currently loaded page (slug)
@param $ignore # conditional
@return void
```

---
## getUniqueNameForFieldByLocation
- ARGS: ($locationName, $field)
- Useage: $updateCase->getUniqueNameForFieldByLocation($locationName, $field)
```php
@param $locationName
@param $field
@return array
```

---
## isGroupCorrect
- ARGS: ($groupToFind, $currentLoopedGroup)
- Useage: $updateCase->isGroupCorrect($groupToFind, $currentLoopedGroup)
```php
No comment added
```

---
## locationViewString
- ARGS: ($location, $element, $group
- Useage: $updateCase->locationViewString($location, $element, $group
```php
No comment added
```

---
## getTextOnly
- ARGS: (string $text, $limit
- Useage: $updateCase->getTextOnly(string $text, $limit
```php
Text without any html tags
@param string $text # html content
@param int $limit # allow to get a portion back with '...' appended
@return false|string
```

---
## removeImages
- ARGS: ($string)
- Useage: $updateCase->removeImages($string)
```php
Remove the image tags from a string
@param $string
@return string
```

---
## removeHtmlElements
- ARGS: ($str)
- Useage: $updateCase->removeHtmlElements($str)
```php
Remove the HTML elements from a string
@param $str
@return array|string|string[]|null
```

---
## ensureHttpOrHttps
- ARGS: ($url, $prefix =
- Useage: $updateCase->ensureHttpOrHttps($url, $prefix =
```php
Easily make sure your path has either https:// OR http://
@param $url
@param $prefix # http:// OR https://
@return string
```

---
## cleanUpStringForQuotedSections
- ARGS: ($str)
- Useage: $updateCase->cleanUpStringForQuotedSections($str)
```php
Quotes will only be SINGLE QUOTES, allows to safely add to html tags that are double quotes and not break the tag
@param $str
@return array|string|string[]
```

---
## removeTextFrom
- ARGS: ($remove, $string)
- Useage: $updateCase->removeTextFrom($remove, $string)
```php
@param $remove
@param $string
@return array|string|string[]
```

---
## ensureHttp
- ARGS: ($url)
- Useage: $updateCase->ensureHttp($url)
```php
DEPRECATED use ensureHttpOrHttps
```

---
## getApiList
- ARGS: ()
- Useage: $updateCase->getApiList()
```php
Allows to view all the functions within your software and verify they are properly setup
@return array
```

---
## getApiListAsMarkup
- ARGS: ()
- Useage: $updateCase->getApiListAsMarkup()
```php
No comment added
```

---
