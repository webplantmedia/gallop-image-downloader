<?php

/**
 * Plugin Name:       Gallop Image Downloader
 * Description:       Example block scaffolded with Create Block tool.
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gallop-image-downloader
 *
 * @package CreateBlock
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function create_block_gallop_image_downloader_block_init()
{
	register_block_type(__DIR__ . '/build');
}
add_action('init', 'create_block_gallop_image_downloader_block_init');


function gallop_register_rest_route()
{
	register_rest_route('wp/v2/posts', '/(?P<id>\d+)/download_images_original', array(
		'methods' => 'POST',
		'callback' => 'gallop_handle_download_images_original',
		'permission_callback' => 'gallop_download_images_permissions_check',
	));
	register_rest_route('wp/v2/posts', '/(?P<id>\d+)/download_images', array(
		'methods' => 'POST',
		'callback' => 'gallop_handle_download_images',
		'permission_callback' => 'gallop_download_images_permissions_check',
	));
	register_rest_route('wp/v2/posts', '/(?P<id>\d+)/delete_image', array(
		'methods' => 'DELETE',
		'callback' => 'gallop_delete_image',
		'permission_callback' => 'gallop_download_images_permissions_check',
	));
}

function gallop_download_images_permissions_check(WP_REST_Request $request)
{
	return current_user_can('edit_post', $request['id']);
}

function gallop_handle_download_images_original(WP_REST_Request $request)
{
	$post_id = $request['id'];
	$images = get_attached_media('image', $post_id);
	$zip = new ZipArchive();
	$uploads_dir = wp_upload_dir();
	$zip_filename = $uploads_dir['path'] . '/images-' . $post_id . '-' . time() . '.zip';
	$zip_basename = basename($zip_filename);

	if ($filename = get_post_meta($post_id, 'gallop_zip_file', true)) {
		$saved_path = $uploads_dir['path'] . '/' . $filename;
		if (file_exists($saved_path)) {
			$saved_basename = basename($saved_path);
			$saved_download_url = $uploads_dir['url'] . '/' . $saved_basename;
			return new WP_REST_Response(array('success' => true, 'url' => $saved_download_url, 'filename' => $saved_basename), 200);
		}
	}

	if ($zip->open($zip_filename, ZipArchive::CREATE) === TRUE) {
		foreach ($images as $image) {
			$file_path = get_attached_file($image->ID);
			$file_path = str_replace('-scaled', '', $file_path);
			$extension = pathinfo($file_path, PATHINFO_EXTENSION);
			$file_path_no_ext = substr($file_path, 0, strrpos($file_path, "."));
			$new_file_path = $file_path_no_ext . '-dwn-original.' . $extension;
			if (file_exists($new_file_path)) {
				$zip->addFile($new_file_path, basename($file_path));
			} else if (file_exists($file_path)) {
				$zip->addFile($file_path, basename($file_path));
			}
		}
		$zip->close();

		if (file_exists($zip_filename)) {
			update_post_meta($post_id, 'gallop_zip_file', $zip_basename);
			$download_url = $uploads_dir['url'] . '/' . $zip_basename;
			return new WP_REST_Response(array('success' => true, 'url' => $download_url, 'filename' => $zip_basename), 200);
		} else {
			return new WP_REST_Response(array('success' => false, 'message' => 'Zip file was not created.'), 500);
		}
	} else {
		return new WP_REST_Response(array('success' => false, 'message' => 'Failed to open zip file for writing.'), 500);
	}
}

function gallop_handle_download_images(WP_REST_Request $request)
{
	$post_id = $request['id'];
	$images = get_attached_media('image', $post_id);
	$zip = new ZipArchive();
	$uploads_dir = wp_upload_dir();
	$zip_filename = $uploads_dir['path'] . '/images-' . $post_id . '-' . time() . '.zip';
	$zip_basename = basename($zip_filename);

	if ($filename = get_post_meta($post_id, 'gallop_zip_file', true)) {
		$saved_path = $uploads_dir['path'] . '/' . $filename;
		if (file_exists($saved_path)) {
			$saved_basename = basename($saved_path);
			$saved_download_url = $uploads_dir['url'] . '/' . $saved_basename;
			return new WP_REST_Response(array('success' => true, 'url' => $saved_download_url, 'filename' => $saved_basename), 200);
		}
	}

	if ($zip->open($zip_filename, ZipArchive::CREATE) === TRUE) {
		foreach ($images as $image) {
			$file_path = get_attached_file($image->ID);
			if (file_exists($file_path)) {
				$zip->addFile($file_path, basename($file_path));
			}
		}
		$zip->close();

		if (file_exists($zip_filename)) {
			update_post_meta($post_id, 'gallop_zip_file', $zip_basename);
			$download_url = $uploads_dir['url'] . '/' . $zip_basename;
			return new WP_REST_Response(array('success' => true, 'url' => $download_url, 'filename' => $zip_basename), 200);
		} else {
			return new WP_REST_Response(array('success' => false, 'message' => 'Zip file was not created.'), 500);
		}
	} else {
		return new WP_REST_Response(array('success' => false, 'message' => 'Failed to open zip file for writing.'), 500);
	}
}

function gallop_delete_image(WP_REST_Request $request)
{
	$post_id = $request['id'];
	$filename = get_post_meta($post_id, 'gallop_zip_file', true);
	echo $filename;
	$uploads_dir = wp_upload_dir();
	$file_path = $uploads_dir['path'] . '/' . $filename;

	if (file_exists($file_path)) {
		unlink($file_path);
		delete_post_meta($post_id, 'gallop_zip_file');
		return new WP_REST_Response(array('success' => true), 200);
	}

	return new WP_REST_Response(array('success' => false, 'message' => 'File not found.'), 404);
}

add_action('rest_api_init', 'gallop_register_rest_route');

function register_gallop_meta()
{
	register_post_meta('post', 'gallop_zip_file', [
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
		'sanitize_callback' => 'gallop_sanitize_zip_file',
		'auth_callback' => function () {
			return current_user_can('edit_posts');
		}
	]);
}
add_action('init', 'register_gallop_meta');

function gallop_sanitize_zip_file($meta_value)
{
	return sanitize_file_name($meta_value);
}
