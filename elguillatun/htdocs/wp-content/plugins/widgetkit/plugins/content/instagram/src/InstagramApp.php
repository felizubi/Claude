<?php

namespace YOOtheme\Widgetkit\Content\instagram;

use YOOtheme\Framework\Application;
use YOOtheme\Framework\ApplicationAware;


class InstagramApp extends ApplicationAware
{
    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function fetch($params, $content)
    {
        // Cache settings
        $now = time();
        $expires = 6 * 3600;    // cache time 6 hours
        $max_items = $params['limit'] ?: '12';

        $posts = array();

        // Cache invalid?
        if (array_key_exists('hash', $content) // never cached
            || $now - $content['hashed'] > $expires // cached values too old
            || md5(serialize($params)) != $content['hash']) // content settings have changed
        {

            try {

                $data = $this->get_user_data($params['username'], $max_items);

                $instagram_user = $data['user'];

                $data = $data['data'];

                foreach ($data['data']['user']['edge_owner_to_timeline_media']['edges'] as $item) {

                    $post = array(
                        'title' => "{$instagram_user['full_name']} ({$instagram_user['username']})",
                        'content' => $item['node']['edge_media_to_caption']['edges'][0]['node']['text'],
                        'date' => date('d-m-Y H:i:s O', $item['node']['taken_at_timestamp']),
                        'link' => "//instagram.com/p/{$item['node']['shortcode']}/?taken-by={$instagram_user['username']}",
                        'location' => null,
                        'media' => $item['node']['thumbnail_src'],
                        'options' => array(
                            'media' => array(
                                'width' => $item['node']['dimensions']['width'],
                                'height' => $item['node']['dimensions']['height']
                            )
                        )
                    );

                    // seperate the hashtags
                    $post['content'] = preg_replace('/#/', ' #', $post['content']);
                    // make hashtags clickable
                    $post['content'] = preg_replace('/(?<=^|(?<=[^a-zA-Z0-9-_\.]))\#([\P{Z}]+)/', '<a href="https://instagram.com/explore/tags/$1">#$1</a>', $post['content']);

                    // make user names clickable
                    $post['content'] = preg_replace('/(?<=^|(?<=[^a-zA-Z0-9-_\.]))\@([\P{Z}]+)/', '<a href="https://instagram.com/$1">@$1</a>', $post['content']);

                    // convert emoticons to UTF-8 code
                    $post['content'] = mb_convert_encoding($post['content'], 'UTF-8');


                    //                    if($item['type'] == 'video'){
                    //                        $post['media'] = $item['videos']['standard_resolution']['url'];
                    //                        $post['options']['media'] = array(
                    //                            'poster' => $item['images']['standard_resolution']['url'],
                    //                            'width'  => $item['videos']['standard_resolution']['width'],
                    //                            'height' => $item['videos']['standard_resolution']['height']
                    //                        );
                    //                    }

                    if ($params['title'] == 'username') {
                        $post['title'] = $instagram_user['username'];
                    } elseif ($params['title'] == 'fullname') {
                        $post['title'] = $instagram_user['full_name'];
                    }

                    $posts[] = $post;

                }

                // write cache
                $content['prepared'] = json_encode($posts);
                $content['hash'] = md5(serialize($params));
                $content['hashed'] = $now;
                unset($content['error']);

                $this->app['content']->save($content->toArray());

                return $posts;
            } catch (\Exception $e) {
                // Fallback to cache and log of API error
                $content['error'] = $e->getMessage();
                $this->app['content']->save($content->toArray());
            }
        }

        // read from cache
        $posts = json_decode($content['prepared'], true);

        return $posts ? $posts: array();
    }

    protected function get_user_data($user_name, $max_items = 12)
    {
        if (!function_exists('curl_exec') && ini_get('open_basedir') !== '') {
            throw new \Exception('Curl not enabled.');
        }

        $url = "https://www.instagram.com/{$user_name}/";

        $conn = curl_init();
        curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($conn, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($conn, CURLOPT_AUTOREFERER, false);
        curl_setopt($conn, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($conn, CURLOPT_VERBOSE, 0);
        curl_setopt($conn, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($conn, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36');

        curl_setopt($conn, CURLOPT_URL, $url);

        $content = curl_exec($conn);

        if (curl_errno($conn)) {
            throw new \Exception('Curl: ' . curl_error($conn));
        }

        if (preg_match_all('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/', $content, $matches, PREG_SET_ORDER)) {
            foreach($matches as $script) {
                if (strpos($script[0], 'edge_owner_to_timeline_media') !== false) {
                    $json_data = preg_replace('/^<script[^>]*>window\._sharedData\s*\=\s*(.*?)\s*;\s*<\/script>/', '\1', trim($script[0]));
                    break;
                }
            }
        }

        if (!$json_data) {
            throw new \Exception('Failed to extract JSON data');
        }

        $data = json_decode($json_data, true);

        $user = $data['entry_data']['ProfilePage'][0]['graphql']['user'];
        $variables = json_encode(array('id' => $user['id'], 'first' => $max_items, 'after' => null));

        $graphql_query = http_build_query(array(
                'query_hash' => '42323d64886122307be10013ad2dcc44',
                'variables' => $variables
            )
        );

        $url = "https://www.instagram.com/graphql/query/?{$graphql_query}";

        curl_setopt($conn, CURLOPT_URL, $url);
        curl_setopt($conn, CURLOPT_REFERER, "https://www.instagram.com/{$user_name}/");

        $content = curl_exec($conn);

        curl_close($conn);

        return $response = array('user' => $user, 'data' => json_decode($content, true));
    }

    /**
     * Hashes request parameters.
     *
     * @param $params
     * @return string
     */
    protected function hash($params)
    {
        $fields = array($params);

        return md5(serialize($fields));
    }
}
