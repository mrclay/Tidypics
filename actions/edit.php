<?php

	/**
	 * Tidypics edit album/image action
	 * 
	 */
	 
	// Make sure we're logged in (send us to the front page if not)
	if (!isloggedin()) forward();

	// Get input data
	$guid    = (int) get_input('guid');  // guid of image or album
	$title   = get_input('tidypicstitle');
	$body    = get_input('tidypicsbody');
	$access  = get_input('access_id');
	$tags    = get_input('tidypicstags');
	$subtype = get_input('subtype');
	
	$container_guid = get_input('container_guid');

	// Make sure we actually have permission to edit
	$entity = get_entity($guid);
	if (!$entity->canEdit()) {
		forward();
	}

	// Get owning user/group
	$owner = get_entity($entity->getOwner());

	// change access only if access is different from current
	if ($subtype == 'album' && $entity->access_id != $access) {
		$entity->access_id = $access;
	
		//get images from album and update access on image entities
		$images = get_entities("object","image", $guid, '', 999, '', false);
		foreach ($images as $im) {
			$im->access_id = $access;
			$im->save();
		}
	}


	// Set its title and description appropriately
	$entity->title = $title;
	$entity->description = $body;

	// Before we can set metadata, we need to save the entity
	if (!$entity->save()) {
		register_error(elgg_echo("album:error"));
		$entity->delete();
		forward($_SERVER['HTTP_REFERER']); //failed, so forward to previous page
	}

	// Now let's add tags
	$tagarray = string_to_tag_array($tags);
	$entity->clearMetadata('tags');
	if (is_array($tagarray)) {
		$entity->tags = $tagarray;
	}

	//if cover meta is sent from image save as metadata
	if ($subtype == 'image' && get_input('cover') == elgg_echo('album:cover:yes')) {
		$album = get_entity($container_guid); 
		$album->cover = $entity->guid;
	}

	// Success message
	if ($subtype == 'album')
		system_message(elgg_echo("album:edited"));
	else
		system_message(elgg_echo('images:edited'));

	forward($entity->getURL());
?>