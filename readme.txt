=== WP Term Thumbnail ===
Contributors: suifengtec
Tags: thumbnail,image,term,taxonomy,category,tag
Requires at least: 4.4
Tested up to: 4.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://coolwp.com


It allows you to add a thumbnail to each term.


You can also upload  an image in base64 format via WP REST API and set as  a term thumbnail or whatever.

== Description ==


Get a term thumbnail  to show it:

`
WP_Term_Thumbnail_Module_Utils::get_term_thumbnail( $term_id, $size = 'post-thumbnail', $attr = '' )

`

Get a term thumbnail URL:

`
WP_Term_Thumbnail_Module_Utils::get_term_thumbnail_url( $term_id, $size='full')

`


Set a thumbnail for a term:

`

WP_Term_Thumbnail_Module_Utils::set_term_thumbnail( $term_id, $thumbnail_id )

`
Upload an image in base64 format and set as a term thumbnail:

`

WP_Term_Thumbnail_Module_Utils::upload_image_base64( $img_base64Str, $objectType='term',$objectID=0 )

`

the above `$objectType` may be `term` or `post` or `user`, then you can use the following action hooks to do something：

`

// $objectType = 'post';

do_action('wp_term_thumbnail_after_image_uploaded_set_post_thumbnail', $objectTypeID, $attach_id);


// $objectType = 'user';

do_action('wp_term_thumbnail_after_image_uploaded_set_user_avatar', $objectTypeID, $attach_id);

`


Delete a term thumbnail:

`
WP_Term_Thumbnail_Module_Utils::delete_term_thumbnail( $term_id )

`



== Installation ==


= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for 'WP Term Thumbnail'
3. Activate WP Term Thumbnail from your Plugins page. 

= From WordPress.org =

1. Download WP Term Thumbnail.
2. Upload the 'wp-term-thumbnail' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate WP Term Thumbnail from your Plugins page.



== Frequently Asked Questions ==

none.

== Screenshots ==

1. set a term thumbnail when adding it;
2. add/edit a/the term thumbnail when editing term;
3. thumbnail column in term list.


== Upgrade Notice ==

none.

== Changelog ==

= 1.0.0 =
initial releases
