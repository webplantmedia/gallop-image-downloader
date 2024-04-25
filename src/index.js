import { registerPlugin } from "@wordpress/plugins";
import { PluginDocumentSettingPanel } from "@wordpress/edit-post";
import { Button } from "@wordpress/components";
import { useSelect, useDispatch } from "@wordpress/data";

const DownloadImagesButton = () => {
	const postId = wp.data.select("core/editor").getCurrentPostId();
	const meta = useSelect((select) =>
		select("core/editor").getEditedPostAttribute("meta"),
	);
	const { editPost } = useDispatch("core/editor");

	const downloadImages = async () => {
		const response = await wp.apiFetch({
			path: `/wp/v2/posts/${postId}/download_images`, // Custom REST route
			method: "POST",
		});

		if (response?.success && response?.url) {
			window.location.href = response.url;
			editPost({ meta: { gallop_zip_file: response.filename } });
		} else {
			console.log("Error downloading images.");
		}
	};

	const deleteImage = async () => {
		const response = await wp.apiFetch({
			path: `/wp/v2/posts/${postId}/delete_image`,
			method: "DELETE",
		});

		if (response.success) {
			editPost({ meta: { gallop_zip_file: "" } });
		} else {
			console.log("Error deleting the file.");
		}
	};

	return (
		<PluginDocumentSettingPanel
			name="gallop-image-downloader"
			title="Download Images"
			className="gallop-image-downloader"
		>
			<p>Download all attached images.</p>
			<Button isPrimary onClick={downloadImages}>
				Download Now
			</Button>
			{meta.gallop_zip_file && (
				<div>
					<a href="#" onClick={deleteImage}>
						{meta.gallop_zip_file}
					</a>
					<Button isDestructive onClick={deleteImage}>
						X
					</Button>
				</div>
			)}
		</PluginDocumentSettingPanel>
	);
};

registerPlugin("gallop-image-downloader", {
	render: DownloadImagesButton,
});
