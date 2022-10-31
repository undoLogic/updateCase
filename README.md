# UpdateCase Client

### Overview

Our solution provides a simple website publishing software for your staff to confidentially modify your website content (text & images) independently from all the complex programming & scripting required to maintain your custom business website.

### Concept
- Prepare your account on UpdateCase.com
- Include this class on your website
- Use UpdateCase.com to add the methods after you create content with copy / paste (better than manually typing out)
- Non-technical team can now update content (text / images) independantly from technical team while ensuring branding is maintained
- Your technical team can now focus on more complex and effective updates instead of making text and image updates

### API Methods


## __construct($options)
```php
$this->updateCase = new UpdateCase(['debug' => true,'variant_id' => $variant_id,'version' => 5,'lang' => $this->request->getAttribute('lang')]);
@param array $options
# debug true will force network connections while false will only use local cached data
# variant_id required to interface with updateCase.com and get the correct data
# version 5 is the current version
# lang which language to use (supported: en/es/fr)
@return array entire structure which can be passed into the view
```

---
## debug($maxRecentRows = 20)
```php
@param $maxRecentRows # default to 20 can be increased to see further back
@return string # can be echo'd to see a complete log of the actions to diagnose any issues and understand exactly what is happening
```

---
## changeVariant($variant_id)
```php
Allows to switch to a different variant_id which is useful when you want to share content between websites
@param $variant_id
@return void
```

---
## changeSlug($slug)
```php
Switch to a different slug (or page on UpdateCase.com)
@param $slug
@return bool
```

---
## loadPageBySlug($slug)
```php
DEPRECATED: This is now 'changeSlug' and will be removed on the next major release
@param $slug
@return void
```

---
## getContentBy($locationName, $elementName, $groupName = false, $slug = false)
```php
Display content added to UpdateCase. Best to copy-and-paste directly from site to save time setting up.
@param string $locationName
@param string $elementName
@param string $groupName # allows to use groups and loop blocks
@param string $slug # allows to display content from a specific slug without 'changeSlug'
@return string content which was added to UpdateCase.com
```

---
## currLang()
```php
Get the active language (en/es/fr)
@return mixed
```

---
## getMetaTitle()
```php
Get the Meta TITLE either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
@return string
```

---
## getMetaDescription()
```php
Get the Meta DESCRIPTION either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
@return string
```

---
## getMetaKeywords()
```php
Get the Meta KEYWORDS either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
@return string
```

---
## getMetaProperty($name)
```php
Get the Meta TITLE/KEYWORDS/DESCRIPTION either from the active slug (page) or if that does not exist on UpdateCase.com the ALL slug (page) Meta title will be returned instead
@param $name # use 'title' OR 'keywords' OR 'description'
@return string
```

---
## getMetaOgLocale($lang)
```php
OG LOCALE requires a full language string
@param $lang
@return false|string
```

---
## setDate($date)
```php
Allows to change the date which is used to simulate events in the future / past to test conditional locations
@param $date
@return void
```

---
## getDate()
```php
What date the UpdateCase client is using
@return false|string
```

---
## getImageBy($location, $element, $group = false, $size = 'medium', $slug = false)
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
## getImage($location, $element, $group = false, $size = 'medium')
```php
DEPRECATED use 'getImageBy' instead
```

---
## getFileBy($location, $element, $group = false, $slug = false)
```php
@param $location
@param $element
@param $group
@param $slug
@return false|string
```

---
## getLinkBy($locationName, $elementName, $groupName = false, $slug = false, $prefix = 'http://')
```php
@param $locationName
@param $elementName
@param $groupName
@param $slug
@param $prefix
@return mixed|string
```

---
## isLocationActive($currLocationName)
```php
@param $currLocationName
@return bool
```

---
## doesSlugExist($slug)
```php
Check if a slug was created on UpdateCase.com
@param $slug
@return bool
```

---
## getByWithoutLoading($slug, $location_to_check, $element_to_check, $lang = false)
```php
Does not require to change the slug to get content
@param $slug
@param $location_to_check
@param $element_to_check
@param $lang
@return false|string|void
```

---
## getPageSlugsByTag($tagName, $sortBy = 'DATE-ASC', $ensureAllTags = false, $limitToLang = false)
```php
List of slugs from a tag useful to get and display all pages from a tag
@param $tagName
@param $sortBy
@param $ensureAllTags
@param $limitToLang
@return array
```

---
## getPageLangs()
```php
Languages present in current page (slug) within ALL locations / elements
@return array
```

---
## getPageDate($format = 'Y-m-d H:i:s')
```php
Page date which is set on UpdateCase.com
@param $format # PHP date format
@return false|string
```

