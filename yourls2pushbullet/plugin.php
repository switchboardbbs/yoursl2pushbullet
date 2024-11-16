<?php
/*
Plugin Name: Yourls2Pushbullet
Plugin URI: https://github.com/yourls-contrib/yourls2pushbullet
Description: Sends notifications to Pushbullet when a link is clicked
Version: 1.05
Author: Switchboard BBS
Author URI: https://switchboardbbs.com/
*/

// No direct call
if (!defined('YOURLS_ABSPATH')) {
    die();
}

function yourls2pushbullet_send_notification($title, $message)
{
    $apiKey = yourls_get_option('yourls2pushbullet_api_key', '');
    $channelTag = yourls_get_option('yourls2pushbullet_channel_tag', '');
    $pushbulletUrl = 'https://api.pushbullet.com/v2/pushes';
    $source = yourls_get_option('yourls2pushbullet_source', '');

    $postData = array(
        'type' => 'note',
        'title' => $title,
        'body' => $message . "\n" . 'Source: ' . $source
    );

    if (!empty($channelTag)) {
        $postData['channel_tag'] = $channelTag;
    }

    $headers = array(
        'Content-Type: application/json',
        'Access-Token: ' . $apiKey
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $pushbulletUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!empty($response)) {
        trigger_error('Failed to send Pushbullet notification!');
    }
}

yourls_add_action('redirect_shorturl', 'yourls2pushbullet_redirect_shorturl');

function yourls2pushbullet_redirect_shorturl($args)
{
    $shortUrl = $args[1];
    $redirectedUrl = $args[0];

    $title = 'New click at ' . date('Y-m-d H:i:s') . "\n";
    $message = "ShortURL: $shortUrl\n\nRedirected to: $redirectedUrl\n";

    yourls2pushbullet_send_notification($title, $message);
}

yourls_add_action('plugins_loaded', 'yourls2pushbullet_loaded');
function yourls2pushbullet_loaded()
{
    yourls_register_plugin_page('yourls2pushbullet_settings', 'Yourls2Pushbullet Settings', 'yourls2pushbullet_register_settings_page');
}

function yourls2pushbullet_register_settings_page()
{
    if (isset($_POST['yourls2pushbullet_api_key'])) {
        yourls_verify_nonce('yourls2pushbullet_settings');

        $api_key = $_POST['yourls2pushbullet_api_key'];
        $source = $_POST['yourls2pushbullet_source'];
        $channelTag = $_POST['yourls2pushbullet_channel_tag'];

        yourls_update_option('yourls2pushbullet_api_key', $api_key);
        yourls_update_option('yourls2pushbullet_source', $source);
        yourls_update_option('yourls2pushbullet_channel_tag', $channelTag);
    }

    $api_key = yourls_get_option('yourls2pushbullet_api_key', '');
    $source = yourls_get_option('yourls2pushbullet_source', '');
    $channelTag = yourls_get_option('yourls2pushbullet_channel_tag', '');
    $nonce = yourls_create_nonce('yourls2pushbullet_settings');

    echo <<<HTML
        <main>
            <h2>Yourls2Pushbullet Settings</h2>
            <form method="post">
            <input type="hidden" name="nonce" value="$nonce" />
            <p>
                <label>Secret Access Token:</label>
                <p style="color:#adadad">Your Secret Access Token can be found in your Pushbullet Profile > Settings </p>
                <input type="text" name="yourls2pushbullet_api_key" value="$api_key" />
            </p>
            <p>
                <label>Channel Tag:</label>
                <p style="color:#adadad;">Use this to post to a specific Pushbullet channel</p>
                <input type="text" name="yourls2pushbullet_channel_tag" value="$channelTag" />
            </p>
            <p>
                <label>Source name:</label>
                <p style="color:#adadad;">For attribution purposes (useful if you have multiple YOURLs instances directing to a single Pushbullet account)</p>
                <input type="text" name="yourls2pushbullet_source" value="$source" />
            </p>
            <p>
                <input type="submit" value="Save Settings" />
            </p>
            </form>
        </main>
    HTML;
}
