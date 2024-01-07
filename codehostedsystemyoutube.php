<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Log\Log;
use DOMDocument;

class PlgSystemCodehostedSystemYoutube extends CMSPlugin
{
    private $app;

    private $routeView;

    private $routeOption;

    private $playButtonSvg = '<svg height="100%" version="1.1" viewBox="0 0 68 48" width="100%"><path class="ytp-large-play-button-bg" d="M66.52,7.74c-0.78-2.93-2.49-5.41-5.42-6.19C55.79,.13,34,0,34,0S12.21,.13,6.9,1.55 C3.97,2.33,2.27,4.81,1.48,7.74C0.06,13.05,0,24,0,24s0.06,10.95,1.48,16.26c0.78,2.93,2.49,5.41,5.42,6.19 C12.21,47.87,34,48,34,48s21.79-0.13,27.1-1.55c2.93-0.78,4.64-3.26,5.42-6.19C67.94,34.95,68,24,68,24S67.94,13.05,66.52,7.74z" fill="#f00"></path><path d="M 45,24 27,14 27,34" fill="#fff"></path></svg>';

    public function __construct(&$subject, $config = array())
    {
        parent::__construct($subject, $config);
        // Add a logger
        Log::addLogger(
            [
                'text_file' => 'codehosted_youtubeloader.php'
            ], 
            Log::ALL, 
            ['codehosted_youtubeloader']
        );        
    }

    public function onAfterInitialise()
    {
        $this->app = JFactory::getApplication();
    }

    public function onAfterRoute()
    {
        $this->routeView = $this->app->input->get('view');
        $this->routeOption = $this->app->input->get('option');
    }

    public function onAfterRender()
    {
        if ($this->routeOption == 'com_content' && $this->routeView == 'article') {
            $body = $this->app->getBody();
            $finBody = $this->processYoutubeTags($body);
            $this->app->setBody($finBody);
        }
    }

    public function onBeforeCompileHead()
    {
        $doc = JFactory::getDocument();
        $wa = $doc->getWebAssetManager();
        $wa->getRegistry()->addRegistryFile('media/plg_system_youtubeloader/joomla.asset.json');
        $wa->useScript('plugin.codehosted.youtubeloader')
            ->useStyle('plugin.codehosted.youtubeloader');
    }

    private function processYoutubeTags(string $body): string
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress warnings due to malformed HTML
        $dom->loadHTML($body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $youtubeElements = $dom->getElementsByTagName('youtube');

        /**
         * When you replace an element in the DOM with a new node, the DOMNodeList updates 
         * so that the replaced element is no longer in the list. This means if you replaced 
         * the first youtube element, the second youtube element moves up to the first position, 
         * and when the loop increments, it moves on to what is now the second element, 
         * effectively skipping what was originally the second youtube element.
         * 
         * We must operate on a copy of the DOMNodeList to avoid this issue.
         */
        $youtubeElementsArray = [];
        foreach ($youtubeElements as $element) {
            $youtubeElementsArray[] = $element;
        }
        
        foreach ($youtubeElementsArray as $element) {
            // Get the video ID from the data-id or video attribute
            $videoId = $element->getAttribute('data-id') ?: $element->getAttribute('video');
            $videoInfo = $this->sendCurlRequest("https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$videoId}", 'GET', '');

            // Create a new div element to wrap img and play div
            $wrapperDiv = $dom->createElement('div');
            $wrapperDiv->setAttribute('class', 'youtube-wrapper');


            // Create a new img element with the thumbnail URL
            $imgNode = $dom->createElement('img');
            $imgNode->setAttribute('src', $videoInfo['thumbnail_url']);
            $imgNode->setAttribute('alt', $videoInfo['title']);
            $imgNode->setAttribute('class', 'youtube-thumbnail');
            $imgNode->setAttribute('id', "thumbnail-{$videoId}");
            $imgNode->setAttribute('data-video-id', $videoId);
            $imgNode->setAttribute('data-youtube', $videoInfo['html']);

            // Append img to wrapper div
            $wrapperDiv->appendChild($imgNode);

            // Create a div element with class "play"
            $playDiv = $dom->createElement('div');
            $playDiv->setAttribute('id', "play-{$videoId}");
            $playDiv->setAttribute('data-video-id', $videoId);
            $playDiv->setAttribute('data-youtube', $videoInfo['html']);

            // add a play button inside the play div
            $fragment = $dom->createDocumentFragment();
            $fragment->appendXML($this->playButtonSvg);

            // create a play button and append $fragment to it
            $playButton = $dom->createElement('button');
            $playButton->setAttribute('id', "button-{$videoId}");
            $playButton->setAttribute('data-video-id', $videoId);
            $playButton->setAttribute('data-youtube', $videoInfo['html']);

            $playButton->setAttribute('class', 'ytp-large-play-button ytp-button');
            $playButton->appendChild($fragment);

            // append play button to play div
            $playDiv->appendChild($playButton);

            // Append play div to wrapper div
            $wrapperDiv->appendChild($playDiv);

            // Replace the original element with the wrapper div
            $element->parentNode->replaceChild($wrapperDiv, $element);
        }

        return $dom->saveHTML();
    }

    private function sendCurlRequest($url, $method, $accessToken, $data = null, $options = [])
    {
        $ch = curl_init();
    
        // If there are options like updateMask, append them to the URL as query parameters
        if (!empty($options)) {
            $query = http_build_query($options, '', '&');
            $url .= '?' . $query;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
        ];

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}