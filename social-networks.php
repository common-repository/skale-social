<?php

function wpw_sds_social_networks(){
    $arr = array();

    $arr[] = array(
        "name"      => "facebook",
        "label"      => "Facebook",
        "url"       => "http://www.facebook.com/sharer.php?u=__SHARE_URL__",
        "icon"      => "fa-facebook",
        "color"     => "#3B5998"
    );

    $arr[] = array(
        "name"      => "twitter",
        "label"      => "Twitter",
        "url"       => "http://twitter.com/share?url=__SHARE_URL__",
        "icon"      => "fa-twitter",
        "color"     => "#1da1f3"
    );

    $arr[] = array(
        "name"      => "google",
        "label"      => "Google+",
        "url"       => "https://plus.google.com/share?url=__SHARE_URL__",
        "icon"      => "fa-google-plus",
        "color"     => "#d34836"
    );

    $arr[] = array(
        "name"      => "pinterest",
        "label"      => "Pinterest",
        "url"       => "http://pinterest.com/pin/create/bookmarklet/?is_video=false&url=__SHARE_URL__&media=__POST_THUMB__&description=__PAGE_TITLE__",
        "icon"      => "fa-pinterest",
        "color"     => "#d01e1b"
    );

    $arr[] = array(
        "name"      => "linkedin",
        "label"      => "Linkedin",
        "url"       => "http://www.linkedin.com/shareArticle?mini=true&amp;url=__SHARE_URL__",
        "icon"      => "fa-linkedin",
        "color"     => "#0077b5"
    );

    $arr[] = array(
        "name"      => "tumblr",
        "label"      => "Tumblr",
        "url"       => "https://www.tumblr.com/widgets/share/tool?canonicalUrl=__SHARE_URL__",
        "icon"      => "fa-tumblr",
        "color"     => "#36465d"
    );

    $arr[] = array(
        "name"      => "reddit",
        "label"      => "Reddit",
        "url"       => "http://www.reddit.com/submit?url=__SHARE_URL__&title=__PAGE_TITLE__",
        "icon"      => "fa-reddit-alien",
        "color"     => "#ff5700"
    );

    $newArr = array();

    foreach ($arr as $item) {
        $newArr[$item["name"]] = $item;
    }

    return $newArr;

}
