@if ($app_is_not_demo && auth()->check())
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			fetch('/vip-intercom-partial')
				.then(response => response.text())
				.then(html => {
					const container = document.getElementById('vip-intercom-container');
					if (container) {
						container.innerHTML = html;
					}

					const temp = document.createElement('div');
					temp.innerHTML = html;
					const scripts = temp.getElementsByTagName('script');

					for (let script of scripts) {
						const newScript = document.createElement('script');
						if (script.src) {
							newScript.src = script.src;
						} else {
							newScript.textContent = script.textContent;
						}
						document.body.appendChild(newScript);
					}

					if (typeof Alpine !== 'undefined') {
						Alpine.start();
					} else if (typeof window.Alpine !== 'undefined') {
						window.Alpine.start();
					}

					setTimeout(() => {
						if (typeof Alpine !== 'undefined') {
							Alpine.initTree(container);
						} else if (typeof window.Alpine !== 'undefined') {
							window.Alpine.initTree(container);
						}
					}, 100);
				});
		});
	</script>

	<div id="vip-intercom-container">

	</div>
@endif
