<?php
/**
 * UrlMeta Class
 * 
 * This class handles fetching and parsing metadata from URLs to generate link previews.
 * It extracts title, description, keywords, and images from HTML meta tags.
 */
class UrlMeta {
    private $dom = '';
    public $websiteData = array();
    
    /**
     * Constructor - initializes the DOM with the given URL
     */
    public function __construct($url) {
        $this->initializeDom($url);
    }
    
    /**
     * Initialize DOM with URL content
     */
    private function initializeDom($url) {
        if(!$this->validateUrlFormat($url)) {
            throw new Exception("Invalid URL format");
        }
        
        if(empty($url)) {
            throw new Exception("No URL was supplied.");
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]);

        $data = curl_exec($ch);
        
        if(curl_errno($ch)) {
            curl_close($ch);
            throw new Exception("Failed to fetch URL: Connection error");
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if($httpCode !== 200) {
            throw new Exception("Failed to fetch URL: HTTP error $httpCode");
        }

        $this->dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$this->dom->loadHTML($data, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        
        $this->websiteData["url"] = $url;
        return $this->dom;
    }

    /**
     * Get all website metadata
     */
    public function getWebsiteData() {
        try {
            $this->websiteData["title"] = $this->getWebsiteTitle();
            $this->websiteData["description"] = $this->getWebsiteDescription();
            $this->websiteData["image"] = $this->getWebsiteImages();
            return json_encode($this->websiteData);
        } catch (Exception $e) {
            return json_encode([
                "title" => "Link Preview",
                "description" => "Unable to load preview",
                "image" => "",
                "url" => $this->websiteData["url"]
            ]);
        }
    }
    
    /**
     * Get website title
     */
    private function getWebsiteTitle() {
        // Try Open Graph title first
        $metaTags = $this->dom->getElementsByTagName('meta');
        foreach ($metaTags as $meta) {
            if ($meta->getAttribute('property') === 'og:title') {
                return $meta->getAttribute('content');
            }
        }

        // Fallback to regular title tag
        $titleTags = $this->dom->getElementsByTagName('title');
        if ($titleTags->length > 0) {
            return $titleTags->item(0)->nodeValue;
        }

        return "Untitled";
    }
    
    /**
     * Get website description
     */
    private function getWebsiteDescription() {
        $metaTags = $this->dom->getElementsByTagName('meta');
        
        // Try Open Graph description first
        foreach ($metaTags as $meta) {
            if ($meta->getAttribute('property') === 'og:description') {
                return $meta->getAttribute('content');
            }
        }
        
        // Then try regular description
        foreach ($metaTags as $meta) {
            if ($meta->getAttribute('name') === 'description') {
                return $meta->getAttribute('content');
            }
        }
        
        return "";
    }
    
    /**
     * Get website images
     */
    private function getWebsiteImages() {
        // Try Open Graph image first
        $metaTags = $this->dom->getElementsByTagName('meta');
        foreach ($metaTags as $meta) {
            if ($meta->getAttribute('property') === 'og:image') {
                $imageSrc = $meta->getAttribute('content');
                if ($this->isValidUrl($imageSrc)) {
                    return $imageSrc;
                }
            }
        }

        // Then try to find first suitable image tag
        $images = $this->dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if (!empty($src)) {
                // Convert relative URLs to absolute
                if (strpos($src, 'http') !== 0) {
                    $baseUrl = parse_url($this->websiteData["url"], PHP_URL_SCHEME) . '://' . 
                              parse_url($this->websiteData["url"], PHP_URL_HOST);
                    $src = $baseUrl . ($src[0] === '/' ? '' : '/') . $src;
                }
                
                if ($this->isValidUrl($src)) {
                    return $src;
                }
            }
        }
        
        return "";
    }
    
    /**
     * Validate URL format
     */
    private function validateUrlFormat($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
    
    /**
     * Check if URL is valid
     */
    private function isValidUrl($url) {
        return !empty($url) && filter_var($url, FILTER_VALIDATE_URL);
    }
}