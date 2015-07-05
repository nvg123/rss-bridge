<?php
/**
*
* @name The Oatmeal
* @description Un petit site de dessins assez rigolos
* @update 20/02/201403/07/2015
*/
require_once 'bridges/RssExpander.php';
define("THE_OATMEAL", "http://theoatmeal.com/");
define("RSS", "http://feeds.feedburner.com/oatmealfeed");
class TheOatmealBridge extends RssExpander{

    public function collectData(array $param){
        $param['url'] = RSS;
        parent::collectData($param);
    }


    /**
     * Since the oatmeal produces a weird RSS feed, I have to fix it by loading the items separatly from the feed infos
     */
    protected function collect_RSS_2_0_data($rssContent) {
        $rssContent->registerXPathNamespace("dc", "http://purl.org/dc/elements/1.1/");
        $rssHeaderContent = $rssContent->channel[0];
//        $this->message("RSS content is ===========\n".var_export($rssHeaderContent, true)."===========");
        $this->load_RSS_2_0_feed_data($rssHeaderContent);
        foreach($rssContent->item as $item) {
            $this->message("parsing item ".var_export($item, true));
            $this->items[] = $this->parseRSSItem($item);
        }
    }


    protected function parseRSSItem($newsItem) {
        $item = new Item();
        $item->title = trim($newsItem->title);
        $this->message("browsing Oatmeal item ".var_export($newsItem, true));
        if(empty($newsItem->guid)) {
            $item->uri = $newsItem->link;
        } else {
            $item->uri = $newsItem->guid;
        }
        // now load that uri from cache
        $this->message("now loading page ".$item->uri);
        $articlePage = str_get_html($this->get_cached($item->uri));

        $content = $articlePage->find('#comic', 0);
		if($content==null) {
			$content = $articlePage->find('#blog');
		}
        $item->content = $content->innertext;
        
        $namespaces = $newsItem->getNameSpaces(true);

        $dc = $newsItem->children($namespaces['dc']);
        $this->message("dc content is ".var_export($dc, true));
        $item->name = $dc->creator;
        $item->timestamp = DateTime::createFromFormat(DateTime::ISO8601, $dc->date)->getTimestamp();
        $this->message("writtem by ".$item->name." on ".$item->timestamp);
        return $item;
    }
    
    public function getCacheDuration(){
        return 1; // 2h hours
    }
}
