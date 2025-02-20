Generate Web Page Link Preview with PHP
Link Preview feature extracts title and meta information from a web page and displays this info as a preview. When a web page URL is shared on the social media platform (Facebook, LinkedIn, Twitter, etc), the link preview is appearing as a summary. If your web application lets users share web page URLs, the Link Preview functionality is very useful.

Mostly title, description, and image thumbnail are extracted from the remote URL for link preview. The Link Preview helps to have a clear idea about the URL they sharing. This tutorial will explain how to generate link preview from web page URL using PHP.

In this example script, we will use PHP cURL to fetch metadata from the website and display a web page summary (title, description, and image) in a preview box. Also, the web page URL will be added as a link to visit the website.

URL Metadata Handler PHP Library
We will build a custom library to handle the cURL requests with PHP. The UrlMeta library helps to retrieve the title, description, keywords, and images from HTML meta tags of the web page URL using PHP.

initializeDom() – This function is executed on initialize of UrlMeta class.
The validateUrlFormat() function is used to validate the format of the URL.
The verifyUrlExists() function is used to check whether the web URL exists.
Initiate cURL request to fetch the DOM elements from the URL with PHP.
getWebsiteData() – This function collect the meta information using the helper functions and return data in JSON fromat.
getWebsiteTitle() – Extract title from the DOM elements using PHP getElementsByTagName() method.
getWebsiteDescription() – Extract description from the DOM meta elements using PHP getElementsByTagName() method.
getWebsiteKeyword() – Extract keywords from the DOM meta elements using PHP getElementsByTagName() method.
getWebsiteImages() – Check if any image is available in og:image property of the meta tags. Otherwise, return an image from the DOM elements.



<?php 
/** 
 * 
 * This URL Meta handler class is a custom PHP library to retrieve metadata from URL. 
 * 
 * @class        UrlMeta 
 * @author        CodexWorld 
 * @link        http://www.codexworld.com 
 * @version        1.0 
 */ 
class UrlMeta { 
    private $dom = ''; 
    public $websiteData = array(); 
    private $imageArr = array(); 
     
    public function __construct($url) { 
        $this->initializeDom($url); 
    } 
     
    private function initializeDom($url){ 
        if($this->validateUrlFormat($url) == false){ 
            throw new Exception("URL does not have a valid format."); 
        } 
 
        if (!$this->verifyUrlExists($url)){ 
            throw new Exception("URL does not appear to exist."); 
        } 
         
        if(!empty($url)){ 
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_HEADER, 0); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            curl_setopt($ch, CURLOPT_URL, $url); 
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
            $data = curl_exec($ch); 
            curl_close($ch); 
            $this->dom = new DOMDocument(); 
            @$this->dom->loadHTML($data); 
            $this->websiteData["url"] = $url; 
            return $this->dom; 
        }else{ 
            throw new Exception("No URL was supplied."); 
        } 
    } 
     
    function getWebsiteData(){ 
        $this->websiteData["title"] = $this->getWebsiteTitle(); 
        $this->websiteData["description"] = $this->getWebsiteDescription(); 
        $this->websiteData["keywords"] = $this->getWebsiteKeyword(); 
        $this->websiteData["image"] = $this->getWebsiteImages(); 
        return json_encode($this->websiteData); 
    } 
     
    function getWebsiteTitle(){ 
        $titleNode = $this->dom->getElementsByTagName("title"); 
        $titleValue = $titleNode->item(0)->nodeValue; 
        return $titleValue; 
    } 
     
    function getWebsiteDescription(){ 
        $descriptionNode = $this->dom->getElementsByTagName("meta"); 
        for ($i=0; $i < $descriptionNode->length; $i++) { 
             $descriptionItem = $descriptionNode->item($i); 
             if($descriptionItem->getAttribute('name') == "description"){ 
                return $descriptionItem->getAttribute('content'); 
             } 
        } 
    } 
     
    function getWebsiteKeyword(){ 
        $keywordNode = $this->dom->getElementsByTagName("meta"); 
        for ($i=0; $i < $keywordNode->length; $i++) { 
             $keywordItem = $keywordNode->item($i); 
             if($keywordItem->getAttribute('name') == "keywords"){ 
                return $keywordItem->getAttribute('content'); 
             } 
        } 
    } 
     
    function getWebsiteImages(){ 
        // Check if meta image is exists 
        $ogimageNode = $this->dom->getElementsByTagName("meta"); 
        for ($i=0; $i < $ogimageNode->length; $i++) { 
             $ogimageItem = $ogimageNode->item($i); 
             if($ogimageItem->getAttribute('property') == "og:image"){ 
                return $ogimageItem->getAttribute('content'); 
             } 
        } 
         
        $imageNode = $this->dom->getElementsByTagName("img"); 
        for ($i=0; $i < $imageNode->length; $i++) { 
            $imageItem = $imageNode->item($i); 
            $imageSrc = $imageItem->getAttribute('src'); 
            if(!empty($imageSrc)){ 
                $url = $this->websiteData["url"]; 
                $url = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST); 
                $url = trim($url, '/'); 
                $imageSrc = (strpos($imageSrc, 'http') !== false)?$imageSrc:$url.'/'.$imageSrc; 
                return $imageSrc; 
            } 
        } 
    } 
     
    protected function validateUrlFormat($url){ 
        return filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED); 
    } 
     
    protected function verifyUrlExists($url){ 
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_NOBODY, true); 
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true); 
        curl_exec($ch); 
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
        curl_close($ch); 
 
        return (!empty($response) && $response != 404); 
    } 
} 
?>

HTML Form to Input URL
Define an HTML input element to provide a web page URL.

<form method="post" action="" class="form">
    <div class="form-group">
        <label>Web Page URL:</label>
        <input type="text" class="form-control" name="url" value="" required="">
    </div>
    <div class="form-group">
        <input type="submit" class="form-control btn-primary" name="submit" value="Extract"/>
    </div>
</form>
Extract Metadata from URL
The following code snippet extracts the title and metadata from the submitted URL using PHP.

Initialize and load the UrlMeta library.
Pass the URL in the UrlMeta class.
Get title, description, keywords, and image from remote URL using getWebsiteData() function of UrlMeta class.
Decode JSON data in array using PHP json_decode() function.
<?php 
 
if(isset($_POST['url'])){ 
    $url = $_POST['url']; 
     
    try{ 
        // Initialize URL meta class 
        $urlMeta = new UrlMeta($url); 
         
        // Get meta info from URL 
        $metaDataJson = $urlMeta->getWebsiteData(); 
         
        // Decode JSON data in array 
        $metaData = json_decode($metaDataJson); 
    }catch(Exception $e){ 
        $statusMsg = $e->getMessage(); 
    } 
} 
 
?>
Link Preview Box
Display thumbnail image, title, and description as a box view to show link preview.

Provide a hyperlink with a URL to visit the web page.
<?php if(!empty($metaData)){ ?>
<div class="card">
    <img src="<?php echo $metaData->image; ?>" class="card-img-top" alt="...">
    <div class="card-body">
        <h5 class="card-title"><?php echo $metaData->title; ?></h5>
        <p class="card-text"><?php echo $metaData->description; ?></p>
        <a href="<?php echo $metaData->url; ?>" class="btn btn-primary" target="_blank">Visit site</a>
    </div>
</div>
<?php } ?>
Conclusion
Most of the link-sharing platform uses the Link Preview feature to generate web page preview before making it public. Our example script provides a simple solution to integrate the link preview functionality in the web application using PHP. You can easily build a Facebook-like link preview feature with PHP. To make it user-friendly, you can use jQuery and Ajax to load link preview without page refresh.