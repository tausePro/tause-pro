<style>
	[x-cloak] { display: none !important; }
</style>

@if(\App\Helpers\Classes\MarketplaceHelper::isRegistered('content-manager') && setting('content_manager_enabled', '1') === '1')
	{{-- Include the Media Manager Modal Component --}}
	@livewire('media-manager-modal')
	{{-- Media Manager JavaScript --}}
	<script>
		// Make content manager availability global
		window.contentManagerEnabled = true;
		// Store selected media data globally
		window.selectedMediaData = new Map();
		// Global media manager function
		window.openMediaManager = function(targetInput, fileTypes = ['all'], isMultiple = false) {
			if (targetInput) {
				window.currentFileInput = targetInput;
			}
			Livewire.dispatch('openMediaManager', {
				allowedTypes: fileTypes,
				isMultiple: isMultiple,
			});
			return false;
		};
		// Auto-enhance all file inputs when DOM is ready
		document.addEventListener('DOMContentLoaded', function() {
			initializeMediaManagerForFileInputs();
			observeNewFileInputs();
		});

		// Initialize existing file inputs
		function initializeMediaManagerForFileInputs() {
			const fileInputs = document.querySelectorAll('input[type="file"]:not([data-media-manager-enabled])');
			fileInputs.forEach(function(input) {
				enhanceFileInputWithMediaManager(input);
			});
		}

		// Enhance a single file input
		function enhanceFileInputWithMediaManager(input) {
			// Skip if already enhanced
			if (input.hasAttribute('data-media-manager-enabled') || input.hasAttribute('data-exclude-media-manager')) {
				return;
			}
			// Mark as enhanced
			input.setAttribute('data-media-manager-enabled', 'true');
			// Store original onclick if exists
			const originalOnClick = input.onclick;
			// Add click handler
			input.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				// Execute original onclick if it exists
				if (originalOnClick && typeof originalOnClick === 'function') {
					const result = originalOnClick.call(this, e);
					// If original onclick returns false, don't proceed
					if (result === false) {
						return false;
					}
				}
				// Store reference and open media manager
				window.currentFileInput = this;
				const allowedType = this.dataset.mediaType || this.accept;
				const isMultiple = this.multiple || this.hasAttribute('multiple') || this.dataset.multiple === 'true';

				let allowedTypes = [];
				if (allowedType.includes('image') || allowedType.includes('.png') || allowedType.includes('.jpg') || allowedType.includes('.jpeg') || allowedType.includes('.gif')) {
					allowedTypes.push('image');
				}
				if (allowedType.includes('video') || allowedType.includes('.mp4') || allowedType.includes('.webm') || allowedType.includes('.avi')) {
					allowedTypes.push('video');
				}
				if (allowedType.includes('application') || allowedType.includes('file') || allowedType.includes('.pdf') || allowedType.includes('.doc')) {
					allowedTypes.push('file');
				}
				if (allowedTypes.length === 0) allowedTypes = ['all'];
				window.openMediaManager(this, allowedTypes, isMultiple);
				return false;
			});
		}
		// Watch for dynamically added file inputs
		function observeNewFileInputs() {
			const observer = new MutationObserver(function(mutations) {
				mutations.forEach(function(mutation) {
					mutation.addedNodes.forEach(function(node) {
						if (node.nodeType === 1) { // Element node
							// Check if the added node is a file input
							if (node.matches && node.matches('input[type="file"]')) {
								enhanceFileInputWithMediaManager(node);
							}
							// Check for file inputs within the added node
							if (node.querySelectorAll) {
								const fileInputs = node.querySelectorAll('input[type="file"]');
								if (fileInputs.length > 0) {
									fileInputs.forEach(enhanceFileInputWithMediaManager);
								}
							}
						}
					});
				});
			});
			observer.observe(document.body, {
				childList: true,
				subtree: true
			});
		}

		// Handle media selection from the modal
		document.addEventListener('livewire:init', function() {
			Livewire.on('mediaSelected', function(eventData) {
				// Extract data from array if needed
				const data = Array.isArray(eventData) ? eventData[0] : eventData;
				if (window.currentFileInput && data && data.items) {
					// Convert items to array if it's an object
					let items = data.items;
					if (typeof items === 'object' && !Array.isArray(items)) {
						items = Object.values(items);
					}
					// Store the selected media data
					const inputKey = window.currentFileInput.name || 'default';
					window.selectedMediaData.set(inputKey, items);
					// Create File objects and update input.files
					const input = window.currentFileInput;
					const dataTransfer = new DataTransfer();
					Promise.all(
						items.map(async (item, index) => {
							try {
								const response = await fetch(item.url);
								const blob = await response.blob();

								const file = new File([blob], item.title || `file-${index}`, {
									type: blob.type,
									lastModified: Date.now()
								});

								dataTransfer.items.add(file);
							} catch (error) {
								console.error(`❌ Failed to load file from URL: ${item.url}`, error);
							}
						})
					).then(() => {
						// Set files on the original input
						input.files = dataTransfer.files;

						// Trigger change and input events
						input.dispatchEvent(new Event('change', { bubbles: true }));
						input.dispatchEvent(new Event('input', { bubbles: true }));
					});

					// Create hidden inputs for regular form submission (optional)
					createHiddenInputsForSelectedMedia(window.currentFileInput, items);

					// Dispatch custom event
					const event = new CustomEvent('mediaManagerSelection', {
						detail: {
							input: input,
							selectedItems: items,
							type: data.type
						}
					});
					input.dispatchEvent(event);

					// Clear reference
					window.currentFileInput = null;
				} else {
					console.warn('⚠️ Media selection event missing required data:', {
						hasCurrentInput: !!window.currentFileInput,
						hasData: !!data,
						hasItems: !!(data && data.items),
						dataStructure: data
					});
				}
			});
		});

		// Create hidden inputs for form submission
		async function createHiddenInputsForSelectedMedia(input, items) {
			// Remove existing _media_manager[] hidden inputs if any
			const existingHiddenInputs = input.parentNode.querySelectorAll(`input[name="${input.name}_media_manager[]"]`);
			existingHiddenInputs.forEach(hidden => hidden.remove());

			// Clear previous files from original input
			input.value = '';
			input.removeAttribute('data-using-media-manager');

			// Clean up previously injected file inputs
			const oldClones = input.form?.querySelectorAll(`input[type="file"][data-cloned="true"]`) || [];
			oldClones.forEach(el => el.remove());

			// Loop through media items and create new hidden file inputs
			for (const [index, item] of items.entries()) {
				try {
					const response = await fetch(item.url);
					const blob = await response.blob();

					const file = new File([blob], item.title || `file-${index}`, {
						type: blob.type,
						lastModified: Date.now()
					});

					// Create a new hidden file input element
					const fakeInput = document.createElement('input');
					fakeInput.type = 'file'; // You can also set it to 'hidden' below
					fakeInput.name = input.name;
					fakeInput.setAttribute('data-cloned', 'true');
					fakeInput.style.display = 'none'; // 👈 Hide it visually

					// Use DataTransfer to assign files
					const dt = new DataTransfer();
					dt.items.add(file);
					fakeInput.files = dt.files;

					// Append to form
					if (input.form) {
						input.form.appendChild(fakeInput);
					} else {
						input.parentNode.appendChild(fakeInput);
					}
				} catch (error) {
					console.error(`❌ Failed to load file from URL: ${item.url}`, error);
				}
			}
		}

		// Add form submission handler to log what's being sent
		document.addEventListener('submit', function(e) {
			const form = e.target;
			const formData = new FormData(form);
			for (let [key, value] of formData.entries()) {
				if (value instanceof File) {
					// console.log(`  ${key}: [File] ${value.name} (${value.size} bytes)`);
				} else {
					// console.log(`  ${key}: ${value}`);
				}
			}

			// Check for media manager data
			const mediaManagerInputs = form.querySelectorAll('input[name*="_media_manager"]');
			if (mediaManagerInputs.length > 0) {
				mediaManagerInputs.forEach(input => {
					try {
						const data = JSON.parse(input.value);
						// console.log(`  ${input.name}:`, data);
					} catch (e) {
						// console.log(`  ${input.name}: ${input.value}`);
					}
				});
			}
		});
	</script>
@else
	{{-- Content manager not registered - use regular file inputs --}}
	<script>
		window.contentManagerEnabled = false;
		window.openMediaManager = function() {
			return true;
		};
	</script>
@endif
