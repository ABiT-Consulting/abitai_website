<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>worlds first ai erp</title>
	<style>
		:root {
			color-scheme: light;
			font-family: Arial, Helvetica, sans-serif;
		}

		body {
			align-items: center;
			background: #ffffff;
			color: #111111;
			display: flex;
			flex-direction: column;
			gap: 2rem;
			justify-content: center;
			margin: 0;
			min-height: 100vh;
			padding: 2rem;
			text-align: center;
		}

		h1 {
			font-size: clamp(2.5rem, 8vw, 7rem);
			font-weight: 700;
			line-height: 1.05;
			margin: 0;
			max-width: 12ch;
			text-transform: lowercase;
		}

		.chat-box {
			align-items: stretch;
			border: 1px solid #d8d8d8;
			border-radius: 8px;
			display: flex;
			flex-direction: column;
			gap: 0.75rem;
			max-width: 42rem;
			padding: 1rem;
			text-align: left;
			width: min(100%, 42rem);
		}

		.chat-box textarea {
			border: 0;
			color: #111111;
			font: inherit;
			min-height: 5rem;
			outline: none;
			resize: vertical;
			width: 100%;
		}

		.chat-actions {
			align-items: center;
			display: flex;
			gap: 0.75rem;
			justify-content: space-between;
		}

		.image-button,
		.send-button {
			align-items: center;
			background: #111111;
			border: 0;
			border-radius: 6px;
			color: #ffffff;
			cursor: pointer;
			display: inline-flex;
			font: inherit;
			font-weight: 700;
			gap: 0.4rem;
			padding: 0.7rem 0.9rem;
		}

		.image-button {
			background: #f0f0f0;
			color: #111111;
		}

		.image-input {
			height: 1px;
			opacity: 0;
			overflow: hidden;
			position: absolute;
			width: 1px;
		}

		.image-preview {
			display: none;
			gap: 0.5rem;
			grid-template-columns: repeat(auto-fill, minmax(4rem, 1fr));
		}

		.image-preview.has-images {
			display: grid;
		}

		.image-preview img {
			aspect-ratio: 1;
			border: 1px solid #d8d8d8;
			border-radius: 6px;
			object-fit: cover;
			width: 100%;
		}
	</style>
</head>
<body>
	<h1>worlds first ai erp</h1>
	<form class="chat-box" aria-label="Chat message">
		<textarea name="message" placeholder="Type your message"></textarea>
		<div id="image-preview" class="image-preview" aria-live="polite"></div>
		<div class="chat-actions">
			<label class="image-button" for="chat-images">Add images</label>
			<input class="image-input" id="chat-images" name="images[]" type="file" accept="image/*" multiple>
			<button class="send-button" type="submit">Send</button>
		</div>
	</form>
	<script>
		const imageInput = document.getElementById('chat-images');
		const imagePreview = document.getElementById('image-preview');

		imageInput.addEventListener('change', () => {
			imagePreview.textContent = '';
			imagePreview.classList.toggle('has-images', imageInput.files.length > 0);

			Array.from(imageInput.files).forEach((file) => {
				if (!file.type.startsWith('image/')) {
					return;
				}

				const image = document.createElement('img');
				image.alt = file.name;
				image.src = URL.createObjectURL(file);
				image.onload = () => URL.revokeObjectURL(image.src);
				imagePreview.appendChild(image);
			});
		});
	</script>
</body>
</html>
