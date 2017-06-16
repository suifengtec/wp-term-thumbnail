# wp-term-thumbnail


This is a WordPress plugin.

it adds the thumbnail feature to your terms,  you can also upload image in base64 format via it.

# Usage


Get a term thumbnail  to show it:

```
WP_Term_Thumbnail_Module_Utils::get_term_thumbnail( $term_id, $size = 'post-thumbnail', $attr = '' )

```

Get a term thumbnail URL:

```
WP_Term_Thumbnail_Module_Utils::get_term_thumbnail_url( $term_id, $size='full')

```


Set a thumbnail for a term:

```

WP_Term_Thumbnail_Module_Utils::set_term_thumbnail( $term_id, $thumbnail_id )

```
Upload an image in base64 format and set as a term thumbnail:

```

WP_Term_Thumbnail_Module_Utils::upload_image_base64( $img_base64Str, $objectType='term',$objectID=0 )

```

the above `$objectType` may be `term` or `post` or `user`, then you can use the following action hooks to do something：

```

// $objectType = 'post';

do_action('wp_term_thumbnail_after_image_uploaded_set_post_thumbnail', $objectTypeID, $attach_id);


// $objectType = 'user';

do_action('wp_term_thumbnail_after_image_uploaded_set_user_avatar', $objectTypeID, $attach_id);

```


Delete a term thumbnail:

```
WP_Term_Thumbnail_Module_Utils::delete_term_thumbnail( $term_id )

```