---
## getPage()
```php
The page that was loaded from changeSlug
@return array
```

---
## getTagsFromAllPages($ignore = [])
```php
All the tags for every page within the loaded variant
@param $ignore # array allowing to ignore a list
@return array
```

---
## isTagPresent($tag)
```php
Is the tag present in the loaded page (slug)
@param $tag
@return bool
```

---
## getTags($ignore = [])
```php
The tags from the current loaded page (slug)
@param $ignore
@return array
```

---
## getIdBy($locationName, $elementName, $groupName = false)
```php
A unique ID for the revision on the element. Each time text is changed the ID will change
@param $locationName
@param $elementName
@param $groupName
@return false|mixed
```

---
## getPageSlugsByTagWithLocationElement($tagName, $sortBy = 'ASC', $location, $element, $group = false, $limit = false, $offset = false, $options = false)
```php
@param $tagName
@param $sortBy
@param $location
@param $element
@param $group
@param $limit
@param $offset
@param $options
@return array|void
```

---
## getImageAltTag($location, $element, $group = false, $size = 'medium')
```php
Get a ALT string to be used in the image tag eg img src="" alt="getImageAltTag(.....
@param $location
@param $element
@param $group
@param $size
@return string
```

---
## Translate($term, $element = 'en->fr')
```php
Translate a word using the translation function located in UpdateCase.com
@param $term
@param $element # eg en->fr meaning we are adding english terms which appear as french when echo'd
@return false|mixed|string
```

---
## exists($locationName, $elementName = false, $groupName = false)
```php
Check if this location / element can be used and exists on UpdateCase.com
@param $locationName
@param $elementName
@param $groupName
@return bool|void
```

---
## isEmpty($locationName, $elementName = false, $groupName = false)
```php
When it exists by if the content is empty
@param $locationName
@param $elementName
@param $groupName
@return bool
```

---
## isNotEmpty($locationName, $elementName = false, $groupName = false)
```php
Not empty so there IS content available
@param $locationName
@param $elementName
@param $groupName
@return bool
```

---
## doesContain($search, $locationName, $elementName = false, $groupName = false)
```php
Useful for searches to see if a string of text is available
@param $search # string search term
@param $locationName
@param $elementName # optional
@param $groupName # optianal
@return bool
```

---
## existsInPage($slug, $locationName, $elementName = false, $groupName = false)
```php
@param $slug
@param $locationName
@param $elementName # optional
@param $groupName # optional
@return bool|void
```

---
## getPagesBySearch($searches, $limitToTag = false)
```php
Search feature to give string or array of search terms to get the slug where they are available
@param array|string $searches # allows to search for slugs from
@param $limitToTag
@return array|false|void # this returns a complex array with all the data
```

---
## getPageSlugsBySearch($searches)
```php
Allow to search for a string or array or strings and get the slugs where they are available
@param array|string $searches
@return array|false # slugs which contain the search
```

---
## getPageSlugsByYear($year)
```php
All pages (slugs) are stored on UpdateCase.com with a date, this allows to get a list from a specific year
@param $year
@return array|false
```

---
## getFile($location, $element, $group = false)
```php
DEPRECATED use getFileBy instead
```

---
## getLocationNames($ignore = false)
```php
List of location names from the currently loaded page (slug)
@param $ignore # conditional
@return void
```

---
## getUniqueNameForFieldByLocation($locationName, $field)
```php
@param $locationName
@param $field
@return array
```

---
## getTextOnly($text, $limit = false)
```php
Text without any html tags
@param string $text # html content
@param int $limit # allow to get a portion back with '...' appended
@return false|string
```

---
## removeImages($string)
```php
Remove the image tags from a string
@param $string
@return string
```

---
## removeHtmlElements($str)
```php
Remove the HTML elements from a string
@param $str
@return array|string|string[]|null
```

---
## ensureHttpOrHttps($url, $prefix = 'http://')
```php
Easily make sure your path has either https:// OR http://
@param $url
@param $prefix # http:// OR https://
@return string
```

---
## removeTextFrom($remove, $string)
```php
@param $remove
@param $string
@return array|string|string[]
```

---
## ensureHttp($url)
```php
DEPRECATED use ensureHttpOrHttps
```

---
## isEvery($nth, $count)
```php
DEPRECATED
```

---
## getSingleNamesByLocation($locationName, $sort = 'ASC', $slug = false)
```php
DEPRECATED
```

---
## getGroupNamesByLocation($locationName, $sort = 'ASC', $slug = false)
```php
DEPRECATED
```

---
## getTotalRecords()
```php
DEPRECATED
```

---
## convertString($from, $to, $string)
```php
DEPRECATED
```

---
